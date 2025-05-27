jQuery(document).ready( function($) {
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
                    $.post(ajaxurl,
                        { 
                            'lastname': inputValue,
                            'eme_admin_nonce': ememails.translate_adminnonce,
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
                                            $('input[name=send_previewmailto_id]').val(eme_htmlDecode(item.person_id));
                                            inputField.val(eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+'  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+ememails.translate_nomatchperson+'</strong>')
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
            $('input[name=send_previewmailto_id]').val('');
        }).change(function() {
            if ($(this).val()=='') {
                $('input[name=send_previewmailto_id]').val('');
                $(this).attr('readonly', false).removeClass('clearable');
            }
        });
    }

    if ($('input[name=eventmail_chooseperson]').length) {
        let emeadmin_eventmailchooseperson_timeout; // Declare a variable to hold the timeout ID
        $("input[name=eventmail_chooseperson]").on("input", function(e) {
            clearTimeout(emeadmin_eventmailchooseperson_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                emeadmin_eventmailchooseperson_timeout = setTimeout(function() {
                    $.post(ajaxurl,
                        { 
                            'lastname': inputValue,
                            'eme_admin_nonce': ememails.translate_adminnonce,
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
                                            $('input[name=send_previeweventmailto_id]').val(eme_htmlDecode(item.person_id));
                                            inputField.val(eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+'  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+ememails.translate_nomatchperson+'</strong>')
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
        $('input[name=eventmail_chooseperson]').on("keyup",function() {
            $('input[name=send_previeweventmailto_id]').val('');
        }).change(function() {
            if ($(this).val()=='') {
                $('input[name=send_previeweventmailto_id]').val('');
                $(this).attr('readonly', false).removeClass('clearable');
            }
        });
    }

    $('#eventmailButton').on("click",function (e) {
        e.preventDefault();
        // if we want html mail, we need to save the html message first, otherwise the mail content is not ok via ajax submit
        if (ememails.translate_htmleditor=='tinymce' && ememails.translate_htmlmail=='yes') {
            tinymce.get('event_mail_message')?.save();
        }

        let $form = $(this.form);
        let $alldata = new FormData($form[0]);
        $alldata.append('action', 'eme_eventmail');
        $alldata.append('eme_admin_nonce', ememails.translate_adminnonce);
        $('#eventmailButton').text(ememails.translate_pleasewait);
        $('#eventmailButton').prop('disabled', true);
        $.ajax({url: ajaxurl, data: $alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#eventmail-message').html(data.htmlmessage);
                $('div#eventmail-message').show();
                if (data.Result=='OK') {
                    $form.trigger('reset');
                    // the form reset doesn't reset select2 fields ...
                    // so we call it ourselves
                    $('#event_ids').val(null).trigger("change");
                    $("#eme_eventmail_send_persons").val(null).trigger("change");
                    $("#eme_eventmail_send_groups").val(null).trigger("change");
                    $("#eme_eventmail_send_members").val(null).trigger("change");
                    $("#eme_eventmail_send_membergroups").val(null).trigger("change");
                    $("#eme_eventmail_send_memberships").val(null).trigger("change");
                    $("#eme_mail_type").val(null).trigger("change");
                    $('div#eventmail-message').delay(5000).fadeOut('slow');
                }
                $('#eventmailButton').text(ememails.translate_sendmail);
                $('#eventmailButton').prop('disabled', false);
            });
        return false;
    });

    $('#genericmailButton').on("click",function (e) {
        e.preventDefault();
        // if we want html mail, we need to save the html message first, otherwise the mail content is not ok via ajax submit
        if (ememails.translate_htmleditor=='tinymce' && ememails.translate_htmlmail=='yes') {
            tinymce.get('generic_mail_message')?.save();
        }
        let $form = $(this.form);
        let $alldata = new FormData($form[0]);
        $alldata.append('action', 'eme_genericmail');
        $alldata.append('eme_admin_nonce', ememails.translate_adminnonce);
        $('#genericmailButton').text(ememails.translate_pleasewait);
        $('#genericmailButton').prop('disabled', true);
        $.ajax({url: ajaxurl, data: $alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#genericmail-message').html(data.htmlmessage);
                $('div#genericmail-message').show();
                if (data.Result=='OK') {
                    $form.trigger('reset');
                    // the form reset doesn't reset select2 fields ...
                    // so we call it ourselves
                    $("#eme_genericmail_send_persons").val(null).trigger("change");
                    $("#eme_genericmail_send_peoplegroups").val(null).trigger("change");
                    $("#eme_genericmail_send_members").val(null).trigger("change");
                    $("#eme_genericmail_send_membergroups").val(null).trigger("change");
                    $("#eme_send_memberships").val(null).trigger("change");
                    // the form reset doesn't reset other show/hide stuff apparently ...
                    // so we call it ourselves
                    $('input#eme_send_all_people').trigger('change');
                    $('div#genericmail-message').delay(5000).fadeOut('slow');
                }
                $('#genericmailButton').text(ememails.translate_sendmail);
                $('#genericmailButton').prop('disabled', false);
            });
        return false;
    });

    $('#previeweventmailButton').on("click",function (e) {
        e.preventDefault();
        // if we want html mail, we need to save the html message first, otherwise the mail content is not ok via ajax submit
        if (ememails.translate_htmleditor=='tinymce' && ememails.translate_htmlmail=='yes') {
            tinymce.get('event_mail_message')?.save();
        }
        let $form = $(this.form);
        let $alldata = new FormData($form[0]);
        $alldata.append('action', 'eme_previeweventmail');
        $alldata.append('eme_admin_nonce', ememails.translate_adminnonce);
        $.ajax({url: ajaxurl, data: $alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#previeweventmail-message').html(data.htmlmessage);
                $('div#previeweventmail-message').show();
                $('div#previeweventmail-message').delay(5000).fadeOut('slow');
                if (data.Result=='OK') {
                    $('input[name=eventmail_chooseperson]').val('');
                    $('input[name=send_previeweventmailto_id]').val('');
                    $('input[name=eventmail_chooseperson]').attr('readonly', false);
                }
            });
        return false;
    });
    $('#previewmailButton').on("click",function (e) {
        e.preventDefault();
        // if we want html mail, we need to save the html message first, otherwise the mail content is not ok via ajax submit
        if (ememails.translate_htmleditor=='tinymce' && ememails.translate_htmlmail=='yes') {
            tinymce.get('generic_mail_message')?.save();
        }
        let $form = $(this.form);
        let $alldata = new FormData($form[0]);
        $alldata.append('action', 'eme_previewmail');
        $alldata.append('eme_admin_nonce', ememails.translate_adminnonce);
        $.ajax({url: ajaxurl, data: $alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#previewmail-message').html(data.htmlmessage);
                $('div#previewmail-message').show();
                $('div#previewmail-message').delay(5000).fadeOut('slow');
                if (data.Result=='OK') {
                    $('input[name=chooseperson]').val('');
                    $('input[name=send_previewmailto_id]').val('');
                    $('input[name=chooseperson]').attr('readonly', false);
                }
            });
        return false;
    });

    $('#testmailButton').on("click",function (e) {
        e.preventDefault();
        let $form = $(this.form);
        let $alldata = new FormData($form[0]);
        $alldata.append('action', 'eme_testmail');
        $alldata.append('eme_admin_nonce', ememails.translate_adminnonce);
        $('#testmailButton').text(ememails.translate_pleasewait);
        $('#testmailButton').prop('disabled', true);
        $.ajax({url: ajaxurl, data: $alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#testmail-message').html(data.htmlmessage);
                $('div#testmail-message').show();
                if (data.Result=='OK') {
                    $form.trigger('reset');
                }
                $('#testmailButton').text(ememails.translate_sendmail);
                $('#testmailButton').prop('disabled', false);
            });
        return false;
    });

    // show selected template in form
    $('select#event_subject_template').on("change",function (e) {
        e.preventDefault();
        $.post(ajaxurl,
            { action: 'eme_get_template',
                'eme_admin_nonce': ememails.translate_adminnonce,
                template_id: $('select#event_subject_template').val(),
            },
            function(data){
                $('input#event_mail_subject').val(data.htmlmessage);
            }, 'json');

    });

    // show selected template in form
    $('select#event_message_template').on("change",function (e) {
        e.preventDefault();
        $.post(ajaxurl,
            { action: 'eme_get_template',
                'eme_admin_nonce': ememails.translate_adminnonce,
                template_id: $('select#event_message_template').val(),
            },
            function(data){
                if (ememails.translate_htmleditor=='tinymce') {
	            $('textarea#event_mail_message').val(data.htmlmessage);
                    if (ememails.translate_htmlmail=='yes') {
                        tinymce.get('event_mail_message')?.save();
                    }
                }

                if (ememails.translate_htmleditor=='jodit') {
		    const $textarea = $('textarea#event_mail_message');
		    const editorInstance = $textarea.data('joditEditor');

		    if (editorInstance) {
			    editorInstance.value = data.htmlmessage; // sets new HTML content
		    } else {
			    // fallback in case editor not yet initialized
			    $textarea.val(data.htmlmessage);
                    }
                }
            }, 'json');

    });

    // show selected template in form
    //$('select#generic_subject_template').change(function (e) {
    //       e.preventDefault();
    //	  $.post(ajaxurl,
    //		  { action: 'eme_get_template',
    //		   'eme_admin_nonce': ememails.translate_adminnonce,
    //		    template_id: $('select#generic_subject_template').val(),
    //		  },
    //		  function(data){
    //		      $('input#generic_mail_subject').val(data.htmlmessage);
    //		  }, "json");
    //  });

    // show selected template in form
    $('select#generic_message_template').on("change",function (e) {
        e.preventDefault();
        $.post(ajaxurl,
            { action: 'eme_get_template',
                'eme_admin_nonce': ememails.translate_adminnonce,
                template_id: $('select#generic_message_template').val(),
            },
            function(data){
                if (ememails.translate_htmleditor=='tinymce') {
                    $('textarea#generic_mail_message').val(data.htmlmessage);
                    if (ememails.translate_htmlmail=='yes') {
                        tinymce.get('generic_mail_message')?.save();
                    }
                }

                if (ememails.translate_htmleditor=='jodit') {
		    const $textarea = $('textarea#generic_mail_message');
		    const editorInstance = $textarea.data('joditEditor');

		    if (editorInstance) {
			    editorInstance.value = data.htmlmessage; // sets new HTML content
		    } else {
			    // fallback in case editor not yet initialized
			    $textarea.val(data.htmlmessage);
                    }
                }
            }, 'json');

    });

    function updateShowSendGroups () {
        if ($('input#eme_send_all_people').prop('checked')) {
            $('div#div_eme_send_groups').hide();
            $('div#div_eme_send_all_people').show();
        } else {
            $('div#div_eme_send_groups').show();
            $('div#div_eme_send_all_people').hide();
        }
    }
    $('input#eme_send_all_people').on("change",updateShowSendGroups);
    updateShowSendGroups();

    function updateShowMailTypes () {
        if ($('select[name=eme_mail_type]').val() == 'attendees' || $('select[name=eme_mail_type]').val() == 'bookings') {
            $('tr#eme_pending_approved_row').show();
            $('tr#eme_only_unpaid_row').show();
            if ($('select[name=eme_mail_type]').val() == 'attendees') {
                $('span#span_unpaid_attendees').show();
                $('span#span_unpaid_bookings').hide();
            } else {
                $('span#span_unpaid_attendees').hide();
                $('span#span_unpaid_bookings').show();
            }
            $('tr#eme_exclude_registered_row').hide();
            $('tr#eme_rsvp_status_row').show();
        } else {
            $('tr#eme_pending_approved_row').hide();
            $('tr#eme_only_unpaid_row').hide();
            if ($('select[name=eme_mail_type]').val() != '') {
                $('tr#eme_exclude_registered_row').show();
            } else {
                $('tr#eme_exclude_registered_row').hide();
            }
            $('tr#eme_rsvp_status_row').hide();
        }
        if ($('select[name=eme_mail_type]').val() == 'people_and_groups') {
            $('tr#eme_people_row').show();
            $('tr#eme_groups_row').show();
            $('tr#eme_members_row1').show();
            $('tr#eme_members_row2').show();
            $('tr#eme_members_row3').show();
        } else {
            $('tr#eme_people_row').hide();
            $('tr#eme_groups_row').hide();
            $('tr#eme_members_row1').hide();
            $('tr#eme_members_row2').hide();
            $('tr#eme_members_row3').hide();
        }
    }
    $('select[name=eme_mail_type]').on("change",updateShowMailTypes);
    updateShowMailTypes();

    $('.eme_select2_events_class').select2({
        ajax: {
            url: ajaxurl+'?action=eme_events_select2',
            dataType: 'json',
            delay: 500,
            data: function (params) {
                let search_all=0;
                if ($('#eventsearch_all').is(':checked')) {
                    search_all=1;
                }
                return {
                    q: params.term, // search term
                    search_all: search_all,
                    eme_admin_nonce: ememails.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                // parse the results into the format expected by Select2
                // since we are using custom formatting functions we do not need to
                // alter the remote JSON data, except to indicate that infinite
                // scrolling can be used
                return {
                    results: data.Records,
                };
            },
            cache: true
        },
        placeholder: ememails.translate_selectevents,
        width: '90%'
    });

    //Prepare jtable plugin
    if ($('#MailingReportTableContainer').length) {
        $('#MailingReportTableContainer').jtable({
            title: ememails.translate_mailingreport,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'sent_datetime ASC',
            actions: {
                listAction: ajaxurl,
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_mailingreport_list",
                    'eme_admin_nonce': ememails.translate_adminnonce,
                    'mailing_id': parseInt($_GET['id']),
                    'search_name': $('#search_name').val()
                }
                return params;
            },
            fields: {
                receiveremail: {
                    title: ememails.translate_email,
                    visibility: 'hidden',
                },
                receivername: {
                    title: ememails.translate_name,
                },
                status: {
                    title: ememails.translate_status,
                },
                sent_datetime: {
                    title: ememails.translate_sentdatetime,
                },
                first_read_on: {
                    title: ememails.translate_first_read_on,
                    visibility: 'hidden',
                },
                last_read_on: {
                    title: ememails.translate_last_read_on,
                    visibility: 'hidden',
                },
                read_count: {
                    title: ememails.translate_total_readcount,
                },
                error_msg: {
                    title: ememails.translate_errormessage,
                    visibility: 'hidden',
                    sorting: false
                },
                action: {
                    title: ememails.translate_action,
                    listClass: 'eme-wsnobreak',
                    visibility: 'fixed',
                    sorting: false
                }
            },
            sortingInfoSelector: '#mailingreporttablesortingInfo',
            messages: {
                'sortingInfoNone': ''
            }
        });
        $('#MailingReportTableContainer').jtable('load');
        $('<div id="mailingreporttablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#MailingReportTableContainer');

        // Re-load records when user click 'load records' button.
        $('#ReportLoadRecordsButton').on("click",function (e) {
            e.preventDefault();
            $('#MailingReportTableContainer').jtable('load');
            // return false to make sure the real form doesn't submit
            return false;
        });
    }

    if ($('#MailsTableContainer').length) {
        $('#MailsTableContainer').jtable({
            title: ememails.translate_mails,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: "creation_date DESC",
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            actions: {
                listAction: ajaxurl,
            },
            listQueryParams: function () {
                let search_failed=0;
                if ($('#search_failed').is(":checked")) {
                    search_failed = 1;
                }
                let params = {
                    'action': "eme_mails_list",
                    'search_text': $('#search_text').val(),
                    'search_failed': search_failed,
                    'eme_admin_nonce': ememails.translate_adminnonce,
                }
                return params;
            },
            fields: {
                id: {
                    title: ememails.translate_id,
                    key: true,
                    list: false,
                },
                fromemail: {
                    title: ememails.translate_senderemail,
                    visibility: 'hidden',
                },
                fromname: {
                    title: ememails.translate_sendername,
                },
                receiveremail: {
                    title: ememails.translate_recipientemail,
                },
                receivername: {
                    title: ememails.translate_recipientname,
                },
                subject: {
                    title: ememails.translate_subject,
                },
                status: {
                    title: ememails.translate_status,
                },
                creation_date: {
                    title: ememails.translate_queueddatetime,
                },
                sent_datetime: {
                    title: ememails.translate_sentdatetime,
                },
                first_read_on: {
                    title: ememails.translate_first_read_on,
                    visibility: 'hidden',
                },
                last_read_on: {
                    title: ememails.translate_last_read_on,
                    visibility: 'hidden',
                },
                read_count: {
                    title: ememails.translate_total_readcount,
                    visibility: 'hidden',
                },
                error_msg: {
                    title: ememails.translate_errormessage,
                    visibility: 'hidden',
                    sorting: false
                },
                action: {
                    title: ememails.translate_action,
                    listClass: 'eme-wsnobreak',
                    visibility: 'fixed',
                    sorting: false
                }
            },
            sortingInfoSelector: '#mailstablesortingInfo',
            messages: {
                'sortingInfoNone': ''
            }
        });
        //$('#MailsTableContainer').jtable('load');
        $('<div id="mailstablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#MailsTableContainer');

        // Actions button
        $('#MailsActionsButton').on("click",function (e) {
            e.preventDefault();
            let selectedRows = $('#MailsTableContainer').jtable('selectedRows');
            let do_action = $('#eme_admin_action_mails').val();
            let action_ok=1;
            if (selectedRows.length > 0 && do_action != '') {
                if ((do_action=='deleteMails') && !confirm(ememails.translate_areyousuretodeleteselected)) {
                    action_ok=0;
                }

                if (action_ok==1) {
                    $('#MailsActionsButton').text(ememails.translate_pleasewait);
                    $('#MailsActionsButton').prop('disabled', true);
                    let ids = [];
                    let personids = [];
                    selectedRows.each(function () {
                        ids.push($(this).attr('data-record-key'));
                        personids.push($(this).data('record')['person_id']);
                    });

                    let idsjoined = ids.join(); //will be such a string '2,5,7'
                    let params = {
                        'mail_ids': idsjoined,
                        'action': 'eme_manage_mails',
                        'do_action': do_action,
                        'eme_admin_nonce': ememails.translate_adminnonce };

                    if (do_action=='sendMails') {
                        let personidsjoined = personids.join();
                        form = $('<form method="POST" action="'+ememails.translate_admin_sendmails_url+'">');
                        params = {
                            'person_ids': personidsjoined,
                            'eme_admin_action': 'new_mailing'
                        };
                        $.each(params, function(k, v) {
                            form.append($('<input type="hidden" name="' + k + '" value="' + v + '">'));
                        });
                        $('body').append(form);
                        form.trigger("submit");
                        return false;
                    }

                    $.post(ajaxurl, params, function(data) {
                        $('#MailsTableContainer').jtable('reload');
                        $('#MailsActionsButton').text(ememails.translate_apply);
                        $('#MailsActionsButton').prop('disabled', false);
                        $('div#mails-message').html(data.htmlmessage);
                        $('div#mails-message').show();
                        $('div#mails-message').delay(3000).fadeOut('slow');
                    }, 'json');
                }
            }
            // return false to make sure the real form doesn't submit
            return false;
        });

        // Re-load records when user click 'load records' button.
        $('#MailsLoadRecordsButton').on("click",function (e) {
            e.preventDefault();
            $('#MailsTableContainer').jtable('load');
            // return false to make sure the real form doesn't submit
            return false;
        });
    }

    if ($('#MailingsTableContainer').length) {
        $('#MailingsTableContainer').jtable({
            title: ememails.translate_mailings,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'planned_on DESC, name',
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            actions: {
                listAction: ajaxurl,
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_mailings_list",
                    'search_text': $('#search_mailingstext').val(),
                    'eme_admin_nonce': ememails.translate_adminnonce,
                }
                return params;
            },
            fields: {
                id: {
                    key: true,
                    list: false,
                },
                name: {
                    title: ememails.translate_mailingname,
                },
                subject: {
                    title: ememails.translate_subject,
                },
                planned_on: {
                    title: ememails.translate_planneddatetime,
                },
                creation_date: {
                    title: ememails.translate_queueddatetime,
                    visibility: 'hidden',
                },
                status: {
                    title: ememails.translate_status,
                },
                read_count: {
                    title: ememails.translate_unique_readcount,
                    visibility: 'hidden',
                },
                total_read_count: {
                    title: ememails.translate_total_readcount,
                },
                extra_info: {
                    title: ememails.translate_extrainfo,
                    sorting: false
                },
                report: {
                    title: ememails.translate_report,
                    sorting: false
                },
                action: {
                    title: ememails.translate_action,
                    listClass: 'eme-wsnobreak',
                    visibility: 'fixed',
                    sorting: false
                }
            },
            sortingInfoSelector: '#mailingstablesortingInfo',
            messages: {
                'sortingInfoNone': ''
            }
        });
        //$('#MailingsTableContainer').jtable('load');
        $('<div id="mailingstablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#MailingsTableContainer');

        // Actions button
        $('#MailingsActionsButton').on("click",function (e) {
            e.preventDefault();
            let selectedRows = $('#MailingsTableContainer').jtable('selectedRows');
            let do_action = $('#eme_admin_action_mailings').val();
            let action_ok=1;
            if (selectedRows.length > 0 && do_action != '') {
                if ((do_action=='deleteMailings') && !confirm(ememails.translate_areyousuretodeleteselected)) {
                    action_ok=0;
                }
                if (action_ok==1) {
                    $('#MailingsActionsButton').text(ememails.translate_pleasewait);
                    $('#MailingsActionsButton').prop('disabled', true);
                    let ids = [];
                    selectedRows.each(function () {
                        ids.push($(this).attr('data-record-key'));
                    });

                    let idsjoined = ids.join(); //will be such a string '2,5,7'
                    let params = {
                        'mailing_ids': idsjoined,
                        'action': 'eme_manage_mailings',
                        'do_action': do_action,
                        'eme_admin_nonce': ememails.translate_adminnonce };

                    $.post(ajaxurl, params, function(data) {
                        $('#MailingsTableContainer').jtable('reload');
                        $('#MailingsActionsButton').text(ememails.translate_apply);
                        $('#MailingsActionsButton').prop('disabled', false);
                        $('div#mailings-message').html(data.htmlmessage);
                        $('div#mailings-message').show();
                        $('div#mailings-message').delay(5000).fadeOut('slow');
                    }, 'json');
                }
            }
            // return false to make sure the real form doesn't submit
            return false;
        });

        // Re-load records when user click 'load records' button.
        $('#MailingsLoadRecordsButton').on("click",function (e) {
            e.preventDefault();
            $('#MailingsTableContainer').jtable('load');
            // return false to make sure the real form doesn't submit
            return false;
        });
    }

    if ($('#ArchivedMailingsTableContainer').length) {
        $('#ArchivedMailingsTableContainer').jtable({
            title: ememails.translate_archivedmailings,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'planned_on DESC, name',
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            actions: {
                listAction: ajaxurl,
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_archivedmailings_list",
                    'search_text': $('#search_archivedmailingstext').val(),
                    'eme_admin_nonce': ememails.translate_adminnonce,
                }
                return params;
            },
            fields: {
                id: {
                    key: true,
                    list: false,
                },
                name: {
                    title: ememails.translate_mailingname,
                },
                subject: {
                    title: ememails.translate_subject,
                },
                planned_on: {
                    title: ememails.translate_planneddatetime,
                },
                read_count: {
                    title: ememails.translate_unique_readcount,
                    visibility: 'hidden',
                },
                total_read_count: {
                    title: ememails.translate_total_readcount,
                },
                extra_info: {
                    title: ememails.translate_extrainfo,
                    sorting: false
                },
                action: {
                    title: ememails.translate_action,
                    listClass: 'eme-wsnobreak',
                    visibility: 'fixed',
                    sorting: false
                }
            },
            sortingInfoSelector: '#archivedmailingstablesortingInfo',
            messages: {
                'sortingInfoNone': ''
            }
        });
        //$('#ArchivedMailingsTableContainer').jtable('load');
        $('<div id="archivedmailingstablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#ArchivedMailingsTableContainer');

        // Actions button
        $('#ArchivedMailingsActionsButton').on("click",function (e) {
            e.preventDefault();
            let selectedRows = $('#ArchivedMailingsTableContainer').jtable('selectedRows');
            let do_action = $('#eme_admin_action_archivedmailings').val();
            let action_ok=1;
            if (selectedRows.length > 0 && do_action != '') {
                if ((do_action=='deleteArchivedMailings') && !confirm(ememails.translate_areyousuretodeleteselected)) {
                    action_ok=0;
                }
                if (action_ok==1) {
                    $('#ArchivedMailingsActionsButton').text(ememails.translate_pleasewait);
                    $('#ArchivedMailingsActionsButton').prop('disabled', true);
                    let ids = [];
                    selectedRows.each(function () {
                        ids.push($(this).attr('data-record-key'));
                    });

                    let idsjoined = ids.join(); //will be such a string '2,5,7'
                    let params = {
                        'mailing_ids': idsjoined,
                        'action': 'eme_manage_archivedmailings',
                        'do_action': do_action,
                        'eme_admin_nonce': ememails.translate_adminnonce };

                    $.post(ajaxurl, params, function(data) {
                        $('#ArchivedMailingsTableContainer').jtable('reload');
                        $('#ArchivedMailingsActionsButton').text(ememails.translate_apply);
                        $('#ArchivedMailingsActionsButton').prop('disabled', false);
                        $('div#archivedmailings-message').html(data.htmlmessage);
                        $('div#archivedmailings-message').show();
                        $('div#archivedmailings-message').delay(5000).fadeOut('slow');
                    }, 'json');
                }
            }
            // return false to make sure the real form doesn't submit
            return false;
        });

        // Re-load records when user click 'load records' button.
        $('#ArchivedMailingsLoadRecordsButton').on("click",function (e) {
            e.preventDefault();
            $('#ArchivedMailingsTableContainer').jtable('load');
            // return false to make sure the real form doesn't submit
            return false;
        });
    }

    $('#eventmail_attach_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: ememails.translate_addattachments,
            button: {
                text: ememails.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#eventmail_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_eventmail_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_eventmail_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_eventmail_attach_ids').val(tmp_ids_val);
                $('#eventmail_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_eventmail_attach_ids').val() != '') {
        $('#eventmail_remove_attach_button').show();
    } else {
        $('#eventmail_remove_attach_button').hide();
    }
    $('#eventmail_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#eventmail_attach_links').html('');
        $('#eme_eventmail_attach_ids').val('');
        $('#eventmail_remove_attach_button').hide();
    });

    $('#generic_attach_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: ememails.translate_addattachments,
            button: {
                text: ememails.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#generic_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_generic_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_generic_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_generic_attach_ids').val(tmp_ids_val);
                $('#generic_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_generic_attach_ids').val() != '') {
        $('#generic_remove_attach_button').show();
    } else {
        $('#generic_remove_attach_button').hide();
    }
    $('#generic_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#generic_attach_links').html('');
        $('#eme_generic_attach_ids').val('');
        $('#generic_remove_attach_button').hide();
    });

    if ($('#eventmail_startdate').length) {
        $('#eventmail_startdate').fdatepicker({
            todayButton: new Date(),
            clearButton: true,
            fieldSizing: true,
            timepicker: true,
            minutesStep: parseInt(ememails.translate_minutesStep),
            language: ememails.translate_flanguage,
            firstDay: parseInt(ememails.translate_firstDayOfWeek),
            altFieldDateFormat: 'Y-m-d H:i:00',
            multipleDatesSeparator: ', ',
            dateFormat: ememails.translate_fdateformat,
            timeFormat: ememails.translate_ftimeformat,
            onSelect: function(formattedDate,date,inst) {
                if (!Array.isArray(date)) {
                    $('#eventmail-specificdates').text("");
                    $('#eventmailButton').text(ememails.translate_sendmail);
                } else {
                    $('#eventmail-specificdates').html('<br />'+ememails.translate_selecteddates+'<br />');
                    $.each(date, function( index, value ) {
                        date_formatted = inst.formatDate(ememails.translate_fdatetimeformat,value);
                        $('#eventmail-specificdates').append(date_formatted+'<br />');
                    });
                    $('#eventmailButton').text(ememails.translate_planmail);
                }
            }
        });
    }
    if ($('#genericmail_startdate').length) {
        $('#genericmail_startdate').fdatepicker({
            todayButton: new Date(),
            clearButton: true,
            fieldSizing: true,
            timepicker: true,
            minutesStep: parseInt(ememails.translate_minutesStep),
            language: ememails.translate_flanguage,
            firstDay: parseInt(ememails.translate_firstDayOfWeek),
            altFieldDateFormat: 'Y-m-d H:i:00',
            multipleDatesSeparator: ', ',
            dateFormat: ememails.translate_fdateformat,
            timeFormat: ememails.translate_ftimeformat,
            onSelect: function(formattedDate,date,inst) {
                if (!Array.isArray(date)) {
                    $('#genericmail-specificdates').text("");
                    $('#genericmailButton').text(ememails.translate_sendmail);
                } else {
                    $('#genericmail-specificdates').html('<br />'+ememails.translate_selecteddates+'<br />');
                    $.each(date, function( index, value ) {
                        date_formatted = inst.formatDate(ememails.translate_fdatetimeformat,value);
                        $('#genericmail-specificdates').append(date_formatted+'<br />');
                    });
                    $('#genericmailButton').text(ememails.translate_planmail);
                }
            }
        });
    }
});
