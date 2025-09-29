document.addEventListener('DOMContentLoaded', function () {
    function eme_tasklastname_clearable() {
        const taskLastnameInput = EME.$('input[name=task_lastname]');
        if (!taskLastnameInput) return;

        if (taskLastnameInput.value === '') {
            taskLastnameInput.readOnly = false;
            taskLastnameInput.classList.remove('clearable');
            // Clear and make all dependent fields editable
            const dependentFields = [
                'task_firstname', 'task_address1', 'task_address2', 'task_city',
                'task_state', 'task_zip', 'task_country', 'task_email', 'task_phone'
            ];
            dependentFields.forEach(fieldName => {
                const field = EME.$(`input[name=${fieldName}]`);
                if (field) {
                    field.value = '';
                    field.readOnly = false;
                }
            });
        }
        if (taskLastnameInput.value !== '') {
            taskLastnameInput.classList.add('clearable', 'x');
        }
    }

    // --- Autocomplete Core Function ---
    function initAutocomplete(inputSelector, autocompleteAction, fieldMap) {
        const input = EME.$(inputSelector);
        if (!input) return;

        let timeout;

        // Remove suggestions on outside click
        document.addEventListener('click', () => {
            EME.$$('.eme-autocomplete-suggestions').forEach(el => el.remove());
        });

        input.addEventListener('input', function () {
            clearTimeout(timeout);
            EME.$$('.eme-autocomplete-suggestions').forEach(el => el.remove());

            const value = this.value.trim();
            if (value.length < 2) return;

            const form = this.closest('form');
            if (!form) return;

            // Build request data from entire form (like jQuery's serializeArray)
            const formData = new FormData(form);
            const data = {};
            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }
            data.eme_ajax_action = autocompleteAction;

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

                        // Different HTML for RSVP vs Task forms
                        if (autocompleteAction === 'rsvp_autocomplete_people') {
                            suggestion.innerHTML = `
                                <strong>${eme_htmlDecode(item.lastname)} ${eme_htmlDecode(item.firstname)}</strong>
                                <br><small>${eme_htmlDecode(item.email)} - ${eme_htmlDecode(item.phone)}</small>
                            `;
                        } else {
                            suggestion.innerHTML = `
                                <strong>${eme_htmlDecode(item.lastname)} ${eme_htmlDecode(item.firstname)}</strong>
                                <br><small>${eme_htmlDecode(item.email)}</small>
                            `;
                        }

                        suggestion.addEventListener('click', e => {
                            e.preventDefault();
                            e.stopPropagation();

                            // Set values and make readonly/required based on form type
                            Object.keys(fieldMap).forEach(formKey => {
                                const target = EME.$(`input[name="${formKey}"]`);
                                if (target) {
                                    const value = item[fieldMap[formKey]];
                                    if (value !== undefined) {
                                        target.value = eme_htmlDecode(value);
                                        target.readOnly = true;
                                        
                                        // Handle required property for RSVP form fields
                                        if (autocompleteAction === 'rsvp_autocomplete_people' && 
                                            formKey !== 'lastname' && formKey !== 'firstname' && 
                                            formKey !== 'wp_id' && formKey !== 'person_id') {
                                            target.required = false;
                                        }
                                        if (formKey == 'birthdate') {
                                            const target2 = EME.$('input[name="dp_birthdate"]');
                                            if (target2._fdatepicker) {
                                                const startObj = new Date(value);
                                                target2._fdatepicker.setDate(startObj);
                                            }
                                        }
                                    }
                                }
                            });

                            // Remove suggestions
                            EME.$$('.eme-autocomplete-suggestions').forEach(el => el.remove());

                            // Trigger change event for clearable functionality
                            input.dispatchEvent(new Event('change'));
                        });
                        suggestions.appendChild(suggestion);
                    });

                    input.insertAdjacentElement('afterend', suggestions);
                })
                .catch(err => console.warn('Autocomplete fetch error:', err));
            }, 500);
        });
    }

    // --- Initialize Autocomplete for RSVP Form ---
    if (EME.$("input[name='lastname']")) {
        initAutocomplete("input[name='lastname']", 'rsvp_autocomplete_people', {
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
            person_id: 'person_id',
            birthdate: 'birthdate'
        });
    }

    // --- Initialize Autocomplete for Task Form ---
    if (EME.$("input[name='task_lastname']")) {
        initAutocomplete("input[name='task_lastname']", 'task_autocomplete_people', {
            task_lastname: 'lastname',
            task_firstname: 'firstname',
            task_email: 'email',
            task_phone: 'phone'
        });

        // Setup clearable behavior for task_lastname
        const taskLastnameInput = EME.$('input[name=task_lastname]');
        if (taskLastnameInput) {
            taskLastnameInput.addEventListener('change', eme_tasklastname_clearable);
            eme_tasklastname_clearable(); // Initial call
        }
    }
});
