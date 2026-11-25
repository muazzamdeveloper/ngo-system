// Small formatting buttons for the page text box. They only type simple markup (Markdown) into it.
(function () {
    document.querySelectorAll('.editor-bar').forEach(function (bar) {
        var box = document.getElementById(bar.getAttribute('data-editor-for'));

        if (!box) {
            return;
        }

        bar.addEventListener('click', function (event) {
            var button = event.target.closest('button');

            if (!button) {
                return;
            }

            var start = box.selectionStart;
            var end = box.selectionEnd;
            var text = box.value;
            var selected = text.substring(start, end);
            var wrap = button.getAttribute('data-md');
            var line = button.getAttribute('data-md-line');
            var insert;

            if (wrap) {
                var parts = wrap.split('|');
                insert = parts[0] + (selected || 'text') + parts[1];
            } else if (line) {
                insert = (start > 0 && text.charAt(start - 1) !== '\n' ? '\n' : '') + line + (selected || 'text');
            } else {
                return;
            }

            box.value = text.substring(0, start) + insert + text.substring(end);
            box.focus();
            box.selectionStart = box.selectionEnd = start + insert.length;
        });
    });
})();
