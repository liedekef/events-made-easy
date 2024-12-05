function eme_htmlDecode(value){ 
    return jQuery('<div/>').html(value).text(); 
}

function eme_tog(v){return v?'addClass':'removeClass';}

// this function is being called in multiple js files, so needs to be global
function eme_lastname_clearable() {
    if (jQuery('input[name=lastname]').val()=='') {
        jQuery('input[name=lastname]').attr('readonly', false).removeClass('clearable');
        jQuery('input[name=firstname]').val('').attr('readonly', false);
        jQuery('input[name=address1]').val('').attr('readonly', false);
        jQuery('input[name=address2]').val('').attr('readonly', false);
        jQuery('input[name=city]').val('').attr('readonly', false);
        jQuery('input[name=state]').val('').attr('readonly', false);
        jQuery('input[name=zip]').val('').attr('readonly', false);
        jQuery('input[name=country]').val('').attr('readonly', false);
        jQuery('input[name=email]').val('').attr('readonly', false);
        jQuery('input[name=phone]').val('').attr('readonly', false);
        jQuery('input[name=wp_id]').val('');
        jQuery('input[name=person_id]').val('');
        jQuery('input[name=wp_id]').trigger('input');
    }
    if (jQuery('input[name=lastname]').val()!='') {
        jQuery('input[name=lastname]').addClass('clearable x');
    }
}

/*
var eme_CaptchaCallback = function() {
    jQuery('.g-recaptcha').each(function(index, el) {
        grecaptcha.render(el, {
            'sitekey' : jQuery(el).attr('data-sitekey')
            ,'theme' : jQuery(el).attr('data-theme')
            ,'size' : jQuery(el).attr('data-size')
            ,'tabindex' : jQuery(el).attr('data-tabindex')
            ,'callback' : jQuery(el).attr('data-callback')
            ,'expired-callback' : jQuery(el).attr('data-expired-callback')
            ,'error-callback' : jQuery(el).attr('data-error-callback')
        });
    });
};
*/

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

    function loadCalendar(tableDiv, fullcalendar, htmltable, htmldiv, showlong_events, month, year, cat_chosen, author_chosen, contact_person_chosen, location_chosen, not_cat_chosen,template_chosen,holiday_chosen,weekdays,language) {
        if (fullcalendar === undefined) {
            fullcalendar = 0;
        }

        if (showlong_events === undefined) {
            showlong_events = 0;
        }
        fullcalendar = (typeof fullcalendar == 'undefined')? 0 : fullcalendar;
        showlong_events = (typeof showlong_events == 'undefined')? 0 : showlong_events;
        month = (typeof month == 'undefined')? 0 : month;
        year = (typeof year == 'undefined')? 0 : year;
        cat_chosen = (typeof cat_chosen == 'undefined')? '' : cat_chosen;
        not_cat_chosen = (typeof not_cat_chosen == 'undefined')? '' : not_cat_chosen;
        author_chosen = (typeof author_chosen == 'undefined')? '' : author_chosen;
        contact_person_chosen = (typeof contact_person_chosen == 'undefined')? '' : contact_person_chosen;
        location_chosen = (typeof location_chosen == 'undefined')? '' : location_chosen;
        template_chosen = (typeof template_chosen == 'undefined')? 0 : template_chosen;
        holiday_chosen = (typeof holiday_chosen == 'undefined')? 0 : holiday_chosen;
        weekdays = (typeof weekdays == 'undefined')? '' : weekdays;
        language = (typeof language == 'undefined')? '' : language;
        $.post(emebasic.translate_ajax_url, {
            eme_frontend_nonce: emebasic.translate_frontendnonce,
            action: 'eme_calendar',
            calmonth: parseInt(month,10),
            calyear: parseInt(year,10),
            full : fullcalendar,
            long_events: showlong_events,
            htmltable: htmltable,
            htmldiv: htmldiv,
            category: cat_chosen,
            notcategory: not_cat_chosen,
            author: author_chosen,
            contact_person: contact_person_chosen,
            location_id: location_chosen,
            template_id: template_chosen,
            holiday_id: holiday_chosen,
            weekdays: weekdays,
            lang: language
        }, function(data){
            $('#'+tableDiv).replaceWith(data);
            // replaceWith removes all event handlers, so we need to re-add them
            $('a.eme-cal-prev-month').on('click',function(e) {
                e.preventDefault();
                $(this).html('<img src="'+emebasic.translate_plugin_url+'images/spinner.gif">');
                loadCalendar($(this).data('calendar_divid'), $(this).data('full'), $(this).data('htmltable'), $(this).data('htmldiv'), $(this).data('long_events'), $(this).data('month'), $(this).data('year'), $(this).data('category'), $(this).data('author'), $(this).data('contact_person'), $(this).data('location_id'), $(this).data('notcategory'),$(this).data('template_id'),$(this).data('holiday_id'),$(this).data('weekdays'),$(this).data('language'));
            });
            $('a.eme-cal-next-month').on('click',function(e) {
                e.preventDefault();
                $(this).html('<img src="'+emebasic.translate_plugin_url+'images/spinner.gif">');
                loadCalendar($(this).data('calendar_divid'), $(this).data('full'), $(this).data('htmltable'), $(this).data('htmldiv'), $(this).data('long_events'), $(this).data('month'), $(this).data('year'), $(this).data('category'), $(this).data('author'), $(this).data('contact_person'), $(this).data('location_id'), $(this).data('notcategory'),$(this).data('template_id'),$(this).data('holiday_id'),$(this).data('weekdays'),$(this).data('language'));
            });
        });
    }

    // everything that has the class eme-showifjs show be visible when JS is running
    $('.eme-showifjs').show();

    $('a.eme-cal-prev-month').on('click',function(e) {
        e.preventDefault();
        $(this).html('<img src="'+emebasic.translate_plugin_url+'images/spinner.gif">');
        loadCalendar($(this).data('calendar_divid'), $(this).data('full'), $(this).data('htmltable'), $(this).data('htmldiv'), $(this).data('long_events'), $(this).data('month'), $(this).data('year'), $(this).data('category'), $(this).data('author'), $(this).data('contact_person'), $(this).data('location_id'), $(this).data('notcategory'),$(this).data('template_id'),$(this).data('holiday_id'),$(this).data('weekdays'),$(this).data('language'));
    });
    $('a.eme-cal-next-month').on('click',function(e) {
        e.preventDefault();
        $(this).html('<img src="'+emebasic.translate_plugin_url+'images/spinner.gif">');
        loadCalendar($(this).data('calendar_divid'), $(this).data('full'), $(this).data('htmltable'), $(this).data('htmldiv'), $(this).data('long_events'), $(this).data('month'), $(this).data('year'), $(this).data('category'), $(this).data('author'), $(this).data('contact_person'), $(this).data('location_id'), $(this).data('notcategory'),$(this).data('template_id'),$(this).data('holiday_id'),$(this).data('weekdays'),$(this).data('language'));
    });

    // the next code adds an "X" to input fields of class clearable if not empty
    $(document).on('input', '.clearable', function(){
        $(this)[eme_tog(this.value)]('x');
    }).on('mousemove', '.x', function( e ){
        $(this)[eme_tog(this.offsetWidth-18 < e.clientX-this.getBoundingClientRect().left)]('onX');
    }).on('touchstart click', '.onX', function( ev ){
        ev.preventDefault();
        $(this).removeClass('x onX').val('').change();
    });

    function eme_genericform_json(form_id,form_name,ajax_action) {
        $('#'+form_id).find('#loading_gif').show();
        $('#'+form_id).find(':submit').hide();
        let alldata = new FormData($('#'+form_id)[0]);
        alldata.append('action', ajax_action);
        $.ajax({url: emebasic.translate_ajax_url, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('#'+form_id).find('#loading_gif').hide();
                if (data.Result=='REDIRECT_IMM') {
                    $('div#eme-'+form_name+'-message-ok-'+form_id).html(data.htmlmessage);
                } else if (data.Result=='OK') {
                    $('div#eme-'+form_name+'-message-ok-'+form_id).html(data.htmlmessage);
                    $('div#eme-'+form_name+'-message-ok-'+form_id).show();
                    $('div#eme-'+form_name+'-message-error-'+form_id).hide();
                    if (data.keep_form==1) {
                        // we are requested to show the form again, so let's just reset it to the initial state
                        $('#'+form_id).trigger('reset');
                        if ($('#'+form_id).find('#eme_captcha_img').length) {
                            src=$('#'+form_id).find('#eme_captcha_img').attr('src');
                            // the booking is ok and the form needs to be presented again, so refresh the captcha
                            // we need a new captcha, we take the src and add a timestamp to it, so the browser won't cache it
                            // also: remove possible older timestamps, to be clean
                            src=src.replace(/&ts=.*/,'');
                            let timestamp = new Date().getTime();
                            $('#'+form_id).find('#eme_captcha_img').attr('src',src+'&ts='+timestamp);
                        }
                        $('#'+form_id).find(':submit').show();
                        $('#'+form_id).find('#loading_gif').hide();
                    } else {
                        $('div#div_eme-'+form_name+'-form-'+form_id).hide();
                    }
                    $(document).scrollTop( $('div#eme-'+form_name+'-message-ok-'+form_id).offset().top - $(window).height()/2 + $('div#eme-'+form_name+'-message-ok-'+form_id).height()/2);  
                } else {
                    $('div#eme-'+form_name+'-message-error-'+form_id).html(data.htmlmessage);
                    $('div#eme-'+form_name+'-message-ok-'+form_id).hide();
                    $('div#eme-'+form_name+'-message-error-'+form_id).show();
                    $('#'+form_id).find(':submit').show();
                    $(document).scrollTop( $('div#eme-'+form_name+'-message-error-'+form_id).offset().top - $(window).height()/2 + $('div#eme-'+form_name+'-message-error-'+form_id).height()/2);  
                }
            })
            .fail(function(xhr, textStatus, error){
                $('div#eme-'+form_name+'-message-error-'+form_id).html(emebasic.translate_error);
                $('div#eme-'+form_name+'-message-ok-'+form_id).hide();
                $('div#eme-'+form_name+'-message-error-'+form_id).show();
                $('#'+form_id).find('#loading_gif').hide();
                $('#'+form_id).find(':submit').show();
                $(document).scrollTop( $('div#eme-'+form_name+'-message-error-'+form_id).offset().top - $(window).height()/2 + $('div#eme-'+form_name+'-message-error-'+form_id).height()/2);  
            });
    }

    function eme_add_member_json(form_id) {
        $('#'+form_id).find(':submit').hide();
        $('#'+form_id).find('#member_loading_gif').show();
        alldata = new FormData($('#'+form_id)[0]);
        alldata.append('action', 'eme_add_member');
        $.ajax({url: emebasic.translate_ajax_url, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('#'+form_id).find('#member_loading_gif').hide();
                if (data.Result=='OK') {
                    $('div#eme-member-addmessage-ok-'+form_id).html(data.htmlmessage);
                    $('div#eme-member-addmessage-ok-'+form_id).show();
                    $('div#eme-member-addmessage-error-'+form_id).hide();
                    $('div#div_eme-member-form-'+form_id).hide();
                    if (typeof data.paymentform !== 'undefined') {
                        $('div#div_eme-payment-form-'+form_id).html(data.paymentform);
                        $('div#div_eme-payment-form-+form_id').show();
                    }
                    if (typeof data.paymentredirect !== 'undefined') {
                        setTimeout(function () {
                            window.location.href=data.paymentredirect;
                        }, parseInt(data.waitperiod));
                    }
                    $(document).scrollTop( $('div#eme-member-addmessage-ok-'+form_id).offset().top - $(window).height()/2 + $('div#eme-member-addmessage-ok-'+form_id).height()/2);  
                } else {
                    $('div#eme-member-addmessage-error-'+form_id).html(data.htmlmessage);
                    $('div#eme-member-addmessage-ok-'+form_id).hide();
                    $('div#eme-member-addmessage-error-'+form_id).show();
                    $('#'+form_id).find(':submit').show();
                    $(document).scrollTop( $('div#eme-member-addmessage-error-'+form_id).offset().top - $(window).height()/2 + $('div#eme-member-addmessage-error-'+form_id).height()/2);  
                }
            })
            .fail(function(xhr, textStatus, error){
                $('div#eme-member-addmessage-error-'+form_id).html(emebasic.translate_error);
                $('div#eme-member-addmessage-error-'+form_id).append(xhr.responseText+' : '+error);
                $('div#eme-member-addmessage-ok-'+form_id).hide();
                $('div#eme-member-addmessage-error-'+form_id).show();
                $('#'+form_id).find('#member_loading_gif').hide();
                $('#'+form_id).find(':submit').show();
                $(document).scrollTop( $('div#eme-member-addmessage-error-'+form_id).offset().top - $(window).height()/2 + $('div#eme-member-addmessage-error-'+form_id).height()/2);  
            });
    }

    function eme_add_booking_json(form_id) {
        $('#'+form_id).find('#rsvp_add_loading_gif').show();
        $('#'+form_id).find(':submit').hide();
        alldata = new FormData($('#'+form_id)[0]);
        alldata.append('action','eme_add_bookings');
        // we add the following 4 params to the request too, so we can check in the backend if it comes from an invite
        let $_GET = getQueryParams(document.location.search);
        if (typeof $_GET['eme_invite']!=='undefined') {
            alldata.append('eme_invite',$_GET['eme_invite']);
        }
        if (typeof $_GET['eme_email']!=='undefined') {
            alldata.append('eme_email',$_GET['eme_email']);
        }
        if (typeof $_GET['eme_ln']!=='undefined') {
            alldata.append('eme_ln',$_GET['eme_ln']);
        }
        if (typeof $_GET['eme_fn']!=='undefined') {
            alldata.append('eme_fn',$_GET['eme_fn']);
        }
        $.ajax({url: emebasic.translate_ajax_url, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json' })
            .done(function(data){
                if (data.Result=='OK') {
                    $('div#eme-rsvp-addmessage-ok-'+form_id).html(data.htmlmessage);
                    $('div#eme-rsvp-addmessage-ok-'+form_id).show();
                    $('div#eme-rsvp-addmessage-error-'+form_id).hide();
                    if (data.keep_form==1) {
                        // we are requested to show the form again, so let's just reset it to the initial state
                        $('#'+form_id).trigger('reset');
                        eme_dynamic_bookingdata_json(form_id);
                        if ($('#'+form_id).find('#eme_captcha_img').length) {
                            src=$('#'+form_id).find('#eme_captcha_img').attr('src');
                            // the booking is ok and the form needs to be presented again, so refresh the captcha
                            // we need a new captcha, we take the src and add a timestamp to it, so the browser won't cache it
                            // also: remove possible older timestamps, to be clean
                            src=src.replace(/&ts=.*/,'');
                            let timestamp = new Date().getTime();
                            $('#'+form_id).find('#eme_captcha_img').attr('src',src+'&ts='+timestamp);
                        }
                        $('#'+form_id).find(':submit').show();
                        $('#'+form_id).find('#rsvp_add_loading_gif').hide();
                    } else {
                        $('div#div_eme-rsvp-form-'+form_id).hide();
                        if (typeof data.paymentform !== 'undefined') {
                            $('div#div_eme-payment-form-'+form_id).html(data.paymentform);
                            $('div#div_eme-payment-form-'+form_id).show();
                        }
                        if (typeof data.paymentredirect !== 'undefined') {
                            setTimeout(function () {
                                window.location.href=data.paymentredirect;
                            }, parseInt(data.waitperiod));
                        }
                    }
                    // scroll to the message shown, with an added offset of half the screen height, so the message doesn't start at the high top of the screen
                    $(document).scrollTop( $('div#eme-rsvp-addmessage-ok-'+form_id).offset().top - $(window).height()/2 + $('div#eme-rsvp-addmessage-ok-'+form_id).height()/2);  
                } else {
                    $('div#eme-rsvp-addmessage-error-'+form_id).html(data.htmlmessage);
                    $('div#eme-rsvp-addmessage-ok-'+form_id).hide();
                    $('div#eme-rsvp-addmessage-error-'+form_id).show();
                    // scroll to the message shown, with an added offset of half the screen height, so the message doesn't start at the high top of the screen
                    $(document).scrollTop( $('div#eme-rsvp-addmessage-error-'+form_id).offset().top - $(window).height()/2 + $('div#eme-rsvp-addmessage-error-'+form_id).height()/2);  
                    $('#'+form_id).find(':submit').show();
                    $('#'+form_id).find('#rsvp_add_loading_gif').hide();
                }
            })
            .fail(function(xhr, textStatus, error){
                $('div#eme-rsvp-addmessage-error-'+form_id).html(emebasic.translate_error);
                $('div#eme-rsvp-addmessage-ok-'+form_id).hide();
                $('div#eme-rsvp-addmessage-error-'+form_id).show();
                // scroll to the message shown, with an added offset of half the screen height, so the message doesn't start at the high top of the screen
                $(document).scrollTop( $('div#eme-rsvp-addmessage-error-'+form_id).offset().top - $(window).height()/2 + $('div#eme-rsvp-addmessage-error-'+form_id).height()/2);  
                $('#'+form_id).find(':submit').show();
                $('#'+form_id).find('#rsvp_add_loading_gif').hide();
            });
    }

    function eme_dynamic_bookingprice_json(form_id) {
        $('#'+form_id).find(':submit').hide();
        let alldata = new FormData($('#'+form_id)[0]);
        // now calculate the price, but only do it if we have a "full" form
        if ($('#'+form_id).find('span#eme_calc_bookingprice').length) {
            alldata.append('action', 'eme_calc_bookingprice');
            alldata.append('eme_frontend_nonce', emebasic.translate_frontendnonce);
            $('#'+form_id).find('span#eme_calc_bookingprice').html('<img src="'+emebasic.translate_plugin_url+'images/spinner.gif">');
            $.ajax({url: emebasic.translate_ajax_url, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
                .done(function(data){
                    $('#'+form_id).find(':submit').show();
                    $('#'+form_id).find('span#eme_calc_bookingprice').html(data.total);
                })
                .fail(function(xhr, textStatus, error){
                    $('#'+form_id).find(':submit').show();
                    $('#'+form_id).find('span#eme_calc_bookingprice').html('Invalid reply');
                });
        } else {
            $('#'+form_id).find(':submit').show();
        }
    }
    function eme_dynamic_bookingdata_json(form_id) {
        $('#'+form_id).find(':submit').hide();
        let alldata = new FormData($('#'+form_id)[0]);
        if ($('#'+form_id).find('div#eme_dyndata').length) {
            $('#'+form_id).find('div#eme_dyndata').html('<img src="'+emebasic.translate_plugin_url+'images/spinner.gif">');
            alldata.append('action', 'eme_dyndata_rsvp');
            alldata.append('eme_frontend_nonce', emebasic.translate_frontendnonce);
            $.ajax({url: emebasic.translate_ajax_url, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
                .done(function(data){
                    $('#'+form_id).find(':submit').show();
                    $('#'+form_id).find('div#eme_dyndata').html(data.Result);
                    // make sure to init select2 for dynamic added fields
                    if ($('.eme_select2_width50_class.dynamicfield').length) {
                        $('.eme_select2_width50_class.dynamicfield').select2({width: '50%'});
                    }
                    // make sure to init the datapicker for dynamic added fields
                    if ($('.eme_formfield_fdate.dynamicfield').length) {
                        $('.eme_formfield_fdate.dynamicfield').fdatepicker({
                            todayButton: new Date(),
                            clearButton: true,
                            language: emebasic.translate_flanguage,
                            firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                            altFieldDateFormat: 'Y-m-d',
                            dateFormat: emebasic.translate_fdateformat
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
                            minutesStep: parseInt(emebasic.translate_minutesStep),
                            language: emebasic.translate_flanguage,
                            firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                            altFieldDateFormat: 'Y-m-d H:i:00',
                            dateFormat: emebasic.translate_fdateformat,
                            timeFormat: emebasic.translate_ftimeformat
                        });
                        $.each($('.eme_formfield_fdatetime'), function() {
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
                            timeFormat: emebasic.translate_ftimeformat
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

                    eme_dynamic_bookingprice_json(form_id);
                })
                .fail(function(xhr, textStatus, error){
                    $('#'+form_id).find(':submit').show();
                });
        } else {
            eme_dynamic_bookingprice_json(form_id);
        }
    }
    function eme_dynamic_memberprice_json(form_id) {
        $('#'+form_id).find(':submit').hide();
        let alldata = new FormData($('#'+form_id)[0]);
        // calculate the price, but only do it if we have a "full" form
        if ($('#'+form_id).find('span#eme_calc_memberprice').length) {
            $('#'+form_id).find('span#eme_calc_memberprice').html('<img src="'+emebasic.translate_plugin_url+'images/spinner.gif">');
            alldata.append('action', 'eme_calc_memberprice');
            alldata.append('eme_frontend_nonce', emebasic.translate_frontendnonce);
            $.ajax({url: emebasic.translate_ajax_url, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
                .done(function(data){
                    $('#'+form_id).find(':submit').show();
                    $('#'+form_id).find('span#eme_calc_memberprice').html(data.total);
                })
                .fail(function(xhr, textStatus, error){
                    $('#'+form_id).find(':submit').show();
                    $('#'+form_id).find('span#eme_calc_memberprice').html('Invalid reply');
                });
        } else {
            $('#'+form_id).find(':submit').show();
        }
    }

    function eme_dynamic_familymemberdata_json(form_id) {
        $('#'+form_id).find(':submit').hide();
        let alldata = new FormData($('#'+form_id)[0]);
        if ($('#'+form_id).find('div#eme_dyndata_family').length) {
            $('#'+form_id).find('div#eme_dyndata_family').html('<img src="'+emebasic.translate_plugin_url+'images/spinner.gif">');
            alldata.append('action', 'eme_dyndata_familymember');
            alldata.append('eme_frontend_nonce', emebasic.translate_frontendnonce);
            $.ajax({url: emebasic.translate_ajax_url, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
                .done(function(data){
                    $('#'+form_id).find(':submit').show();
                    $('#'+form_id).find('div#eme_dyndata_family').html(data.Result);
                    // make sure to init select2 for dynamic added fields
                    if ($('.eme_select2_width50_class.dynamicfield').length) {
                        $('.eme_select2_width50_class.dynamicfield').select2({width: '50%'});
                    }
                    // make sure to init the datapicker for dynamic added fields
                    if ($('.eme_formfield_fdate.dynamicfield').length) {
                        $('.eme_formfield_fdate.dynamicfield').fdatepicker({
                            todayButton: new Date(),
                            clearButton: true,
                            language: emebasic.translate_flanguage,
                            firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                            altFieldDateFormat: 'Y-m-d',
                            dateFormat: emebasic.translate_fdateformat
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
                            minutesStep: parseInt(emebasic.translate_minutesStep),
                            language: emebasic.translate_flanguage,
                            firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                            altFieldDateFormat: 'Y-m-d H:i:00',
                            dateFormat: emebasic.translate_fdateformat,
                            timeFormat: emebasic.translate_ftimeformat
                        });
                        $.each($('.eme_formfield_fdatetime.dynamicfield'), function() {
                            if ($(this).data('date')  && $(this).data('date') != '0000-00-00 00:00:00' ) {
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
                            timeFormat: emebasic.translate_ftimeformat
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
                })
                .fail(function(xhr, textStatus, error){
                    $('#'+form_id).find(':submit').show();
                });
        } else {
            $('#'+form_id).find(':submit').show();
        }
    }
    function eme_dynamic_memberdata_json(form_id) {
        $('#'+form_id).find(':submit').hide();
        let alldata = new FormData($('#'+form_id)[0]);
        if ($('#'+form_id).find('div#eme_dyndata').length) {
            $('#'+form_id).find('div#eme_dyndata').html('<img src="'+emebasic.translate_plugin_url+'images/spinner.gif">');
            alldata.append('action', 'eme_dyndata_member');
            // normally the nonce is already added (because it is different for adding or frontend editing of a member), so we check
            if ($.inArray('eme_frontend_nonce', alldata) == -1) {
                alldata.append('eme_frontend_nonce', emebasic.translate_frontendnonce);
            }
            $.ajax({url: emebasic.translate_ajax_url, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
                .done(function(data){
                    $('#'+form_id).find(':submit').show();
                    $('#'+form_id).find('div#eme_dyndata').html(data.Result);
                    // make sure to init select2 for dynamic added fields
                    if ($('.eme_select2_width50_class.dynamicfield').length) {
                        $('.eme_select2_width50_class.dynamicfield').select2({width: '50%'});
                    }
                    // make sure to init the datapicker for dynamic added fields
                    if ($('.eme_formfield_fdate.dynamicfield').length) {
                        $('.eme_formfield_fdate.dynamicfield').fdatepicker({
                            todayButton: new Date(),
                            clearButton: true,
                            language: emebasic.translate_flanguage,
                            firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                            altFieldDateFormat: 'Y-m-d',
                            dateFormat: emebasic.translate_fdateformat
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
                            minutesStep: parseInt(emebasic.translate_minutesStep),
                            language: emebasic.translate_flanguage,
                            firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                            altFieldDateFormat: 'Y-m-d H:i:00',
                            dateFormat: emebasic.translate_fdateformat,
                            timeFormat: emebasic.translate_ftimeformat
                        });
                        $.each($('.eme_formfield_fdatetime.dynamicfield'), function() {
                            if ($(this).data('date') != '' && $(this).data('date') != '0000-00-00 00:00:00' ) {
                                $(this).fdatepicker().data('fdatepicker').selectDate($(this).data('date'));
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
                            timeFormat: emebasic.translate_ftimeformat
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
                    eme_dynamic_memberprice_json(form_id);
                })
                .fail(function(xhr, textStatus, error){
                    $('#'+form_id).find(':submit').show();
                });
        } else {
            eme_dynamic_memberprice_json(form_id);
        }
    }

    if ($("input[name=lastname]").length && $("input[name=lastname]").data('clearable')) {
        $('input[name=lastname]').on("change",eme_lastname_clearable);
        eme_lastname_clearable();
    }

    // using the below on-syntax propagates the onchange from the form to all elements below, also those dynamically added
    // some basic rsvp and member form validation
    // normally required fields are handled by the browser, but not always (certainly not datepicker fields)
    $('.eme_submit_button').on('click', function(event) {
        let valid=true;
        let parent_form_id=$(this.form).attr('id');
        $.each($('input:text[required]'), function() {
            //if ($(this).prop('required') && $(this).val() == '') {
            if ($(this).is(":visible") && $(this).closest("form").attr('id') == parent_form_id) {
                let myval=$(this).val();
                // the really emty string is catched by the browser, we check for just a whitespace string
                if (myval.match(/^\s+$/)) {
                    $(this).addClass('eme_required');
                    $(document).scrollTop($(this).offset().top - $(window).height()/2 );
                    valid=false;
                } else {
                    $(this).removeClass('eme_required');
                }
            }
        });
        $.each($('.eme_formfield_fdatetime[required]'), function() {
            //if ($(this).prop('required') && $(this).val() == '') {
            if ($(this).is(":visible") && $(this).closest("form").attr('id') == parent_form_id) {
                let myval=$(this).val();
                if (myval.match(/^\s*$/)) {
                    $(this).addClass('eme_required');
                    $(document).scrollTop($(this).offset().top - $(window).height()/2 );
                    valid=false;
                } else {
                    $(this).removeClass('eme_required');
                }
            }
        });
        $.each($('.eme_formfield_fdate[required]'), function() {
            //if ($(this).prop('required') && $(this).val() == '') {
            if ($(this).is(":visible") && $(this).closest("form").attr('id') == parent_form_id) {
                let myval=$(this).val();
                if (myval.match(/^\s*$/)) {
                    $(this).addClass('eme_required');
                    $(document).scrollTop($(this).offset().top - $(window).height()/2 );
                    valid=false;
                } else {
                    $(this).removeClass('eme_required');
                }
            }
        });
        $.each($('.eme-checkbox-group-required'), function() {
            if ($(this).is(":visible") && $(this).closest("form").attr('id') == parent_form_id) {
                number_checked=0;
                $.each($(this).children("input:checkbox"), function() {
                    if ($(this).is(':checked')) {
                        number_checked = number_checked+1;
                    }
                });
                if (number_checked == 0) {
                    $(this).addClass('eme_required');
                    $(document).scrollTop($(this).offset().top - $(window).height()/2 );
                    valid=false;
                } else {
                    $(this).removeClass('eme_required');
                }
            }
        });
        if (!valid) {
            return false;
        }
    });
    $('[name=eme-rsvp-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        if ($(this).find('#massmail').length && $(this).find('#massmail').val()!=1 && $(this).find('#MassMailDialog').length) {
            let dialog = $('#MassMailDialog')[0];
            dialog.showModal();

            $('#dialog-confirm').on('click', function(e) {
                e.preventDefault();
                dialog.close();
                eme_add_booking_json(form_id);
            });

            $('#dialog-cancel').on('click', function(e) {
                e.preventDefault();
                dialog.close();
            });
        } else {
            eme_add_booking_json(form_id);
        }
    });
    $('[name=eme-member-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        if ($(this).find('#massmail').length && $(this).find('#massmail').val()!=1 && $(this).find('#MassMailDialog').length) {
            let dialog = $('#MassMailDialog')[0];
            dialog.showModal();

            $('#dialog-confirm').on('click', function(e) {
                e.preventDefault();
                dialog.close();
                eme_add_member_json(form_id);
            });

            $('#dialog-cancel').on('click', function(e) {
                e.preventDefault();
                dialog.close();
            });
        } else {
            eme_add_member_json(form_id);
        }
    });
    $('[name=eme-cancel-payment-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        eme_genericform_json(form_id,'cancel-payment','eme_cancel_payment');
    });
    $('[name=eme-cancel-bookings-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        eme_genericform_json(form_id,'cancel-bookings','eme_cancel_bookings');
    });
    $('[name=eme-subscribe-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        eme_genericform_json(form_id,'subscribe','eme_subscribe');
    });
    $('[name=eme-unsubscribe-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        eme_genericform_json(form_id,'unsubscribe','eme_unsubscribe');
    });
    $('[name=eme-rpi-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        eme_genericform_json(form_id,'rpi','eme_rpi');
    });
    $('[name=eme-gdpr-approve-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        eme_genericform_json(form_id,'gdpr-approve','eme_gdpr_approve');
    });
    $('[name=eme-cpi-request-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        eme_genericform_json(form_id,'cpi-request','eme_cpi_request');
    });
    $('[name=eme-cpi-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        eme_genericform_json(form_id,'cpi','eme_cpi');
    });
    $('[name=eme-tasks-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        eme_genericform_json(form_id,'tasks','eme_tasks');
    });
    $('[name=eme-fs-form]').on('submit', function(event) {
        event.preventDefault();
        let form_id=$(this).attr('id');
        if (emebasic.translate_fs_wysiwyg=="true") {
            let editor = tinymce.get('event_notes');
            if ( editor !== null) {
                editor.save();
            }
            let editor2 = tinymce.get('location_description');
            if ( editor2 !== null) {
                editor.save();
            }
        }
        eme_genericform_json(form_id,'fs','eme_frontend_submit');
    });
    // when doing form changes, we set a small delay to avoid calling the json function too many times
    let timer;
    let delay = 1000; // 1 seconds delay after last input
    if ($('[name=eme-rsvp-form]').length) {
        // the on-syntax helps to propagate the event handler to dynamic created fields too
        $('[name=eme-rsvp-form]').on('input', function(event) {
            let form_id=$(this).attr('id');
            // for fields with no dynamic updates, we only consider a possible price change
            if ($(event.target).is('.nodynamicupdates')) {
                if ($(event.target).is('.dynamicprice')) {
                    window.clearTimeout(timer);
                    $('#'+form_id).find(':submit').hide();
                    timer = window.setTimeout(function(){
                        eme_dynamic_bookingprice_json(form_id);
                    }, delay);
                }
                return;
            }
            window.clearTimeout(timer);
            $('#'+form_id).find(':submit').hide();
            timer = window.setTimeout(function(){
                eme_dynamic_bookingdata_json(form_id);
            }, delay);
        });
        $('[name=eme-rsvp-form]').each(function() {
            let form_id=$(this).attr('id');
            eme_dynamic_bookingdata_json(form_id);
        });
    }
    if ($('#eme-rsvp-adminform').length) {
        // the on-syntax helps to propagate the event handler to dynamic created fields too
        $('#eme-rsvp-adminform').on('input', function(event) {
            let form_id=$(this).attr('id');
            // for fields with no dynamic updates, we only consider a possible price change
            if ($(event.target).is('.nodynamicupdates')) {
                if ($(event.target).is('.dynamicprice')) {
                    window.clearTimeout(timer);
                    $('#'+form_id).find(':submit').hide();
                    timer = window.setTimeout(function(){
                        eme_dynamic_bookingprice_json(form_id);
                    }, delay);
                }
                return;
            }
            window.clearTimeout(timer);
            $('#'+form_id).find(':submit').hide();
            timer = window.setTimeout(function(){
                eme_dynamic_bookingdata_json(form_id);
            }, delay);
        });
        // the next variable is used to see if this is the first time the admin form is shown
        // that way we know if we can get the already filled out answers for a booking when first editing it
        eme_dynamic_bookingdata_json('eme-rsvp-adminform');
    }

    if ($('[name=eme-member-form]').length) {
        // the on-syntax helps to propagate the event handler to dynamic created fields too
        $('[name=eme-member-form]').on('input', function(event) {
            let form_id=$(this).attr('id');
            if ($(event.target).attr('id') == 'familycount' ) {
                eme_dynamic_familymemberdata_json(form_id);
            }
            // for fields with no dynamic updates, we only consider a possible price change
            if ($(event.target).is('.nodynamicupdates')) {
                if ($(event.target).is('.dynamicprice')) {
                    window.clearTimeout(timer);
                    $('#'+form_id).find(':submit').hide();
                    timer = window.setTimeout(function(){
                        eme_dynamic_memberprice_json(form_id);
                    }, delay);
                }
                return;
            }
            window.clearTimeout(timer);
            $('#'+form_id).find(':submit').hide();
            timer = window.setTimeout(function(){
                eme_dynamic_memberdata_json(form_id);
            }, delay);
        });
        $('[name=eme-member-form]').each(function() {
            let form_id=$(this).attr('id');
            eme_dynamic_familymemberdata_json(form_id);
            eme_dynamic_memberdata_json(form_id);
        });
    }
    if ($('#eme-member-adminform').length) {
        // the on-syntax helps to propagate the event handler to dynamic created fields too
        $('#eme-member-adminform').on('input', function(event) {
            let form_id=$(this).attr('id');
            // for fields with no dynamic updates, we only consider a possible price change
            if ($(event.target).is('.nodynamicupdates')) {
                if ($(event.target).is('.dynamicprice')) {
                    $('#'+form_id).find(':submit').hide();
                    window.clearTimeout(timer);
                    timer = window.setTimeout(function(){
                        eme_dynamic_memberprice_json(form_id);
                    }, delay);
                }
                return;
            }
            window.clearTimeout(timer);
            $('#'+form_id).find(':submit').hide();
            timer = window.setTimeout(function(){
                eme_dynamic_memberdata_json(form_id);
                $('#'+form_id).find(':submit').show();
            }, delay);
        });
        eme_dynamic_memberdata_json('eme-member-adminform');
    }
    if ($('.eme_formfield_fdatetime').length) {
        $('.eme_formfield_fdatetime').fdatepicker({
            todayButton: new Date(),
            clearButton: true,
            closeButton: true,
            timepicker: true,
            minutesStep: parseInt(emebasic.translate_minutesStep),
            language: emebasic.translate_flanguage,
            firstDay: parseInt(emebasic.translate_firstDayOfWeek),
            altFieldDateFormat: 'Y-m-d H:i:00',
            dateFormat: emebasic.translate_fdateformat,
            timeFormat: emebasic.translate_ftimeformat
        });
        $.each($('.eme_formfield_fdatetime'), function() {
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
    if ($('.eme_formfield_fdate').length) {
        $('.eme_formfield_fdate').fdatepicker({
            todayButton: new Date(),
            clearButton: true,
            closeButton: true,
            autoClose: true,
            language: emebasic.translate_flanguage,
            firstDay: parseInt(emebasic.translate_firstDayOfWeek),
            altFieldDateFormat: 'Y-m-d',
            dateFormat: emebasic.translate_fdateformat
        });
        $.each($('.eme_formfield_fdate'), function() {
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
    if ($('.eme_formfield_ftime').length) {
        $('.eme_formfield_ftime').fdatepicker({
            timepicker: true,
            onlyTimepicker: true,
            clearButton: true,
            closeButton: true,
            minutesStep: parseInt(emebasic.translate_minutesStep),
            language: emebasic.translate_flanguage,
            altFieldDateFormat: 'H:i:00',
            timeFormat: emebasic.translate_ftimeformat
        });
        $.each($('.eme_formfield_ftime'), function() {
            if ($(this).data('date') && $(this).data('date') != '00:00:00' ) {
                $(this).fdatepicker().data('fdatepicker').selectDate($(this).data('date'));
                // to avoid it being done multiple times
                $(this).removeData('date');
                $(this).removeAttr('date');
            }
            if ($(this).data('timeFormat')) {
                $(this).fdatepicker().data('fdatepicker').update('timeFormat', $(this).data('timeFormat'));
                // to avoid it being done multiple times
                $(this).removeData('timeFormat');
                $(this).removeAttr('timeFormat');
            }
        });
    }
    if ($('.eme_formfield_timepicker').length) {
        $('.eme_formfield_timepicker').timepicker({
            timeFormat: emebasic.translate_ftimeformat
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
    if ($('.eme_select2_width50_class').length) {
        $('.eme_select2_width50_class').select2({width: '50%'});
    }
    if ($('#country_code.eme_select2_country_class').length) {
        $('#country_code.eme_select2_country_class').select2({
            // ajax based results mess up the width, so we need to set it
            width: '100%',
            ajax: {
                url: emebasic.translate_ajax_url,
                type: 'POST',
                dataType: 'json',
                delay: 500,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        page: params.page || 1,
                        pagesize: 30,
                        action: 'eme_select_country',
                        eme_frontend_nonce: emebasic.translate_frontendnonce
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
                            more: (params.page * 30) < data.TotalRecordCount
                        }
                    };
                },
                cache: true
            },
            allowClear: true,
            placeholder: emebasic.translate_selectcountry
        });
        // if the country_code changes, clear the state_code if present
        $('#country_code.eme_select2_country_class').on('change', function (e) {
            // Do something
            if ($('#state_code.eme_select2_state_class').length) {
                $('#state_code.eme_select2_state_class').val(null).trigger('change');
            }
        });
    }
    if ($('#state_code.eme_select2_state_class').length) {
        $('#state_code.eme_select2_state_class').select2({
            // ajax based results mess up the width, so we need to set it
            width: '100%',
            ajax: {
                url: emebasic.translate_ajax_url,
                type: 'POST',
                dataType: 'json',
                delay: 500,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        page: params.page || 1,
                        pagesize: 30,
                        country_code: $('#country_code').val(),
                        action: 'eme_select_state',
                        eme_frontend_nonce: emebasic.translate_frontendnonce
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
                            more: (params.page * 30) < data.TotalRecordCount
                        }
                    };
                },
                cache: true
            },
            allowClear: true,
            placeholder: emebasic.translate_selectstate
        });
    }

    // for the person image
    if ($('#eme_person_image_button').length) {
        $('#eme_person_remove_old_image').on("click",function(e) {
            $('#eme_person_image_id').val('');
            $('#eme_person_image_example' ).attr('src','');
            $('#eme_person_current_image' ).hide();
            $('#eme_person_no_image' ).show();
            $('#eme_person_remove_old_image' ).hide();
            $('#eme_person_image_button' ).prop("value",emebasic.translate_chooseimg);
        });
        $('#eme_person_image_button').on("click",function(e) {
            e.preventDefault();
            let custom_uploader = wp.media({
                title: emebasic.translate_selectimg,
                button: {
                    text: emebasic.translate_setimg
                },
                // Tell the modal to show only images.
                library: {
                    type: 'image'
                },
                multiple: false  // Set this to true to allow multiple files to be selected
            }).on('select', function() {
                let attachment = custom_uploader.state().get('selection').first().toJSON();
                $('#eme_person_image_id').val(attachment.id);
                $('#eme_person_image_example' ).attr('src',attachment.url);
                $('#eme_person_current_image' ).show();
                $('#eme_person_no_image' ).hide();
                $('#eme_person_remove_old_image' ).show();
                $('#eme_person_image_button' ).prop("value",emebasic.translate_replaceimg);
            }).open();
        });
        if (parseInt($('#eme_person_image_id').val()) >0) {
            $('#eme_person_no_image' ).hide();
            $('#eme_person_current_image' ).show();
            $('#eme_person_remove_old_image' ).show();
            $('#eme_person_image_button' ).prop("value",emebasic.translate_replaceimg);
        } else {
            $('#eme_person_no_image' ).show();
            $('#eme_person_current_image' ).hide();
            $('#eme_person_remove_old_image' ).hide();
            $('#eme_person_image_button' ).prop("value",emebasic.translate_chooseimg);
        }
    }

    // center the payment form, if present on the page
    if ($('#eme-payment-form').length) {
        $(document).scrollTop( $('div#eme-payment-form').offset().top - $(window).height()/2 + $('div#eme-payment-form').height()/2);
    }

    $('.eme_select2').select2();
});
