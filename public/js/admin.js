// Admin panel behaviour: mobile sidebar, collapsible menu groups, user menu, copy buttons.
(function () {
    var toggle = document.getElementById('menu-toggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    var userMenu = document.getElementById('user-menu');

    function openMenu() {
        if (!sidebar || !overlay) { return; }
        sidebar.classList.add('is-open');
        overlay.classList.add('is-visible');
    }

    function closeMenu() {
        if (!sidebar || !overlay) { return; }
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-visible');
    }

    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function () {
            if (sidebar.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });
        overlay.addEventListener('click', closeMenu);
    }

    // Collapsible sidebar groups (the group that holds the current page starts open).
    document.querySelectorAll('.nav-group-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var group = button.parentElement;
            var open = group.classList.toggle('is-open');
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    // User menu in the top bar.
    if (userMenu) {
        var userButton = userMenu.querySelector('.user-menu-toggle');

        userButton.addEventListener('click', function (event) {
            event.stopPropagation();
            var open = userMenu.classList.toggle('is-open');
            userButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!userMenu.contains(event.target)) {
                userMenu.classList.remove('is-open');
                userButton.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
            if (userMenu) {
                userMenu.classList.remove('is-open');
            }
        }
    });

    // Buttons with data-copy="text" copy that text and show "Copied!" for a moment.
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var text = button.getAttribute('data-copy');
            var label = button.querySelector('span');

            function done() {
                if (label) {
                    var old = label.textContent;
                    label.textContent = 'Copied!';
                    setTimeout(function () { label.textContent = old; }, 1500);
                }
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                var box = document.createElement('textarea');
                box.value = text;
                document.body.appendChild(box);
                box.select();
                document.execCommand('copy');
                document.body.removeChild(box);
                done();
            }
        });
    });
})();
