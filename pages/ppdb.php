<?php
$ppdbSections = ppdb_sections_data();
<<<<<<< HEAD
$ppdbDocuments = public_documents('PPDB');
$ppdbContactItems = $ppdbSections['kontak'] ?? [];
if (!$ppdbContactItems) {
    $ppdbContactItems = array_filter($content['contactChannels'] ?? [], static function (array $item): bool {
        return in_array($item['title'] ?? '', ['Telepon', 'Email Resmi', 'Jam Layanan'], true);
    });
=======
$ppdbMessage = '';
$ppdbError = '';
$ppdbReceiptUrl = '';
$genderOptions = ['Laki-laki', 'Perempuan'];
$registrationPathOptions = ['Zonasi', 'Prestasi', 'Afirmasi', 'Mutasi'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentName = trim($_POST['student_name'] ?? '');
    $birthPlace = trim($_POST['birth_place'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $nisn = trim($_POST['nisn'] ?? '');
    $previousSchool = trim($_POST['previous_school'] ?? '');
    $registrationPath = trim($_POST['registration_path'] ?? '');
    $parentName = trim($_POST['parent_name'] ?? '');
    $parentPhone = trim($_POST['parent_phone'] ?? '');
    $parentEmail = trim($_POST['parent_email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $privacyConsent = isset($_POST['privacy_consent']);

    if (!db_available()) {
        $ppdbError = 'Form PPDB belum aktif karena database belum terhubung.';
    } elseif (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $ppdbError = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($studentName === '' || $birthPlace === '' || $birthDate === '' || $gender === '' || $previousSchool === '' || $registrationPath === '' || $parentName === '' || $parentPhone === '' || $parentEmail === '' || $address === '') {
        $ppdbError = 'Lengkapi semua data wajib sebelum mengirim pendaftaran.';
    } elseif (!in_array($gender, $genderOptions, true)) {
        $ppdbError = 'Pilihan jenis kelamin tidak valid.';
    } elseif (!in_array($registrationPath, $registrationPathOptions, true)) {
        $ppdbError = 'Pilihan jalur pendaftaran tidak valid.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) {
        $ppdbError = 'Format tanggal lahir belum valid.';
    } elseif (!filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
        $ppdbError = 'Format email orang tua/wali belum valid.';
    } elseif (!$privacyConsent) {
        $ppdbError = 'Orang tua/wali wajib menyetujui kebijakan privasi dan penggunaan data PPDB.';
    } else {
        $uploads = [
            'family_card_path' => uploaded_document_result('family_card_file', 'ppdb'),
            'birth_certificate_path' => uploaded_document_result('birth_certificate_file', 'ppdb'),
            'report_card_path' => uploaded_document_result('report_card_file', 'ppdb'),
            'photo_path' => uploaded_document_result('photo_file', 'ppdb'),
        ];

        foreach ($uploads as $upload) {
            if ($upload['error']) {
                $ppdbError = $upload['error'];
                break;
            }
            if (!$upload['path']) {
                $ppdbError = 'Semua berkas wajib diunggah: KK, akta kelahiran, rapor, dan pas foto.';
                break;
            }
        }

        if (!$ppdbError) {
            $registrationNumber = save_ppdb_application([
                'student_name' => substr($studentName, 0, 160),
                'birth_place' => substr($birthPlace, 0, 120),
                'birth_date' => $birthDate,
                'gender' => $gender,
                'nisn' => substr($nisn, 0, 40),
                'previous_school' => substr($previousSchool, 0, 160),
                'registration_path' => $registrationPath,
                'parent_name' => substr($parentName, 0, 160),
                'parent_phone' => substr($parentPhone, 0, 60),
                'parent_email' => substr($parentEmail, 0, 180),
                'address' => substr($address, 0, 2000),
                'family_card_path' => $uploads['family_card_path']['path'],
                'birth_certificate_path' => $uploads['birth_certificate_path']['path'],
                'report_card_path' => $uploads['report_card_path']['path'],
                'photo_path' => $uploads['photo_path']['path'],
                'consent_accepted_at' => date('Y-m-d H:i:s'),
                'privacy_version' => '2026-04-29',
                'academic_year' => current_academic_year(),
            ]);

            $_SESSION['ppdb_receipt'] = [
                'registration_number' => $registrationNumber,
                'parent_email' => substr($parentEmail, 0, 180),
            ];
            $ppdbReceiptUrl = base_url('ppdb/bukti?registration_number=' . rawurlencode($registrationNumber));
            $ppdbMessage = 'Pendaftaran berhasil dikirim. Nomor pendaftaran: ' . $registrationNumber . '. Simpan nomor ini untuk konfirmasi ke panitia.';
            $_POST = [];
        }
    }
>>>>>>> 98147e3 (upload pertama)
}
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">PPDB</span>
<<<<<<< HEAD
        <h1>Informasi PPDB <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Pusat informasi penerimaan peserta didik baru tahun ajaran <?= e(current_academic_year()); ?>: jalur, alur, persyaratan, edaran resmi, dan kontak panitia.</p>
=======
        <h1>Penerimaan Peserta Didik Baru <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Informasi umum pendaftaran tahun ajaran <?= e(current_academic_year()); ?>, persyaratan, alur, jadwal, serta layanan panitia yang dapat diperbarui sesuai ketentuan sekolah.</p>
>>>>>>> 98147e3 (upload pertama)
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
<<<<<<< HEAD
            <span class="eyebrow">Sistem Informasi</span>
            <h2>PPDB sekolah sebagai pusat rujukan informasi</h2>
            <p>Pendaftaran online mengikuti kanal resmi Dinas Pendidikan atau sistem yang ditetapkan panitia. Website sekolah ini difokuskan untuk membantu orang tua membaca informasi, menyiapkan dokumen, dan menghubungi panitia.</p>
        </div>
        <div class="ppdb-info-panel">
            <article class="panel dark">
                <span class="eyebrow inverse">Penting</span>
                <h3>Pendaftaran tidak dilakukan melalui website ini</h3>
                <p>Gunakan tautan, edaran, atau pengumuman resmi yang diterbitkan Dinas Pendidikan dan panitia PPDB. Informasi pada halaman ini dapat diperbarui sesuai kebijakan terbaru.</p>
            </article>
            <div class="card-grid two">
                <?php foreach ($ppdbSections['informasi'] ?? [] as $item): ?>
                    <article class="card">
                        <h3><?= e($item['title']); ?></h3>
                        <p><?= e($item['desc']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Jalur PPDB</span>
            <h2>Kriteria dan jalur yang perlu dipahami</h2>
            <p>Ketentuan jalur mengikuti aturan resmi yang berlaku pada tahun ajaran berjalan.</p>
=======
            <span class="eyebrow">Kriteria Pendaftaran</span>
            <h2>Jalur PPDB yang tersedia</h2>
            <p>Pilih jalur pendaftaran sesuai kondisi calon peserta didik dan siapkan dokumen pendukung yang diminta panitia.</p>
>>>>>>> 98147e3 (upload pertama)
        </div>
        <div class="ppdb-criteria-grid">
            <?php foreach (($ppdbSections['kriteria'] ?? []) as $item): ?>
                <article class="card ppdb-criteria-card">
                    <span><?= e(substr($item['title'], 0, 1)); ?></span>
                    <h3><?= e($item['title']); ?></h3>
                    <p><?= e($item['desc']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<<<<<<< HEAD
<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Alur PPDB</span>
            <h2>Tahapan informasi dan layanan panitia</h2>
=======
<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Daftar Online</span>
            <h2>Formulir pendaftaran calon peserta didik baru</h2>
            <p>Isi data calon peserta didik dan unggah berkas persyaratan. Berkas diterima dalam format PDF, JPG, PNG, atau WEBP dengan ukuran maksimal 3 MB per file.</p>
        </div>
        <div class="panel">
            <?php if ($ppdbMessage): ?><div class="alert success"><?= e($ppdbMessage); ?></div><?php endif; ?>
            <?php if ($ppdbError): ?><div class="alert error"><?= e($ppdbError); ?></div><?php endif; ?>
            <?php if ($ppdbReceiptUrl): ?>
                <div class="action-bar">
                    <a class="button primary blue" href="<?= e($ppdbReceiptUrl); ?>">Lihat & Cetak Bukti Pendaftaran</a>
                </div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="form-grid two-col">
                <?= csrf_field(); ?>
                <label><span>Nama Calon Siswa</span><input type="text" name="student_name" value="<?= e($_POST['student_name'] ?? ''); ?>" required></label>
                <label><span>NISN</span><input type="text" name="nisn" value="<?= e($_POST['nisn'] ?? ''); ?>" placeholder="Opsional bila belum ada"></label>
                <label><span>Tempat Lahir</span><input type="text" name="birth_place" value="<?= e($_POST['birth_place'] ?? ''); ?>" required></label>
                <label><span>Tanggal Lahir</span><input type="date" name="birth_date" value="<?= e($_POST['birth_date'] ?? ''); ?>" required></label>
                <label>
                    <span>Jenis Kelamin</span>
                    <select name="gender" required>
                        <option value="">Pilih jenis kelamin</option>
                        <?php foreach ($genderOptions as $option): ?>
                            <option value="<?= e($option); ?>" <?= ($_POST['gender'] ?? '') === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Asal Sekolah</span><input type="text" name="previous_school" value="<?= e($_POST['previous_school'] ?? ''); ?>" required></label>
                <label>
                    <span>Jalur Pendaftaran</span>
                    <select name="registration_path" required>
                        <option value="">Pilih jalur pendaftaran</option>
                        <?php foreach ($registrationPathOptions as $option): ?>
                            <option value="<?= e($option); ?>" <?= ($_POST['registration_path'] ?? '') === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Nama Orang Tua/Wali</span><input type="text" name="parent_name" value="<?= e($_POST['parent_name'] ?? ''); ?>" required></label>
                <label><span>No. HP Orang Tua/Wali</span><input type="text" name="parent_phone" value="<?= e($_POST['parent_phone'] ?? ''); ?>" required></label>
                <label class="full"><span>Email Orang Tua/Wali</span><input type="email" name="parent_email" value="<?= e($_POST['parent_email'] ?? ''); ?>" required></label>
                <label class="full"><span>Alamat Lengkap</span><textarea name="address" required><?= e($_POST['address'] ?? ''); ?></textarea></label>
                <label><span>Kartu Keluarga</span><input type="file" name="family_card_file" accept=".pdf,.jpg,.jpeg,.png,.webp" required></label>
                <label><span>Akta Kelahiran</span><input type="file" name="birth_certificate_file" accept=".pdf,.jpg,.jpeg,.png,.webp" required></label>
                <label><span>Rapor Terakhir</span><input type="file" name="report_card_file" accept=".pdf,.jpg,.jpeg,.png,.webp" required></label>
                <label><span>Pas Foto</span><input type="file" name="photo_file" accept=".pdf,.jpg,.jpeg,.png,.webp" required></label>
                <label class="checkbox-label full">
                    <input type="checkbox" name="privacy_consent" value="1" <?= isset($_POST['privacy_consent']) ? 'checked' : ''; ?> required>
                    <span>Saya menyatakan data yang diisi benar dan menyetujui penggunaan data untuk proses administrasi PPDB sesuai <a class="text-link" href="<?= base_url('kebijakan-privasi'); ?>" target="_blank" rel="noopener">Kebijakan Privasi</a>.</span>
                </label>
                <div class="full"><button class="button primary blue" type="submit">Kirim Pendaftaran</button></div>
            </form>
        </div>
    </div>
</section>

<section class="section">
    <div class="container card-grid three">
        <?php foreach ($ppdbSections['informasi'] ?? [] as $item): ?>
            <article class="card">
                <h3><?= e($item['title']); ?></h3>
                <p><?= e($item['desc']); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Alur PPDB</span>
            <h2>Tahapan umum penerimaan peserta didik baru</h2>
>>>>>>> 98147e3 (upload pertama)
        </div>
        <div class="card-grid two">
            <?php foreach ($content['ppdbSteps'] as $index => $step): ?>
                <article class="timeline-item">
                    <span><?= $index + 1; ?></span>
                    <div><p><?= e($step); ?></p></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<<<<<<< HEAD
<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Edaran PPDB</span>
            <h2>Dokumen dan pengumuman resmi</h2>
            <p>Unduh surat edaran, jadwal, formulir informasi, atau dokumen PPDB lain yang diterbitkan sekolah.</p>
        </div>
        <?php if ($ppdbDocuments): ?>
            <div class="document-grid">
                <?php foreach ($ppdbDocuments as $document): ?>
                    <article class="document-card">
                        <span class="eyebrow"><?= e($document['category']); ?></span>
                        <h3><?= e($document['title']); ?></h3>
                        <p><?= e($document['description']); ?></p>
                        <p class="muted"><?= e($document['original_filename']); ?> - <?= number_format(((int) $document['file_size']) / 1024, 1); ?> KB</p>
                        <a class="button primary blue" href="<?= base_url('dokumen/unduh?id=' . (int) $document['id']); ?>">Unduh PDF</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <article class="panel">
                <p class="muted">Edaran PPDB belum tersedia. Panitia dapat mengunggah dokumen dari menu Dokumen dengan kategori PPDB.</p>
            </article>
        <?php endif; ?>
    </div>
</section>

=======
>>>>>>> 98147e3 (upload pertama)
<section class="section">
    <div class="container two-grid">
        <article class="panel">
            <div class="section-heading">
                <span class="eyebrow">Persyaratan</span>
                <h2>Dokumen yang perlu disiapkan</h2>
            </div>
            <ul class="feature-list plain">
                <?php foreach (($ppdbSections['persyaratan'] ?? []) as $item): ?>
                    <li><?= e($item['desc']); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="panel">
            <div class="section-heading">
                <span class="eyebrow">Catatan Penting</span>
<<<<<<< HEAD
                <h2>Hal yang perlu diperhatikan</h2>
=======
                <h2>Informasi tambahan untuk calon peserta didik</h2>
>>>>>>> 98147e3 (upload pertama)
            </div>
            <ul class="feature-list plain">
                <?php foreach (($ppdbSections['catatan'] ?? []) as $item): ?>
                    <li><?= e($item['desc']); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    </div>
</section>
<<<<<<< HEAD

<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Kontak Panitia</span>
            <h2>Layanan informasi PPDB</h2>
            <p>Hubungi panitia pada jam layanan untuk memastikan jadwal, persyaratan, dan kanal pendaftaran resmi.</p>
        </div>
        <div class="card-grid three">
            <?php foreach ($ppdbContactItems as $item): ?>
                <article class="card">
                    <h3><?= e($item['title']); ?></h3>
                    <?php if (!empty($item['value'])): ?>
                        <p><strong><?= e($item['value']); ?></strong></p>
                    <?php endif; ?>
                    <?php if (!empty($item['desc'])): ?>
                        <p><?= e($item['desc']); ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
=======
>>>>>>> 98147e3 (upload pertama)
