    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-video-source-choice]').forEach(function (choice) {
        var form = choice.closest('form');
        if (!form) { return; }
        var groups = Array.from(form.querySelectorAll('[data-video-source-field]'));
        var disabledInitially = new Map();
        groups.forEach(function (group) {
            group.querySelectorAll('input').forEach(function (input) {
                disabledInitially.set(input, input.disabled);
            });
        });
        function updateVideoFields() {
            var selected = choice.querySelector('input:checked');
            groups.forEach(function (group) {
                var active = selected && group.getAttribute('data-video-source-field') === selected.value;
                group.hidden = !active;
                group.querySelectorAll('input').forEach(function (input) {
                    input.disabled = !active || disabledInitially.get(input);
                });
            });
        }
        choice.addEventListener('change', updateVideoFields);
        updateVideoFields();
    });
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
