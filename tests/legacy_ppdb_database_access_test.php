<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/repositories.php';

class LegacyReferenceStatement
{
    public function __construct(private array $rows) {}
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT): array { return $this->rows; }
}

class LegacyReferenceDatabase
{
    public int $legacyQueries = 0;
    public bool $fail = false;
    public string $shared = '/public/uploads/news/news-20261008090000-0123456789abcdef.jpg';
    public function query(string $sql): LegacyReferenceStatement
    {
        if (str_contains($sql, 'ppdb_applications')) {
            $this->legacyQueries++;
            if ($sql !== 'SELECT family_card_path, birth_certificate_path, report_card_path, photo_path FROM ppdb_applications') {
                throw new RuntimeException('Unnecessary applicant fields requested.');
            }
            if ($this->fail) {
                throw new RuntimeException('Synthetic reference query failure.');
            }
            return new LegacyReferenceStatement([[
                'family_card_path'=>$this->shared,
                'birth_certificate_path'=>'storage/uploads/ppdb/private.pdf',
                'report_card_path'=>null,
                'photo_path'=>null,
            ]]);
        }
        return new LegacyReferenceStatement([]);
    }
}

$checks = 0;
function legacy_reference_check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}
$pdo = new LegacyReferenceDatabase();
$references = media_reference_paths($pdo);
legacy_reference_check($pdo->legacyQueries === 1, 'Only one minimal historical file query');
legacy_reference_check(isset($references[ltrim($pdo->shared, '/')]), 'Historical shared asset remains protected');
legacy_reference_check(isset($references['public/uploads/news/thumbs/' . basename($pdo->shared)]), 'Historical shared thumbnail remains protected');
legacy_reference_check(managed_media_path('storage/uploads/ppdb/private.pdf') === null, 'Private PPDB directory cannot be collected');
$pdo->fail = true;
$failed = false;
try {
    media_reference_paths($pdo);
} catch (RuntimeException $exception) {
    $failed = true;
}
legacy_reference_check($failed, 'Failed reference verification never returns an empty successful reference set');
$source = file_get_contents(__DIR__ . '/../includes/repositories.php');
legacy_reference_check(!str_contains($source, 'ppdb_applications'), 'Operational repository no longer accesses applicant table');
foreach (['ppdb_applications.php','ppdb_export.php','ppdb_download.php'] as $endpoint) {
    $source = file_get_contents(__DIR__ . '/../admin/' . $endpoint);
    legacy_reference_check(str_contains($source, 'http_response_code(410);') && !str_contains($source, 'bootstrap.php'), 'Legacy endpoint cannot initialize database: ' . $endpoint);
}
echo 'PASS: ' . $checks . " legacy database boundary checks; no real applicant records used.\n";
