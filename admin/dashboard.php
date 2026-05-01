<?php
require __DIR__ . '/../includes/bootstrap.php';
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
$newPpdbApplications = $dbReady ? ppdb_application_count('baru') : 0;
$verifiedPpdbApplications = $dbReady ? ppdb_application_count('diverifikasi') : 0;
$acceptedPpdbApplications = $dbReady ? ppdb_application_count('diterima') : 0;
$rejectedPpdbApplications = $dbReady ? ppdb_application_count('ditolak') : 0;
$totalPpdbApplications = $dbReady ? ppdb_application_count() : 0;
$activeAnnouncements = $dbReady ? announcement_count(true) : 0;
<<<<<<< HEAD
$totalNews = news_count(true);
=======
$totalNews = news_count();
>>>>>>> 98147e3 (upload pertama)
$totalGallery = gallery_count();
$activeStaff = staff_count(true);
$activeAgenda = agenda_count(true);
$activeDocuments = document_count(true);
$activeAchievements = achievement_count(true);
$activeExtracurriculars = extracurricular_count(true);
$activeFacilities = facility_count(true);
$activeFaqs = faq_count(true);
<<<<<<< HEAD
$upcomingAgenda = $dbReady ? upcoming_agenda_items(5) : [];
$recentLogs = ($dbReady && admin_can('activity_logs')) ? admin_activity_logs([], 6) : [];
$contentStatusRows = array_values(array_filter(
    $dbReady ? admin_content_status_summary() : [],
    static fn(array $row): bool => admin_can($row['permission'])
));
=======
$upcomingAgenda = $dbReady ? agenda_items(5) : [];
$recentLogs = ($dbReady && admin_can('activity_logs')) ? admin_activity_logs([], 6) : [];
>>>>>>> 98147e3 (upload pertama)

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

require __DIR__ . '/../templates/admin_header.php';
?>
<div class="admin-card dashboard-hero polished-dashboard-hero">
    <div class="dashboard-hero-copy">
        <span class="status-badge status-diverifikasi"><?= e(admin_role_label()); ?></span>
        <h1>Dashboard Admin</h1>
        <p>Ringkasan operasional website sekolah: konten, PPDB, pesan masuk, agenda, dan aktivitas admin terbaru.</p>
    </div>
    <div class="dashboard-quick-actions">
        <?php if (admin_can('settings')): ?><a class="button secondary" href="<?= base_url('admin/settings.php'); ?>">Pengaturan</a><?php endif; ?>
        <?php if (admin_can('ppdb_applications')): ?><a class="button secondary" href="<?= base_url('admin/ppdb_applications.php'); ?>">PPDB</a><?php endif; ?>
        <?php if (admin_can('contact_messages')): ?><a class="button secondary" href="<?= base_url('admin/contact_messages.php'); ?>">Pesan</a><?php endif; ?>
        <?php if (admin_can('production_check')): ?><a class="button secondary" href="<?= base_url('admin/production_check.php'); ?>">Cek Production</a><?php endif; ?>
        <?php if (admin_can('db_backup')): ?><a class="button primary blue" href="<?= base_url('admin/db_backup.php'); ?>">Backup</a><?php endif; ?>
    </div>
    <div class="alert dashboard-db-alert <?= $dbReady ? 'success' : 'warning'; ?>">
        <?= $dbReady ? 'Database MySQL terhubung. Fitur CRUD dan laporan siap dipakai.' : 'Database belum terhubung. Website publik tetap memakai seed data, tetapi laporan admin akan lengkap setelah MySQL aktif.'; ?>
    </div>
</div>

<div class="dashboard-stat-grid">
    <?php if (admin_can('ppdb_applications')): ?>
        <a class="dashboard-stat-card stat-ppdb" href="<?= base_url('admin/ppdb_applications.php?status=baru'); ?>">
            <span>PPDB Baru</span>
            <strong><?= (int) $newPpdbApplications; ?></strong>
            <small><?= (int) $totalPpdbApplications; ?> total pendaftar</small>
        </a>
    <?php endif; ?>
    <?php if (admin_can('contact_messages')): ?>
        <a class="dashboard-stat-card stat-message" href="<?= base_url('admin/contact_messages.php'); ?>">
            <span>Pesan Baru</span>
            <strong><?= (int) $newMessages; ?></strong>
            <small><?= (int) $totalMessages; ?> total pesan</small>
        </a>
    <?php endif; ?>
    <?php if (admin_can('news')): ?>
        <a class="dashboard-stat-card stat-news" href="<?= base_url('admin/news.php'); ?>">
            <span>Berita</span>
            <strong><?= (int) $totalNews; ?></strong>
            <small>publikasi tersimpan</small>
        </a>
    <?php endif; ?>
    <?php if (admin_can('agenda')): ?>
        <a class="dashboard-stat-card stat-agenda" href="<?= base_url('admin/agenda.php'); ?>">
            <span>Agenda Aktif</span>
            <strong><?= (int) $activeAgenda; ?></strong>
            <small>kegiatan tampil publik</small>
        </a>
    <?php endif; ?>
</div>

<?php if (($newPpdbApplications > 0 && admin_can('ppdb_applications')) || ($newMessages > 0 && admin_can('contact_messages'))): ?>
    <div class="admin-card dashboard-notice">
        <div>
            <h2>Butuh Tindak Lanjut</h2>
            <p class="muted">Ada data baru yang perlu diperiksa operator.</p>
        </div>
        <div class="dashboard-notice-actions">
            <?php if ($newPpdbApplications > 0 && admin_can('ppdb_applications')): ?>
                <a class="button primary blue" href="<?= base_url('admin/ppdb_applications.php?status=baru'); ?>">
                    <?= (int) $newPpdbApplications; ?> PPDB baru
                </a>
            <?php endif; ?>
            <?php if ($newMessages > 0 && admin_can('contact_messages')): ?>
                <a class="button secondary" href="<?= base_url('admin/contact_messages.php'); ?>">
                    <?= (int) $newMessages; ?> pesan baru
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (admin_can('ppdb_applications')): ?>
    <div class="admin-card dashboard-chart-card">
        <div class="dashboard-section-head">
            <div>
                <h2>Status PPDB</h2>
                <p class="muted">Distribusi status pendaftar saat ini.</p>
            </div>
            <a class="button secondary" href="<?= base_url('admin/ppdb_applications.php'); ?>">Kelola PPDB</a>
        </div>
        <?php
        $ppdbStatusRows = [
            ['label' => 'Baru', 'value' => $newPpdbApplications, 'class' => 'status-baru', 'color' => '#2563eb'],
            ['label' => 'Diverifikasi', 'value' => $verifiedPpdbApplications, 'class' => 'status-diverifikasi', 'color' => '#06b6d4'],
            ['label' => 'Diterima', 'value' => $acceptedPpdbApplications, 'class' => 'status-diterima', 'color' => '#22c55e'],
            ['label' => 'Ditolak', 'value' => $rejectedPpdbApplications, 'class' => 'status-ditolak', 'color' => '#ef4444'],
        ];
        $ppdbGradientParts = [];
        $ppdbOffset = 0.0;
        foreach ($ppdbStatusRows as $row) {
            $value = max(0, (int) $row['value']);
            if ($totalPpdbApplications <= 0 || $value <= 0) {
                continue;
            }

            $percent = ($value / $totalPpdbApplications) * 100;
            $ppdbGradientParts[] = sprintf('%s %.4F%% %.4F%%', $row['color'], $ppdbOffset, $ppdbOffset + $percent);
            $ppdbOffset += $percent;
        }
        $ppdbGradient = $ppdbGradientParts ? implode(', ', $ppdbGradientParts) : '#dbe4ee 0 100%';
        ?>
        <div class="content-chart-grid ppdb-chart-grid">
            <div class="content-donut-wrap">
                <div class="content-donut ppdb-donut" style="--donut-chart: <?= e($ppdbGradient); ?>">
                    <div>
                        <strong><?= (int) $totalPpdbApplications; ?></strong>
                        <span>Total PPDB</span>
                    </div>
                </div>
            </div>
            <div class="content-pie-bar ppdb-pie-bar" aria-label="Bar of pie status PPDB">
                <?php foreach ($ppdbStatusRows as $row): ?>
                    <?php
                    $value = max(0, (int) $row['value']);
                    $height = $totalPpdbApplications > 0 && $value > 0 ? max(4, ($value / $totalPpdbApplications) * 100) : 0;
                    ?>
                    <?php if ($height > 0): ?>
                        <span
                            style="--bar-height: <?= e((string) $height); ?>%; --bar-color: <?= e($row['color']); ?>"
                            title="<?= e($row['label'] . ': ' . $value); ?>"
                        ></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="content-chart-list ppdb-chart-list">
            <?php foreach ($ppdbStatusRows as $row): ?>
                <div class="content-chart-item">
                    <span class="chart-dot" style="background: <?= e($row['color']); ?>"></span>
                    <span class="status-badge <?= e($row['class']); ?>"><?= e($row['label']); ?></span>
                    <strong><?= (int) $row['value']; ?></strong>
                    <small><?= $totalPpdbApplications > 0 ? e((string) round(((int) $row['value'] / $totalPpdbApplications) * 100)) : '0'; ?>%</small>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($visibleMetrics): ?>
    <div class="admin-card dashboard-chart-card">
        <div class="dashboard-section-head">
            <div>
                <h2>Ringkasan Konten</h2>
                <p class="muted">Jumlah data aktif atau tersimpan di modul konten utama.</p>
            </div>
        </div>
        <div class="content-chart-grid">
            <div class="content-donut-wrap">
                <div class="content-donut" style="--donut-chart: <?= e($metricGradient); ?>">
                    <div>
                        <strong><?= (int) $metricTotal; ?></strong>
                        <span>Total Konten</span>
                    </div>
                </div>
            </div>
            <div class="content-pie-bar" aria-label="Bar of pie ringkasan konten">
                <?php foreach ($visibleMetrics as $index => $metric): ?>
                    <?php
                    $value = max(0, (int) $metric['value']);
                    $height = $metricTotal > 0 && $value > 0 ? max(4, ($value / $metricTotal) * 100) : 0;
                    ?>
                    <?php if ($height > 0): ?>
                        <span
                            style="--bar-height: <?= e((string) $height); ?>%; --bar-color: <?= e($metricColors[$index % count($metricColors)]); ?>"
                            title="<?= e($metric['label'] . ': ' . $value); ?>"
                        ></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="content-chart-list">
                <?php foreach ($visibleMetrics as $index => $metric): ?>
                    <?php $value = max(0, (int) $metric['value']); ?>
                    <a class="content-chart-item" href="<?= base_url($metric['href']); ?>">
                        <span class="chart-dot" style="background: <?= e($metricColors[$index % count($metricColors)]); ?>"></span>
                        <span><?= e($metric['label']); ?></span>
                        <strong><?= (int) $value; ?></strong>
                        <small><?= $metricTotal > 0 ? e((string) round(($value / $metricTotal) * 100)) : '0'; ?>%</small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<<<<<<< HEAD
<?php if ($contentStatusRows): ?>
    <div class="admin-card dashboard-chart-card">
        <div class="dashboard-section-head">
            <div>
                <h2>Status Publikasi Konten</h2>
                <p class="muted">Pantau konten yang sudah tampil publik dan yang masih tersimpan sebagai draft.</p>
            </div>
        </div>
        <div class="publish-status-grid">
            <?php foreach ($contentStatusRows as $row): ?>
                <?php
                $total = max(0, (int) $row['total']);
                $active = max(0, (int) $row['active']);
                $draft = max(0, $total - $active);
                $percent = $total > 0 ? max(4, min(100, (int) round(($active / $total) * 100))) : 0;
                ?>
                <a class="publish-status-card" href="<?= base_url($row['href']); ?>">
                    <span><?= e($row['label']); ?></span>
                    <strong><?= (int) $active; ?> publish</strong>
                    <small><?= (int) $draft; ?> draft dari <?= (int) $total; ?> data</small>
                    <div class="publish-status-bar" aria-hidden="true"><i style="width: <?= (int) $percent; ?>%"></i></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

=======
>>>>>>> 98147e3 (upload pertama)
<div class="dashboard-two-col">
    <?php if (admin_can('agenda')): ?>
        <div class="admin-card">
            <div class="dashboard-section-head">
                <div>
                    <h2>Agenda Terdekat</h2>
                    <p class="muted">Lima agenda aktif yang paling dekat.</p>
                </div>
                <a class="button secondary" href="<?= base_url('admin/agenda.php'); ?>">Agenda</a>
            </div>
            <?php if (!$upcomingAgenda): ?>
                <p class="muted">Belum ada agenda aktif.</p>
            <?php else: ?>
                <div class="dashboard-list">
                    <?php foreach ($upcomingAgenda as $agenda): ?>
                        <div class="dashboard-list-item">
                            <strong><?= e($agenda['title']); ?></strong>
                            <span><?= e(!empty($agenda['starts_at']) ? date('d M Y H:i', strtotime($agenda['starts_at'])) : ($agenda['date_label'] ?? '-')); ?></span>
                            <small><?= e($agenda['location'] ?? 'Sekolah'); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (admin_can('activity_logs')): ?>
        <div class="admin-card">
            <div class="dashboard-section-head">
                <div>
                    <h2>Aktivitas Terbaru</h2>
                    <p class="muted">Riwayat singkat aksi admin.</p>
                </div>
                <a class="button secondary" href="<?= base_url('admin/activity_logs.php'); ?>">Log</a>
            </div>
            <?php if (!$recentLogs): ?>
                <p class="muted">Belum ada aktivitas admin yang tercatat.</p>
            <?php else: ?>
                <div class="dashboard-list">
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="dashboard-list-item">
                            <strong><?= e($log['description']); ?></strong>
                            <span><?= e(($log['username'] ?? '-') . ' - ' . date('d M Y H:i', strtotime($log['created_at']))); ?></span>
                            <small><?= e($log['module'] . ' / ' . $log['action']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../templates/admin_footer.php'; ?>
