<div class="admin-card">
    <h1>Profil & Pengaturan Sekolah</h1>
    <?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL lebih dulu agar tombol simpan aktif.</div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="form-grid two-col settings-form">
        <?= csrf_field(); ?>
        <input type="hidden" name="existing_logo_path" value="<?= e($content['schoolProfile']['logoPath'] ?? '/public/logo-sekolah.png'); ?>">
        <input type="hidden" name="existing_principal_photo_path" value="<?= e($content['principalMessage']['photoPath'] ?? '/public/kepala-sekolah.jpeg'); ?>">
        <input type="hidden" name="existing_accreditation_file_path" value="<?= e($content['schoolAccreditation']['filePath'] ?? ''); ?>">
        <input type="hidden" name="existing_accreditation_original_filename" value="<?= e($content['schoolAccreditation']['originalFilename'] ?? ''); ?>">
        <input type="hidden" name="existing_accreditation_file_size" value="<?= e((string) ($content['schoolAccreditation']['fileSize'] ?? 0)); ?>">
        <details class="settings-section full" open>
            <summary>
                <span>
                    <strong>Identitas Sekolah</strong>
                    <small>Nama, tagline, logo, dan tahun ajaran aktif.</small>
                </span>
            </summary>
            <div class="form-grid two-col">
                <label><span>Nama Sekolah</span><input type="text" name="name" value="<?= e($content['schoolProfile']['name']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Nama Singkat</span><input type="text" name="short_name" value="<?= e($content['schoolProfile']['shortName']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Tagline</span><input type="text" name="tagline" value="<?= e($content['schoolProfile']['tagline']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Deskripsi Singkat</span><textarea name="description" <?= db_available() ? '' : 'disabled'; ?>><?= e($content['schoolProfile']['description']); ?></textarea></label>
                <label class="full"><span>Deskripsi Hero</span><textarea name="hero_description" <?= db_available() ? '' : 'disabled'; ?>><?= e($content['schoolProfile']['heroDescription']); ?></textarea></label>
                <label>
                    <span>Logo Sekolah</span>
                    <input type="file" name="school_logo_file" accept=".jpg,.jpeg,.png,.webp" <?= db_available() ? '' : 'disabled'; ?>>
                    <span class="muted"><?= e(image_upload_help_text()); ?></span>
                    <img class="admin-preview" src="<?= e(asset(ltrim($content['schoolProfile']['logoPath'] ?? '/public/logo-sekolah.png', '/'))); ?>" alt="Preview logo sekolah">
                </label>
            </div>
            <div class="settings-subsection">
                <span class="form-section-title">Tahun Ajaran Aktif</span>
            <label>
                <span>Tahun Ajaran</span>
                <input type="text" name="current_academic_year" value="<?= e($content['siteAcademic']['currentYear'] ?? '2025/2026'); ?>" placeholder="2025/2026" <?= db_available() ? '' : 'disabled'; ?>>
            </label>
            <p class="muted">Tahun ajaran aktif ditampilkan pada informasi PPDB dan digunakan sebagai default agenda, dokumen, dan prestasi baru.</p>
            </div>
        </details>
        <details class="settings-section full">
            <summary>
                <span>
                    <strong>Tampilan Beranda</strong>
                    <small>Hero, video profil, link cepat, dan bagian yang tampil.</small>
                </span>
            </summary>
            <div class="form-grid two-col">
                <label class="full"><span>Judul Hero Beranda</span><input type="text" name="home_hero_title" value="<?= e($content['siteHome']['heroTitle'] ?? $content['schoolProfile']['name']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Deskripsi Hero Beranda</span><textarea name="home_hero_subtitle" <?= db_available() ? '' : 'disabled'; ?>><?= e($content['siteHome']['heroSubtitle'] ?? $content['schoolProfile']['heroDescription']); ?></textarea></label>
                <label><span>Label Tombol Utama</span><input type="text" name="home_primary_button_label" value="<?= e($content['siteHome']['primaryButtonLabel'] ?? 'Lihat Profil Sekolah'); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Link Tombol Utama</span><input type="text" name="home_primary_button_url" value="<?= e($content['siteHome']['primaryButtonUrl'] ?? '/profil'); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Label Tombol Kedua</span><input type="text" name="home_secondary_button_label" value="<?= e($content['siteHome']['secondaryButtonLabel'] ?? 'Informasi PPDB'); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Link Tombol Kedua</span><input type="text" name="home_secondary_button_url" value="<?= e($content['siteHome']['secondaryButtonUrl'] ?? '/ppdb'); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <?php $videoSource = $content['siteHome']['profileVideoSource'] ?? (youtube_video_id($content['siteHome']['profileVideoUrl'] ?? '') !== null ? 'youtube' : 'upload'); ?>
                <fieldset class="full video-source-choice" data-video-source-choice>
                    <legend>Sumber Video</legend>
                    <label><input type="radio" name="home_profile_video_source" value="upload" <?= $videoSource === 'upload' ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> Upload Video</label>
                    <label><input type="radio" name="home_profile_video_source" value="youtube" <?= $videoSource === 'youtube' ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> Link YouTube</label>
                </fieldset>
                <p class="full muted">Untuk video berukuran besar, gunakan YouTube agar tidak membebani kapasitas hosting.</p>
                <div class="full" data-video-source-field="upload">
                <label><span>File Video Profil (MP4/MOV, maksimal 20 MB)</span><input type="file" name="home_profile_video_file" accept=".mp4,.mov,video/mp4,video/quicktime" <?= db_available() ? '' : 'disabled'; ?>></label>
                <?php if (video_file_url($content['siteHome']['profileVideoUrl'] ?? '') !== ''): ?>
                    <a class="text-link full" href="<?= e(video_file_url($content['siteHome']['profileVideoUrl'])); ?>" target="_blank" rel="noopener">Lihat video tersimpan</a>
                <?php endif; ?>
                </div>
                <div class="full" data-video-source-field="youtube">
                <label><span>Link YouTube</span><input type="url" name="home_profile_video_url" value="<?= e(youtube_video_id($content['siteHome']['profileVideoUrl'] ?? '') !== null ? $content['siteHome']['profileVideoUrl'] : ''); ?>" placeholder="https://www.youtube.com/watch?v=..." <?= db_available() ? '' : 'disabled'; ?>></label>
                </div>
                <label class="full"><span>Judul Video Profil</span><input type="text" name="home_profile_video_title" value="<?= e($content['siteHome']['profileVideoTitle'] ?? 'Profil sekolah dalam satu tayangan'); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Keterangan Video Profil</span><textarea name="home_profile_video_description" <?= db_available() ? '' : 'disabled'; ?>><?= e($content['siteHome']['profileVideoDescription'] ?? 'Ruang ini disiapkan untuk menampilkan video profil resmi sekolah, mulai dari lingkungan belajar, kegiatan siswa, fasilitas, hingga budaya sekolah.'); ?></textarea></label>
                <label><span>Jumlah Foto Guru di Beranda</span><input type="number" min="4" max="24" name="home_teacher_limit" value="<?= e((string) ($content['siteHome']['teacherLimit'] ?? 12)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Jumlah Agenda di Beranda</span><input type="number" min="1" max="9" name="home_agenda_limit" value="<?= e((string) ($content['siteHome']['agendaLimit'] ?? 3)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
            </div>
            <label class="full">
                <span>Link Cepat Beranda</span>
                <textarea name="home_quick_links" <?= db_available() ? '' : 'disabled'; ?>><?= e(settings_link_rows_text($content['quickLinks'])); ?></textarea>
                <span class="muted">Format: Judul | Deskripsi | Link. Satu item per baris.</span>
            </label>
            <label class="full">
                <span>Program Unggulan Beranda</span>
                <textarea name="home_featured_areas" <?= db_available() ? '' : 'disabled'; ?>><?= e(settings_link_rows_text($content['featuredAreas'])); ?></textarea>
                <span class="muted">Format: Judul | Deskripsi | Link. Satu item per baris.</span>
            </label>
            <label class="full">
                <span>Daftar Keunggulan Beranda</span>
                <textarea name="home_excellence_programs" <?= db_available() ? '' : 'disabled'; ?>><?= e(implode("\n", $content['excellencePrograms'])); ?></textarea>
                <span class="muted">Satu poin keunggulan per baris.</span>
            </label>
            <div class="toggle-grid">
                <label class="checkbox-label toggle-item">
                    <input type="checkbox" name="home_show_announcements" value="1" <?= ($content['siteHome']['showAnnouncements'] ?? true) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                    <span>Pengumuman</span>
                </label>
                <label class="checkbox-label toggle-item">
                    <input type="checkbox" name="home_show_quick_links" value="1" <?= ($content['siteHome']['showQuickLinks'] ?? true) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                    <span>Link Cepat</span>
                </label>
                <label class="checkbox-label toggle-item">
                    <input type="checkbox" name="home_show_logo_card" value="1" <?= ($content['siteHome']['showLogoCard'] ?? true) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                    <span>Kartu Logo</span>
                </label>
                <label class="checkbox-label toggle-item">
                    <input type="checkbox" name="home_show_stats" value="1" <?= ($content['siteHome']['showStats'] ?? true) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                    <span>Statistik</span>
                </label>
                <label class="checkbox-label toggle-item">
                    <input type="checkbox" name="home_show_principal_card" value="1" <?= ($content['siteHome']['showPrincipalCard'] ?? true) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                    <span>Kepala Sekolah</span>
                </label>
                <label class="checkbox-label toggle-item">
                    <input type="checkbox" name="home_show_teacher_carousel" value="1" <?= ($content['siteHome']['showTeacherCarousel'] ?? true) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                    <span>Carousel Guru</span>
                </label>
                <label class="checkbox-label toggle-item">
                    <input type="checkbox" name="home_show_profile_intro" value="1" <?= ($content['siteHome']['showProfileIntro'] ?? true) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                    <span>Intro Profil</span>
                </label>
                <label class="checkbox-label toggle-item">
                    <input type="checkbox" name="home_show_featured_areas" value="1" <?= ($content['siteHome']['showFeaturedAreas'] ?? true) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                    <span>Program Unggulan</span>
                </label>
                <label class="checkbox-label toggle-item">
                    <input type="checkbox" name="home_show_agenda" value="1" <?= ($content['siteHome']['showAgenda'] ?? true) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                    <span>Agenda Beranda</span>
                </label>
            </div>
            <p class="muted">Bagian yang tidak dicentang akan disembunyikan dari beranda tanpa menghapus datanya.</p>
        </details>
        <details class="settings-section full">
            <summary>
                <span>
                    <strong>Mode Maintenance Publik</strong>
                    <small>Matikan sementara halaman publik saat perawatan website.</small>
                </span>
            </summary>
            <label class="checkbox-label toggle-item">
                <input type="checkbox" name="maintenance_enabled" value="1" <?= !empty($content['siteMaintenance']['enabled']) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                <span>Aktifkan mode maintenance untuk halaman publik</span>
            </label>
            <label class="full">
                <span>Pesan Maintenance</span>
                <textarea name="maintenance_message" <?= db_available() ? '' : 'disabled'; ?>><?= e($content['siteMaintenance']['message'] ?? 'Website sekolah sedang dalam pemeliharaan singkat. Silakan kembali beberapa saat lagi.'); ?></textarea>
            </label>
            <p class="muted">Saat aktif, pengunjung publik melihat halaman pemeliharaan. Admin tetap bisa login dan mengelola website.</p>
        </details>
        <details class="settings-section full">
            <summary>
                <span>
                    <strong>Tema Warna Website</strong>
                    <small>Ubah warna utama, aksen, dan sidebar admin.</small>
                </span>
            </summary>
            <div class="theme-grid">
                <label>
                    <span>Warna Utama</span>
                    <input type="color" name="theme_primary_color" value="<?= e(normalize_hex_color($content['siteTheme']['primaryColor'] ?? null, '#0284c7')); ?>" <?= db_available() ? '' : 'disabled'; ?>>
                </label>
                <label>
                    <span>Warna Utama Gelap</span>
                    <input type="color" name="theme_primary_dark_color" value="<?= e(normalize_hex_color($content['siteTheme']['primaryDarkColor'] ?? null, '#0b4fa8')); ?>" <?= db_available() ? '' : 'disabled'; ?>>
                </label>
                <label>
                    <span>Warna Kedua</span>
                    <input type="color" name="theme_secondary_color" value="<?= e(normalize_hex_color($content['siteTheme']['secondaryColor'] ?? null, '#06b6d4')); ?>" <?= db_available() ? '' : 'disabled'; ?>>
                </label>
                <label>
                    <span>Warna Aksen</span>
                    <input type="color" name="theme_accent_color" value="<?= e(normalize_hex_color($content['siteTheme']['accentColor'] ?? null, '#eab308')); ?>" <?= db_available() ? '' : 'disabled'; ?>>
                </label>
                <label>
                    <span>Warna Gelap/Sidebar</span>
                    <input type="color" name="theme_dark_color" value="<?= e(normalize_hex_color($content['siteTheme']['darkColor'] ?? null, '#0f172a')); ?>" <?= db_available() ? '' : 'disabled'; ?>>
                </label>
            </div>
            <p class="muted">Warna ini langsung memengaruhi hero, tombol utama, aksen footer, link aktif, dan sidebar admin.</p>
        </details>
        <details class="settings-section full">
            <summary>
                <span>
                    <strong>Kontak & Layanan</strong>
                    <small>Alamat, telepon, email, dan jam layanan sekolah.</small>
                </span>
            </summary>
            <div class="form-grid two-col">
                <label class="full"><span>Alamat</span><textarea name="address" <?= db_available() ? '' : 'disabled'; ?>><?= e($content['schoolProfile']['address']); ?></textarea></label>
                <label><span>Telepon</span><input type="text" name="phone" value="<?= e($content['schoolProfile']['phone']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Email</span><input type="text" name="email" value="<?= e($content['schoolProfile']['email']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Jam Layanan</span><input type="text" name="service_hours" value="<?= e($content['schoolProfile']['serviceHours']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
            </div>
        </details>
        <details class="settings-section full">
            <summary>
                <span>
                    <strong>Kepala Sekolah</strong>
                    <small>Nama, foto, dan sambutan kepala sekolah.</small>
                </span>
            </summary>
            <div class="form-grid two-col">
                <label><span>Kepala Sekolah</span><input type="text" name="principal_name" value="<?= e($content['principalMessage']['name']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Jabatan</span><input type="text" name="principal_title" value="<?= e($content['principalMessage']['title']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Salam Sambutan</span><input type="text" name="principal_greeting" value="<?= e($content['principalMessage']['greeting']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label>
                    <span>Foto Kepala Sekolah</span>
                    <input type="file" name="principal_photo_file" accept=".jpg,.jpeg,.png,.webp" <?= db_available() ? '' : 'disabled'; ?>>
                    <span class="muted"><?= e(image_upload_help_text()); ?></span>
                    <img class="admin-preview" src="<?= e(asset(ltrim($content['principalMessage']['photoPath'] ?? '/public/kepala-sekolah.jpeg', '/'))); ?>" alt="Preview foto kepala sekolah">
                </label>
                <label class="full"><span>Paragraf Sambutan (satu paragraf per baris)</span><textarea name="principal_paragraphs" <?= db_available() ? '' : 'disabled'; ?>><?= e(implode("\n", $content['principalMessage']['paragraphs'])); ?></textarea></label>
            </div>
        </details>
        <details class="settings-section full">
            <summary>
                <span>
                    <strong>Profil Sekolah</strong>
                    <small>Sejarah, visi, misi, dan fasilitas ringkas.</small>
                </span>
            </summary>
            <div class="form-grid two-col">
                <label class="full"><span>Sejarah Singkat</span><textarea name="history_text" <?= db_available() ? '' : 'disabled'; ?>><?= e($content['profileSections'][0]['text'] ?? ''); ?></textarea></label>
                <label class="full"><span>Visi Sekolah</span><textarea name="vision_text" <?= db_available() ? '' : 'disabled'; ?>><?= e($content['profileSections'][1]['text'] ?? ''); ?></textarea></label>
                <label class="full"><span>Misi Sekolah</span><textarea name="mission_text" <?= db_available() ? '' : 'disabled'; ?>><?= e($content['profileSections'][2]['text'] ?? ''); ?></textarea></label>
                <label class="full">
                    <span>Fasilitas Sekolah Ringkas (satu fasilitas per baris)</span>
                    <textarea name="school_facilities" <?= db_available() ? '' : 'disabled'; ?>><?= e(implode("\n", $content['schoolFacilities'])); ?></textarea>
                    <span class="muted">Daftar ini tampil sebagai ringkasan di halaman profil. Foto fasilitas dikelola dari menu Fasilitas.</span>
                </label>
            </div>
        </details>
        <details class="settings-section full">
            <summary>
                <span>
                    <strong>Akreditasi Sekolah</strong>
                    <small>Status akreditasi dan file PDF resmi.</small>
                </span>
            </summary>
            <div class="form-grid two-col">
                <label><span>Status Akreditasi</span><input type="text" name="accreditation_status" value="<?= e($content['schoolAccreditation']['status'] ?? 'A'); ?>" placeholder="A" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Nomor SK</span><input type="text" name="accreditation_sk_number" value="<?= e($content['schoolAccreditation']['skNumber'] ?? '555/BAN-SM/SK/2023'); ?>" placeholder="555/BAN-SM/SK/2023" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Tanggal SK</span><input type="text" name="accreditation_sk_date" value="<?= e($content['schoolAccreditation']['skDate'] ?? '25 Mei 2023'); ?>" placeholder="25 Mei 2023" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label>
                    <span>Lembar Akreditasi PDF</span>
                    <input type="file" name="accreditation_file" accept=".pdf,application/pdf" <?= db_available() ? '' : 'disabled'; ?>>
                    <span class="muted">Format PDF, maksimal 5 MB. File ini dapat dibuka pengunjung dari halaman profil.</span>
                </label>
                <label class="full"><span>Keterangan Akreditasi</span><textarea name="accreditation_description" <?= db_available() ? '' : 'disabled'; ?>><?= e($content['schoolAccreditation']['description'] ?? ''); ?></textarea></label>
            </div>
            <?php if (!empty($content['schoolAccreditation']['filePath'])): ?>
                <p class="muted">File aktif: <?= e($content['schoolAccreditation']['originalFilename'] ?: basename($content['schoolAccreditation']['filePath'])); ?>. <a class="text-link" href="<?= e(base_url('akreditasi/lihat')); ?>" target="_blank" rel="noopener">Lihat file</a></p>
            <?php else: ?>
                <p class="muted">Belum ada lembar akreditasi yang diunggah.</p>
            <?php endif; ?>
        </details>
        <details class="settings-section full">
            <summary>
                <span>
                    <strong>Statistik Beranda</strong>
                    <small>Jumlah siswa, guru, ekstrakurikuler, dan rombel.</small>
                </span>
            </summary>
            <div class="form-grid two-col">
                <label><span>Jumlah Siswa</span><input type="text" name="stat_students" value="<?= e($content['stats'][0]['value']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Jumlah Guru & Staf</span><input type="text" name="stat_teachers" value="<?= e($content['stats'][1]['value']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Jumlah Ekstrakurikuler</span><input type="text" name="stat_extracurriculars" value="<?= e($content['stats'][2]['value']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Jumlah Rombel</span><input type="text" name="stat_rombel" value="<?= e($content['stats'][3]['value']); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
            </div>
        </details>
        <details class="settings-section full">
            <summary>
                <span>
                    <strong>Menu Navigasi Publik</strong>
                    <small>Atur menu yang tampil di navbar publik.</small>
                </span>
            </summary>
            <div class="toggle-grid">
                <?php foreach ($content['navigation'] as $item): ?>
                    <label class="checkbox-label toggle-item">
                        <input type="checkbox" name="nav_visible[<?= e($item['href']); ?>]" value="1" <?= ($item['visible'] ?? true) ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>>
                        <span><?= e($item['label']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="muted">Menu yang tidak dicentang disembunyikan dari navbar publik, tetapi halamannya tetap bisa diakses langsung melalui URL.</p>
        </details>
        <div class="settings-save-bar full"><button class="button primary" type="submit" <?= db_available() ? '' : 'disabled'; ?>>Simpan Pengaturan</button></div>
    </form>
</div>
