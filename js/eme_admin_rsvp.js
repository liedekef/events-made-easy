jQuery(document).ready(function ($) { 
    if (typeof getQueryParams === 'undefined') {
        function getQueryParams(qs) {
            qs = qs.split('+').join(' ');
            let params = {},
                tokens,
                re = /[?&]?([^=]+)=([^&]*)/g;

            while (tokens = re.exec(qs)) {
                params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
            }
            return params;
        }
    }

    let $_GET = getQueryParams(document.location.search);
    //Prepare jtable plugin
    if ($('#BookingsTableContainer').length) {
        let rsvpfields = {
            booking_id: {
                key: true,
                list: false,
            },
            event_name: {
                title: emersvp.translate_eventinfo
            },
            event_id: {
                title: emersvp.translate_event_id,
                sorting: false,
                visibility: 'hidden'
            },
            person_id: {
                title: emersvp.translate_person_id,
                sorting: false,
                visibility: 'hidden'
            },
            rsvp: {
                title: emersvp.translate_rsvp,
                sorting: false,
                width: '2%',
                listClass: 'eme-jtable-center'
            },
            event_start: {
                title: emersvp.translate_eventstart,
            },
            booker: {
                title: emersvp.translate_booker
            },
            creation_date: {
                title: emersvp.translate_bookingdate
            },
            seats: {
                title: emersvp.translate_seats,
                sorting: false,
                listClass: 'eme-jtable-center'
            },
            eventprice: {
                title: emersvp.translate_eventprice,
                sorting: false
            },
            event_cats: {
                title: emersvp.translate_event_cats,
                sorting: false,
                visibility: 'hidden'
            },
            discount: {
                title: emersvp.translate_discount,
                sorting: false,
                visibility: 'hidden'
            },
            dcodes_used: {
                title: emersvp.translate_dcodes_used,
                sorting: false,
                visibility: 'hidden'
            },
            totalprice: {
                title: emersvp.translate_totalprice,
                sorting: false
            },
            unique_nbr: {
                title: emersvp.translate_uniquenbr,
                visibility: 'hidden'
            },
            booking_paid: {
                title: emersvp.translate_paid,
                visibility: 'hidden'
            },
            remaining: {
                title: emersvp.translate_remaining,
                sorting: false,
                visibility: 'hidden'
            },
            received: {
                title: emersvp.translate_received,
                sorting: false,
                visibility: 'hidden'
            },
            payment_date: {
                title: emersvp.translate_paymentdate,
                visibility: 'hidden'
            },
            pg: {
                title: emersvp.translate_pg,
                visibility: 'hidden'
            },
            pg_pid: {
                title: emersvp.translate_pg_pid,
                visibility: 'hidden'
            },
            payment_id: {
                title: emersvp.translate_paymentid
            },
            attend_count: {
                title: emersvp.translate_attend_count,
                visibility: 'hidden'
            },
            lastreminder: {
                title: emersvp.translate_lastreminder,
                sorting: false,
                visibility: 'hidden'
            },
            booking_comment: {
                title: emersvp.translate_comment,
                sorting: false,
                visibility: 'hidden'
            },
            wp_user: {
                title: emersvp.translate_wpuser,
                sorting: false,
                visibility: 'hidden'
            }
        }
        let editfield = {
            edit_link: {
                title: emersvp.translate_edit,
                sorting: false,
                visibility: 'fixed',
                listClass: 'jtable-command-column-header eme-jtable-center',
                width: '1%',
            }
        }
        let extrafields=$('#BookingsTableContainer').data('extrafields').toString().split(',');
        let extrafieldnames=$('#BookingsTableContainer').data('extrafieldnames').toString().split(',');
        let extrafieldsearchable=$('#BookingsTableContainer').data('extrafieldsearchable').toString().split(',');
        $.each(extrafields, function( index, value ) {
            if (value == 'SEPARATOR') {
                let fieldindex='SEPARATOR_'+index;
                let extrafield = {};
                extrafield[fieldindex] = {
                    title: extrafieldnames[index],
                    sorting: false,
                    visibility: 'separator'
                };
                $.extend(rsvpfields,extrafield);
            } else if (value != '') {
                let fieldindex='FIELD_'+value;
                let extrafield = {};
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
                $.extend(rsvpfields,extrafield);
            }
        });
        if (typeof $_GET['trash']==='undefined' || $_GET['trash']==0) {
            $.extend(rsvpfields,editfield);
        }

        $('#BookingsTableContainer').jtable({
            title: emersvp.translate_bookings,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'creation_date ASC',
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            toolbar: {
                items: [
                    {
                        text: emersvp.translate_markpaidandapprove,
                        cssClass: 'eme_jtable_button_for_pending_only',
                        click: function () {
                            let selectedRows = $('#BookingsTableContainer').jtable('selectedRows');
                            let do_action = 'markpaidandapprove';
                            if (selectedRows.length > 0) {
                                let ids = [];
                                selectedRows.each(function () {
                                    ids.push($(this).attr('data-record-key'));
                                });
                                let idsjoined = ids.join(); //will be such a string '2,5,7'
                                $('.eme_jtable_button_for_pending_only .jtable-toolbar-item-text').text(emersvp.translate_pleasewait);
                                $.post(ajaxurl, {'booking_ids': idsjoined, 'action': 'eme_manage_bookings', 'do_action': do_action, 'eme_admin_nonce': emersvp.translate_adminnonce }, function(data) {
                                    if (data.Result!='OK') {
                                        $('div#bookings-message').html(data.htmlmessage);
                                        $('div#bookings-message').show();
                                        $('div#bookings-message').delay(3000).fadeOut('slow');
                                    }

                                    $('#BookingsTableContainer').jtable('reload');
                                    $('.eme_jtable_button_for_pending_only .jtable-toolbar-item-text').text(emersvp.translate_markpaidandapprove);
                                }, 'json');
                            }
                        }
                    },
                    {
                        text: emersvp.translate_markpaid,
                        cssClass: 'eme_jtable_button_for_approved_only',
                        click: function () {
                            let selectedRows = $('#BookingsTableContainer').jtable('selectedRows');
                            let do_action = 'markPaid';
                            if (selectedRows.length > 0) {
                                let ids = [];
                                selectedRows.each(function () {
                                    ids.push($(this).attr('data-record-key'));
                                });
                                let idsjoined = ids.join(); //will be such a string '2,5,7'
                                $('.eme_jtable_button_for_approved_only .jtable-toolbar-item-text').text(emersvp.translate_pleasewait);
                                $.post(ajaxurl, {'booking_ids': idsjoined, 'action': 'eme_manage_bookings', 'do_action': do_action, 'eme_admin_nonce': emersvp.translate_adminnonce }, function(data) {
                                    if (data.Result!='OK') {
                                        $('div#bookings-message').html(data.htmlmessage);
                                        $('div#bookings-message').show();
                                        $('div#bookings-message').delay(3000).fadeOut('slow');
                                    }

                                    $('#BookingsTableContainer').jtable('reload');
                                    $('.eme_jtable_button_for_approved_only .jtable-toolbar-item-text').text(emersvp.translate_markpaid);
                                }, 'json');
                            }
                        }
                    },
                    {
                        text: emersvp.translate_csv,
                        click: function () {
                            jtable_csv('#BookingsTableContainer','bookings');
                        }
                    },
                    {
                        text: emersvp.translate_print,
                        click: function () {
                            $('#BookingsTableContainer').find('table:first').printElement();
                        }
                    }
                ]
            },
            actions: {
                listAction: ajaxurl
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_bookings_list",
                    'eme_admin_nonce': emersvp.translate_adminnonce,
                    'trash': $_GET['trash'],
                    'scope': $('#scope').val(),
                    'category': $('#category').val(),
                    'booking_status': $('#booking_status').val(),
                    'search_event': $('#search_event').val(),
                    'search_person': $('#search_person').val(),
                    'search_customfields': $('#search_customfields').val(),
                    'search_unique': $('#search_unique').val(),
                    'search_paymentid': $('#search_paymentid').val(),
                    'search_pg_pid': $('#search_pg_pid').val(),
                    'search_start_date': $('#search_start_date').val(),
                    'search_end_date': $('#search_end_date').val(),
                    'event_id': $('#event_id').val(),
                    'person_id': $_GET['person_id']
                }
                return params;
            },
            fields: rsvpfields,
            sortingInfoSelector: '#bookingstablesortingInfo',
            messages: {
                'sortingInfoNone': ''
            }
        });

        $('#BookingsTableContainer').jtable('load');
        $('<div id="bookingstablesortingInfo" style="margin-top: 10px; font-weight: bold;"></div>').insertBefore('#BookingsTableContainer');

    }

    function updateShowHideStuff() {
        let action=$('select#eme_admin_action').val();
        if ($.inArray(action,['resendApprovedBooking']) >= 0) {
            $('span#span_sendtocontact').show();
        } else {
            $('span#span_sendtocontact').hide();
        }
        if ($.inArray(action,['trashBooking','approveBooking','pendingBooking','unsetwaitinglistBooking','setwaitinglistBooking','markPaid','markUnpaid']) >= 0) {
            $('span#span_sendmails').show();
        } else {
            $('span#span_sendmails').hide();
        }
        if (($.inArray(action,['trashBooking','pendingBooking','setwaitinglistBooking','markUnpaid']) >= 0) && (typeof $_GET['trash']==='undefined' || $_GET['trash']==0)) {
            $('span#span_refund').show();
        } else {
            $('span#span_refund').hide();
        }
        if ($.inArray(action,['partialPayment']) >= 0) {
            $('span#span_partialpayment').show();
        } else {
            $('span#span_partialpayment').hide();
        }
        if (action == 'rsvpMails') {
            jQuery('span#span_rsvpmailtemplate').show();
        } else {
            jQuery('span#span_rsvpmailtemplate').hide();
        }
        if (action == 'pdf') {
            jQuery('span#span_pdftemplate').show();
        } else {
            jQuery('span#span_pdftemplate').hide();
        }
        if (action == 'html') {
            jQuery('span#span_htmltemplate').show();
        } else {
            jQuery('span#span_htmltemplate').hide();
        }
    }
    $('select#eme_admin_action').on("change",updateShowHideStuff);
    updateShowHideStuff();

    // hide one toolbar button if not on pending approval and trash=0 (or not set)
    function showhideButtonPaidApprove() {
        if ($('#booking_status').val() == "PENDING" && (typeof $_GET['trash']==='undefined' || $_GET['trash']==0)) {
            $('.eme_jtable_button_for_pending_only').show();
        } else {
            $('.eme_jtable_button_for_pending_only').hide();
        }
        if ($('#booking_status').val() == "APPROVED" && (typeof $_GET['trash']==='undefined' || $_GET['trash']==0)) {
            $('.eme_jtable_button_for_approved_only').show();
        } else {
            $('.eme_jtable_button_for_approved_only').hide();
        }
    }
    showhideButtonPaidApprove();

    // Actions button
    $('#BookingsActionsButton').on("click",function (e) {
        e.preventDefault();
        let selectedRows = $('#BookingsTableContainer').jtable('selectedRows');
        let do_action = $('#eme_admin_action').val();
        let send_to_contact_too = $('#send_to_contact_too').val();
        let send_mail = $('#send_mail').val();
        let refund = $('#refund').val();
        let partial_amount = $('#partial_amount').val();
        let rsvpmail_template = $('#rsvpmail_template').val();
        let rsvpmail_template_subject = $('#rsvpmail_template_subject').val();
        let pdf_template = $('#pdf_template').val();
        let pdf_template_header = $('#pdf_template_header').val();
        let pdf_template_footer = $('#pdf_template_footer').val();
        let html_template = $('#html_template').val();
        let html_template_header = $('#html_template_header').val();
        let html_template_footer = $('#html_template_footer').val();

        let action_ok=1;
        if (selectedRows.length > 0 && do_action != '') {
            if ((do_action=='trashBooking' || do_action=='deleteBooking') && !confirm(emersvp.translate_areyousuretodeleteselected)) {
                action_ok=0;
            }
            if ((do_action=='partialPayment') && selectedRows.length > 1) {
                alert(emersvp.translate_selectonerowonlyforpartial);
                action_ok=0;
            }
            if (action_ok==1) {
                $('#BookingsActionsButton').text(emersvp.translate_pleasewait);
                $('#BookingsActionsButton').prop('disabled', true);
                let ids = [];
                let form;
                selectedRows.each(function () {
                    ids.push($(this).attr('data-record-key'));
                });

                let idsjoined = ids.join(); //will be such a string '2,5,7'
                let params = {
                    'booking_ids': idsjoined,
                    'action': 'eme_manage_bookings',
                    'do_action': do_action,
                    'send_to_contact_too': send_to_contact_too,
                    'send_mail': send_mail,
                    'refund': refund,
                    'partial_amount': partial_amount,
                    'rsvpmail_template': rsvpmail_template,
                    'rsvpmail_template_subject': rsvpmail_template_subject,
                    'pdf_template': pdf_template,
                    'pdf_template_header': pdf_template_header,
                    'pdf_template_footer': pdf_template_footer,
                    'html_template': html_template,
                    'html_template_header': html_template_header,
                    'html_templata_footer': html_template_footer,
                    'eme_admin_nonce': emersvp.translate_adminnonce };

                if (do_action=='sendMails') {
                    form = $('<form method="POST" action="'+emersvp.translate_admin_sendmails_url+'">');
                    params = {
                        'booking_ids': idsjoined,
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
                    $('#BookingsActionsButton').text(emersvp.translate_apply);
                    $('#BookingsActionsButton').prop('disabled', false);
                    return false;
                }
                $.post(ajaxurl, params, function(data) {
                    $('#BookingsTableContainer').jtable('reload');
                    $('#BookingsActionsButton').text(emersvp.translate_apply);
                    $('#BookingsActionsButton').prop('disabled', false);
                    $('div#bookings-message').html(data.htmlmessage);
                    $('div#bookings-message').show();
                    $('div#bookings-message').delay(3000).fadeOut('slow');
                }, 'json');
            }
        }
        // return false to make sure the real form doesn't submit
        return false;
    });

    // Re-load records when user click 'load records' button.
    $('#BookingsLoadRecordsButton').on("click",function (e) {
        e.preventDefault();
        $('#BookingsTableContainer').jtable('load');
        // return false to make sure the real form doesn't submit
        return false;
    });

    // we add the on-click to the body and limit to the .eme_iban_button class, so that the iban-buttons that are only added via ajax are handled as well
    $('body').on('click', '.eme_iban_button', function(e) {
        e.preventDefault();
        // clicking selects/deselects the row too, so invert it again
        $('#BookingsTableContainer').jtable('invertRowSelection',$(this).closest('tr'));
        let params = {
            'action': 'eme_get_payconiq_iban',
            'pg_pid': $(this).data('pg_pid'),
            'eme_admin_nonce': emersvp.translate_adminnonce
        };
        $.post(ajaxurl, params, function(data) {
            $('#button_'+data.payment_id).hide();
            $('span#payconiq_'+data.payment_id).html(data.iban);
        }, 'json');
        // return false to make sure the real form doesn't submit
        return false;
    });


    // for autocomplete to work, the element needs to exist, otherwise JS errors occur
    // we check for that using length
    if ($('input[name=chooseevent]').length) {
        let emeadmin_chooseevent_timeout; // Declare a variable to hold the timeout ID
        $("input[name=chooseevent]").on("input", function(e) {
            clearTimeout(emeadmin_chooseevent_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                emeadmin_chooseevent_timeout = setTimeout(function() {
                    let search_all=0;
                    if ($('#eventsearch_all').is(':checked')) {
                        search_all=1;
                    }
                    $.post(ajaxurl,
                        { 
                            'q': inputValue,
                            'exclude_id': $('#event_id').val(),
                            'only_rsvp': 1,
                            'search_all': search_all,
                            'eme_admin_nonce': emersvp.translate_adminnonce,
                            'action': 'eme_autocomplete_event'
                        },
                        function(data) {
                            suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                            $.each(data, function(index, item) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+eme_htmlDecode(item.eventinfo)+"</strong>")
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        if (item.event_id) {
                                            $('input[name=transferto_id]').val(eme_htmlDecode(item.event_id));
                                            inputField.val(eme_htmlDecode(item.eventinfo)+"  ").attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+emersvp.translate_nomatchevent+'</strong>')
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
        $('input[name=chooseevent]').on("change",function() {
            if ($(this).val()=='') {
                $(this).attr('readonly', false).removeClass('clearable');
                $('input[name=transferto_id]').val('');
            }
        });
    }
});
