// Core EME namespace with utilities
const EME = (function($) {
    // Shared utilities
    const utils = {
        debounce: function(func, wait = 300) {
            let timeout;
            return function(...args) { // Use a regular function (not arrow) to preserve `this`
                const context = this; // Capture `this` from the caller
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        },

        htmlDecode: function(value) {
            return $('<div/>').html(value).text();
        },

        getQueryParams: function() {
            const qs = document.location.search.split('+').join(' ');
            let params = {}, tokens, re = /[?&]?([^=]+)=([^&]*)/g;
            while (tokens = re.exec(qs)) {
                params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
            }
            return params;
        },

        toggleClass: function(v) {
            return v ? 'addClass' : 'removeClass';
        },

        // Standard AJAX settings
        ajaxSettings: function(data) {
            return {
                url: emebasic.translate_ajax_url,
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                type: 'POST',
                dataType: 'json'
            };
        },

        // Handle form responses consistently
        handleFormResponse: function(options) {
            const { 
                formId, 
                formName, 
                data, 
                keepForm = false,
                paymentHandling = false
            } = options;
            
	    const $form = $('#' + formId);
            const $submit = $form.find(':submit');

            const $successMsg = $(`div#eme-${formName}-message-ok-${formId}`);
            const $errorMsg = $(`div#eme-${formName}-message-error-${formId}`);

            if (data.Result === 'OK' || data.Result === 'REDIRECT_IMM') {
                $successMsg.html(data.htmlmessage).show();
                $errorMsg.hide();

                if (data.Result === 'REDIRECT_IMM') return;

                if (keepForm || data.keep_form == 1) {
                    $form.trigger('reset');
                    this.refreshCaptcha(formId);
                    $submit.show();
                } else {
                    $(`div#div_eme-${formName}-form-${formId}`).hide();
                }

                if (paymentHandling) {
                    this.handlePaymentResponse(formId, data);
                }
            } else {
                $errorMsg.html(data.htmlmessage).show();
                $successMsg.hide();
                $submit.show();
            }

            this.scrollToMessage($successMsg.is(':visible') ? $successMsg : $errorMsg);
        },

        // Handle payment-related responses
        handlePaymentResponse: function(formId, data) {
            const $paymentForm = $(`div#div_eme-payment-form-${formId}`);
            if (typeof data.paymentform !== 'undefined') {
                $paymentForm.html(data.paymentform).show();
            }
            
            if (typeof data.paymentredirect !== 'undefined') {
                setTimeout(() => {
                    window.location.href = data.paymentredirect;
                }, parseInt(data.waitperiod));
            }
        },

        // Refresh captcha image
        refreshCaptcha: function(formId) {
            const $captcha = $(`#${formId}`).find('#eme_captcha_img');
            if ($captcha.length) {
                const src = $captcha.attr('src').replace(/&ts=.*/, '');
                $captcha.attr('src', `${src}&ts=${new Date().getTime()}`);
            }
        },

        // Scroll to message center screen
        scrollToMessage: function($element) {
            if ($element.length) {
                const offset = $element.offset().top - ($(window).height() / 2) + ($element.height() / 2);
                $(document).scrollTop(offset);
            }
        },

        // Initialize dynamic form fields
        initDynamicFields: function() {
            this.initSelect2Fields();
            this.initDatePickers();
            this.initTimePickers();
        },

        // Initialize select2 fields
        initSelect2Fields: function() {
            $('.eme_select2_width50_class.dynamicfield').select2({ width: '50%' });
        },

        // Initialize date pickers
        initDatePickers: function() {
            this.initDatePicker('.eme_formfield_fdate.dynamicfield', {
                todayButton: new Date(),
                clearButton: true,
                language: emebasic.translate_flanguage,
                firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                altFieldDateFormat: 'Y-m-d',
                dateFormat: emebasic.translate_fdateformat
            });

            this.initDatePicker('.eme_formfield_fdatetime.dynamicfield', {
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
        },

        // Helper for date picker initialization
        initDatePicker: function(selector, options) {
            const $elements = $(selector);
            if ($elements.length) {
                $elements.fdatepicker(options).each(function() {
                    const $this = $(this);
                    const date = $this.data('date');
                    
                    if (date && date !== '0000-00-00' && date !== '0000-00-00 00:00:00') {
                        $this.fdatepicker().data('fdatepicker').selectDate(date);
                        $this.removeData('date').removeAttr('date');
                    }
                    
                    ['dateFormat', 'timeFormat'].forEach(attr => {
                        if ($this.data(attr)) {
                            $this.fdatepicker().data('fdatepicker').update(attr, $this.data(attr));
                            $this.removeData(attr).removeAttr(attr);
                        }
                    });
                });
            }
        },

        // Initialize time pickers
        initTimePickers: function() {
            $('.eme_formfield_timepicker.dynamicfield').timepicker({
                timeFormat: emebasic.translate_ftimeformat
            }).each(function() {
                const $this = $(this);
                if ($this.data('timeFormat')) {
                    $this.timepicker('option', { 'timeFormat': $this.data('timeFormat') });
                    $this.removeData('timeFormat').removeAttr('timeFormat');
                }
            });
        },

        // Lastname clearable functionality
        lastnameClearable: function() {
            const $lastname = $('input[name=lastname]');
            if (!$lastname.length || !$lastname.data('clearable')) return;

            $lastname.on("change", EME.utils.handleLastnameChange);
            EME.utils.handleLastnameChange();
        },

        handleLastnameChange: function() {
            const $lastname = $('input[name=lastname]');
            if ($lastname.val() === '') {
                $lastname.attr('readonly', false).removeClass('clearable');
                ['firstname', 'address1', 'address2', 'city', 'state', 'zip', 'country', 'email', 'phone'].forEach(field => {
                    $(`input[name=${field}]`).val('').attr('readonly', false);
                });
                $('input[name=wp_id], input[name=person_id]').val('');
                $('input[name=wp_id]').trigger('input');
            }
            if ($lastname.val() !== '') {
                $lastname.addClass('clearable x');
            }
        },

    };

    // Form handlers
    const forms = {
        // Generic form handler
        handleGenericForm: function(formId, formName, ajaxAction) {
            const $form = $('#' + formId);
            const $submit = $form.find(':submit');
            const $loadingGif = $form.find('#loading_gif');
            $loadingGif.show();
            $submit.hide();

            const formData = new FormData($form[0]);
            formData.append('action', ajaxAction);

            $.ajax(utils.ajaxSettings(formData))
                .done(data => {
                    $loadingGif.hide();
                    utils.handleFormResponse({
                        formId,
                        formName,
                        data,
                        keepForm: data.keep_form == 1
                    });
                })
                .fail((xhr, textStatus, error) => {
                    $loadingGif.hide();
                    $submit.show();
                    $(`div#eme-${formName}-message-error-${formId}`)
                        .html(emebasic.translate_error)
                        .show();
                    $(`div#eme-${formName}-message-ok-${formId}`).hide();
                    utils.scrollToMessage($(`div#eme-${formName}-message-error-${formId}`));
                });
        },

        // Member form handler
        handleMemberForm: function(formId) {
            const $form = $('#' + formId);
            const $submit = $form.find(':submit');
            const $memberLoadingGif = $form.find('#member_loading_gif');

            $memberLoadingGif.show();
            $submit.hide();

            const formData = new FormData($form[0]);
            formData.append('action', 'eme_add_member');

            $.ajax(utils.ajaxSettings(formData))
                .done(data => {
                    $memberLoadingGif.hide();
                    utils.handleFormResponse({
                        formId,
                        formName: 'member-add',
                        data,
                        paymentHandling: true
                    });
                })
                .fail((xhr, textStatus, error) => {
                    $memberLoadingGif.hide();
                    $submit.show();
                    const $errorMsg = $(`div#eme-member-addmessage-error-${formId}`);
                    $errorMsg.html(`${emebasic.translate_error}<br>${xhr.responseText} : ${error}`).show();
                    $(`div#eme-member-addmessage-ok-${formId}`).hide();
                    utils.scrollToMessage($errorMsg);
                });
        },

        // Booking form handler
        handleBookingForm: function(formId) {
            const $form = $('#' + formId);
            const $submit = $form.find(':submit');
            const $rsvpLoadingGif = $form.find('#rsvp_add_loading_gif');

            $rsvpLoadingGif.show();
            $submit.hide();

            const formData = new FormData($form[0]);
            formData.append('action', 'eme_add_bookings');

            // Add invite params if present
            ['eme_invite', 'eme_email', 'eme_ln', 'eme_fn'].forEach(param => {
                if (typeof $_GET[param] !== 'undefined') {
                    formData.append(param, $_GET[param]);
                }
            });

            $.ajax(utils.ajaxSettings(formData))
                .done(data => {
                    if (data.Result === 'OK') {
                        $rsvpLoadingGif.hide();
                        utils.handleFormResponse({
                            formId,
                            formName: 'rsvp-add',
                            data,
                            keepForm: data.keep_form == 1,
                            paymentHandling: true
                        });

                        if (data.keep_form == 1) {
                            $form.trigger('reset');
                            this.updateDynamicBookingData(formId);
                            utils.refreshCaptcha(formId);
                        }
                    } else {
                        $submit.show();
                        $rsvpLoadingGif.hide();
                        const $errorMsg = $(`div#eme-rsvp-addmessage-error-${formId}`);
                        $errorMsg.html(data.htmlmessage).show();
                        $(`div#eme-rsvp-addmessage-ok-${formId}`).hide();
                        utils.scrollToMessage($errorMsg);
                    }
                })
                .fail((xhr, textStatus, error) => {
                    $submit.show();
                    $rsvpLoadingGif.hide();
                    const $errorMsg = $(`div#eme-rsvp-addmessage-error-${formId}`);
                    $errorMsg.html(emebasic.translate_error).show();
                    $(`div#eme-rsvp-addmessage-ok-${formId}`).hide();
                    utils.scrollToMessage($errorMsg);
                });
        },

        // Dynamic data handlers
        updateDynamicData: function(formId, elementId, action) {
            const $form = $('#' + formId);
            const $submit = $form.find(':submit');

            const $dyndata = $form.find(`div#${elementId}`);
            if (!$dyndata.length) return;

            $submit.hide();
            $dyndata.html(`<img src="${emebasic.translate_plugin_url}images/spinner.gif">`);

            const formData = new FormData($form[0]);
            formData.append('action', action);
            formData.append('eme_frontend_nonce', emebasic.translate_frontendnonce);

            $.ajax(utils.ajaxSettings(formData))
                .done(data => {
                    $submit.show();
                    $dyndata.html(data.Result);
                    utils.initDynamicFields();
                    this.updateDynamicPrices(formId);
                })
                .fail(() => {
                    $submit.show();
                });
        },

        updateDynamicPrices: function(formId) {
            const $form = $('#' + formId);
            const $submit = $form.find(':submit');
            $submit.hide();

            const formData = new FormData($form[0]);
            formData.append('eme_frontend_nonce', emebasic.translate_frontendnonce);

            const priceSpans = [
                { id: 'eme_calc_bookingprice', action: 'eme_calc_bookingprice' },
                { id: 'eme_calc_bookingprice_detail', action: 'eme_calc_bookingprice_detail' },
                { id: 'eme_calc_memberprice', action: 'eme_calc_memberprice' },
                { id: 'eme_calc_memberprice_detail', action: 'eme_calc_memberprice_detail' }
            ];

            const foundSpan = priceSpans.find(span => {
                const $span = $form.find(`span#${span.id}`);
                if ($span.length) {
                    $span.html(`<img src="${emebasic.translate_plugin_url}images/spinner.gif">`);
                    formData.append('action', span.action);

                    $.ajax(utils.ajaxSettings(formData))
                        .done(data => {
                            $submit.show();
                            $span.html(data.total);
                        })
                        .fail(() => {
                            $submit.show();
                            $span.html('Invalid reply');
                        });

                    return true;
                }
                return false;
            });

            if (!foundSpan) {
                $submit.show();
            }
        }
    };

    // Calendar handlers
    const calendar = {
        load: function(tableDiv, fullcalendar, htmltable, htmldiv, showlong_events, month, year, cat_chosen, author_chosen, contact_person_chosen, location_chosen, not_cat_chosen, template_chosen, holiday_chosen, weekdays, language) {
            // Default parameter handling
            fullcalendar = fullcalendar || 0;
            showlong_events = showlong_events || 0;
            month = month || 0;
            year = year || 0;
            cat_chosen = cat_chosen || '';
            not_cat_chosen = not_cat_chosen || '';
            author_chosen = author_chosen || '';
            contact_person_chosen = contact_person_chosen || '';
            location_chosen = location_chosen || '';
            template_chosen = template_chosen || 0;
            holiday_chosen = holiday_chosen || 0;
            weekdays = weekdays || '';
            language = language || '';

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
            }, data => {
                $(`#${tableDiv}`).replaceWith(data);
		// replaceWith removes all event handlers, so we need to re-add them
                this.bindCalendarNavigation();
            });
        },

        bindCalendarNavigation: function() {
            $('a.eme-cal-prev-month, a.eme-cal-next-month').on('click', function(e) {
                e.preventDefault();
                const $this = $(this);
                $this.html(`<img src="${emebasic.translate_plugin_url}images/spinner.gif">`);
                calendar.load(
                    $this.data('calendar_divid'),
                    $this.data('full'),
                    $this.data('htmltable'),
                    $this.data('htmldiv'),
                    $this.data('long_events'),
                    $this.data('month'),
                    $this.data('year'),
                    $this.data('category'),
                    $this.data('author'),
                    $this.data('contact_person'),
                    $this.data('location_id'),
                    $this.data('notcategory'),
                    $this.data('template_id'),
                    $this.data('holiday_id'),
                    $this.data('weekdays'),
                    $this.data('language')
                );
            });
        }
    };

    // Initialize all components
    const init = {
        // Form validation
        setupFormValidation: function() {
            $('.eme_submit_button').on('click', function(event) {
                const $form = $(this).closest('form');
                const formId = $form.attr('id');
                let valid = true;

                // Validate required text fields
                $form.find('input:text[required]:visible').each(function() {
                    if ($(this).val().match(/^\s+$/)) {
                        $(this).addClass('eme_required');
                        utils.scrollToMessage($(this));
                        valid = false;
                    } else {
                        $(this).removeClass('eme_required');
                    }
                });

                // Validate date/time fields
                $form.find('.eme_formfield_fdatetime[required]:visible, .eme_formfield_fdate[required]:visible').each(function() {
                    if ($(this).val().match(/^\s*$/)) {
                        $(this).addClass('eme_required');
                        utils.scrollToMessage($(this));
                        valid = false;
                    } else {
                        $(this).removeClass('eme_required');
                    }
                });

                // Validate checkbox groups
                $form.find('.eme-checkbox-group-required:visible').each(function() {
                    const checkedCount = $(this).find('input:checkbox:checked').length;
                    if (checkedCount === 0) {
                        $(this).addClass('eme_required');
                        utils.scrollToMessage($(this));
                        valid = false;
                    } else {
                        $(this).removeClass('eme_required');
                    }
                });

                return valid;
            });
        },

        // Form submissions
        setupFormSubmissions: function() {
            // Generic form handler for all form types
            const handleFormSubmission = function(event, formType, handler) {
                event.preventDefault();
                const formId = $(this).attr('id');
                
                if ($(this).find('#massmail').length && 
                    $(this).find('#massmail').val() != 1 && 
                    $(this).find('#MassMailDialog').length) {
                    
                    const dialog = $('#MassMailDialog')[0];
                    dialog.showModal();

                    $('#dialog-confirm').on('click', function(e) {
                        e.preventDefault();
                        dialog.close();
                        handler(formId);
                    });

                    $('#dialog-cancel').on('click', function(e) {
                        e.preventDefault();
                        dialog.close();
                    });
                } else {
                    handler(formId);
                }
            };

            // Bind all form types
            $('[name=eme-rsvp-form]').on('submit', function(e) {
                handleFormSubmission(e, 'rsvp', forms.handleBookingForm);
            });

            $('[name=eme-member-form]').on('submit', function(e) {
                handleFormSubmission(e, 'member', forms.handleMemberForm);
            });

            // Generic forms
            const genericForms = [
                'cancel-payment', 'cancel-bookings', 'subscribe', 
                'unsubscribe', 'rpi', 'gdpr-approve', 'cpi-request', 
                'cpi', 'tasks', 'fs'
            ];

            genericForms.forEach(formType => {
                $(`[name=eme-${formType}-form]`).on('submit', function(e) {
                    handleFormSubmission(e, formType, (formId) => {
                        forms.handleGenericForm(formId, formType.replace('-', ''), `eme_${formType.replace('-', '_')}`);
                    });
                });
            });

            // Special handling for FS form with TinyMCE
            $('[name=eme-fs-form]').on('submit', function(e) {
                if (emebasic.translate_htmleditor == "tinemce" && emebasic.translate_fs_wysiwyg == "true") {
                    tinymce.get('event_notes')?.save();
                    tinymce.get('location_description')?.save();
                }
                handleFormSubmission(e, 'fs', (formId) => {
                    forms.handleGenericForm(formId, 'fs', 'eme_frontend_submit');
                });
            });
        },

        // Dynamic form updates
        setupDynamicFormUpdates: function() {
            const delay = 500; // .5 seconds delay after last input
            let timer;

            // RSVP forms
            $('[name=eme-rsvp-form], #eme-rsvp-adminform').on('input', utils.debounce(function(event) {
                const formId = $(this).attr('id');
                const $target = $(event.target);

                if ($target.hasClass('nodynamicupdates')) {
                    if ($target.hasClass('dynamicprice')) {
                        forms.updateDynamicPrices(formId);
                    }
                    return;
                }

                forms.updateDynamicData(formId, 'eme_dyndata', 'eme_dyndata_rsvp');
            }, delay));

            // Member forms
            $('[name=eme-member-form], #eme-member-adminform').on('input', utils.debounce(function(event) {
                const formId = $(this).attr('id');
                const $target = $(event.target);

                if ($target.attr('id') == 'familycount') {
                    forms.updateDynamicData(formId, 'eme_dyndata_family', 'eme_dyndata_familymember');
                }

                if ($target.hasClass('nodynamicupdates')) {
                    if ($target.hasClass('dynamicprice')) {
                        forms.updateDynamicPrices(formId);
                    }
                    return;
                }

                forms.updateDynamicData(formId, 'eme_dyndata', 'eme_dyndata_member');
            }, delay));
        },

        // Date/time pickers
        setupDateTimePickers: function() {
            // DateTime pickers
            $('.eme_formfield_fdatetime').fdatepicker({
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
            }).each(function() {
                const $this = $(this);
                const date = $this.data('date');
                
                if (date && date !== '0000-00-00 00:00:00') {
                    $this.fdatepicker().data('fdatepicker').selectDate(date);
                    $this.removeData('date').removeAttr('date');
                }
                
                ['dateFormat', 'timeFormat'].forEach(attr => {
                    if ($this.data(attr)) {
                        $this.fdatepicker().data('fdatepicker').update(attr, $this.data(attr));
                        $this.removeData(attr).removeAttr(attr);
                    }
                });
            });

            // Date pickers
            $('.eme_formfield_fdate').fdatepicker({
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
            }).each(function() {
                const $this = $(this);
                const date = $this.data('date');
                
                if (date && date !== '0000-00-00') {
                    $this.fdatepicker().data('fdatepicker').selectDate(date);
                    $this.removeData('date').removeAttr('date');
                }
                
                if ($this.data('dateFormat')) {
                    $this.fdatepicker().data('fdatepicker').update('dateFormat', $this.data('dateFormat'));
                    $this.removeData('dateFormat').removeAttr('dateFormat');
                }
            });

            // Time pickers
            $('.eme_formfield_ftime').fdatepicker({
                timepicker: true,
                onlyTimepicker: true,
                clearButton: true,
                closeButton: true,
                minutesStep: parseInt(emebasic.translate_minutesStep),
                language: emebasic.translate_flanguage,
                altFieldDateFormat: 'H:i:00',
                timeFormat: emebasic.translate_ftimeformat
            }).each(function() {
                const $this = $(this);
                const date = $this.data('date');
                
                if (date && date !== '00:00:00') {
                    $this.fdatepicker().data('fdatepicker').selectDate(date);
                    $this.removeData('date').removeAttr('date');
                }
                
                if ($this.data('timeFormat')) {
                    $this.fdatepicker().data('fdatepicker').update('timeFormat', $this.data('timeFormat'));
                    $this.removeData('timeFormat').removeAttr('timeFormat');
                }
            });

            // jQuery UI Timepicker
            $('.eme_formfield_timepicker').timepicker({
                timeFormat: emebasic.translate_ftimeformat
            }).each(function() {
                const $this = $(this);
                if ($this.data('timeFormat')) {
                    $this.timepicker('option', { 'timeFormat': $this.data('timeFormat') });
                    $this.removeData('timeFormat').removeAttr('timeFormat');
                }
            });
        },

        // Select2 controls
        setupSelect2Controls: function() {
            // Basic Select2
            $('.eme_select2_width50_class').select2({ width: '50%' });
            $('.eme_select2_fitcontent').select2({ dropdownAutoWidth: true, width: 'fit-content' });
            $('.eme_select2_filter').select2();

            // Country Select2
            $('.eme_select2_country_class').select2({
                width: '100%',
                ajax: {
                    url: emebasic.translate_ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 500,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                            pagesize: 30,
                            action: 'eme_select_country',
                            eme_frontend_nonce: emebasic.translate_frontendnonce
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.Records,
                            pagination: {
                                more: (data.Page * 30) < data.TotalRecordCount
                            }
                        };
                    },
                    cache: true
                },
                allowClear: true,
                placeholder: emebasic.translate_selectcountry
            }).on('change', function() {
                $(this).closest("form").find('[name=state_code]').val(null).trigger('change');
            });

            // State Select2
            $('.eme_select2_state_class').select2({
                width: '100%',
                ajax: {
                    url: emebasic.translate_ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 500,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                            pagesize: 30,
                            country_code: $(this).closest("form").find('[name=country_code]').val(),
                            action: 'eme_select_state',
                            eme_frontend_nonce: emebasic.translate_frontendnonce
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.Records,
                            pagination: {
                                more: (data.Page * 30) < data.TotalRecordCount
                            }
                        };
                    },
                    cache: true
                },
                allowClear: true,
                placeholder: emebasic.translate_selectstate
            });
        },

        // Media uploader for person image
        setupMediaUploader: function() {
            const $imageButton = $('#eme_person_image_button');
            if (!$imageButton.length) return;

            $('#eme_person_remove_old_image').on("click", function() {
                $('#eme_person_image_id').val('');
                $('#eme_person_image_example').attr('src', '');
                $('#eme_person_current_image').hide();
                $('#eme_person_no_image').show();
                $('#eme_person_remove_old_image').hide();
                $imageButton.val(emebasic.translate_chooseimg);
            });

            $imageButton.on("click", function(e) {
                e.preventDefault();
                const custom_uploader = wp.media({
                    title: emebasic.translate_selectimg,
                    button: { text: emebasic.translate_setimg },
                    library: { type: 'image' },
                    multiple: false
                }).on('select', function() {
                    const attachment = custom_uploader.state().get('selection').first().toJSON();
                    $('#eme_person_image_id').val(attachment.id);
                    $('#eme_person_image_example').attr('src', attachment.url);
                    $('#eme_person_current_image').show();
                    $('#eme_person_no_image').hide();
                    $('#eme_person_remove_old_image').show();
                    $imageButton.val(emebasic.translate_replaceimg);
                }).open();
            });

            if (parseInt($('#eme_person_image_id').val()) > 0) {
                $('#eme_person_no_image').hide();
                $('#eme_person_current_image').show();
                $('#eme_person_remove_old_image').show();
                $imageButton.val(emebasic.translate_replaceimg);
            } else {
                $('#eme_person_no_image').show();
                $('#eme_person_current_image').hide();
                $('#eme_person_remove_old_image').hide();
                $imageButton.val(emebasic.translate_chooseimg);
            }
        },

        // Clearable inputs
        setupClearableInputs: function() {
            $(document)
                .on('input', '.clearable', function() {
                    $(this)[utils.toggleClass(this.value)]('x');
                })
                .on('mousemove', '.x', function(e) {
                    $(this)[utils.toggleClass(this.offsetWidth - 18 < e.clientX - this.getBoundingClientRect().left)]('onX');
                })
                .on('touchstart click', '.onX', function(ev) {
                    ev.preventDefault();
                    $(this).removeClass('x onX').val('').change();
                });
        },

        // Initialize everything
        initialize: function() {
            // Show elements that should be visible when JS is running
            $('.eme-showifjs').show();

            // Initialize all components
            EME.utils.lastnameClearable();
            this.setupFormValidation();
            this.setupFormSubmissions();
            this.setupDynamicFormUpdates();
            this.setupDateTimePickers();
            this.setupSelect2Controls();
            this.setupMediaUploader();
            this.setupClearableInputs();

            // Initialize calendar navigation
	    calendar.bindCalendarNavigation();

            // Center payment form if present
            if ($('#eme-payment-form').length) {
                utils.scrollToMessage($('div#eme-payment-form'));
            }

            // Initialize dynamic forms
            $('[name=eme-rsvp-form], #eme-rsvp-adminform').each(function() {
                const formId = $(this).attr('id');
                forms.updateDynamicData(formId, 'eme_dyndata', 'eme_dyndata_rsvp');
            });

            $('[name=eme-member-form], #eme-member-adminform').each(function() {
                const formId = $(this).attr('id');
                forms.updateDynamicData(formId, 'eme_dyndata_family', 'eme_dyndata_familymember');
                forms.updateDynamicData(formId, 'eme_dyndata', 'eme_dyndata_member');
            });
        }
    };

    // Public API
    return {
        utils,
        forms,
        calendar,
        init
    };
})(jQuery);

// Global variables
const $_GET = EME.utils.getQueryParams();

// Initialize when DOM is ready
jQuery(document).ready(function() {
    EME.init.initialize();
});
