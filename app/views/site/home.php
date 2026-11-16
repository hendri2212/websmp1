<?php if ($homeAnnouncements): ?>
<section class="announcement-band" aria-labelledby="home-announcements-heading">
    <div class="container">
        <div class="announcement-head">
            <div>
                <span class="eyebrow">Informasi Sekolah</span>
                <h2 id="home-announcements-heading">Pengumuman</h2>
            </div>
            <?php if (count($homeAnnouncements) > 1): ?>
                <div class="carousel-actions" aria-label="Navigasi pengumuman">
                    <button class="carousel-button" type="button" data-carousel-prev="home-announcement-carousel" aria-label="Geser pengumuman ke kiri">&#8249;</button>
                    <button class="carousel-button" type="button" data-carousel-next="home-announcement-carousel" aria-label="Geser pengumuman ke kanan">&#8250;</button>
                </div>
            <?php endif; ?>
        </div>
        <div class="announcement-carousel" id="home-announcement-carousel">
            <?php foreach ($homeAnnouncements as $announcement): ?>
                <?php $announcementLink = safe_content_url($announcement['link_url'] ?? ''); ?>
                <article class="announcement-card public-announcement-card">
                    <div>
                        <h3><?= e($announcement['title']); ?></h3>
                        <p><?= nl2br(e($announcement['content'])); ?></p>
                        <?php if ($announcementLink !== ''): ?>
                            <a class="text-link announcement-more" href="<?= e(content_link_url($announcementLink)); ?>"><?= e(trim($announcement['link_label'] ?? '') ?: 'Selengkapnya'); ?></a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<section class="hero home-hero">
    <div class="hero-glow"></div>
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow inverse"><?= e($school['tagline']); ?></span>
            <h1><?= e($heroTitle); ?></h1>
            <p class="hero-text"><?= e($heroSubtitle); ?></p>
            <div class="cta-row">
                <a class="button primary" href="<?= e($primaryButtonHref); ?>"><?= e($primaryButtonLabel); ?></a>
                <a class="button ghost" href="<?= e($secondaryButtonHref); ?>"><?= e($secondaryButtonLabel); ?></a>
            </div>
        </div>
        <div class="hero-side hero-showcase home-mobile-rail hero-side-rail hero-with-achievements">
            <section class="hero-achievements" aria-labelledby="hero-achievements-heading" <?= count($heroAchievements) > 1 ? 'data-achievement-slider' : ''; ?>>
                <div class="hero-achievements-head">
                    <h2 id="hero-achievements-heading">Spensa Update</h2>
                    <a href="<?= e(base_url('prestasi')); ?>">Lihat semua &#8594;</a>
                </div>
                <?php if ($heroAchievements): ?>
                <div class="hero-achievements-slides">
                    <?php foreach ($heroAchievements as $slideIndex => $achievement): ?>
                    <?php $achievementHref = base_url('prestasi/' . (int) $achievement['id']); ?>
                    <article class="hero-achievement-slide" <?= $slideIndex > 0 ? 'hidden' : ''; ?> aria-label="<?= e('Prestasi ' . ($slideIndex + 1) . ' dari ' . count($heroAchievements)); ?>">
                        <a class="hero-achievement-image" href="<?= e($achievementHref); ?>" aria-label="<?= e('Lihat prestasi: ' . $achievement['title']); ?>">
                            <?php if (is_placeholder_image($achievement['image_path'] ?? null)): ?>
                            <span class="hero-achievement-placeholder"><?= e(initials_from_text($achievement['title'])); ?></span>
                            <?php else: ?>
                            <img src="<?= e(image_asset_path($achievement['image_path'])); ?>" alt="<?= e($achievement['title']); ?>" loading="<?= $slideIndex === 0 ? 'eager' : 'lazy'; ?>">
                            <?php endif; ?>
                        </a>
                        <div class="hero-achievement-copy">
                            <span class="hero-achievement-label"><?= e(trim($achievement['category'] ?? '') ?: 'Prestasi'); ?></span>
                            <h3><a href="<?= e($achievementHref); ?>"><?= e($achievement['title']); ?></a></h3>
                            <?php if (!empty($achievement['achievement_date'])): ?>
                            <time datetime="<?= e($achievement['achievement_date']); ?>"><?= e(date('d M Y', strtotime($achievement['achievement_date']))); ?></time>
                            <?php endif; ?>
                            <a class="hero-achievement-link" href="<?= e($achievementHref); ?>">Lihat Prestasi &#8594;</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php if (count($heroAchievements) > 1): ?>
                <div class="hero-achievement-controls">
                    <div class="hero-achievement-dots" aria-label="Pilih prestasi">
                        <?php foreach ($heroAchievements as $slideIndex => $achievement): ?>
                        <button type="button" data-achievement-dot="<?= $slideIndex; ?>" aria-label="<?= e('Tampilkan prestasi ' . ($slideIndex + 1)); ?>" aria-pressed="<?= $slideIndex === 0 ? 'true' : 'false'; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="hero-achievement-pause" data-achievement-pause aria-label="Jeda pergantian prestasi" title="Jeda pergantian prestasi">&#9208;</button>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="hero-achievements-empty">
                    <p>Belum ada prestasi yang dipublikasikan.</p>
                    <a class="hero-achievement-link" href="<?= e(base_url('prestasi')); ?>">Lihat Prestasi &#8594;</a>
                </div>
                <?php endif; ?>
            </section>
            <?php if ($showStats): ?>
            <div class="stats-card home-rail-item">
                <?php foreach ($content['stats'] as $item): ?>
                    <div>
                        <strong><?= e($item['value']); ?></strong>
                        <span><?= e($item['label']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($showPrincipalCard && $content['principalMessage']['name'] !== ''): ?>
            <div class="principal-card home-rail-item">
                <?php if ($principalPhotoPath !== ''): ?>
                <img src="<?= e(asset(ltrim($principalPhotoPath, '/'))); ?>" alt="Kepala sekolah">
                <?php endif; ?>
                <div>
                    <span class="eyebrow">Sambutan Singkat</span>
                    <h3>Mewujudkan generasi yang unggul dalam prestasi, karakter, dan pemanfaatan teknologi.</h3>
                    <p><?= e($content['principalMessage']['name']); ?> - <?= e($content['principalMessage']['title']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php if (($homeSettings['showQuickLinks'] ?? true) && !empty($content['quickLinks'])): ?>
        <div class="quick-grid quick-icon-grid hero-quick-grid home-mobile-rail" aria-label="Menu informasi cepat">
            <?php foreach ($content['quickLinks'] as $item): ?>
                <a class="quick-card quick-icon-card home-rail-item" href="<?= e(base_url(ltrim($item['href'], '/'))); ?>" aria-label="<?= e($item['title'] . '. ' . $item['desc']); ?>">
                    <span class="quick-icon"><?= home_quick_link_icon($item['href']); ?></span>
                    <strong><?= e($item['title']); ?></strong>
                    <span class="quick-card-desc"><?= e($item['desc']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="section home-video-section">
    <div class="container">
        <div class="section-head-row compact">
            <div class="section-heading">
                <span class="eyebrow">Video Profil</span>
                <h2><?= e($profileVideoTitle); ?></h2>
                <p><?= e($profileVideoDescription); ?></p>
            </div>
        </div>
        <article class="home-video-panel">
            <?php if ($profileVideoFileUrl !== ''): ?>
                <video controls muted playsinline preload="metadata" aria-label="<?= e($profileVideoTitle); ?>" src="<?= e($profileVideoFileUrl); ?>">
                    Browser Anda tidak mendukung pemutaran video.
                </video>
                <p class="profile-video-error" data-profile-video-error role="alert" hidden>Video tidak dapat diputar di browser ini.</p>
            <?php elseif ($profileVideoUrl !== ''): ?>
                <iframe id="profile-youtube-player" data-youtube-autoplay src="<?= e($profileVideoUrl . '?enablejsapi=1&playsinline=1'); ?>" title="<?= e($profileVideoTitle); ?>" loading="lazy" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                <div class="profile-youtube-fallback">
                    <p data-youtube-error role="status" hidden>Video tidak dapat diputar di halaman ini. Silakan tonton melalui YouTube.</p>
                    <a class="text-link" href="<?= e($profileYoutubeWatchUrl); ?>" target="_blank" rel="noopener noreferrer">Tonton di YouTube</a>
                </div>
            <?php else: ?>
                <div class="home-video-placeholder">
                    <span aria-hidden="true"></span>
                    <div>
                        <strong>Video profil sekolah</strong>
                        <p>Konten video dapat ditambahkan saat materi profil resmi sudah siap.</p>
                    </div>
                </div>
            <?php endif; ?>
        </article>
    </div>
</section>

<?php if (($homeSettings['showProfileIntro'] ?? false) && $content['homeProfileIntro']['text'] !== ''): ?>
<section class="section home-profile-section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Profil Sekolah</span>
            <h2><?= e($content['homeProfileIntro']['title']); ?></h2>
            <p><?= e($content['homeProfileIntro']['description']); ?></p>
        </div>
        <div class="two-grid home-mobile-rail">
            <article class="panel home-rail-item">
                <h3><?= e($content['homeProfileIntro']['heading']); ?></h3>
                <p><?= e($content['homeProfileIntro']['text']); ?></p>
            </article>
            <article class="panel dark home-rail-item">
                <span class="eyebrow inverse">Keunggulan</span>
                <ul class="feature-list">
                    <?php foreach ($content['excellencePrograms'] as $item): ?>
                        <li><?= e($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </article>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (($homeSettings['showTeacherCarousel'] ?? true) && $homeStaffMembers): ?>
<section class="section alt">
    <div class="container">
        <div class="teacher-strip-head">
            <h2>Guru Pengajar</h2>
            <a href="<?= e(base_url('guru-staf')); ?>">Selengkapnya</a>
        </div>
        <div class="teacher-strip-wrap">
            <div class="carousel-actions teacher-carousel-actions" aria-label="Navigasi foto guru">
                <button class="carousel-button" type="button" data-carousel-prev="home-staff-carousel" aria-label="Geser foto guru ke kiri">&#8249;</button>
                <button class="carousel-button" type="button" data-carousel-next="home-staff-carousel" aria-label="Geser foto guru ke kanan">&#8250;</button>
            </div>
            <div class="teacher-carousel" id="home-staff-carousel">
                <?php foreach ($homeStaffMembers as $staff): ?>
                    <?php $displayName = preg_replace('/,?\s*(S\.?\s?Pd\.?(?:\.?I|\.?Ing)?|M\.?Pd\.?|S\.?Ag|S\.?Sos|SE|A\.?Md|S\.?\s?IP)\b.*$/i', '', $staff['name']); ?>
                    <article class="teacher-slide">
                        <?php if (is_placeholder_image($staff['photo_path'] ?? null)): ?>
                            <div class="visual-placeholder portrait-placeholder" aria-hidden="true">
                                <span><?= e(initials_from_text($displayName ?: $staff['name'])); ?></span>
                            </div>
                        <?php else: ?>
                            <img src="<?= e(image_asset_path($staff['photo_path'])); ?>" alt="<?= e($staff['name']); ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="teacher-slide-name">
                            <strong><?= e($displayName ?: $staff['name']); ?></strong>
                            <span><?= e($staff['subject']); ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($homeSettings['showAgenda'] ?? true): ?>
<section class="section home-agenda-section">
    <div class="container">
        <div class="section-head-row compact">
            <div class="section-heading">
                <span class="eyebrow">Agenda Terdekat</span>
                <h2>Kalender kegiatan sekolah</h2>
                <p>Ringkasan kegiatan terdekat yang sedang ditayangkan oleh admin sekolah.</p>
            </div>
            <div class="carousel-actions home-section-actions" aria-label="Navigasi agenda">
                <button class="carousel-button" type="button" data-carousel-prev="home-agenda-carousel" aria-label="Geser agenda ke kiri">&#8249;</button>
                <button class="carousel-button" type="button" data-carousel-next="home-agenda-carousel" aria-label="Geser agenda ke kanan">&#8250;</button>
            </div>
        </div>
        <div class="card-grid three home-mobile-rail" id="home-agenda-carousel">
            <?php foreach ($upcomingAgenda as $agenda): ?>
                <?php $dateLabel = $agenda['date_label'] ?? date('d F Y H:i', strtotime($agenda['starts_at'])); ?>
                <article class="card agenda-mini home-rail-item">
                    <div class="agenda-mini-date">
                        <strong><?= e(!empty($agenda['starts_at']) ? date('d', strtotime($agenda['starts_at'])) : $dateLabel); ?></strong>
                        <span><?= e(!empty($agenda['starts_at']) ? date('M Y', strtotime($agenda['starts_at'])) : 'Agenda'); ?></span>
                    </div>
                    <div>
                        <span class="eyebrow"><?= e($agenda['category'] ?? 'Agenda'); ?></span>
                        <h3><?= e($agenda['title']); ?></h3>
                        <p><strong><?= e($dateLabel); ?></strong></p>
                        <p><?= e(plain_text_excerpt($agenda['description'], 120)); ?></p>
                        <p class="muted">Lokasi: <?= e($agenda['location'] ?? 'Sekolah'); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="cta-row">
            <a class="button secondary" href="<?= e(base_url('agenda')); ?>">Lihat Semua Agenda</a>
        </div>
    </div>
</section>
<?php endif; ?>
