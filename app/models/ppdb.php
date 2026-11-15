<?php

function ppdb_entries(): array
{
    $pdo = db();
    return $pdo ? $pdo->query('SELECT * FROM ppdb_entries ORDER BY section ASC, sort_order ASC, id DESC')->fetchAll() : [];
}

function admin_ppdb_row(int $id): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM ppdb_entries WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function ppdb_sections_data(): array
{
    $entries = ppdb_entries();
    if ($entries) {
        $sections = [];
        foreach ($entries as $entry) {
            $section = strtolower(trim($entry['section']));
            if ($section === '') {
                $section = 'informasi';
            }

            $sections[$section][] = [
                'id' => (int) $entry['id'],
                'title' => $entry['title'],
                'desc' => $entry['content'],
            ];
        }

        if (empty($sections['kriteria'])) {
            $sections['kriteria'] = site_content()['ppdbCriteria'] ?? [];
        }

        return $sections;
    }

    $content = site_content();
    return [
        'informasi' => $content['ppdbInformation'] ?? [],
        'kriteria' => $content['ppdbCriteria'] ?? [],
        'persyaratan' => array_map(
            static fn(string $item): array => ['title' => 'Persyaratan', 'desc' => $item],
            $content['ppdbRequirements'] ?? []
        ),
        'catatan' => array_map(
            static fn(string $item): array => ['title' => 'Catatan Penting', 'desc' => $item],
            $content['ppdbNotes'] ?? []
        ),
    ];
}

function save_ppdb_entry(array $data): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        $statement = $pdo->prepare('UPDATE ppdb_entries SET section = :section, title = :title, content = :content, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'ppdb_content', 'Konten PPDB diperbarui: ' . $data['title'], 'ppdb_entry', (int) $data['id']);
        return;
    }

    $statement = $pdo->prepare('INSERT INTO ppdb_entries (section, title, content, sort_order) VALUES (:section, :title, :content, :sort_order)');
    unset($data['id']);
    $statement->execute($data);
    log_admin_activity('create', 'ppdb_content', 'Konten PPDB dibuat: ' . $data['title'], 'ppdb_entry', (int) $pdo->lastInsertId());
}

function delete_ppdb_entry(int $id): bool
{
    $pdo = db();
    if ($pdo) {
        $row = admin_ppdb_row($id);
        $statement = $pdo->prepare('DELETE FROM ppdb_entries WHERE id = :id');
        $statement->execute(['id' => $id]);
        $deleted = $statement->rowCount();
        if ($deleted > 0) {
            log_admin_activity('delete', 'ppdb_content', 'Konten PPDB dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'ppdb_entry', $id);
        }
        return $deleted > 0;
    }
    return false;
}
