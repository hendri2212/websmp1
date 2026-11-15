<?php

function seed_content(): array
{
    static $seed;
    if ($seed === null) {
        $seed = require APP_ROOT . '/config/site_data.php';
    }
    return $seed;
}

function site_content(): array
{
    $content = seed_content();
    $pdo = db();

    if (!$pdo) {
        return $content;
    }

    $keys = ['schoolProfile', 'siteMaintenance', 'siteTheme', 'siteAcademic', 'siteHome', 'navigation', 'stats', 'quickLinks', 'featuredAreas', 'excellencePrograms', 'schoolHighlights', 'schoolAgenda', 'profileSections', 'principalMessage', 'schoolIdentity', 'schoolAccreditation', 'schoolObjectives', 'schoolFacilities', 'academicPrograms', 'academicServices', 'academicSupportItems', 'academicCommitments', 'staffMembers', 'studentPrograms', 'studentDevelopmentAreas', 'extracurriculars', 'studentGuidancePoints', 'galleryHighlights', 'ppdbSteps', 'ppdbRequirements', 'ppdbInformation', 'ppdbCriteria', 'ppdbNotes', 'contactChannels', 'contactServices', 'contactNotes'];
    $rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    foreach ($keys as $key) {
        if (!empty($settings[$key])) {
            $decoded = json_decode($settings[$key], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $content[$key] = $decoded;
            }
        }
    }

    $content['schoolProfile']['logoPath'] = normalized_school_logo_path($content['schoolProfile']['logoPath'] ?? null);
    $content['navigation'] = normalized_navigation($content['navigation'] ?? [], seed_content()['navigation'] ?? []);
    $content['siteAcademic'] = normalized_site_academic($content['siteAcademic'] ?? [], seed_content()['siteAcademic'] ?? []);
    $content['siteHome'] = normalized_site_home($content['siteHome'] ?? [], seed_content()['siteHome'] ?? []);

    return $content;
}

function normalized_school_logo_path(mixed $path): string
{
    $path = is_string($path) ? '/' . ltrim(trim($path), '/') : '';
    if (!str_starts_with($path, '/public/')
        || !file_is_within_directory(APP_ROOT . $path, APP_ROOT . '/public')) {
        return '/public/logo-sekolah.png';
    }

    return $path;
}

function normalized_site_academic(array $settings, array $defaults): array
{
    $currentYear = trim((string) ($settings['currentYear'] ?? $defaults['currentYear'] ?? '2025/2026'));
    return ['currentYear' => $currentYear !== '' ? $currentYear : '2025/2026'];
}

function current_academic_year(): string
{
    return site_content()['siteAcademic']['currentYear'] ?? '2025/2026';
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

function normalized_site_home(array $settings, array $defaults): array
{
    $normalized = array_replace($defaults, $settings);
    foreach (['showAnnouncements', 'showQuickLinks', 'showLogoCard', 'showStats', 'showPrincipalCard', 'showTeacherCarousel', 'showProfileIntro', 'showFeaturedAreas', 'showAgenda'] as $key) {
        $normalized[$key] = array_key_exists($key, $normalized) ? (bool) $normalized[$key] : true;
    }

    foreach (['heroTitle', 'heroSubtitle', 'primaryButtonLabel', 'primaryButtonUrl', 'secondaryButtonLabel', 'secondaryButtonUrl', 'profileVideoUrl', 'profileVideoTitle', 'profileVideoDescription'] as $key) {
        $normalized[$key] = trim((string) ($normalized[$key] ?? ($defaults[$key] ?? '')));
    }
    $normalized['teacherLimit'] = max(4, min(24, (int) ($normalized['teacherLimit'] ?? 12)));
    $normalized['agendaLimit'] = max(1, min(9, (int) ($normalized['agendaLimit'] ?? 3)));

    return $normalized;
}

function normalized_navigation(array $navigation, array $defaults): array
{
    $currentByHref = [];
    foreach ($navigation as $item) {
        if (!empty($item['href'])) {
            $currentByHref[$item['href']] = $item;
        }
    }

    $normalized = [];
    foreach ($defaults as $default) {
        $current = $currentByHref[$default['href']] ?? [];
        $normalized[] = [
            'href' => $default['href'],
            'label' => trim($current['label'] ?? $default['label']),
            'visible' => array_key_exists('visible', $current) ? (bool) $current['visible'] : (bool) ($default['visible'] ?? true),
        ];
    }

    return $normalized;
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
    log_admin_activity('update', 'settings', 'Pengaturan website diperbarui.', 'site_settings');
}

function school_profile_sections(): array
{
    return site_content()['profileSections'] ?? [];
}
