    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-target]').forEach(function (button) {
        var target = document.getElementById(button.getAttribute('data-toggle-target'));
        if (!target) {
            return;
        }

        button.addEventListener('click', function () {
            var isOpen = target.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            var icon = button.querySelector('[aria-hidden="true"]');
            if (icon) {
                icon.textContent = isOpen ? '×' : '+';
            }
            if (isOpen) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>
</body>
</html>
