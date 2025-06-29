jQuery(document).ready( function($) {
    if ($('#HolidaysTableContainer').length) {
        $('#HolidaysTableContainer').jtable({
            title: emeholidays.translate_holidaylists,
            paging: true,
            sorting: true,
            multiSorting: true,
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            defaultSorting: '',
            actions: {
                listAction: ajaxurl,
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_holidays_list",
                    'eme_admin_nonce': emeholidays.translate_adminnonce,
                }
                return params;
            },
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emeholidays.translate_id,
                },
                name: {
                    title: emeholidays.translate_name,
                },
            },
            sortingInfoSelector: '#holidaystablesortingInfo',
            messages: {
                'sortingInfoNone': ''
            }
        });
        $('#HolidaysTableContainer').jtable('load');
        $('<div id="holidaystablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#HolidaysTableContainer');

        // Actions button
        $('#HolidaysActionsButton').on("click",function (e) {
            e.preventDefault();
            let selectedRows = $('#HolidaysTableContainer').jtable('selectedRows');
            let do_action = $('#eme_admin_action').val();
            let action_ok=1;
            if (selectedRows.length > 0 && do_action != '') {
                if ((do_action=='deleteHolidays') && !confirm(emeholidays.translate_areyousuretodeleteselected)) {
                    action_ok=0;
                }
                if (action_ok==1) {
                    $('#HolidaysActionsButton').text(emeholidays.translate_pleasewait);
                    $('#HolidaysActionsButton').prop('disabled', true);
                    let ids = [];
                    selectedRows.each(function () {
                        ids.push($(this).attr('data-record-key'));
                    });

                    let idsjoined = ids.join(); //will be such a string '2,5,7'
                    let params = {
                        'holidays_ids': idsjoined,
                        'action': 'eme_manage_holidays',
                        'do_action': do_action,
                        'eme_admin_nonce': emeholidays.translate_adminnonce };

                    $.post(ajaxurl, params, function(data) {
                        $('#HolidaysTableContainer').jtable('reload');
                        $('#HolidaysActionsButton').text(emeholidays.translate_apply);
                        $('#HolidaysActionsButton').prop('disabled', false);
                        $('div#holidays-message').html(data.htmlmessage);
                        $('div#holidays-message').show();
                        $('div#holidays-message').delay(3000).fadeOut('slow');
                    }, 'json');
                }
            }
            // return false to make sure the real form doesn't submit
            return false;
        });

        // Re-load records when user click 'load records' button.
        $('#HolidaysLoadRecordsButton').on("click",function (e) {
            e.preventDefault();
            $('#HolidaysTableContainer').jtable('load');
            // return false to make sure the real form doesn't submit
            return false;
        });
    }
});
