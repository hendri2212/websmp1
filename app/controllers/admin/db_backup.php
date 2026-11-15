<?php
require_admin_permission('db_backup');

$config = require APP_ROOT . '/config/database.php';
$pdo = db();
$error = '';
$tableSummaries = $pdo ? backup_table_summaries($pdo) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('db_backup', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (!$pdo) {
        $error = 'Database belum terhubung sehingga backup tidak bisa dibuat.';
    } else {
        $backupType = $_POST['backup_type'] ?? 'sql';
        if ($backupType === 'full') {
            $filename = 'backup-lengkap-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $config['database']) . '-' . date('Ymd-His') . '.zip';
            $zipResult = build_full_backup_zip($pdo, $config['database'], $tableSummaries, $filename);
            if ($zipResult['error']) {
                $error = $zipResult['error'];
            } else {


                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($zipResult['path']));
                try {
                    readfile($zipResult['path']);
                } finally {
                    if (is_file($zipResult['path'])) { unlink($zipResult['path']); }
                }
                exit;
            }
        } else {
            $sql = build_database_backup($pdo, $config['database']);
            $filename = 'backup-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $config['database']) . '-' . date('Ymd-His') . '.sql';


            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($sql));
            echo $sql;
            exit;
        }
    }
}

$pageTitle = 'Backup Database';
render_view('admin/db_backup.php', get_defined_vars(), 'admin');
