document.addEventListener('DOMContentLoaded', function () {
    const TaskSignupsTableContainer = $('#TaskSignupsTableContainer');
    let TaskSignupsTable;

    // --- Initialize Task Signups Table with ftable ---
    if (TaskSignupsTableContainer) {
        // Insert sorting info element before table
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'tasksignupstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        TaskSignupsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

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
                    search_name: eme_getValue($('#search_name')),
                    search_event: eme_getValue($('#search_event')),
                    search_eventid: eme_getValue($('#search_eventid')),
                    search_person: eme_getValue($('#search_person')),
                    search_scope: eme_getValue($('#search_scope')),
                    search_start_date: $('#search_start_date')?.value || '',
                    search_end_date: $('#search_end_date')?.value || '',
                    search_signup_status: eme_getValue($('#search_signup_status'))
                };
            },
            fields: {
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
            },
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
        const actionSelect = $('#eme_admin_action');
        const sendMailSpan = $('#span_sendmails');
        if (!actionSelect || !sendMailSpan) return;

        const action = actionSelect.value;
        const show = ['approveTaskSignups', 'deleteTaskSignups'].includes(action);
        eme_toggle(sendMailSpan, show);
    }

    const actionSelect = $('#eme_admin_action');
    if (actionSelect) {
        actionSelect.addEventListener('change', updateShowHideStuff);
        updateShowHideStuff(); // Initial call
    }

    // --- Bulk Actions Button ---
    const actionsButton = $('#TaskSignupsActionsButton');
    if (actionsButton) {
        actionsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = TaskSignupsTable.getSelectedRows();
            const doAction = $('#eme_admin_action')?.value;
            const sendMail = $('#send_mail')?.value || 'no';

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
                    TaskSignupsTable.load();
                    actionsButton.textContent = emetasks.translate_apply;
                    actionsButton.disabled = false;

                    const messageDiv = $('div#tasksignups-message');
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
    const loadRecordsButton = $('#TaskSignupsLoadRecordsButton');
    if (loadRecordsButton) {
        loadRecordsButton.addEventListener('click', function (e) {
            e.preventDefault();
            TaskSignupsTable.load();
        });
    }
});
