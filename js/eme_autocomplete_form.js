document.addEventListener('DOMContentLoaded', function () {
    // --- Utility shortcuts ---
    const $ = (selector, context = document) => context.querySelector(selector);
    const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));

    // --- Utility: Collect form data as key-value pairs ---
    function serializeForm(form) {
        const formData = new FormData(form);
        const obj = {};
        for (const [key, value] of formData.entries()) {
            obj[key] = value;
        }
        return obj;
    }

    // --- Clearable UI Handler ---
    function setupClearable(inputName, dependentFields = []) {
        const input = $(`input[name="${inputName}"]`);
        if (!input) return;

        function updateClearable() {
            if (input.value === '') {
                input.readOnly = false;
                input.classList.remove('clearable', 'x');
                dependentFields.forEach(fieldName => {
                    const field = $(`input[name="${fieldName}"]`);
                    if (field) {
                        field.value = '';
                        field.readOnly = false;
                    }
                });
            } else {
                input.classList.add('clearable', 'x');
            }
        }

        input.addEventListener('input', updateClearable);
        input.addEventListener('change', updateClearable);
        updateClearable(); // Initial call
    }

    // --- Autocomplete Core Function ---
    function initAutocomplete(inputSelector, fieldMap, requestDataKeys = []) {
        const input = $(inputSelector);
        if (!input) return;

        let timeout;

        // Remove suggestions on outside click
        document.addEventListener('click', () => {
            $$('.eme-autocomplete-suggestions').forEach(el => el.remove());
        });

        input.addEventListener('input', function () {
            clearTimeout(timeout);
            $$('.eme-autocomplete-suggestions').forEach(el => el.remove());

            const value = this.value.trim();
            if (value.length < 2) return;

            const form = this.closest('form');
            if (!form) return;

            // Build request data
            const data = {};
            requestDataKeys.forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) data[key] = field.value;
            });
            data.eme_ajax_action = 'rsvp_autocomplete_people';

            timeout = setTimeout(() => {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(data)
                })
                .then(r => r.json())
                .then(results => {
                    if (!results || !Array.isArray(results) || results.length === 0) return;

                    const suggestions = document.createElement('div');
                    suggestions.className = 'eme-autocomplete-suggestions';

                    results.forEach(item => {
                        const suggestion = document.createElement('div');
                        suggestion.className = 'eme-autocomplete-suggestion';
                        suggestion.innerHTML = `
                            <strong>${eme_htmlDecode(item.lastname)} ${eme_htmlDecode(item.firstname)}</strong>
                            <br><small>${eme_htmlDecode(item.email)} - ${eme_htmlDecode(item.phone)}</small>
                        `;
                        suggestion.addEventListener('click', e => {
                            e.preventDefault();
                            e.stopPropagation();

                            // Set values and make readonly
                            Object.keys(fieldMap).forEach(formKey => {
                                const target = $(`input[name="${formKey}"]`);
                                if (target) {
                                    const value = item[fieldMap[formKey]];
                                    if (value !== undefined) {
                                        target.value = eme_htmlDecode(value);
                                        target.readOnly = true;
                                        if (formKey !== 'wp_id' && formKey !== 'person_id') {
                                            target.required = false;
                                        }
                                    }
                                }
                            });

                            // Trigger change to update UI (e.g., clearable)
                            input.dispatchEvent(new Event('change'));
                        });
                        suggestions.appendChild(suggestion);
                    });

                    input.insertAdjacentElement('afterend', suggestions);
                })
                .catch(err => console.warn('Autocomplete fetch error:', err));
            }, 500);
        });

        // Reapply clearable logic
        input.addEventListener('change', () => {
            const event = new Event('input', { bubbles: true });
            input.dispatchEvent(event);
        });
    }

    // --- Initialize Autocomplete for RSVP Form ---
    if ($("input[name='lastname']")) {
        initAutocomplete("input[name='lastname']", {
            lastname: 'lastname',
            firstname: 'firstname',
            address1: 'address1',
            address2: 'address2',
            city: 'city',
            state: 'state',
            zip: 'zip',
            country: 'country',
            email: 'email',
            phone: 'phone',
            wp_id: 'wp_id',
            person_id: 'person_id'
        }, [
            'lastname',
            'event_id',
            'eme_form_id',
            'eme_frontendform_id'
        ]);
    }

    // --- Initialize Autocomplete for Task Form ---
    if ($("input[name='task_lastname']")) {
        initAutocomplete("input[name='task_lastname']", {
            task_lastname: 'lastname',
            task_firstname: 'firstname',
            task_email: 'email',
            task_phone: 'phone'
        }, [
            'task_lastname',
            'eme_form_id',
            'eme_frontendform_id'
        ]);

        // Setup clearable behavior for task_lastname
        setupClearable('task_lastname', [
            'task_firstname',
            'task_address1',
            'task_address2',
            'task_city',
            'task_state',
            'task_zip',
            'task_country',
            'task_email',
            'task_phone'
        ]);
    }
});
