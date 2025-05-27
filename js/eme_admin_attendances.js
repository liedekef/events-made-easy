jQuery(document).ready( function($) {

    if ($('#AttendancesTableContainer').length) {
        $('#AttendancesTableContainer').jtable({
            title: emeattendances.translate_attendance_reports,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'creation_date ASC',
            deleteConfirmation: function(data) {
                data.deleteConfirmMessage = emeattendances.translate_areyousuretodeletethis;
            },
            actions: {
                listAction: ajaxurl,
                deleteAction: ajaxurl+'?action=eme_manage_attendances&do_action=deleteAttendances&eme_admin_nonce='+emeattendances.translate_adminnonce
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_attendances_list",
                    'eme_admin_nonce': emeattendances.translate_adminnonce,
                    'search_type': $('#search_type').val(),
                    'search_start_date': $('#search_start_date').val(),
                    'search_end_date': $('#search_end_date').val()
                }
                return params;
            },
            fields: {
                id: {
                    key: true,
                    visibility: 'hidden',
                    title: emeattendances.translate_id
                },
                creation_date: {
                    title: emeattendances.translate_attendancedate
                },
                type: {
                    title: emeattendances.translate_type
                },
                person: {
                    sorting: false,
                    title: emeattendances.translate_personinfo
                },
                related_name: {
                    sorting: false,
                    title: emeattendances.translate_name
                },
            },
            toolbar: {
                items: [{
                    text: emeattendances.translate_csv,
                    click: function () {
                        eme_jtable_csv('#AttendancesTableContainer','attendences');
                    }
                },
                    {
                        text: emeattendances.translate_print,
                        click: function () {
                            eme_printTable('#AttendancesTableContainer');
                        }
                    }
                ]
            },
            sortingInfoSelector: '#attendancestablesortingInfo',
            messages: {
                'sortingInfoNone': ''
            }
        });

        $('#AttendancesTableContainer').jtable('load');
        $('<div id="attendancestablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#AttendancesTableContainer');
    }

    // Re-load records when user click 'load records' button.
    $('#AttendancesLoadRecordsButton').on("click",function (e) {
        e.preventDefault();
        $('#AttendancesTableContainer').jtable('load');
        // return false to make sure the real form doesn't submit
        return false;
    });

    // for autocomplete to work, the element needs to exist, otherwise JS errors occur
    // we check for that using length
    if ($('input[name=chooseperson]').length) {
        let emeadmin_attendance_timeout; // Declare a variable to hold the timeout ID
        $("input[name=chooseperson]").on("input", function(e) {
            clearTimeout(emeadmin_attendance_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                emeadmin_attendance_timeout = setTimeout(function() {
                    $.post(ajaxurl,
                        { 
                            'lastname': inputValue,
                            'eme_admin_nonce': emeattendances.translate_adminnonce,
                            'action': 'eme_autocomplete_people',
                            'eme_searchlimit': 'people'
                        },
                        function(data) {
                            suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                            $.each(data, function(index, item) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+'</strong><br /><small>'+eme_htmlDecode(item.email)+'</small>')
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        if (item.person_id) {
                                            $('input[name=person_id]').val(eme_htmlDecode(item.person_id));
                                            inputField.val(eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname) +' ('+eme_htmlDecode(item.person_id)+')  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+emeattendances.translate_nomatchperson+'</strong>')
                                );
                            }
                            $(".eme-autocomplete-suggestions").remove();
                            inputField.after(suggestions);
                        }, "json");
                }, 500); // Delay of 0.5 second
            }
        });
        $(document).on("click", function() {
            $(".eme-autocomplete-suggestions").remove();
        });

        // if manual input: set the hidden field empty again
        $('input[name=chooseperson]').on("keyup",function() {
            $('input[name=person_id]').val('');
        }).change(function() {
            if ($(this).val()=='') {
                $('input[name=person_id]').val('');
                $(this).attr('readonly', false).removeClass('clearable');
            }
        });
    }
});
