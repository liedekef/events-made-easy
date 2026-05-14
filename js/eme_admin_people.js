document.addEventListener('DOMContentLoaded', function () {
    const PeopleTableContainer = EME.$('#PeopleTableContainer');
    let PeopleTable;
    const GroupsTableContainer = EME.$('#GroupsTableContainer');
    let GroupsTable;

    // --- Initialize People Table ---
    if (PeopleTableContainer) {
        let personFields = {
            'people.person_id': {
                key: true,
                title: emeadmin.translate_personid,
                width: '1%',
                columnResizable: false,
                visibility: 'hidden'
            },
            'people.lastname': {
                title: emeadmin.translate_lastname,
            },
            'people.firstname': {
                title: emeadmin.translate_firstname
            },
            'people.address1': {
                title: emeadmin.translate_address1,
                visibility: 'hidden'
            },
            'people.address2': {
                title: emeadmin.translate_address2,
                visibility: 'hidden'
            },
            'people.city': {
                title: emeadmin.translate_city,
                visibility: 'hidden'
            },
            'people.zip': {
                title: emeadmin.translate_zip,
                visibility: 'hidden'
            },
            'people.state': {
                title: emeadmin.translate_state,
                visibility: 'hidden'
            },
            'people.country': {
                title: emeadmin.translate_country,
                visibility: 'hidden'
            },
            'people.email': {
                title: emeadmin.translate_email,
            },
            'people.phone': {
                title: emeadmin.translate_phone,
                visibility: 'hidden'
            },
            'people.birthdate': {
                title: emeadmin.translate_birthdate,
                visibility: 'hidden'
            },
            'people.birthplace': {
                title: emeadmin.translate_birthplace,
                visibility: 'hidden'
            },
            'people.lang': {
                title: emeadmin.translate_lang,
                visibility: 'hidden',
            },
            'people.massmail': {
                title: emeadmin.translate_massmail,
                visibility: 'hidden'
            },
            'people.bd_email': {
                title: emeadmin.translate_bd_email,
                visibility: 'hidden'
            },
            'people.gdpr': {
                title: emeadmin.translate_gdpr,
                visibility: 'hidden'
            },
            'people.gdpr_date': {
                title: emeadmin.translate_gdpr_date,
                visibility: 'hidden'
            },
            'people.creation_date': {
                title: emeadmin.translate_created_on,
                visibility: 'hidden'
            },
            'people.modif_date': {
                title: emeadmin.translate_modified_on,
                visibility: 'hidden'
            },
            'people.related_to': {
                title: emeadmin.translate_related_to,
                sorting: false,
                visibility: 'hidden'
            },
            'people.groups': {
                title: emeadmin.translate_persongroups,
                sorting: false,
                visibility: 'hidden'
            },
            'people.memberships': {
                title: emeadmin.translate_personmemberships,
                sorting: false,
                visibility: 'hidden'
            },
            'people.wp_user': {
                title: emeadmin.translate_wpuser,
                sorting: false,
                visibility: 'hidden'
            },
            'bookingsmade': {
                title: emeadmin.translate_bookingsmade,
                sorting: false,
                visibility: 'hidden',
                display: function (data) {
                    return '<a href="admin.php?page=eme-registration-seats&person_id='+ data.record['people.person_id']+'">' + emeadmin.translate_showallbookings + '</a>';
                }
            }
        };

        // Add extra fields
        const extraFieldsAttr = PeopleTableContainer.dataset.extrafields;
        const extraFieldNamesAttr = PeopleTableContainer.dataset.extrafieldnames;
        const extraFieldSearchableAttr = PeopleTableContainer.dataset.extrafieldsearchable;
        if (extraFieldsAttr && extraFieldNamesAttr) {
            const extraFields = extraFieldsAttr.split(',');
            const extraNames = extraFieldNamesAttr.split(',');
            const extraSearches = extraFieldSearchableAttr.split(',');
            extraFields.forEach((value, index) => {
                if (value == 'SEPARATOR') {
                    let fieldindex = 'SEPARATOR_'+index;
                    personFields[fieldindex] = { title: extraNames[index], sorting: false, visibility: 'separator' };
                } else {
                    let fieldindex = 'FIELD_'+value;
                    personFields[fieldindex] = { title: extraNames[index], sorting: extraSearches[index]=='1', visibility: 'hidden' };
                }
            });
        }

        PeopleTable = new FTable('#PeopleTableContainer', {
            title: emeadmin.translate_people,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            defaultSorting: 'people.lastname ASC, people.firstname ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            csvExport: true,
            printTable: true,
            actions: { listAction: ajaxurl },
            listQueryParams: () => ({
                action: 'eme_people_list',
                eme_admin_nonce: emeadmin.translate_adminnonce,
                trash: $_GET['trash'] || '',
                search_person: eme_getValue(EME.$('#search_person')),
                search_groups: eme_getValue(EME.$('#search_groups')),
                search_memberstatus: eme_getValue(EME.$('#search_memberstatus')),
                search_membershipids: eme_getValue(EME.$('#search_membershipids')),
                search_customfields: eme_getValue(EME.$('#search_customfields')),
                search_customfieldids: eme_getValue(EME.$('#search_customfieldids')),
                search_exactmatch: EME.$('#search_exactmatch')?.checked ? 1 : 0
            }),
            fields: personFields
        });

        PeopleTable.load();
    }

    // --- Initialize Groups Table ---
    if (GroupsTableContainer) {
        GroupsTable = new FTable('#GroupsTableContainer', {
            title: emeadmin.translate_groups,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl+'?action=eme_groups_list&eme_admin_nonce='+emeadmin.translate_adminnonce,
                deleteAction: ajaxurl+'?action=eme_manage_groups&do_action=deleteGroups&eme_admin_nonce='+emeadmin.translate_adminnonce,
            },
            fields: {
                'group_id': {
                    title: emeadmin.translate_groupid,
                    key: true,
                    create: false,
                    edit: false,
                    visibility: 'hidden'
                },
                'name': {
                    title: emeadmin.translate_name,
                },
                'description': {
                    title: emeadmin.translate_description
                },
                'public': {
                    title: emeadmin.translate_publicgroup,
                    visibility: 'hidden'
                },
                'groupcount': {
                    title: emeadmin.translate_groupcount,
                    sorting: false
                }
            }
        });

        GroupsTable.load();
    }

    // --- Conditional UI: Show/hide based on action ---
    function updateShowHideStuff() {
        const action = EME.$('#eme_admin_action')?.value || '';
        eme_toggle(EME.$('#span_language'), action === 'changeLanguage');
        eme_toggle(EME.$('#span_addtogroup'), action === 'addToGroup');
        eme_toggle(EME.$('#span_removefromgroup'), action === 'removeFromGroup');
        eme_toggle(EME.$('#span_pdftemplate'), action === 'pdf');
        eme_toggle(EME.$('#span_htmltemplate'), action === 'html');
        eme_toggle(EME.$('span#span_transferto'), ['trashPeople', 'deletePeople'].includes(action));
    }

    EME.$('#eme_admin_action')?.addEventListener('change', updateShowHideStuff);
    updateShowHideStuff();

    // --- Dynamic People Data (for dyngroups) ---
    function eme_dynamic_people_data_json(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const formData = new FormData(form);
        formData.append('action', 'eme_people_dyndata');
        formData.append('eme_admin_nonce', emeadmin.translate_adminnonce);

        eme_postJSON(ajaxurl, formData, (data) => {
            if (data && data.Result) {
                EME.$('#eme_dynpersondata').innerHTML = data.Result;
                eme_init_widgets(true);
            }
        });
    }

    // Attach to dyngroups change
    if (EME.$('#editperson')) {
        EME.$('#editperson').addEventListener('change', function (e) {
            if (e.target.matches('select.dyngroups')) {
                eme_dynamic_people_data_json('editperson');
            }
        });
        eme_dynamic_people_data_json('editperson');
    }

    // --- SnapSelect: transferto_id (chooseperson) ---
    if (EME.$('select.eme_snapselect_chooseperson')) {
        initSnapSelectRemote('select.eme_snapselect_chooseperson', {
            showClearButton: true,
            data: function(search, page) {
                let excludeIds = '';
                if (PeopleTableContainer && PeopleTable) {
                    const selectedRows = PeopleTable.getSelectedRows();
                    if (selectedRows.length > 0) {
                        excludeIds = selectedRows.map(row => row.recordData['people.person_id']).join(',');
                    }
                }
                return {
                    action:             'eme_chooseperson_snapselect',
                    eme_admin_nonce:    emeadmin.translate_adminnonce,
                    exclude_personids:  excludeIds,
                };
            }
        });
    }

    // --- SnapSelect: related_person_id (chooserelatedperson) ---
    if (EME.$('select.eme_snapselect_chooserelatedperson')) {
        initSnapSelectRemote('select.eme_snapselect_chooserelatedperson', {
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
    }

    initSnapSelectRemote('#wp_id.eme_snapselect_wpuser_class_exclude_linked', {
        data: function(search, page) {
            return {
                action: 'eme_wpuser_snapselect_exclude_linked',
                eme_admin_nonce: emeadmin.translate_adminnonce,
                person_wpid: this.dataset.person_wpid || 0
            };
        }
    });

    // --- People Bulk Actions ---
    const peopleButton = EME.$('#PeopleActionsButton');
    if (peopleButton) {
        peopleButton.addEventListener('click', async function (e) {
            e.preventDefault();
            const selectedRows = PeopleTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (['trashPeople', 'deletePeople'].includes(doAction)) {
                const ok = await FTable.confirm(emeadmin.translate_confirmdelete, emeadmin.translate_areyousuretodeleteselected);
                if (!ok) return;
            }

            peopleButton.textContent = emeadmin.translate_pleasewait;
            peopleButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('person_id', idsJoined);
            formData.append('action', 'eme_manage_people');
            formData.append('do_action', doAction);
            formData.append('transferto_id', EME.$('#transferto_id')?.value || '');
            formData.append('language', EME.$('#language')?.value || '');
            formData.append('pdf_template', EME.$('#pdf_template')?.value || '');
            formData.append('pdf_template_header', EME.$('#pdf_template_header')?.value || '');
            formData.append('pdf_template_footer', EME.$('#pdf_template_footer')?.value || '');
            formData.append('html_template', EME.$('#html_template')?.value || '');
            formData.append('html_template_header', EME.$('#html_template_header')?.value || '');
            formData.append('html_template_footer', EME.$('#html_template_footer')?.value || '');
            formData.append('addtogroup', EME.$('#addtogroup')?.value || '');
            formData.append('removefromgroup', EME.$('#removefromgroup')?.value || '');
            formData.append('eme_admin_nonce', emeadmin.translate_adminnonce);

            if (doAction === 'sendMails') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = emeadmin.translate_admin_sendmails_url;
                ['person_ids', 'eme_admin_action'].forEach(key => {
                    const val = key === 'person_ids' ? idsJoined : 'new_mailing';
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
                for (const [key, value] of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
                document.body.appendChild(form);
                form.submit();
                peopleButton.textContent = emeadmin.translate_apply;
                peopleButton.disabled = false;
                return;
            }

            eme_postJSON(ajaxurl, formData, (data) => {
                PeopleTable.reload();
                peopleButton.textContent = emeadmin.translate_apply;
                peopleButton.disabled = false;

                const msg = EME.$('#people-message');
                if (msg) {
                    msg.innerHTML = data.htmlmessage;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 5000);
                }
            });
        });
    }

    // --- Groups Bulk Actions ---
    const groupsButton = EME.$('#GroupsActionsButton');
    if (groupsButton) {
        groupsButton.addEventListener('click', async function (e) {
            e.preventDefault();
            const selectedRows = GroupsTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (doAction==='deleteGroups') {
                const ok = await FTable.confirm(emeadmin.translate_confirmdelete, emeadmin.translate_areyousuretodeleteselected);
                if (!ok) return;
            }

            groupsButton.textContent = emeadmin.translate_pleasewait;
            groupsButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('group_id', idsJoined);
            formData.append('action', 'eme_manage_groups');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emeadmin.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                GroupsTable.reload();
                groupsButton.textContent = emeadmin.translate_apply;
                groupsButton.disabled = false;

                const msg = EME.$('#groups-message');
                if (msg) {
                    msg.innerHTML = data.htmlmessage;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 5000);
                }
            });
        });
    }

    const storeQueryButton = EME.$('#StoreQueryButton');
    const storeQueryDiv = EME.$('#StoreQueryDiv');
    EME.$('#PeopleLoadRecordsButton')?.addEventListener('click', e => {
        e.preventDefault();
        if (eme_getValue(EME.$('#search_person')).length ||
            eme_getValue(EME.$('#search_groups')).length ||
            eme_getValue(EME.$('#search_memberstatus')).length ||
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
        PeopleTable.load();
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
            'search_groups': eme_getValue(EME.$('#search_groups')),
            'search_memberstatus': eme_getValue(EME.$('#search_memberstatus')),
            'search_membershipids': eme_getValue(EME.$('#search_membershipids')),
            'search_customfields': eme_getValue(EME.$('#search_customfields')),
            'search_customfieldids': eme_getValue(EME.$('#search_customfieldids')),
            'search_exactmatch': exactmatch,
            'action': 'eme_store_people_query',
            'eme_admin_nonce': emeadmin.translate_adminnonce,
            'dynamicgroupname': EME.$('#dynamicgroupname').value
        };

        const formData = new FormData();
        for (const [key, value] of Object.entries(params)) {
            formData.append(key, value);
        }

        eme_postJSON(ajaxurl, formData, (data) => {
            eme_toggle(storeQueryButton, false);
            eme_toggle(storeQueryDiv, false);
            const msg = EME.$('#people-message');
            if (msg) {
                msg.innerHTML = data.htmlmessage;
                eme_toggle(msg, true);
                setTimeout(() => eme_toggle(msg, false), 5000);
            }
        });

        // return false to make sure the real form doesn't submit
        return false;
    });
});
