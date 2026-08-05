document.addEventListener('DOMContentLoaded', function () {
    const TaskSignupsTableContainer = EME.$('#TaskSignupsTableContainer');
    let TaskSignupsTable;

    // Builds & submits a real (non-ajax) POST form — used by the sendMails bulk action,
    // which navigates to the mailing composer.
    function eme_submit_hidden_form(url, fields) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        Object.entries(fields).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }

    // --- Initialize Task Signups Table with ftable ---
    if (TaskSignupsTableContainer) {
        let taskSignupFields = {
            id: {
                key: true,
                width: '1%',
                columnResizable: false,
                list: false
            },
            event_name: {
                visibility: 'fixed',
                title: emeadmin.translate_event
            },
            task_name: {
                visibility: 'fixed',
                title: emeadmin.translate_taskname
            },
            task_start: {
                title: emeadmin.translate_taskstart
            },
            task_end: {
                title: emeadmin.translate_taskend
            },
            signup_status: {
                title: emeadmin.translate_tasksignup_status
            },
            signup_date: {
                visibility: 'hidden',
                title: emeadmin.translate_tasksignup_date
            },
            comment: {
                title: emeadmin.translate_comment,
                sorting: false,
                visibility: 'hidden'
            },
            person_info: {
                sorting: false,
                title: emeadmin.translate_person
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
            title: emeadmin.translate_signups,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            defaultSorting: 'event_name ASC, task_start ASC, task_name ASC, signup_status',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            csvExport: true,
            printTable: true,
            actions: {
                listAction: ajaxurl,
                deleteAction: ajaxurl+'?action=eme_manage_task_signups&do_action=deleteTaskSignups&eme_admin_nonce='+emeadmin.translate_adminnonce
            },
            listQueryParams: function () {
                return {
                    action: 'eme_task_signups_list',
                    eme_admin_nonce: emeadmin.translate_adminnonce,
                    search_name: eme_getValue(EME.$('#search_name')),
                    search_event: eme_getValue(EME.$('#search_event')),
                    search_eventid: eme_getValue(EME.$('#search_eventid')),
                    search_person: eme_getValue(EME.$('#search_person')),
                    search_scope: eme_getValue(EME.$('#search_scope')),
                    search_start_date: EME.$('[name=search_start_date]')?.value || '',
                    search_end_date: EME.$('[name=search_end_date]')?.value || '',
                    search_signup_status: eme_getValue(EME.$('#search_signup_status'))
                };
            },
            fields: taskSignupFields,
            bulkActions: {
                select: '#eme_admin_action_tasksignups',
                button: '#TaskSignupsActionsButton',
                idField: 'id',
                action: ajaxurl,
                confirmActions: ['deleteTaskSignups'],
                confirmTitle: emeadmin.translate_confirmdelete,
                confirmMessage: emeadmin.translate_areyousuretodeleteselected,
                visibleWhen: {
                    '#span_sendmails': ['approveTaskSignups', 'deleteTaskSignups']
                },
                extraData: () => ({
                    action: 'eme_manage_task_signups',
                    send_mail: EME.$('#send_mail')?.value || 'no',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                }),
                handlers: {
                    sendMails: ({ ids }) => eme_submit_hidden_form(emeadmin.translate_admin_sendmails_url, {
                        tasksignup_ids: ids.join(','),
                        eme_admin_action: 'new_mailing'
                    })
                }
            },
            bulkActionComplete: ({ data }) => {
                if (data?.Result === 'ERROR') {
                    TaskSignupsTable.showError(data.htmlmessage);
                } else if (data?.Result === 'WARNING') {
                    TaskSignupsTable.showWarning(data.htmlmessage);
                } else {
                    TaskSignupsTable.showInfo(data?.htmlmessage);
                }
            }
        });

        // Load the table
        TaskSignupsTable.load();
    }

    // --- Reload Button ---
    const loadRecordsButton = EME.$('#TaskSignupsLoadRecordsButton');
    if (loadRecordsButton) {
        loadRecordsButton.addEventListener('click', function (e) {
            e.preventDefault();
            TaskSignupsTable.load();
        });
    }

    // --- Task Assignment: 3 snapselects with cascade ---
    const eventSelect = EME.$('#eme_event_selector');
    const taskSelect = EME.$('#eme_task_selector');
    const personSelect = EME.$('#eme_person_selector');
    const assignButton = EME.$('#AssignTaskButton');

    if (eventSelect && taskSelect && personSelect && assignButton) {
        // Event snapselect: future events with tasks
        initSnapSelectRemote(eventSelect, {
            placeholder: emeadmin.translate_selectevent,
            showClearButton: true,
            data: {
                action: 'eme_events_snapselect',
                only_events_with_tasks: 1,
                eme_admin_nonce: emeadmin.translate_adminnonce,
            },
            onItemAdd: function(value, text) {
                // Clear and refresh the task select
                if (taskSelect.snapselectInstance) {
                    taskSelect.snapselectInstance.clear();
                    taskSelect.snapselectInstance.clearCache();
                    taskSelect.snapselectInstance.setPlaceholder(emeadmin.translate_selecttask);
                }
            },
            onItemDelete: function(value, text) {
                if (taskSelect.snapselectInstance) {
                    taskSelect.snapselectInstance.clear();
                    taskSelect.snapselectInstance.clearCache();
                    taskSelect.snapselectInstance.setPlaceholder(emeadmin.translate_selecteventfirst);
                }
            }
        });

        // Task snapselect: tasks for selected event (dynamic event_id)
        initSnapSelectRemote(taskSelect, {
            placeholder: emeadmin.translate_selecteventfirst,
            showClearButton: true,
            data: function(search, page) {
                const selectedEventId = eventSelect.value;
                return {
                    action: 'eme_event_tasks_snapselect',
                    eme_admin_nonce: emeadmin.translate_adminnonce,
                    event_id: selectedEventId || 0,
                    q: search || '',
                };
            },
        });

        // Person snapselect: existing endpoint
        initSnapSelectRemote(personSelect, {
            placeholder: emeadmin.translate_selectperson,
            showClearButton: true,
            data: {
                action: 'eme_chooseperson_snapselect',
                eme_admin_nonce: emeadmin.translate_adminnonce,
            }
        });

        // Assign button handler
        assignButton.addEventListener('click', function (e) {
            e.preventDefault();

            const taskId = taskSelect.value;
            const personId = personSelect.value;

            if (!taskId || !personId) {
                if (TaskSignupsTable) {
                    TaskSignupsTable.showWarning('Please select a task and a person.');
                }
                return;
            }

            const origTextContent = assignButton.textContent;
            assignButton.textContent = emeadmin.translate_pleasewait;
            assignButton.disabled = true;

            const formData = new FormData();
            formData.append('action', 'eme_assign_task_signup');
            formData.append('task_id', taskId);
            formData.append('person_id', personId);
            formData.append('eme_admin_nonce', emeadmin.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, function(data) {
                if (data.Result === 'ERROR') {
                    if (TaskSignupsTable) {
                        TaskSignupsTable.showError(data.htmlmessage);
                    }
                } else {
                    if (TaskSignupsTable) {
                        TaskSignupsTable.showInfo(data.htmlmessage);
                        TaskSignupsTable.reload();
                    }
                    // Clear selections
                    if (eventSelect.snapselectInstance) {
                        eventSelect.snapselectInstance.clear();
                    }
                    if (taskSelect.snapselectInstance) {
                        taskSelect.snapselectInstance.clear();
                    }
                    if (personSelect.snapselectInstance) {
                        personSelect.snapselectInstance.clear();
                    }
                }
                assignButton.textContent = origTextContent;
                assignButton.disabled = false;
            });
        });
    }
});
