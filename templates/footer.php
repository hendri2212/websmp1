<?php $content = $content ?? site_content(); ?>
<?php $logoPath = $content['schoolProfile']['logoPath'] ?? '/public/logo-sekolah.png'; ?>
    </main>
    <footer class="site-footer">
        <div class="accent-line"></div>
        <div class="container footer-grid">
            <div>
                <div class="footer-brand">
                    <img src="<?= asset(ltrim($logoPath, '/')); ?>" alt="Logo sekolah">
                    <div>
                        <strong><?= e($content['schoolProfile']['name']); ?></strong>
                        <span>Lembaga pendidikan resmi</span>
                    </div>
                </div>
                <p><?= e($content['schoolProfile']['description']); ?></p>
            </div>
            <div>
                <h3>Navigasi</h3>
                <ul class="footer-list">
                    <?php foreach ($content['navigation'] as $item): ?>
                        <?php if (($item['visible'] ?? true) === true): ?>
                            <li><a href="<?= base_url(ltrim($item['href'], '/')); ?>"><?= e($item['label']); ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h3>Kontak</h3>
                <ul class="footer-list">
                    <li><?= e($content['schoolProfile']['address']); ?></li>
                    <li><?= e($content['schoolProfile']['phone']); ?></li>
                    <li><?= e($content['schoolProfile']['email']); ?></li>
                    <li><?= e($content['schoolProfile']['serviceHours']); ?></li>
                </ul>
            </div>
        </div>
    </footer>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var button = document.querySelector('.menu-toggle');
    var nav = document.getElementById('site-nav');
    if (!button || !nav) {
        return;
    }

    button.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('is-open');
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    nav.addEventListener('click', function (event) {
        if (event.target.tagName === 'A') {
            nav.classList.remove('is-open');
            button.setAttribute('aria-expanded', 'false');
        }
    });

    document.querySelectorAll('[data-carousel-prev], [data-carousel-next], [data-carousel-up], [data-carousel-down]').forEach(function (control) {
        control.addEventListener('click', function () {
            var targetId = control.getAttribute('data-carousel-prev') || control.getAttribute('data-carousel-next') || control.getAttribute('data-carousel-up') || control.getAttribute('data-carousel-down');
            var carousel = document.getElementById(targetId);
            if (!carousel) {
                return;
            }

            var firstSlide = carousel.querySelector('.announcement-card, .teacher-slide, .staff-slide, .content-slide, .home-rail-item, .quick-card, .card, .panel, li');
            var gap = parseFloat(window.getComputedStyle(carousel).columnGap || window.getComputedStyle(carousel).gap || 14) || 14;
            var isVertical = control.hasAttribute('data-carousel-up') || control.hasAttribute('data-carousel-down');
            var distance = firstSlide
                ? (isVertical ? firstSlide.getBoundingClientRect().height : firstSlide.getBoundingClientRect().width) + gap
                : (isVertical ? carousel.clientHeight : carousel.clientWidth);
            var direction = (control.hasAttribute('data-carousel-prev') || control.hasAttribute('data-carousel-up')) ? -1 : 1;
            if (isVertical) {
                carousel.scrollBy({ top: distance * direction, behavior: 'smooth' });
                return;
            }

            carousel.scrollBy({ left: distance * direction, behavior: 'smooth' });
        });
    });
});
</script>
</body>
</html>
