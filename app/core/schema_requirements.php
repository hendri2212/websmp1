<?php

function require_schema_columns(PDO $pdo, string $table, array $required): void
{
    if (!in_array($table, ['admins', 'news', 'agenda_items', 'school_documents', 'school_achievements'], true)) {
        throw new LogicException('Invalid schema requirement.');
    }
    $columns = $pdo->query('SHOW COLUMNS FROM ' . $table)->fetchAll();
    $names = array_column($columns, 'Field');
    if (array_diff($required, $names)) {
        throw new RuntimeException('Struktur penyimpanan belum kompatibel. Hubungi pengelola sistem.');
    }
}
