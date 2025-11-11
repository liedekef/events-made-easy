document.addEventListener('DOMContentLoaded', function () {
    const MailingReportTableContainer = EME.$('#MailingReportTableContainer');
    let MailingReportTable;
    const MailsTableContainer = EME.$('#MailsTableContainer');
    let MailsTable;
    const MailingsTableContainer = EME.$('#MailingsTableContainer');
    let MailingsTable;
    const ArchivedMailingsTableContainer = EME.$('#ArchivedMailingsTableContainer');
    let ArchivedMailingsTable;

    // --- Autocomplete: chooseperson ---
    function setupAutocomplete(inputSelector, hiddenIdSelector, noMatchText) {
        const input = EME.$(inputSelector);
        if (!input) return;

        let timeout;
        input.addEventListener('input', function () {
            clearTimeout(timeout);
            EME.$$('.eme-autocomplete-suggestions').forEach(s => s.remove());
            const value = this.value;
            if (value.length >= 2) {
                timeout = setTimeout(() => {
                    const formData = new FormData();
                    formData.append('lastname', value);
                    formData.append('eme_admin_nonce', ememails.translate_adminnonce);
                    formData.append('action', 'eme_autocomplete_people');
                    formData.append('eme_searchlimit', 'people');

                    fetch(ajaxurl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        const suggestions = document.createElement('div');
                        suggestions.className = 'eme-autocomplete-suggestions';
                        if (data.length) {
                            data.forEach(item => {
                                const suggestion = document.createElement('div');
                                suggestion.className = 'eme-autocomplete-suggestion';
                                suggestion.innerHTML = `
                                    <strong>${eme_htmlDecode(item.lastname)} ${eme_htmlDecode(item.firstname)}</strong>
                                    <br><small>${eme_htmlDecode(item.email)}</small>
                                `;
                                suggestion.addEventListener('click', e => {
                                    e.preventDefault();
                                    if (item.person_id) {
                                        EME.$(hiddenIdSelector).value = eme_htmlDecode(item.person_id);
                                        input.value = `${eme_htmlDecode(item.lastname)} ${eme_htmlDecode(item.firstname)}  `;
                                        input.setAttribute('readonly', true);
                                        input.classList.add('clearable', 'x');
                                    }
                                });
                                suggestions.appendChild(suggestion);
                            });
                        } else {
                            const noMatch = document.createElement('div');
                            noMatch.className = 'eme-autocomplete-suggestion';
                            noMatch.innerHTML = `<strong>${noMatchText}</strong>`;
                            suggestions.appendChild(noMatch);
                        }
                        input.after(suggestions);
                    });
                }, 500);
            }
        });

        document.addEventListener('click', () => {
            EME.$$('.eme-autocomplete-suggestions').forEach(s => s.remove());
        });

        input.addEventListener('keyup', () => {
            EME.$(hiddenIdSelector).value = '';
        });

        input.addEventListener('change', () => {
            if (input.value === '') {
                EME.$(hiddenIdSelector).value = '';
                input.removeAttribute('readonly');
                input.classList.remove('clearable', 'x');
            }
        });
    }

    setupAutocomplete('input[name="chooseperson"]', 'input[name="send_previewmailto_id"]', ememails.translate_nomatchperson);
    setupAutocomplete('input[name="eventmail_chooseperson"]', 'input[name="send_previeweventmailto_id"]', ememails.translate_nomatchperson);

    // --- Mail Form Submission Handler ---
    function ajaxMailButtonHandler(buttonSelector, action, editorTarget, messageDivSelector, resetSelectors = [], extraReset = null) {
        const button = EME.$(buttonSelector);
        if (!button) return;

        button.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.form;

            // the tinymce save needs to happen before the FormData-call, as it changes the form content
            if (ememails.translate_htmleditor === 'tinymce' && ememails.translate_htmlmail === 'yes') {
                if (typeof tinymce !== 'undefined' && tinymce.get(editorTarget)) {
                    tinymce.get(editorTarget).save();
                }
            }

            const formData = new FormData(form);
            formData.append('action', action);
            formData.append('eme_admin_nonce', ememails.translate_adminnonce);

            button.textContent = ememails.translate_pleasewait;
            button.disabled = true;

            eme_postJSON(ajaxurl, formData, (data) => {
                const messageDiv = EME.$(messageDivSelector);
                messageDiv.innerHTML = data.htmlmessage;
                eme_toggle(messageDiv, true);

                if (data.Result === 'OK') {
                    form.reset();
                    resetSelectors.forEach(sel => {
                        const el = EME.$(sel);
                        if (el && el.tomselect) {
                            el.tomselect.clear();
                        }
                    });
                    if (typeof extraReset === 'function') extraReset();
                    setTimeout(() => { eme_toggle(messageDiv, false); }, 5000);
                }

                button.textContent = ememails.translate_sendmail;
                button.disabled = false;
            });
        });
    }

    ajaxMailButtonHandler(
        '#eventmailButton',
        'eme_eventmail',
        'event_mail_message',
        '#eventmail-message',
        [
            '#event_ids',
            "#eme_eventmail_send_persons",
            "#eme_eventmail_send_groups",
            "#eme_eventmail_send_members",
            "#eme_eventmail_send_membergroups",
            "#eme_eventmail_send_memberships",
            "#eme_mail_type"
        ]
    );

    ajaxMailButtonHandler(
        '#genericmailButton',
        'eme_genericmail',
        'generic_mail_message',
        '#genericmail-message',
        [
            "#eme_genericmail_send_persons",
            "#eme_genericmail_send_peoplegroups",
            "#eme_genericmail_send_members",
            "#eme_genericmail_send_membergroups",
            "#eme_send_memberships"
        ],
        function () {
            EME.$('#eme_send_all_people').dispatchEvent(new Event('change'));
        }
    );

    // --- Mail Preview Handler ---
    function mailPreviewHandler(buttonSelector, action, messageDivSelector, inputSelectors) {
        const button = EME.$(buttonSelector);
        if (!button) return;

        button.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.form;
 
            // the tinymce save needs to happen before the FormData-call, as it changes the form content
            if (ememails.translate_htmleditor === 'tinymce' && ememails.translate_htmlmail === 'yes') {
                const editorField = form.querySelector('textarea');
                if (editorField && tinymce.get(editorField.id)) {
                    tinymce.get(editorField.id).save();
                }
            }

            const formData = new FormData(form);
            formData.append('action', action);
            formData.append('eme_admin_nonce', ememails.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                const messageDiv = EME.$(messageDivSelector);
                messageDiv.innerHTML = data.htmlmessage;
                eme_toggle(messageDiv, true);
                setTimeout(() => { eme_toggle(messageDiv, false); }, 5000);

                if (data.Result === 'OK') {
                    inputSelectors.forEach(sel => {
                        const el = EME.$(sel);
                        if (el) {
                            el.value = '';
                            if (sel.includes('chooseperson')) {
                                el.removeAttribute('readonly');
                                el.classList.remove('clearable', 'x');
                            }
                        }
                    });
                }
            });
        });
    }

    mailPreviewHandler('#previeweventmailButton', 'eme_previeweventmail', '#previeweventmail-result', [
        'input[name="eventmail_chooseperson"]',
        'input[name="send_previeweventmailto_id"]'
    ]);

    mailPreviewHandler('#previewmailButton', 'eme_previewmail', '#previewmail-result', [
        'input[name="chooseperson"]',
        'input[name="send_previewmailto_id"]'
    ]);

    // --- Test Mail Handler ---
    const testmailButton = EME.$('#testmailButton');
    if (testmailButton) {
        testmailButton.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.form;
            const formData = new FormData(form);
            formData.append('action', 'eme_testmail');
            formData.append('eme_admin_nonce', ememails.translate_adminnonce);

            testmailButton.textContent = ememails.translate_pleasewait;
            testmailButton.disabled = true;

            eme_postJSON(ajaxurl, formData, (data) => {
                const msg = EME.$('#testmail-message');
                msg.innerHTML = data.htmlmessage;
                eme_toggle(msg, true);
                if (data.Result === 'OK') form.reset();
                testmailButton.textContent = ememails.translate_sendmail;
                testmailButton.disabled = false;
            });
        });
    }

    // --- Template Select Handler ---
    function setEditorContent(targetSelector, editorType, editorTarget, value) {
        const textarea = EME.$(targetSelector);
        if (!textarea) return;
        textarea.value = value;

        switch (editorType) {
            case 'tinymce':
                if (typeof tinymce !== 'undefined' && tinymce.get(editorTarget)) {
                    tinymce.get(editorTarget).setContent(value);
                }
                break;
            case 'jodit':
                if (typeof Jodit !== 'undefined' && Jodit.instances[editorTarget]) {
                    Jodit.instances[editorTarget].value = value;
                }
                break;
        }
    }

    function templateSelectHandler({ selectSelector, targetSelector, editorType, editorTarget }) {
        const select = EME.$(selectSelector);
        if (!select) return;

        select.addEventListener('change', function () {
            const formData = new FormData();
            formData.append('action', 'eme_get_template');
            formData.append('eme_admin_nonce', ememails.translate_adminnonce);
            formData.append('template_id', select.value);

            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    setEditorContent(targetSelector, editorType, editorTarget, data.htmlmessage);
                });
        });
    }

    templateSelectHandler({
        selectSelector: 'select#event_subject_template',
        targetSelector: 'input#event_mail_subject'
    });

    templateSelectHandler({
        selectSelector: 'select#event_message_template',
        targetSelector: 'textarea#event_mail_message',
        editorType: ememails.translate_htmlmail === 'yes' ? ememails.translate_htmleditor : undefined,
        editorTarget: ememails.translate_htmleditor === 'tinymce' ? 'event_mail_message' :
                      ememails.translate_htmleditor === 'jodit' ? 'joditdiv_event_mail_message' : undefined
    });

    templateSelectHandler({
        selectSelector: 'select#generic_message_template',
        targetSelector: 'textarea#generic_mail_message',
        editorType: ememails.translate_htmlmail === 'yes' ? ememails.translate_htmleditor : undefined,
        editorTarget: ememails.translate_htmleditor === 'tinymce' ? 'generic_mail_message' :
                      ememails.translate_htmleditor === 'jodit' ? 'joditdiv_generic_mail_message' : undefined
    });

    // --- Show/Hide Groups ---
    function updateShowSendGroups() {
        const checked = EME.$('#eme_send_all_people').checked;
        eme_toggle(EME.$('#div_eme_send_groups'), !checked);
        eme_toggle(EME.$('#div_eme_send_all_people'), checked);
    }

    const sendAllPeople = EME.$('#eme_send_all_people');
    if (sendAllPeople) {
        sendAllPeople.addEventListener('change', updateShowSendGroups);
        updateShowSendGroups();
    }

    // --- Show/Hide Mail Types ---
    function updateShowMailTypes() {
        const mailType = EME.$('select[name="eme_mail_type"]').value;
        const isAttendeesOrBookings = ['attendees', 'bookings'].includes(mailType);
        const isPeopleAndGroups = mailType === 'people_and_groups';

        eme_toggle(EME.$('#eme_pending_approved_row'), isAttendeesOrBookings);
        eme_toggle(EME.$('#eme_only_unpaid_row'), isAttendeesOrBookings);
        eme_toggle(EME.$('#eme_exclude_registered_row'), mailType !== '');
        eme_toggle(EME.$('#eme_rsvp_status_row'), isAttendeesOrBookings);

        EME.$$('span[id^="span_unpaid_"]').forEach(el => eme_toggle(el, false));
        if (mailType === 'attendees') eme_toggle(EME.$('#span_unpaid_attendees'),true);
        if (mailType === 'bookings') eme_toggle(EME.$('#span_unpaid_bookings'),true);

        const showPeopleRows = isPeopleAndGroups;
        EME.$$('tr[id^="eme_people_row"], tr[id^="eme_groups_row"], tr[id^="eme_members_row"]').forEach(el => {
            eme_toggle(el, showPeopleRows);
        });
    }

    const mailTypeSelect = EME.$('select[name="eme_mail_type"]');
    if (mailTypeSelect) {
        mailTypeSelect.addEventListener('change', updateShowMailTypes);
        updateShowMailTypes();
    }

    eme_admin_init_attachment_ui('#eventmail_attach_button', '#eventmail_attach_links', '#eme_eventmail_attach_ids', '#eventmail_remove_attach_button');
    eme_admin_init_attachment_ui('#generic_attach_button', '#generic_attach_links', '#eme_generic_attach_ids', '#generic_remove_attach_button');

    // --- ftable: Mailing Report Table ---
    if (MailingReportTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'mailingreporttablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        MailingReportTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        MailingReportTable = new FTable('#MailingReportTableContainer', {
            title: ememails.translate_mailingreport,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'sent_datetime ASC',
            actions: {
                listAction: ajaxurl,
            },
            listQueryParams: () => ({
                action: "eme_mailingreport_list",
                eme_admin_nonce: ememails.translate_adminnonce,
                mailing_id: parseInt($_GET['id']),
                search_name: EME.$('#search_name')?.value || ''
            }),
            fields: {
                receiveremail: { title: ememails.translate_email, visibility: 'hidden' },
                receivername: { title: ememails.translate_name },
                status: { title: ememails.translate_status },
                sent_datetime: { title: ememails.translate_sentdatetime },
                first_read_on: { title: ememails.translate_first_read_on, visibility: 'hidden' },
                last_read_on: { title: ememails.translate_last_read_on, visibility: 'hidden' },
                read_count: { title: ememails.translate_total_readcount },
                error_msg: { title: ememails.translate_errormessage, visibility: 'hidden', sorting: false },
                action: { title: ememails.translate_action, listClass: 'eme-wsnobreak', visibility: 'fixed', sorting: false }
            },
            sortingInfoSelector: '#mailingreporttablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        MailingReportTable.load();
        // --- Reload Button ---
        const loadRecordsButton = EME.$('#ReportLoadRecordsButton');
        if (loadRecordsButton) {
            loadRecordsButton.addEventListener('click', function (e) {
                e.preventDefault();
                MailingReportTable.load();
            });
        }

    }

    // --- ftable: Mails Table ---
    if (MailsTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'mailstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        MailsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        MailsTable = new FTable('#MailsTableContainer', {
            title: ememails.translate_mails,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: "creation_date DESC",
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl,
            },
            listQueryParams: () => ({
                action: "eme_mails_list",
                search_text: EME.$('#search_text')?.value || '',
                search_failed: EME.$('#search_failed')?.checked ? 1 : 0,
                eme_admin_nonce: ememails.translate_adminnonce
            }),
            fields: {
                id: { key: true, width: '1%', columnResizable: false, list: false, title: ememails.translate_id },
                fromemail: { title: ememails.translate_senderemail, visibility: 'hidden' },
                fromname: { title: ememails.translate_sendername },
                receiveremail: { title: ememails.translate_recipientemail },
                receivername: { title: ememails.translate_recipientname },
                subject: { title: ememails.translate_subject },
                status: { title: ememails.translate_status },
                creation_date: { title: ememails.translate_queueddatetime },
                sent_datetime: { title: ememails.translate_sentdatetime },
                first_read_on: { title: ememails.translate_first_read_on, visibility: 'hidden' },
                last_read_on: { title: ememails.translate_last_read_on, visibility: 'hidden' },
                read_count: { title: ememails.translate_total_readcount, visibility: 'hidden' },
                error_msg: { title: ememails.translate_errormessage, visibility: 'hidden', sorting: false },
                action: { title: ememails.translate_action, listClass: 'eme-wsnobreak', visibility: 'fixed', sorting: false }
            },
            sortingInfoSelector: '#mailstablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        //MailsTable.load();
        // --- Reload Button ---
        const loadRecordsButton = EME.$('#MailsLoadRecordsButton');
        if (loadRecordsButton) {
            loadRecordsButton.addEventListener('click', function (e) {
                e.preventDefault();
                MailsTable.load();
            });
        }

        EME.$('#MailsActionsButton')?.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = MailsTable.getSelectedRows();
            const do_action = EME.$('#eme_admin_action_mails').value;
            if (!selectedRows.length || !do_action) return;

            let action_ok = 1;
            if (do_action === 'deleteMails' && !confirm(ememails.translate_areyousuretodeleteselected)) {
                action_ok = 0;
            }

            if (action_ok === 1) {
                this.textContent = ememails.translate_pleasewait;
                this.disabled = true;

                const ids = selectedRows.map(row => row.dataset.recordKey);
                const personids = selectedRows.map(row => row.dataset.record?.person_id);
                const idsjoined = ids.join();

                if (do_action === 'sendMails') {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = ememails.translate_admin_sendmails_url;
                    ['person_ids', 'eme_admin_action'].forEach(key => {
                        const val = key === 'person_ids' ? personids.join() : 'new_mailing';
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

                const formData = new FormData();
                formData.append('mail_ids', idsjoined);
                formData.append('action', 'eme_manage_mails');
                formData.append('do_action', do_action);
                formData.append('eme_admin_nonce', ememails.translate_adminnonce);

                eme_postJSON(ajaxurl, formData, (data) => {
                    MailsTable.reload();
                    this.textContent = ememails.translate_apply;
                    this.disabled = false;
                    const msg = EME.$('#mails-message');
                    msg.innerHTML = data.htmlmessage;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                });
            }
        });
    }

    // --- ftable: Mailings Table ---
    if (MailingsTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'mailingstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        MailingsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        MailingsTable = new FTable('#MailingsTableContainer', {
            title: ememails.translate_mailings,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'planned_on DESC, name',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl,
            },
            listQueryParams: () => ({
                action: "eme_mailings_list",
                search_text: EME.$('#search_mailingstext')?.value || '',
                eme_admin_nonce: ememails.translate_adminnonce
            }),
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    list: false,
                },
                name: {
                    title: ememails.translate_mailingname,
                },
                subject: {
                    title: ememails.translate_subject,
                },
                planned_on: {
                    title: ememails.translate_planneddatetime,
                },
                creation_date: {
                    title: ememails.translate_queueddatetime,
                    visibility: 'hidden',
                },
                status: {
                    title: ememails.translate_status,
                },
                read_count: {
                    title: ememails.translate_unique_readcount,
                    visibility: 'hidden',
                },
                total_read_count: {
                    title: ememails.translate_total_readcount,
                },
                extra_info: {
                    title: ememails.translate_extrainfo,
                    sorting: false
                },
                report: {
                    title: ememails.translate_report,
                    sorting: false
                },
                action: {
                    title: ememails.translate_action,
                    listClass: 'eme-wsnobreak',
                    visibility: 'fixed',
                    columnResizable: false,
                    sorting: false
                }
            },
            sortingInfoSelector: '#mailingstablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        //MailingsTable.load();
        // --- Reload Button ---
        const loadRecordsButton = EME.$('#MailingsLoadRecordsButton');
        if (loadRecordsButton) {
            loadRecordsButton.addEventListener('click', function (e) {
                e.preventDefault();
                MailingsTable.load();
            });
        }
        EME.$('#MailingsActionsButton')?.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = MailingsTable.getSelectedRows();
            const do_action = EME.$('#eme_admin_action_mailings').value;
            if (!selectedRows.length || !do_action) return;

            let action_ok = 1;
            if (do_action === 'deleteMailings' && !confirm(ememails.translate_areyousuretodeleteselected)) {
                action_ok = 0;
            }

            if (action_ok === 1) {
                this.textContent = ememails.translate_pleasewait;
                this.disabled = true;

                const ids = selectedRows.map(row => row.dataset.recordKey);
                const idsjoined = ids.join();

                const formData = new FormData();
                formData.append('mailing_ids', idsjoined);
                formData.append('action', 'eme_manage_mailings');
                formData.append('do_action', do_action);
                formData.append('eme_admin_nonce', ememails.translate_adminnonce);

                eme_postJSON(ajaxurl, formData, (data) => {
                    MailingsTable.reload();
                    this.textContent = ememails.translate_apply;
                    this.disabled = false;
                    const msg = EME.$('#mailings-message');
                    msg.innerHTML = data.htmlmessage;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 5000);
                });
            }
        });
    }

    // --- ftable: Archived Mailings Table ---
    if (ArchivedMailingsTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'archivedmailingstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        ArchivedMailingsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        ArchivedMailingsTable = new FTable('#ArchivedMailingsTableContainer', {
            title: ememails.translate_archivedmailings,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'planned_on DESC, name',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl,
            },
            listQueryParams: () => ({
                action: "eme_archivedmailings_list",
                search_text: EME.$('#search_archivedmailingstext')?.value || '',
                eme_admin_nonce: ememails.translate_adminnonce
            }),
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    list: false,
                },
                name: {
                    title: ememails.translate_mailingname,
                },
                subject: {
                    title: ememails.translate_subject,
                },
                planned_on: {
                    title: ememails.translate_planneddatetime,
                },
                read_count: {
                    title: ememails.translate_unique_readcount,
                    visibility: 'hidden',
                },
                total_read_count: {
                    title: ememails.translate_total_readcount,
                },
                extra_info: {
                    title: ememails.translate_extrainfo,
                    sorting: false
                },
                action: {
                    title: ememails.translate_action,
                    listClass: 'eme-wsnobreak',
                    visibility: 'fixed',
                    columnResizable: false,
                    sorting: false
                }
            },
            sortingInfoSelector: '#archivedmailingstablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        //ArchivedMailingsTable.load();
        // --- Reload Button ---
        const loadRecordsButton = EME.$('#ArchivedMailingsLoadRecordsButton');
        if (loadRecordsButton) {
            loadRecordsButton.addEventListener('click', function (e) {
                e.preventDefault();
                ArchivedMailingsTable.load();
            });
        }

        EME.$('#ArchivedMailingsActionsButton')?.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = ArchivedMailingsTable.getSelectedRows();
            const do_action = EME.$('#eme_admin_action_archivedmailings').value;
            if (!selectedRows.length || !do_action) return;

            let action_ok = 1;
            if (do_action === 'deleteArchivedMailings' && !confirm(ememails.translate_areyousuretodeleteselected)) {
                action_ok = 0;
            }

            if (action_ok === 1) {
                this.textContent = ememails.translate_pleasewait;
                this.disabled = true;

                const ids = selectedRows.map(row => row.dataset.recordKey);
                const idsjoined = ids.join();

                const formData = new FormData();
                formData.append('mailing_ids', idsjoined);
                formData.append('action', 'eme_manage_archivedmailings');
                formData.append('do_action', do_action);
                formData.append('eme_admin_nonce', ememails.translate_adminnonce);

                eme_postJSON(ajaxurl, formData, (data) => {
                    ArchivedMailingsTable.reload();
                    this.textContent = ememails.translate_apply;
                    this.disabled = false;
                    const msg = EME.$('#archivedmailings-message');
                    msg.innerHTML = data.htmlmessage;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 5000);
                });
            }
        });
    }

    initTomSelectRemote('select.eme_select2_events_class', {
        placeholder: ememails.translate_selectevents,
        extraPlugins: ['remove_button'],
        pagesize: 30,
        action: 'eme_events_select2',
            ajaxParams: {
                search_all: EME.$('#eventsearch_all')?.checked ? 1 : 0,
                eme_admin_nonce: ememails.translate_adminnonce
            }
    });

});
