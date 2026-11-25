// Public site behaviour: hero slider and tabs.
(function () {
    // Hero slider: slides fade into each other, with arrows, dots and (unless reduced motion is asked for) autoplay.
    document.querySelectorAll('[data-hero]').forEach(function (hero) {
        var slides = hero.querySelectorAll('.hero-slide');
        var dots = hero.querySelectorAll('.hero-dots button');
        var current = 0;
        var timer = null;
        var interval = parseInt(hero.getAttribute('data-interval'), 10) || 6000;
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (slides.length < 2) {
            return;
        }

        function show(index) {
            current = (index + slides.length) % slides.length;

            slides.forEach(function (slide, i) {
                slide.classList.toggle('is-active', i === current);
            });
            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === current);
            });
        }

        function start() {
            stop();

            if (!reduce) {
                timer = setInterval(function () { show(current + 1); }, interval);
            }
        }

        function stop() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        var prev = hero.querySelector('.hero-prev');
        var next = hero.querySelector('.hero-next');

        if (prev) { prev.addEventListener('click', function () { show(current - 1); start(); }); }
        if (next) { next.addEventListener('click', function () { show(current + 1); start(); }); }

        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { show(i); start(); });
        });

        hero.addEventListener('mouseenter', stop);
        hero.addEventListener('mouseleave', start);
        start();
    });

    // Tabs
    document.querySelectorAll('[data-tabs]').forEach(function (box) {
        var buttons = box.querySelectorAll('.tab-list button');
        var panels = box.querySelectorAll('.tab-panel');

        buttons.forEach(function (button, index) {
            button.addEventListener('click', function () {
                buttons.forEach(function (b, i) {
                    b.classList.toggle('is-active', i === index);
                    b.setAttribute('aria-selected', i === index ? 'true' : 'false');
                });
                panels.forEach(function (p, i) {
                    p.classList.toggle('is-active', i === index);
                });
            });
        });
    });
})();
