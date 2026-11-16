<?php

function upload_filename_error(array $file, array $extensions): string
{
    $name = $file['name'] ?? null;
    if (!is_string($name) || !is_string($file['tmp_name'] ?? null)
        || !is_int($file['error'] ?? null) || !is_int($file['size'] ?? null)) {
        return 'Berkas tidak dapat diproses. Pilih ulang berkas Anda.';
    }
    if ($name === '' && $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    $fullPath = $file['full_path'] ?? $name;
    if (!is_string($fullPath) || $fullPath !== $name || !mb_check_encoding($name, 'UTF-8')
        || mb_strlen($name, 'UTF-8') > 255 || preg_match('/[\x00-\x1f\x7f\/\\\\]/', $name)
        || str_starts_with($name, '.')
        || preg_match('/\.(?:php[0-9]*|phtml|pht|phar|phps|cgi|pl|py|rb|asp|aspx|jsp|shtml|sh|ps1|vbs|wsf|exe|com|bat|cmd)(?:\.|$)/i', $name)
        || !in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $extensions, true)) {
        return 'Nama atau ekstensi file upload tidak diizinkan.';
    }
    return '';
}

function rollback_uploaded_files(): void
{
    if (!empty($GLOBALS['media_commit_in_progress'])) {
        $GLOBALS['pending_uploaded_files'] = [];
        return;
    }
    foreach ($GLOBALS['pending_uploaded_files'] ?? [] as $path => $unused) {
        if (is_file($path) && !@unlink($path)) {
            error_log('Upload cleanup deferred for an owned temporary media file.');
        } elseif (!file_exists($path)) {
            try {
                media_cleanup_forget(str_replace('\\', '/', ltrim(substr($path, strlen(APP_ROOT)), '/\\')));
            } catch (Throwable $exception) {
                error_log('Upload cleanup journal retained for retry.');
            }
        }
    }
    $GLOBALS['pending_uploaded_files'] = [];
}

function commit_uploaded_files(): void
{
    foreach ($GLOBALS['pending_uploaded_files'] ?? [] as $path => $unused) {
        try {
            media_cleanup_forget(str_replace('\\', '/', ltrim(substr($path, strlen(APP_ROOT)), '/\\')));
        } catch (Throwable $exception) {
            error_log('Upload cleanup journal retained; persisted references remain protected.');
        }
    }
    $GLOBALS['pending_uploaded_files'] = [];
    $GLOBALS['media_commit_in_progress'] = false;
}

function reserve_uploaded_file(string $path): bool
{
    $root = APP_ROOT;
    $directory = realpath(dirname($path));
    $allowed = false;
    foreach (['/public/uploads', '/storage/uploads'] as $suffix) {
        $allowedRoot = realpath($root . $suffix);
        if ($allowedRoot && $root && str_starts_with($allowedRoot, $root . DIRECTORY_SEPARATOR)
            && !is_link($root . $suffix) && $directory && ($directory === $allowedRoot
            || str_starts_with($directory, $allowedRoot . DIRECTORY_SEPARATOR))) {
            $allowed = true;
        }
    }
    if (!$allowed || is_link($path)) {
        return false;
    }
    $path = $directory . DIRECTORY_SEPARATOR . basename($path);
    $relative = str_replace('\\', '/', ltrim(substr($path, strlen($root)), '/\\'));
    if (managed_media_path($relative) === null) {
        return false;
    }
    try {
        media_lifecycle_lock();
    } catch (Throwable $exception) {
        return false;
    }
    $handle = @fopen($path, 'xb');
    if ($handle === false) {
        return false;
    }
    fclose($handle);
    try {
        media_cleanup_enqueue($relative);
    } catch (Throwable $exception) {
        @unlink($path);
        return false;
    }
    static $registered = false;
    if (!$registered) {
        register_shutdown_function('rollback_uploaded_files');
        $registered = true;
    }
    $GLOBALS['pending_uploaded_files'][$path] = true;
    return true;
}

function upload_image_fits_memory(int $width, int $height): bool
{
    if ($width < 400 || $height < 300 || $width > 10000 || $height > 10000 || $width * $height > 20000000) {
        return false;
    }
    $limit = trim(ini_get('memory_limit'));
    $bytes = (int) $limit;
    $suffix = strtolower(substr($limit, -1));
    $bytes *= match ($suffix) { 'g'=>1073741824, 'm'=>1048576, 'k'=>1024, default=>1 };
    $available = $bytes < 0 ? 268435456 : $bytes - memory_get_usage(true);
    return $width * $height * 8 + 24 * 1048576 <= $available;
}

function upload_webp_container_is_complete(string $path): bool
{
    $bytes = @file_get_contents($path);
    if (!is_string($bytes) || strlen($bytes) < 20 || substr($bytes, 0, 4) !== 'RIFF' || substr($bytes, 8, 4) !== 'WEBP'
        || unpack('Vsize', substr($bytes, 4, 4))['size'] !== strlen($bytes) - 8) {
        return false;
    }
    $offset = 12;
    while ($offset + 8 <= strlen($bytes)) {
        $size = unpack('Vsize', substr($bytes, $offset + 4, 4))['size'];
        $offset += 8 + $size + ($size % 2);
        if ($offset > strlen($bytes)) {
            return false;
        }
    }
    return $offset === strlen($bytes);
}

function uploaded_pdf_has_basic_structure(string $path): bool
{
    $bytes = @file_get_contents($path);
    if (!is_string($bytes) || !preg_match('/\A%PDF-[12]\.[0-9](?:\r\n|\r|\n)/', $bytes)
        || !preg_match('/startxref\s+([0-9]+)\s+%%EOF\s*\z/', $bytes, $match)) {
        return false;
    }
    $offset = database_id($match[1]);
    if ($offset === null || $offset >= strlen($bytes)) {
        return false;
    }
    $xref = substr($bytes, $offset);
    if (preg_match('/\Axref\s/', $xref)) {
        return preg_match('/trailer\s*<<[\s\S]*?\/Root\s+[0-9]+\s+[0-9]+\s+R/', $xref) === 1;
    }
    // PDF 1.5+ can use an xref stream; this is a structural gate, not a full PDF parser.
    return preg_match('/\A[0-9]+\s+[0-9]+\s+obj\s*<<(?:(?!stream)[\s\S])*?\/Type\s*\/XRef\b/', $xref) === 1;
}

function upload_video_uint(string $bytes, int $offset): int
{
    if ($offset < 0 || $offset + 4 > strlen($bytes)) {
        throw new UnexpectedValueException('Incomplete video integer.');
    }
    return unpack('Nvalue', substr($bytes, $offset, 4))['value'];
}

function upload_video_boxes(string $bytes, int $start, int $end, int &$budget): array
{
    $boxes = [];
    while ($start < $end) {
        if (--$budget < 0 || $end - $start < 8) { throw new UnexpectedValueException('Invalid video box.'); }
        $length = upload_video_uint($bytes, $start);
        $header = 8;
        if ($length === 1) {
            if ($end - $start < 16 || upload_video_uint($bytes, $start + 8) !== 0) {
                throw new UnexpectedValueException('Invalid extended video box.');
            }
            $length = upload_video_uint($bytes, $start + 12);
            $header = 16;
        } elseif ($length === 0) {
            $length = $end - $start;
        }
        if ($length < $header || $length > $end - $start) { throw new UnexpectedValueException('Truncated video box.'); }
        $boxes[] = ['type' => substr($bytes, $start + 4, 4), 'start' => $start,
            'data' => $start + $header, 'end' => $start + $length];
        $start += $length;
    }
    return $boxes;
}

function upload_video_box(array $boxes, string $type): array
{
    $matches = array_values(array_filter($boxes, static fn($box) => $box['type'] === $type));
    if (count($matches) !== 1) { throw new UnexpectedValueException('Missing or duplicate video metadata.'); }
    return $matches[0];
}

function upload_video_payload(string $bytes, array $box, int $minimum): string
{
    if ($box['end'] - $box['data'] < $minimum) { throw new UnexpectedValueException('Incomplete video metadata.'); }
    return substr($bytes, $box['data'], $box['end'] - $box['data']);
}

function upload_video_media_range(array $media, int $offset, int $length): bool
{
    foreach ($media as $box) {
        if ($length > 0 && $offset >= $box['data'] && $offset <= $box['end']
            && $length <= $box['end'] - $offset) { return true; }
    }
    return false;
}

function upload_video_table(string $bytes, array $box, int $width): array
{
    $data = upload_video_payload($bytes, $box, 8);
    $count = upload_video_uint($data, 4);
    if (substr($data, 0, 4) !== "\0\0\0\0" || $count > 250000 || strlen($data) !== 8 + $width * $count) {
        throw new UnexpectedValueException('Invalid video sample table.');
    }
    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $row = [];
        for ($j = 0; $j < $width; $j += 4) { $row[] = upload_video_uint($data, 8 + $i * $width + $j); }
        $rows[] = $row;
    }
    return $rows;
}

function upload_video_sample(string $bytes, int $offset, int $length, array $description): bool
{
    $prefix = $description['prefix'];
    if ($prefix === 0) { return true; }
    $end = $offset + $length;
    $picture = false;
    while ($offset < $end) {
        if ($end - $offset < $prefix) { return false; }
        $n = 0;
        for ($i = 0; $i < $prefix; $i++) { $n = ($n << 8) | ord($bytes[$offset++]); }
        if ($n < ($description['hevc'] ? 2 : 1) || $n > $end - $offset) { return false; }
        $header = ord($bytes[$offset]);
        if ($header & 0x80) { return false; }
        if ($description['hevc']) {
            $type = ($header >> 1) & 0x3f;
            if ($type > 40 || !(ord($bytes[$offset + 1]) & 7)) { return false; }
            $picture = $picture || $type <= 31;
        } else {
            $type = $header & 31;
            if ($type < 1 || $type > 23) { return false; }
            $picture = $picture || ($type >= 1 && $type <= 5);
        }
        $offset += $n;
    }
    return $picture;
}

function upload_video_track(string $bytes, array $track, array $media, int &$budget, int &$sampleBudget): array
{
    $children = upload_video_boxes($bytes, $track['data'], $track['end'], $budget);
    $tkhd = upload_video_payload($bytes, upload_video_box($children, 'tkhd'), 84);
    $version = ord($tkhd[0]);
    if (!in_array($version, [0, 1], true) || strlen($tkhd) < ($version === 1 ? 96 : 84)) {
        throw new UnexpectedValueException('Invalid track header.');
    }
    $id = upload_video_uint($tkhd, $version === 1 ? 20 : 12);
    if ($id < 1) { throw new UnexpectedValueException('Invalid track ID.'); }
    $mdia = upload_video_box($children, 'mdia');
    $children = upload_video_boxes($bytes, $mdia['data'], $mdia['end'], $budget);
    $hdlr = upload_video_payload($bytes, upload_video_box($children, 'hdlr'), 24);
    $mdhd = upload_video_payload($bytes, upload_video_box($children, 'mdhd'), 24);
    $version = ord($mdhd[0]);
    if (!in_array($version, [0, 1], true) || strlen($mdhd) < ($version === 1 ? 36 : 24)
        || upload_video_uint($mdhd, $version === 1 ? 20 : 12) === 0) {
        throw new UnexpectedValueException('Invalid media clock.');
    }
    if (substr($hdlr, 8, 4) !== 'vide') { return ['id' => $id, 'video' => false]; }
    $minf = upload_video_box($children, 'minf');
    $children = upload_video_boxes($bytes, $minf['data'], $minf['end'], $budget);
    $stbl = upload_video_box($children, 'stbl');
    $tables = upload_video_boxes($bytes, $stbl['data'], $stbl['end'], $budget);
    $stsd = upload_video_box($tables, 'stsd');
    $description = upload_video_payload($bytes, $stsd, 8);
    $entries = upload_video_boxes($bytes, $stsd['data'] + 8, $stsd['end'], $budget);
    if (substr($description, 0, 4) !== "\0\0\0\0" || !$entries
        || count($entries) !== upload_video_uint($description, 4)) { throw new UnexpectedValueException('Invalid video description.'); }
    $codecs = [];
    foreach ($entries as $entry) {
        $visual = upload_video_payload($bytes, $entry, 78);
        if (unpack('nvalue', substr($visual, 6, 2))['value'] < 1
            || unpack('nvalue', substr($visual, 24, 2))['value'] < 1
            || unpack('nvalue', substr($visual, 26, 2))['value'] < 1) {
            throw new UnexpectedValueException('Invalid visual sample entry.');
        }
        // QuickTime visual sample descriptions have the same fixed 78-byte prefix.
        $extensions = upload_video_boxes($bytes, $entry['data'] + 78, $entry['end'], $budget);
        $configType = match ($entry['type']) { 'avc1', 'avc3' => 'avcC', 'hvc1', 'hev1' => 'hvcC',
            'av01' => 'av1C', 'vp09' => 'vpcC', 'mp4v' => 'esds', default => null };
        $codec = ['prefix' => 0, 'hevc' => false];
        if ($configType !== null) {
            $config = upload_video_payload($bytes, upload_video_box($extensions, $configType), $configType === 'hvcC' ? 23 : 4);
            if (in_array($configType, ['avcC', 'hvcC'], true) && ord($config[0]) !== 1) {
                throw new UnexpectedValueException('Invalid codec configuration.');
            }
            if ($configType === 'avcC') {
                if (strlen($config) < 7) { throw new UnexpectedValueException('Incomplete AVC configuration.'); }
                $codec['prefix'] = (ord($config[4]) & 3) + 1;
                if ($codec['prefix'] === 3) { throw new UnexpectedValueException('Invalid AVC length prefix.'); }
                $cursor = 6; $spsCount = ord($config[5]) & 31;
                foreach ([$spsCount, null] as $group => $count) {
                    if ($group === 1) {
                        if ($cursor >= strlen($config)) { throw new UnexpectedValueException('Missing AVC picture configuration.'); }
                        $count = ord($config[$cursor++]);
                    }
                    if ($entry['type'] === 'avc1' && $count === 0) { throw new UnexpectedValueException('Missing AVC parameter sets.'); }
                    for ($i = 0; $i < $count; $i++) {
                        if ($cursor + 2 > strlen($config)) { throw new UnexpectedValueException('Truncated AVC parameter set.'); }
                        $n = unpack('nvalue', substr($config, $cursor, 2))['value']; $cursor += 2;
                        if ($n < 1 || $n > strlen($config) - $cursor || (ord($config[$cursor]) & 31) !== ($group === 0 ? 7 : 8)) {
                            throw new UnexpectedValueException('Invalid AVC parameter set.');
                        }
                        $cursor += $n;
                    }
                }
            } elseif ($configType === 'hvcC') {
                $codec = ['prefix' => (ord($config[21]) & 3) + 1, 'hevc' => true];
                if ($codec['prefix'] === 3) { throw new UnexpectedValueException('Invalid HEVC length prefix.'); }
            }
        }
        $codecs[] = $codec;
    }
    $sizes = upload_video_payload($bytes, upload_video_box($tables, 'stsz'), 12);
    $fixed = upload_video_uint($sizes, 4); $count = upload_video_uint($sizes, 8);
    $sampleBudget -= $count;
    if (substr($sizes, 0, 4) !== "\0\0\0\0" || $count > 250000
        || $sampleBudget < 0 || strlen($sizes) !== 12 + ($fixed === 0 ? 4 * $count : 0)) { throw new UnexpectedValueException('Invalid video sample sizes.'); }
    $timing = upload_video_table($bytes, upload_video_box($tables, 'stts'), 8);
    $sampleCount = 0;
    foreach ($timing as [$n, $duration]) {
        if ($n < 1 || $duration < 1) { throw new UnexpectedValueException('Invalid sample timing.'); }
        $sampleCount += $n;
    }
    if ($sampleCount !== $count) { throw new UnexpectedValueException('Inconsistent sample count.'); }
    $mapping = upload_video_table($bytes, upload_video_box($tables, 'stsc'), 12);
    $offsetBoxes = array_values(array_filter($tables, static fn($box) => in_array($box['type'], ['stco', 'co64'], true)));
    if (count($offsetBoxes) !== 1) { throw new UnexpectedValueException('Invalid chunk offset table.'); }
    $offsets = upload_video_table($bytes, $offsetBoxes[0], $offsetBoxes[0]['type'] === 'co64' ? 8 : 4);
    if ($count === 0) {
        if ($mapping || $offsets) { throw new UnexpectedValueException('Invalid empty track tables.'); }
        return ['id' => $id, 'video' => true, 'samples' => 0, 'descriptions' => count($entries), 'codecs' => $codecs];
    }
    if (!$mapping || !$offsets || $mapping[0][0] !== 1) { throw new UnexpectedValueException('Missing video chunks.'); }
    $last = 0;
    foreach ($mapping as [$first, $n, $index]) {
        if ($first <= $last || $first > count($offsets) || $n < 1 || $index < 1 || $index > count($entries)) {
            throw new UnexpectedValueException('Invalid chunk mapping.');
        }
        $last = $first;
    }
    $sample = 0; $map = 0;
    foreach ($offsets as $chunk => $parts) {
        if (count($parts) === 2 && $parts[0] !== 0) { throw new UnexpectedValueException('Chunk outside upload.'); }
        $offset = $parts[count($parts) - 1];
        while (isset($mapping[$map + 1]) && $mapping[$map + 1][0] <= $chunk + 1) { $map++; }
        $length = 0;
        for ($i = 0; $i < $mapping[$map][1]; $i++) {
            if ($sample >= $count) { throw new UnexpectedValueException('Too many chunk samples.'); }
            $n = $fixed ?: upload_video_uint($sizes, 12 + 4 * $sample);
            if ($n < 1) { throw new UnexpectedValueException('Empty video sample.'); }
            if (!upload_video_media_range($media, $offset + $length, $n)
                || !upload_video_sample($bytes, $offset + $length, $n, $codecs[$mapping[$map][2] - 1])) {
                throw new UnexpectedValueException('Invalid encoded video sample.');
            }
            $length += $n; $sample++;
        }
        if (!upload_video_media_range($media, $offset, $length)) { throw new UnexpectedValueException('Missing chunk media.'); }
    }
    if ($sample !== $count) { throw new UnexpectedValueException('Unmapped video samples.'); }
    return ['id' => $id, 'video' => true, 'samples' => $count, 'descriptions' => count($entries), 'codecs' => $codecs];
}

function upload_video_fragments(string $bytes, array $boxes, array $tracks, array $defaults, array $media, int &$budget, int &$sampleBudget): int
{
    $videoSamples = 0;
    foreach ($boxes as $moof) {
        if ($moof['type'] !== 'moof') { continue; }
        $children = upload_video_boxes($bytes, $moof['data'], $moof['end'], $budget);
        upload_video_payload($bytes, upload_video_box($children, 'mfhd'), 8);
        $previousEnd = $moof['start'];
        foreach ($children as $traf) {
            if ($traf['type'] !== 'traf') { continue; }
            $runs = upload_video_boxes($bytes, $traf['data'], $traf['end'], $budget);
            $header = upload_video_payload($bytes, upload_video_box($runs, 'tfhd'), 8);
            $flags = upload_video_uint($header, 0) & 0xffffff;
            $id = upload_video_uint($header, 4);
            if (ord($header[0]) !== 0 || !isset($tracks[$id], $defaults[$id])) { throw new UnexpectedValueException('Invalid fragment track.'); }
            $cursor = 8; $base = ($flags & 0x20000) ? $moof['start'] : $previousEnd;
            if ($flags & 1) {
                if (upload_video_uint($header, $cursor) !== 0) { throw new UnexpectedValueException('Fragment outside upload.'); }
                $base = upload_video_uint($header, $cursor + 4); $cursor += 8;
            }
            $description = $defaults[$id]['description']; $duration = $defaults[$id]['duration']; $size = $defaults[$id]['size'];
            foreach ([2, 8, 16, 32] as $flag) {
                if (!($flags & $flag)) { continue; }
                $value = upload_video_uint($header, $cursor); $cursor += 4;
                if ($flag === 2) { $description = $value; }
                if ($flag === 8) { $duration = $value; }
                if ($flag === 16) { $size = $value; }
            }
            if ($cursor !== strlen($header) || $description < 1
                || ($tracks[$id]['video'] && $description > $tracks[$id]['descriptions'])) { throw new UnexpectedValueException('Invalid fragment defaults.'); }
            $end = $base;
            foreach ($runs as $run) {
                if ($run['type'] !== 'trun') { continue; }
                $data = upload_video_payload($bytes, $run, 8);
                $flags = upload_video_uint($data, 0) & 0xffffff; $count = upload_video_uint($data, 4); $cursor = 8;
                $sampleBudget -= $count;
                if (ord($data[0]) > 1 || $count > 250000 || $sampleBudget < 0) { throw new UnexpectedValueException('Invalid fragment run.'); }
                if ($flags & 1) {
                    $delta = upload_video_uint($data, $cursor); $cursor += 4;
                    if ($delta > 0x7fffffff) { $delta -= 0x100000000; }
                    $end = $base + $delta;
                }
                if ($flags & 4) { upload_video_uint($data, $cursor); $cursor += 4; }
                $length = 0;
                for ($i = 0; $i < $count; $i++) {
                    $sampleDuration = $duration; $sampleSize = $size;
                    foreach ([0x100, 0x200, 0x400, 0x800] as $flag) {
                        if (!($flags & $flag)) { continue; }
                        $value = upload_video_uint($data, $cursor); $cursor += 4;
                        if ($flag === 0x100) { $sampleDuration = $value; }
                        if ($flag === 0x200) { $sampleSize = $value; }
                    }
                    if ($sampleDuration < 1 || $sampleSize < 1) { throw new UnexpectedValueException('Invalid fragment sample.'); }
                    if ($tracks[$id]['video'] && (!upload_video_media_range($media, $end + $length, $sampleSize)
                        || !upload_video_sample($bytes, $end + $length, $sampleSize, $tracks[$id]['codecs'][$description - 1]))) {
                        throw new UnexpectedValueException('Invalid encoded fragment sample.');
                    }
                    $length += $sampleSize;
                }
                if ($cursor !== strlen($data) || ($count && !upload_video_media_range($media, $end, $length))) {
                    throw new UnexpectedValueException('Missing fragment media.');
                }
                $end += $length;
                if ($tracks[$id]['video']) { $videoSamples += $count; }
            }
            $previousEnd = $end;
        }
    }
    return $videoSamples;
}

function uploaded_video_has_basic_structure(string $path, bool $allowQuickTime = false): bool
{
    $size = @filesize($path);
    if ($size === false || $size < 32 || $size > 20 * 1048576) { return false; }
    $bytes = @file_get_contents($path);
    if (!is_string($bytes) || strlen($bytes) !== $size) { return false; }
    try {
        $budget = 10000;
        $boxes = upload_video_boxes($bytes, 0, $size, $budget);
        $ftyp = array_values(array_filter($boxes, static fn($box) => $box['type'] === 'ftyp'));
        if (!$ftyp && !$allowQuickTime) { return false; }
        if ($ftyp && (count($ftyp) !== 1 || $ftyp[0]['start'] !== 0
            || strlen(upload_video_payload($bytes, $ftyp[0], 8)) % 4 !== 0)) { return false; }
        $media = array_values(array_filter($boxes, static fn($box) => $box['type'] === 'mdat' && $box['end'] > $box['data']));
        if (!$media) { return false; }
        $moov = upload_video_box($boxes, 'moov');
        if ($moov['end'] - $moov['data'] > 8 * 1048576) { return false; }
        $children = upload_video_boxes($bytes, $moov['data'], $moov['end'], $budget);
        $mvhd = upload_video_payload($bytes, upload_video_box($children, 'mvhd'), 100);
        if (ord($mvhd[0]) > 1 || strlen($mvhd) < (ord($mvhd[0]) === 1 ? 112 : 100)
            || upload_video_uint($mvhd, ord($mvhd[0]) === 1 ? 20 : 12) === 0) { return false; }
        $tracks = []; $samples = 0; $defaults = []; $sampleBudget = 250000;
        foreach ($children as $child) {
            if ($child['type'] === 'trak') {
                $track = upload_video_track($bytes, $child, $media, $budget, $sampleBudget);
                if (isset($tracks[$track['id']])) { return false; }
                $tracks[$track['id']] = $track;
                if ($track['video']) { $samples += $track['samples']; }
            } elseif ($child['type'] === 'mvex') {
                foreach (upload_video_boxes($bytes, $child['data'], $child['end'], $budget) as $default) {
                    if ($default['type'] !== 'trex') { continue; }
                    $data = upload_video_payload($bytes, $default, 24); $id = upload_video_uint($data, 4);
                    if (strlen($data) !== 24 || isset($defaults[$id])) { return false; }
                    $defaults[$id] = ['description' => upload_video_uint($data, 8),
                        'duration' => upload_video_uint($data, 12), 'size' => upload_video_uint($data, 16)];
                }
            }
        }
        // Resolve actual track/sample references; strings embedded in arbitrary metadata are not tracks.
        return $samples + upload_video_fragments($bytes, $boxes, $tracks, $defaults, $media, $budget, $sampleBudget) > 0;
    } catch (UnexpectedValueException $exception) {
        return false;
    }
}
