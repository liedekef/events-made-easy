jQuery(document).ready(function ($) { 
    function eme_dynamic_people_data_json(form_id) {
        if ($('div#eme_dynpersondata').length) {
            let alldata = new FormData($('#'+form_id)[0]);
            alldata.append('action', 'eme_people_dyndata');
            alldata.append('eme_admin_nonce', emepeople.translate_adminnonce);
            $('div#eme_dynpersondata').html('<img src="'+emepeople.translate_plugin_url+'images/spinner.gif">');
            $.ajax({url: ajaxurl, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
                .done(function(data){
                    $('div#eme_dynpersondata').html(data.Result);
                    // make sure to init select2 for dynamic added fields
                    if ($('.eme_select2.dynamicfield').length) {
                        $('.eme_select2.dynamicfield').select2({dropdownAutoWidth: true, width: 'style'});
                    }
                    if ($('.eme_select2_width50_class.dynamicfield').length) {
                        $('.eme_select2_width50_class.dynamicfield').select2({dropdownAutoWidth: true, width: '50%'});
                    }
                    if ($('.eme_formfield_fdate.dynamicfield').length) {
                        $('.eme_formfield_fdate.dynamicfield').fdatepicker({ 
                            todayButton: new Date(),
                            clearButton: true,
                            language: emepeople.translate_flanguage,
                            firstDay: parseInt(emepeople.translate_firstDayOfWeek),
                            altFieldDateFormat: 'Y-m-d',
                            dateFormat: emepeople.translate_fdateformat
                        });
                        $.each($('.eme_formfield_fdate.dynamicfield'), function() {
                            if ($(this).data('date') && $(this).data('date') != '0000-00-00') {
                                $(this).fdatepicker().data('fdatepicker').selectDate($(this).data('date'));
                                // to avoid it being done multiple times
                                $(this).removeData('date');
                                $(this).removeAttr('date');
                            }
                            if ($(this).data('dateFormat')) {
                                $(this).fdatepicker().data('fdatepicker').update('dateFormat', $(this).data('dateFormat'));
                                // to avoid it being done multiple times
                                $(this).removeData('dateFormat');
                                $(this).removeAttr('dateFormat');
                            }
                        });
                    }
                    if ($('.eme_formfield_fdatetime.dynamicfield').length) {
                        $('.eme_formfield_fdatetime.dynamicfield').fdatepicker({
                            todayButton: new Date(),
                            clearButton: true,
                            closeButton: true,
                            timepicker: true,
                            minutesStep: parseInt(emepeople.translate_minutesStep),
                            language: emepeople.translate_flanguage,
                            firstDay: parseInt(emepeople.translate_firstDayOfWeek),
                            altFieldDateFormat: 'Y-m-d H:i:00',
                            dateFormat: emepeople.translate_fdateformat,
                            timeFormat: emepeople.translate_ftimeformat
                        });
                        $.each($('.eme_formfield_fdatetime.dynamicfield'), function() {
                            if ($(this).data('date') && $(this).data('date') != '0000-00-00 00:00:00' ) {
                                $(this).fdatepicker().data('fdatepicker').selectDate($(this).data('date'));
                                // to avoid it being done multiple times
                                $(this).removeData('date');
                                $(this).removeAttr('date');
                            }
                            if ($(this).data('dateFormat')) {
                                $(this).fdatepicker().data('fdatepicker').update('dateFormat', $(this).data('dateFormat'));
                                // to avoid it being done multiple times
                                $(this).removeData('dateFormat');
                                $(this).removeAttr('dateFormat');
                            }
                            if ($(this).data('timeFormat')) {
                                $(this).fdatepicker().data('fdatepicker').update('timeFormat', $(this).data('timeFormat'));
                                // to avoid it being done multiple times
                                $(this).removeData('timeFormat');
                                $(this).removeAttr('timeFormat');
                            }
                        });
                    }
                    if ($('.eme_formfield_timepicker.dynamicfield').length) {
                        $('.eme_formfield_timepicker.dynamicfield').timepicker({
                            timeFormat: emepeople.translate_ftimeformat
                        });
                        $.each($('.eme_formfield_timepicker'), function() {
                            if ($(this).data('timeFormat')) {
                                $(this).timepicker('option', { 'timeFormat': $(this).data('timeFormat') });
                                // to avoid it being done multiple times
                                $(this).removeData('timeFormat');
                                $(this).removeAttr('timeFormat');
                            }
                        });
                    }
                });
        }
    }


    if ($('#PeopleTableContainer').length) {
        let personfields = {
            'people.person_id': {
                key: true,
                title: emepeople.translate_personid,
                width: '1%',
                columnResizable: false,
                visibility: 'hidden'
            },
            'people.lastname': {
                title: emepeople.translate_lastname,
                inputClass: 'validate[required]'
            },
            'people.firstname': {
                title: emepeople.translate_firstname
            },
            'people.address1': {
                title: emepeople.translate_address1,
                visibility: 'hidden'
            },
            'people.address2': {
                title: emepeople.translate_address2,
                visibility: 'hidden'
            },
            'people.city': {
                title: emepeople.translate_city,
                visibility: 'hidden'
            },
            'people.zip': {
                title: emepeople.translate_zip,
                visibility: 'hidden'
            },
            'people.state': {
                title: emepeople.translate_state,
                visibility: 'hidden'
            },
            'people.country': {
                title: emepeople.translate_country,
                visibility: 'hidden'
            },
            'people.email': {
                title: emepeople.translate_email,
                inputClass: 'validate[required]'
            },
            'people.phone': {
                title: emepeople.translate_phone,
                visibility: 'hidden'
            },
            'people.birthdate': {
                title: emepeople.translate_birthdate,
                visibility: 'hidden'
            },
            'people.birthplace': {
                title: emepeople.translate_birthplace,
                visibility: 'hidden'
            },
            'people.lang': {
                title: emepeople.translate_lang,
                visibility: 'hidden',
            },
            'people.massmail': {
                title: emepeople.translate_massmail,
                visibility: 'hidden'
            },
            'people.bd_email': {
                title: emepeople.translate_bd_email,
                visibility: 'hidden'
            },
            'people.gdpr': {
                title: emepeople.translate_gdpr,
                visibility: 'hidden'
            },
            'people.gdpr_date': {
                title: emepeople.translate_gdpr_date,
                visibility: 'hidden'
            },
            'people.creation_date': {
                title: emepeople.translate_created_on,
                visibility: 'hidden'
            },
            'people.modif_date': {
                title: emepeople.translate_modified_on,
                visibility: 'hidden'
            },
            'people.related_to': {
                title: emepeople.translate_related_to,
                sorting: false,
                visibility: 'hidden'
            },
            'people.groups': {
                title: emepeople.translate_persongroups,
                sorting: false,
                visibility: 'hidden'
            },
            'people.memberships': {
                title: emepeople.translate_personmemberships,
                sorting: false,
                visibility: 'hidden'
            },
            'people.wp_user': {
                title: emepeople.translate_wpuser,
                sorting: false,
                visibility: 'hidden'
            },
            'bookingsmade': {
                title: emepeople.translate_bookingsmade,
                sorting: false,
                visibility: 'hidden',
                display: function (data) {
                    return '<a href="admin.php?page=eme-registration-seats&person_id='+ data.record['people.person_id']+'">' + emepeople.translate_showallbookings + '</a>';
                }
            }
        }
        let extrafields=$('#PeopleTableContainer').data('extrafields').toString().split(',');
        let extrafieldnames=$('#PeopleTableContainer').data('extrafieldnames').toString().split(',');
        let extrafieldsearchable=$('#PeopleTableContainer').data('extrafieldsearchable').toString().split(',');
        $.each(extrafields, function( index, value ) {
            if (value != '') {
                let fieldindex='FIELD_'+value;
                let extrafield = {}
                if (extrafieldsearchable[index]=='1') {
                    sorting=true;
                } else {
                    sorting=false;
                }
                extrafield[fieldindex] = {
                    title: extrafieldnames[index],
                    sorting: sorting,
                    visibility: 'hidden'
                };
                $.extend(personfields,extrafield);
            }
        });

        //Prepare jtable plugin
        $('#PeopleTableContainer').jtable({
            title: emepeople.translate_people,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'people.lastname ASC, people.firstname ASC',
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            csvExport: true,
            printTable: true,
            actions: {
                listAction: ajaxurl
            },
            listQueryParams: function () {
                let exactmatch=0;
                if ($('#search_exactmatch').prop("checked")) {
                    exactmatch = 1;
                }
                let params = {
                    'action': "eme_people_list",
                    'eme_admin_nonce': emepeople.translate_adminnonce,
                    'trash': $_GET['trash'],
                    'search_person': $('#search_person').val(),
                    'search_groups': $('#search_groups').val(),
                    'search_memberstatus': $('#search_memberstatus').val(),
                    'search_membershipids': $('#search_membershipids').val(),
                    'search_customfields': $('#search_customfields').val(),
                    'search_customfieldids': $('#search_customfieldids').val(),
                    'search_exactmatch': exactmatch
                }
                return params;
            },

            fields: personfields,
            sortingInfoSelector: '#peopletablesortingInfo',
            messages: {
                'sortingInfoNone': ''
            }
        });
        $('#PeopleTableContainer').jtable('load');
        $('<div id="peopletablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#PeopleTableContainer');
    }

    if ($('#GroupsTableContainer').length) {
        $('#GroupsTableContainer').jtable({
            title: emepeople.translate_groups,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            actions: {
                listAction: ajaxurl+'?action=eme_groups_list&eme_admin_nonce='+emepeople.translate_adminnonce,
                deleteAction: ajaxurl+'?action=eme_manage_groups&do_action=deleteGroups&eme_admin_nonce='+emepeople.translate_adminnonce,
            },
            fields: {
                'group_id': {
                    title: emepeople.translate_groupid,
                    key: true,
                    create: false,
                    edit: false,
                    visibility: 'hidden'
                },
                'name': {
                    title: emepeople.translate_name,
                    inputClass: 'validate[required]'
                },
                'description': {
                    title: emepeople.translate_description
                },
                'public': {
                    title: emepeople.translate_publicgroup,
                    visibility: 'hidden'
                },
                'groupcount': {
                    title: emepeople.translate_groupcount,
                    sorting: false
                }
            }
        });

        $('#GroupsTableContainer').jtable('load');

        // Actions button
        $('#GroupsActionsButton').on("click",function (e) {
            e.preventDefault();
            let selectedRows = $('#GroupsTableContainer').jtable('selectedRows');
            let do_action = $('#eme_admin_action').val();
            let action_ok=1;
            if (selectedRows.length > 0 && do_action != '') {
                if ((do_action=='deleteGroups') && !confirm(emepeople.translate_areyousuretodeleteselected)) {
                    action_ok=0;
                }
                if (action_ok==1) {
                    $('#GroupsActionsButton').text(emepeople.translate_pleasewait);
                    $('#GroupsActionsButton').prop('disabled', true);
                    let ids = [];
                    selectedRows.each(function () {
                        ids.push($(this).attr('data-record-key'));
                    });

                    let idsjoined = ids.join(); //will be such a string '2,5,7'
                    $.post(ajaxurl, {'group_id': idsjoined, 'action': 'eme_manage_groups', 'do_action': do_action, 'eme_admin_nonce': emepeople.translate_adminnonce }, function(data) {
                        $('#GroupsTableContainer').jtable('reload');
                        $('#GroupsActionsButton').text(emepeople.translate_apply);
                        $('#GroupsActionsButton').prop('disabled', false);
                        if (do_action=='deleteGroups') {
                            $('div#groups-message').html(data.htmlmessage);
                            $('div#groups-message').show();
                            $('div#groups-message').delay(5000).fadeOut('slow');
                        }
                    }, 'json');
                }
            }
            // return false to make sure the real form doesn't submit
            return false;
        });
    }

    // Actions button
    $('#PeopleActionsButton').on("click",function (e) {
        e.preventDefault();
        let selectedRows = $('#PeopleTableContainer').jtable('selectedRows');
        let do_action = $('#eme_admin_action').val();

        let action_ok=1;
        if (selectedRows.length > 0 && do_action != '') {
            if ((do_action=='trashPeople' || do_action=='deletePeople') && !confirm(emepeople.translate_areyousuretodeleteselected)) {
                action_ok=0;
            }
            if (action_ok==1) {
                $('#PeopleActionsButton').text(emepeople.translate_pleasewait);
                $('#PeopleActionsButton').prop('disabled', true);
                let ids = [];
                selectedRows.each(function () {
                    ids.push($(this).attr('data-record-key'));
                });

                let idsjoined = ids.join(); //will be such a string '2,5,7'
                let form;
                let params = {
                    'person_id': idsjoined,
                    'action': 'eme_manage_people',
                    'do_action': do_action,
                    'chooseperson': $('#chooseperson').val(),
                    'transferto_id': $('#transferto_id').val(),
                    'language': $('#language').val(),
                    'pdf_template': $('#pdf_template').val(),
                    'pdf_template_header': $('#pdf_template_header').val(),
                    'pdf_template_footer': $('#pdf_template_footer').val(),
                    'html_template': $('#html_template').val(),
                    'html_template_header': $('#html_template_header').val(),
                    'html_templata_footer': $('#html_template_footer').val(),
                    'addtogroup': $('#addtogroup').val(),
                    'removefromgroup': $('#removefromgroup').val(),
                    'eme_admin_nonce': emepeople.translate_adminnonce };

                if (do_action=='sendMails') {
                    form = $('<form method="POST" action="'+emepeople.translate_admin_sendmails_url+'">');
                    params = {
                        'person_ids': idsjoined,
                        'eme_admin_action': 'new_mailing'
                    };
                    $.each(params, function(k, v) {
                        form.append($('<input type="hidden" name="' + k + '" value="' + v + '">'));
                    });
                    $('body').append(form);
                    form.trigger("submit");
                    return false;
                }
                if (do_action=='pdf' || do_action=='html') {
                    form = $('<form method="POST" action="' + ajaxurl + '">');
                    $.each(params, function(k, v) {
                        form.append($('<input type="hidden" name="' + k + '" value="' + v + '">'));
                    });
                    $('body').append(form);
                    form.trigger("submit");
                    $('#PeopleActionsButton').text(emepeople.translate_apply);
                    $('#PeopleActionsButton').prop('disabled', false);
                    return false;
                }
                $.post(ajaxurl, params, function(data) {
                    $('#PeopleTableContainer').jtable('reload');
                    $('#PeopleActionsButton').text(emepeople.translate_apply);
                    $('#PeopleActionsButton').prop('disabled', false);
                    $('div#people-message').html(data.htmlmessage);
                    $('div#people-message').show();
                    $('div#people-message').delay(5000).fadeOut('slow');
                }, 'json');
            }
        }
        // return false to make sure the real form doesn't submit
        return false;
    });

    // Re-load records when user click 'load records' button.
    $('#PeopleLoadRecordsButton').on("click",function (e) {
        e.preventDefault();
        $('#PeopleTableContainer').jtable('load');
        if ($('#search_person').val().length || $('#search_groups').val().length || $('#search_memberstatus').val().length || $('#search_membershipids').val().length || $('#search_customfields').val().length || $('#search_customfieldids').val().length) {
            $('#StoreQueryButton').show();
        } else {
            $('#StoreQueryButton').hide();
        }
        $('#StoreQueryDiv').hide();
        // return false to make sure the real form doesn't submit
        return false;
    });
    $('#StoreQueryButton').on("click",function (e) {
        e.preventDefault();
        $('#StoreQueryButton').hide();
        $('#StoreQueryDiv').show();
        // return false to make sure the real form doesn't submit
        return false;
    });
    $('#StoreQuerySubmitButton').on("click",function (e) {
        e.preventDefault();
        let exactmatch=0;
        if ($('#search_exactmatch').prop("checked")) {
            exactmatch = 1;
        }
        let params = {
            'search_person': $('#search_person').val(),
            'search_groups': $('#search_groups').val(),
            'search_memberstatus': $('#search_memberstatus').val(),
            'search_membershipids': $('#search_membershipids').val(),
            'search_customfields': $('#search_customfields').val(),
            'search_customfieldids': $('#search_customfieldids').val(),
            'search_exactmatch': exactmatch,
            'action': 'eme_store_people_query',
            'eme_admin_nonce': emepeople.translate_adminnonce,
            'dynamicgroupname': $('#dynamicgroupname').val()
        };
        $.post(ajaxurl, params, function(data) {
            $('#StoreQueryButton').hide();
            $('#StoreQueryDiv').hide();
            $('div#people-message').html(data.htmlmessage);
            $('div#people-message').show();
            $('div#people-message').delay(5000).fadeOut('slow');
        }, 'json');
        // return false to make sure the real form doesn't submit
        return false;
    });
    $('#StoreQueryButton').hide();
    $('#StoreQueryDiv').hide();

    function updateShowHideStuff () {
        let action=$('select#eme_admin_action').val();

        if (action == 'changeLanguage') {
            $('span#span_language').show();
        } else {
            $('span#span_language').hide();
        }
        if (action == 'trashPeople' || action == 'deletePeople') {
            $('span#span_transferto').show();
        } else {
            $('span#span_transferto').hide();
        }
        if (action == 'addToGroup') {
            $('span#span_addtogroup').show();
        } else {
            $('span#span_addtogroup').hide();
        }
        if (action == 'removeFromGroup') {
            $('span#span_removefromgroup').show();
        } else {
            $('span#span_removefromgroup').hide();
        }
        if (action == 'pdf') {
            $('span#span_pdftemplate').show();
        } else {
            $('span#span_pdftemplate').hide();
        }
        if (action == 'html') {
            $('span#span_htmltemplate').show();
        } else {
            $('span#span_htmltemplate').hide();
        }
    }
    updateShowHideStuff();
    $('select#eme_admin_action').on("change",updateShowHideStuff);

    if ($('#editperson').length) {
        $('#editperson').on('change','select.dyngroups', function() {
            eme_dynamic_people_data_json('editperson');
        });
        eme_dynamic_people_data_json('editperson');
    }

    // for autocomplete to work, the element needs to exist, otherwise JS errors occur
    // we check for that using length
    if ($('input[name=chooserelatedperson]').length) {
        let emeadmin_chooserelatedperson_timeout; // Declare a variable to hold the timeout ID
        $("input[name=chooserelatedperson]").on("input", function(e) {
            clearTimeout(emeadmin_chooserelatedperson_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                emeadmin_chooserelatedperson_timeout = setTimeout(function() {
                    $.post(ajaxurl,
                        { 
                            'lastname': inputValue,
                            'eme_admin_nonce': emepeople.translate_adminnonce,
                            'action': 'eme_autocomplete_people',
                            'eme_searchlimit': 'people',
                            'exclude_personids': $('input[name=person_id]').val()
                        },
                        function(data) {
                            suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                            $.each(data, function(index, item) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+'</strong><br><small>'+eme_htmlDecode(item.email)+'</small>')
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        if (item.person_id) {
                                            $('input[name=related_person_id]').val(eme_htmlDecode(item.person_id));
                                            inputField.val(eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+' ('+eme_htmlDecode(item.email)+')  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+emepeople.translate_nomatchperson+'</strong>')
                                );
                            }
                            $('.eme-autocomplete-suggestions').remove();
                            inputField.after(suggestions);
                        }, "json");
                }, 500); // Delay of 0.5 second
            }
        });
        $(document).on("click", function() {
            $(".eme-autocomplete-suggestions").remove();
        });

        // if manual input: set the hidden field empty again
        $('input[name=chooserelatedperson]').on("keyup",function() {
            $('input[name=related_person_id]').val('');
        }).change(function() {
            if ($(this).val()=='') {
                $('input[name=related_person_id]').val('');
                $(this).attr('readonly', false).removeClass('clearable');
            }
        });
    }

    // for autocomplete to work, the element needs to exist, otherwise JS errors occur
    // we check for that using length
    if ($('input[name=chooseperson]').length) {
        let emeadmin_chooseperson_timeout; // Declare a variable to hold the timeout ID
        $("input[name=chooseperson]").on("input", function(e) {
            clearTimeout(emeadmin_chooseperson_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                emeadmin_chooseperson_timeout = setTimeout(function() {
                    let idsjoined = "";
                    // let's get the list if selected people, so we can exclude these
                    if ($('#PeopleTableContainer').length) {
                        let selectedRows = $('#PeopleTableContainer').jtable('selectedRows');
                        if (selectedRows.length > 0) {
                            let ids = [];
                            selectedRows.each(function () {
                                ids.push($(this).data('record')['people.person_id']);
                            });
                            idsjoined = ids.join(); //will be such a string '2,5,7'
                        }
                    }
                    $.post(ajaxurl,
                        { 
                            'lastname': inputValue,
                            'eme_admin_nonce': emepeople.translate_adminnonce,
                            'action': 'eme_autocomplete_people',
                            'eme_searchlimit': 'people',
                            'exclude_personids': idsjoined
                        },
                        function(data) {
                            suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                            $.each(data, function(index, item) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+'</strong><br><small>'+eme_htmlDecode(item.email)+'</small>')
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        if (item.person_id) {
                                            $('input[name=transferto_id]').val(eme_htmlDecode(item.person_id));
                                            inputField.val(eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+' ('+eme_htmlDecode(item.person_id)+')  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+emepeople.translate_nomatchperson+'</strong>')
                                );
                            }
                            $('.eme-autocomplete-suggestions').remove();
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
            $('input[name=transferto_id]').val('');
        }).change(function() {
            if ($(this).val()=='') {
                $('input[name=transferto_id]').val('');
                $(this).attr('readonly', false).removeClass('clearable');
            }
        });
    }
});
