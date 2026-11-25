// A classic-style editor: Visual and Text tabs and a toolbar, like WordPress. The server cleans the HTML again on save.
(function () {
    function clean(html) {
        // Removes anything active (scripts, frames, event handlers, javascript: links) before HTML is shown in the Visual tab.
        var doc = new DOMParser().parseFromString('<body>' + html + '</body>', 'text/html');

        doc.querySelectorAll('script,style,iframe,object,embed,link,meta,form,svg,math,template').forEach(function (n) { n.remove(); });
        doc.body.querySelectorAll('*').forEach(function (el) {
            Array.prototype.slice.call(el.attributes).forEach(function (a) {
                var name = a.name.toLowerCase();
                var value = a.value.replace(/[\s\u0000-\u001f]+/g, '').toLowerCase();

                if (name.indexOf('on') === 0 || (name === 'href' && !/^(https?:|mailto:|tel:|\/|#)/.test(value)) || name === 'style' || name === 'src') {
                    el.removeAttribute(a.name);
                }
            });
        });

        return doc.body.innerHTML;
    }

    document.querySelectorAll('[data-classic-editor]').forEach(function (box) {
        var visual = box.querySelector('.ce-visual');
        var text = box.querySelector('.ce-text');
        var tabs = box.querySelectorAll('.ce-tabs button');
        var toolbar = box.querySelector('.ce-toolbar');
        var form = box.closest('form');
        var mode = 'visual';

        visual.innerHTML = clean(text.value);
        text.style.display = 'none';

        function setMode(next) {
            if (next === mode) {
                return;
            }

            if (next === 'text') {
                text.value = visual.innerHTML;
                visual.style.display = 'none';
                toolbar.style.display = 'none';
                text.style.display = 'block';
            } else {
                visual.innerHTML = clean(text.value);
                text.style.display = 'none';
                visual.style.display = 'block';
                toolbar.style.display = 'flex';
            }

            mode = next;
            tabs.forEach(function (t) { t.classList.toggle('is-active', t.getAttribute('data-tab') === next); });
        }

        tabs.forEach(function (t) {
            t.addEventListener('click', function () { setMode(t.getAttribute('data-tab')); });
        });

        toolbar.addEventListener('mousedown', function (event) {
            // keep the text selection while a toolbar button is pressed
            if (event.target.closest('button')) {
                event.preventDefault();
            }
        });

        toolbar.addEventListener('click', function (event) {
            var button = event.target.closest('button');

            if (!button) {
                return;
            }

            visual.focus();

            if (button.hasAttribute('data-link')) {
                var url = window.prompt('Link address (https://...)', 'https://');

                if (url) {
                    document.execCommand('createLink', false, url);
                }
            } else if (button.getAttribute('data-cmd')) {
                document.execCommand(button.getAttribute('data-cmd'), false, null);
            }
        });

        var block = toolbar.querySelector('[data-block]');

        if (block) {
            block.addEventListener('change', function () {
                visual.focus();
                document.execCommand('formatBlock', false, block.value);
            });
        }

        // Pasting keeps only the plain text, so no hidden formatting from Word or web pages comes along.
        visual.addEventListener('paste', function (event) {
            event.preventDefault();
            var plain = (event.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, plain);
        });

        form.addEventListener('submit', function () {
            if (mode === 'visual') {
                text.value = visual.innerHTML;
            }
        });
    });
})();
