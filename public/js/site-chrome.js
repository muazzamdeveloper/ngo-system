// Header behaviour: mobile menu toggle and dropdowns that open on tap.
(function () {
    var toggle = document.querySelector('.chrome-menu-toggle');
    var list = document.getElementById('chrome-menu-list');

    if (toggle && list) {
        toggle.addEventListener('click', function () {
            var open = list.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    document.querySelectorAll('.has-dropdown > .dropdown-toggle').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            var item = button.parentElement;
            var open = !item.classList.contains('is-open');
            document.querySelectorAll('.has-dropdown.is-open').forEach(function (other) {
                if (other !== item) { other.classList.remove('is-open'); other.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false'); }
            });
            item.classList.toggle('is-open', open);
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.has-dropdown.is-open').forEach(function (item) { item.classList.remove('is-open'); });
    });
})();

// Home hero: rotate the slides every 5 seconds, pause on hover, and respect reduced-motion settings.
(function () {
    var hero = document.querySelector('.hm-hero');
    if (!hero) return;
    var slides = hero.querySelectorAll('.hm-hero-slide');
    if (slides.length < 2) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var current = 0;
    var timer = null;

    function show(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach(function (slide, i) { slide.classList.toggle('is-hidden', i !== current); });
    }
    function start() { stop(); timer = setInterval(function () { show(current + 1); }, 5000); }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }

    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', start);
    start();
})();

// Language selector: loads the Google Translate widget into the header (only when the placeholder exists).
(function () {
    if (!document.getElementById('google_translate_element')) return;
    var holder = document.getElementById('google_translate_element');
    window.googleTranslateElementInit = function () {
        if (!window.google || !window.google.translate) return;
        holder.innerHTML = '';
        new window.google.translate.TranslateElement({
            pageLanguage: 'en',
            autoDisplay: false,
            layout: window.google.translate.TranslateElement.InlineLayout.SIMPLE
        }, 'google_translate_element');
    };
    var script = document.createElement('script');
    script.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    script.async = true;
    script.onerror = function () {
        var box = document.querySelector('.chrome-lang');
        if (box) box.style.display = 'none';
    };
    document.head.appendChild(script);
})();
