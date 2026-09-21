/**
 * Turns every `?` marker into a Bootstrap tooltip.
 *
 * Progressive enhancement: the text already sits in the `title` attribute, so
 * with this script blocked the browser shows its own tooltip instead. Bootstrap
 * moves `title` aside when it takes over, so the two never appear at once.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap === 'undefined') {
        return;
    }

    document.querySelectorAll('.help-tip[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { placement: 'top', trigger: 'hover focus' });
    });
});
