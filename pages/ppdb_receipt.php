<?php
$receiptError = '';
$application = null;
$registrationNumber = trim($_GET['registration_number'] ?? ($_POST['registration_number'] ?? ''));
$parentEmail = trim($_POST['parent_email'] ?? '');
$sessionReceipt = $_SESSION['ppdb_receipt'] ?? null;

if ($registrationNumber !== '' && is_array($sessionReceipt) && ($sessionReceipt['registration_number'] ?? '') === $registrationNumber) {
    $parentEmail = $sessionReceipt['parent_email'] ?? $parentEmail;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $parentEmail !== '') {
    if (!db_available()) {
        $receiptError = 'Database belum terhubung.';
    } elseif ($registrationNumber === '' || $parentEmail === '') {
        $receiptError = 'Nomor pendaftaran dan email orang tua/wali wajib diisi.';
    } else {
        $application = ppdb_application_by_registration($registrationNumber, $parentEmail);
        if (!$application) {
            $receiptError = 'Data pendaftaran tidak ditemukan. Periksa kembali nomor pendaftaran dan email orang tua/wali.';
        }
    }
}

$statusLabels = [
    'baru' => 'Baru',
    'diverifikasi' => 'Diverifikasi',
    'diterima' => 'Diterima',
    'ditolak' => 'Ditolak',
];
?>
<section class="page-hero print-hide">
    <div class="container">
        <span class="eyebrow inverse">Bukti PPDB</span>
        <h1>Cetak Bukti Pendaftaran</h1>
        <p>Masukkan nomor pendaftaran dan email orang tua/wali untuk menampilkan bukti pendaftaran PPDB online.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!$application): ?>
            <div class="panel print-hide">
                <?php if ($receiptError): ?><div class="alert error"><?= e($receiptError); ?></div><?php endif; ?>
                <form method="post" class="form-grid two-col">
                    <label><span>Nomor Pendaftaran</span><input type="text" name="registration_number" value="<?= e($registrationNumber); ?>" required></label>
                    <label><span>Email Orang Tua/Wali</span><input type="email" name="parent_email" value="<?= e($parentEmail); ?>" required></label>
                    <div class="full"><button class="button primary blue" type="submit">Tampilkan Bukti</button></div>
                </form>
            </div>
        <?php else: ?>
            <div class="receipt-actions print-hide">
                <button class="button primary blue" type="button" onclick="window.print()">Cetak Bukti</button>
                <a class="button secondary" href="<?= base_url('ppdb'); ?>">Kembali ke PPDB</a>
            </div>
            <article class="receipt-card">
                <div class="receipt-header">
                    <img src="<?= asset(ltrim($content['schoolProfile']['logoPath'] ?? '/public/logo-sekolah.png', '/')); ?>" alt="Logo sekolah">
                    <div>
                        <h2>Bukti Pendaftaran PPDB Online</h2>
                        <p><?= e($content['schoolProfile']['name']); ?></p>
                        <p><?= e($content['schoolProfile']['address']); ?></p>
                    </div>
                </div>
                <div class="receipt-number">
                    <span>Nomor Pendaftaran</span>
                    <strong><?= e($application['registration_number']); ?></strong>
                </div>
                <div class="detail-grid receipt-grid">
                    <p><strong>Nama Calon Siswa</strong><br><?= e($application['student_name']); ?></p>
                    <p><strong>Status</strong><br><?= e($statusLabels[$application['status']] ?? $application['status']); ?></p>
                    <p><strong>Tempat/Tanggal Lahir</strong><br><?= e($application['birth_place']); ?>, <?= e(date('d F Y', strtotime($application['birth_date']))); ?></p>
                    <p><strong>Jenis Kelamin</strong><br><?= e($application['gender']); ?></p>
                    <p><strong>NISN</strong><br><?= e($application['nisn'] ?: '-'); ?></p>
                    <p><strong>Asal Sekolah</strong><br><?= e($application['previous_school']); ?></p>
                    <p><strong>Jalur Pendaftaran</strong><br><?= e(($application['registration_path'] ?? '') ?: '-'); ?></p>
                    <p><strong>Nama Orang Tua/Wali</strong><br><?= e($application['parent_name']); ?></p>
                    <p><strong>No. HP Orang Tua/Wali</strong><br><?= e($application['parent_phone']); ?></p>
                    <p><strong>Email Orang Tua/Wali</strong><br><?= e($application['parent_email']); ?></p>
                    <p><strong>Tanggal Daftar</strong><br><?= e(date('d F Y H:i', strtotime($application['created_at']))); ?></p>
                    <p class="full"><strong>Alamat</strong><br><?= nl2br(e($application['address'])); ?></p>
                    <?php if (!empty($application['admin_notes'])): ?>
                        <p class="full"><strong>Catatan Admin</strong><br><?= nl2br(e($application['admin_notes'])); ?></p>
                    <?php endif; ?>
                </div>
                <div class="receipt-footer">
                    <p>Bukti ini dicetak dari sistem PPDB online. Simpan nomor pendaftaran untuk proses verifikasi bersama panitia.</p>
                    <div>
                        <p><?= e(date('d F Y')); ?></p>
                        <strong>Panitia PPDB</strong>
                    </div>
                </div>
            </article>
        <?php endif; ?>
    </div>
</section>
