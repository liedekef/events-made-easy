document.addEventListener('DOMContentLoaded', function () {
    const TaskSignupsTableContainer = EME.$('#TaskSignupsTableContainer');
    let TaskSignupsTable;

    // --- Initialize Task Signups Table with ftable ---
    if (TaskSignupsTableContainer) {
        // Insert sorting info element before table
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'tasksignupstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        TaskSignupsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        let taskSignupFields = {
            id: {
                key: true,
                width: '1%',
                columnResizable: false,
                list: false
            },
            event_name: {
                visibility: 'fixed',
                title: emetasks.translate_event
            },
            task_name: {
                visibility: 'fixed',
                title: emetasks.translate_taskname
            },
            task_start: {
                title: emetasks.translate_taskstart
            },
            task_end: {
                title: emetasks.translate_taskend
            },
            signup_status: {
                visibility: 'hidden',
                title: emetasks.translate_tasksignup_status
            },
            signup_date: {
                visibility: 'hidden',
                title: emetasks.translate_tasksignup_date
            },
            comment: {
                title: emetasks.translate_comment,
                sorting: false,
                visibility: 'hidden'
            },
            person_info: {
                sorting: false,
                title: emetasks.translate_person
            }
        }
        // Add extra fields
        const extraFieldsAttr = TaskSignupsTableContainer.dataset.extrafields;
        const extraFieldNamesAttr = TaskSignupsTableContainer.dataset.extrafieldnames;
        const extraFieldSearchableAttr = TaskSignupsTableContainer.dataset.extrafieldsearchable;
        if (extraFieldsAttr && extraFieldNamesAttr) {
            const extraFields = extraFieldsAttr.split(',');
            const extraNames = extraFieldNamesAttr.split(',');
            const extraSearches = extraFieldSearchableAttr.split(',');
            extraFields.forEach((value, index) => {
                if (value == 'SEPARATOR') {
                    let fieldindex = 'SEPARATOR_'+index;
                    taskSignupFields[fieldindex] = { title: extraNames[index], sorting: false, visibility: 'separator' };
                } else {
                    let fieldindex = 'FIELD_'+value;
                    taskSignupFields[fieldindex] = { title: extraNames[index], sorting: extraSearches[index]=='1', visibility: 'hidden' };
                }
            });
        }

        TaskSignupsTable = new FTable('#TaskSignupsTableContainer', {
            title: emetasks.translate_signups,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'event_name ASC, task_start ASC, task_name ASC, signup_status',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            csvExport: true,
            printTable: true,
            actions: {
                listAction: ajaxurl,
                deleteAction: ajaxurl+'?action=eme_manage_task_signups&do_action=deleteTaskSignups&eme_admin_nonce='+emetasks.translate_adminnonce
            },
            listQueryParams: function () {
                return {
                    action: 'eme_task_signups_list',
                    eme_admin_nonce: emetasks.translate_adminnonce,
                    search_name: eme_getValue(EME.$('#search_name')),
                    search_event: eme_getValue(EME.$('#search_event')),
                    search_eventid: eme_getValue(EME.$('#search_eventid')),
                    search_person: eme_getValue(EME.$('#search_person')),
                    search_scope: eme_getValue(EME.$('#search_scope')),
                    search_start_date: EME.$('#search_start_date')?.value || '',
                    search_end_date: EME.$('#search_end_date')?.value || '',
                    search_signup_status: eme_getValue(EME.$('#search_signup_status'))
                };
            },
            fields: taskSignupFields,
            sortingInfoSelector: '#tasksignupstablesortingInfo',
            messages: {
                sortingInfoNone: ''
            }
        });

        // Load the table
        TaskSignupsTable.load();
    }

    // --- Conditional UI: Show/hide "Send mails" based on selected action ---
    function updateShowHideStuff() {
        const actionSelect = EME.$('#eme_admin_action');
        const sendMailSpan = EME.$('#span_sendmails');
        if (!actionSelect || !sendMailSpan) return;

        const action = actionSelect.value;
        const show = ['approveTaskSignups', 'deleteTaskSignups'].includes(action);
        eme_toggle(sendMailSpan, show);
    }

    const actionSelect = EME.$('#eme_admin_action');
    if (actionSelect) {
        actionSelect.addEventListener('change', updateShowHideStuff);
        updateShowHideStuff(); // Initial call
    }

    // --- Bulk Actions Button ---
    const actionsButton = EME.$('#TaskSignupsActionsButton');
    if (actionsButton) {
        actionsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = TaskSignupsTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action')?.value;
            const sendMail = EME.$('#send_mail')?.value || 'no';

            if (!selectedRows.length || !doAction) return;

            let proceed = true;
            if (doAction === 'deleteTaskSignups' && !confirm(emetasks.translate_areyousuretodeleteselected)) {
                proceed = false;
            }

            if (proceed) {
                actionsButton.textContent = emetasks.translate_pleasewait;
                actionsButton.disabled = true;

                const ids = selectedRows.map(row => row.dataset.recordKey);
                const idsJoined = ids.join(',');

                // Special case: "Send Mails" redirects to mailing form
                if (doAction === 'sendMails') {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = emetasks.translate_admin_sendmails_url;
                    ['tasksignup_ids', 'eme_admin_action'].forEach(key => {
                        const val = key === 'tasksignup_ids' ? idsJoined : 'new_mailing';
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

                // Regular AJAX action
                const formData = new FormData();
                formData.append('id', idsJoined);
                formData.append('action', 'eme_manage_task_signups');
                formData.append('do_action', doAction);
                formData.append('send_mail', sendMail);
                formData.append('eme_admin_nonce', emetasks.translate_adminnonce);

                eme_postJSON(ajaxurl, formData, (data) => {
                    TaskSignupsTable.reload();
                    actionsButton.textContent = emetasks.translate_apply;
                    actionsButton.disabled = false;

                    const messageDiv = EME.$('#tasksignups-message');
                    if (messageDiv) {
                        messageDiv.innerHTML = data.htmlmessage;
                        eme_toggle(messageDiv, true);
                        setTimeout(() => {
                            eme_toggle(messageDiv, false);
                        }, 5000);
                    }
                });
            }
        });
    }

    // --- Reload Button ---
    const loadRecordsButton = EME.$('#TaskSignupsLoadRecordsButton');
    if (loadRecordsButton) {
        loadRecordsButton.addEventListener('click', function (e) {
            e.preventDefault();
            TaskSignupsTable.load();
        });
    }
});
