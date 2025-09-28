function calculateRsvpStart() {
    // Get the target (start or end)
    const targetSelect = document.getElementById('eme_prop_rsvp_start_target');
    const target = targetSelect.value; // "start" or "end"
    const displayElement = document.getElementById('rsvp-start-display');

    // Get hidden date and time values
    const dateField = target === 'start'
        ? document.getElementById('start-date-to-submit')
        : document.getElementById('end-date-to-submit');
    const timeField = target === 'start'
        ? document.getElementById('start-time-to-submit')
        : document.getElementById('end-time-to-submit');

    if (!dateField || !timeField) {
        displayElement.textContent = '';
        return;
    }

    const eventDateStr = dateField.value; // e.g. "2025-06-15"
    const eventTimeStr = timeField.value; // e.g. "14:30:00"
    if (!eventDateStr || !eventTimeStr) {
        displayElement.textContent = '';
        return;
    }

    // Combine into ISO string and create Date object
    const eventDateTime = new Date(eventDateStr + 'T' + eventTimeStr);
    if (isNaN(eventDateTime)) {
        displayElement.textContent = '';
        return;
    }

    // Get offset values
    const daysOffset = parseInt(document.getElementById('eme_prop_rsvp_start_number_days').value) || 0;
    const hoursOffset = parseFloat(document.getElementById('eme_prop_rsvp_start_number_hours').value) || 0;
    if (!daysOffset && !hoursOffset) {
        displayElement.textContent = '';
        return;
    }

    // Calculate start by subtracting offset
    const totalOffsetMs = (daysOffset * 24 + hoursOffset) * 60 * 60 * 1000;
    const startDateTime = new Date(eventDateTime.getTime() - totalOffsetMs);
    const formattedStart = startDateTime.toLocaleString();

    // Display it
    displayElement.textContent = formattedStart;
}

function calculateRsvpEnd() {
    // Get the target (start or end)
    const targetSelect = document.getElementById('eme_prop_rsvp_end_target');
    const target = targetSelect.value; // "start" or "end"
    const displayElement = document.getElementById('rsvp-end-display');

    // Get hidden date and time values
    const dateField = target === 'start'
        ? document.getElementById('start-date-to-submit')
        : document.getElementById('end-date-to-submit');
    const timeField = target === 'start'
        ? document.getElementById('start-time-to-submit')
        : document.getElementById('end-time-to-submit');

    if (!dateField || !timeField) {
        displayElement.textContent = '';
        return;
    }

    const eventDateStr = dateField.value; // e.g. "2025-06-15"
    const eventTimeStr = timeField.value; // e.g. "14:30:00"
    if (!eventDateStr || !eventTimeStr) {
        displayElement.textContent = '';
        return;
    }

    // Combine into ISO string and create Date object
    const eventDateTime = new Date(eventDateStr + 'T' + eventTimeStr);
    if (isNaN(eventDateTime)) {
        displayElement.textContent = '';
        return;
    }

    // Get offset values
    const daysOffset = parseInt(document.getElementById('eme_prop_rsvp_end_number_days').value) || 0;
    const hoursOffset = parseFloat(document.getElementById('eme_prop_rsvp_end_number_hours').value) || 0;

    // Calculate start by subtracting offset
    const totalOffsetMs = (daysOffset * 24 + hoursOffset) * 60 * 60 * 1000;
    const endDateTime = new Date(eventDateTime.getTime() - totalOffsetMs);
    const formattedEnd = endDateTime.toLocaleString();

    // Display it
    displayElement.textContent = formattedEnd;
}

function calculateRsvpCutoffDisplay() {
    const displayElement = document.getElementById('rsvp-cancel-end-display');

    // Get hidden date and time values
    const dateField = document.getElementById('start-date-to-submit');
    const timeField = document.getElementById('start-time-to-submit');

    if (!dateField || !timeField) {
        displayElement.textContent = '';
        return;
    }

    const eventDateStr = dateField.value; // e.g. "2025-06-15"
    const eventTimeStr = timeField.value; // e.g. "14:30:00"
    if (!eventDateStr || !eventTimeStr) {
        displayElement.textContent = '';
        return;
    }

    // Combine into ISO string and create Date object
    const eventDateTime = new Date(eventDateStr + 'T' + eventTimeStr);
    if (isNaN(eventDateTime)) {
        displayElement.textContent = '';
        return;
    }

    // Get offset values
    const daysOffset = parseInt(document.getElementById('eme_prop_cancel_rsvp_days').value) || 0;
    if (!daysOffset) {
        displayElement.textContent = '';
        return;
    }

    // Calculate start by subtracting offset
    const totalOffsetMs = daysOffset * 24 * 60 * 60 * 1000;
    const startDateTime = new Date(eventDateTime.getTime() - totalOffsetMs);
    const formattedStart = startDateTime.toLocaleString();

    // Display it
    displayElement.textContent = formattedStart;
}

document.addEventListener('DOMContentLoaded', function () {
    const EventsTableContainer = EME.$('#EventsTableContainer');
    let EventsTable;
    const RecurrencesTableContainer = EME.$('#RecurrencesTableContainer');
    let RecurrencesTable;

    function updateIntervalDescriptor() {
        EME.$$('.interval-desc').forEach(el => eme_toggle(el, false));
        
        // for specific months, we just hide and return
        if (EME.$('#recurrence-frequency')?.value === 'specific_months') {
            const intervalInput = EME.$('#recurrence-interval');
            const specificSpan = EME.$('span#specific_months_span');
            if (intervalInput) eme_toggle(intervalInput, false);
            if (specificSpan) eme_toggle(specificSpan, true);
            return;
        } else {
            const intervalInput = EME.$('#recurrence-interval');
            const specificSpan = EME.$('span#specific_months_span');
            if (intervalInput) eme_toggle(intervalInput, true);
            if (specificSpan) eme_toggle(specificSpan, false);
        }
        
        let number = '-plural';
        const intervalVal = EME.$('#recurrence-interval')?.value;
        if (intervalVal === '1' || intervalVal === '') {
            number = '-singular';
        }
        
        const frequency = EME.$('#recurrence-frequency')?.value;
        const descriptor = EME.$(`span#interval-${frequency}${number}`);
        if (descriptor) eme_toggle(descriptor, true);
    }

    function updateIntervalSelectors() {
        EME.$$('span.alternate-selector').forEach(el => eme_toggle(el, false));
        const frequency = EME.$('#recurrence-frequency')?.value;
        const selector = EME.$(`span#${frequency}-selector`);
        if (selector) eme_toggle(selector, true);
    }

    function updateShowHideRecurrence() {
        const recurrenceChecked = EME.$('#event-recurrence')?.checked;
        const patternEl = EME.$('#event_recurrence_pattern');
        const durationDiv = EME.$('#div_recurrence_event_duration');
        const recDateDiv = EME.$('#div_recurrence_date');
        const eventDateDiv = EME.$('#div_event_date');
        
        if (recurrenceChecked) {
            if (patternEl) {
                patternEl.style.opacity = '0';
                eme_toggle(patternEl, true);
                patternEl.style.transition = 'opacity 300ms';
                requestAnimationFrame(() => patternEl.style.opacity = '1');
            }
            if (durationDiv) eme_toggle(durationDiv, true);
            if (recDateDiv) eme_toggle(recDateDiv, true);
            if (eventDateDiv) eme_toggle(eventDateDiv, false);
        } else {
            if (patternEl) eme_toggle(patternEl, false);
            if (durationDiv) eme_toggle(durationDiv, false);
            if (recDateDiv) eme_toggle(recDateDiv, false);
            if (eventDateDiv) eme_toggle(eventDateDiv, true);
        }
    }

    function updateShowHideRecurrenceSpecificDays() {
        const frequency = EME.$('#recurrence-frequency')?.value;
        const intervalsDiv = EME.$('#recurrence-intervals');
        const endDateInput = EME.$('#localized-rec-end-date');
        const explanationP = EME.$('p#recurrence-dates-explanation');
        const specificSpan = EME.$('span#recurrence-dates-explanation-specificdates');
        const startDateInput = EME.$('#localized-rec-start-date');
        
        if (frequency === 'specific') {
            if (intervalsDiv) eme_toggle(intervalsDiv, false);
            if (endDateInput) eme_toggle(endDateInput, false);
            if (explanationP) eme_toggle(explanationP, false);
            if (specificSpan) eme_toggle(specificSpan, true);
            if (startDateInput) {
                startDateInput.setAttribute('required', 'true');
                if (startDateInput._fdatepicker) {
                    startDateInput._fdatepicker.setOption('multiple', true);
                }
            }
        } else {
            if (intervalsDiv) eme_toggle(intervalsDiv, true);
            if (endDateInput) eme_toggle(endDateInput, true);
            if (explanationP) eme_toggle(explanationP, true);
            if (specificSpan) eme_toggle(specificSpan, false);
            if (startDateInput) {
                startDateInput.removeAttribute('required');
                if (startDateInput._fdatepicker) {
                    startDateInput._fdatepicker.setOption('multiple', false);
                }
                // if the recurrence contained specific days before, clear those
                const submitInput = EME.$('#rec-start-date-to-submit');
                if (submitInput?.value.includes(',')) {
                    startDateInput._fdatepicker.clear();
                }
            }
        }
    }

    function updateShowHideRsvp() {
        const rsvpChecked = EME.$('#event_rsvp')?.checked;
        const elements = [
            EME.$('#rsvp-details'),
            EME.$('#div_event_rsvp'),
            EME.$('#div_dyndata'),
            EME.$('#div_event_dyndata_allfields'),
            EME.$('#div_event_payment_methods'),
            EME.$('#div_event_registration_form_format'),
            EME.$('#div_event_cancel_form_format'),
            EME.$('#div_event_registration_recorded_ok_html'),
            EME.$('#div_event_attendance_info')
        ];
        
        elements.forEach(el => {
            if (el) {
                if (rsvpChecked) {
                    el.style.opacity = '0';
                    eme_toggle(el, true);
                    el.style.transition = 'opacity 300ms';
                    requestAnimationFrame(() => el.style.opacity = '1');
                } else {
                    el.style.transition = 'opacity 300ms';
                    el.style.opacity = '0';
                    setTimeout(() => eme_toggle(el, false), 300);
                }
            }
        });
    }

    function updateShowHideTasks() {
        const tasksChecked = EME.$('#event_tasks')?.checked;
        const container = EME.$('#tab-tasks-container');
        if (container) {
            if (tasksChecked) {
                container.style.opacity = '0';
                eme_toggle(container, true);
                container.style.transition = 'opacity 300ms';
                requestAnimationFrame(() => container.style.opacity = '1');
            } else {
                container.style.transition = 'opacity 300ms';
                container.style.opacity = '0';
                setTimeout(() => eme_toggle(container, false), 300);
            }
        }
    }

    function updateShowHideTodos() {
        const todosChecked = EME.$('#event_todos')?.checked;
        const container = EME.$('#tab-todos-container');
        if (container) {
            if (todosChecked) {
                container.style.opacity = '0';
                eme_toggle(container, true);
                container.style.transition = 'opacity 300ms';
                requestAnimationFrame(() => container.style.opacity = '1');
            } else {
                container.style.transition = 'opacity 300ms';
                container.style.opacity = '0';
                setTimeout(() => eme_toggle(container, false), 300);
            }
        }
    }

    function updateShowHideRsvpAutoApprove() {
        const approvalChecked = EME.$('#approval_required-checkbox')?.checked;
        const warningSpan = EME.$('span#span_approval_required_mail_warning');
        const settingsP = EME.$('#p_approve_settings');
        const pendingDetails = EME.$('#details_pending');
        const reminderDiv = EME.$('#div_event_registration_pending_reminder_email');
        
        if (approvalChecked) {
            if (warningSpan) {
                warningSpan.style.opacity = '0';
                eme_toggle(warningSpan, true);
                warningSpan.style.transition = 'opacity 300ms';
                requestAnimationFrame(() => warningSpan.style.opacity = '1');
            }
            if (settingsP) {
                settingsP.style.opacity = '0';
                eme_toggle(settingsP, true);
                settingsP.style.transition = 'opacity 300ms';
                requestAnimationFrame(() => settingsP.style.opacity = '1');
            }
            if (pendingDetails) eme_toggle(pendingDetails, true);
            if (reminderDiv) eme_toggle(reminderDiv, true);
        } else {
            if (warningSpan) eme_toggle(warningSpan, false);
            if (settingsP) {
                settingsP.style.transition = 'opacity 300ms';
                settingsP.style.opacity = '0';
                setTimeout(() => eme_toggle(settingsP, false), 300);
            }
            if (pendingDetails) eme_toggle(pendingDetails, false);
            if (reminderDiv) eme_toggle(reminderDiv, false);
        }
    }

    function updateShowHideRsvpRequireUserConfirmation() {
        const confirmChecked = EME.$('#eme_prop_require_user_confirmation')?.checked;
        const details = EME.$('#details_userconfirm');
        if (details) {
            eme_toggle(details, confirmChecked);
        }
    }

    function updateShowHideTime() {
        const allDayChecked = EME.$('#eme_prop_all_day')?.checked;
        const timeSelector = EME.$('#time-selector');
        if (timeSelector) {
            eme_toggle(timeSelector, !allDayChecked);
        }
    }

    function updateShowHideMultiPriceDescription() {
        const priceInput = EME.$('#price');
        if (priceInput) {
            const multiPriceRow = EME.$('#row_multiprice_desc');
            const priceRow = EME.$('#row_price_desc');
            
            if (priceInput.value.includes('||')) {
                if (multiPriceRow) eme_toggle(multiPriceRow, true);
                if (priceRow) eme_toggle(priceRow, false);
            } else {
                if (multiPriceRow) eme_toggle(multiPriceRow, false);
                if (priceRow) eme_toggle(priceRow, true);
            }
        }
    }

    function updateShowHideLocMaxCapWarning() {
        const capacityInput = EME.$('#eme_loc_prop_max_capacity');
        const warning = EME.$('#loc_max_cap_warning');
        if (capacityInput && warning) {
            const capacity = parseInt(capacityInput.value) || 0;
            eme_toggle(warning, capacity > 0);
        }
    }

    function eme_event_location_autocomplete() {
        const locationNameInput = EME.$('#location_name');
        if (locationNameInput) {
            let timeout;
            
            document.addEventListener('click', () => {
                EME.$$('.eme-autocomplete-suggestions').forEach(el => el.remove());
            });

            locationNameInput.addEventListener('input', function() {
                clearTimeout(timeout);
                EME.$$('.eme-autocomplete-suggestions').forEach(el => el.remove());
                
                const inputValue = this.value;
                if (inputValue.length >= 2) {
                    timeout = setTimeout(() => {
                        const formData = new URLSearchParams({
                            eme_admin_nonce: emeevents.translate_adminnonce || '',
                            name: inputValue,
                            action: 'eme_autocomplete_locations'
                        });

                        fetch(window.ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData.toString()
                        })
                        .then(response => response.json())
                        .then(data => {
                            const suggestions = document.createElement('div');
                            suggestions.className = 'eme-autocomplete-suggestions';

                            data.forEach(item => {
                                const suggestion = document.createElement('div');
                                suggestion.className = 'eme-autocomplete-suggestion';
                                suggestion.innerHTML = `<strong>${eme_htmlDecode(item.name)}</strong><br><small>${eme_htmlDecode(item.address1)} - ${eme_htmlDecode(item.city)}</small>`;
                                
                                suggestion.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    EME.$('#location_id').value = eme_htmlDecode(item.location_id);
                                    EME.$('#location_name').value = eme_htmlDecode(item.name);
                                    EME.$('#location_address1').value = eme_htmlDecode(item.address1);
                                    EME.$('#location_address2').value = eme_htmlDecode(item.address2);
                                    EME.$('#location_city').value = eme_htmlDecode(item.city);
                                    EME.$('#location_state').value = eme_htmlDecode(item.state);
                                    EME.$('#location_zip').value = eme_htmlDecode(item.zip);
                                    EME.$('#location_country').value = eme_htmlDecode(item.country);
                                    EME.$('#location_latitude').value = eme_htmlDecode(item.latitude);
                                    EME.$('#location_longitude').value = eme_htmlDecode(item.longitude);
                                    EME.$('#location_url').value = eme_htmlDecode(item.location_url);
                                    EME.$('#eme_loc_prop_map_icon').value = eme_htmlDecode(item.map_icon);
                                    EME.$('#eme_loc_prop_max_capacity').value = eme_htmlDecode(item.max_capacity);
                                    EME.$('#eme_loc_prop_online_only').value = eme_htmlDecode(item.online_only);
                                    
                                    // Set all fields to readonly
                                    ['location_id', 'location_name', 'location_address1', 'location_address2', 'location_city', 
                                     'location_state', 'location_zip', 'location_country', 'location_latitude', 'location_longitude',
                                     'location_url', 'eme_loc_prop_map_icon', 'eme_loc_prop_max_capacity'].forEach(fieldName => {
                                        const field = EME.$(`#${fieldName}`);
                                        if (field) field.readOnly = true;
                                    });
                                    
                                    const onlineField = EME.$('#eme_loc_prop_online_only');
                                    if (onlineField) onlineField.disabled = true;
                                    
                                    const editImg = EME.$('#img_edit_location');
                                    if (editImg) eme_toggle(editImg, true);
                                    
                                    if (typeof L !== 'undefined' && emeevents.translate_map_is_active === "true") {
                                        eme_displayAddress(0);
                                    }
                                });
                                
                                suggestions.appendChild(suggestion);
                            });

                            if (!data.length) {
                                const noMatch = document.createElement('div');
                                noMatch.className = 'eme-autocomplete-suggestion';
                                noMatch.innerHTML = `<strong>${emeevents.translate_nomatchlocation || 'No matches found'}</strong>`;
                                suggestions.appendChild(noMatch);
                            }

                            EME.$$('.eme-autocomplete-suggestions').forEach(el => el.remove());
                            locationNameInput.insertAdjacentElement('afterend', suggestions);
                        });
                    }, 500);
                }
            });

            locationNameInput.addEventListener('change', function() {
                if (this.value === '') {
                    ['location_id', 'location_name', 'location_address1', 'location_address2', 'location_city', 
                     'location_state', 'location_zip', 'location_country', 'location_latitude', 'location_longitude',
                     'location_url', 'eme_loc_prop_map_icon', 'eme_loc_prop_max_capacity'].forEach(fieldName => {
                        const field = EME.$(`#${fieldName}`);
                        if (field) {
                            field.value = '';
                            field.readOnly = false;
                        }
                    });
                    
                    const onlineField = EME.$('#eme_loc_prop_online_only');
                    if (onlineField) onlineField.disabled = false;
                    
                    const editImg = EME.$('#img_edit_location');
                    if (editImg) eme_toggle(editImg, false);
                }
            });

            const editImg = EME.$('#img_edit_location');
            if (editImg) {
                editImg.addEventListener('click', (e) => {
                    e.preventDefault();
                    ['location_id', 'location_name', 'location_address1', 'location_address2', 'location_city', 
                     'location_state', 'location_zip', 'location_country', 'location_latitude', 'location_longitude',
                     'location_url', 'eme_loc_prop_map_icon', 'eme_loc_prop_max_capacity'].forEach(fieldName => {
                        const field = EME.$(`#${fieldName}`);
                        if (field) field.readOnly = false;
                    });
                    
                    const onlineField = EME.$('#eme_loc_prop_online_only');
                    if (onlineField) onlineField.disabled = false;
                    
                    EME.$('#location_id').value = '';
                    eme_toggle(editImg, false);
                });
            }

            // Set initial state
            const locationIdInput = EME.$('#location_id');
            if (locationIdInput) {
                if (locationIdInput.value === '0' || locationIdInput.value === '') {
                    ['location_name', 'location_address1', 'location_address2', 'location_city', 
                     'location_state', 'location_zip', 'location_country', 'location_latitude', 'location_longitude',
                     'location_url', 'eme_loc_prop_map_icon', 'eme_loc_prop_max_capacity'].forEach(fieldName => {
                        const field = EME.$(`#${fieldName}`);
                        if (field) field.readOnly = false;
                    });
                    
                    const onlineField = EME.$('#eme_loc_prop_online_only');
                    if (onlineField) onlineField.disabled = false;
                    
                    if (editImg) eme_toggle(editImg, false);
                } else {
                    ['location_name', 'location_address1', 'location_address2', 'location_city', 
                     'location_state', 'location_zip', 'location_country', 'location_latitude', 'location_longitude',
                     'location_url', 'eme_loc_prop_map_icon', 'eme_loc_prop_max_capacity'].forEach(fieldName => {
                        const field = EME.$(`#${fieldName}`);
                        if (field) field.readOnly = true;
                    });
                    
                    const onlineField = EME.$('#eme_loc_prop_online_only');
                    if (onlineField) onlineField.disabled = true;
                    
                    if (editImg) eme_toggle(editImg, true);
                }

                locationIdInput.addEventListener('change', function() {
                    const editImg = EME.$('#img_edit_location');
                    if (editImg) {
                        eme_toggle(editImg, this.value);
                    }
                });
            }
        } else {
            // Handle location select dropdown
            const locationSelect = EME.$('#location-select-id');
            if (locationSelect) {
                locationSelect.addEventListener('change', function() {
                    const formData = new URLSearchParams({
                        eme_admin_action: 'autocomplete_locations',
                        eme_admin_nonce: emeevents.translate_adminnonce || '',
                        id: this.value
                    });

                    fetch(window.location.href + '?' + formData.toString())
                        .then(response => response.json())
                        .then(item => {
                            EME.$('input[name="location-select-name"]').value = item.name;
                            EME.$('input[name="location-select-address1"]').value = item.address1;
                            EME.$('input[name="location-select-address2"]').value = item.address2;
                            EME.$('input[name="location-select-city"]').value = item.city;
                            EME.$('input[name="location-select-state"]').value = item.state;
                            EME.$('input[name="location-select-zip"]').value = item.zip;
                            EME.$('input[name="location-select-country"]').value = item.country;
                            EME.$('input[name="location-select-latitude"]').value = item.latitude;
                            EME.$('input[name="location-select-longitude"]').value = item.longitude;
                            
                            if (emeevents.translate_map_is_active === 'true') {
                                loadMapLatLong(item.name, item.address1, item.address2, item.city, item.state, item.zip, item.country, item.latitude, item.longitude);
                            }
                        });
                });
            }
        }
    }

    function applyDefaultOnFocusBlur() {
        EME.$$('input[data-default]').forEach(el => {
            const defaultValue = el.getAttribute('data-default').replace(/<br\s*\/?>/gi, '<br>');
            
            el.addEventListener('focus', function() {
                if (this.value.trim() === '') {
                    this.value = defaultValue;
                }
            });

            el.addEventListener('blur', function() {
                if (this.value.trim() === defaultValue) {
                    this.value = '';
                }
            });
        });

        EME.$$('span[data-default]').forEach(span => {
            const defaultValue = span.getAttribute('data-default').replace(/<br\s*\/?>/gi, '<br>');
            const targetId = span.getAttribute('data-targetid');
            const target = EME.$(`#${targetId}`);
            
            if (target) {
                target.addEventListener('focus', function() {
                    if (this.value.trim() === '') {
                        this.value = defaultValue;
                    }
                });

                target.addEventListener('blur', function() {
                    if (this.value.trim() === defaultValue) {
                        this.value = '';
                    }
                });
            }
        });
    }

    function validateEventForm() {
        const recurrenceChecked = EME.$('#event-recurrence')?.checked;
        const startDate = EME.$('#localized-rec-start-date')?.value;
        const endDate = EME.$('#localized-rec-end-date')?.value;
        const endDateField = EME.$('#localized-rec-end-date');
        
        if (recurrenceChecked && startDate === endDate) {
            alert(emeevents.translate_startenddate_identical || 'Start and end dates cannot be identical');
            if (endDateField) endDateField.style.border = '2px solid red';
            return false;
        } else {
            if (endDateField) endDateField.style.border = '1px solid #DFDFDF';
        }
        
        // Enable online_only checkbox before submit
        const onlineField = EME.$('#eme_loc_prop_online_only');
        if (onlineField) onlineField.disabled = false;
        
        return true;
    }

    function changeEventAdminPageTitle() {
        let title;
        const eventNameInput = EME.$('input[name=event_name]');
        if (eventNameInput) {
            const eventName = eventNameInput.value;
            if (!eventName) {
                title = emeevents.translate_insertnewevent || 'Insert New Event';
            } else {
                title = emeevents.translate_editeventstring || 'Edit Event: %s';
                title = title.replace(/%s/g, eventName);
            }
            document.title = eme_htmlDecode(title);
        }
    }

    function updateShowHideStuff() {
        const action = EME.$('#eme_admin_action')?.value || '';
        const categorySpan = EME.$('span#span_addtocategory');
        const trashSpan = EME.$('span#span_sendtrashmails');
        const extendSpan = EME.$('span#span_extendrecurrences');
        
        if (categorySpan) eme_toggle(categorySpan,action === 'addCategory');
        if (trashSpan) eme_toggle(trashSpan,action === 'trashEvents');
        if (extendSpan) eme_toggle(extendSpan,action === 'extendRecurrences');
    }

    // Initialize date pickers
    if (EME.$('#localized-start-date')) {
        new FDatepicker('#localized-start-date',{
            format: emeevents.translate_fdateformat,
            onSelect: function(formattedDate, date, inst) {
                const endDatePicker = EME.$('#localized-end-date');
                if (endDatePicker && endDatePicker._fdatepicker) {
                    const endDate = endDatePicker._fdatepicker.selectedDate;
                    if (endDate) {
                        if (endDate.getTime() < date.getTime()) {
                            endDatePicker._fdatepicker.setDate(date);
                        }
                    }
                }
            }
        });
    }

    if (EME.$('#localized-end-date')) {
        new FDatepicker('#localized-end-date',{
            format: emeevents.translate_fdateformat,
            onSelect: function(formattedDate, date, inst) {
                const startDatePicker = EME.$('#localized-start-date');
                if (startDatePicker && startDatePicker._fdatepicker) {
                    const startDate = startDatePicker._fdatepicker.selectedDate;
                    if (startDate) {
                        if (startDate.getTime() > date.getTime()) {
                            startDatePicker._fdatepicker.setDate(date);
                        }
                    }
                }
            }
        });
    }

    if (EME.$('#localized-rec-start-date')) {
        new FDatepicker('#localized-rec-start-date',{
            format: emeevents.translate_fdateformat
        });
    }

    if (EME.$('#localized-rec-end-date')) {
        new FDatepicker('#localized-rec-end-date',{
            format: emeevents.translate_fdateformat,
            onSelect: function(formattedDate, date, inst) {
                if (!Array.isArray(date)) {
                    const startDatePicker = EME.$('#localized-rec-start-date');
                    if (startDatePicker && startDatePicker._fdatepicker) {
                        const startDate = startDatePicker._fdatepicker.selectedDate;
                        if (startDate) {
                            if (startDate.getTime() > date.getTime()) {
                                startDatePicker._fdatepicker.setDate(date);
                            }
                        }
                    }
                }
            }
        });
    }

    // Bind event listeners
    const eventRecurrence = EME.$('#event-recurrence');
    if (eventRecurrence) eventRecurrence.addEventListener('change', updateShowHideRecurrence);
    
    const eventTasks = EME.$('#event_tasks');
    if (eventTasks) eventTasks.addEventListener('change', updateShowHideTasks);
    
    const eventTodos = EME.$('#event_todos');
    if (eventTodos) eventTodos.addEventListener('change', updateShowHideTodos);
    
    const eventRsvp = EME.$('#event_rsvp');
    if (eventRsvp) eventRsvp.addEventListener('change', updateShowHideRsvp);
    
    const allDay = EME.$('#eme_prop_all_day');
    if (allDay) allDay.addEventListener('change', updateShowHideTime);
    
    const price = EME.$('#price');
    if (price) price.addEventListener('change', updateShowHideMultiPriceDescription);
    
    const maxCapacity = EME.$('#eme_loc_prop_max_capacity');
    if (maxCapacity) maxCapacity.addEventListener('change', updateShowHideLocMaxCapWarning);
    
    const approvalRequired = EME.$('#approval_required-checkbox');
    if (approvalRequired) approvalRequired.addEventListener('change', updateShowHideRsvpAutoApprove);
    
    const userConfirmation = EME.$('#eme_prop_require_user_confirmation');
    if (userConfirmation) userConfirmation.addEventListener('change', updateShowHideRsvpRequireUserConfirmation);

    // Recurrence elements
    const recurrenceInterval = EME.$('#recurrence-interval');
    if (recurrenceInterval) recurrenceInterval.addEventListener('keyup', updateIntervalDescriptor);
    
    const recurrenceFrequency = EME.$('#recurrence-frequency');
    if (recurrenceFrequency) {
        recurrenceFrequency.addEventListener('change', updateIntervalDescriptor);
        recurrenceFrequency.addEventListener('change', updateIntervalSelectors);
        recurrenceFrequency.addEventListener('change', updateShowHideRecurrenceSpecificDays);
    }

    // Image handling
    const imageButton = EME.$('#event_image_button');
    const removeImageBtn = EME.$('#event_remove_image_button');
    const imageUrl = EME.$('#event_image_url');
    const imageExample = EME.$('#eme_event_image_example');
    const imageId = EME.$('#event_image_id');
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', (e) => {
            e.preventDefault();
            
            if (imageUrl) imageUrl.value = '';
            if (imageId) imageId.value = '';
            if (imageExample) {
                imageExample.src = '';
                eme_toggle(imageExample, false);
            }
            if (imageButton) eme_toggle(imageButton, true);
            eme_toggle(removeImageBtn, false);
        });
    }

    if (imageButton) {
        imageButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (window.wp && window.wp.media) {
                const customUploader = window.wp.media({
                    title: emeevents.translate_selectfeaturedimg || 'Select Featured Image',
                    button: { text: emeevents.translate_setfeaturedimg || 'Set Featured Image' },
                    library: { type: 'image' },
                    multiple: false
                }).on('select', function() {
                    const selection = customUploader.state().get('selection');
                    selection.map(function(attach) {
                        const attachment = attach.toJSON();
                        
                        if (imageUrl) imageUrl.value = attachment.url;
                        if (imageId) imageId.value = attachment.id;
                        if (imageExample) {
                            imageExample.src = attachment.url;
                            eme_toggle(imageExample, true);
                        }
                        eme_toggle(imageButton, false);
                        if (removeBtn) eme_toggle(removeImageBtn, true);
                    });
                }).open();
            }
        });
    }

    if (imageUrl) {
        if (imageUrl.value !== '') {
            if (imageButton) eme_toggle(imageButton, false);
            if (removeImageBtn) eme_toggle(removeImageBtn, true);
            if (imageExample) eme_toggle(imageExample, true);
        } else {
            if (imageButton) eme_toggle(imageButton, true);
            if (removeImageBtn) eme_toggle(removeImageBtn, false);
            if (imageExample) eme_toggle(imageExample, false);
        }
    }

    initTomSelectRemote('#event_author.eme_select2_wpuser_class', {
        action: 'eme_wpuser_select2',
        ajaxParams: {
            eme_admin_nonce: emeevents.translate_adminnonce
        }
    });
    initTomSelectRemote('#event_contactperson_id.eme_select2_wpuser_class', {
        action: 'eme_wpuser_select2',
        extraPlugins: ['clear_button'],
        placeholder: emeevents.translate_selectcontact,
        ajaxParams: {
            eme_admin_nonce: emeevents.translate_adminnonce
        }
    });

    // Admin action change handler
    const adminAction = EME.$('#eme_admin_action');
    if (adminAction) {
        updateShowHideStuff();
        adminAction.addEventListener('change', updateShowHideStuff);
    }

    // --- Initialize Events Table ---
    if (EventsTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'eventstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        EventsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        let eventFields = {
            event_id: {
                key: true,
                title: emeevents.translate_id,
                width: '1%',
                columnResizable: false,
                visibility: 'hidden'
            },
            event_name: {
                title: emeevents.translate_name,
                visibility: 'fixed'
            },
            event_status: {
                title: emeevents.translate_status,
                width: '5%'
            },
            copy: {
                title: emeevents.translate_copy,
                sorting: false,
                width: '2%',
                columnResizable: false,
                listClass: 'eme-ftable-center',
            },
            rsvp: {
                title: emeevents.translate_rsvp,
                sorting: false,
                width: '2%',
                columnResizable: false,
                listClass: 'eme-ftable-center'
            },
            eventprice: {
                title: emeevents.translate_eventprice,
                sorting: false
            },
            location_name: {
                title: emeevents.translate_location
            },
            event_start: {
                title: emeevents.translate_eventstart,
                width: '5%'
            },
            creation_date: {
                title: emeevents.translate_created_on,
                visibility: 'hidden',
                width: '5%'
            },
            modif_date: {
                title: emeevents.translate_modified_on,
                visibility: 'hidden',
                width: '5%'
            },
            recinfo: {
                title: emeevents.translate_recinfo,
                sorting: false
            }
        };

        // Add extra fields
        const extraFieldsAttr = EventsTableContainer.dataset.extrafields;
        const extraFieldNamesAttr = EventsTableContainer.dataset.extrafieldnames;
        const extraFieldSearchableAttr = EventsTableContainer.dataset.extrafieldsearchable;
        if (extraFieldsAttr && extraFieldNamesAttr) {
            const extraFields = extraFieldsAttr.split(',');
            const extraNames = extraFieldNamesAttr.split(',');
            const extraSearches = extraFieldSearchableAttr.split(',');
            extraFields.forEach((value, index) => {
                if (value == 'SEPARATOR') {
                    let fieldindex = 'SEPARATOR_'+index;
                    eventFields[fieldindex] = { title: extraNames[index], sorting: false, visibility: 'separator' };
                } else {
                    let fieldindex = 'FIELD_'+value;
                    eventFields[fieldindex] = { title: extraNames[index], sorting: extraSearches[index]=='1', visibility: 'hidden' };
                }
            });
        }

        EventsTable = new FTable('#EventsTableContainer', {
            title: emeevents.translate_events,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'event_start ASC, event_name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            csvExport: true,
            printTable: true,
            actions: { listAction: ajaxurl },
            listQueryParams: () => ({
                action: 'eme_events_list',
                eme_admin_nonce: emeevents.translate_adminnonce,
                trash: $_GET['trash'] || '',
                scope: EME.$('#scope')?.value || '',
                status: EME.$('#status')?.value || '',
                category: EME.$('#category')?.value || '',
                search_name: EME.$('#search_name')?.value || '',
                search_location: EME.$('#search_location')?.value || '',
                search_start_date: EME.$('#search_start_date')?.value || '',
                search_end_date: EME.$('#search_end_date')?.value || '',
                search_customfields: eme_getValue(EME.$('#search_customfields')),
                search_customfieldids: eme_getValue(EME.$('#search_customfieldids'))
            }),
            fields: eventFields,
            sortingInfoSelector: '#eventstablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        EventsTable.load();
    }

    // --- Initialize Recurrences Table ---
    if (RecurrencesTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'recurrencetablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        RecurrencesTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        RecurrencesTable = new FTable('#RecurrencesTableContainer', {
            title: emeevents.translate_recurrences,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'event_name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: { listAction: ajaxurl },
            listQueryParams: () => ({
                action: 'eme_recurrences_list',
                eme_admin_nonce: emeevents.translate_adminnonce,
                scope: EME.$('#scope')?.value || '',
                search_name: EME.$('#search_name')?.value || '',
                search_start_date: EME.$('#search_start_date')?.value || '',
                search_end_date: EME.$('#search_end_date')?.value || ''
            }),
            fields: {
                recurrence_id: {
                    key: true,
                    title: emeevents.translate_id,
                    width: '1%',
                    columnResizable: false
                },
                event_name: {
                    title: emeevents.translate_name,
                    sorting: false,
                    visibility: 'fixed'
                },
                event_status: {
                    title: emeevents.translate_status,
                    sorting: false,
                    width: '5%'
                },
                copy: {
                    title: emeevents.translate_copy,
                    sorting: false,
                    width: '2%',
                    listClass: 'eme-ftable-center'
                },
                eventprice: {
                    title: emeevents.translate_eventprice,
                    sorting: false
                },
                location_name: {
                    title: emeevents.translate_location,
                    sorting: false,
                },
                creation_date: {
                    title: emeevents.translate_created_on,
                    visibility: 'hidden',
                    width: '5%'
                },
                modif_date: {
                    title: emeevents.translate_modified_on,
                    visibility: 'hidden',
                    width: '5%'
                },
                recinfo: {
                    title: emeevents.translate_recinfo,
                    sorting: false
                },
                rec_singledur: {
                    title: emeevents.translate_rec_singledur,
                    sorting: false
                }
            },
            sortingInfoSelector: '#recurrencetablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        RecurrencesTable.load();
    }

    // --- Events Bulk Actions ---
    const eventsButton = EME.$('#EventsActionsButton');
    if (eventsButton) {
        eventsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = EventsTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;
            const sendTrashMails = EME.$('#send_trashmails')?.value || 'no';
            const addCategory = EME.$('#addtocategory')?.value || '';

            if (selectedRows.length === 0 || !doAction) return;

            let proceed = true;
            if (['trashEvents', 'deleteEvents', 'deleteRecurrences'].includes(doAction) && !confirm(emeevents.translate_areyousuretodeleteselected)) {
                proceed = false;
            }

            if (proceed) {
                eventsButton.textContent = emeevents.translate_pleasewait;
                eventsButton.disabled = true;

                const ids = selectedRows.map(row => row.dataset.recordKey);
                const idsJoined = ids.join(',');

                const formData = new FormData();
                formData.append('event_id', idsJoined);
                formData.append('action', 'eme_manage_events');
                formData.append('do_action', doAction);
                formData.append('send_trashmails', sendTrashMails);
                formData.append('addtocategory', addCategory);
                formData.append('eme_admin_nonce', emeevents.translate_adminnonce);

                eme_postJSON(ajaxurl, formData, (data) => {
                    EventsTable.reload();
                    eventsButton.textContent = emeevents.translate_apply;
                    eventsButton.disabled = false;

                    const msg = EME.$('#events-message');
                    if (msg) {
                        msg.textContent = data.Message;
                        eme_toggle(msg, true);
                        setTimeout(() => eme_toggle(msg, false), 5000);
                    }
                });
            }
        });
    }

    // --- Recurrences Bulk Actions ---
    const recurrencesButton = EME.$('#RecurrencesActionsButton');
    if (recurrencesButton) {
        recurrencesButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = RecurrencesTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;
            const recNewStartDate = EME.$('#rec_new_start_date')?.value || '';
            const recNewEndDate = EME.$('#rec_new_end_date')?.value || '';

            if (selectedRows.length === 0 || !doAction) return;

            let proceed = true;
            if (doAction === 'deleteRecurrences' && !confirm(emeevents.translate_areyousuretodeleteselected)) {
                proceed = false;
            }

            if (proceed) {
                recurrencesButton.textContent = emeevents.translate_pleasewait;
                recurrencesButton.disabled = true;

                const ids = selectedRows.map(row => row.dataset.recordKey);
                const idsJoined = ids.join(',');

                const formData = new FormData();
                formData.append('recurrence_id', idsJoined);
                formData.append('action', 'eme_manage_recurrences');
                formData.append('do_action', doAction);
                formData.append('rec_new_start_date', recNewStartDate);
                formData.append('rec_new_end_date', recNewEndDate);
                formData.append('eme_admin_nonce', emeevents.translate_adminnonce);

                eme_postJSON(ajaxurl, formData, (data) => {
                    RecurrencesTable.reload();
                    recurrencesButton.textContent = emeevents.translate_apply;
                    recurrencesButton.disabled = false;

                    const msg = EME.$('#recurrences-message');
                    if (msg) {
                        msg.textContent = data.Message;
                        eme_toggle(msg, true);
                        setTimeout(() => eme_toggle(msg, false), 5000);
                    }
                });
            }
        });
    }

    // --- Reload Buttons ---
    EME.$('#EventsLoadRecordsButton')?.addEventListener('click', e => {
        e.preventDefault();
        EventsTable.load();
    });

    EME.$('#RecurrencesLoadRecordsButton')?.addEventListener('click', e => {
        e.preventDefault();
        RecurrencesTable.load();
    });

    EME.$('#eventForm')?.addEventListener('submit', function(event) {
        const form = this.form;
        // Manually trigger HTML5 validation
        if (!form.checkValidity()) {
            event.preventDefault(); // Stop submission

            // Find the first invalid field
            const invalidField = form.querySelector(':invalid');
            if (invalidField) {
                eme_scrollToInvalidInput(invalidField); // this switches to the correct tab
            }
            return;
        }
    });

    // calc RSVP cutoff date
    const rsvpstartinputs = [
        'eme_prop_rsvp_start_target',
        'eme_prop_rsvp_start_number_days',
        'eme_prop_rsvp_start_number_hours'
    ];
    rsvpstartinputs.forEach(selector => {
        const el = document.getElementById(selector);
        if (el) {
            el.addEventListener('input', calculateRsvpStart);
        }
    });
    const rsvpendinputs = [
        'eme_prop_rsvp_end_target',
        'eme_prop_rsvp_end_number_days',
        'eme_prop_rsvp_end_number_hours'
    ];
    rsvpendinputs.forEach(selector => {
        const el = document.getElementById(selector);
        if (el) {
            el.addEventListener('input', calculateRsvpEnd);
        }
    });
    const rsvpcutoffinputs = [
        'eme_prop_cancel_rsvp_days'
    ];
    rsvpcutoffinputs.forEach(selector => {
        const el = document.getElementById(selector);
        if (el) {
            el.addEventListener('input', calculateRsvpCutoffDisplay);
        }
    });
    const rsvpstartendinputs = [
        'start-date-to-submit',
        'end-date-to-submit',
        'start-time-to-submit',
        'end-time-to-submit',
    ];
    rsvpstartendinputs.forEach(selector => {
        const el = document.getElementById(selector);
        if (el) {
            el.addEventListener('change', () => {
                calculateRsvpStart();
                calculateRsvpEnd();
                calculateRsvpCutoffDisplay();
            });
        }
    });

    setTimeout(() => {
        // Hide recurrence date div initially
        const recDateDiv = EME.$('#div_recurrence_date');
        if (recDateDiv) eme_toggle(recDateDiv, false);

        // Apply default focus/blur behavior
        applyDefaultOnFocusBlur();

        eme_event_location_autocomplete();

        // Initialize all show/hide functions
        updateShowHideStuff();
        updateShowHideRecurrence();
        updateShowHideRsvp();
        updateShowHideTasks();
        updateShowHideTodos();
        updateShowHideRsvpAutoApprove();
        updateShowHideRsvpRequireUserConfirmation();
        updateShowHideTime();
        updateShowHideMultiPriceDescription();
        updateShowHideLocMaxCapWarning();
        calculateRsvpStart();
        calculateRsvpEnd();

        if (EME.$('#recurrence-frequency')) {
            updateIntervalDescriptor();
            updateIntervalSelectors();
            updateShowHideRecurrenceSpecificDays();
        }

        // Event name title update
        const eventNameInput = EME.$('input[name=event_name]');
        if (eventNameInput) {
            changeEventAdminPageTitle();
            eventNameInput.addEventListener('keyup', changeEventAdminPageTitle);
        }

    }, 100);
});
