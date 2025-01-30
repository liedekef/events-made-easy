jQuery(document).ready( function($) {
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

    function updateIntervalDescriptor () { 
        $('.interval-desc').hide();
        // for specific months, we just hide and return
        if ($('select#recurrence-frequency').val() == 'specific_months') {
            $('input#recurrence-interval').hide();
            $('span#specific_months_span').show();
            return;
        } else {
            $('input#recurrence-interval').show();
            $('span#specific_months_span').hide();
        }
        let number = '-plural';
        if ($('input#recurrence-interval').val() == 1 || $('input#recurrence-interval').val() == '') {
            number = '-singular';
        }
        let descriptor = 'span#interval-'+$('select#recurrence-frequency').val()+number;
        $(descriptor).show();
    }

    function updateIntervalSelectors () {
        $('span.alternate-selector').hide();
        $('span#'+ $('select#recurrence-frequency').val() + '-selector').show();
        //$('p.recurrence-tip').hide();
        //$('p#'+ $(this).val() + '-tip').show();
    }

    function updateShowHideRecurrence () {
        if($('input#event-recurrence').prop('checked')) {
            $('#event_recurrence_pattern').fadeIn();
            $('div#div_recurrence_event_duration').show();
            $('div#div_recurrence_date').show();
            $('div#div_event_date').hide();
        } else {
            $('#event_recurrence_pattern').hide();
            $('div#div_recurrence_event_duration').hide();
            $('div#div_recurrence_date').hide();
            $('div#div_event_date').show();
        }
    }

    function updateShowHideRecurrenceSpecificDays () {
        if ($('select#recurrence-frequency').val() == 'specific') {
            $('div#recurrence-intervals').hide();
            $('input#localized-rec-end-date').hide();
            $('p#recurrence-dates-explanation').hide();
            $('span#recurrence-dates-explanation-specificdates').show();
            $('#localized-rec-start-date').attr('required', true);
            $('#localized-rec-start-date').fdatepicker().data('fdatepicker').update('multipleDates',true);
        } else {
            $('div#recurrence-intervals').show();
            $('input#localized-rec-end-date').show();
            $('p#recurrence-dates-explanation').show();
            $('span#recurrence-dates-explanation-specificdates').hide();
            $('#localized-rec-start-date').attr('required', false);
            $('#localized-rec-start-date').fdatepicker().data('fdatepicker').update('multipleDates',false);
            // if the recurrence contained specific days before, clear those because that would not work upon save
            if ($('#rec-start-date-to-submit').val().indexOf(',') !== -1) {
                $('#localized-rec-start-date').fdatepicker().data('fdatepicker').clear();
            }
        }
    }

    function updateShowHideRsvp() {
        if ($('input#event_rsvp').prop('checked')) {
            $('div#rsvp-details').fadeIn();
            $('div#div_event_rsvp').fadeIn();
            $('div#div_dyndata').fadeIn();
            $('div#div_event_dyndata_allfields').fadeIn();
            $('div#div_event_payment_methods').fadeIn();
            $('div#div_event_registration_form_format').fadeIn();
            $('div#div_event_cancel_form_format').fadeIn();
            $('div#div_event_registration_recorded_ok_html').fadeIn();
            $('div#div_event_attendance_info').fadeIn();
        } else {
            $('div#rsvp-details').fadeOut();
        }
    }
    function updateShowHideTasks() {
        if ($('input#event_tasks').prop('checked')) {
            $('div#tab-tasks-container').fadeIn();
        } else {
            $('div#tab-tasks-container').fadeOut();
        }
    }
    function updateShowHideTodos() {
        if ($('input#event_todos').prop('checked')) {
            $('div#tab-todos-container').fadeIn();
        } else {
            $('div#tab-todos-container').fadeOut();
        }
    }

    function updateShowHideRsvpAutoApprove() {
        if ($('input#approval_required-checkbox').prop('checked')) {
            $('span#span_approval_required_mail_warning').fadeIn();
            $('#p_approve_settings').fadeIn();
            $('#details_pending').show();
            $('#div_event_registration_pending_reminder_email').show();
        } else {
            $('span#span_approval_required_mail_warning').hide();
            $('#p_approve_settings').fadeOut();
            $('#details_pending').hide();
            $('#div_event_registration_pending_reminder_email').hide();
        }
    }

    function updateShowHideTime() {
        if ($('input#eme_prop_all_day').prop('checked')) {
            $('div#time-selector').hide();
        } else {
            $('div#time-selector').show();
        }
    }

    function updateShowHideMultiPriceDescription() {
        if ($('input#price').length) {
            if ($('input#price').val().indexOf('||') !== -1) {
                $('tr#row_multiprice_desc').show();
                $('tr#row_price_desc').hide();
            } else {
                $('tr#row_multiprice_desc').hide();
                $('tr#row_price_desc').show();
            }
        }
    }

    function eme_event_location_autocomplete() {
        // for autocomplete to work, the element needs to exist, otherwise JS errors occur
        // we check for that using length
        if ($('input#location_name').length) {
            let emeadmin_locationname_timeout; // Declare a variable to hold the timeout ID
            $("input#location_name").on("input", function() {
                clearTimeout(emeadmin_locationname_timeout); // Clear the previous timeout
                let suggestions;
                let inputField = $(this);
                let inputValue = inputField.val();
                $(".eme-autocomplete-suggestions").remove();
                if (inputValue.length >= 2) {
                    emeadmin_locationname_timeout = setTimeout(function() {
                        $.post(ajaxurl,
                            { eme_admin_nonce: emeevents.translate_adminnonce, name: inputValue, action: 'eme_autocomplete_locations'},
                            function(data) {
                                suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                                $.each(data, function(index, item) {
                                    suggestions.append(
                                        $("<div class='eme-autocomplete-suggestion'></div>")
                                        .html("<strong>"+eme_htmlDecode(item.name)+'</strong><br /><small>'+eme_htmlDecode(item.address1)+' - '+eme_htmlDecode(item.city)+ '</small>')
                                        .on("click", function(e) {
                                            // we stop bubbling events, so other "onchange" events won't trigger anymore (like the one in eme_edit_maps for the name change, which might cause a wrong display depending on who wins :-)
                                            e.preventDefault();
                                            $('input#location_id').val(eme_htmlDecode(item.location_id)).attr("readonly", true);
                                            $('input#location_name').val(eme_htmlDecode(item.name)).attr("readonly", true);
                                            $('input#location_address1').val(eme_htmlDecode(item.address1)).attr("readonly", true);
                                            $('input#location_address2').val(eme_htmlDecode(item.address2)).attr("readonly", true);
                                            $('input#location_city').val(eme_htmlDecode(item.city)).attr("readonly", true);
                                            $('input#location_state').val(eme_htmlDecode(item.state)).attr("readonly", true);
                                            $('input#location_zip').val(eme_htmlDecode(item.zip)).attr("readonly", true);
                                            $('input#location_country').val(eme_htmlDecode(item.country)).attr("readonly", true);
                                            $('input#location_latitude').val(eme_htmlDecode(item.latitude)).attr("readonly", true);
                                            $('input#location_longitude').val(eme_htmlDecode(item.longitude)).attr("readonly", true);
                                            $('input#location_url').val(eme_htmlDecode(item.location_url)).attr("readonly", true);
                                            $('input#eme_loc_prop_map_icon').val(eme_htmlDecode(item.map_icon)).attr("readonly", true);
                                            $('input#eme_loc_prop_max_capacity').val(eme_htmlDecode(item.max_capacity)).attr("readonly", true);
                                            $('input#eme_loc_prop_online_only').val(eme_htmlDecode(item.online_only)).attr("disabled", true);
                                            $('#img_edit_location').show();
                                            if (typeof L !== 'undefined' && emeevents.translate_map_is_active==="true") {
                                                eme_displayAddress(0);
                                            }
                                        })
                                    );
                                });
                                if (!data.length) {
                                    suggestions.append(
                                        $("<div class='eme-autocomplete-suggestion'></div>")
                                        .html("<strong>"+emeevents.translate_nomatchlocation+'</strong>')
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

            $("input#location_name").change(function(){
                if ($("input#location_name").val()=='') {
                    $('input#location_id').val('');
                    $('input#location_name').val('').attr("readonly", false);
                    $('input#location_address1').val('').attr("readonly", false);
                    $('input#location_address2').val('').attr("readonly", false);
                    $('input#location_city').val('').attr("readonly", false);
                    $('input#location_state').val('').attr("readonly", false);
                    $('input#location_zip').val('').attr("readonly", false);
                    $('input#location_country').val('').attr("readonly", false);
                    $('input#location_latitude').val('').attr("readonly", false);
                    $('input#location_longitude').val('').attr("readonly", false);
                    $('input#eme_loc_prop_map_icon').attr('readonly', false);
                    $('input#eme_loc_prop_max_capacity').val('').attr('readonly', false);
                    $('input#eme_loc_prop_online_only').attr('disabled',false);
                    $('input#location_url').val('').attr('readonly', false);
                    $('#img_edit_location').hide();
                }
            });

            $('#img_edit_location').on("click",function(e) {
                e.preventDefault();
                $('input#location_id').val('');
                $('input#location_name').attr('readonly', false);
                $('input#location_address1').attr('readonly', false);
                $('input#location_address2').attr('readonly', false);
                $('input#location_city').attr('readonly', false);
                $('input#location_state').attr('readonly', false);
                $('input#location_zip').attr('readonly', false);
                $('input#location_country').attr('readonly', false);
                $('input#location_latitude').attr('readonly', false);
                $('input#location_longitude').attr('readonly', false);
                $('input#eme_loc_prop_map_icon').attr('readonly', false);
                $('input#eme_loc_prop_max_capacity').attr('readonly', false);
                $('input#eme_loc_prop_online_only').attr('disabled',false);
                $('input#location_url').attr('readonly', false);
                $('#img_edit_location').hide();
            });
            if ($('input#location_id').val()=='0') {
                $('input[name=location_name]').attr('readonly', false);
                $('input#location_address1').attr('readonly', false);
                $('input#location_address2').attr('readonly', false);
                $('input#location_city').attr('readonly', false);
                $('input#location_state').attr('readonly', false);
                $('input#location_zip').attr('readonly', false);
                $('input#location_country').attr('readonly', false);
                $('input#location_latitude').attr('readonly', false);
                $('input#location_longitude').attr('readonly', false);
                $('input#eme_loc_prop_map_icon').attr('readonly', false);
                $('input#eme_loc_prop_max_capacity').attr('readonly', false);
                $('input#eme_loc_prop_online_only').attr('disabled',false);
                $('input#location_url').attr('readonly', false);
                $('#img_edit_location').hide();
            } else {
                $('input[name=location_name]').attr('readonly', true);
                $('input#location_address1').attr('readonly', true);
                $('input#location_address2').attr('readonly', true);
                $('input#location_city').attr('readonly', true);
                $('input#location_state').attr('readonly', true);
                $('input#location_zip').attr('readonly', true);
                $('input#location_country').attr('readonly', true);
                $('input#location_latitude').attr('readonly', true);
                $('input#location_longitude').attr('readonly', true);
                $('input#eme_loc_prop_map_icon').attr('readonly', true);
                $('input#eme_loc_prop_max_capacity').attr('readonly', true);
                $('input#eme_loc_prop_online_only').attr('disabled',true);
                $('input#location_url').attr('readonly', true);
                $('#img_edit_location').show();
            }
            $('input#location_id').on("change",function(){
                if ($('input#location_id').val()=='') {
                    $('#img_edit_location').hide();
                } else {
                    $('#img_edit_location').show();
                }
            });

        } else if ($('input[name="location-select-name"]').length) {
            $('#location-select-id').on("change",function() {
                $.getJSON(self.location.href,{'eme_admin_action': 'autocomplete_locations', 'eme_admin_nonce': emeevents.translate_adminnonce, id: $(this).val()}, function(item){
                    $('input[name="location-select-name"]').val(item.name);
                    $('input[name="location-select-address1"]').val(item.address1);
                    $('input[name="location-select-address2"]').val(item.address2);
                    $('input[name="location-select-city"]').val(item.city);
                    $('input[name="location-select-state"]').val(item.state);
                    $('input[name="location-select-zip"]').val(item.zip);
                    $('input[name="location-select-country"]').val(item.country);
                    $('input[name="location-select-latitude"]').val(item.latitude);
                    $('input[name="location-select-longitude"]').val(item.longitude);
                    if(emeevents.translate_map_is_active === 'true') {
                        loadMapLatLong(item.name,item.address1,item.address2,item.city,item.state,item.zip,item.country,item.latitude,item.longitude);
                    }
                })
            });
        }
    }

    if ($('#localized-start-date').length) {
        $('#localized-start-date').fdatepicker({
            autoClose: true,
            onSelect: function(formattedDate,date,inst) {
                //$('#localized-end-date').fdatepicker().data('fdatepicker').update('minDate',date);
                startDate_formatted = inst.formatDate('Ymd',date);
                endDate_basic = $('#localized-end-date').fdatepicker().data('fdatepicker').selectedDates[0];
                endDate_formatted = inst.formatDate('Ymd',endDate_basic);
                if (endDate_formatted<startDate_formatted) {
                    $('#localized-end-date').fdatepicker().data('fdatepicker').selectDate(date);
                }
            }
        });
    }
    if ($('#localized-end-date').length) {
        $('#localized-end-date').fdatepicker({
            autoClose: true,
            onSelect: function(formattedDate,date,inst) {
                //$('#localized-start-date').fdatepicker().data('fdatepicker').update('maxDate',date);
                endDate_formatted = inst.formatDate('Ymd',date);
                startDate_basic = $('#localized-start-date').fdatepicker().data('fdatepicker').selectedDates[0];
                startDate_formatted = inst.formatDate('Ymd',startDate_basic);
                if (startDate_formatted>endDate_formatted) {
                    $('#localized-start-date').fdatepicker().data('fdatepicker').selectDate(date);
                }
            }
        });
    }
    if ($('#localized-rec-start-date').length) {
        $('#localized-rec-start-date').fdatepicker({
            autoClose: true,
            onSelect: function(formattedDate,date,inst) {
                // if multiple days are selected, date is an array, and then we don't touch it for now
                if (!Array.isArray(date)) {
                    $('#recurrence-dates-specificdates').text("");
                    //$('#localized-rec-end-date').fdatepicker().data('fdatepicker').update('minDate',date);
                    //startDate_formatted = inst.formatDate('Ymd',date);
                    //endDate_basic = $('#localized-rec-end-date').fdatepicker().data('fdatepicker').selectedDates[0];
                    //endDate_formatted = inst.formatDate('Ymd',endDate_basic);
                    //if (endDate_formatted<startDate_formatted) {
                    //	   $('#localized-rec-end-date').fdatepicker().data('fdatepicker').selectDate(date);
                    //}
                } else {
                    $('#recurrence-dates-specificdates').html('<br />'+emeevents.translate_selecteddates+'<br />');
                    $.each(date, function( index, value ) {
                        date_formatted = inst.formatDate(emeevents.translate_fdateformat,value);
                        $('#recurrence-dates-specificdates').append(date_formatted+'<br />');
                    });
                }
            }
        });
    }
    if ($('#localized-rec-end-date').length) {
        $('#localized-rec-end-date').fdatepicker({
            autoClose: true,
            onSelect: function(formattedDate,date,inst) {
                if (!Array.isArray(date)) {
                    //$('#localized-rec-start-date').fdatepicker().data('fdatepicker').update('maxDate',date);
                    endDate_formatted = inst.formatDate('Ymd',date);
                    startDate_basic = $('#localized-rec-start-date').fdatepicker().data('fdatepicker').selectedDates[0];
                    startDate_formatted = inst.formatDate('Ymd',startDate_basic);
                    if (startDate_formatted>endDate_formatted) {
                        $('#localized-rec-start-date').fdatepicker().data('fdatepicker').selectDate(date);
                    }
                }
            }
        });
    }

    $('#div_recurrence_date').hide();

    // if any of event_single_event_format,event_page_title_format,event_contactperson_email_body,event_respondent_email_body,event_registration_pending_email_body, event_registration_form_format, event_registration_updated_email_body
    // is empty: display default value on focus, and if the value hasn't changed from the default: empty it on blur

    function text_focus_blur(target,def_value) {
        $(target).on("focus",function(){
            if ($(this).val() == '') {
                $(this).val(def_value);
            }
        }).on("blur",function(){
            if ($(this).val() == def_value) {
                $(this).val('');
            }
        }); 
    }
    text_focus_blur('textarea#event_page_title_format',eme_event_page_title_format());
    text_focus_blur('textarea#event_single_event_format',eme_single_event_format());
    text_focus_blur('textarea#event_registration_recorded_ok_html',eme_registration_recorded_ok_html());
    text_focus_blur('input#eme_prop_event_contactperson_email_subject',eme_contactperson_email_subject());
    text_focus_blur('textarea#event_contactperson_email_body',eme_contactperson_email_body());
    text_focus_blur('input#eme_prop_contactperson_registration_pending_email_subject',eme_contactperson_pending_email_subject());
    text_focus_blur('textarea#eme_prop_contactperson_registration_pending_email_body',eme_contactperson_pending_email_body());
    text_focus_blur('input#eme_prop_contactperson_registration_cancelled_email_subject',eme_contactperson_cancelled_email_subject());
    text_focus_blur('textarea#eme_prop_contactperson_registration_cancelled_email_body',eme_contactperson_cancelled_email_body());
    text_focus_blur('input#eme_prop_contactperson_registration_ipn_email_subject',eme_contactperson_ipn_email_subject());
    text_focus_blur('textarea#eme_prop_contactperson_registration_ipn_email_body',eme_contactperson_ipn_email_body());
    text_focus_blur('input#eme_prop_contactperson_registration_paid_email_subject',eme_contactperson_paid_email_subject());
    text_focus_blur('textarea#eme_prop_contactperson_registration_paid_email_body',eme_contactperson_paid_email_body());
    text_focus_blur('input#eme_prop_event_respondent_email_subject',eme_respondent_email_subject());
    text_focus_blur('textarea#event_respondent_email_body',eme_respondent_email_body());
    text_focus_blur('input#eme_prop_event_registration_pending_email_subject',eme_registration_pending_email_subject());
    text_focus_blur('textarea#event_registration_pending_email_body',eme_registration_pending_email_body());
    text_focus_blur('input#eme_prop_event_registration_pending_reminder_email_subject',eme_registration_pending_reminder_email_subject());
    text_focus_blur('textarea#event_registration_pending_reminder_email_body',eme_registration_pending_reminder_email_body());
    text_focus_blur('input#eme_prop_event_registration_updated_email_subject',eme_registration_updated_email_subject());
    text_focus_blur('textarea#event_registration_updated_email_body',eme_registration_updated_email_body());
    text_focus_blur('input#eme_prop_event_registration_reminder_email_subject',eme_registration_reminder_email_subject());
    text_focus_blur('textarea#event_registration_reminder_email_body',eme_registration_reminder_email_body());
    text_focus_blur('input#eme_prop_event_registration_cancelled_email_subject',eme_registration_cancelled_email_subject());
    text_focus_blur('textarea#event_registration_cancelled_email_body',eme_registration_cancelled_email_body());
    text_focus_blur('input#eme_prop_event_registration_trashed_email_subject',eme_registration_trashed_email_subject());
    text_focus_blur('textarea#event_registration_trashed_email_body',eme_registration_trashed_email_body());
    text_focus_blur('input#eme_prop_event_registration_paid_email_subject',eme_registration_paid_email_subject());
    text_focus_blur('textarea#event_registration_paid_email_body',eme_registration_paid_email_body());
    text_focus_blur('textarea#event_registration_form_format',eme_registration_form_format());
    text_focus_blur('textarea#event_cancel_form_format',eme_cancel_form_format());

    if ($('#eventForm').length) {
        // initialize the code for auto-complete of location info
        eme_event_location_autocomplete();
        // the validate plugin can take other tabs/hidden fields into account
        $('#eventForm').validate({
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

    updateShowHideRecurrence();
    updateShowHideRsvp();
    updateShowHideTasks();
    updateShowHideTodos();
    updateShowHideRsvpAutoApprove();
    if ($('select#recurrence-frequency').length) {
        updateIntervalDescriptor(); 
        updateIntervalSelectors();
        updateShowHideRecurrenceSpecificDays();
    }
    updateShowHideTime();
    updateShowHideMultiPriceDescription();
    $('input#event-recurrence').on("change",updateShowHideRecurrence);
    $('input#event_tasks').on("change",updateShowHideTasks);
    $('input#event_todos').on("change",updateShowHideTodos);
    $('input#event_rsvp').on("change",updateShowHideRsvp);
    $('input#eme_prop_all_day').on("change",updateShowHideTime);
    $('input#price').on("change",updateShowHideMultiPriceDescription);
    $('input#approval_required-checkbox').on("change",updateShowHideRsvpAutoApprove);
    // recurrency elements
    $('input#recurrence-interval').on("keyup",updateIntervalDescriptor);
    $('select#recurrence-frequency').on("change",updateIntervalDescriptor);
    $('select#recurrence-frequency').on("change",updateIntervalSelectors);
    $('select#recurrence-frequency').on("change",updateShowHideRecurrenceSpecificDays);

    function validateEventForm() {
        // users cannot submit the event form unless some fields are filled
        if ($('input#event-recurrence').prop('checked') && $('input#localized-rec-start-date').val() == $('input#localized-rec-end-date').val()) {
            alert (emeevents.translate_startenddate_identical); 
            $('input#localized-rec-end-date').css('border','2px solid red');
            return false;
        } else {
            $('input#localized-rec-end-date').css('border','1px solid #DFDFDF');
        }
        // just before we return true, also set the disabled checkbox online_only to enabled, otherwise it won't submit if disabled
        $('input#eme_loc_prop_online_only').attr('disabled',false);
        return true;
    }
    $('#eventForm').bind('submit', validateEventForm);

    if ($('#EventsTableContainer').length) {
        let eventfields = {
            event_id: {
		key: true,
                title: emeevents.translate_id,
                visibility: 'hidden'
            },
            event_name: {
                title: emeevents.translate_name,
                visibility: 'fixed'
            },
            event_status: {
                title: emeevents.translate_status,
                width: '5%'
            },
            copy: {
                title: emeevents.translate_copy,
                sorting: false,
                width: '2%',
                listClass: 'eme-jtable-center'
            },
            rsvp: {
                title: emeevents.translate_rsvp,
                sorting: false,
                width: '2%',
                listClass: 'eme-jtable-center'
            },
            eventprice: {
                title: emeevents.translate_eventprice,
                sorting: false
            },
            location_name: {
                title: emeevents.translate_location
            },
            event_start: {
                title: emeevents.translate_eventstart,
                width: '5%'
            },
            creation_date: {
                title: emeevents.translate_created_on,
                visibility: 'hidden',
                width: '5%'
            },
            modif_date: {
                title: emeevents.translate_modified_on,
                visibility: 'hidden',
                width: '5%'
            },
            recinfo: {
                title: emeevents.translate_recinfo,
                sorting: false
            }
        }
        let extrafields=$('#EventsTableContainer').data('extrafields').toString().split(',');
        let extrafieldnames=$('#EventsTableContainer').data('extrafieldnames').toString().split(',');
        let extrafieldsearchable=$('#EventsTableContainer').data('extrafieldsearchable').toString().split(',');
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
                $.extend(eventfields,extrafield);
            }
        });

        //Prepare jtable plugin
        let $_GET = getQueryParams(document.location.search);
        $('#EventsTableContainer').jtable({
            title: emeevents.translate_events,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'event_start ASC, event_name ASC',
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            toolbar: {
                items: [{
                    text: emeevents.translate_csv,
                    click: function () {
                        jtable_csv('#EventsTableContainer','events');
                    }
                },
                    {
                        text: emeevents.translate_print,
                        click: function () {
                            $('#EventsTableContainer').find('table:first').printElement();
                        }
                    }
                ]
            },
            actions: {
                listAction: ajaxurl
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_events_list",
                    'eme_admin_nonce': emeevents.translate_adminnonce,
                    'trash': $_GET['trash'],
                    'scope': $('#scope').val(),
                    'status': $('#status').val(),
                    'category': $('#category').val(),
                    'search_name': $('#search_name').val(),
                    'search_location': $('#search_location').val(),
                    'search_start_date': $('#search_start_date').val(),
                    'search_end_date': $('#search_end_date').val(),
                    'search_customfields': $('#search_customfields').val(),
                    'search_customfieldids': $('#search_customfieldids').val()
                }
                return params;
            },
            fields: eventfields
        });

        // Load list from server
        $('#EventsTableContainer').jtable('load');

        // Actions button
        $('#EventsActionsButton').on("click",function (e) {
            e.preventDefault();
            let selectedRows = $('#EventsTableContainer').jtable('selectedRows');
            let do_action = $('#eme_admin_action').val();
            let send_trashmails = $('#send_trashmails').val();
            let addtocategory = $('#addtocategory').val();

            let action_ok=1;
            if (selectedRows.length > 0 && do_action != '') {
                if ((do_action=='trashEvents' || do_action=='deleteEvents' || do_action=='deleteRecurrences') && !confirm(emeevents.translate_areyousuretodeleteselected)) {
                    action_ok=0;
                }
                if (action_ok==1) {
                    $('#EventsActionsButton').text(emeevents.translate_pleasewait);
                    $('#EventsActionsButton').prop('disabled', true);
                    let ids = [];
                    selectedRows.each(function () {
                        ids.push($(this).data('record')['event_id']);
                    });

                    let idsjoined = ids.join(); //will be such a string '2,5,7'
                    let params = {
                        'event_id': idsjoined,
                        'action': 'eme_manage_events',
                        'do_action': do_action,
                        'send_trashmails': send_trashmails,
                        'addtocategory': addtocategory,
                        'eme_admin_nonce': emeevents.translate_adminnonce };

                    $.post(ajaxurl, params, function(data) {
                        $('#EventsTableContainer').jtable('reload');
                        $('#EventsActionsButton').text(emeevents.translate_apply);
                        $('#EventsActionsButton').prop('disabled', false);
                        $('div#events-message').html(data.Message);
                        $('div#events-message').show();
                        $('div#events-message').delay(3000).fadeOut('slow');
                    }, 'json');
                }
            }
            // return false to make sure the real form doesn't submit
            return false;
        });

        // Re-load records when user click 'load records' button.
        $('#EventsLoadRecordsButton').on("click",function (e) {
            e.preventDefault();
            $('#EventsTableContainer').jtable('load');
            // return false to make sure the real form doesn't submit
            return false;
        });
    }

    if ($('#RecurrencesTableContainer').length) {
        let recurrencefields = {
            recurrence_id: {
                key: true,
                title: emeevents.translate_id,
                visibility: 'hidden'
            },
            event_name: {
                title: emeevents.translate_name,
                sorting: false,
                visibility: 'fixed'
            },
            event_status: {
                title: emeevents.translate_status,
                sorting: false,
                width: '5%'
            },
            copy: {
                title: emeevents.translate_copy,
                sorting: false,
                width: '2%',
                listClass: 'eme-jtable-center'
            },
            eventprice: {
                title: emeevents.translate_eventprice,
                sorting: false
            },
            location_name: {
                title: emeevents.translate_location,
                sorting: false,
            },
            creation_date: {
                title: emeevents.translate_created_on,
                visibility: 'hidden',
                width: '5%'
            },
            modif_date: {
                title: emeevents.translate_modified_on,
                visibility: 'hidden',
                width: '5%'
            },
            recinfo: {
                title: emeevents.translate_recinfo,
                sorting: false
            },
            rec_singledur: {
                title: emeevents.translate_rec_singledur,
                sorting: false
            }
        }
        let $_GET = getQueryParams(document.location.search);
        //Prepare jtable plugin
        $('#RecurrencesTableContainer').jtable({
            title: emeevents.translate_recurrences,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: '',
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            toolbar: {
                items: [{
                    text: emeevents.translate_csv,
                    click: function () {
                        jtable_csv('#RecurrencesTableContainer','recurrences');
                    }
                },
                    {
                        text: emeevents.translate_print,
                        click: function () {
                            $('#RecurrencesTableContainer').find('table:first').printElement();
                        }
                    }]
            },
            actions: {
                listAction: ajaxurl
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_recurrences_list",
                    'eme_admin_nonce': emeevents.translate_adminnonce,
                    'trash': $_GET['trash'],
                    'scope': $('#scope').val(),
                    'status': $('#status').val(),
                    'category': $('#category').val(),
                    'search_name': $('#search_name').val(),
                    'search_location': $('#search_location').val(),
                    'search_start_date': $('#search_start_date').val(),
                    'search_end_date': $('#search_end_date').val(),
                    'search_customfields': $('#search_customfields').val(),
                    'search_customfieldids': $('#search_customfieldids').val()
                }
                return params;
            },
            fields: recurrencefields
        });

        // Load list from server
        $('#RecurrencesTableContainer').jtable('load');

        // Actions button
        $('#RecurrencesActionsButton').on("click",function (e) {
            e.preventDefault();
            let selectedRows = $('#RecurrencesTableContainer').jtable('selectedRows');
            let do_action = $('#eme_admin_action').val();
            let rec_new_start_date = $('#rec_new_start_date').val();
            let rec_new_end_date = $('#rec_new_end_date').val();

            let action_ok=1;
            if (selectedRows.length > 0 && do_action != '') {
                if (do_action=='deleteRecurrences' && !confirm(emeevents.translate_areyousuretodeleteselected)) {
                    action_ok=0;
                }
                if (action_ok==1) {
                    $('#RecurrencesActionsButton').text(emeevents.translate_pleasewait);
                    $('#RecurrencesActionsButton').prop('disabled', true);
                    let ids = [];
                    selectedRows.each(function () {
                        ids.push($(this).data('record')['recurrence_id']);
                    });

                    let idsjoined = ids.join(); //will be such a string '2,5,7'
                    let params = {
                        'recurrence_id': idsjoined,
                        'action': 'eme_manage_recurrences',
                        'do_action': do_action,
                        'rec_new_start_date': rec_new_start_date,
                        'rec_new_end_date': rec_new_end_date,
                        'eme_admin_nonce': emeevents.translate_adminnonce };

                    $.post(ajaxurl, params, function(data) {
                        $('#RecurrencesTableContainer').jtable('reload');
                        $('#RecurrencesActionsButton').text(emeevents.translate_apply);
                        $('#RecurrencesActionsButton').prop('disabled', false);
                        $('div#events-message').html(data.Message);
                        $('div#events-message').show();
                        $('div#events-message').delay(3000).fadeOut('slow');
                    }, 'json');
                }
            }
            // return false to make sure the real form doesn't submit
            return false;
        });

        // Re-load records when user click 'load records' button.
        $('#RecurrencesLoadRecordsButton').on("click",function (e) {
            e.preventDefault();
            $('#RecurrencesTableContainer').jtable('load');
            // return false to make sure the real form doesn't submit
            return false;
        });
    }

    function updateShowHideStuff () {
        let action=$('select#eme_admin_action').val();
        if (action == 'addCategory') {
            jQuery('span#span_addtocategory').show();
        } else {
            jQuery('span#span_addtocategory').hide();
        }
        if (action == 'trashEvents') {
            $('span#span_sendtrashmails').show();
        } else {
            $('span#span_sendtrashmails').hide();
        }
        if (action == 'extendRecurrences') {
            $('span#span_extendrecurrences').show();
        } else {
            $('span#span_extendrecurrences').hide();
        }
    }
    updateShowHideStuff();
    $('select#eme_admin_action').on("change",updateShowHideStuff);

    function changeEventAdminPageTitle() {
        let eventname=$('input[name=event_name]').val();
        if (!eventname) {
            title=emeevents.translate_insertnewevent;
        } else {
            title=emeevents.translate_editeventstring;
            title=title.replace(/%s/g, eventname);
        }
        jQuery(document).prop('title', eme_htmlDecode(title));
    }
    if ($('input[name=event_name]').length) {
        changeEventAdminPageTitle();
        $('input[name=event_name]').on("keyup",changeEventAdminPageTitle);
    }

    // for the image 
    $('#event_remove_image_button').on("click",function(e) {
        $('#event_image_url').val('');
        $('#event_image_id').val('');
        $('#eme_event_image_example' ).attr('src','').hide();
        $('#event_image_button' ).show();
        $('#event_remove_image_button' ).hide();
    });
    $('#event_image_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: emeevents.translate_selectfeaturedimg,
            button: {
                text: emeevents.translate_setfeaturedimg
            },
            // Tell the modal to show only images.
            library: {
                type: 'image'
            },
            multiple: false  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#event_image_url').val(attachment.url);
                $('#event_image_id').val(attachment.id);
                $('#eme_event_image_example' ).attr('src',attachment.url).show();
                $('#event_image_button' ).hide();
                $('#event_remove_image_button' ).show();
            });
        }).open();
    });
    if ($('#event_image_url').val() != '') {
        $('#event_image_button' ).hide();
        $('#event_remove_image_button' ).show();
        $('#eme_event_image_example' ).show();
    } else {
        $('#event_image_button' ).show();
        $('#event_remove_image_button' ).hide();
        $('#eme_event_image_example' ).hide();
    }

    $('#event_author.eme_select2_wpuser_class').select2({
        width: '100%',
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 1000,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page || 1,
                    pagesize: 10,
                    action: 'eme_wpuser_select2',
                    eme_admin_nonce: emeevents.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                // parse the results into the format expected by Select2
                // since we are using custom formatting functions we do not need to
                // alter the remote JSON data, except to indicate that infinite
                // scrolling can be used
                params.page = params.page || 1;
                return {
                    results: data.Records,
                    pagination: {
                        more: (params.page * 10) < data.TotalRecordCount
                    }
                };
            },
            cache: true
        }
    });
    $('#event_contactperson_id.eme_select2_wpuser_class').select2({
        width: '100%',
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 1000,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page || 1,
                    pagesize: 10,
                    action: 'eme_wpuser_select2',
                    eme_admin_nonce: emeevents.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                // parse the results into the format expected by Select2
                // since we are using custom formatting functions we do not need to
                // alter the remote JSON data, except to indicate that infinite
                // scrolling can be used
                params.page = params.page || 1;
                return {
                    results: data.Records,
                    pagination: {
                        more: (params.page * 10) < data.TotalRecordCount
                    }
                };
            },
            cache: true
        },
        allowClear: true,
        placeholder: emeevents.translate_selectcontact
    });

});
