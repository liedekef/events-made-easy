document.addEventListener('DOMContentLoaded', function () {
    const MembershipsTableContainer = EME.$('#MembershipsTableContainer');
    let MembershipsTable;
    const MembersTableContainer = EME.$('#MembersTableContainer');
    let MembersTable;

    // --- Initialize Memberships Table ---
    if (MembershipsTableContainer) {
        MembershipsTable = new FTable('#MembershipsTableContainer', {
            title: emeadmin.translate_memberships,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: { listAction: ajaxurl },
            listQueryParams: () => ({
                action: 'eme_memberships_list',
                lang: emeadmin.translate_locale,
                eme_admin_nonce: emeadmin.translate_adminnonce,
                used_field_id: $_GET['used_field_id'] || ''
            }),
            fields: {
                membership_id: {
                    key: true,
                    title: emeadmin.translate_id,
                    width: '1%',
                    columnResizable: false,
                    visibility: 'hidden'
                },
                name: {
                    title: emeadmin.translate_name
                },
                description: {
                    title: emeadmin.translate_description
                },
                membercount: {
                    title: emeadmin.translate_membercount,
                    sorting: false
                }
            },
            bulkActions: {
                select: '#eme_admin_action_memberships',
                button: '#MembershipsActionsButton',
                idField: 'membership_id',
                action: ajaxurl,
                confirmActions: ['deleteMemberships'],
                confirmTitle: emeadmin.translate_confirmdelete,
                confirmMessage: emeadmin.translate_areyousuretodeleteselected,
                extraData: () => ({
                    action: 'eme_manage_memberships',
                    lang: emeadmin.translate_locale,
                    eme_admin_nonce: emeadmin.translate_adminnonce
                })
            },
            bulkActionComplete: ({ data }) => {
                eme_show_ftable_bulk_result(MembershipsTable, data);
            },
            bulkActionError: ({ data }) => {
                MembershipsTable.showError(emeadmin.translate_problem);
            }
        });

        MembershipsTable.load();
    }

    // --- Memberships Bulk Actions ---
    // --- Initialize Members Table ---
    if (MembersTableContainer) {
        let memberFields = {
            'members.member_id': {
                key: true,
                width: '1%',
                columnResizable: false,
                title: emeadmin.translate_memberid,
                visibility: 'hidden'
            },
            lastname: {
                title: emeadmin.translate_lastname
            },
            firstname: {
                title: emeadmin.translate_firstname
            },
            email: {
                title: emeadmin.translate_email
            },
            related_member_id: {
                title: emeadmin.translate_related_to,
                visibility: 'hidden'
            },
            address1: {
                title: emeadmin.translate_address1,
                visibility: 'hidden'
            },
            address2: {
                title: emeadmin.translate_address2,
                visibility: 'hidden'
            },
            city: {
                title: emeadmin.translate_city,
                visibility: 'hidden'
            },
            zip: {
                title: emeadmin.translate_zip,
                visibility: 'hidden'
            },
            state: {
                title: emeadmin.translate_state,
                visibility: 'hidden'
            },
            country: {
                title: emeadmin.translate_country,
                visibility: 'hidden'
            },
            birthdate: {
                title: emeadmin.translate_birthdate,
                visibility: 'hidden'
            },
            birthplace: {
                title: emeadmin.translate_birthplace,
                visibility: 'hidden'
            },
            membership_name: {
                title: emeadmin.translate_membership,
                visibility: 'hidden'
            },
            membershipprice: {
                title: emeadmin.translate_membershipprice,
                visibility: 'hidden',
                sorting: false
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
                visibility: 'hidden',
                sorting: false
            },
            start_date: {
                title: emeadmin.translate_startdate,
                visibility: 'hidden'
            },
            end_date: {
                title: emeadmin.translate_enddate,
                visibility: 'hidden'
            },
            usage_count: {
                title: emeadmin.translate_usage_count,
                visibility: 'hidden',
                sorting: false
            },
            creation_date: {
                title: emeadmin.translate_registrationdate,
                visibility: 'hidden'
            },
            last_seen: {
                title: emeadmin.translate_last_seen,
                visibility: 'hidden'
            },
            paid: {
                title: emeadmin.translate_paid,
                visibility: 'hidden'
            },
            unique_nbr: {
                title: emeadmin.translate_uniquenbr,
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
                title: emeadmin.translate_paymentid,
                visibility: 'hidden'
            },
            reminder_date: {
                title: emeadmin.translate_lastreminder,
                visibility: 'hidden'
            },
            reminder: {
                title: emeadmin.translate_nbrreminder,
                visibility: 'hidden'
            },
            status: {
                title: emeadmin.translate_status,
                visibility: 'hidden'
            },
            wp_user: {
                title: emeadmin.translate_wpuser,
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
            title: emeadmin.translate_members,
            paging: true,
            sorting: true,
            sortingResetButton: true,
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
                eme_admin_nonce: emeadmin.translate_adminnonce,
                lang: emeadmin.translate_locale,
                search_person: eme_getValue(EME.$('#search_person')),
                search_memberstatus: eme_getValue(EME.$('#search_memberstatus')),
                search_membershipids: eme_getValue(EME.$('#search_membershipids')),
                search_memberid: eme_getValue(EME.$('#search_memberid')),
                search_paymentid: eme_getValue(EME.$('#search_paymentid')),
                search_pg_pid: eme_getValue(EME.$('#search_pg_pid')),
                ...(() => {
                    const cf = eme_get_customfieldfilter_values('eme_cf_filters');
                    return {
                        search_customfieldids: cf.fieldids,
                        search_customfieldvalues: cf.values,
                        search_customfieldexact: cf.exacts
                    };
                })(),
                used_field_id: $_GET['used_field_id'] || ''
            }),
            fields: memberFields,
            bulkActions: {
                select: '#eme_admin_action_members',
                button: '#MembersActionsButton',
                idField: 'member_id',
                action: ajaxurl,
                confirmActions: ['deleteMembers'],
                confirmTitle: emeadmin.translate_confirmdelete,
                confirmMessage: emeadmin.translate_areyousuretodeleteselected,
                visibleWhen: {
                    '#span_sendmails': ['acceptPayment', 'stopMembership', 'markUnpaid'],
                    '#span_trashperson': ['deleteMembers'],
                    '#span_membermailtemplate': ['memberMails'],
                    '#span_pdftemplate': ['pdf'],
                    '#span_htmltemplate': ['html']
                },
                extraData: () => {
                    const action = EME.$('#eme_admin_action_members')?.value || '';
                    const sendMailEl = EME.$('#send_mail');
                    if (sendMailEl) {
                        if (['acceptPayment', 'stopMembership'].includes(action)) {
                            sendMailEl.value = 1;
                        } else if (action === 'markUnpaid') {
                            sendMailEl.value = 0;
                        }
                    }
                    return {
                        action: 'eme_manage_members',
                        lang: emeadmin.translate_locale,
                        send_mail: EME.$('#send_mail')?.value || '',
                        trash_person: EME.$('#trash_person')?.value || '',
                        membermail_template: EME.$('#membermail_template')?.value || '',
                        membermail_template_subject: EME.$('#membermail_template_subject')?.value || '',
                        pdf_template: EME.$('#pdf_template')?.value || '',
                        pdf_template_header: EME.$('#pdf_template_header')?.value || '',
                        pdf_template_footer: EME.$('#pdf_template_footer')?.value || '',
                        html_template: EME.$('#html_template')?.value || '',
                        html_template_header: EME.$('#html_template_header')?.value || '',
                        html_template_footer: EME.$('#html_template_footer')?.value || '',
                        eme_admin_nonce: emeadmin.translate_adminnonce
                    };
                },
                handlers: {
                    sendMails: ({ ids }) => eme_submit_hidden_form(emeadmin.translate_admin_sendmails_url, {
                        member_ids: ids.join(','),
                        eme_admin_action: 'new_mailing'
                    }),
                    pdf: ({ ids }) => eme_submit_hidden_form(ajaxurl, {
                        action: 'eme_manage_members',
                        member_id: ids.join(','),
                        do_action: 'pdf',
                        pdf_template: EME.$('#pdf_template')?.value || '',
                        pdf_template_header: EME.$('#pdf_template_header')?.value || '',
                        pdf_template_footer: EME.$('#pdf_template_footer')?.value || '',
                        eme_admin_nonce: emeadmin.translate_adminnonce
                    }),
                    html: ({ ids }) => eme_submit_hidden_form(ajaxurl, {
                        action: 'eme_manage_members',
                        member_id: ids.join(','),
                        do_action: 'html',
                        html_template: EME.$('#html_template')?.value || '',
                        html_template_header: EME.$('#html_template_header')?.value || '',
                        html_template_footer: EME.$('#html_template_footer')?.value || '',
                        eme_admin_nonce: emeadmin.translate_adminnonce
                    })
                }
            },
            bulkActionComplete: ({ data }) => {
                eme_show_ftable_bulk_result(MembersTable, data);
            },
            bulkActionError: ({ data }) => {
                MembersTable.showError(emeadmin.translate_problem);
            }
        });

        MembersTable.load();
    }

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
    if (EME.$('select.eme_snapselect_autocompletememberperson')) {
        initSnapSelectRemote('select.eme_snapselect_autocompletememberperson', {
            showClearButton: true,
            url: ajaxurl,
            cache: true,
            data: function(search, page) {
                return {
                    action:          'eme_chooseperson_snapselect',
                    eme_admin_nonce: emeadmin.translate_adminnonce,
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
            showClearButton: true,
            url: ajaxurl,
            cache: true,
            data: function(search, page) {
                return {
                    action:            'eme_memberperson_snapselect',
                    eme_admin_nonce:   emeadmin.translate_adminnonce,
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
            showClearButton: true,
            url: ajaxurl,
            cache: true,
            data: function(search, page) {
                return {
                    action:          'eme_membermainaccount_snapselect',
                    eme_admin_nonce: emeadmin.translate_adminnonce,
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
            eme_get_customfieldfilter_values('eme_cf_filters').fieldids.length) {
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
        const cf = eme_get_customfieldfilter_values('eme_cf_filters');
        let params = {
            'search_person': eme_getValue(EME.$('#search_person')),
            'search_memberstatus': eme_getValue(EME.$('#search_memberstatus')),
            'search_membershipids': eme_getValue(EME.$('#search_membershipids')),
            'search_memberid': eme_getValue(EME.$('#search_memberid')),
            'search_customfieldids': cf.fieldids,
            'search_customfieldvalues': cf.values,
            'search_customfieldexact': cf.exacts,
            'action': 'eme_store_members_query',
            'eme_admin_nonce': emeadmin.translate_adminnonce,
            'dynamicgroupname': EME.$('#dynamicgroupname').value
        };

        const formData = new FormData();
        for (const [key, value] of Object.entries(params)) {
            formData.append(key, value);
        }

        eme_postJSON(ajaxurl, formData, (data) => {
            eme_show_ftable_bulk_result(MembersTable, data);
            eme_toggle(storeQueryButton, false);
            eme_toggle(storeQueryDiv, false);
        });

        // return false to make sure the real form doesn't submit
        return false;
    });

    eme_admin_init_attachment_ui('#newmember_attach_button', '#newmember_attach_links', '#eme_newmember_attach_ids', '#newmember_remove_attach_button');
    eme_admin_init_attachment_ui('#extended_attach_button', '#extended_attach_links', '#eme_extended_attach_ids', '#extended_remove_attach_button');
    eme_admin_init_attachment_ui('#paid_attach_button', '#paid_attach_links', '#eme_paid_attach_ids', '#paid_remove_attach_button');

    // --- Delete a dyndata occurrence block from the admin member-edit form ---
    document.addEventListener('click', async function (e) {
        if (!e.target.matches('.eme_delete_dyndata_occurence')) return;

        const block     = e.target.closest('.eme_dyndata_occurence_block');
        const grouping  = block.dataset.grouping;
        const occurence = block.dataset.occurence;
        const memberId  = block.dataset.memberId;

        const confirmMsg = emeadmin.translate_areyousure_group;
        const ok = await FTable.confirm(emeadmin.translate_confirmdelete, confirmMsg);
        if (!ok) return;

        e.target.disabled    = true;
        e.target.textContent = '…';

        const fd = new FormData();
        fd.append('action',          'eme_delete_dyndata_occurence');
        fd.append('eme_admin_nonce', emeadmin.translate_adminnonce);
        fd.append('member_id',       memberId);
        fd.append('grouping',        grouping);
        fd.append('occurence',       occurence);

        eme_postJSON(ajaxurl, fd, (data) => {
            if (data.Result !== 'OK') {
                alert(data.htmlmessage);
                e.target.disabled    = false;
                e.target.textContent = '✕';
                return;
            }

            // Remove the deleted block from the DOM
            block.remove();

            // Re-index the remaining blocks in this group: update data-occurence,
            // labels and input names so the form POSTs correct indices on save
            const form = document.getElementById('eme-member-adminform');
            if (!form) return;
            const remaining = form.querySelectorAll(
                `.eme_dyndata_occurence_block[data-grouping="${grouping}"]`
            );
            remaining.forEach((b, idx) => {
                b.dataset.occurence = idx;

                // Update the visible label
                const lbl = b.querySelector('.eme_dyndata_occurence_label');
                if (lbl) {
                    const base = emeadmin.translate_occurrence || 'Occurrence';
                    lbl.textContent = base + ' ' + (idx + 1);
                }

                // Re-index input/select/textarea names:
                // dynamic_member[mid][grouping][OLD_OCC][FIELDx]  →  [...][idx][FIELDx]
                b.querySelectorAll('[name]').forEach(input => {
                    input.name = input.name.replace(
                        /^(dynamic_member\[\d+\]\[\d+\]\[)\d+(\].*)$/,
                        '$1' + idx + '$2'
                    );
                });
            });
        });
    });

});
