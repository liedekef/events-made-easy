document.addEventListener('DOMContentLoaded', function () {
    const BookingsTableContainer = EME.$('#BookingsTableContainer');
    let BookingsTable;

    function eme_rsvp_bulk_extra_data() {
        return {
            action: 'eme_manage_bookings',
            lang: emeadmin.translate_locale,
            send_mail: EME.$('#send_mail')?.value || 'no',
            send_to_contact_too: EME.$('#send_to_contact_too')?.value || '',
            refund: EME.$('#refund')?.value || '',
            partial_amount: EME.$('#partial_amount')?.value || '',
            rsvpmail_template: EME.$('#rsvpmail_template')?.value || '',
            rsvpmail_template_subject: EME.$('#rsvpmail_template_subject')?.value || '',
            pdf_template: EME.$('#pdf_template')?.value || '',
            pdf_template_header: EME.$('#pdf_template_header')?.value || '',
            pdf_template_footer: EME.$('#pdf_template_footer')?.value || '',
            html_template: EME.$('#html_template')?.value || '',
            html_template_header: EME.$('#html_template_header')?.value || '',
            html_template_footer: EME.$('#html_template_footer')?.value || '',
            eme_admin_nonce: emeadmin.translate_adminnonce
        };
    }

    // addToGroup / removeFromGroup post person ids to the people handler, so they
    // can't use the booking-oriented default bulkActions flow.
    async function eme_rsvp_bulk_people_action(doAction, selectedRows, table) {
        const personIds = selectedRows.map(row => row.recordData.person_id).join(',');
        const result = await FTableHttpClient.post(ajaxurl, {
            action: 'eme_manage_people',
            person_id: personIds,
            do_action: doAction,
            addtogroup: EME.$('#addtogroup')?.value || '',
            removefromgroup: EME.$('#removefromgroup')?.value || '',
            eme_admin_nonce: emeadmin.translate_adminnonce
        });
        table.clearListCache();
        table.reload();
        eme_show_ftable_bulk_result(table, result);
    }

    // --- Initialize Bookings Table ---
    if (BookingsTableContainer) {
        let bookingFields = {
            booking_id: {
                key: true,
                width: '1%',
                columnResizable: false,
                list: false,
            },
            event_name: {
                title: emeadmin.translate_eventinfo
            },
            event_id: {
                title: emeadmin.translate_event_id,
                sorting: false,
                visibility: 'hidden'
            },
            person_id: {
                title: emeadmin.translate_person_id,
                sorting: false,
                width: '1%',
                columnResizable: false,
                visibility: 'hidden'
            },
            rsvp: {
                title: emeadmin.translate_rsvp,
                sorting: false,
                width: '2%',
                columnResizable: false,
                listClass: 'eme-ftable-center'
            },
            event_start: {
                title: emeadmin.translate_eventstart,
            },
            booker: {
                title: emeadmin.translate_booker
            },
            creation_date: {
                title: emeadmin.translate_bookingdate
            },
            seats: {
                title: emeadmin.translate_seats,
                sorting: false,
                listClass: 'eme-ftable-center'
            },
            eventprice: {
                title: emeadmin.translate_eventprice,
                sorting: false
            },
            event_cats: {
                title: emeadmin.translate_event_cats,
                sorting: false,
                visibility: 'hidden'
            },
            discount: {
                title: emeadmin.translate_discount,
                sorting: false,
                visibility: 'hidden'
            },
            dcodes_used: {
                title: emeadmin.translate_dcodes_used,
                sorting: false,
                visibility: 'hidden'
            },
            totalprice: {
                title: emeadmin.translate_totalprice,
                sorting: false
            },
            unique_nbr: {
                title: emeadmin.translate_uniquenbr,
                visibility: 'hidden'
            },
            booking_paid: {
                title: emeadmin.translate_paid,
                visibility: 'hidden'
            },
            remaining: {
                title: emeadmin.translate_remaining,
                sorting: false,
                visibility: 'hidden'
            },
            received: {
                title: emeadmin.translate_received,
                sorting: false,
                visibility: 'hidden'
            },
            payment_date: {
                title: emeadmin.translate_paymentdate,
                visibility: 'hidden'
            },
            pg: {
                title: emeadmin.translate_pg,
                visibility: 'hidden'
            },
            pg_pid: {
                title: emeadmin.translate_pg_pid,
                visibility: 'hidden'
            },
            payment_id: {
                title: emeadmin.translate_paymentid
            },
            attend_count: {
                title: emeadmin.translate_attend_count,
                visibility: 'hidden'
            },
            lastreminder: {
                title: emeadmin.translate_lastreminder,
                sorting: false,
                visibility: 'hidden'
            },
            booking_comment: {
                title: emeadmin.translate_comment,
                sorting: false,
                visibility: 'hidden'
            },
            wp_user: {
                title: emeadmin.translate_wpuser,
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
                title: emeadmin.translate_edit,
                sorting: false,
                visibility: 'fixed',
                columnResizable: false,
                width: '1%',
                listClass: 'ftable-command-column eme-ftable-center',
                value: record => {
                    const a = document.createElement('a');
                    a.href = record.edit_link_url;
                    a.textContent = emeadmin.translate_edit;
                    a.className = 'button';
                    return a;
                }
            };
        }

        BookingsTable = new FTable('#BookingsTableContainer', {
            title: emeadmin.translate_bookings,
            paging: true,
            sorting: true,
            sortingResetButton: true,
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
                        text: emeadmin.translate_markpaidandapprove,
                        buttonClass: 'eme_ftable_button_for_pending_only',
                        click: function() {
                            const selectedRows = BookingsTable.getSelectedRows();
                            if (selectedRows.length === 0) return;

                            const ids = selectedRows.map(row => row.dataset.recordKey);
                            const idsjoined = ids.join(',');

                            const button = EME.$('.eme_ftable_button_for_pending_only .ftable-toolbar-item-text');
                            if (button) button.textContent = emeadmin.translate_pleasewait;

                            fetch(ajaxurl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                    'booking_ids': idsjoined,
                                    'action': 'eme_manage_bookings',
                                    'do_action': 'markpaidandapprove',
                                    'eme_admin_nonce': emeadmin.translate_adminnonce,
                                    'lang': emeadmin.translate_locale
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    eme_show_ftable_bulk_result(BookingsTable, data);
                                    BookingsTable.reload();
                                })
                                .catch(error => {
                                    console.error('AJAX error:', error);
                                    BookingsTable.reload();
                                })
                                .finally(() => {
                                    if (button) button.textContent = emeadmin.translate_markpaidandapprove;
                                });
                        }
                    },
                    {
                        text: emeadmin.translate_markpaid,
                        buttonClass: 'eme_ftable_button_for_approved_only',
                        click: function() {
                            const selectedRows = BookingsTable.getSelectedRows();
                            if (selectedRows.length === 0) return;

                            const ids = selectedRows.map(row => row.dataset.recordKey);
                            const idsjoined = ids.join();

                            const button = EME.$('.eme_ftable_button_for_approved_only .ftable-toolbar-item-text');
                            if (button) button.textContent = emeadmin.translate_pleasewait;

                            fetch(ajaxurl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                    'booking_ids': idsjoined,
                                    'action': 'eme_manage_bookings',
                                    'do_action': 'markPaid',
                                    'eme_admin_nonce': emeadmin.translate_adminnonce,
                                    'lang': emeadmin.translate_locale
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    eme_show_ftable_bulk_result(BookingsTable, data);
                                    BookingsTable.reload();
                                })
                                .catch(error => {
                                    console.error('AJAX error:', error);
                                    BookingsTable.reload();
                                })
                                .finally(() => {
                                    if (button) button.textContent = emeadmin.translate_markpaid;
                                });
                        }
                    }
                ]
            },
            actions: { listAction: ajaxurl },
            listQueryParams: () => ({
                action: 'eme_bookings_list',
                eme_admin_nonce: emeadmin.translate_adminnonce,
                lang: emeadmin.translate_locale,
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
                search_start_date: eme_getValue(EME.$('[name=search_start_date]')),
                search_end_date: eme_getValue(EME.$('[name=search_end_date]')),
                event_id: EME.$('#event_id')?.value || '',
                person_id: $_GET['person_id']
            }),
            fields: bookingFields,
            bulkActions: {
                select: '#eme_admin_action_rsvp',
                button: '#BookingsActionsButton',
                idField: 'booking_ids',
                action: ajaxurl,
                confirmActions: ['trashBooking', 'deleteBooking'],
                confirmTitle: emeadmin.translate_confirmdelete,
                confirmMessage: emeadmin.translate_areyousuretodeleteselected,
                extraData: eme_rsvp_bulk_extra_data,
                handlers: {
                    sendMails: ({ ids }) => eme_submit_hidden_form(emeadmin.translate_admin_sendmails_url, {
                        booking_ids: ids.join(','),
                        eme_admin_action: 'new_mailing'
                    }),
                    pdf: ({ doAction, ids }) => eme_submit_hidden_form(ajaxurl, {
                        booking_ids: ids.join(','),
                        do_action: doAction,
                        ...eme_rsvp_bulk_extra_data()
                    }),
                    html: ({ doAction, ids }) => eme_submit_hidden_form(ajaxurl, {
                        booking_ids: ids.join(','),
                        do_action: doAction,
                        ...eme_rsvp_bulk_extra_data()
                    }),
                    addToGroup: ({ doAction, selectedRows, table }) => eme_rsvp_bulk_people_action(doAction, selectedRows, table),
                    removeFromGroup: ({ doAction, selectedRows, table }) => eme_rsvp_bulk_people_action(doAction, selectedRows, table),
                    partialPayment: async ({ doAction, ids, selectedRows, table }) => {
                        if (selectedRows.length > 1) {
                            alert(emeadmin.translate_selectonerowonlyforpartial);
                            return;
                        }
                        const result = await FTableHttpClient.post(ajaxurl, {
                            booking_ids: ids.join(','),
                            do_action: doAction,
                            ...eme_rsvp_bulk_extra_data()
                        });
                        table.clearListCache();
                        table.reload();
                        eme_show_ftable_bulk_result(table, result);
                    }
                }
            },
            bulkActionComplete: ({ data }) => {
                eme_show_ftable_bulk_result(BookingsTable, data);
            },
            bulkActionError: ({ data }) => {
                BookingsTable.showError(emeadmin.translate_problem);
            }
        });

        BookingsTable.load();
    }

    // --- Conditional UI for Actions ---
    function updateShowHideStuff() {
        const action = EME.$('#eme_admin_action_rsvp')?.value || '';

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

    EME.$('#eme_admin_action_rsvp')?.addEventListener('change', updateShowHideStuff);
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

    // --- Reload Button ---
    const loadButton = EME.$('#BookingsLoadRecordsButton');
    if (loadButton) {
        loadButton.addEventListener('click', e => {
            e.preventDefault();
            BookingsTable.load();
        });
    }

    initSnapSelectRemote('select.eme_snapselect_events_class', {
        showClearButton: true,
        data: function(search, page) {
            return {
                exclude_id: this.dataset.exclude_event_id || '',
                only_rsvpable_events: 1,
                action: 'eme_events_snapselect',
                search_all: EME.$('#eventsearch_all')?.checked ? 1 : 0,
                eme_admin_nonce: emeadmin.translate_adminnonce
            };
        }
    });
    initSnapSelectRemote('select.eme_snapselect_transfer_to_person_class', {
        showClearButton: true,
        cache: true,
        data: function(search, page) {
            return {
                action:            'eme_chooseperson_snapselect',
                eme_admin_nonce:   emeadmin.translate_adminnonce,
                exclude_personids: this.dataset.personId || '',
            };
        }
    });

    // --- Transfer booking: only show the dedicated "transfer" button once a target is chosen, ---
    // --- and keep the two transfer methods (to event / to person) mutually exclusive.          ---
    const transferEventSelect  = EME.$('select[name="transferto_id"]');
    const transferPersonSelect = EME.$('select[name="transferto_person_id"]');
    const transferEventButton  = EME.$('#transfer_to_event_button');
    const transferPersonButton = EME.$('#transfer_to_person_button');

    if (transferEventSelect && transferPersonSelect && transferEventButton && transferPersonButton) {
        const clearSnapSelect = (selectEl) => {
            if (selectEl.snapselectInstance) {
                selectEl.snapselectInstance.clear();
            } else {
                selectEl.value = '';
            }
        };

        const toggleTransferButtons = () => {
            transferEventButton.style.display  = transferEventSelect.value  ? '' : 'none';
            transferPersonButton.style.display = transferPersonSelect.value ? '' : 'none';
        };

        document.addEventListener('change', (e) => {
            if (e.target === transferEventSelect) {
                if (transferEventSelect.value && transferPersonSelect.value) {
                    clearSnapSelect(transferPersonSelect);
                }
                toggleTransferButtons();
            } else if (e.target === transferPersonSelect) {
                if (transferPersonSelect.value && transferEventSelect.value) {
                    clearSnapSelect(transferEventSelect);
                }
                toggleTransferButtons();
            }
        });

        toggleTransferButtons();
    }
});
