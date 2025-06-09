// Refactored for clarity and to leverage utilities from eme.js and eme_admin.js
// Assumes eme.js and eme_admin.js are loaded before this file

jQuery(document).ready(function($) {
    // --- Person Autocomplete Handler ---
    function setupAutocomplete(inputSelector, hiddenIdSelector, noMatchText) {
        if ($(inputSelector).length) {
            let autocompleteTimeout;
            $(inputSelector).on("input", function() {
                clearTimeout(autocompleteTimeout);
                $(".eme-autocomplete-suggestions").remove();
                let $input = $(this);
                let value = $input.val();
                if (value.length >= 2) {
                    autocompleteTimeout = setTimeout(function() {
                        $.post(
                            ajaxurl,
                            {
                                'lastname': value,
                                'eme_admin_nonce': ememails.translate_adminnonce,
                                'action': 'eme_autocomplete_people',
                                'eme_searchlimit': 'people'
                            },
                            function(data) {
                                let $suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                                if (data.length) {
                                    $.each(data, function(_, item) {
                                        $("<div class='eme-autocomplete-suggestion'></div>")
                                            .html("<strong>" + eme_htmlDecode(item.lastname) + " " + eme_htmlDecode(item.firstname) +
                                                  "</strong><br><small>" + eme_htmlDecode(item.email) + "</small>")
                                            .on("click", function(e) {
                                                e.preventDefault();
                                                if (item.person_id) {
                                                    $(hiddenIdSelector).val(eme_htmlDecode(item.person_id));
                                                    $input.val(eme_htmlDecode(item.lastname) + " " + eme_htmlDecode(item.firstname) + "  ")
                                                        .attr('readonly', true).addClass('clearable x');
                                                }
                                            })
                                            .appendTo($suggestions);
                                    });
                                } else {
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                        .html("<strong>" + noMatchText + "</strong>")
                                        .appendTo($suggestions);
                                }
                                $(".eme-autocomplete-suggestions").remove();
                                $input.after($suggestions);
                            },
                            "json"
                        );
                    }, 500);
                }
            });

            $(document).on("click", function() {
                $(".eme-autocomplete-suggestions").remove();
            });

            // If manual input: clear hidden field
            $(inputSelector).on("keyup", function() {
                $(hiddenIdSelector).val('');
            }).change(function() {
                if ($(this).val() === '') {
                    $(hiddenIdSelector).val('');
                    $(this).attr('readonly', false).removeClass('clearable x');
                }
            });
        }
    }

    setupAutocomplete('input[name=chooseperson]', 'input[name=send_previewmailto_id]', ememails.translate_nomatchperson);
    setupAutocomplete('input[name=eventmail_chooseperson]', 'input[name=send_previeweventmailto_id]', ememails.translate_nomatchperson);

    // --- Mail Form Submission Handlers ---
    function ajaxMailButtonHandler(buttonSelector, action, formSelector, messageDivSelector, resetSelect2Selectors = [], extraReset = null) {
        $(buttonSelector).on("click", function(e) {
            e.preventDefault();

            // Save HTML message if using WYSIWYG
            if (ememails.translate_htmleditor === 'tinymce' && ememails.translate_htmlmail === 'yes') {
                let editorField = $(formSelector + " textarea").attr('id');
                if (editorField && tinymce.get(editorField)) {
                    tinymce.get(editorField).save();
                }
            }

            let $form = $(this.form);
            let formData = new FormData($form[0]);
            formData.append('action', action);
            formData.append('eme_admin_nonce', ememails.translate_adminnonce);

            $(buttonSelector).text(ememails.translate_pleasewait).prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                type: 'POST',
                dataType: 'json'
            })
            .done(function(data) {
                $(messageDivSelector).html(data.htmlmessage).show();
                if (data.Result === 'OK') {
                    $form.trigger('reset');
                    resetSelect2Selectors.forEach(sel => $(sel).val(null).trigger("change"));
                    if (typeof extraReset === "function") extraReset();
                    $(messageDivSelector).delay(5000).fadeOut('slow');
                }
                $(buttonSelector).text(ememails.translate_sendmail).prop('disabled', false);
            });
            return false;
        });
    }

    ajaxMailButtonHandler(
        '#eventmailButton', 'eme_eventmail', 
        '#eventmailButton', 'div#eventmail-message',
        [
            '#event_ids', "#eme_eventmail_send_persons", "#eme_eventmail_send_groups",
            "#eme_eventmail_send_members", "#eme_eventmail_send_membergroups", "#eme_eventmail_send_memberships", "#eme_mail_type"
        ]
    );
    ajaxMailButtonHandler(
        '#genericmailButton', 'eme_genericmail',
        '#genericmailButton', 'div#genericmail-message',
        [
            "#eme_genericmail_send_persons", "#eme_genericmail_send_peoplegroups", "#eme_genericmail_send_members",
            "#eme_genericmail_send_membergroups", "#eme_send_memberships"
        ],
        function() { $('input#eme_send_all_people').trigger('change'); }
    );

    // --- Mail Preview Handlers ---
    function mailPreviewHandler(buttonSelector, action, messageDivSelector, inputSelectors) {
        $(buttonSelector).on("click", function(e) {
            e.preventDefault();

            // Save HTML message if using WYSIWYG
            if (ememails.translate_htmleditor === 'tinymce' && ememails.translate_htmlmail === 'yes') {
                let editorField = $(this.form).find("textarea").attr('id');
                if (editorField && tinymce.get(editorField)) tinymce.get(editorField).save();
            }

            let $form = $(this.form);
            let $alldata = new FormData($form[0]);
            $alldata.append('action', action);
            $alldata.append('eme_admin_nonce', ememails.translate_adminnonce);

            $.ajax({
                url: ajaxurl,
                data: $alldata,
                cache: false,
                contentType: false,
                processData: false,
                type: 'POST',
                dataType: 'json'
            })
            .done(function(data) {
                $(messageDivSelector).html(data.htmlmessage).show().delay(5000).fadeOut('slow');
                if (data.Result === 'OK') {
                    inputSelectors.forEach(sel => {
                        $(sel).val('');
                        // the next line matches the visible elements name=chooseperson and name=eventmail_chooseperson
                        if (sel.indexOf('chooseperson') > -1) $(sel).attr('readonly', false).removeClass('clearable x');
                    });
                }
            });
            return false;
        });
    }
    mailPreviewHandler('#previeweventmailButton', 'eme_previeweventmail', 'div#previeweventmail-message', [
        'input[name=eventmail_chooseperson]', 'input[name=send_previeweventmailto_id]'
    ]);
    mailPreviewHandler('#previewmailButton', 'eme_previewmail', 'div#previewmail-message', [
        'input[name=chooseperson]', 'input[name=send_previewmailto_id]'
    ]);

    // --- Testmail Handler ---
    $('#testmailButton').on("click", function(e) {
        e.preventDefault();
        let $form = $(this.form);
        let $alldata = new FormData($form[0]);
        $alldata.append('action', 'eme_testmail');
        $alldata.append('eme_admin_nonce', ememails.translate_adminnonce);
        $('#testmailButton').text(ememails.translate_pleasewait).prop('disabled', true);
        $.ajax({
            url: ajaxurl,
            data: $alldata,
            cache: false,
            contentType: false,
            processData: false,
            type: 'POST',
            dataType: 'json'
        })
        .done(function(data) {
            $('div#testmail-message').html(data.htmlmessage).show();
            if (data.Result === 'OK') $form.trigger('reset');
            $('#testmailButton').text(ememails.translate_sendmail).prop('disabled', false);
        });
        return false;
    });

    // --- Template Select Handlers (Generic for future editors) ---
    function setEditorContent(targetSelector, editorType, editorTarget, value) {
        // Always set the textarea value to keep it in sync for AJAX submits
        $(targetSelector).val(value);
        switch (editorType) {
            case 'tinymce':
                if (typeof tinymce !== "undefined" && tinymce.get(editorTarget)) {
                    tinymce.get(editorTarget).setContent(value);
                }
                break;
            case 'jodit':
                if (typeof Jodit !== "undefined" && Jodit.instances[editorTarget]) {
                    Jodit.instances[editorTarget].value = value;
                }
                break;
                // Add future editors here
        }
    }

    function templateSelectHandler({
        selectSelector,
        targetSelector,
        editorType,
        editorTarget
    }) {
        $(selectSelector).on("change", function(e) {
            e.preventDefault();
            $.post(
                ajaxurl,
                {
                    action: 'eme_get_template',
                    'eme_admin_nonce': ememails.translate_adminnonce,
                    template_id: $(selectSelector).val()
                },
                function(data) {
                    setEditorContent(targetSelector, editorType, editorTarget, data.htmlmessage);
                },
                'json'
            );
        });
    }

    templateSelectHandler({
        selectSelector: 'select#event_subject_template',
        targetSelector: 'input#event_mail_subject'
    });

    templateSelectHandler({
        selectSelector: 'select#event_message_template',
        targetSelector: 'textarea#event_mail_message',
        editorType: ememails.translate_htmlmail === 'yes' ? ememails.translate_htmleditor : undefined,
        editorTarget: (ememails.translate_htmleditor === 'tinymce') ? 'event_mail_message' :
                      (ememails.translate_htmleditor === 'jodit') ? 'joditdiv_event_mail_message' : undefined
    });

    templateSelectHandler({
        selectSelector: 'select#generic_message_template',
        targetSelector: 'textarea#generic_mail_message',
        editorType: ememails.translate_htmlmail === 'yes' ? ememails.translate_htmleditor : undefined,
        editorTarget: (ememails.translate_htmleditor === 'tinymce') ? 'generic_mail_message' :
                      (ememails.translate_htmleditor === 'jodit') ? 'joditdiv_generic_mail_message' : undefined
    });

    // --- Show/Hide Groups and Mail Types ---
    function updateShowSendGroups() {
        if ($('input#eme_send_all_people').prop('checked')) {
            $('div#div_eme_send_groups').hide();
            $('div#div_eme_send_all_people').show();
        } else {
            $('div#div_eme_send_groups').show();
            $('div#div_eme_send_all_people').hide();
        }
    }
    $('input#eme_send_all_people').on("change", updateShowSendGroups);
    updateShowSendGroups();

    function updateShowMailTypes() {
        let mailType = $('select[name=eme_mail_type]').val();
        if (mailType === 'attendees' || mailType === 'bookings') {
            $('tr#eme_pending_approved_row, tr#eme_only_unpaid_row').show();
            $('span#span_unpaid_attendees').toggle(mailType === 'attendees');
            $('span#span_unpaid_bookings').toggle(mailType === 'bookings');
            $('tr#eme_exclude_registered_row').hide();
            $('tr#eme_rsvp_status_row').show();
        } else {
            $('tr#eme_pending_approved_row, tr#eme_only_unpaid_row').hide();
            $('tr#eme_exclude_registered_row').toggle(mailType !== '');
            $('tr#eme_rsvp_status_row').hide();
        }
        if (mailType === 'people_and_groups') {
            $('tr#eme_people_row, tr#eme_groups_row, tr#eme_members_row1, tr#eme_members_row2, tr#eme_members_row3').show();
        } else {
            $('tr#eme_people_row, tr#eme_groups_row, tr#eme_members_row1, tr#eme_members_row2, tr#eme_members_row3').hide();
        }
    }
    $('select[name=eme_mail_type]').on("change", updateShowMailTypes);
    updateShowMailTypes();

    // --- Select2 Initialization (events) ---
    $('.eme_select2_events_class').select2({
        ajax: {
            url: ajaxurl + '?action=eme_events_select2',
            dataType: 'json',
            delay: 500,
            data: function(params) {
                return {
                    q: params.term,
                    search_all: $('#eventsearch_all').is(':checked') ? 1 : 0,
                    eme_admin_nonce: ememails.translate_adminnonce
                };
            },
            processResults: function(data) {
                return { results: data.Records };
            },
            cache: true
        },
        placeholder: ememails.translate_selectevents,
        width: '90%'
    });

    // --- Attachment UI for Event and Generic Mails ---
    function initAttachmentUI(buttonSelector, linksSelector, idsSelector, removeBtnSelector) {
        $(buttonSelector).on("click", function(e) {
            e.preventDefault();
            let custom_uploader = wp.media({
                title: ememails.translate_addattachments,
                button: { text: ememails.translate_addattachments },
                multiple: true
            }).on('select', function() {
                let selection = custom_uploader.state().get('selection');
                selection.map(function(attach) {
                    let attachment = attach.toJSON();
                    $(linksSelector).append("<a target='_blank' href='" + attachment.url + "'>" + attachment.title + "</a><br>");
                    let currentVal = $(idsSelector).val() || '';
                    let idsArr = currentVal ? currentVal.split(',') : [];
                    idsArr.push(attachment.id);
                    $(idsSelector).val(idsArr.join(','));
                    $(removeBtnSelector).show();
                });
            }).open();
        });
        if ($(idsSelector).val() !== '') {
            $(removeBtnSelector).show();
        } else {
            $(removeBtnSelector).hide();
        }
        $(removeBtnSelector).on("click", function(e) {
            e.preventDefault();
            $(linksSelector).html('');
            $(idsSelector).val('');
            $(removeBtnSelector).hide();
        });
    }
    initAttachmentUI('#eventmail_attach_button', '#eventmail_attach_links', '#eme_eventmail_attach_ids', '#eventmail_remove_attach_button');
    initAttachmentUI('#generic_attach_button', '#generic_attach_links', '#eme_generic_attach_ids', '#generic_remove_attach_button');

    // --- Start Date Pickers with fdatepicker ---
    function setupFDatepicker(dateSelector, specificDatesDiv, buttonSelector, planText) {
        if ($(dateSelector).length) {
            $(dateSelector).fdatepicker({
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
                onSelect: function(formattedDate, date, inst) {
                    if (!Array.isArray(date)) {
                        $(specificDatesDiv).text("");
                        $(buttonSelector).text(ememails.translate_sendmail);
                    } else {
                        $(specificDatesDiv).html('<br>' + ememails.translate_selecteddates + '<br>');
                        $.each(date, function(_, value) {
                            let dateFormatted = inst.formatDate(ememails.translate_fdatetimeformat, value);
                            $(specificDatesDiv).append(dateFormatted + '<br>');
                        });
                        $(buttonSelector).text(planText);
                    }
                }
            });
        }
    }
    setupFDatepicker('#eventmail_startdate', '#eventmail-specificdates', '#eventmailButton', ememails.translate_planmail);
    setupFDatepicker('#genericmail_startdate', '#genericmail-specificdates', '#genericmailButton', ememails.translate_planmail);

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
});
