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

        return;
    }

    $statement = $pdo->prepare('INSERT INTO ppdb_entries (section, title, content, sort_order) VALUES (:section, :title, :content, :sort_order)');
    unset($data['id']);
    $statement->execute($data);
}

function delete_ppdb_entry(int $id): bool
{
    $pdo = db();
    if ($pdo) {
        $statement = $pdo->prepare('DELETE FROM ppdb_entries WHERE id = :id');
        $statement->execute(['id' => $id]);
        $deleted = $statement->rowCount();
        return $deleted > 0;
    }
    return false;
}
