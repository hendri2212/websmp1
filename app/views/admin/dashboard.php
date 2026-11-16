<div class="admin-card dashboard-hero polished-dashboard-hero">
    <div class="dashboard-hero-copy">
        <span class="status-badge status-diverifikasi"><?= e(admin_role_label()); ?></span>
        <h1>Dashboard Admin</h1>
        <p>Ringkasan operasional website sekolah: konten, informasi PPDB, pesan masuk, agenda.</p>
    </div>
    <div class="dashboard-quick-actions">
        <?php if (admin_can('settings')): ?><a class="button secondary" href="<?= e(base_url('admin/settings.php')); ?>">Pengaturan</a><?php endif; ?>
        <?php if (admin_can('ppdb_content')): ?><a class="button secondary" href="<?= e(base_url('admin/ppdb.php')); ?>">Informasi PPDB</a><?php endif; ?>
        <?php if (admin_can('contact_messages')): ?><a class="button secondary" href="<?= e(base_url('admin/contact_messages.php')); ?>">Pesan</a><?php endif; ?>
        <?php if (admin_can('db_backup')): ?><a class="button primary blue" href="<?= e(base_url('admin/db_backup.php')); ?>">Cadangkan Data</a><?php endif; ?>
    </div>
    <?php if (!$dbReady): ?>
        <div class="alert warning">Data belum dapat dimuat. Silakan coba lagi.</div>
    <?php endif; ?>
</div>

<div class="dashboard-stat-grid">
    <?php if (admin_can('contact_messages')): ?>
        <a class="dashboard-stat-card stat-message" href="<?= e(base_url('admin/contact_messages.php')); ?>">
            <span>Pesan Baru</span>
            <strong><?= (int) $newMessages; ?></strong>
            <small><?= (int) $totalMessages; ?> total pesan</small>
        </a>
    <?php endif; ?>
    <?php if (admin_can('news')): ?>
        <a class="dashboard-stat-card stat-news" href="<?= e(base_url('admin/news.php')); ?>">
            <span>Berita</span>
            <strong><?= (int) $totalNews; ?></strong>
            <small>publikasi tersimpan</small>
        </a>
    <?php endif; ?>
    <?php if (admin_can('agenda')): ?>
        <a class="dashboard-stat-card stat-agenda" href="<?= e(base_url('admin/agenda.php')); ?>">
            <span>Agenda Aktif</span>
            <strong><?= (int) $activeAgenda; ?></strong>
            <small>kegiatan tampil publik</small>
        </a>
    <?php endif; ?>
</div>

<?php if ($newMessages > 0 && admin_can('contact_messages')): ?>
    <div class="admin-card dashboard-notice">
        <div>
            <h2>Butuh Tindak Lanjut</h2>
            <p class="muted">Ada data baru yang perlu diperiksa operator.</p>
        </div>
        <div class="dashboard-notice-actions">
            <?php if ($newMessages > 0 && admin_can('contact_messages')): ?>
                <a class="button secondary" href="<?= e(base_url('admin/contact_messages.php')); ?>">
                    <?= (int) $newMessages; ?> pesan baru
                </a>
            <?php endif; ?>
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
                    <a class="content-chart-item" href="<?= e(base_url($metric['href'])); ?>">
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
                <a class="publish-status-card" href="<?= e(base_url($row['href'])); ?>">
                    <span><?= e($row['label']); ?></span>
                    <strong><?= (int) $active; ?> publish</strong>
                    <small><?= (int) $draft; ?> draft dari <?= (int) $total; ?> data</small>
                    <div class="publish-status-bar" aria-hidden="true"><i style="width: <?= (int) $percent; ?>%"></i></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="dashboard-two-col">
    <?php if (admin_can('agenda')): ?>
        <div class="admin-card">
            <div class="dashboard-section-head">
                <div>
                    <h2>Agenda Terdekat</h2>
                    <p class="muted">Lima agenda aktif yang paling dekat.</p>
                </div>
                <a class="button secondary" href="<?= e(base_url('admin/agenda.php')); ?>">Agenda</a>
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


</div>
