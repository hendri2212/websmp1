<?php $school = $content['schoolProfile']; ?>
<?php $logoPath = $school['logoPath'] ?? '/public/logo-sekolah.png'; ?>
<?php $principalPhotoPath = $content['principalMessage']['photoPath'] ?? '/public/kepala-sekolah.jpeg'; ?>
<?php $homeSettings = $content['siteHome'] ?? []; ?>
<?php $homeAchievements = home_achievements(); ?>
<?php
$teacherLimit = max(4, min(24, (int) ($homeSettings['teacherLimit'] ?? 12)));
$agendaLimit = max(1, min(9, (int) ($homeSettings['agendaLimit'] ?? 3)));
$upcomingAgenda = upcoming_agenda_items($agendaLimit);
$homeStaffMembers = array_slice(staff_members(), 0, $teacherLimit);
$heroTitle = trim($homeSettings['heroTitle'] ?? '') ?: $school['name'];
$heroSubtitle = trim($homeSettings['heroSubtitle'] ?? '') ?: $school['heroDescription'];
$primaryButtonLabel = trim($homeSettings['primaryButtonLabel'] ?? '') ?: 'Lihat Profil Sekolah';
$primaryButtonUrl = trim($homeSettings['primaryButtonUrl'] ?? '') ?: '/profil';
$secondaryButtonLabel = trim($homeSettings['secondaryButtonLabel'] ?? '') ?: 'Informasi PPDB';
$secondaryButtonUrl = trim($homeSettings['secondaryButtonUrl'] ?? '') ?: '/ppdb';
$profileVideoUrl = video_embed_url($homeSettings['profileVideoUrl'] ?? '');
$profileVideoTitle = trim($homeSettings['profileVideoTitle'] ?? '') ?: 'Profil sekolah dalam satu tayangan';
$profileVideoDescription = trim($homeSettings['profileVideoDescription'] ?? '') ?: 'Ruang ini disiapkan untuk menampilkan video profil resmi sekolah, mulai dari lingkungan belajar, kegiatan siswa, fasilitas, hingga budaya sekolah.';
$primaryButtonHref = str_starts_with($primaryButtonUrl, 'http') ? $primaryButtonUrl : base_url(ltrim($primaryButtonUrl, '/'));
$secondaryButtonHref = str_starts_with($secondaryButtonUrl, 'http') ? $secondaryButtonUrl : base_url(ltrim($secondaryButtonUrl, '/'));
$showLogoCard = $homeSettings['showLogoCard'] ?? true;
$showStats = $homeSettings['showStats'] ?? true;
$showPrincipalCard = $homeSettings['showPrincipalCard'] ?? true;
$showHeroSide = $showLogoCard || $showStats || $showPrincipalCard;

if (!function_exists('home_quick_link_icon')) {
    function home_quick_link_icon(string $href): string
    {
        $path = '/' . trim(parse_url($href, PHP_URL_PATH) ?: $href, '/');
        $icons = [
            '/profil' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="9" y="14" width="46" height="38" rx="5" fill="#bfdbfe"/><circle cx="24" cy="29" r="7" fill="#f59e0b"/><path d="M14 48c2-10 18-10 20 0" fill="#0b4fa8"/><rect x="38" y="22" width="10" height="6" rx="2" fill="#06b6d4"/><rect x="38" y="34" width="12" height="5" rx="2" fill="#0284c7"/></svg>',
            '/fasilitas' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M12 52h40V26L32 12 12 26z" fill="#fb923c"/><path d="M20 52V31h24v21" fill="#0284c7"/><rect x="26" y="38" width="12" height="14" rx="2" fill="#dbeafe"/><path d="M28 25h8v8h-8z" fill="#fef3c7"/></svg>',
            '/akademik' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="14" y="10" width="34" height="44" rx="4" fill="#e0f2fe"/><path d="M22 20h18M22 29h20M22 38h14" stroke="#0b4fa8" stroke-width="4" stroke-linecap="round"/><path d="M44 13l8 8-8 8" fill="#f59e0b"/></svg>',
            '/ekstrakurikuler' => '<svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="24" cy="39" r="13" fill="#fb923c"/><path d="M13 39h22M24 26c5 6 5 20 0 26M16 30c6 4 10 4 16 0" stroke="#fff7ed" stroke-width="3" fill="none"/><path d="M39 16l13 13M52 16L39 29" stroke="#06b6d4" stroke-width="6" stroke-linecap="round"/><circle cx="46" cy="23" r="12" fill="none" stroke="#22c55e" stroke-width="4"/></svg>',
            '/ppdb' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="13" y="12" width="38" height="44" rx="5" fill="#dbeafe"/><path d="M22 25h20M22 34h20M22 43h12" stroke="#0b4fa8" stroke-width="4" stroke-linecap="round"/><path d="M42 10l8 8-8 8" fill="#eab308"/></svg>',
            '/dokumen' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M18 8h21l9 9v39H18z" fill="#e0f2fe"/><path d="M39 8v12h11" fill="#bfdbfe"/><path d="M24 29h18M24 38h18M24 47h12" stroke="#0b4fa8" stroke-width="4" stroke-linecap="round"/></svg>',
            '/prestasi' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M20 10h24v14c0 8-5 15-12 18-7-3-12-10-12-18z" fill="#eab308"/><path d="M16 16H8c0 10 5 16 13 18M48 16h8c0 10-5 16-13 18" stroke="#fb923c" stroke-width="5" fill="none" stroke-linecap="round"/><path d="M32 42v10M24 54h16" stroke="#0b4fa8" stroke-width="5" stroke-linecap="round"/></svg>',
            '/galeri' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="10" y="16" width="44" height="34" rx="5" fill="#bfdbfe"/><circle cx="24" cy="28" r="5" fill="#f59e0b"/><path d="M14 48l13-13 9 8 7-7 9 12z" fill="#0284c7"/></svg>',
            '/kontak' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="10" y="16" width="44" height="34" rx="6" fill="#e0f2fe"/><path d="M13 20l19 17 19-17" stroke="#0b4fa8" stroke-width="5" fill="none" stroke-linecap="round"/><path d="M18 47l12-12M46 47L34 35" stroke="#06b6d4" stroke-width="4" stroke-linecap="round"/></svg>',
            '/faq' => '<svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="24" fill="#dbeafe"/><path d="M24 25c1-6 7-9 13-6 6 4 4 11-1 14-3 2-4 3-4 7" stroke="#0b4fa8" stroke-width="5" fill="none" stroke-linecap="round"/><circle cx="32" cy="48" r="3" fill="#0284c7"/></svg>',
        ];

        return $icons[$path] ?? '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="12" y="12" width="40" height="40" rx="9" fill="#dbeafe"/><path d="M23 32h18M32 23v18" stroke="#0b4fa8" stroke-width="6" stroke-linecap="round"/></svg>';
    }
}
?>
<?php if (($homeSettings['showAnnouncements'] ?? true) && $homeAchievements): ?>
<section class="announcement-band">
    <div class="container">
        <div class="announcement-head">
            <div>
                <span class="eyebrow">Prestasi Terbaru</span>
                <h2>Prestasi Sekolah</h2>
            </div>
            <?php if (count($homeAchievements) > 1): ?>
                <div class="carousel-actions" aria-label="Navigasi prestasi">
                    <button class="carousel-button" type="button" data-carousel-prev="home-achievement-carousel" aria-label="Geser prestasi ke kiri">&#8249;</button>
                    <button class="carousel-button" type="button" data-carousel-next="home-achievement-carousel" aria-label="Geser prestasi ke kanan">&#8250;</button>
                </div>
            <?php endif; ?>
        </div>
        <div class="announcement-carousel" id="home-achievement-carousel">
            <?php foreach ($homeAchievements as $achievement): ?>
                <article class="announcement-card">
                    <div>
                        <span class="eyebrow"><?= e($achievement['category']); ?> - <?= e($achievement['level']); ?></span>
                        <h3><?= e($achievement['title']); ?></h3>
                        <p class="announcement-excerpt"><?= e($achievement['achiever_name']); ?> meraih prestasi pada <?= e(date('d F Y', strtotime($achievement['achievement_date']))); ?>. <?= e($achievement['description']); ?></p>
                        <a class="text-link announcement-more" href="<?= base_url('prestasi/' . (int) $achievement['id']); ?>">Selengkapnya</a>
                    </div>
                    <a class="announcement-thumb" href="<?= base_url('prestasi/' . (int) $achievement['id']); ?>" aria-label="Lihat foto prestasi <?= e($achievement['title']); ?>">
                        <?php if (is_placeholder_image($achievement['image_path'] ?? null)): ?>
                            <span><?= e(initials_from_text($achievement['title'])); ?></span>
                        <?php else: ?>
                            <img src="<?= image_asset_path($achievement['image_path']); ?>" alt="<?= e($achievement['title']); ?>" loading="lazy">
                        <?php endif; ?>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<section class="hero home-hero">
    <div class="hero-glow"></div>
    <div class="container hero-grid <?= $showHeroSide ? '' : 'single'; ?>">
        <div class="hero-copy">
            <span class="eyebrow inverse"><?= e($school['tagline']); ?></span>
            <h1><?= e($heroTitle); ?></h1>
            <p class="hero-text"><?= e($heroSubtitle); ?></p>
            <div class="cta-row">
                <a class="button primary" href="<?= e($primaryButtonHref); ?>"><?= e($primaryButtonLabel); ?></a>
                <a class="button ghost" href="<?= e($secondaryButtonHref); ?>"><?= e($secondaryButtonLabel); ?></a>
            </div>
        </div>
        <?php if ($showHeroSide): ?>
        <div class="hero-side hero-showcase home-mobile-rail hero-side-rail">
            <?php if ($showLogoCard): ?>
            <div class="logo-card home-rail-item">
                <img src="<?= asset(ltrim($logoPath, '/')); ?>" alt="Logo sekolah">
                <span>Website Resmi</span>
            </div>
            <?php endif; ?>
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
            <?php if ($showPrincipalCard): ?>
            <div class="principal-card home-rail-item">
                <img src="<?= asset(ltrim($principalPhotoPath, '/')); ?>" alt="Kepala sekolah">
                <div>
                    <span class="eyebrow">Sambutan Singkat</span>
                    <h3>Mewujudkan generasi yang unggul dalam prestasi, karakter, dan pemanfaatan teknologi.</h3>
                    <p><?= e($content['principalMessage']['name']); ?> - <?= e($content['principalMessage']['title']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if (($homeSettings['showQuickLinks'] ?? true) && !empty($content['quickLinks'])): ?>
        <div class="quick-grid quick-icon-grid hero-quick-grid home-mobile-rail" aria-label="Menu informasi cepat">
            <?php foreach ($content['quickLinks'] as $item): ?>
                <a class="quick-card quick-icon-card home-rail-item" href="<?= base_url(ltrim($item['href'], '/')); ?>" aria-label="<?= e($item['title'] . '. ' . $item['desc']); ?>">
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
            <?php if ($profileVideoUrl !== ''): ?>
                <iframe src="<?= e($profileVideoUrl); ?>" title="Video profil sekolah" loading="lazy" allowfullscreen></iframe>
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

<?php if ($homeSettings['showProfileIntro'] ?? true): ?>
<section class="section home-profile-section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Profil Sekolah</span>
            <h2>Ruang digital resmi untuk menampilkan identitas dan arah sekolah</h2>
            <p>Website ini menjadi pintu masuk utama menuju halaman profil, akademik, kesiswaan, berita, galeri, PPDB, dan kontak.</p>
        </div>
        <div class="two-grid home-mobile-rail">
            <article class="panel home-rail-item">
                <h3>Sekolah yang mendukung pembelajaran, karakter, dan kreativitas siswa</h3>
                <p>SMP Negeri 1 Kotabaru berkomitmen menyelenggarakan layanan pendidikan yang mendorong perkembangan akademik, kedisiplinan, kepemimpinan, serta penguatan potensi non-akademik peserta didik.</p>
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
            <a href="<?= base_url('guru-staf'); ?>">Selengkapnya</a>
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
                            <img src="<?= image_asset_path($staff['photo_path']); ?>" alt="<?= e($staff['name']); ?>" loading="lazy">
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
            <a class="button secondary" href="<?= base_url('agenda'); ?>">Lihat Semua Agenda</a>
        </div>
    </div>
</section>
<?php endif; ?>
