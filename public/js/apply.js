// Public application form: stop oversized files before upload, and block double submits.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('apply-form');

    if (!form) {
        return;
    }

    // Show the chosen file's name inside its upload box.
    form.querySelectorAll('.dz-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var box = input.closest('.dropzone');
            var title = box ? box.querySelector('.dz-title') : null;

            if (!box || !title) {
                return;
            }

            if (input.files.length) {
                title.textContent = input.files[0].name;
                box.classList.add('has-file');
            } else {
                title.textContent = title.getAttribute('data-default') || 'Upload';
                box.classList.remove('has-file');
            }
        });
    });

    var button = form.querySelector('button[type="submit"]');
    var submitHtml = button ? button.innerHTML : '';

    form.addEventListener('submit', function (event) {
        var inputs = form.querySelectorAll('input[type="file"]');

        for (var i = 0; i < inputs.length; i++) {
            var file = inputs[i].files[0];
            var maxBytes = parseFloat(inputs[i].getAttribute('data-max-mb') || '0') * 1024 * 1024;

            if (file && maxBytes && file.size > maxBytes) {
                event.preventDefault();
                alert('The file "' + file.name + '" is too large. The limit is ' + inputs[i].getAttribute('data-max-mb') + ' MB.');
                inputs[i].focus();
                return;
            }
        }

        if (button) {
            button.disabled = true;
            button.textContent = 'Submitting...';
        }
    });

    // Coming back with the browser Back button should not leave the button stuck.
    window.addEventListener('pageshow', function () {
        if (button) {
            button.disabled = false;
            button.innerHTML = submitHtml;
        }
    });
});
