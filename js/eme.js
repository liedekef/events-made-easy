// Events Made Easy – Modernized, Shortened, and Readable JS
// All AJAX "action" params and logic match the original PHP backend.

function eme_debounce(func, wait = 300) {
    let timeout;
    return function(...args) { // Use a regular function (not arrow) to preserve `this`
        const context = this; // Capture `this` from the caller
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

function eme_htmlDecode(inputStr) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = inputStr;
    return textarea.value;
}

function eme_getQueryParams(qs) {
    qs = qs.split('+').join(' ');
    let params = {}, tokens, re = /[?&]?([^=]+)=([^&]*)/g;
    while ((tokens = re.exec(qs))) {
        params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
    }
    return params;
}
const $_GET = eme_getQueryParams(document.location.search);

function eme_tog(v) { return v ? 'addClass' : 'removeClass'; }

function eme_lastname_clearable() {
    const $ln = jQuery('input[name=lastname]');
    const fields = ['firstname', 'address1', 'address2', 'city', 'state', 'zip', 'country', 'email', 'phone'];
    if ($ln.val() == '') {
        $ln.attr('readonly', false).removeClass('clearable x');
        fields.forEach(f => jQuery(`input[name=${f}]`).val('').prop('readonly', false));
        jQuery('input[name=wp_id], input[name=person_id]').val('');
    }
    if ($ln.val() != '') {
        $ln.addClass('clearable x');
    }
}

jQuery(document).ready(function ($) {
    $('.eme-showifjs').show();

    // --- Unified AJAX Handler for Booking/Member/Generic forms ---
    function eme_ajax_form(form_id, action, okSel, errSel, loadingSel, extraParams = {}) {
        const $form = $('#' + form_id);
        $form.find(':submit').hide();
        if (loadingSel) $form.find(loadingSel).show();
        let alldata = new FormData($form[0]);
        alldata.append('action', action);
        Object.entries(extraParams).forEach(([k, v]) => alldata.append(k, v));
        jQuery.ajax({
            url: emebasic.translate_ajax_url,
            data: alldata,
            cache: false,
            contentType: false,
            processData: false,
            type: 'POST',
            dataType: 'json'
        }).done(function (data) {
            if (loadingSel) $form.find(loadingSel).hide();
            $form.find(':submit').show();
            if (data.Result === "OK" || data.Result === "REDIRECT_IMM") {
                if (okSel) $(okSel).html(data.htmlmessage).show();
                if (errSel) $(errSel).hide();
                if (data.keep_form == 1) {
                    $form.trigger('reset');
                    eme_refresh_captcha(form_id);
                } else {
                    $form.closest('[id^=div_eme-]').hide();
                }
                if (data.paymentform) $(`#div_eme-payment-form-${form_id}`).html(data.paymentform).show();
                if (data.paymentredirect) setTimeout(() => window.location.href = data.paymentredirect, parseInt(data.waitperiod));
                eme_scrollToMsg(okSel);
            } else {
                if (errSel) $(errSel).html(data.htmlmessage).show();
                if (okSel) $(okSel).hide();
                eme_scrollToMsg(errSel);
            }
        }).fail(function (xhr, textStatus, error) {
            if (errSel) $(errSel).html(emebasic.translate_error + (xhr?.responseText ? '<br>' + xhr.responseText : '')).show();
            if (okSel) $(okSel).hide();
            if (loadingSel) $form.find(loadingSel).hide();
            $form.find(':submit').show();
            eme_scrollToMsg(errSel);
        });
    }

    function eme_refresh_captcha(form_id) {
        const $captcha = $(`#${form_id}`).find('#eme_captcha_img');
        if ($captcha.length) {
            let src = $captcha.attr('src').replace(/&ts=.*/, '');
            $captcha.attr('src', src + '&ts=' + Date.now());
        }
    }

    function eme_scrollToMsg(sel) {
        const $msg = $(sel);
        if ($msg.length) {
            $(document).scrollTop($msg.offset().top - $(window).height() / 2 + $msg.height() / 2);
        }
    }

    // --- Unified Dynamic Data/Price AJAX ---
    function eme_dynamic_price_json(form_id, isBooking = true) {
        const $form = $('#' + form_id);
        $form.find(':submit').hide();
        let alldata = new FormData($form[0]);
        const priceSpans = isBooking
            ? [{ sel: 'span#eme_calc_bookingprice', action: 'eme_calc_bookingprice' }, { sel: 'span#eme_calc_bookingprice_detail', action: 'eme_calc_bookingprice_detail' }]
            : [{ sel: 'span#eme_calc_memberprice', action: 'eme_calc_memberprice' }, { sel: 'span#eme_calc_memberprice_detail', action: 'eme_calc_memberprice_detail' }];
        let found = false;
        priceSpans.forEach(({ sel, action }) => {
            const $span = $form.find(sel);
            if ($span.length) {
                found = true;
                $span.html('<img src="' + emebasic.translate_plugin_url + 'images/spinner.gif">');
                alldata.set('action', action);
                alldata.set('eme_frontend_nonce', emebasic.translate_frontendnonce);
                jQuery.ajax({
                    url: emebasic.translate_ajax_url,
                    data: alldata,
                    cache: false,
                    contentType: false,
                    processData: false,
                    type: 'POST',
                    dataType: 'json'
                }).done(data => {
                    $form.find(':submit').show();
                    $span.html(data.total);
                }).fail(() => {
                    $form.find(':submit').show();
                    $span.html('Invalid reply');
                });
            }
        });
        if (!found) $form.find(':submit').show();
    }

    function eme_dynamic_data_json(form_id, isBooking = true) {
        const $form = $('#' + form_id);
        $form.find(':submit').hide();
        let alldata = new FormData($form[0]);
        const dataDivSel = 'div#eme_dyndata';
        const action = isBooking ? 'eme_dyndata_rsvp' : 'eme_dyndata_member';
        const $dataDiv = $form.find(dataDivSel);
        if ($dataDiv.length) {
            $dataDiv.html('<img src="' + emebasic.translate_plugin_url + 'images/spinner.gif">');
            alldata.set('action', action);
            alldata.set('eme_frontend_nonce', emebasic.translate_frontendnonce);
            jQuery.ajax({
                url: emebasic.translate_ajax_url,
                data: alldata,
                cache: false,
                contentType: false,
                processData: false,
                type: 'POST',
                dataType: 'json'
            }).done(data => {
                $form.find(':submit').show();
                $dataDiv.html(data.Result);
                eme_init_widgets(true);
                eme_dynamic_price_json(form_id, isBooking);
            }).fail(() => $form.find(':submit').show());
        } else {
            eme_dynamic_price_json(form_id, isBooking);
        }
    }

    function eme_dynamic_familymemberdata_json(form_id) {
        const $form = $('#' + form_id);
        $form.find(':submit').hide();
        let alldata = new FormData($form[0]);
        const $dataDiv = $form.find('div#eme_dyndata_family');
        if ($dataDiv.length) {
            $dataDiv.html('<img src="' + emebasic.translate_plugin_url + 'images/spinner.gif">');
            alldata.set('action', 'eme_dyndata_familymember');
            alldata.set('eme_frontend_nonce', emebasic.translate_frontendnonce);
            jQuery.ajax({
                url: emebasic.translate_ajax_url,
                data: alldata,
                cache: false,
                contentType: false,
                processData: false,
                type: 'POST',
                dataType: 'json'
            }).done(data => {
                $form.find(':submit').show();
                $dataDiv.html(data.Result);
                eme_init_widgets(true);
            }).fail(() => $form.find(':submit').show());
        } else {
            $form.find(':submit').show();
        }
    }

    // --- Widget Initialization ---
    function eme_init_widgets(dynamicOnly = false) {
        const dynamicSelector = dynamicOnly ? '.dynamicfield' : '';

        if ($('.eme_formfield_fdatetime' + dynamicSelector).length) {
            $('.eme_formfield_fdatetime' + dynamicSelector).fdatepicker({
                todayButton: new Date(),
                clearButton: true,
                closeButton: true,
                fieldSizing: true,
                timepicker: true,
                minutesStep: parseInt(emebasic.translate_minutesStep),
                language: emebasic.translate_flanguage,
                firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                altFieldDateFormat: 'Y-m-d H:i:00',
                multipleDatesSeparator: ", ",
                dateFormat: emebasic.translate_fdateformat,
                timeFormat: emebasic.translate_ftimeformat
            }).each(function () {
                const $this = $(this);
                if ($this.data('date') && $this.data('date') != '0000-00-00 00:00:00') {
                    $this.data('fdatepicker').selectDate($this.data('date'));
                    $this.removeData('date').removeAttr('date');
                }
                if ($this.data('dateFormat')) {
                    $this.data('fdatepicker').update('dateFormat', $this.data('dateFormat'));
                    $this.removeData('dateFormat').removeAttr('dateFormat');
                }
                if ($this.data('timeFormat')) {
                    $this.data('fdatepicker').update('timeFormat', $this.data('timeFormat'));
                    $this.removeData('timeFormat').removeAttr('timeFormat');
                }
            });
        }

        if ($('.eme_formfield_fdate' + dynamicSelector).length) {
            $('.eme_formfield_fdate' + dynamicSelector).fdatepicker({
                todayButton: new Date(),
                clearButton: true,
                closeButton: true,
                autoClose: true,
                fieldSizing: true,
                language: emebasic.translate_flanguage,
                firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                altFieldDateFormat: 'Y-m-d',
                multipleDatesSeparator: ", ",
                dateFormat: emebasic.translate_fdateformat
            }).each(function () {
                const $this = $(this);
                if ($this.data('date') && $this.data('date') != '0000-00-00') {
                    $this.data('fdatepicker').selectDate($this.data('date'));
                    $this.removeData('date').removeAttr('date');
                }
                if ($this.data('dateFormat')) {
                    $this.data('fdatepicker').update('dateFormat', $this.data('dateFormat'));
                    $this.removeData('dateFormat').removeAttr('dateFormat');
                }
            });
        }

        if ($('.eme_formfield_ftime' + dynamicSelector).length) {
            $('.eme_formfield_ftime' + dynamicSelector).fdatepicker({
                timepicker: true,
                onlyTimepicker: true,
                clearButton: true,
                closeButton: true,
                minutesStep: parseInt(emebasic.translate_minutesStep),
                language: emebasic.translate_flanguage,
                altFieldDateFormat: 'H:i:00',
                timeFormat: emebasic.translate_ftimeformat
            }).each(function () {
                const $this = $(this);
                if ($this.data('date') && $this.data('date') != '00:00:00') {
                    $this.data('fdatepicker').selectDate($this.data('date'));
                    $this.removeData('date').removeAttr('date');
                }
                if ($this.data('timeFormat')) {
                    $this.data('fdatepicker').update('timeFormat', $this.data('timeFormat'));
                    $this.removeData('timeFormat').removeAttr('timeFormat');
                }
            });
        }

        if ($('.eme_formfield_timepicker' + dynamicSelector).length) {
            $('.eme_formfield_timepicker' + dynamicSelector).timepicker({
                timeFormat: emebasic.translate_ftimeformat
            }).each(function () {
                const $this = $(this);
                if ($this.data('timeFormat')) {
                    $this.timepicker('option', { 'timeFormat': $this.data('timeFormat') });
                    $this.removeData('timeFormat').removeAttr('timeFormat');
                }
            });
        }

        if ($('.eme_select2' + dynamicSelector).length) {
            $('.eme_select2' + dynamicSelector).select2({
                dropdownAutoWidth: true,
                width: 'style',
                templateSelection: function (data) {
                    if (!data.id) return data.text;
                    const $option = $(data.element), $optgroup = $option.closest('optgroup');
                    if ($optgroup.length) return $optgroup.attr('label') + ' > ' + data.text;
                    return data.text;
                }
            });
        }

        if ($('.eme_select2_width50_class' + dynamicSelector).length) {
            $('.eme_select2_width50_class' + dynamicSelector).select2({ dropdownAutoWidth: true, width: '50%' });
        }

        if ($('.eme_select2_country_class' + dynamicSelector).length) {
            $('.eme_select2_country_class' + dynamicSelector).select2({
                width: '100%',
                ajax: {
                    url: emebasic.translate_ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 500,
                    data: params => ({
                        q: params.term,
                        page: params.page || 1,
                        pagesize: 30,
                        action: 'eme_select_country',
                        eme_frontend_nonce: emebasic.translate_frontendnonce
                    }),
                    processResults: (data, params) => ({
                        results: data.Records,
                        pagination: { more: (params.page * 30) < data.TotalRecordCount }
                    }),
                    cache: true
                },
                allowClear: true,
                placeholder: emebasic.translate_selectcountry
            }).on('change', function () {
                let statefield = $(this).closest("form").find('[name=state_code]');
                if (statefield.length) statefield.val(null).trigger('change');
            });
        }

        if ($('.eme_select2_state_class' + dynamicSelector).length) {
            $('.eme_select2_state_class' + dynamicSelector).select2({
                width: '100%',
                ajax: {
                    url: emebasic.translate_ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 500,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                            pagesize: 30,
                            country_code: $(this).closest("form").find('[name=country_code]').val(),
                            action: 'eme_select_state',
                            eme_frontend_nonce: emebasic.translate_frontendnonce
                        };
                    },
                    processResults: (data, params) => ({
                        results: data.Records,
                        pagination: { more: (params.page * 30) < data.TotalRecordCount }
                    }),
                    cache: true
                },
                allowClear: true,
                placeholder: emebasic.translate_selectstate
            });
        }

        if ($('.eme_select2_filter' + dynamicSelector).length) {
            $('.eme_select2_filter' + dynamicSelector).select2();
        }

        if ($('.eme_select2_fitcontent' + dynamicSelector).length) {
            $('.eme_select2_fitcontent' + dynamicSelector).select2({ dropdownAutoWidth: true, width: 'fit-content' });
        }
    }

    // Calendar navigation
    function loadCalendar(
        tableDiv, fullcalendar = 0, htmltable, htmldiv, showlong_events = 0,
        month = 0, year = 0, cat_chosen = '', author_chosen = '', contact_person_chosen = '',
        location_chosen = '', not_cat_chosen = '', template_chosen = 0, holiday_chosen = 0,
        weekdays = '', language = ''
    ) {
        $.post(emebasic.translate_ajax_url, {
            'eme_frontend_nonce': emebasic.translate_frontendnonce,
            'action': 'eme_calendar',
            'calmonth': parseInt(month, 10),
            'calyear': parseInt(year, 10),
            'full': fullcalendar,
            'long_events': showlong_events,
            'htmltable': htmltable,
            'htmldiv': htmldiv,
            'category': cat_chosen,
            'notcategory': not_cat_chosen,
            'author': author_chosen,
            'contact_person': contact_person_chosen,
            'location_id': location_chosen,
            'template_id': template_chosen,
            'holiday_id': holiday_chosen,
            'weekdays': weekdays,
            'lang': language
        }, function (data) {
            $(`#${tableDiv}`).replaceWith(data);
            // Re-attach event handlers after DOM replacement
            $('a.eme-cal-prev-month, a.eme-cal-next-month').on('click', function (e) {
                e.preventDefault();
                $(this).html('<img src="' + emebasic.translate_plugin_url + 'images/spinner.gif">');
                loadCalendar(
                    $(this).data('calendar_divid'),
                    $(this).data('full'),
                    $(this).data('htmltable'),
                    $(this).data('htmldiv'),
                    $(this).data('long_events'),
                    $(this).data('month'),
                    $(this).data('year'),
                    $(this).data('category'),
                    $(this).data('author'),
                    $(this).data('contact_person'),
                    $(this).data('location_id'),
                    $(this).data('notcategory'),
                    $(this).data('template_id'),
                    $(this).data('holiday_id'),
                    $(this).data('weekdays'),
                    $(this).data('language')
                );
            });
        });
    }
    $('a.eme-cal-prev-month, a.eme-cal-next-month').on('click', function (e) {
        e.preventDefault();
        $(this).html('<img src="' + emebasic.translate_plugin_url + 'images/spinner.gif">');
        loadCalendar(
            $(this).data('calendar_divid'),
            $(this).data('full'),
            $(this).data('htmltable'),
            $(this).data('htmldiv'),
            $(this).data('long_events'),
            $(this).data('month'),
            $(this).data('year'),
            $(this).data('category'),
            $(this).data('author'),
            $(this).data('contact_person'),
            $(this).data('location_id'),
            $(this).data('notcategory'),
            $(this).data('template_id'),
            $(this).data('holiday_id'),
            $(this).data('weekdays'),
            $(this).data('language')
        );
    });

    // Clearable input with 'x'
    $(document).on('input', '.clearable', function () {
        $(this)[eme_tog(this.value)]('x');
    }).on('mousemove', '.x', function (e) {
        $(this)[eme_tog(this.offsetWidth - 18 < e.clientX - this.getBoundingClientRect().left)]('onX');
    }).on('touchstart click', '.onX', function (ev) {
        ev.preventDefault();
        $(this).removeClass('x onX').val('').change();
    });

    // Lastname clearable
    if ($("input[name=lastname]").length && $("input[name=lastname]").data('clearable')) {
        $('input[name=lastname]').on("change", eme_lastname_clearable);
        eme_lastname_clearable();
    }

    // --- Generic forms ---
    const genericForms = [
        { sel: '[name=eme-cancel-payment-form]', action: 'eme_cancel_payment', ok: 'div#eme-cancel-payment-message-ok-', err: 'div#eme-cancel-payment-message-error-', loading: '#loading_gif' },
        { sel: '[name=eme-cancel-bookings-form]', action: 'eme_cancel_bookings', ok: 'div#eme-cancel-bookings-message-ok-', err: 'div#eme-cancel-bookings-message-error-', loading: '#loading_gif' },
        { sel: '[name=eme-subscribe-form]', action: 'eme_subscribe', ok: 'div#eme-subscribe-message-ok-', err: 'div#eme-subscribe-message-error-', loading: '#loading_gif' },
        { sel: '[name=eme-unsubscribe-form]', action: 'eme_unsubscribe', ok: 'div#eme-unsubscribe-message-ok-', err: 'div#eme-unsubscribe-message-error-', loading: '#loading_gif' },
        { sel: '[name=eme-rpi-form]', action: 'eme_rpi', ok: 'div#eme-rpi-message-ok-', err: 'div#eme-rpi-message-error-', loading: '#loading_gif' },
        { sel: '[name=eme-gdpr-approve-form]', action: 'eme_gdpr_approve', ok: 'div#eme-gdpr-approve-message-ok-', err: 'div#eme-gdpr-approve-message-error-', loading: '#loading_gif' },
        { sel: '[name=eme-cpi-request-form]', action: 'eme_cpi_request', ok: 'div#eme-cpi-request-message-ok-', err: 'div#eme-cpi-request-message-error-', loading: '#loading_gif' },
        { sel: '[name=eme-cpi-form]', action: 'eme_cpi', ok: 'div#eme-cpi-message-ok-', err: 'div#eme-cpi-message-error-', loading: '#loading_gif' },
        { sel: '[name=eme-tasks-form]', action: 'eme_tasks', ok: 'div#eme-tasks-message-ok-', err: 'div#eme-tasks-message-error-', loading: '#loading_gif' },
        { sel: '[name=eme-fs-form]', action: 'eme_frontend_submit', ok: 'div#eme-fs-message-ok-', err: 'div#eme-fs-message-error-', loading: '#loading_gif', isFS: true }
    ];
    genericForms.forEach(({ sel, action, ok, err, loading, isFS }) => {
        $(sel).on('submit', function (event) {
            event.preventDefault();
            const form_id = $(this).attr('id');
            if (isFS && emebasic.translate_htmleditor === "tinemce" && emebasic.translate_fs_wysiwyg === "true") {
                if (typeof tinymce !== "undefined") {
                    tinymce.get('event_notes')?.save();
                    tinymce.get('location_description')?.save();
                }
            }
            eme_ajax_form(form_id, action, ok + form_id, err + form_id, loading);
        });
    });

    // --- Booking/Member form handlers with confirmation ---
    function eme_handle_massmail(form_id, callback) {
        let $form = $('#' + form_id);
        if ($form.find('#massmail').length && $form.find('#massmail').val() != 1 && $form.find('#MassMailDialog').length) {
            let dialog = $('#MassMailDialog')[0];
            dialog.showModal();
            $('#dialog-confirm').on('click', function (e) {
                e.preventDefault();
                dialog.close();
                callback(form_id);
            });
            $('#dialog-cancel').on('click', function (e) {
                e.preventDefault();
                dialog.close();
            });
        } else {
            callback(form_id);
        }
    }

    $('[name=eme-rsvp-form]').on('submit', function (event) {
        event.preventDefault();
        eme_handle_massmail($(this).attr('id'), function (form_id) {
            let extra = {};
            ['eme_invite', 'eme_email', 'eme_ln', 'eme_fn'].forEach(k => { if ($_GET[k]) extra[k] = $_GET[k]; });
            eme_ajax_form(form_id, 'eme_add_bookings', 'div#eme-rsvp-addmessage-ok-' + form_id, 'div#eme-rsvp-addmessage-error-' + form_id, '#rsvp_add_loading_gif', extra);
            eme_dynamic_data_json(form_id, true);
        });
    });

    $('[name=eme-member-form]').on('submit', function (event) {
        event.preventDefault();
        eme_handle_massmail($(this).attr('id'), function (form_id) {
            eme_ajax_form(form_id, 'eme_add_member', 'div#eme-member-addmessage-ok-' + form_id, 'div#eme-member-addmessage-error-' + form_id, '#member_loading_gif');
            eme_dynamic_data_json(form_id, false);
        });
    });

    // --- Validation for submit buttons ---
    $('.eme_submit_button').on('click', function (event) {
        let valid = true, parent_form_id = $(this.form).attr('id');
        function scrollToInvalid($el) { $(document).scrollTop($el.offset().top - $(window).height() / 2); }
        $('input:text[required], .eme_formfield_fdatetime[required], .eme_formfield_fdate[required]').each(function () {
            if ($(this).is(":visible") && $(this).closest("form").attr('id') == parent_form_id) {
                let val = $(this).val();
                if (val.match(/^\s*$/)) {
                    $(this).addClass('eme_required');
                    scrollToInvalid($(this));
                    valid = false;
                } else {
                    $(this).removeClass('eme_required');
                }
            }
        });
        $('.eme-checkbox-group-required').each(function () {
            if ($(this).is(":visible") && $(this).closest("form").attr('id') == parent_form_id) {
                let checked = $(this).children("input:checkbox:checked").length;
                if (!checked) {
                    $(this).addClass('eme_required');
                    scrollToInvalid($(this));
                    valid = false;
                } else {
                    $(this).removeClass('eme_required');
                }
            }
        });
        $('select[required].select2-hidden-accessible').each(function() {
            console.log("checking step1");
            if ($(this).is(":visible") && $(this).closest("form").attr('id') == parent_form_id) {
                const $select = $(this);
                const $select2Container = $select.next('.select2-container');
                const isMultiSelect = $select.prop('multiple');
                let isEmpty = false;

                // Check if field is empty
                if (isMultiSelect) {
                    isEmpty = $select.val() === null || $select.val().length === 0;
                } else {
                    isEmpty = !$select.val();
                }

                if (isEmpty) {
                    // Add error class to the visible Select2 element
                    if (isMultiSelect) {
                        $select2Container.find('.select2-selection--multiple').addClass('eme_required');
                    } else {
                        $select2Container.find('.select2-selection--single').addClass('eme_required');
                    }


                    scrollToInvalid($select2Container);

                    // Focus the Select2 dropdown
                    $select2Container.find('.select2-selection').focus();

                    valid = false;
                } else {
                    // Remove error class
                    if (isMultiSelect) {
                        $select2Container.find('.select2-selection--multiple').removeClass('eme_required');
                    } else {
                        $select2Container.find('.select2-selection--single').removeClass('eme_required');
                    }
                }

            }
        });

        if (!valid) return false;
    });

    // --- Dynamic fields AJAX debounce handlers ---
    function eme_attach_dynamic_handlers(selector, isBooking) {
        $(selector).each(function () {
            let form_id = $(this).attr('id');
            let debounced_data = eme_debounce(() => eme_dynamic_data_json(form_id, isBooking), 500);
            let debounced_price = eme_debounce(() => eme_dynamic_price_json(form_id, isBooking), 500);
            let debounced_family = !isBooking ? eme_debounce(() => eme_dynamic_familymemberdata_json(form_id), 500) : null;
            $(this).on('input', function (event) {
                if (debounced_family && $(event.target).attr('id') === 'familycount') debounced_family();
                if ($(event.target).hasClass('nodynamicupdates')) {
                    if ($(event.target).hasClass('dynamicprice')) {
                        $(this).find(':submit').hide();
                        debounced_price();
                    }
                    return;
                }
                $(this).find(':submit').hide();
                debounced_data();
            });
            if (debounced_family) debounced_family();
            debounced_data();
        });
    }
    eme_attach_dynamic_handlers('[name=eme-rsvp-form]', true);
    eme_attach_dynamic_handlers('#eme-rsvp-adminform', true);
    eme_attach_dynamic_handlers('[name=eme-member-form]', false);
    eme_attach_dynamic_handlers('#eme-member-adminform', false);

    // Person image upload widget
    if ($('#eme_person_image_button').length) {
        $('#eme_person_remove_old_image').on("click", function () {
            $('#eme_person_image_id').val('');
            $('#eme_person_image_example').attr('src', '');
            $('#eme_person_current_image').hide();
            $('#eme_person_no_image').show();
            $('#eme_person_remove_old_image').hide();
            $('#eme_person_image_button').prop("value", emebasic.translate_chooseimg);
        });
        $('#eme_person_image_button').on("click", function (e) {
            e.preventDefault();
            let custom_uploader = wp.media({
                title: emebasic.translate_selectimg,
                button: { text: emebasic.translate_setimg },
                library: { type: 'image' },
                multiple: false
            }).on('select', function () {
                let attachment = custom_uploader.state().get('selection').first().toJSON();
                $('#eme_person_image_id').val(attachment.id);
                $('#eme_person_image_example').attr('src', attachment.url);
                $('#eme_person_current_image').show();
                $('#eme_person_no_image').hide();
                $('#eme_person_remove_old_image').show();
                $('#eme_person_image_button').prop("value", emebasic.translate_replaceimg);
            }).open();
        });
        if (parseInt($('#eme_person_image_id').val()) > 0) {
            $('#eme_person_no_image').hide();
            $('#eme_person_current_image').show();
            $('#eme_person_remove_old_image').show();
            $('#eme_person_image_button').prop("value", emebasic.translate_replaceimg);
        } else {
            $('#eme_person_no_image').show();
            $('#eme_person_current_image').hide();
            $('#eme_person_remove_old_image').hide();
            $('#eme_person_image_button').prop("value", emebasic.translate_chooseimg);
        }
    }
    if ($('#eme-payment-form').length) {
        $(document).scrollTop($('div#eme-payment-form').offset().top - $(window).height() / 2 + $('div#eme-payment-form').height() / 2);
    }

    eme_init_widgets();
});
