<?php
require_admin_permission('settings');

function settings_parse_link_rows(string $raw, array $fallback): array
{
    $rows = [];
    foreach (preg_split('/\R+/', $raw) as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) < 3 || $parts[0] === '' || $parts[2] === '') {
            continue;
        }
        $rows[] = [
            'title' => $parts[0],
            'desc' => $parts[1],
            'href' => $parts[2],
        ];
    }

    return $rows ?: $fallback;
}

function settings_link_rows_text(array $rows): string
{
    return implode("\n", array_map(static function (array $row): string {
        return ($row['title'] ?? '') . ' | ' . ($row['desc'] ?? '') . ' | ' . ($row['href'] ?? '/');
    }, $rows));
}

function settings_identity_value(array $items, string $label, string $fallback): string
{
    foreach ($items as $item) {
        if (($item['label'] ?? '') === $label) {
            return (string) ($item['value'] ?? $fallback);
        }
    }

    return $fallback;
}

$message = '';
$error = '';
$content = site_content();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('settings', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } else {
        $profile = $content['schoolProfile'];
        $profile['name'] = $_POST['name'] ?? $profile['name'];
        $profile['shortName'] = $_POST['short_name'] ?? $profile['shortName'];
        $profile['tagline'] = $_POST['tagline'] ?? $profile['tagline'];
        $profile['description'] = $_POST['description'] ?? $profile['description'];
        $profile['heroDescription'] = $_POST['hero_description'] ?? $profile['heroDescription'];
        $profile['address'] = $_POST['address'] ?? $profile['address'];
        $profile['phone'] = $_POST['phone'] ?? $profile['phone'];
        $profile['email'] = $_POST['email'] ?? $profile['email'];
        $profile['serviceHours'] = $_POST['service_hours'] ?? $profile['serviceHours'];
        $logoUpload = uploaded_image_result('school_logo_file', 'profile');
        $profile['logoPath'] = $logoUpload['path'] ?? ($profile['logoPath'] ?? '');

        $maintenance = [
            'enabled' => isset($_POST['maintenance_enabled']),
            'details' => trim($_POST['maintenance_details'] ?? $content['siteMaintenance']['details']),
            'message' => trim($_POST['maintenance_message'] ?? ($content['siteMaintenance']['message'] ?? '')),
        ];

        $theme = [
            'primaryColor' => normalize_hex_color($_POST['theme_primary_color'] ?? null, '#0284c7'),
            'primaryDarkColor' => normalize_hex_color($_POST['theme_primary_dark_color'] ?? null, '#0b4fa8'),
            'secondaryColor' => normalize_hex_color($_POST['theme_secondary_color'] ?? null, '#06b6d4'),
            'accentColor' => normalize_hex_color($_POST['theme_accent_color'] ?? null, '#eab308'),
            'darkColor' => normalize_hex_color($_POST['theme_dark_color'] ?? null, '#0f172a'),
        ];
        $academic = [
            'currentYear' => trim($_POST['current_academic_year'] ?? ($content['siteAcademic']['currentYear'] ?? '')),
        ];

        $home = [
            'principalSlogan' => trim($_POST['home_principal_slogan'] ?? $content['siteHome']['principalSlogan']),
            'heroTitle' => trim($_POST['home_hero_title'] ?? ($content['siteHome']['heroTitle'] ?? $profile['name'])),
            'heroSubtitle' => trim($_POST['home_hero_subtitle'] ?? ($content['siteHome']['heroSubtitle'] ?? $profile['heroDescription'])),
            'primaryButtonLabel' => trim($_POST['home_primary_button_label'] ?? ($content['siteHome']['primaryButtonLabel'] ?? '')),
            'primaryButtonUrl' => trim($_POST['home_primary_button_url'] ?? ($content['siteHome']['primaryButtonUrl'] ?? '/profil')),
            'secondaryButtonLabel' => trim($_POST['home_secondary_button_label'] ?? ($content['siteHome']['secondaryButtonLabel'] ?? '')),
            'secondaryButtonUrl' => trim($_POST['home_secondary_button_url'] ?? ($content['siteHome']['secondaryButtonUrl'] ?? '/ppdb')),
            'profileVideoUrl' => trim($_POST['home_profile_video_url'] ?? ($content['siteHome']['profileVideoUrl'] ?? '')),
            'profileVideoTitle' => trim($_POST['home_profile_video_title'] ?? ($content['siteHome']['profileVideoTitle'] ?? '')),
            'profileVideoDescription' => trim($_POST['home_profile_video_description'] ?? ($content['siteHome']['profileVideoDescription'] ?? '')),
            'teacherLimit' => max(4, min(24, (int) ($_POST['home_teacher_limit'] ?? ($content['siteHome']['teacherLimit'] ?? 12)))),
            'agendaLimit' => max(1, min(9, (int) ($_POST['home_agenda_limit'] ?? ($content['siteHome']['agendaLimit'] ?? 3)))),
            'showAnnouncements' => isset($_POST['home_show_announcements']),
            'showQuickLinks' => isset($_POST['home_show_quick_links']),
            'showLogoCard' => isset($_POST['home_show_logo_card']),
            'showStats' => isset($_POST['home_show_stats']),
            'showPrincipalCard' => isset($_POST['home_show_principal_card']),
            'showTeacherCarousel' => isset($_POST['home_show_teacher_carousel']),
            'showProfileIntro' => isset($_POST['home_show_profile_intro']),
            'showFeaturedAreas' => isset($_POST['home_show_featured_areas']),
            'showAgenda' => isset($_POST['home_show_agenda']),
        ];
        $hasVideoUpload = isset($_FILES['home_profile_video_file']) && ($_FILES['home_profile_video_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $videoSource = $_POST['home_profile_video_source'] ?? ($hasVideoUpload ? 'upload' : (youtube_video_id($home['profileVideoUrl']) !== null ? 'youtube' : 'upload'));
        $profileVideoUpload = ['path'=>null, 'error'=>null];
        if (!in_array($videoSource, ['upload','youtube'], true)) {
            $error = 'Sumber video tidak valid.';
        } elseif ($videoSource === 'youtube') {
            $videoId = youtube_video_id($home['profileVideoUrl']);
            if ($videoId === null) {
                $error = 'Gunakan URL YouTube watch, youtu.be, atau shorts yang valid.';
            } elseif ($hasVideoUpload) {
                $error = 'Pilih satu sumber video saja.';
            } else {
                $home['profileVideoUrl'] = 'https://www.youtube.com/watch?v=' . $videoId;
            }
        } else {
            $profileVideoUpload = uploaded_profile_video_result('home_profile_video_file');
            $oldVideo = $content['siteHome']['profileVideoUrl'] ?? '';
            $home['profileVideoUrl'] = $profileVideoUpload['path'] ?? profile_video_upload_path($oldVideo) ?? '';
            if (!$hasVideoUpload && $oldVideo !== '' && $home['profileVideoUrl'] === '') {
                $error = 'Pilih file video untuk mengganti sumber sebelumnya.';
            }
        }
        $home['profileVideoSource'] = $videoSource;
        if ($home['heroTitle'] === '') {
            $home['heroTitle'] = $profile['name'];
        }
        if ($home['heroSubtitle'] === '') {
            $home['heroSubtitle'] = $profile['heroDescription'];
        }
        if ($home['primaryButtonUrl'] === '') {
            $home['primaryButtonUrl'] = '/profil';
        }
        if ($home['secondaryButtonUrl'] === '') {
            $home['secondaryButtonUrl'] = '/ppdb';
        }

        $principal = $content['principalMessage'];
        $principal['name'] = $_POST['principal_name'] ?? $principal['name'];
        $principal['title'] = $_POST['principal_title'] ?? $principal['title'];
        $principal['greeting'] = $_POST['principal_greeting'] ?? $principal['greeting'];
        $principal['paragraphs'] = array_values(array_filter(array_map('trim', preg_split('/\R+/', $_POST['principal_paragraphs'] ?? ''))));
        $principalPhotoUpload = uploaded_image_result('principal_photo_file', 'profile');
        $principal['photoPath'] = $principalPhotoUpload['path'] ?? ($principal['photoPath'] ?? '');

        $currentAccreditation = $content['schoolAccreditation'] ?? [];
        $accreditationUpload = uploaded_pdf_result('accreditation_file', 'accreditation');
        $accreditationStatus = trim($_POST['accreditation_status'] ?? ($currentAccreditation['status'] ?? ''));
        $accreditationSkNumber = trim($_POST['accreditation_sk_number'] ?? ($currentAccreditation['skNumber'] ?? ''));
        $accreditationSkDate = trim($_POST['accreditation_sk_date'] ?? ($currentAccreditation['skDate'] ?? ''));
        $accreditation = [
            'status' => $accreditationStatus,
            'skNumber' => $accreditationSkNumber,
            'skDate' => $accreditationSkDate,
            'description' => trim($_POST['accreditation_description'] ?? ($currentAccreditation['description'] ?? '')),
            'filePath' => $accreditationUpload['path'] ?? ($currentAccreditation['filePath'] ?? ''),
            'originalFilename' => $accreditationUpload['original_filename'] ?? ($currentAccreditation['originalFilename'] ?? ''),
            'fileSize' => (int) ($accreditationUpload['file_size'] ?: ($currentAccreditation['fileSize'] ?? 0)),
        ];

        if ($logoUpload['error']) {
            $error = $logoUpload['error'];
        } elseif ($principalPhotoUpload['error']) {
            $error = $principalPhotoUpload['error'];
        } elseif ($accreditationUpload['error']) {
            $error = $accreditationUpload['error'];
        } elseif ($profileVideoUpload['error']) {
            $error = $profileVideoUpload['error'];
        }
        foreach (['home_primary_button_url','home_secondary_button_url','home_profile_video_url'] as $field) {
            if (isset($_POST[$field]) && trim($_POST[$field]) !== '' && safe_content_url($_POST[$field]) === '') {
                $error = 'URL tidak valid. Gunakan path lokal atau URL HTTP/HTTPS.';
            }
        }

        $profileSections = [
            ['title' => 'Sejarah Singkat', 'text' => trim($_POST['history_text'] ?? ($content['profileSections'][0]['text'] ?? ''))],
            ['title' => 'Visi', 'text' => trim($_POST['vision_text'] ?? ($content['profileSections'][1]['text'] ?? ''))],
            ['title' => 'Misi', 'text' => trim($_POST['mission_text'] ?? ($content['profileSections'][2]['text'] ?? ''))],
        ];

        $stats = [
            ['label' => 'Siswa', 'value' => $_POST['stat_students'] ?? $content['stats'][0]['value']],
            ['label' => 'Guru & Staf', 'value' => $_POST['stat_teachers'] ?? $content['stats'][1]['value']],
            ['label' => 'Ekstrakurikuler', 'value' => $_POST['stat_extracurriculars'] ?? $content['stats'][2]['value']],
            ['label' => 'Rombel', 'value' => $_POST['stat_rombel'] ?? $content['stats'][3]['value']],
        ];
        $quickLinks = settings_parse_link_rows($_POST['home_quick_links'] ?? '', $content['quickLinks']);
        $featuredAreas = settings_parse_link_rows($_POST['home_featured_areas'] ?? '', $content['featuredAreas']);
        $excellencePrograms = array_values(array_filter(array_map('trim', preg_split('/\R+/', $_POST['home_excellence_programs'] ?? ''))));
        if ($excellencePrograms === []) {
            $excellencePrograms = $content['excellencePrograms'];
        }

        $visibleNavigation = $_POST['nav_visible'] ?? [];
        $navigation = array_map(static function (array $item) use ($visibleNavigation): array {
            return [
                'href' => $item['href'],
                'label' => $item['label'],
                'visible' => isset($visibleNavigation[$item['href']]),
            ];
        }, $content['navigation']);

        $accreditationSummary = $accreditation['status'];
        if ($accreditation['skNumber'] !== '') {
            $accreditationSummary .= ' - SK No. ' . $accreditation['skNumber'];
        }
        if ($accreditation['skDate'] !== '') {
            $accreditationSummary .= ' tanggal ' . $accreditation['skDate'];
        }

        $identity = [
            ['label' => 'Nama Sekolah', 'value' => $profile['name']],
            ['label' => 'Status', 'value' => settings_identity_value($content['schoolIdentity'], 'Status', '')],
            ['label' => 'NPSN', 'value' => settings_identity_value($content['schoolIdentity'], 'NPSN', '')],
            ['label' => 'NSS', 'value' => settings_identity_value($content['schoolIdentity'], 'NSS', '')],
            ['label' => 'Alamat', 'value' => $profile['address']],
            ['label' => 'Akreditasi', 'value' => $accreditationSummary],
            ['label' => 'Tahun Berdiri', 'value' => settings_identity_value($content['schoolIdentity'], 'Tahun Berdiri', '')],
            ['label' => 'Jam Layanan', 'value' => $profile['serviceHours']],
            ['label' => 'Email Resmi', 'value' => $profile['email']],
            ['label' => 'Telepon', 'value' => $profile['phone']],
        ];

        $contactChannels = [
            ['title' => 'Alamat Sekolah', 'value' => $profile['address'], 'desc' => $content['contactChannels'][0]['desc'] ?? 'Alamat sekolah yang digunakan untuk layanan dan kunjungan resmi.'],
            ['title' => 'Telepon', 'value' => $profile['phone'], 'desc' => $content['contactChannels'][1]['desc'] ?? 'Nomor telepon resmi sekolah yang dapat dihubungi pada jam layanan administrasi.'],
            ['title' => 'Email Resmi', 'value' => $profile['email'], 'desc' => $content['contactChannels'][2]['desc'] ?? 'Alamat surat elektronik resmi sekolah untuk layanan informasi dan korespondensi.'],
            ['title' => 'Jam Layanan', 'value' => $profile['serviceHours'], 'desc' => $content['contactChannels'][3]['desc'] ?? 'Jam layanan sekolah untuk administrasi dan informasi umum.'],
        ];

        if (!$error) {
            save_site_sections([
                'schoolProfile' => $profile,
                'siteMaintenance' => $maintenance,
                'siteTheme' => $theme,
                'siteAcademic' => $academic,
                'siteHome' => $home,
                'principalMessage' => $principal,
                'navigation' => $navigation,
                'stats' => $stats,
                'quickLinks' => $quickLinks,
                'featuredAreas' => $featuredAreas,
                'excellencePrograms' => $excellencePrograms,
                'profileSections' => $profileSections,
                'schoolIdentity' => $identity,
                'schoolAccreditation' => $accreditation,
                'contactChannels' => $contactChannels,
            ]);
            commit_uploaded_files();
            $content = site_content();
            $message = 'Pengaturan sekolah berhasil diperbarui.';
        }
    }
}

$pageTitle = 'Pengaturan Sekolah';
render_view('admin/settings.php', get_defined_vars(), 'admin');
