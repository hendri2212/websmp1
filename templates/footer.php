<?php $content = $content ?? site_content(); ?>
<?php $logoPath = $content['schoolProfile']['logoPath'] ?? '/public/logo-sekolah.png'; ?>
    </main>
    <footer class="site-footer">
        <div class="accent-line"></div>
        <div class="container footer-grid">
            <div>
                <div class="footer-brand">
                    <img src="<?= e(asset(ltrim($logoPath, '/'))); ?>" alt="Logo sekolah">
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
                            <li><a href="<?= e(base_url(ltrim($item['href'], '/'))); ?>"><?= e($item['label']); ?></a></li>
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
    function startWhenVisible(element, start) {
        if (!('IntersectionObserver' in window)) { start(); return; }
        var observer = new IntersectionObserver(function (entries) {
            if (entries.some(function (entry) { return entry.intersectionRatio >= 0.35; })) {
                observer.disconnect();
                start();
            }
        }, { threshold: 0.35 });
        observer.observe(element);
    }
    document.querySelectorAll('.home-video-panel video').forEach(function (video) {
        var errorMessage = video.parentElement.querySelector('[data-profile-video-error]');
        if (!errorMessage) { return; }
        function updateError() { errorMessage.hidden = !video.error; }
        video.addEventListener('error', updateError);
        video.addEventListener('loadedmetadata', updateError);
        updateError();
        video.muted = true;
        startWhenVisible(video, function () {
            var playback = video.play();
            if (playback) { playback.catch(function () { /* Native controls remain available if autoplay is blocked. */ }); }
        });
    });
    var youtubeFrame = document.querySelector('[data-youtube-autoplay]');
    if (youtubeFrame) {
        var youtubeError = youtubeFrame.parentElement.querySelector('[data-youtube-error]');
        startWhenVisible(youtubeFrame, function () {
            var embedUrl = new URL(youtubeFrame.src);
            embedUrl.searchParams.set('origin', window.location.origin);
            youtubeFrame.src = embedUrl.toString();
            function initializeYoutube() {
                new YT.Player(youtubeFrame.id, {
                    events: {
                        onReady: function (event) {
                            event.target.mute();
                            event.target.playVideo();
                        },
                        onError: function () {
                            if (youtubeError) { youtubeError.hidden = false; }
                        },
                        onStateChange: function (event) {
                            if (youtubeError && event.data === YT.PlayerState.PLAYING) { youtubeError.hidden = true; }
                        }
                    }
                });
            }
            if (window.YT && window.YT.Player) { initializeYoutube(); return; }
            var previousReady = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = function () {
                if (typeof previousReady === 'function') { previousReady(); }
                initializeYoutube();
            };
            var apiScript = document.createElement('script');
            apiScript.src = 'https://www.youtube.com/iframe_api';
            apiScript.async = true;
            document.head.appendChild(apiScript);
        });
    }
    document.querySelectorAll('[data-achievement-slider]').forEach(function (slider) {
        var slides = Array.from(slider.querySelectorAll('.hero-achievement-slide'));
        var dots = Array.from(slider.querySelectorAll('[data-achievement-dot]'));
        var pause = slider.querySelector('[data-achievement-pause]');
        var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
        var index = 0;
        var timer = null;
        var paused = false;
        var hovered = false;
        if (slides.length < 2 || !pause) { return; }

        function schedule() {
            window.clearTimeout(timer);
            if (paused || hovered || motion.matches || document.hidden || slider.contains(document.activeElement)) { return; }
            timer = window.setTimeout(function () {
                if (paused || hovered || motion.matches || document.hidden || slider.contains(document.activeElement)) { schedule(); return; }
                show((index + 1) % slides.length);
            }, 5000);
        }
        function show(next) {
            index = next;
            slides.forEach(function (slide, position) { slide.hidden = position !== index; });
            dots.forEach(function (dot, position) { dot.setAttribute('aria-pressed', position === index ? 'true' : 'false'); });
            schedule();
        }
        dots.forEach(function (dot, position) {
            dot.addEventListener('click', function () { show(position); });
        });
        pause.addEventListener('click', function () {
            paused = !paused;
            pause.innerHTML = paused ? '&#9654;' : '&#9208;';
            pause.setAttribute('aria-label', paused ? 'Lanjutkan pergantian prestasi' : 'Jeda pergantian prestasi');
            pause.title = pause.getAttribute('aria-label');
            schedule();
        });
        slider.addEventListener('mouseenter', function () { hovered = true; schedule(); });
        slider.addEventListener('mouseleave', function () { hovered = false; schedule(); });
        slider.addEventListener('focusin', schedule);
        slider.addEventListener('focusout', function () { window.setTimeout(schedule, 0); });
        document.addEventListener('visibilitychange', schedule);
        motion.addEventListener('change', schedule);
        schedule();
    });
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
