<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require __DIR__ . '/../includes/functions.php';
$checks = 0;
$failures = 0;
function upload_unit_check(bool $passed, string $label): void
{
    global $checks, $failures;
    $checks++;
    if (!$passed) {
        $failures++;
        echo '[FAIL] ' . $label . PHP_EOL;
    }
}
$file = ['name'=>'photo.png','tmp_name'=>'temporary','error'=>0,'size'=>100];
foreach (['photo.png','photo.JPG','photo.jpeg','photo.webp','School photo [1].png','School "photo".png'] as $name) {
    upload_unit_check(upload_filename_error(array_replace($file,['name'=>$name]), ['jpg','jpeg','png','webp']) === '', 'valid filename');
}
foreach (['image.php.jpg','image.jpg.php','image..jpg','payload.phtml','../image.jpg','..\\image.jpg',
    '.png','image.exe','image.phps','image.php5.png','image.jpg/extra',"image\0.png",str_repeat('a',256) . '.png',[]] as $name) {
    upload_unit_check(upload_filename_error(array_replace($file,['name'=>$name]), ['jpg','jpeg','png','webp']) !== '', 'invalid filename');
}
upload_unit_check(upload_filename_error($file + ['full_path'=>'../photo.png'], ['png']) !== '', 'PHP basename cannot hide path');
upload_unit_check(upload_filename_error(array_replace($file,['name'=>'document.pdf']), ['pdf']) === '', 'valid PDF extension');
upload_unit_check(upload_filename_error(array_replace($file,['name'=>'document.php.pdf']), ['pdf']) !== '', 'PDF double extension');
upload_unit_check(upload_image_fits_memory(640,480), 'normal image budget');
upload_unit_check(!upload_image_fits_memory(30000,30000), 'image decompression budget');
upload_unit_check(!upload_image_fits_memory(200,150), 'minimum image dimensions');
$paths = [];
try {
    foreach (['fake'=>'%PDF-1.4' . "\nDummy\n%%EOF\n", 'xref'=>'%PDF-1.4' . "\nstartxref\n999999\n%%EOF\n"] as $label => $bytes) {
        $path = tempnam(sys_get_temp_dir(), 'school-upload-unit-');
        $paths[] = $path;
        file_put_contents($path,$bytes);
        upload_unit_check(!uploaded_pdf_has_basic_structure($path), 'invalid PDF structure ' . $label);
    }
    $image = imagecreatetruecolor(640,480);
    $path = tempnam(sys_get_temp_dir(), 'school-upload-unit-');
    $paths[] = $path;
    imagepng($image,$path);
    imagedestroy($image);
    $decoded = image_resource_from_upload($path,'image/png');
    upload_unit_check($decoded !== false, 'actual PNG decodes');
    if ($decoded) {
        imagedestroy($decoded);
    }
    file_put_contents($path, substr(file_get_contents($path),0,33));
    upload_unit_check(image_resource_from_upload($path,'image/png') === false, 'header-only PNG never falls back to original');
    $image = imagecreatetruecolor(640,480);
    imagejpeg($image,$path);
    $warningsBefore = ini_get('gd.jpeg_ignore_warning');
    file_put_contents($path, substr(file_get_contents($path),0,-60));
    upload_unit_check(image_resource_from_upload($path,'image/jpeg') === false, 'truncated JPEG not repaired silently');
    upload_unit_check(ini_get('gd.jpeg_ignore_warning') === $warningsBefore, 'JPEG warning configuration restored');
    imagewebp($image,$path);
    imagedestroy($image);
    upload_unit_check(upload_webp_container_is_complete($path), 'valid WebP chunk structure');
    file_put_contents($path, substr(file_get_contents($path),0,-12));
    upload_unit_check(image_resource_from_upload($path,'image/webp') === false, 'truncated WebP rejected before GD fatal');
} finally {
    foreach ($paths as $path) {
        unlink($path);
    }
}
require __DIR__ . '/../includes/repositories.php';
function db(): object { return $GLOBALS['upload_test_database']; }
function log_admin_activity(...$arguments): void {}
$database = new class {
    public array $rows = ['existing'=>'unchanged'];
    public array $before = [];
    public bool $active = false;
    public bool $fail = true;
    public int $executions = 0;
    public int $commits = 0;
    public int $rollbacks = 0;
    public function inTransaction(): bool { return $this->active; }
    public function beginTransaction(): void { $this->active = true; $this->before = $this->rows; }
    public function commit(): void { $this->active = false; $this->commits++; }
    public function rollBack(): void { $this->rows = $this->before; $this->active = false; $this->rollbacks++; }
    public function query(string $sql): object {
        return new class {
            public function fetchAll(...$arguments): array { return []; }
            public function fetchColumn(): string { return 'InnoDB'; }
        };
    }
    public function prepare(string $sql): object {
        return new class($this) {
            public function __construct(private object $database) {}
            public function execute(array $values): void {
                if (++$this->database->executions === 2 && $this->database->fail) {
                    throw new PDOException('Synthetic query failure');
                }
                $this->database->rows[$values['setting_key']] = $values['setting_value'];
            }
        };
    }
};
$GLOBALS['upload_test_database'] = $database;
try {
    save_site_sections(['schoolProfile'=>['logoPath'=>'new.png'], 'principalMessage'=>['photoPath'=>'new.png']]);
    upload_unit_check(false, 'synthetic query failure propagated');
} catch (PDOException $exception) {
    upload_unit_check(true, 'synthetic query failure propagated');
}
upload_unit_check($database->rows === ['existing'=>'unchanged'], 'partial settings query rolled back');
upload_unit_check($database->rollbacks === 1 && $database->commits === 0 && !$database->active, 'failed settings transaction closed');
$database->fail = false;
save_site_sections(['schoolProfile'=>['logoPath'=>'new.png']]);
upload_unit_check($database->commits === 1 && isset($database->rows['schoolProfile']), 'successful settings save commits normally');
echo 'RESULT: ' . ($checks - $failures) . '/' . $checks . ' passed' . PHP_EOL;
exit($failures ? 1 : 0);
