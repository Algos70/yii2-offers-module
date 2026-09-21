/**
 * Keeps the offer filter's Apply button in step with the form.
 *
 * The filters submit as a plain GET form, so picking a type pill or a casino
 * changes nothing until Apply is pressed — which is invisible if the button
 * looks identical before and after. This disables it while the form still
 * matches what is already applied, and enables it the moment a selection
 * differs, so the button itself says "there is something to apply".
 *
 * Progressive enhancement: the button ships enabled, so with JavaScript off the
 * form behaves exactly as before.
 */
(function () {
    'use strict';

    function currentState(form) {
        return new URLSearchParams(new FormData(form)).toString();
    }

    function wire(form) {
        var button = form.querySelector('[data-offer-filter-submit]');

        if (button === null) {
            return;
        }

        // What the page was rendered with; anything else is a pending change.
        var applied = currentState(form);

        function sync() {
            button.disabled = currentState(form) === applied;
        }

        form.addEventListener('change', sync);
        sync();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-offer-filter]').forEach(wire);
    });
})();
