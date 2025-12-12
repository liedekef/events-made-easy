document.addEventListener('DOMContentLoaded', function () {
    const BookingsTableContainer = EME.$('#BookingsTableContainer');
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
                columnResizable: false,
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
        const extraFieldSearchableAttr = BookingsTableContainer.dataset.extrafieldsearchable;
        if (extraFieldsAttr && extraFieldNamesAttr) {
            const extraFields = extraFieldsAttr.split(',');
            const extraNames = extraFieldNamesAttr.split(',');
            const extraSearches = extraFieldSearchableAttr.split(',');
            extraFields.forEach((value, index) => {
                if (value == 'SEPARATOR') {
                    let fieldindex = 'SEPARATOR_'+index;
                    bookingFields[fieldindex] = { title: extraNames[index], sorting: false, visibility: 'separator' };
                } else {
                    let fieldindex = 'FIELD_'+value;
                    bookingFields[fieldindex] = { title: extraNames[index], sorting: extraSearches[index]=='1', visibility: 'hidden' };
                }
            });
        }

        // Add edit link field if not trash
        if (eme_isFalsey($_GET['trash'])) {
            bookingFields.edit_link = {
                title: emersvp.translate_edit,
                sorting: false,
                visibility: 'fixed',
                columnResizable: false,
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
        }

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
            toolbar: {
                items: [
                    {
                        text: emersvp.translate_markpaidandapprove,
                        buttonClass: 'eme_ftable_button_for_pending_only',
                        click: function() {
                            const selectedRows = BookingsTable.getSelectedRows();
                            if (selectedRows.length === 0) return;

                            const ids = selectedRows.map(row => row.dataset.recordKey);
                            const idsjoined = ids.join(',');

                            const button = EME.$('.eme_ftable_button_for_pending_only .ftable-toolbar-item-text');
                            if (button) button.textContent = emersvp.translate_pleasewait;

                            fetch(ajaxurl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                    'booking_ids': idsjoined,
                                    'action': 'eme_manage_bookings',
                                    'do_action': 'markpaidandapprove',
                                    'eme_admin_nonce': emersvp.translate_adminnonce
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.Result !== 'OK') {
                                        const messageBox = document.getElementById('bookings-message');
                                        if (messageBox) {
                                            messageBox.innerHTML = data.htmlmessage;
                                            messageBox.style.display = 'block';
                                            setTimeout(() => messageBox.style.display = 'none', 5000);
                                        }
                                    }
                                    BookingsTable.reload();
                                })
                                .catch(error => {
                                    console.error('AJAX error:', error);
                                    BookingsTable.reload();
                                })
                                .finally(() => {
                                    if (button) button.textContent = emersvp.translate_markpaidandapprove;
                                });
                        }
                    },
                    {
                        text: emersvp.translate_markpaid,
                        buttonClass: 'eme_ftable_button_for_approved_only',
                        click: function() {
                            const selectedRows = BookingsTable.getSelectedRows();
                            if (selectedRows.length === 0) return;

                            const ids = selectedRows.map(row => row.dataset.recordKey);
                            const idsjoined = ids.join();

                            const button = EME.$('.eme_ftable_button_for_approved_only .ftable-toolbar-item-text');
                            if (button) button.textContent = emersvp.translate_pleasewait;

                            fetch(ajaxurl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                    'booking_ids': idsjoined,
                                    'action': 'eme_manage_bookings',
                                    'do_action': 'markPaid',
                                    'eme_admin_nonce': emersvp.translate_adminnonce
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.Result !== 'OK') {
                                        const messageBox = document.getElementById('bookings-message');
                                        if (messageBox) {
                                            messageBox.innerHTML = data.htmlmessage;
                                            messageBox.style.display = 'block';
                                            setTimeout(() => messageBox.style.display = 'none', 5000);
                                        }
                                    }
                                    BookingsTable.reload();
                                })
                                .catch(error => {
                                    console.error('AJAX error:', error);
                                    BookingsTable.reload();
                                })
                                .finally(() => {
                                    if (button) button.textContent = emersvp.translate_markpaid;
                                });
                        }
                    }
                ]
            },
            actions: { listAction: ajaxurl },
            listQueryParams: () => ({
                action: 'eme_bookings_list',
                eme_admin_nonce: emersvp.translate_adminnonce,
                trash: $_GET['trash'] || '',
                scope: eme_getValue(EME.$('#scope')),
                category: eme_getValue(EME.$('#category')),
                booking_status: eme_getValue(EME.$('#booking_status')),
                search_event: eme_getValue(EME.$('#search_event')),
                search_person: eme_getValue(EME.$('#search_person')),
                search_customfields: eme_getValue(EME.$('#search_customfields')),
                search_unique: eme_getValue(EME.$('#search_unique')),
                search_paymentid: eme_getValue(EME.$('#search_paymentid')),
                search_pg_pid: eme_getValue(EME.$('#search_pg_pid')),
                search_start_date: eme_getValue(EME.$('#search_start_date')),
                search_end_date: eme_getValue(EME.$('#search_end_date')),
                event_id: EME.$('#event_id')?.value || '',
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
        const action = EME.$('#eme_admin_action')?.value || '';

        eme_toggle(EME.$('#span_pdftemplate'), action === 'pdf');
        eme_toggle(EME.$('#span_htmltemplate'), action === 'html');
        eme_toggle(EME.$('span#span_sendtocontact'), action === 'resendApprovedBooking');
        eme_toggle(EME.$('#span_sendmails'), ['trashBooking','approveBooking','pendingBooking','unsetwaitinglistBooking','setwaitinglistBooking','markPaid','markUnpaid'].includes(action));
        eme_toggle(EME.$('span#span_refund'), ['trashBooking','pendingBooking','setwaitinglistBooking','markUnpaid'].includes(action) && eme_isFalsey($_GET['trash']));
        eme_toggle(EME.$('#span_addtogroup'), action === 'addToGroup');
        eme_toggle(EME.$('#span_removefromgroup'), action === 'removeFromGroup');
        eme_toggle(EME.$('#span_removefromgroup'), action === 'removeFromGroup');
        eme_toggle(EME.$('span#span_partialpayment'), action === 'partialPayment');
        eme_toggle(EME.$('span#span_rsvpmailtemplate'), action === 'rsvpMails');
    }

    EME.$('#eme_admin_action')?.addEventListener('change', updateShowHideStuff);
    updateShowHideStuff();

    // hide one toolbar button if not on pending approval and trash=0 (or not set)
    function showhideButtonPaidApprove() {
        const bookingStatus = EME.$('#booking_status');
        if (bookingStatus) {
            eme_toggle(EME.$('.eme_ftable_button_for_pending_only'), bookingStatus.value == "PENDING" && eme_isFalsey($_GET['trash']));
            eme_toggle(EME.$('.eme_ftable_button_for_approved_only'), bookingStatus.value == "APPROVED" && eme_isFalsey($_GET['trash']));
        }
    }
    showhideButtonPaidApprove();

    // --- Bulk Actions ---
    const actionsButton = EME.$('#BookingsActionsButton');
    if (actionsButton) {
        actionsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = BookingsTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;
            const sendMail = EME.$('#send_mail')?.value || 'no';

            if (selectedRows.length === 0 || !doAction) return;

            if (['trashBookings', 'deleteBookings'].includes(doAction) && !confirm(emersvp.translate_areyousuretodeleteselected)) return;
            if (doAction == 'partialPayment' && selectedRows.length > 1) {
                alert(emersvp.translate_selectonerowonlyforpartial);
                return;
            }

            actionsButton.textContent = emersvp.translate_pleasewait;
            actionsButton.disabled = true;

            const formData = new FormData();
            let idsJoined;
            if (doAction=='addToGroup' || doAction=='removeFromGroup') {
                const ids = selectedRows.map(row => row.recordData.person_id);
                idsJoined = ids.join(',');
                formData.append('person_id', idsJoined);
                formData.append('action', 'eme_manage_people');
                formData.append('do_action', doAction);
                formData.append('addtogroup', EME.$('#addtogroup')?.value);
                formData.append('removefromgroup', EME.$('#removefromgroup')?.value);
            } else { 
                const ids = selectedRows.map(row => row.dataset.recordKey);
                idsJoined = ids.join(',');
                formData.append('booking_ids', idsJoined);
                formData.append('action', 'eme_manage_bookings');
                formData.append('do_action', doAction);
                formData.append('send_mail', sendMail);
                formData.append('send_to_contact_too', EME.$('#send_to_contact_too')?.value);
                formData.append('refund', EME.$('#refund')?.value);
                formData.append('partial_amount', EME.$('#partial_amount')?.value);
                formData.append('rsvpmail_template', EME.$('#rsvpmail_template')?.value);
                formData.append('rsvpmail_template_subject', EME.$('#rsvpmail_template_subject')?.value);
                formData.append('pdf_template', EME.$('#pdf_template')?.value || '');
                formData.append('pdf_template_header', EME.$('#pdf_template_header')?.value || '');
                formData.append('pdf_template_footer', EME.$('#pdf_template_footer')?.value || '');
                formData.append('html_template', EME.$('#html_template')?.value || '');
                formData.append('html_template_header', EME.$('#html_template_header')?.value || '');
                formData.append('html_template_footer', EME.$('#html_template_footer')?.value || '');
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
                const msg = EME.$('#bookings-message');
                if (msg) {
                    msg.innerHTML = data.htmlmessage;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 5000);
                }
                BookingsTable.reload();
                actionsButton.textContent = emersvp.translate_apply;
                actionsButton.disabled = false;
            });
        });
    }

    // --- Reload Button ---
    const loadButton = EME.$('#BookingsLoadRecordsButton');
    if (loadButton) {
        loadButton.addEventListener('click', e => {
            e.preventDefault();
            BookingsTable.load();
        });
    }

    // --- Autocomplete: chooseevent ---
    const chooseevent = EME.$('input[name="chooseevent"]');
    if (chooseevent) {
        let timeout;
        document.addEventListener('click', () => EME.$$('.eme-autocomplete-suggestions').forEach(el => el.remove()));

        chooseevent.addEventListener('input', function () {
            clearTimeout(timeout);
            EME.$$('.eme-autocomplete-suggestions').forEach(el => el.remove());
            const value = this.value.trim();
            if (value.length < 2) return;

            timeout = setTimeout(() => {
                const searchAll = EME.$('#eventsearch_all')?.checked ? 1 : 0;
                const formData = new FormData();
                formData.append('q', value);
                formData.append('exclude_id', EME.$('#event_id')?.value || '');
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
                                EME.$('input[name="transferto_id"]').value = eme_htmlDecode(item.event_id);
                                chooseevent.value = `${eme_htmlDecode(item.eventinfo)} `;
                                chooseevent.readOnly = true;
                                chooseevent.classList.add('clearable', 'x');
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
                    chooseevent.insertAdjacentElement('afterend', suggestions);
                });
            }, 500);
        });

        chooseevent.addEventListener('change', () => {
            if (chooseevent.value === '') {
                EME.$('input[name="transferto_id"]').value = '';
                chooseevent.readOnly = false;
                chooseevent.classList.remove('clearable', 'x');
            }
        });
    }
});
