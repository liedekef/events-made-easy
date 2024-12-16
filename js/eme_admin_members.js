jQuery(document).ready(function ($) { 

    if ($('#MembersTableContainer').length) {
        let memberfields = {
            'members.member_id': {
                key: true,
                title: ememembers.translate_memberid,
                visibility: 'hidden'
            },
            person_id: {
                title: ememembers.translate_personid,
                visibility: 'hidden'
            },
            lastname: {
                title: ememembers.translate_lastname
            },
            firstname: {
                title: ememembers.translate_firstname
            },
            email: {
                title: ememembers.translate_email
            },
            related_member_id: {
                title: ememembers.translate_related_to,
                visibility: 'hidden'
            },
            address1: {
                title: ememembers.translate_address1,
                visibility: 'hidden'
            },
            address2: {
                title: ememembers.translate_address2,
                visibility: 'hidden'
            },
            city: {
                title: ememembers.translate_city,
                visibility: 'hidden'
            },
            zip: {
                title: ememembers.translate_zip,
                visibility: 'hidden'
            },
            state: {
                title: ememembers.translate_state,
                visibility: 'hidden'
            },
            country: {
                title: ememembers.translate_country,
                visibility: 'hidden'
            },
            birthdate: {
                title: ememembers.translate_birthdate,
                visibility: 'hidden'
            },
            birthplace: {
                title: ememembers.translate_birthplace,
                visibility: 'hidden'
            },
            membership_name: {
                title: ememembers.translate_membership,
                visibility: 'hidden'
            },
            membershipprice: {
                title: ememembers.translate_membershipprice,
                visibility: 'hidden',
                sorting: false
            },
            discount: {
                title: ememembers.translate_discount,
                sorting: false,
                visibility: 'hidden'
            },
            dcodes_used: {
                title: ememembers.translate_dcodes_used,
                sorting: false,
                visibility: 'hidden'
            },
            totalprice: {
                title: ememembers.translate_totalprice,
                visibility: 'hidden',
                sorting: false
            },
            start_date: {
                title: ememembers.translate_startdate,
                visibility: 'hidden'
            },
            end_date: {
                title: ememembers.translate_enddate,
                visibility: 'hidden'
            },
            usage_count: {
                title: ememembers.translate_usage_count,
                visibility: 'hidden',
                sorting: false
            },
            creation_date: {
                title: ememembers.translate_registrationdate,
                visibility: 'hidden'
            },
            last_seen: {
                title: ememembers.translate_last_seen,
                visibility: 'hidden'
            },
            paid: {
                title: ememembers.translate_paid,
                visibility: 'hidden'
            },
            unique_nbr: {
                title: ememembers.translate_uniquenbr,
                visibility: 'hidden'
            },
            payment_date: {
                title: ememembers.translate_paymentdate,
                visibility: 'hidden'
            },
            pg: {
                title: ememembers.translate_pg,
                visibility: 'hidden'
            },
            pg_pid: {
                title: ememembers.translate_pg_pid,
                visibility: 'hidden'
            },
            payment_id: {
                title: ememembers.translate_paymentid,
                visibility: 'hidden'
            },
            reminder_date: {
                title: ememembers.translate_lastreminder,
                visibility: 'hidden'
            },
            reminder: {
                title: ememembers.translate_nbrreminder,
                visibility: 'hidden'
            },
            status: {
                title: ememembers.translate_status,
                visibility: 'hidden'
            },
            wp_user: {
                title: ememembers.translate_wpuser,
                sorting: false,
                visibility: 'hidden'
            }
        }
        let extrafields=$('#MembersTableContainer').data('extrafields').toString().split(',');
        let extrafieldnames=$('#MembersTableContainer').data('extrafieldnames').toString().split(',');
        let extrafieldsearchable=$('#MembersTableContainer').data('extrafieldsearchable').toString().split(',');
        $.each(extrafields, function( index, value ) {
            if (value != '') {
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
                $.extend(memberfields,extrafield);
            }
        });

        //Prepare jtable plugin
        $('#MembersTableContainer').jtable({
            title: ememembers.translate_members,
            paging: true,
            sorting: true,
            multiSorting: true,
            jqueryuiTheme: true,
            defaultSorting: '',
            selecting: true, //Enable selecting
            multiselect: true, //Allow multiple selecting
            selectingCheckboxes: true, //Show checkboxes on first column
            selectOnRowClick: true,
            toolbar: {
                items: [{
                    text: ememembers.translate_csv,
                    click: function () {
                        jtable_csv('#MembersTableContainer','members');
                    }
                },
                    {
                        text: ememembers.translate_print,
                        click: function () {
                            $('#MembersTableContainer').printElement();
                        }
                    }
                ]
            },
            actions: {
                listAction: ajaxurl
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_members_list",
                    'eme_admin_nonce': ememembers.translate_adminnonce,
                    'search_person': $('#search_person').val(),
                    'search_memberstatus': $('#search_memberstatus').val(),
                    'search_membershipids': $('#search_membershipids').val(),
                    'search_memberid': $('#search_memberid').val(),
                    'search_paymentid': $('#search_paymentid').val(),
                    'search_pg_pid': $('#search_pg_pid').val(),
                    'search_customfields': $('#search_customfields').val(),
                    'search_customfieldids': $('#search_customfieldids').val(),
                    'search_exactmatch': exactmatch
                }
                return params;
            },

            fields: memberfields
        });
        let exactmatch;
        if ($('#search_exactmatch').is(":checked")) {
            exactmatch = 1;
        } else {
            exactmatch = 0;
        }
        $('#MembersTableContainer').jtable('load');
    }

    if ($('#MembershipsTableContainer').length) {
        let membershipfields = {
            membership_id: {
                key: true,
                title: ememembers.translate_id,
                visibility: 'hidden'
            },
            name: {
                title: ememembers.translate_name
            },
            status: {
                title: ememembers.translate_status,
                visibility: 'hidden',
                sorting: false
            },
            description: {
                title: ememembers.translate_description
            },
            membercount: {
                title: ememembers.translate_membercount,
                sorting: false
            },
            contact: {
                title: ememembers.translate_contact,
                sorting: false
            }
        }
        let extrafields=$('#MembershipsTableContainer').data('extrafields').toString().split(',');
        let extrafieldnames=$('#MembershipsTableContainer').data('extrafieldnames').toString().split(',');
        let extrafieldsearchable=$('#MembershipsTableContainer').data('extrafieldsearchable').toString().split(',');
        $.each(extrafields, function( index, value ) {
            if (value != '') {
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
                $.extend(membershipfields,extrafield);
            }
        });
        $('#MembershipsTableContainer').jtable({
            title: ememembers.translate_memberships,
            paging: true,
            sorting: true,
            multiSorting: true,
            jqueryuiTheme: true,
            defaultSorting: 'name ASC',
            selecting: true, //Enable selecting
            multiselect: true, //Allow multiple selecting
            selectingCheckboxes: true, //Show checkboxes on first column
            selectOnRowClick: true,
            actions: {
                listAction: ajaxurl+'?action=eme_memberships_list&eme_admin_nonce='+ememembers.translate_adminnonce
            },
            fields: membershipfields
        });
        $('#MembershipsTableContainer').jtable('load');
    }

    // Actions button
    $('#MembershipsActionsButton').on("click",function (e) {
        e.preventDefault();
        let selectedRows = $('#MembershipsTableContainer').jtable('selectedRows');
        let do_action = $('#eme_admin_action').val();
        let action_ok=1;
        if (selectedRows.length > 0 && do_action != '') {
            if ((do_action=='deleteMemberships') && !confirm(ememembers.translate_areyousuretodeleteselected)) {
                action_ok=0;
            }
            if (action_ok==1) {
                $('#MembershipsActionsButton').text(ememembers.translate_pleasewait);
                $('#MembershipsActionsButton').prop('disabled', true);
                let ids = [];
                selectedRows.each(function () {
                    ids.push($(this).data('record')['membership_id']);
                });

                let idsjoined = ids.join(); //will be such a string '2,5,7'
                $.post(ajaxurl, {'membership_id': idsjoined, 'action': 'eme_manage_memberships', 'do_action': do_action, 'eme_admin_nonce': ememembers.translate_adminnonce }, function(data) {
                    $('#MembershipsTableContainer').jtable('reload');
                    $('#MembershipsActionsButton').text(ememembers.translate_apply);
                    $('#MembershipsActionsButton').prop('disabled', false);
                    $('div#memberships-message').html(data.htmlmessage);
                    $('div#memberships-message').show();
                    if (do_action!='showMembershipStats') {
                        $('div#memberships-message').delay(3000).fadeOut('slow');
                    }
                }, 'json');
            }
        }
        // return false to make sure the real form doesn't submit
        return false;
    });

    // Actions button
    $('#MembersActionsButton').on("click",function (e) {
        e.preventDefault();
        let selectedRows = $('#MembersTableContainer').jtable('selectedRows');
        let do_action = $('#eme_admin_action').val();
        let send_mail = $('#send_mail').val();
        let trash_person = $('#trash_person').val();
        let membermail_template = $('#membermail_template').val();
        let membermail_template_subject = $('#membermail_template_subject').val();
        let pdf_template = $('#pdf_template').val();
        let pdf_template_header = $('#pdf_template_header').val();
        let pdf_template_footer = $('#pdf_template_footer').val();
        let html_template = $('#html_template').val();
        let html_template_header = $('#html_template_header').val();
        let html_template_footer = $('#html_template_footer').val();

        let action_ok=1;
        if (selectedRows.length > 0 && do_action != '') {
            if ((do_action=='deleteMembers') && !confirm(ememembers.translate_areyousuretodeleteselected)) {
                action_ok=0;
            }
            if (action_ok==1) {
                $('#MembersActionsButton').text(ememembers.translate_pleasewait);
                $('#MembersActionsButton').prop('disabled', true);
                let ids = [];
                selectedRows.each(function () {
                    ids.push($(this).data('record')['members.member_id']);
                });

                let idsjoined = ids.join(); //will be such a string '2,5,7'
                let form;
                let params = {
                    'member_id': idsjoined,
                    'action': 'eme_manage_members',
                    'do_action': do_action,
                    'send_mail': send_mail,
                    'trash_person': trash_person,
                    'pdf_template': pdf_template,
                    'pdf_template_header': pdf_template_header,
                    'pdf_template_footer': pdf_template_footer,
                    'membermail_template': membermail_template,
                    'membermail_template_subject': membermail_template_subject,
                    'html_template': html_template,
                    'html_template_header': html_template_header,
                    'html_templata_footer': html_template_footer,
                    'eme_admin_nonce': ememembers.translate_adminnonce };

                if (do_action=='sendMails') {
                    form = $('<form method="POST" action="'+ememembers.translate_admin_sendmails_url+'">');
                    params = {
                        'member_ids': idsjoined,
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
                    $('#MembersActionsButton').text(ememembers.translate_apply);
                    $('#MembersActionsButton').prop('disabled', false);
                    return false;
                }
                $.post(ajaxurl, params, function(data) {
                    $('#MembersTableContainer').jtable('reload');
                    $('#MembersActionsButton').text(ememembers.translate_apply);
                    $('#MembersActionsButton').prop('disabled', false);
                    $('div#members-message').html(data.htmlmessage);
                    $('div#members-message').show();
                    $('div#members-message').delay(3000).fadeOut('slow');
                }, 'json');
            }
        }
        // return false to make sure the real form doesn't submit
        return false;
    });

    // Re-load records when user click 'load records' button.
    $('#MembersLoadRecordsButton').on("click",function (e) {
        e.preventDefault();
        let exactmatch;
        if ($('#search_exactmatch').is(":checked")) {
            exactmatch = 1;
        } else {
            exactmatch = 0;
        }
        $('#MembersTableContainer').jtable('load');
        if ($('#search_person').val().length || $('#search_memberstatus').val().length || $('#search_membershipids').val().length || $('#search_memberid').val().length || $('#search_customfields').val().length || $('#search_customfieldids').val().length) {
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
        let exactmatch;
        if ($('#search_exactmatch').is(":checked")) {
            exactmatch = 1;
        } else {
            exactmatch = 0;
        }
        let params = {
            'search_person': $('#search_person').val(),
            'search_memberstatus': $('#search_memberstatus').val(),
            'search_membershipids': $('#search_membershipids').val(),
            'search_memberid': $('#search_memberid').val(),
            'search_customfields': $('#search_customfields').val(),
            'search_customfieldids': $('#search_customfieldids').val(),
            'search_exactmatch': exactmatch,
            'action': 'eme_store_members_query',
            'eme_admin_nonce': ememembers.translate_adminnonce,
            'dynamicgroupname': $('#dynamicgroupname').val()
        };
        $.post(ajaxurl, params, function(data) {
            $('#StoreQueryButton').hide();
            $('#StoreQueryDiv').hide();
            $('div#members-message').html(data.htmlmessage);
            $('div#members-message').show();
            $('div#members-message').delay(3000).fadeOut('slow');
        }, 'json');
        // return false to make sure the real form doesn't submit
        return false;
    });
    $('#StoreQueryButton').hide();
    $('#StoreQueryDiv').hide();

    // we add the on-click to the body and limit to the .eme_iban_button class, so that the iban-buttons that are only added via ajax are handled as well
    $('body').on('click', '.eme_iban_button', function(e) {
        e.preventDefault();
        let params = {
            'action': 'eme_get_payconiq_iban',
            'pg_pid': $(this).data('pg_pid'),
            'eme_admin_nonce': ememembers.translate_adminnonce
        };
        $.post(ajaxurl, params, function(data) {
            $('#button_'+data.payment_id).hide();
            $('span#payconiq_'+data.payment_id).html(data.iban);
        }, 'json');
        // return false to make sure the real form doesn't submit
        return false;
    });

    function updateShowHideFixedStartdate () {
        if ($('select#type').val() == 'fixed') {
            $('tr#startdate').show();
        } else {
            $('tr#startdate').hide();
        }
    }
    if ($('select#type').length) {
        $('select#type').on("change",updateShowHideFixedStartdate);
        updateShowHideFixedStartdate();
    }
    function updateShowHideReminder () {
        if ($('select#duration_period').val() == 'forever') {
            $('tr#reminder').hide();
            $('#duration_count').hide();
        } else {
            $('tr#reminder').show();
            $('#duration_count').show();
        }
    }
    if ($('select#duration_period').length) {
        $('select#duration_period').on("change",updateShowHideReminder);
        updateShowHideReminder();
    }

    if ($('select#paid').length) {
        $('select#paid').on("change",function(){
            if ($('select#paid').val() == '1' && $('input#dp_payment_date').val() == '') {
                let curdate=new Date();
                $('#dp_payment_date').fdatepicker().data('fdatepicker').selectDate(curdate);
            }
        });
    }

    //function updateShowHideMemberState () {
    //   if ($('select#status_automatic').val() == '1') {
    //      $('select#status').attr('disabled', true);
    //   } else {
    //      $('select#status').attr('disabled', false);
    //   }
    //}
    //if ($('select#status_automatic').length) {
    //   $('select#status_automatic').on("change",updateShowHideMemberState);
    //   updateShowHideMemberState();
    //}

    function updateShowHideRenewal () {
        if ($('input#allow_renewal').prop('checked')) {
            $('tr#tr_renewal_cutoff_days').fadeIn();
        } else {
            $('tr#tr_renewal_cutoff_days').fadeOut();
        }
    }
    $('input#allow_renewal').on("change",updateShowHideRenewal);
    updateShowHideRenewal();

    function updateShowHideOffline () {
        if ($('input[name="properties[use_offline]"]').prop('checked')) {
            $('tr#tr_offline').fadeIn();
        } else {
            $('tr#tr_offline').fadeOut();
        }
    }
    $('input[name="properties[use_offline]"]').on("change",updateShowHideOffline);
    updateShowHideOffline();

    // initially the div is not shown using display:none, so jquery has time to render it and then we call show()
    if ($('#membershipForm').length) {
        $('#membershipForm').validate({
            // ignore: false is added so the fields of tabs that are not visible when editing an event are evaluated too
            ignore: false,
            focusCleanup: true,
            errorClass: "eme_required",
            invalidHandler: function(e,validator) {
                $.each(validator.invalid, function(key, value) {
                    // get the closest tabname
                    let tabname=$('[name="'+key+'"]').closest('.eme-tab-content').attr('id');
                    activateTab(tabname);
                    // break the loop, we only want to switch to the first tab with the error
                    return false;
                });
            }
        });
    }

    // for autocomplete to work, the element needs to exist, otherwise JS errors occur
    // we check for that using length
    if ($('input[name=transferperson]').length) {
        let emeadmin_transferperson_timeout; // Declare a variable to hold the timeout ID
        $("input[name=transferperson]").on("input", function(e) {
            clearTimeout(emeadmin_transferperson_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                emeadmin_transferperson_timeout = setTimeout(function() {
                    $.post(ajaxurl,
                        { 
                            'q': inputValue,
                            'eme_admin_nonce': ememembers.translate_adminnonce,
                            'action': 'eme_autocomplete_memberperson',
                            'exclude_personid': $('input[name=person_id]').val(),
                            'membership_id': $('#membership_id').val(),
                            'related_member_id': $('#related_member_id').val()
                        },
                        function(data) {
                            suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                            $.each(data, function(index, item) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+' ('+eme_htmlDecode(item.person_id)+')</strong><br /><small>'+eme_htmlDecode(item.email)+'</small>')
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        if (item.person_id) {
                                            $('input[name=transferto_personid]').val(eme_htmlDecode(item.person_id));
                                            inputField.val(eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+' ('+eme_htmlDecode(item.person_id)+')  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+ememembers.translate_nomatchperson+'</strong>')
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
        $('input[name=transferperson]').on("keyup",function() {
            $('input[name=transferto_personid]').val('');
        }).change(function() {
            if ($(this).val()=='') {
                $('input[name=transferto_personid]').val('');
                $(this).attr('readonly', false).removeClass('clearable');
            }
        });
    }

    // for autocomplete to work, the element needs to exist, otherwise JS errors occur
    // we check for that using length
    if ($('input[name=chooserelatedmember]').length) {
        let emeadmin_chooserelatedmember_timeout; // Declare a variable to hold the timeout ID
        $("input[name=chooserelatedmember]").on("input", function(e) {
            clearTimeout(emeadmin_chooserelatedmember_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                emeadmin_chooserelatedmember_timeout = setTimeout(function() {
                    $.post(ajaxurl,
                        { 
                            'q': inputValue,
                            'member_id': $('#member_id').val(),
                            'membership_id': $('#membership_id').val(),
                            'eme_admin_nonce': ememembers.translate_adminnonce,
                            'action': 'eme_autocomplete_membermainaccount'
                        },
                        function(data) {
                            suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                            $.each(data, function(index, item) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+' ('+eme_htmlDecode(item.member_id)+')</strong><br /><small>'+eme_htmlDecode(item.email)+'</small>')
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        if (item.person_id) {
                                            $('input[name=related_member_id]').val(eme_htmlDecode(item.person_id));
                                            inputField.val(eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+' ('+eme_htmlDecode(item.member_id)+'  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+ememembers.translate_nomatchmember+'</strong>')
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
        $('input[name=chooserelatedmember]').on("keyup",function() {
            $('input[name=related_member_id]').val('');
        }).change(function() {
            if ($(this).val()=='') {
                $('input[name=related_member_id]').val('');
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
                    $.post(ajaxurl,
                        { 
                            'lastname': inputValue,
                            'eme_admin_nonce': ememembers.translate_adminnonce,
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
                                            $('.personal_info').hide();
                                            $('input[name=lastname]').val(eme_htmlDecode(item.lastname)).attr('readonly', true).show();
                                            $('input[name=firstname]').val(eme_htmlDecode(item.firstname)).attr('readonly', true).show();
                                            $('input[name=email]').val(eme_htmlDecode(item.email)).attr('readonly', true).show();
                                            $('input[name=person_id]').val(eme_htmlDecode(item.person_id));
                                            $('input[name=wp_id]').val(eme_htmlDecode(item.wp_id)).trigger('input');
                                            inputField.val(eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+'  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+ememembers.translate_nomatchperson+'</strong>')
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
        $('input[name=chooseperson]').on("change",function() {
            if ($(this).val()=='') {
                $('input[name=person_id]').val('');
                $('input[name=lastname]').val('').attr('readonly', false);
                $('input[name=firstname]').val('').attr('readonly', false);
                $('input[name=email]').val('').attr('readonly', false);
                $('input[name=wp_id]').val('');
                $('input[name=wp_id]').trigger('input');
                $(this).attr('readonly', false).removeClass('clearable');
                $('.personal_info').show();
            }
        });
    }
    function updateShowHideAdminActions () {
        let action=$('select#eme_admin_action').val();
        if ($.inArray(action,['acceptPayment','stopMembership']) >= 0) {
            $('#send_mail').val(1);
            $('span#span_sendmails').show();
        } else if (action == 'markUnpaid') {
            $('#send_mail').val(0);
            $('span#span_sendmails').show();
        } else {
            $('span#span_sendmails').hide();
        }
        if (action == 'deleteMembers') {
            $('span#span_trashperson').show();
        } else {
            $('span#span_trashperson').hide();
        }
        if (action == 'memberMails') {
            $('span#span_membermailtemplate').show();
        } else {
            $('span#span_membermailtemplate').hide();
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
    $('select#eme_admin_action').on("change",updateShowHideAdminActions);
    updateShowHideAdminActions();

    function updateShowHideFamilytpl () {
        if ($('input#family_membership').prop('checked')) {
            $('tr#tr_family_maxmembers').fadeIn();
            $('tr#tr_familymember_form_tpl').fadeIn();
            $('select[name="properties[familymember_form_tpl]"]').prop('required',true);
        } else {
            $('tr#tr_family_maxmembers').fadeOut();
            $('tr#tr_familymember_form_tpl').fadeOut();
            $('select[name="properties[familymember_form_tpl]"]').prop('required',false);
        }
    }
    $('input#family_membership').on("change",updateShowHideFamilytpl);
    updateShowHideFamilytpl();

    $('#newmember_attach_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: ememembers.translate_addattachments,
            button: {
                text: ememembers.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#newmember_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_newmember_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_newmember_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_newmember_attach_ids').val(tmp_ids_val);
                $('#newmember_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_newmember_attach_ids').val() != '') {
        $('#newmember_remove_attach_button').show();
    } else {
        $('#newmember_remove_attach_button').hide();
    }
    $('#newmember_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#newmember_attach_links').html('');
        $('#eme_newmember_attach_ids').val('');
        $('#newmember_attach_button').show();
        $('#newmember_remove_attach_button').hide();
    });

    $('#extended_attach_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: ememembers.translate_addattachments,
            button: {
                text: ememembers.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#extended_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_extended_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_extended_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_extended_attach_ids').val(tmp_ids_val);
                $('#extended_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_extended_attach_ids').val() != '') {
        $('#extended_remove_attach_button').show();
    } else {
        $('#extended_remove_attach_button').hide();
    }
    $('#extended_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#extended_attach_links').html('');
        $('#eme_extended_attach_ids').val('');
        $('#extended_attach_button').show();
        $('#extended_remove_attach_button').hide();
    });
    $('#paid_attach_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: ememembers.translate_addattachments,
            button: {
                text: ememembers.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#paid_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_paid_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_paid_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_paid_attach_ids').val(tmp_ids_val);
                $('#paid_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_paid_attach_ids').val() != '') {
        $('#paid_remove_attach_button').show();
    } else {
        $('#paid_remove_attach_button').hide();
    }
    $('#paid_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#paid_attach_links').html('');
        $('#eme_paid_attach_ids').val('');
        $('#paid_attach_button').show();
        $('#paid_remove_attach_button').hide();
    });

});
