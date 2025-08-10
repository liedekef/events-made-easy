document.addEventListener('DOMContentLoaded', function () {
    const BookingsTableContainer = $('#BookingsTableContainer');
    let BookingsTable;

    // --- Initialize Bookings Table ---
    if (BookingsTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'bookingsrsvptablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        BookingsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        let bookingFields = {
            booking_id: {
                key: true,
                width: '1%',
                columnResizable: false,
                list: false,
            },
            event_name: {
                title: emersvp.translate_eventinfo
            },
            event_id: {
                title: emersvp.translate_event_id,
                sorting: false,
                visibility: 'hidden'
            },
            person_id: {
                title: emersvp.translate_person_id,
                sorting: false,
                width: '1%',
                columnResizable: false,
                visibility: 'hidden'
            },
            rsvp: {
                title: emersvp.translate_rsvp,
                sorting: false,
                width: '2%',
                listClass: 'eme-ftable-center'
            },
            event_start: {
                title: emersvp.translate_eventstart,
            },
            booker: {
                title: emersvp.translate_booker
            },
            creation_date: {
                title: emersvp.translate_bookingdate
            },
            seats: {
                title: emersvp.translate_seats,
                sorting: false,
                listClass: 'eme-ftable-center'
            },
            eventprice: {
                title: emersvp.translate_eventprice,
                sorting: false
            },
            event_cats: {
                title: emersvp.translate_event_cats,
                sorting: false,
                visibility: 'hidden'
            },
            discount: {
                title: emersvp.translate_discount,
                sorting: false,
                visibility: 'hidden'
            },
            dcodes_used: {
                title: emersvp.translate_dcodes_used,
                sorting: false,
                visibility: 'hidden'
            },
            totalprice: {
                title: emersvp.translate_totalprice,
                sorting: false
            },
            unique_nbr: {
                title: emersvp.translate_uniquenbr,
                visibility: 'hidden'
            },
            booking_paid: {
                title: emersvp.translate_paid,
                visibility: 'hidden'
            },
            remaining: {
                title: emersvp.translate_remaining,
                sorting: false,
                visibility: 'hidden'
            },
            received: {
                title: emersvp.translate_received,
                sorting: false,
                visibility: 'hidden'
            },
            payment_date: {
                title: emersvp.translate_paymentdate,
                visibility: 'hidden'
            },
            pg: {
                title: emersvp.translate_pg,
                visibility: 'hidden'
            },
            pg_pid: {
                title: emersvp.translate_pg_pid,
                visibility: 'hidden'
            },
            payment_id: {
                title: emersvp.translate_paymentid
            },
            attend_count: {
                title: emersvp.translate_attend_count,
                visibility: 'hidden'
            },
            lastreminder: {
                title: emersvp.translate_lastreminder,
                sorting: false,
                visibility: 'hidden'
            },
            booking_comment: {
                title: emersvp.translate_comment,
                sorting: false,
                visibility: 'hidden'
            },
            wp_user: {
                title: emersvp.translate_wpuser,
                sorting: false,
                visibility: 'hidden'
            }
        };

        // Add extra fields
        const extraFieldsAttr = BookingsTableContainer.dataset.extrafields;
        const extraFieldNamesAttr = BookingsTableContainer.dataset.extrafieldnames;
        const extrafieldsearchableAttr = BookingsTableContainer.dataset.extrafieldsearchable;
        if (extraFieldsAttr && extraFieldNamesAttr) {
            const extraFields = extraFieldsAttr.split(',');
            const extraNames = extraFieldNamesAttr.split(',');
            const extraSearches = extrafieldsearchableAttr.split(',');
            extraFields.forEach((field, index) => {
                if (field == 'SEPARATOR') {
                    let fieldindex = 'SEPARATOR_'+index;
                    bookingFields[fieldindex] = { title: extraNames[index] || field, sorting: false, visibility: 'separator' };
                } else if (field) {
                    let fieldindex = 'FIELD_'+index;
                    bookingFields[fieldindex] = { title: extraNames[index] || field, sorting: extraSearches[index]=='1', visibility: 'hidden' };
                }
            });
        }

        // Add edit link field
        bookingFields.edit_link = {
            title: emersvp.translate_edit,
            sorting: false,
            visibility: 'fixed',
            width: '1%',
            listClass: 'ftable-command-column eme-ftable-center',
            value: record => {
                const a = document.createElement('a');
                a.href = record.edit_link_url;
                a.textContent = emersvp.translate_edit;
                a.className = 'button';
                return a;
            }
        };

        BookingsTable = new FTable('#BookingsTableContainer', {
            title: emersvp.translate_bookings,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'booking_date DESC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            csvExport: true,
            printTable: true,
            actions: { listAction: ajaxurl },
            listQueryParams: () => ({
                action: 'eme_bookings_list',
                eme_admin_nonce: emersvp.translate_adminnonce,
                trash: new URLSearchParams(window.location.search).get('trash') || '',
                scope: eme_getValue($('#scope')),
                category: eme_getValue($('#category')).value || '',
                booking_status: eme_getValue($('#booking_status')),
                search_event: eme_getValue($('#search_event')),
                search_person: eme_getValue($('#search_person')),
                search_customfields: eme_getValue($('#search_customfields')),
                search_unique: eme_getValue($('#search_unique')),
                search_paymentid: eme_getValue($('#search_paymentid')),
                search_pg_pid: eme_getValue($('#search_pg_pid')),
                search_start_date: eme_getValue($('#search_start_date')),
                search_end_date: eme_getValue($('#search_end_date')),
                event_id: $('#event_id')?.value || '',
                person_id: $_GET['person_id']
            }),
            fields: bookingFields,
            sortingInfoSelector: '#bookingsrsvptablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        BookingsTable.load();
    }

    // --- Conditional UI for Actions ---
    function updateShowHideStuff() {
        const action = $('#eme_admin_action')?.value || '';

        eme_toggle($('#span_pdftemplate'), action === 'pdf');
        eme_toggle($('#span_htmltemplate'), action === 'html');
        eme_toggle($('span#span_sendtocontact'), action === 'resendApprovedBooking');
        eme_toggle($('#span_sendmails'), ['trashBooking','approveBooking','pendingBooking','unsetwaitinglistBooking','setwaitinglistBooking','markPaid','markUnpaid'].includes(action));
        eme_toggle($('span#span_refund'), ['trashBooking','pendingBooking','setwaitinglistBooking','markUnpaid'].includes(action) && !$_GET['trash']);
        eme_toggle($('#span_addtogroup'), action === 'addToGroup');
        eme_toggle($('#span_removefromgroup'), action === 'removeFromGroup');
        eme_toggle($('#span_removefromgroup'), action === 'removeFromGroup');
        eme_toggle($('span#span_partialpayment'), action === 'partialPayment');
        eme_toggle($('span#span_rsvpmailtemplate'), action === 'rsvpMails');
    }

    $('#eme_admin_action')?.addEventListener('change', updateShowHideStuff);
    updateShowHideStuff();

    // --- Bulk Actions ---
    const actionsButton = $('#BookingsActionsButton');
    if (actionsButton) {
        actionsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = BookingsTable.getSelectedRows();
            const doAction = $('#eme_admin_action').value;
            const sendMail = $('#send_mail')?.value || 'no';

            if (selectedRows.length === 0 || !doAction) return;

            if (['trashBookings', 'deleteBookings'].includes(doAction) && !confirm(emersvp.translate_areyousuretodeleteselected)) {
                return;
            }
            if (doAction == 'partialPayment' && selectedRows.length > 1) {
                alert(emersvp.translate_selectonerowonlyforpartial);
                return;
            }

            actionsButton.textContent = emersvp.translate_pleasewait;
            actionsButton.disabled = true;

            const formData = new FormData();
            if (doAction=='addToGroup' || doAction=='removeFromGroup') {
                const ids = selectedRows.map(row => row.recordData.person_id);
                const idsJoined = ids.join(',');
                formData.append('person_id', idsJoined);
                formData.append('action', 'eme_manage_people');
                formData.append('do_action', doAction);
                formData.append('addtogroup', $('#addtogroup').value);
                formData.append('removefromgroup', $('#removefromgroup').value);
            } else { 
                const ids = selectedRows.map(row => row.dataset.recordKey);
                const idsJoined = ids.join(',');
                formData.append('booking_ids', idsJoined);
                formData.append('action', 'eme_manage_bookings');
                formData.append('do_action', doAction);
                formData.append('send_mail', sendMail);
                formData.append('send_to_contact_too', $('#send_to_contact_too').value);
                formData.append('refund', $('#refund').value);
                formData.append('partial_amount', $('#partial_amount').value);
                formData.append('rsvpmail_template', $('#rsvpmail_template').value);
                formData.append('rsvpmail_template_subject', $('#rsvpmail_template_subject').value);
                formData.append('pdf_template', $('#pdf_template')?.value || '');
                formData.append('pdf_template_header', $('#pdf_template_header')?.value || '');
                formData.append('pdf_template_footer', $('#pdf_template_footer')?.value || '');
                formData.append('html_template', $('#html_template')?.value || '');
                formData.append('html_template_header', $('#html_template_header')?.value || '');
                formData.append('html_template_footer', $('#html_template_footer')?.value || '');
            }
            formData.append('eme_admin_nonce', emersvp.translate_adminnonce);

            if (doAction === 'sendMails') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = emersvp.translate_admin_sendmails_url;
                ['booking_ids', 'eme_admin_action'].forEach(key => {
                    const val = key === 'booking_ids' ? idsJoined : 'new_mailing';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = val;
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
                return;
            }

            if (['pdf', 'html'].includes(doAction)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = ajaxurl;
                // Add FormData entries as hidden inputs
                for (const [key, value] of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
                document.body.appendChild(form);
                form.submit();
                actionsButton.textContent = emersvp.translate_apply;
                actionsButton.disabled = false;
                return;
            }

            eme_postJSON(ajaxurl, formData, (data) => {
                if (data.Result !== 'OK') {
                    const msg = $('div#bookings-message');
                    if (msg) {
                        msg.textContent = data.htmlmessage;
                        eme_toggle(msg, true);
                        setTimeout(() => eme_toggle(msg, false), 5000);
                    }
                }
                BookingsTable.reload();
                actionsButton.textContent = emersvp.translate_apply;
                actionsButton.disabled = false;
            });
        });
    }

    // --- Reload Button ---
    const loadButton = $('#BookingsLoadRecordsButton');
    if (loadButton) {
        loadButton.addEventListener('click', e => {
            e.preventDefault();
            BookingsTable.load();
        });
    }

    // --- Autocomplete: chooseevent ---
    if ($('input[name="chooseevent"]')) {
        let timeout;
        const input = $('input[name="chooseevent"]');
        document.addEventListener('click', () => $$('.eme-autocomplete-suggestions').forEach(el => el.remove()));

        input.addEventListener('input', function () {
            clearTimeout(timeout);
            $$('.eme-autocomplete-suggestions').forEach(el => el.remove());
            const value = this.value.trim();
            if (value.length < 2) return;

            timeout = setTimeout(() => {
                const searchAll = $('#eventsearch_all')?.checked ? 1 : 0;
                const formData = new FormData();
                formData.append('q', value);
                formData.append('exclude_id', $('#event_id')?.value || '');
                formData.append('only_rsvp', 1);
                formData.append('search_all', searchAll);
                formData.append('eme_admin_nonce', emersvp.translate_adminnonce);
                formData.append('action', 'eme_autocomplete_event');

                eme_postJSON(ajaxurl, formData, (data) => {
                    const suggestions = document.createElement('div');
                    suggestions.className = 'eme-autocomplete-suggestions';
                    data.forEach(item => {
                        const suggestion = document.createElement('div');
                        suggestion.className = 'eme-autocomplete-suggestion';
                        suggestion.innerHTML = `<strong>${eme_htmlDecode(item.eventinfo)}</strong>`;
                        suggestion.addEventListener('click', e => {
                            e.preventDefault();
                            if (item.event_id) {
                                $('input[name="transferto_id"]').value = eme_htmlDecode(item.event_id);
                                input.value = `${eme_htmlDecode(item.eventinfo)} `;
                                input.readOnly = true;
                                input.classList.add('clearable', 'x');
                            }
                        });
                        suggestions.appendChild(suggestion);
                    });
                    if (data.length === 0) {
                        const noMatch = document.createElement('div');
                        noMatch.className = 'eme-autocomplete-suggestion';
                        noMatch.textContent = emersvp.translate_nomatchevent;
                        suggestions.appendChild(noMatch);
                    }
                    input.insertAdjacentElement('afterend', suggestions);
                });
            }, 500);
        });

        input.addEventListener('change', () => {
            if (input.value === '') {
                $('input[name="transferto_id"]').value = '';
                input.readOnly = false;
                input.classList.remove('clearable', 'x');
            }
        });
    }
});
