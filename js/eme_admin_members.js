document.addEventListener('DOMContentLoaded', function () {
    const MembershipsTableContainer = EME.$('#MembershipsTableContainer');
    let MembershipsTable;
    const MembersTableContainer = EME.$('#MembersTableContainer');
    let MembersTable;

    // --- Initialize Memberships Table ---
    if (MembershipsTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'memberstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        MembershipsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        MembershipsTable = new FTable('#MembershipsTableContainer', {
            title: ememembers.translate_memberships,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: { listAction: ajaxurl },
            listQueryParams: () => ({
                action: 'eme_memberships_list',
                eme_admin_nonce: ememembers.translate_adminnonce
            }),
            fields: {
                membership_id: {
                    key: true,
                    title: ememembers.translate_id,
                    width: '1%',
                    columnResizable: false,
                    visibility: 'hidden'
                },
                name: {
                    title: ememembers.translate_name
                },
                description: {
                    title: ememembers.translate_description
                },
                membercount: {
                    title: ememembers.translate_membercount,
                    sorting: false
                }
            },
            sortingInfoSelector: '#memberstablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        MembershipsTable.load();
    }

    // --- Memberships Bulk Actions ---
    const membershipsButton = EME.$('#MembershipsActionsButton');
    if (membershipsButton) {
        membershipsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = MembershipsTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (['deleteMemberships'].includes(doAction) && !confirm(ememembers.translate_areyousuretodeleteselected)) return;

            membershipsButton.textContent = ememembers.translate_pleasewait;
            membershipsButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('membership_id', idsJoined);
            formData.append('action', 'eme_manage_memberships');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', ememembers.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                MembershipsTable.reload();
                membershipsButton.textContent = ememembers.translate_apply;
                membershipsButton.disabled = false;

                const msg = EME.$('#memberships-message');
                if (msg) {
                    msg.innerHTML = data.htmlmessage;
                    eme_toggle(msg, true);
                    if (doAction !== 'showMembershipStats') {
                        setTimeout(() => eme_toggle(msg, false), 5000);
                    }
                }
            });
        });
    }

    // --- Initialize Members Table ---
    if (MembersTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'membersmemberstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        MembersTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        let memberFields = {
            'members.member_id': {
                key: true,
                width: '1%',
                columnResizable: false,
                title: ememembers.translate_memberid,
                visibility: 'hidden'
            },
            lastname: {
                title: ememembers.translate_lastname
            },
            firstname: {
                title: ememembers.translate_firstname
            },
            email: {
                title: ememembers.translate_email
            },
            related_member_id: {
                title: ememembers.translate_related_to,
                visibility: 'hidden'
            },
            address1: {
                title: ememembers.translate_address1,
                visibility: 'hidden'
            },
            address2: {
                title: ememembers.translate_address2,
                visibility: 'hidden'
            },
            city: {
                title: ememembers.translate_city,
                visibility: 'hidden'
            },
            zip: {
                title: ememembers.translate_zip,
                visibility: 'hidden'
            },
            state: {
                title: ememembers.translate_state,
                visibility: 'hidden'
            },
            country: {
                title: ememembers.translate_country,
                visibility: 'hidden'
            },
            birthdate: {
                title: ememembers.translate_birthdate,
                visibility: 'hidden'
            },
            birthplace: {
                title: ememembers.translate_birthplace,
                visibility: 'hidden'
            },
            membership_name: {
                title: ememembers.translate_membership,
                visibility: 'hidden'
            },
            membershipprice: {
                title: ememembers.translate_membershipprice,
                visibility: 'hidden',
                sorting: false
            },
            discount: {
                title: ememembers.translate_discount,
                sorting: false,
                visibility: 'hidden'
            },
            dcodes_used: {
                title: ememembers.translate_dcodes_used,
                sorting: false,
                visibility: 'hidden'
            },
            totalprice: {
                title: ememembers.translate_totalprice,
                visibility: 'hidden',
                sorting: false
            },
            start_date: {
                title: ememembers.translate_startdate,
                visibility: 'hidden'
            },
            end_date: {
                title: ememembers.translate_enddate,
                visibility: 'hidden'
            },
            usage_count: {
                title: ememembers.translate_usage_count,
                visibility: 'hidden',
                sorting: false
            },
            creation_date: {
                title: ememembers.translate_registrationdate,
                visibility: 'hidden'
            },
            last_seen: {
                title: ememembers.translate_last_seen,
                visibility: 'hidden'
            },
            paid: {
                title: ememembers.translate_paid,
                visibility: 'hidden'
            },
            unique_nbr: {
                title: ememembers.translate_uniquenbr,
                visibility: 'hidden'
            },
            payment_date: {
                title: ememembers.translate_paymentdate,
                visibility: 'hidden'
            },
            pg: {
                title: ememembers.translate_pg,
                visibility: 'hidden'
            },
            pg_pid: {
                title: ememembers.translate_pg_pid,
                visibility: 'hidden'
            },
            payment_id: {
                title: ememembers.translate_paymentid,
                visibility: 'hidden'
            },
            reminder_date: {
                title: ememembers.translate_lastreminder,
                visibility: 'hidden'
            },
            reminder: {
                title: ememembers.translate_nbrreminder,
                visibility: 'hidden'
            },
            status: {
                title: ememembers.translate_status,
                visibility: 'hidden'
            },
            wp_user: {
                title: ememembers.translate_wpuser,
                sorting: false,
                visibility: 'hidden'
            }
        };

        // Add extra fields
        const extraFieldsAttr = MembersTableContainer.dataset.extrafields;
        const extraFieldNamesAttr = MembersTableContainer.dataset.extrafieldnames;
        const extraFieldSearchableAttr = MembersTableContainer.dataset.extrafieldsearchable;
        if (extraFieldsAttr && extraFieldNamesAttr) {
            const extraFields = extraFieldsAttr.split(',');
            const extraNames = extraFieldNamesAttr.split(',');
            const extraSearches = extraFieldSearchableAttr.split(',');
            extraFields.forEach((value, index) => {
                if (value == 'SEPARATOR') {
                    let fieldindex = 'SEPARATOR_'+index;
                    memberFields[fieldindex] = { title: extraNames[index], sorting: false, visibility: 'separator' };
                } else {
                    let fieldindex = 'FIELD_'+value;
                    memberFields[fieldindex] = { title: extraNames[index], sorting: extraSearches[index]=='1', visibility: 'hidden' };
                }
            });
        }

        MembersTable = new FTable('#MembersTableContainer', {
            title: ememembers.translate_members,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'member_name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            csvExport: true,
            printTable: true,
            actions: { listAction: ajaxurl },
            listQueryParams: () => ({
                action: 'eme_members_list',
                eme_admin_nonce: ememembers.translate_adminnonce,
                search_person: eme_getValue(EME.$('#search_person')),
                search_memberstatus: eme_getValue(EME.$('#search_memberstatus')),
                search_membershipids: eme_getValue(EME.$('#search_membershipids')),
                search_memberid: eme_getValue(EME.$('#search_memberid')),
                search_paymentid: eme_getValue(EME.$('#search_paymentid')),
                search_pg_pid: eme_getValue(EME.$('#search_pg_pid')),
                search_customfields: eme_getValue(EME.$('#search_customfields')),
                search_customfieldids: eme_getValue(EME.$('#search_customfieldids')),
                search_exactmatch: EME.$('#search_exactmatch')?.checked ? 1 : 0
            }),
            fields: memberFields,
            sortingInfoSelector: '#membersmemberstablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        MembersTable.load();
    }

    // --- Conditional UI: Show/hide mail options ---
    function updateShowHideAdminActions() {
        const action = EME.$('#eme_admin_action')?.value || '';
        const sendMailsSpan = EME.$('#span_sendmails');
        eme_toggle(EME.$('#span_pdftemplate'), action === 'pdf');
        eme_toggle(EME.$('#span_htmltemplate'), action === 'html');
        eme_toggle(EME.$('span#span_membermailtemplate'), action === 'memberMails');
        eme_toggle(EME.$('span#span_trashperson'), action === 'deleteMembers');

        if (['acceptPayment', 'stopMembership'].includes(action)) {
            EME.$('#send_mail').value = 1;
        }
        if (['markUnpaid'].includes(action)) {
            EME.$('#send_mail').value = 0;
        }
        eme_toggle(sendMailsSpan, ['acceptPayment', 'stopMembership', 'markUnpaid'].includes(action));
    }
    EME.$('#eme_admin_action')?.addEventListener('change', updateShowHideAdminActions);
    updateShowHideAdminActions();

    // --- Conditional UI: Show/hide options ---
    const select_type = EME.$('select#type');
    if (select_type) {
        function updateShowHideFixedStartdate() {
            const type_value = select_type.value || '';
            eme_toggle(EME.$('tr#startdate'), type_value === 'fixed');
        }
        select_type.addEventListener('change', updateShowHideFixedStartdate);
        updateShowHideFixedStartdate();
    }

    // --- Conditional UI: Show/hide options ---
    const select_duration_period = EME.$('select#duration_period');
    if (select_duration_period) {
        function updateShowHideReminder () {
            const duration_period_value = select_duration_period?.value || '';
            eme_toggle(EME.$('tr#reminder'), duration_period_value !== 'forever');
            eme_toggle(EME.$('#duration_count'), duration_period_value !== 'forever');
            eme_toggle(EME.$('tr#freeperiod'), duration_period_value !== 'forever');
            eme_toggle(EME.$('tr#graceperiod'), duration_period_value !== 'forever');
        }
        select_duration_period.addEventListener('change', updateShowHideReminder);
        updateShowHideReminder();
    }

    // --- Conditional UI: Show/hide options ---
    const input_allow_renewal = EME.$('input#allow_renewal');
    if (input_allow_renewal) {
        function updateShowHideRenewal () {
            eme_toggle(EME.$('tr#tr_renewal_cutoff_days'), input_allow_renewal.checked );
        }
        input_allow_renewal.addEventListener('change',updateShowHideRenewal);
        updateShowHideRenewal();
    }

    // --- Conditional UI: Show/hide options ---
    const inputFamilyMembership = EME.$('#family_membership');
    if (inputFamilyMembership) {
        function updateShowHideFamilytpl () {
            if (inputFamilyMembership.checked) {
                eme_toggle(EME.$('#tr_family_maxmembers'), true);
                eme_toggle(EME.$('#tr_familymember_form_tpl'), true);
                EME.$('select[name="properties[familymember_form_tpl]"]').required = true;
            } else {
                eme_toggle(EME.$('#tr_family_maxmembers'), false);
                eme_toggle(EME.$('#tr_familymember_form_tpl'), false);
                EME.$('select[name="properties[familymember_form_tpl]"]').required = false;
            }
        }
        inputFamilyMembership.addEventListener('change', updateShowHideFamilytpl);
        updateShowHideFamilytpl();
    }

    // --- Bulk Actions ---
    const membersButton = EME.$('#MembersActionsButton');
    if (membersButton) {
        membersButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = MembersTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (['deleteMembers'].includes(doAction) && !confirm(ememembers.translate_areyousuretodeleteselected)) return;

            membersButton.textContent = ememembers.translate_pleasewait;
            membersButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('member_id', idsJoined);
            formData.append('action', 'eme_manage_members');
            formData.append('do_action', doAction);
            formData.append('send_mail', EME.$('#send_mail')?.value);
            formData.append('trash_person', EME.$('#trash_person')?.value);
            formData.append('membermail_template', EME.$('#membermail_template')?.value);
            formData.append('membermail_template_subject', EME.$('#membermail_template_subject')?.value);
            formData.append('pdf_template', EME.$('#pdf_template')?.value || '');
            formData.append('pdf_template_header', EME.$('#pdf_template_header')?.value || '');
            formData.append('pdf_template_footer', EME.$('#pdf_template_footer')?.value || '');
            formData.append('html_template', EME.$('#html_template')?.value || '');
            formData.append('html_template_header', EME.$('#html_template_header')?.value || '');
            formData.append('html_template_footer', EME.$('#html_template_footer')?.value || '');
            formData.append('eme_admin_nonce', ememembers.translate_adminnonce);

            if (doAction === 'sendMails') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = ememembers.translate_admin_sendmails_url;
                ['member_ids', 'eme_admin_action'].forEach(key => {
                    const val = key === 'member_ids' ? idsJoined : 'new_mailing';
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
                membersButton.textContent = ememembers.translate_apply;
                membersButton.disabled = false;
                return;
            }

            eme_postJSON(ajaxurl, formData, (data) => {
                MembersTable.reload();
                membersButton.textContent = ememembers.translate_apply;
                membersButton.disabled = false;

                const msg = EME.$('#members-message');
                if (msg) {
                    msg.innerHTML = data.htmlmessage;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 5000);
                }
            });
        });
    }

    EME.$('#membershipForm')?.addEventListener('submit', function(event) {
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

    // --- SnapSelect: chooseperson (add-member form) ---
    if (EME.$('select.eme_snapselect_chooseperson')) {
        initSnapSelectRemote('select.eme_snapselect_chooseperson', {
            allowEmpty: true,
            url: ajaxurl,
            data: function(search, page) {
                return {
                    action:          'eme_chooseperson_snapselect',
                    eme_admin_nonce: ememembers.translate_adminnonce,
                };
            },
            onItemAdd: function(value, text) {
                // first hide all personal info, then show informational info on the selected person
                EME.$$('.personal_info').forEach(el => eme_toggle(el, false));
                // Read extra fields stored on the <option> by SnapSelect
                const opt = this.querySelector(`option[value="${value}"]`);
                EME.$('input[name="person_id"]').value = value;
                EME.$('input[name=wp_id]').value       = opt?.dataset.wpid || '';
                const lastname  = EME.$('input[name=lastname]');
                const firstname = EME.$('input[name=firstname]');
                const email     = EME.$('input[name=email]');
                if (lastname)  { lastname.value  = opt?.dataset.lastname  || ''; lastname.readOnly  = true; eme_toggle(lastname, true); }
                if (firstname) { firstname.value = opt?.dataset.firstname || ''; firstname.readOnly = true; eme_toggle(firstname, true);}
                if (email)     { email.value     = opt?.dataset.email     || ''; email.readOnly     = true; eme_toggle(email, true);}
            },
            onItemDelete: function(value, text) {
                EME.$('input[name="person_id"]').value = '';
                EME.$('input[name=wp_id]').value       = '';
                const lastname  = EME.$('input[name=lastname]');
                const firstname = EME.$('input[name=firstname]');
                const email     = EME.$('input[name=email]');
                if (lastname)  { lastname.value  = ''; lastname.readOnly  = false; }
                if (firstname) { firstname.value = ''; firstname.readOnly = false; }
                if (email)     { email.value     = ''; email.readOnly     = false; }
                EME.$$('.personal_info').forEach(el => eme_toggle(el, true));
            }
        });
    }

    // --- SnapSelect: transferto_personid ---
    if (EME.$('select.eme_snapselect_transferperson')) {
        initSnapSelectRemote('select.eme_snapselect_transferperson', {
            allowEmpty: true,
            url: ajaxurl,
            data: function(search, page) {
                return {
                    action:            'eme_memberperson_snapselect',
                    eme_admin_nonce:   ememembers.translate_adminnonce,
                    exclude_personid:  this.dataset.personId    || '',
                    membership_id:     this.dataset.membershipId || '',
                    related_member_id: this.dataset.memberId     || '',
                };
            }
        });
    }

    // --- SnapSelect: related_member_id ---
    if (EME.$('select.eme_snapselect_relatedmember')) {
        initSnapSelectRemote('select.eme_snapselect_relatedmember', {
            allowEmpty: true,
            url: ajaxurl,
            data: function(search, page) {
                return {
                    action:          'eme_membermainaccount_snapselect',
                    eme_admin_nonce: ememembers.translate_adminnonce,
                    member_id:       this.dataset.memberId     || '',
                    membership_id:   this.dataset.membershipId || '',
                };
            }
        });
    }

    const storeQueryButton = EME.$('#StoreQueryButton');
    const storeQueryDiv = EME.$('#StoreQueryDiv');
    EME.$('#MembersLoadRecordsButton')?.addEventListener('click', e => {
        e.preventDefault();
        if (eme_getValue(EME.$('#search_person')).length ||
            eme_getValue(EME.$('#search_memberstatus')).length ||
            eme_getValue(EME.$('#search_memberid')).length ||
            eme_getValue(EME.$('#search_membershipids')).length ||
            eme_getValue(EME.$('#search_customfields')).length ||
            eme_getValue(EME.$('#search_customfieldids')).length ) {
            if (storeQueryButton) {
                eme_toggle(storeQueryButton, true);
            }
        } else {
            if (storeQueryButton) {
                eme_toggle(storeQueryButton, false);
            }
        }
        if (storeQueryDiv) {
            eme_toggle(storeQueryDiv, false);
        }
        MembersTable.load();
    });

    if (storeQueryButton) {
        storeQueryButton.addEventListener('click', e => {
            e.preventDefault();
            eme_toggle(storeQueryButton, false);
            eme_toggle(storeQueryDiv, true);
        });
        eme_toggle(storeQueryButton, false);
        eme_toggle(storeQueryDiv, false);
    }

    EME.$('#StoreQuerySubmitButton')?.addEventListener("click", function (e) {
        e.preventDefault();
        let exactmatch = 0;
        if (EME.$('#search_exactmatch').checked) {
            exactmatch = 1;
        }
        let params = {
            'search_person': eme_getValue(EME.$('#search_person')),
            'search_memberstatus': eme_getValue(EME.$('#search_memberstatus')),
            'search_membershipids': eme_getValue(EME.$('#search_membershipids')),
            'search_memberid': eme_getValue(EME.$('#search_memberid')),
            'search_customfields': eme_getValue(EME.$('#search_customfields')),
            'search_customfieldids': eme_getValue(EME.$('#search_customfieldids')),
            'search_exactmatch': exactmatch,
            'action': 'eme_store_members_query',
            'eme_admin_nonce': ememembers.translate_adminnonce,
            'dynamicgroupname': EME.$('#dynamicgroupname').value
        };

        const formData = new FormData();
        for (const [key, value] of Object.entries(params)) {
            formData.append(key, value);
        }

        eme_postJSON(ajaxurl, formData, (data) => {
            eme_toggle(storeQueryButton, false);
            eme_toggle(storeQueryDiv, false);
            const msg = EME.$('#members-message');
            if (msg) {
                msg.innerHTML = data.htmlmessage;
                eme_toggle(msg, true);
                setTimeout(() => eme_toggle(msg, false), 5000);
            }
        });

        // return false to make sure the real form doesn't submit
        return false;
    });

    eme_admin_init_attachment_ui('#newmember_attach_button', '#newmember_attach_links', '#eme_newmember_attach_ids', '#newmember_remove_attach_button');
    eme_admin_init_attachment_ui('#extended_attach_button', '#extended_attach_links', '#eme_extended_attach_ids', '#extended_remove_attach_button');
    eme_admin_init_attachment_ui('#paid_attach_button', '#paid_attach_links', '#eme_paid_attach_ids', '#paid_remove_attach_button');
});
