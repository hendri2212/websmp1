<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Kontak</span>
        <h1>Hubungi <?= e($content['schoolProfile']['name']); ?></h1>
        <?php if (!empty($content['pageIntroductions']['kontak'])): ?>
        <p><?= e($content['pageIntroductions']['kontak']); ?></p>
        <?php endif; ?>
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
                <label><span>Nama Lengkap</span><input type="text" name="name" value="<?= e(input_form_value($_POST, 'name')); ?>" required></label>
                <label><span>Email</span><input type="email" name="email" value="<?= e(input_form_value($_POST, 'email')); ?>" required></label>
                <label><span>Nomor Telepon</span><input type="text" name="phone" value="<?= e(input_form_value($_POST, 'phone')); ?>"></label>
                <label><span>Subjek</span><input type="text" name="subject" value="<?= e(input_form_value($_POST, 'subject')); ?>" required></label>
                <label class="full"><span>Pesan</span><textarea name="message" required><?= e(input_form_value($_POST, 'message')); ?></textarea></label>
                <div class="full"><button class="button primary blue" type="submit">Kirim Pesan</button></div>
            </form>
        </div>
    </div>
</section>
