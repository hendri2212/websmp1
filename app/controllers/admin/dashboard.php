<?php
require_admin_permission('dashboard');

function dashboard_percent(int $value, int $max): int
{
    if ($max <= 0) {
        return 0;
    }

    return max(4, min(100, (int) round(($value / $max) * 100)));
}

$pageTitle = 'Dashboard Admin';
$dbReady = db_available();
$newMessages = $dbReady ? contact_message_count('baru') : 0;
$totalMessages = $dbReady ? contact_message_count() : 0;
$activeAnnouncements = $dbReady ? announcement_count(true) : 0;
$totalNews = news_count(true);
$totalGallery = gallery_count();
$activeStaff = staff_count(true);
$activeAgenda = agenda_count(true);
$activeDocuments = document_count(true);
$activeAchievements = achievement_count(true);
$activeExtracurriculars = extracurricular_count(true);
$activeFacilities = facility_count(true);
$activeFaqs = faq_count(true);
$upcomingAgenda = $dbReady ? upcoming_agenda_items(5) : [];
$contentStatusRows = array_values(array_filter(
    $dbReady ? admin_content_status_summary() : [],
    static fn(array $row): bool => admin_can($row['permission'])
));

$contentMetrics = [
    ['permission' => 'news', 'label' => 'Berita', 'value' => $totalNews, 'href' => 'admin/news.php'],
    ['permission' => 'gallery', 'label' => 'Galeri', 'value' => $totalGallery, 'href' => 'admin/gallery.php'],
    ['permission' => 'staff', 'label' => 'Guru & Staf', 'value' => $activeStaff, 'href' => 'admin/staff.php'],
    ['permission' => 'agenda', 'label' => 'Agenda', 'value' => $activeAgenda, 'href' => 'admin/agenda.php'],
    ['permission' => 'documents', 'label' => 'Dokumen', 'value' => $activeDocuments, 'href' => 'admin/documents.php'],
    ['permission' => 'achievements', 'label' => 'Prestasi', 'value' => $activeAchievements, 'href' => 'admin/achievements.php'],
    ['permission' => 'extracurriculars', 'label' => 'Ekskul', 'value' => $activeExtracurriculars, 'href' => 'admin/extracurriculars.php'],
    ['permission' => 'facilities', 'label' => 'Fasilitas', 'value' => $activeFacilities, 'href' => 'admin/facilities.php'],
    ['permission' => 'faqs', 'label' => 'FAQ', 'value' => $activeFaqs, 'href' => 'admin/faqs.php'],
    ['permission' => 'announcements', 'label' => 'Pengumuman', 'value' => $activeAnnouncements, 'href' => 'admin/announcements.php'],
];
$visibleMetrics = array_values(array_filter($contentMetrics, static fn(array $metric): bool => admin_can($metric['permission'])));
$maxMetric = max(1, ...array_map(static fn(array $metric): int => (int) $metric['value'], $visibleMetrics ?: [['value' => 1]]));
$metricColors = ['#0b4fa8', '#0284c7', '#06b6d4', '#eab308', '#22c55e', '#f97316', '#a855f7', '#ec4899', '#64748b', '#14b8a6'];
$metricTotal = array_sum(array_map(static fn(array $metric): int => max(0, (int) $metric['value']), $visibleMetrics));
$metricGradientParts = [];
$metricOffset = 0.0;
foreach ($visibleMetrics as $index => $metric) {
    $value = max(0, (int) $metric['value']);
    if ($metricTotal <= 0 || $value <= 0) {
        continue;
    }

    $percent = ($value / $metricTotal) * 100;
    $color = $metricColors[$index % count($metricColors)];
    $metricGradientParts[] = sprintf('%s %.4F%% %.4F%%', $color, $metricOffset, $metricOffset + $percent);
    $metricOffset += $percent;
}
$metricGradient = $metricGradientParts ? implode(', ', $metricGradientParts) : '#dbe4ee 0 100%';

render_view('admin/dashboard.php', get_defined_vars(), 'admin');
