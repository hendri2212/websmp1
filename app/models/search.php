<?php

function public_search_results(string $query, int $limitPerType = 8): array
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $pdo = db();
    if (!$pdo) {
        return [];
    }

    ensure_news_publication_column();
    ensure_academic_year_columns();

    $like = '%' . $query . '%';
    $results = [];

    $statement = $pdo->prepare('SELECT slug, title, category, excerpt, published_at FROM news WHERE is_active = 1 AND (title LIKE :q OR category LIKE :q OR excerpt LIKE :q OR content LIKE :q) ORDER BY published_at DESC, id DESC LIMIT ' . max(1, (int) $limitPerType));
    $statement->execute(['q' => $like]);
    foreach ($statement->fetchAll() as $row) {
        $results[] = [
            'type' => 'Berita',
            'title' => $row['title'],
            'excerpt' => $row['excerpt'],
            'meta' => $row['category'] . ' - ' . date('d F Y', strtotime($row['published_at'])),
            'url' => base_url('berita/' . rawurlencode($row['slug'])),
        ];
    }

    $statement = $pdo->prepare('SELECT id, title, category, level, achiever_name, description, achievement_date FROM school_achievements WHERE is_active = 1 AND (title LIKE :q OR category LIKE :q OR level LIKE :q OR achiever_name LIKE :q OR description LIKE :q) ORDER BY achievement_date DESC, id DESC LIMIT ' . max(1, (int) $limitPerType));
    $statement->execute(['q' => $like]);
    foreach ($statement->fetchAll() as $row) {
        $results[] = [
            'type' => 'Prestasi',
            'title' => $row['title'],
            'excerpt' => $row['achiever_name'] . ' - ' . $row['description'],
            'meta' => $row['category'] . ' - ' . $row['level'] . ' - ' . date('d F Y', strtotime($row['achievement_date'])),
            'url' => base_url('prestasi/' . (int) $row['id']),
        ];
    }

    $searchables = [
        [
            'type' => 'Agenda',
            'sql' => 'SELECT title, category, location, description, starts_at FROM agenda_items WHERE is_active = 1 AND academic_year = :year AND (title LIKE :q OR category LIKE :q OR location LIKE :q OR description LIKE :q) ORDER BY starts_at ASC, id DESC LIMIT ' . max(1, (int) $limitPerType),
            'url' => base_url('agenda'),
            'mapper' => static fn(array $row): array => [
                'title' => $row['title'],
                'excerpt' => $row['description'],
                'meta' => $row['category'] . ' - ' . date('d F Y H:i', strtotime($row['starts_at'])) . ' - ' . $row['location'],
            ],
        ],
        [
            'type' => 'Dokumen',
            'sql' => 'SELECT title, category, description, created_at FROM school_documents WHERE is_active = 1 AND academic_year = :year AND (title LIKE :q OR category LIKE :q OR description LIKE :q OR original_filename LIKE :q) ORDER BY sort_order ASC, created_at DESC LIMIT ' . max(1, (int) $limitPerType),
            'url' => base_url('dokumen'),
            'mapper' => static fn(array $row): array => [
                'title' => $row['title'],
                'excerpt' => $row['description'],
                'meta' => $row['category'],
            ],
        ],
    ];

    foreach ($searchables as $item) {
        $statement = $pdo->prepare($item['sql']);
        $statement->execute(['q' => $like, 'year' => current_academic_year()]);
        foreach ($statement->fetchAll() as $row) {
            $mapped = $item['mapper']($row);
            $results[] = [
                'type' => $item['type'],
                'title' => $mapped['title'],
                'excerpt' => $mapped['excerpt'],
                'meta' => $mapped['meta'],
                'url' => $item['url'],
            ];
        }
    }

    $simpleTables = [
        ['type' => 'Fasilitas', 'table' => 'school_facilities', 'title' => 'name', 'meta' => 'category', 'excerpt' => 'description', 'url' => base_url('fasilitas')],
        ['type' => 'Ekskul', 'table' => 'extracurriculars', 'title' => 'name', 'meta' => 'category', 'excerpt' => 'description', 'url' => base_url('ekstrakurikuler')],
        ['type' => 'FAQ', 'table' => 'faqs', 'title' => 'question', 'meta' => 'category', 'excerpt' => 'answer', 'url' => base_url('faq')],
    ];

    foreach ($simpleTables as $item) {
        $sql = 'SELECT ' . repository_quote_identifier($item['title']) . ' AS title, ' . repository_quote_identifier($item['meta']) . ' AS meta, ' . repository_quote_identifier($item['excerpt']) . ' AS excerpt FROM ' . repository_quote_identifier($item['table']) . ' WHERE is_active = 1 AND (' . repository_quote_identifier($item['title']) . ' LIKE :q OR ' . repository_quote_identifier($item['meta']) . ' LIKE :q OR ' . repository_quote_identifier($item['excerpt']) . ' LIKE :q) ORDER BY sort_order ASC, id DESC LIMIT ' . max(1, (int) $limitPerType);
        $statement = $pdo->prepare($sql);
        $statement->execute(['q' => $like]);
        foreach ($statement->fetchAll() as $row) {
            $results[] = [
                'type' => $item['type'],
                'title' => $row['title'],
                'excerpt' => $row['excerpt'],
                'meta' => $row['meta'],
                'url' => $item['url'],
            ];
        }
    }

    return $results;
}
