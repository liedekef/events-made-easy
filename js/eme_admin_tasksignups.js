jQuery(document).ready( function($) {
    $('#TaskSignupsTableContainer').jtable({
        title: emetasks.translate_signups,
        paging: true,
        sorting: true,
        multiSorting: true,
        defaultSorting: 'event_name ASC, task_start ASC, task_name ASC, signup_status',
        selecting: true, // Enable selecting
        multiselect: true, // Allow multiple selecting
        selectingCheckboxes: true, // Show checkboxes on first column
        csvExport: true,
        printTable: true,
        deleteConfirmation: function(data) {
            data.deleteConfirmMessage = emetasks.translate_pressdeletetoremove;
        },
        actions: {
            listAction: ajaxurl,
            deleteAction: ajaxurl+'?action=eme_manage_task_signups&do_action=deleteTaskSignups&eme_admin_nonce='+emetasks.translate_adminnonce
        },
        listQueryParams: function () {
            let params = {
                'action': "eme_task_signups_list",
                'eme_admin_nonce': emetasks.translate_adminnonce,
                'search_name': $('#search_name').val(),
                'search_event': $('#search_event').val(),
                'search_eventid': $('#search_eventid').val(),
                'search_person': $('#search_person').val(),
                'search_scope': $('#search_scope').val(),
                'search_start_date': $('#search_start_date').val(),
                'search_end_date': $('#search_end_date').val(),
                'search_signup_status': $('#search_signup_status').val()
            }
            return params;
        },

        fields: {
            id: {
                key: true,
                width: '1%',
                columnResizable: false,
                list: false,
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
            },
        },
        sortingInfoSelector: '#tasksignupstablesortingInfo',
        messages: {
            'sortingInfoNone': ''
        }
    });

    if ($('#TaskSignupsTableContainer').length) {
        $('#TaskSignupsTableContainer').jtable('load');
        $('<div id="tasksignupstablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#TaskSignupsTableContainer');
    }

    function updateShowHideStuff () {
        let action=$('select#eme_admin_action').val();
        if ($.inArray(action,['approveTaskSignups','deleteTaskSignups']) >= 0) {
            $('span#span_sendmails').show();
        } else {
            $('span#span_sendmails').hide();
        }
    }
    $('select#eme_admin_action').on("change",updateShowHideStuff);
    updateShowHideStuff();

    // Actions button
    $('#TaskSignupsActionsButton').on("click",function (e) {
        e.preventDefault();
        let selectedRows = $('#TaskSignupsTableContainer').jtable('selectedRows');
        let do_action = $('#eme_admin_action').val();
        let send_mail = $('#send_mail').val();
        let action_ok=1;
        if (selectedRows.length > 0 && do_action != '') {
            if ((do_action=='deleteTaskSignups') && !confirm(emetasks.translate_areyousuretodeleteselected)) {
                action_ok=0;
            }
            if (action_ok==1) {
                $('#TaskSignupsActionsButton').text(emetasks.translate_pleasewait);
                let ids = [];
                selectedRows.each(function () {
                    ids.push($(this).attr('data-record-key'));
                });
                let idsjoined = ids.join(); //will be such a string '2,5,7'

                if (do_action=='sendMails') {
                    form = $('<form method="POST" action="'+emetasks.translate_admin_sendmails_url+'">');
                    params = {
                        'tasksignup_ids': idsjoined,
                        'eme_admin_action': 'new_mailing'
                    };
                    $.each(params, function(k, v) { 
                        form.append($('<input type="hidden" name="' + k + '" value="' + v + '">')); 
                    });
                    $('body').append(form);
                    form.trigger("submit");
                    return false;
                }

                $.post(ajaxurl, {'id': idsjoined, 'action': 'eme_manage_task_signups', 'send_mail': send_mail, 'do_action': do_action, 'eme_admin_nonce': emetasks.translate_adminnonce }, function(data) {
                    $('#TaskSignupsTableContainer').jtable('reload');
                    $('#TaskSignupsActionsButton').text(emetasks.translate_apply);
                    $('div#tasksignups-message').html(data.htmlmessage);
                    $('div#tasksignups-message').show();
                    $('div#tasksignups-message').delay(5000).fadeOut('slow');
                }, 'json');
            }
        }
        // return false to make sure the real form doesn't submit
        return false;
    });
    //
    // Re-load records when user click 'load records' button.
    $('#TaskSignupsLoadRecordsButton').on("click",function (e) {
        e.preventDefault();
        $('#TaskSignupsTableContainer').jtable('load');
        // return false to make sure the real form doesn't submit
        return false;
    });
});
