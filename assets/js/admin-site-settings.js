/**
 * Site Setting page — repeatable rows for phone numbers and social links.
 *
 * Markup contract:
 *   [data-sbek-add="<group>"]   button that appends a row
 *   [data-sbek-rows="<group>"]  container holding the rows
 *   #sbek-tmpl-<group>          <template> with inputs using data-name="…[__i__]…"
 *   [data-sbek-remove]          button inside a row that removes its [data-sbek-row]
 */
document.addEventListener('DOMContentLoaded', function () {
    // Monotonic index per group keeps field names unique across add/remove.
    const counters = {};

    function nextIndex(group) {
        if (typeof counters[group] === 'undefined') {
            const container = document.querySelector('[data-sbek-rows="' + group + '"]');
            counters[group] = container ? container.querySelectorAll('[data-sbek-row]').length : 0;
        }
        return counters[group]++;
    }

    function addRow(group) {
        const tmpl = document.getElementById('sbek-tmpl-' + group);
        const container = document.querySelector('[data-sbek-rows="' + group + '"]');
        if (!tmpl || !container) return;

        const index = nextIndex(group);
        const row = tmpl.content.firstElementChild.cloneNode(true);

        // Promote data-name → name with the row index baked in.
        row.querySelectorAll('[data-name]').forEach(function (input) {
            input.setAttribute('name', input.getAttribute('data-name').replace('__i__', index));
            input.removeAttribute('data-name');
        });

        container.appendChild(row);
        const firstInput = row.querySelector('input');
        if (firstInput) firstInput.focus();
    }

    // Add buttons.
    document.querySelectorAll('[data-sbek-add]').forEach(function (button) {
        button.addEventListener('click', function () {
            addRow(button.getAttribute('data-sbek-add'));
        });
    });

    // Remove buttons (delegated, so cloned rows work too).
    document.addEventListener('click', function (event) {
        const remove = event.target.closest('[data-sbek-remove]');
        if (!remove) return;
        const row = remove.closest('[data-sbek-row]');
        if (row) row.remove();
    });

    // ── Media library pickers (logo / OG image) ──────────────────────────────
    document.querySelectorAll('[data-sbek-media-field]').forEach(function (field) {
        const input    = field.querySelector('[data-sbek-media-input]');
        const preview  = field.querySelector('[data-sbek-media-preview]');
        const triggers = field.querySelectorAll('[data-sbek-media-select]'); // dropzone + replace
        const remove   = field.querySelector('[data-sbek-media-remove]');
        let frame = null; // one media frame per field, reused

        function openPicker() {
            if (typeof wp === 'undefined' || !wp.media) return;

            if (!frame) {
                frame = wp.media({ title: 'Select image', button: { text: 'Use this image' }, multiple: false });
                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    const sizes = attachment.sizes || {};
                    const thumb = sizes.medium || sizes.large || sizes.full || attachment;

                    input.value = attachment.id;
                    preview.src = thumb.url || attachment.url;
                    field.setAttribute('data-state', 'filled');
                });
            }

            // Preselect the current attachment when reopening.
            frame.on('open', function () {
                if (!input.value) return;
                const selection = frame.state().get('selection');
                const existing = wp.media.attachment(input.value);
                existing.fetch();
                selection.reset([existing]);
            });

            frame.open();
        }

        triggers.forEach(function (trigger) {
            trigger.addEventListener('click', openPicker);
        });

        if (remove) {
            remove.addEventListener('click', function () {
                input.value = '';
                preview.removeAttribute('src');
                field.setAttribute('data-state', 'empty');
            });
        }
    });
});
