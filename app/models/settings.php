<?php

function site_content(): array
{
    // Empty shapes keep forms renderable; editorial content comes only from the database.
    $content = [
        'schoolProfile' => array_fill_keys(['name', 'shortName', 'tagline', 'description', 'heroDescription', 'address', 'phone', 'email', 'serviceHours', 'logoPath'], ''),
        'principalMessage' => array_fill_keys(['name', 'title', 'greeting', 'photoPath'], '') + ['paragraphs' => []],
        'schoolAccreditation' => array_fill_keys(['status', 'skNumber', 'skDate', 'description', 'filePath', 'originalFilename'], '') + ['fileSize' => 0],
        'siteMaintenance' => ['enabled' => false, 'message' => '', 'details' => ''],
        'siteTheme' => [],
        'siteAcademic' => ['currentYear' => ''],
        'siteHome' => [],
        'homeProfileIntro' => array_fill_keys(['title', 'description', 'heading', 'text'], ''),
        'stats' => array_fill(0, 4, ['label' => '', 'value' => '']),
        'profileSections' => array_fill(0, 3, ['title' => '', 'text' => '']),
    ];
    foreach (['navigation', 'quickLinks', 'featuredAreas', 'excellencePrograms', 'schoolHighlights', 'schoolIdentity', 'schoolObjectives', 'academicPrograms', 'academicServices', 'academicSupportItems', 'academicCommitments', 'studentPrograms', 'studentDevelopmentAreas', 'studentGuidancePoints', 'galleryHighlights', 'contactChannels', 'contactServices', 'contactNotes', 'pageIntroductions', 'privacySections'] as $key) {
        $content[$key] = [];
    }
    $pdo = db();
    if ($pdo) {
        foreach ($pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $row) {
            $key = $row['setting_key'];
            $value = json_decode($row['setting_value'], true);
            if (array_key_exists($key, $content) && is_array($value)) {
                $content[$key] = array_is_list($content[$key]) ? $value : array_replace($content[$key], $value);
            }
        }
    }
    $content['schoolProfile']['logoPath'] = normalized_school_logo_path($content['schoolProfile']['logoPath']);
    $content['navigation'] = normalized_navigation($content['navigation']);
    $content['siteHome'] = normalized_site_home($content['siteHome']);
    return $content;
}

function normalized_school_logo_path(mixed $path): string
{
    $path = is_string($path) ? '/' . ltrim(trim($path), '/') : '';
    return str_starts_with($path, '/public/') && file_is_within_directory(APP_ROOT . $path, APP_ROOT . '/public') ? $path : '';
}

function current_academic_year(): string
{
    return trim((string) site_content()['siteAcademic']['currentYear']);
}

function repository_quote_identifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function ensure_academic_year_columns(): void
{
    static $done = false;
    $pdo = db();
    if ($done || !$pdo) {
        return;
    }

    $tables = ['agenda_items', 'school_documents', 'school_achievements'];
    foreach ($tables as $table) {
        require_schema_columns($pdo, $table, ['academic_year']);
    }

    $done = true;
}

function normalized_site_home(array $settings): array
{
    foreach (['showAnnouncements', 'showQuickLinks', 'showLogoCard', 'showStats', 'showPrincipalCard', 'showTeacherCarousel', 'showProfileIntro', 'showFeaturedAreas', 'showAgenda'] as $key) {
        $settings[$key] = (bool) ($settings[$key] ?? false);
    }
    foreach (['heroTitle', 'heroSubtitle', 'primaryButtonLabel', 'primaryButtonUrl', 'secondaryButtonLabel', 'secondaryButtonUrl', 'profileVideoUrl', 'profileVideoTitle', 'profileVideoDescription', 'principalSlogan'] as $key) {
        $settings[$key] = trim((string) ($settings[$key] ?? ''));
    }
    $settings['teacherLimit'] = max(4, min(24, (int) ($settings['teacherLimit'] ?? 12)));
    $settings['agendaLimit'] = max(1, min(9, (int) ($settings['agendaLimit'] ?? 3)));
    return $settings;
}

function normalized_navigation(array $navigation): array
{
    $items = [];
    foreach ($navigation as $item) {
        if (is_array($item) && !empty($item['href'])) {
            $items[] = [
                'href' => (string) $item['href'],
                'label' => trim((string) ($item['label'] ?? '')),
                'visible' => (bool) ($item['visible'] ?? false),
            ];
        }
    }
    return $items;
}

function save_site_sections(array $payload): void
{
    $pdo = db();
    if (!$pdo) {
        rollback_uploaded_files();
        throw new RuntimeException('Database tidak tersedia. File tidak disimpan.');
    }

    media_lifecycle_lock();
    if ($pdo->inTransaction()) {
        throw new RuntimeException('Operasi file memerlukan transaksi tersendiri.');
    }
    $commitAttempted = false;
    try {
        require_transactional_media_table($pdo, 'site_settings');
        $pdo->beginTransaction();
        $oldSettings = $pdo->query('SELECT setting_key, setting_value FROM site_settings FOR UPDATE')->fetchAll();
        foreach ($oldSettings as $setting) {
            if (array_key_exists($setting['setting_key'], $payload)) {
                $oldPaths = media_paths_in_value(json_decode($setting['setting_value'], true, 512, JSON_THROW_ON_ERROR));
                $newPaths = media_paths_in_value($payload[$setting['setting_key']]);
                foreach (array_diff($newPaths, $oldPaths) as $new) {
                    require_new_media_reference($new);
                }
                foreach (array_diff($oldPaths, $newPaths) as $old) {
                    media_cleanup_enqueue($old);
                }
            }
        }
        $existingKeys = array_column($oldSettings, 'setting_key');
        foreach ($payload as $key => $value) {
            if (!in_array($key, $existingKeys, true)) {
                require_new_media_reference($value);
            }
        }
        require_pending_media_files();
        $statement = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($payload as $key => $value) {
            $statement->execute([
                'setting_key' => $key,
                'setting_value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            ]);
        }
        require_pending_media_files();
        $commitAttempted = true;
        $GLOBALS['media_commit_in_progress'] = true;
        $pdo->commit();
    } catch (Throwable $exception) {
        $rollbackConfirmed = true;
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Throwable $rollbackException) {
            $rollbackConfirmed = false;
            error_log('Settings transaction outcome could not be verified; media retained for safe retry.');
        }
        if ($commitAttempted || !$rollbackConfirmed) {
            $GLOBALS['pending_uploaded_files'] = [];
        } else {
            rollback_uploaded_files();
        }
        $GLOBALS['media_commit_in_progress'] = false;
        throw $exception;
    }

    commit_uploaded_files();
    retry_media_cleanup($pdo);
}

function school_profile_sections(): array
{
    return site_content()['profileSections'] ?? [];
}
