<?php
$contactMessage = '';
$contactError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $visitorMessage = trim($_POST['message'] ?? '');

    if (!db_available()) {
        $contactError = 'Form kontak belum aktif karena database belum terhubung.';
    } elseif (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $contactError = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($name === '' || $email === '' || $subject === '' || $visitorMessage === '') {
        $contactError = 'Nama, email, subjek, dan pesan wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactError = 'Format email belum valid.';
    } else {
        save_contact_message([
            'name' => substr($name, 0, 120),
            'email' => substr($email, 0, 180),
            'phone' => substr($phone, 0, 60),
            'subject' => substr($subject, 0, 180),
            'message' => substr($visitorMessage, 0, 3000),
        ]);
        $contactMessage = 'Pesan berhasil dikirim. Terima kasih sudah menghubungi sekolah.';
        $_POST = [];
    }
}
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Kontak</span>
        <h1>Hubungi <?= e($content['schoolProfile']['name']); ?></h1>
        <p>Halaman kontak disediakan untuk memudahkan peserta didik, orang tua, dan masyarakat memperoleh akses ke alamat, nomor resmi, email, serta layanan informasi sekolah.</p>
    </div>
</section>

<section class="section">
    <div class="container card-grid two">
        <?php foreach ($content['contactChannels'] as $item): ?>
            <article class="card">
                <small><?= e($item['title']); ?></small>
                <h3><?= e($item['value']); ?></h3>
                <p><?= e($item['desc']); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section alt">
    <div class="container two-grid narrow">
        <div class="section-heading">
            <span class="eyebrow">Pesan</span>
            <h2>Kirim Pesan ke Sekolah</h2>
            <p>Gunakan form ini untuk pertanyaan umum, informasi layanan, atau pesan yang perlu ditindaklanjuti oleh admin sekolah.</p>
        </div>
        <div class="panel">
            <?php if ($contactMessage): ?><div class="alert success"><?= e($contactMessage); ?></div><?php endif; ?>
            <?php if ($contactError): ?><div class="alert error"><?= e($contactError); ?></div><?php endif; ?>
            <form method="post" class="form-grid">
                <?= csrf_field(); ?>
                <label><span>Nama Lengkap</span><input type="text" name="name" value="<?= e($_POST['name'] ?? ''); ?>" required></label>
                <label><span>Email</span><input type="email" name="email" value="<?= e($_POST['email'] ?? ''); ?>" required></label>
                <label><span>Nomor Telepon</span><input type="text" name="phone" value="<?= e($_POST['phone'] ?? ''); ?>"></label>
                <label><span>Subjek</span><input type="text" name="subject" value="<?= e($_POST['subject'] ?? ''); ?>" required></label>
                <label class="full"><span>Pesan</span><textarea name="message" required><?= e($_POST['message'] ?? ''); ?></textarea></label>
                <div class="full"><button class="button primary blue" type="submit">Kirim Pesan</button></div>
            </form>
        </div>
    </div>
</section>
