<?php

function input_rules(string $module): array
{
    // [maximum characters, required, type, minimum]; SQL text columns also have byte limits.
    $text = [65535, false];
    $requiredText = [65535, true];
    $common = ['sort_order'=>[11, false, 'integer'], 'is_active'=>[1, false, 'checkbox']];
    $year = ['academic_year'=>[20, false, 'year']];
    $rules = [
        'news'=>['slug'=>[180,true,'slug'], 'title'=>[255,true], 'category'=>[100,true],
            'excerpt'=>$requiredText, 'content'=>[4294967295,true,'longtext'], 'published_at'=>[19,true,'datetime']],
        'gallery'=>['title'=>[255,true], 'description'=>$requiredText],
        'documents'=>['title'=>[255,true], 'category'=>[120,true], 'description'=>$requiredText] + $year,
        'announcements'=>['title'=>[255,true], 'content'=>$requiredText, 'link_label'=>[120,false],
            'link_url'=>[255,false,'url'], 'starts_at'=>[19,false,'datetime'], 'ends_at'=>[19,false,'datetime']],
        'staff'=>['name'=>[160,true], 'position'=>[160,true], 'subject'=>[160,true], 'bio'=>$text,
            'phone'=>[60,false], 'email'=>[180,false,'email']],
        'agenda'=>['title'=>[255,true], 'category'=>[120,true], 'location'=>[180,true], 'description'=>$requiredText,
            'starts_at'=>[19,true,'datetime'], 'ends_at'=>[19,false,'datetime']] + $year,
        'achievements'=>['title'=>[255,true], 'level'=>[120,true], 'category'=>[120,true], 'achiever_name'=>[180,true],
            'achievement_date'=>[10,true,'date'], 'description'=>$requiredText] + $year,
        'facilities'=>['name'=>[180,true], 'category'=>[120,true], 'location'=>[180,false], 'capacity'=>[120,false],
            'condition_status'=>[120,true], 'description'=>$requiredText],
        'extracurriculars'=>['name'=>[180,true], 'category'=>[120,true], 'coach'=>[160,false],
            'schedule_text'=>[180,false], 'location'=>[180,false], 'description'=>$requiredText],
        'faqs'=>['question'=>[255,true], 'answer'=>$requiredText, 'category'=>[120,true]],
        'ppdb_content'=>['section'=>[100,true], 'title'=>[255,true], 'content'=>$requiredText],
        'admins'=>['username'=>[50,true], 'full_name'=>[120,false], 'role'=>[20,true,'role'],
            'password'=>[72,false,'password',8]],
        'contact_messages'=>['status'=>[20,true,'status']],
        'contact'=>['name'=>[120,true], 'email'=>[180,true,'email'], 'phone'=>[60,false],
            'subject'=>[180,true], 'message'=>[65535,true]],
        'change_password'=>['current_password'=>[4096,true,'raw'], 'new_password'=>[72,true,'password',8],
            'confirm_password'=>[72,true,'password',8]],
        'db_backup'=>['backup_type'=>[4,false,'backup']],
    ];
    if ($module !== 'settings') {
        return ($rules[$module] ?? []) + $common;
    }
    $settings = [];
    foreach (['name','short_name','tagline','phone','service_hours','principal_name','principal_title',
        'principal_greeting','home_hero_title','home_primary_button_label','home_secondary_button_label',
        'home_profile_video_title','accreditation_status','accreditation_sk_number','accreditation_sk_date'] as $field) {
        $settings[$field] = [255, false];
    }
    foreach (['description','hero_description','address','principal_paragraphs','history_text','vision_text','mission_text',
        'school_facilities','maintenance_message','home_hero_subtitle','home_profile_video_description',
        'home_excellence_programs','accreditation_description'] as $field) {
        $settings[$field] = $text;
    }
    $settings['name'] = [255, true, 'text', 1, true];
    $settings['email'] = [180, false, 'email'];
    $settings['current_academic_year'] = [20, false, 'year'];
    $settings['accreditation_sk_date'] = [40, false, 'display_date'];
    foreach (['home_primary_button_url','home_secondary_button_url','home_profile_video_url'] as $field) {
        $settings[$field] = [255, false, 'url'];
    }
    $settings['home_profile_video_source'] = [7, false, 'video_source'];
    foreach (['home_quick_links','home_featured_areas'] as $field) {
        $settings[$field] = [65535, false, 'links'];
    }
    $settings['home_teacher_limit'] = [2, false, 'teachers'];
    $settings['home_agenda_limit'] = [1, false, 'agenda_limit'];
    foreach (['stat_students','stat_teachers','stat_extracurriculars','stat_rombel'] as $field) {
        $settings[$field] = [60, false];
    }
    foreach (['primary','primary_dark','secondary','accent','dark'] as $field) {
        $settings['theme_' . $field . '_color'] = [7, false, 'color'];
    }
    foreach (['maintenance_enabled','home_show_announcements','home_show_quick_links','home_show_logo_card',
        'home_show_stats','home_show_principal_card','home_show_teacher_carousel','home_show_profile_intro',
        'home_show_featured_areas','home_show_agenda'] as $field) {
        $settings[$field] = [1, false, 'checkbox'];
    }
    return $settings;
}

function input_datetime_value(string $value, bool $dateOnly = false): ?string
{
    $formats = $dateOnly ? ['Y-m-d'] : ['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'];
    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format($format) === $value && (int) $date->format('Y') >= 1000) {
            return $date->format($dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s');
        }
    }
    return null;
}

function input_integer_is_valid(string $value, int $minimum = -2147483648, int $maximum = 2147483647): bool
{
    if (!preg_match('/\A(?:0|[1-9][0-9]*|-[1-9][0-9]*)\z/', $value)) {
        return false;
    }
    return filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>$minimum, 'max_range'=>$maximum]]) !== false;
}

function input_display_date_is_valid(string $value): bool
{
    if (input_datetime_value($value, true) !== null) {
        return true;
    }
    if (!preg_match('/\A([0-9]{1,2}) ([\p{L}]+) ([0-9]{4})\z/u', $value, $matches)) {
        return false;
    }
    $months = ['januari'=>1, 'februari'=>2, 'maret'=>3, 'april'=>4, 'mei'=>5, 'juni'=>6,
        'juli'=>7, 'agustus'=>8, 'september'=>9, 'oktober'=>10, 'november'=>11, 'desember'=>12];
    $month = $months[mb_strtolower($matches[2], 'UTF-8')] ?? 0;
    return (int) $matches[3] >= 1000 && checkdate($month, (int) $matches[1], (int) $matches[3]);
}

function input_validation_error(string $module, array $data, array $files = []): string
{
    // Request fields are scalars except for the existing navigation checkbox map.
    foreach ($data as $field => $value) {
        if ($module === 'settings' && $field === 'nav_visible') {
            if (!is_array($value) || count($value) > 100) {
                return 'Pilihan navigasi tidak valid.';
            }
            foreach ($value as $path => $flag) {
                if (!is_string($path) || strlen($path) > 255 || safe_content_url($path) === '' || $flag !== '1') {
                    return 'Pilihan navigasi tidak valid.';
                }
            }
        } elseif (!is_string($value)) {
            return 'Input form harus berupa teks.';
        }
    }
    if (array_key_exists('delete_id', $data)) {
        return '';
    }
    foreach (input_rules($module) as $field => $rule) {
        [$maximum, $required] = $rule;
        $type = $rule[2] ?? 'text';
        if (($rule[4] ?? false) && !array_key_exists($field, $data)) {
            continue; // Partial settings submissions retain the stored value.
        }
        $raw = $data[$field] ?? '';
        if (!mb_check_encoding($raw, 'UTF-8') || str_contains($raw, "\0")) {
            return 'Encoding input tidak valid.';
        }
        $length = in_array($type, ['password','raw'], true) ? strlen($raw) : mb_strlen($raw, 'UTF-8');
        if ($length > $maximum || (($maximum === 65535 || $type === 'longtext') && strlen($raw) > $maximum)) {
            return 'Panjang field ' . $field . ' tidak sesuai batas.';
        }
        $value = in_array($type, ['password','raw'], true) ? $raw : trim($raw);
        $blank = $value === '' || ($type !== 'raw' && preg_match('/\A[\s\p{Z}]*\z/u', $value));
        if ($type === 'password' && $raw !== '' && $blank) {
            return 'Password tidak boleh hanya berisi whitespace.';
        }
        if ($required && $blank) {
            return 'Field ' . $field . ' wajib diisi.';
        }
        if ($blank && !$required) {
            if (array_key_exists($field, $data) && in_array($type, ['checkbox','teachers','agenda_limit','color','year','backup'], true)) {
                return 'Field ' . $field . ' tidak valid.';
            }
            continue;
        }
        if ($length < ($rule[3] ?? 1)) {
            return 'Panjang field ' . $field . ' tidak sesuai batas.';
        }
        $valid = match ($type) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => safe_content_url($raw) !== '',
            'video_source' => in_array($raw, ['upload', 'youtube'], true),
            'slug' => preg_match('/\A[\p{L}\p{N}]+(?:[-_][\p{L}\p{N}]+)*\z/u', $raw) === 1,
            'date' => input_datetime_value($raw, true) !== null,
            'display_date' => input_display_date_is_valid($value),
            'datetime' => input_datetime_value($raw) !== null,
            'integer' => input_integer_is_valid($raw),
            'teachers' => input_integer_is_valid($raw, 4, 24),
            'agenda_limit' => input_integer_is_valid($raw, 1, 9),
            'year' => preg_match('/\A([0-9]{4})\/([0-9]{4})\z/', $raw, $matches) === 1
                && (int) $matches[1] >= 1000 && (int) $matches[2] === (int) $matches[1] + 1,
            'checkbox' => $raw === '1',
            'role' => in_array($raw, ['superadmin','editor','ppdb'], true),
            'status' => in_array($raw, ['baru','diproses','selesai'], true),
            'backup' => in_array($raw, ['sql','full'], true),
            'color' => preg_match('/\A#[0-9a-fA-F]{6}\z/', $raw) === 1,
            default => true,
        };
        if (!$valid) {
            return 'Format field ' . $field . ' tidak valid.';
        }
        if ($type === 'links') {
            foreach (preg_split('/\R/', $raw) as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) !== 3 || $parts[0] === '' || mb_strlen($parts[0]) > 120
                    || mb_strlen($parts[1]) > 260 || mb_strlen($parts[2]) > 180 || safe_content_url($parts[2]) === '') {
                    return 'Baris tautan harus berisi judul | deskripsi | URL yang valid.';
                }
            }
        }
    }
    if (!empty($data['starts_at']) && !empty($data['ends_at'])
        && input_datetime_value($data['ends_at']) < input_datetime_value($data['starts_at'])) {
        return 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    }
    if ($module === 'admins' && ($data['id'] ?? '') === '' && ($data['password'] ?? '') === '') {
        return 'Password wajib diisi untuk akun baru.';
    }
    foreach ($files as $file) {
        if (!is_array($file) || !is_string($file['name'] ?? null) || !is_string($file['tmp_name'] ?? null)
            || !is_int($file['error'] ?? null) || !is_int($file['size'] ?? null)) {
            return 'Metadata upload tidak valid.';
        }
        $name = $file['name'];
        $fullPath = $file['full_path'] ?? $name;
        if (!is_string($fullPath) || $fullPath !== $name) {
            return 'Path nama file upload tidak valid.';
        }
        if ($name !== '' && (!mb_check_encoding($name, 'UTF-8') || mb_strlen($name) > 255
            || preg_match('/[\x00-\x1f\x7f\/\\\\]/', $name) || in_array($name, ['.','..'], true))) {
            return 'Nama file upload tidak valid.';
        }
    }
    return '';
}

function input_form_value(array $data, string $field): string
{
    return is_string($data[$field] ?? null) ? $data[$field] : '';
}
