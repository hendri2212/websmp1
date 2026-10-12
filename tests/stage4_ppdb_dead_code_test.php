<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
$project = dirname(__DIR__);
$checks = 0;
function dead_code_check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}
$css = file_get_contents($project . '/public/assets/css/style.css');
foreach (['ppdb-chart-grid','ppdb-chart-list','ppdb-donut','ppdb-pie-bar','ppdb-status-form',
    'receipt-actions','receipt-card','receipt-header','receipt-number','receipt-grid','receipt-footer'] as $selector) {
    dead_code_check(!str_contains($css, '.' . $selector), 'Obsolete selector remains: ' . $selector);
}
foreach (['.ppdb-criteria-grid','.ppdb-criteria-card','.content-chart-grid','.status-form',
    '.status-badge','.detail-grid','.filter-actions .button','@media print','@media (max-width: 920px)'] as $selector) {
    dead_code_check(str_contains($css, $selector), 'Shared/information styling removed: ' . $selector);
}
$index = file_get_contents($project . '/index.php');
dead_code_check(!str_contains($index, "case \$path === '/ppdb/bukti':"), 'Unreachable receipt case remains');
dead_code_check(substr_count($index, "require __DIR__ . '/pages/ppdb_receipt.php';") === 1
    && strpos($index, "'/pages/ppdb_receipt.php'") < strpos($index, '$content = site_content();'), 'Early receipt tombstone guard removed');
dead_code_check(str_contains($index, "case \$path === '/ppdb':"), 'Public information route removed');
foreach (['admin/ppdb_applications.php','admin/ppdb_export.php','admin/ppdb_download.php','pages/ppdb_receipt.php'] as $path) {
    $source = file_get_contents($project . '/' . $path);
    dead_code_check(str_contains($source, 'http_response_code(410);') && !str_contains($source, 'bootstrap.php'), 'Retired URL no longer closed: ' . $path);
}
$repository = file_get_contents($project . '/includes/repositories.php');
foreach (['ppdb_entries','admin_ppdb_row','ppdb_sections_data','save_ppdb_entry','delete_ppdb_entry'] as $function) {
    dead_code_check(str_contains($repository, 'function ' . $function . '('), 'Active information helper removed: ' . $function);
}
dead_code_check(!str_contains($repository, 'ppdb_applications'), 'Applicant repository unexpectedly active');
dead_code_check(str_contains(file_get_contents($project . '/admin/ppdb.php'), "require_admin_permission('ppdb_content');"), 'Information authorization removed');
echo 'PASS: ' . $checks . " dead-code boundary checks; no database accessed.\n";
