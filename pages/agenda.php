<?php $agendaRows = agenda_items(); ?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Agenda</span>
        <h1>Agenda dan Kalender Kegiatan <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Informasi jadwal kegiatan akademik, kesiswaan, rapat, asesmen, dan agenda penting sekolah tahun ajaran <?= e(current_academic_year()); ?>.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Kalender Sekolah</span>
            <h2>Kegiatan terjadwal</h2>
            <p>Agenda ini diperbarui oleh admin sekolah sesuai jadwal resmi tahun ajaran <?= e(current_academic_year()); ?>.</p>
        </div>
        <?php if (!$agendaRows): ?>
            <article class="panel">
                <p class="muted">Belum ada agenda kegiatan yang ditayangkan.</p>
            </article>
        <?php else: ?>
            <div class="agenda-list">
                <?php foreach ($agendaRows as $agenda): ?>
                    <?php
                    $dateLabel = $agenda['date_label'] ?? date('d F Y H:i', strtotime($agenda['starts_at']));
                    $endLabel = !empty($agenda['ends_at']) ? date('d F Y H:i', strtotime($agenda['ends_at'])) : '';
                    ?>
                    <article class="agenda-card">
                        <div class="agenda-date">
                            <strong><?= e($dateLabel); ?></strong>
                            <?php if ($endLabel): ?><span>s.d. <?= e($endLabel); ?></span><?php endif; ?>
                        </div>
                        <div>
                            <span class="eyebrow"><?= e($agenda['category'] ?? 'Agenda Sekolah'); ?></span>
                            <h3><?= e($agenda['title']); ?></h3>
                            <p><?= e($agenda['description']); ?></p>
                            <p class="muted">Lokasi: <?= e($agenda['location'] ?? 'Sekolah'); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
