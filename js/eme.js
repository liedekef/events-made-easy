// Events Made Easy – Plain JavaScript Version
// All AJAX "action" params and logic match the original PHP backend.

if (typeof window.EME === 'undefined') {
    window.EME = {
        $: (selector, context = document) => context.querySelector(selector),
        $$: (selector, context = document) => Array.from(context.querySelectorAll(selector))
    };
}

function eme_debounce(func, wait = 300) {
    let timeout;
    return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

function eme_htmlDecode(inputStr) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = inputStr;
    return textarea.value;
}

function eme_getValue(element) {
    if (!element) return null;
    if (!element.multiple) return element.value;
    return Array.from(element.selectedOptions).map(option => option.value);
}

function eme_postJSON(url, data, callback) {
    fetch(url, {
        method: 'POST',
        body: data,
        credentials: 'same-origin'
    })
        .then(r => r.json())
        .then(callback)
        .catch(err => console.error('AJAX Error:', err));
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

function eme_toggle(el, show) {
    if (el) el.classList.toggle('eme-hidden', !show);
    //if (el) el.style.display = show ? '' : 'none';
}

function setTomSelectChWidth(tomselect, extraChars = 0) {
    const control = tomselect.control;
    const placeholder = tomselect.settings.placeholder || 'Select...';
    let text = placeholder;

    // If something is selected, use that instead
    if (tomselect.items.length > 0) {
        const item = tomselect.options[tomselect.items[0]];
        if (item && item.text) {
            text = item.text;
        }
    }

    // Add extra chars for dropdown arrow, padding, etc.
    const chWidth = Math.max(text.length + extraChars, 8); // at least 8ch

    // Apply via CSS variable or direct style
    control.style.minWidth = `${chWidth}ch`;
}

function initTomSelect(selector, options = {}) {
    // Convert selector to elements array
    const elements = typeof selector === 'string'
        ? EME.$$(selector)
        : [selector];

    if (!elements.length) return [];

    // Default configuration
    const defaults = {
        plugins: ['remove_button'],
        placeholder: null,
        hidePlaceholder: true,
    };

    // Merge options
    // const settings = Object.assign({}, defaults, options);
    // Destructure to separate plugin options
    const { plugins, extraPlugins = [], ...userOptions } = options;

    // Merge plugin arrays (with deduplication)
    const mergedPlugins = [
        ...new Set([
            ...(plugins || defaults.plugins),       // User's plugins or defaults
            ...(Array.isArray(extraPlugins) ? extraPlugins : [])  // extraPlugins if provided
        ])
    ];

    // Final settings
    const settings = {
        ...defaults,
        ...userOptions,
        plugins: mergedPlugins,

    };

    return Array.from(elements).map(el => {
        if (el.tomselectInitialized || (el.tomselect && !el.tomselect.destroyed)) {
            return el.tomselect; // Return existing instance
        }
        if (!el.id) {
            el.id = 'tom-select-' + Math.random().toString(36).slice(2, 11);
        }

        const config = {
            plugins: settings.plugins,
            placeholder: settings.placeholder,
            render: {
                item: function(data, escape) {
                    if (!data.optgroup) {
                        return `<div>${escape(data.text)}</div>`;
                    }
                    const groupHeader = this.dropdown_content.querySelector(
                        `.optgroup[data-group="${data.optgroup}"] .optgroup-header`
                    );
                    const groupLabel = groupHeader?.textContent || '';

                    return groupLabel
                        ? `<div>${escape(groupLabel)} > ${escape(data.text)}</div>`
                        : `<div>${escape(data.text)}</div>`;
                }
            },
            onItemAdd: function() {
                if (!this.settings.mode || this.settings.mode === 'single') {
                    this.blur(); // Remove focus after selection
                }
            },
            // Parse standard data-attributes
            ...Object.entries(el.dataset).reduce((acc, [key, value]) => {
                //if (key.startsWith('ts')) {
                   // const optName = key.replace('ts', '').replace(/([A-Z])/, g => g[0].toLowerCase());
                    // Try parsing JSON for complex values
                  //  try { acc[optName] = JSON.parse(value); }
                   // catch { acc[optName] = value; }
                //}
                try { acc[key] = JSON.parse(value); }
                catch { acc[key] = value; }
                return acc;
            }, {})
        }
        const tomselect = new TomSelect(el, config);
        setTomSelectChWidth(tomselect);

        el.tomselectInitialized = true;
        el.tomselect = tomselect;
        return tomselect;
    });
}

function initTomSelectRemote(selector, options = {}) {
    // Convert selector to elements array
    const elements = typeof selector === 'string'
        ? EME.$$(selector)
        : [selector];

    if (!elements.length) return [];

    // Default configuration
    const defaults = {
        plugins: ['virtual_scroll', 'dropdown_input', 'remove_button'],
        valueField: 'id',
        labelField: 'text',
        searchField: ['text'],
        maxOptions: 500,
        preload: 'focus',
        placeholder: null,
        hidePlaceholder: true,
        pagesize: 10,
        action: 'default_select_action',
        url: emebasic.translate_ajax_url,
        ajaxParams: { }
    };

    // Merge options
    // const settings = Object.assign({}, defaults, options);
    // Destructure to separate plugin options
    const { plugins, extraPlugins = [], ...userOptions } = options;

    // Merge plugin arrays (with deduplication)
    const mergedPlugins = [
        ...new Set([
            ...(plugins || defaults.plugins),       // User's plugins or defaults
            ...(Array.isArray(extraPlugins) ? extraPlugins : [])  // extraPlugins if provided
        ])
    ];

    // Final settings
    const settings = {
        ...defaults,
        ...userOptions,
        plugins: mergedPlugins
    };

    return Array.from(elements).map(el => {
        if (el.tomselectInitialized || (el.tomselect && !el.tomselect.destroyed)) {
            return el.tomselect; // Return existing instance
        }
        if (!el.id) {
            el.id = 'tom-select-' + Math.random().toString(36).slice(2, 11);
        }

        const config = {
            plugins: settings.plugins,
            valueField: settings.valueField,
            labelField: settings.labelField,
            searchField: settings.searchField,
            maxOptions: settings.maxOptions,
            preload: settings.preload,
            placeholder: settings.placeholder,
            firstUrl: settings.firstUrl || function(query) {
                const params = new URLSearchParams({
                    q: query || '',
                    page: 1,
                    pagesize: settings.pagesize,
                    action: settings.action,
                    ...settings.ajaxParams
                });
                return settings.url + '?' + params.toString();
            },
            load: settings.load || function(query, callback) {
                const url = this.getUrl(query);
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        const urlParams = new URLSearchParams(url.split('?')[1] || '');
                        const cur_page = parseInt(urlParams.get('page')) || 1;

                        if (json.TotalRecordCount > cur_page * settings.pagesize) {
                            const nextParams = new URLSearchParams({
                                q: query,
                                page: cur_page + 1,
                                pagesize: settings.pagesize,
                                action: settings.action,
                                ...settings.ajaxParams
                            });
                            this.setNextUrl(query, url + '?' + nextParams.toString());
                        }
                        callback(json.Records || json);
                    })
                    .catch(() => callback());
            },
            onItemAdd: function() {
                if (!this.settings.mode || this.settings.mode === 'single') {
                    this.blur(); // Remove focus after selection
                }
            },
            // Copy all other settings except those already handled
            ...Object.entries(settings).reduce((acc, [key, value]) => {
                if (!['plugins', 'valueField', 'labelField', 'searchField', 'maxOptions', 'preload', 'placeholder', 'url', 'action', 'pagesize', 'ajaxParams', 'firstUrl', 'load'].includes(key)) {
                    acc[key] = value;
                }
                return acc;
            }, {}),
            // Parse standard data-attributes
            ...Object.entries(el.dataset).reduce((acc, [key, value]) => {
                //if (key.startsWith('ts')) {
                 //   const optName = key.replace('ts', '').replace(/([A-Z])/, g => g[0].toLowerCase());
                    // Try parsing JSON for complex values
                 //   try { acc[optName] = JSON.parse(value); }
                 //   catch { acc[optName] = value; }
                //}
                // Try parsing JSON for complex values
                try { acc[key] = JSON.parse(value); }
                catch { acc[key] = value; }
                return acc;
            }, {})
        };

        const tomselect = new TomSelect(el, config);
        setTomSelectChWidth(tomselect);

        el.tomselectInitialized = true;
        el.tomselect = tomselect;
        return tomselect;
    });
}

// Helper functions for class manipulation
function eme_addClass(el, className) {
    if (el.classList) {
        el.classList.add(className);
    } else {
        el.className += ' ' + className;
    }
}

function eme_removeClass(el, className) {
    if (el.classList) {
        el.classList.remove(className);
    } else {
        el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
    }
}

function eme_hasClass(el, className) {
    return el.classList ? el.classList.contains(className) : new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
}

function eme_lastname_clearable() {
    const ln = EME.$('input[name=lastname]');
    if (!ln) return;
    
    const fields = ['firstname', 'address1', 'address2', 'city', 'state', 'zip', 'country', 'email', 'phone'];
    if (ln.value == '') {
        ln.readOnly = false;
        eme_removeClass(ln, 'clearable');
        eme_removeClass(ln, 'x');
        fields.forEach(f => {
            const field = EME.$(`input[name=${f}]`);
            if (field) {
                field.value = '';
                field.readOnly = false;
            }
        });
        const wpIdField = EME.$('input[name=wp_id]');
        const personIdField = EME.$('input[name=person_id]');
        if (wpIdField) wpIdField.value = '';
        if (personIdField) personIdField.value = '';
    }
    if (ln.value != '') {
        eme_addClass(ln, 'clearable');
        eme_addClass(ln, 'x');
    }
}

// --- Widget Initialization ---
function eme_init_widgets(dynamicOnly = false) {
    const dynamicSelector = dynamicOnly ? '.dynamicfield' : '';

    // Initialize fdatepicker for datetime fields
    EME.$$('.eme_formfield_fdatetime' + dynamicSelector).forEach(el => {
        if (typeof FDatepicker !== 'undefined') {
            new FDatepicker(el, {
                todayButton: new Date(),
                clearButton: true,
                closeButton: true,
                fieldSizing: true,
                timepicker: true,
                minutesStep: parseInt(emebasic.translate_minutesStep),
                language: emebasic.translate_flanguage,
                firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                altFormat: 'Y-m-d H:i:00',
                multipleSeparator: ", ",
                format: emebasic.translate_fdatetimeformat,
            });
        }
    });

    // Initialize fdatepicker for date fields
    EME.$$('.eme_formfield_fdate' + dynamicSelector).forEach(el => {
        if (typeof FDatepicker !== 'undefined') {
            new FDatepicker(el, {
                todayButton: new Date(),
                clearButton: true,
                closeButton: true,
                autoClose: true,
                fieldSizing: true,
                language: emebasic.translate_flanguage,
                firstDay: parseInt(emebasic.translate_firstDayOfWeek),
                altFormat: 'Y-m-d',
                multipleSeparator: ", ",
                format: emebasic.translate_fdateformat
            });
        }
    });

    // Initialize fdatepicker for time fields
    EME.$$('.eme_formfield_ftime' + dynamicSelector).forEach(el => {
        if (typeof FDatepicker !== 'undefined') {
            new FDatepicker(el, {
                timepicker: true,
                timeOnly: true,
                clearButton: true,
                closeButton: true,
                minutesStep: parseInt(emebasic.translate_minutesStep),
                language: emebasic.translate_flanguage,
                altFormat: 'H:i:00',
                format: emebasic.translate_ftimeformat
            });
        }
    });

    // Basic select2 replacement
    initTomSelect('.eme_select2' + dynamicSelector);
    initTomSelect('.eme_select2_width50_class' + dynamicSelector);
    initTomSelectRemote('.eme_select2_country_class' + dynamicSelector, {
        valueField: 'id',
        labelField: 'text',
        searchField: ['text'],
        placeholder: emebasic.translate_selectcountry,
        action: 'eme_select_country',
        pagesize: 30,
        ajaxParams: {
            eme_frontend_nonce: emebasic.translate_frontendnonce
        },
        onItemAdd: function(value, $item) {
            // Clear corresponding state field when country changes
            const form = this.input.closest('form');
            const stateField = form?.querySelector('.eme_select2_state_class');
            if (stateField && stateField.tomselect) {
                stateField.tomselect.clear();
                stateField.tomselect.clearOptions();
                stateField.tomselect.load();
            }
        },
        onItemRemove: function(value) {
            const form = this.input.closest('form');
            const stateField = form?.querySelector('.eme_select2_state_class');
            if (stateField && stateField.tomselect) {
                stateField.tomselect.clear();
                stateField.tomselect.clearOptions();
                stateField.tomselect.load();
            }
        }
    });

    initTomSelectRemote('.eme_select2_state_class' + dynamicSelector, {
        valueField: 'id',
        labelField: 'text',
        searchField: ['text'],
        placeholder: emebasic.translate_selectstate,
        action: 'eme_select_state',
        pagesize: 30,
        firstUrl: function(query) {
            const form = this.input.closest('form');
            const countryField = form?.querySelector('[name=country_code]');
            const countryCode = countryField?.value || '';
            const params = new URLSearchParams({
                q: query || '',
                page: 1,
                pagesize: 30,
                action: 'eme_select_state',
                eme_frontend_nonce: emebasic.translate_frontendnonce,
                country_code: countryCode
            });
            return emebasic.translate_ajax_url + '?' + params.toString();
        }
    });

    initTomSelect('.eme_select2_filter' + dynamicSelector);
    initTomSelect('.eme_select2_fitcontent' + dynamicSelector);

}

// --- Unified AJAX Handler for Booking/Member/Generic forms ---
function eme_ajax_form(form_id, action, okSel, errSel, loadingSel, extraParams = {}) {
    const form = document.getElementById(form_id);
    if (!form) return;
    const loadingEl = form.querySelector(loadingSel);
    const okEl = EME.$(okSel);
    const errEl = EME.$(errSel);

    // Hide submit buttons
    form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, false));

    // Show loading
    if (loadingSel) {
        if (loadingEl) eme_toggle(loadingEl, true);
    }

    let alldata = new FormData(form);
    alldata.append('action', action);
    Object.entries(extraParams).forEach(([k, v]) => alldata.append(k, v));

    fetch(emebasic.translate_ajax_url, {
        method: 'POST',
        body: alldata
    })
        .then(response => response.json())
        .then(data => {
            // Hide loading
            if (loadingSel) {
                const loadingEl = form.querySelector(loadingSel);
                if (loadingEl) eme_toggle(loadingEl, false);
            }

            // Show submit buttons
            form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, true));

            if (data.Result === "OK" || data.Result === "REDIRECT_IMM") {
                if (okSel) {
                    if (okEl) {
                        okEl.innerHTML = data.htmlmessage;
                        eme_toggle(okEl, true);
                    }
                }
                if (errSel) {
                    if (errEl) eme_toggle(errEl, false);
                }
                if (data.keep_form == 1) {
                    form.reset();
                    eme_refresh_captcha(form_id);
                } else {
                    const parentDiv = form.closest('[id^=div_eme-]');
                    if (parentDiv) eme_toggle(parentDiv, false);
                }
                if (data.paymentform) {
                    const paymentDiv = EME.$(`#div_eme-payment-form-${form_id}`);
                    if (paymentDiv) {
                        paymentDiv.innerHTML = data.paymentform;
                        eme_toggle(paymentDiv, true);
                    }
                }
                if (data.paymentredirect) {
                    setTimeout(() => window.location.href = data.paymentredirect, parseInt(data.waitperiod));
                }
                eme_scrollToEl(okEl);
            } else {
                if (errSel) {
                    if (errEl) {
                        errEl.innerHTML = data.htmlmessage;
                        eme_toggle(errEl, true);
                    }
                }
                if (okSel) {
                    if (okEl) eme_toggle(okEl, false);
                }
                eme_scrollToEl(errEl);
            }
        })
        .catch(error => {
            if (errSel) {
                if (errEl) {
                    errEl.innerHTML = emebasic.translate_error + (error?.message ? '<br>' + error.message : '');
                    eme_toggle(errEl, true);
                }
            }
            if (okSel) {
                if (okEl) eme_toggle(okEl, false);
            }
            if (loadingSel) {
                const loadingEl = form.querySelector(loadingSel);
                if (loadingEl) eme_toggle(loadingEl, false);
            }
            form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, true));
            eme_scrollToEl(errEl);
        });
}

function eme_refresh_captcha(form_id) {
    const captcha = EME.$(`#${form_id} .eme-captcha-img`);
    if (captcha) {
        let src = captcha.src.replace(/&ts=.*/, '');
        captcha.src = src + '&ts=' + Date.now();
    }
}

function eme_scrollToEl(sel) {
    if (sel) {
        const offsetTop = sel.getBoundingClientRect().top + window.pageYOffset;
        window.scrollTo({
            top: offsetTop - window.innerHeight / 2 + sel.offsetHeight / 2,
            behavior: 'smooth'
        });
    }
}

// --- Unified Dynamic Data/Price AJAX ---
function eme_dynamic_price_json(form_id, isBooking = true) {
    const form = document.getElementById(form_id);
    if (!form) return;

    form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, false));
    let alldata = new FormData(form);

    const priceSpans = isBooking
        ? [{ sel: 'span#eme_calc_bookingprice', action: 'eme_calc_bookingprice' }, { sel: 'span#eme_calc_bookingprice_detail', action: 'eme_calc_bookingprice_detail' }]
        : [{ sel: 'span#eme_calc_memberprice', action: 'eme_calc_memberprice' }, { sel: 'span#eme_calc_memberprice_detail', action: 'eme_calc_memberprice_detail' }];

    let found = false;
    priceSpans.forEach(({ sel, action }) => {
        const span = form.querySelector(sel);
        if (span) {
            found = true;
            span.innerHTML = '<img src="' + emebasic.translate_plugin_url + 'images/spinner.gif">';
            alldata.set('action', action);
            alldata.set('eme_frontend_nonce', emebasic.translate_frontendnonce);

            fetch(emebasic.translate_ajax_url, {
                method: 'POST',
                body: alldata
            })
                .then(response => response.json())
                .then(data => {
                    form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, true));
                    span.innerHTML = data.total;
                })
                .catch(() => {
                    form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, true));
                    span.innerHTML = 'Invalid reply';
                });
        }
    });

    if (!found) {
        form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, true));
    }
}

function eme_dynamic_data_json(form_id, isBooking = true) {
    const form = document.getElementById(form_id);
    if (!form) return;

    form.querySelectorAll('input[type="submit"], button[type="submit"]').forEach(btn => eme_toggle(btn, false));
    let alldata = new FormData(form);

    const dataDivSel = 'div#eme_dyndata';
    const action = isBooking ? 'eme_dyndata_rsvp' : 'eme_dyndata_member';
    const dataDiv = form.querySelector(dataDivSel);

    if (dataDiv) {
        dataDiv.innerHTML = '<img src="' + emebasic.translate_plugin_url + 'images/spinner.gif">';
        alldata.set('action', action);
        alldata.set('eme_frontend_nonce', emebasic.translate_frontendnonce);

        fetch(emebasic.translate_ajax_url, {
            method: 'POST',
            body: alldata
        })
            .then(response => response.json())
            .then(data => {
                form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, true));
                dataDiv.innerHTML = data.Result;
                eme_init_widgets(true);
                eme_dynamic_price_json(form_id, isBooking);
            })
            .catch(() => {
                form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, true));
            });
    } else {
        eme_dynamic_price_json(form_id, isBooking);
    }
}

function eme_dynamic_familymemberdata_json(form_id) {
    const form = document.getElementById(form_id);
    if (!form) return;

    form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, false));
    let alldata = new FormData(form);
    const dataDiv = form.querySelector('div#eme_dyndata_family');

    if (dataDiv) {
        dataDiv.innerHTML = '<img src="' + emebasic.translate_plugin_url + 'images/spinner.gif">';
        alldata.set('action', 'eme_dyndata_familymember');
        alldata.set('eme_frontend_nonce', emebasic.translate_frontendnonce);

        fetch(emebasic.translate_ajax_url, {
            method: 'POST',
            body: alldata
        })
            .then(response => response.json())
            .then(data => {
                form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, true));
                dataDiv.innerHTML = data.Result;
                eme_init_widgets(true);
            })
            .catch(() => {
                form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, true));
            });
    } else {
        form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, true));
    }
}

// Calendar navigation
function loadCalendar(
    tableDiv, fullcalendar = 0, htmltable, htmldiv, showlong_events = 0,
    month = 0, year = 0, cat_chosen = '', author_chosen = '', contact_person_chosen = '',
    location_chosen = '', not_cat_chosen = '', template_chosen = 0, holiday_chosen = 0,
    weekdays = '', language = ''
) {
    const formData = new FormData();
    formData.append('eme_frontend_nonce', emebasic.translate_frontendnonce);
    formData.append('action', 'eme_calendar');
    formData.append('calmonth', parseInt(month, 10));
    formData.append('calyear', parseInt(year, 10));
    formData.append('full', fullcalendar);
    formData.append('long_events', showlong_events);
    formData.append('htmltable', htmltable);
    formData.append('htmldiv', htmldiv);
    formData.append('category', cat_chosen);
    formData.append('notcategory', not_cat_chosen);
    formData.append('author', author_chosen);
    formData.append('contact_person', contact_person_chosen);
    formData.append('location_id', location_chosen);
    formData.append('template_id', template_chosen);
    formData.append('holiday_id', holiday_chosen);
    formData.append('weekdays', weekdays);
    formData.append('lang', language);

    fetch(emebasic.translate_ajax_url, {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(data => {
            const tableEl = document.getElementById(tableDiv);
            if (tableEl) {
                tableEl.outerHTML = data;
                // Re-attach event handlers after DOM replacement
                attachCalendarHandlers();
            }
        });
}

function attachCalendarHandlers() {
    EME.$$('a.eme-cal-prev-month, a.eme-cal-next-month').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            this.innerHTML = '<img src="' + emebasic.translate_plugin_url + 'images/spinner.gif">';
            loadCalendar(
                this.dataset.calendar_divid,
                this.dataset.full,
                this.dataset.htmltable,
                this.dataset.htmldiv,
                this.dataset.long_events,
                this.dataset.month,
                this.dataset.year,
                this.dataset.category,
                this.dataset.author,
                this.dataset.contact_person,
                this.dataset.location_id,
                this.dataset.notcategory,
                this.dataset.template_id,
                this.dataset.holiday_id,
                this.dataset.weekdays,
                this.dataset.language
            );
        });
    });
}

// --- Booking/Member form handlers with confirmation ---
function eme_handle_massmail(form_id, callback) {
    const form = document.getElementById(form_id);
    const massmailField = form.querySelector('#massmail');
    const massmailDialog = EME.$('#MassMailDialog');

    if (massmailField && massmailField.value != 1 && massmailDialog) {
        massmailDialog.showModal();

        const confirmBtn = EME.$('#dialog-confirm');
        const cancelBtn = EME.$('#dialog-cancel');

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function(e) {
                e.preventDefault();
                massmailDialog.close();
                callback(form_id);
            }, { once: true });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                massmailDialog.close();
            }, { once: true });
        }
    } else {
        callback(form_id);
    }
}

// --- Dynamic fields AJAX debounce handlers ---
function eme_attach_dynamic_handlers(selector, isBooking) {
    EME.$$(selector).forEach(form => {
        const form_id = form.id;
        const debounced_data = eme_debounce(() => eme_dynamic_data_json(form_id, isBooking), 500);
        const debounced_price = eme_debounce(() => eme_dynamic_price_json(form_id, isBooking), 500);
        const debounced_family = !isBooking ? eme_debounce(() => eme_dynamic_familymemberdata_json(form_id), 500) : null;

        form.addEventListener('input', function(event) {
            if (debounced_family && event.target.id === 'familycount') {
                debounced_family();
            }
            if (eme_hasClass(event.target, 'nodynamicupdates')) {
                if (eme_hasClass(event.target, 'dynamicprice')) {
                    form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, false));
                    debounced_price();
                }
                return;
            }
            form.querySelectorAll('[type="submit"]').forEach(btn => eme_toggle(btn, false));
            debounced_data();
        });

        if (debounced_family) debounced_family();
        debounced_data();
    });
}
    
function eme_scrollToInvalidInput(el) { 
    // First check if field is in a tab
    const tabPane = el.closest('.eme-tab-content');
    if (tabPane) {
        const tabId = tabPane.id;
        const isVisible = tabPane.classList.contains('active') ||
            window.getComputedStyle(tabPane).display !== 'none';

        if (!isVisible && typeof eme_activateTab === 'function') {
            // Activate the tab containing the invalid field
            eme_activateTab(tabId);
            // Small delay to ensure tab is visible before scrolling
            setTimeout(() => {
                if (typeof el.reportValidity === 'function') {
                    el.reportValidity();
                } else {
                    eme_addClass(el, 'eme_required');
                    eme_scrollToEl(el);
                }
            }, 300);
            return; // Exit, scrolling happens after timeout
        }
    }

    // If not in tab or tab is already active, scroll normally
    if (typeof el.reportValidity === 'function') {
        el.reportValidity();
    } else {
        eme_addClass(el, 'eme_required');
        eme_scrollToEl(el);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Show elements that should be visible with JS
    EME.$$('.eme-showifjs').forEach(el => {
        eme_toggle(el, true);
    });

    
    // Initial calendar handler attachment
    attachCalendarHandlers();

    // Clearable input with 'x'
    document.addEventListener('input', function(e) {
        if (eme_hasClass(e.target, 'clearable')) {
            if (e.target.value) {
                eme_addClass(e.target, 'x');
            } else {
                eme_removeClass(e.target, 'x');
            }
        }
    });

    document.addEventListener('mousemove', function(e) {
        if (eme_hasClass(e.target, 'x')) {
            const rect = e.target.getBoundingClientRect();
            if (e.target.offsetWidth - 18 < e.clientX - rect.left) {
                eme_addClass(e.target, 'onX');
            } else {
                eme_removeClass(e.target, 'onX');
            }
        }
    });

    document.addEventListener('click', function(e) {
        if (eme_hasClass(e.target, 'onX')) {
            e.preventDefault();
            eme_removeClass(e.target, 'x');
            eme_removeClass(e.target, 'onX');
            e.target.value = '';
            e.target.dispatchEvent(new Event('change'));
        }
    });

    document.addEventListener('touchstart', function(e) {
        if (eme_hasClass(e.target, 'onX')) {
            e.preventDefault();
            eme_removeClass(e.target, 'x');
            eme_removeClass(e.target, 'onX');
            e.target.value = '';
            e.target.dispatchEvent(new Event('change'));
        }
    });

    // Lastname clearable
    const lastnameField = EME.$("input[name=lastname]");
    if (lastnameField && lastnameField.dataset.clearable) {
        lastnameField.addEventListener('change', eme_lastname_clearable);
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
        EME.$$(sel).forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                const form_id = this.id;
                if (isFS && emebasic.translate_htmleditor === "tinemce" && emebasic.translate_fs_wysiwyg === "true") {
                    if (typeof tinymce !== "undefined") {
                        const eventNotesEditor = tinymce.get('event_notes');
                        const locationDescEditor = tinymce.get('location_description');
                        if (eventNotesEditor) eventNotesEditor.save();
                        if (locationDescEditor) locationDescEditor.save();
                    }
                }
                eme_ajax_form(form_id, action, ok + form_id, err + form_id, loading);
            });
        });
    });

    EME.$$('[name=eme-rsvp-form]').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            eme_handle_massmail(this.id, function(form_id) {
                let extra = {};
                ['eme_invite', 'eme_email', 'eme_ln', 'eme_fn'].forEach(k => { 
                    if ($_GET[k]) extra[k] = $_GET[k]; 
                });
                eme_ajax_form(form_id, 'eme_add_bookings', 'div#eme-rsvp-addmessage-ok-' + form_id, 'div#eme-rsvp-addmessage-error-' + form_id, '#rsvp_add_loading_gif', extra);
                eme_dynamic_data_json(form_id, true);
            });
        });
    });

    EME.$$('[name=eme-member-form]').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            eme_handle_massmail(this.id, function(form_id) {
                eme_ajax_form(form_id, 'eme_add_member', 'div#eme-member-addmessage-ok-' + form_id, 'div#eme-member-addmessage-error-' + form_id, '#member_loading_gif');
                eme_dynamic_data_json(form_id, false);
            });
        });
    });

    // --- Validation for submit buttons ---
    EME.$$('.eme_submit_button').forEach(btn => {
        btn.addEventListener('click', function(event) {
            let valid = true;
            const parent_form_id = this.form.id;
            
            // Check required text inputs and date fields
            EME.$$('input:text[required], .eme_formfield_fdatetime[required], .eme_formfield_fdate[required]').forEach(input => {
                if (input.offsetParent !== null && input.closest("form").id === parent_form_id) {
                    const val = input.value;
                    if (val.match(/^\s*$/)) {
                        eme_addClass(input, 'eme_required');
                        eme_scrollToInvalidInput(input);
                        valid = false;
                    } else {
                        eme_removeClass(input, 'eme_required');
                    }
                }
            });
            
            // Check required checkbox groups
            EME.$$('.eme-checkbox-group-required').forEach(group => {
                if (group.offsetParent !== null && group.closest("form").id === parent_form_id) {
                    const checked = group.querySelectorAll("input:checkbox:checked").length;
                    if (!checked) {
                        eme_addClass(group, 'eme_required');
                        eme_scrollToInvalidInput(group);
                        valid = false;
                    } else {
                        eme_removeClass(group, 'eme_required');
                    }
                }
            });
            
            if (!valid) return false;
        });
    });

    eme_attach_dynamic_handlers('[name=eme-rsvp-form]', true);
    eme_attach_dynamic_handlers('#eme-rsvp-adminform', true);
    eme_attach_dynamic_handlers('[name=eme-member-form]', false);
    eme_attach_dynamic_handlers('#eme-member-adminform', false);

    // Person image upload widget
    const personImageButton = document.getElementById('eme_person_image_button');
    if (personImageButton) {
        const removeButton = document.getElementById('eme_person_remove_old_image');
        const imageIdField = document.getElementById('eme_person_image_id');
        const imageExample = document.getElementById('eme_person_image_example');
        const currentImageDiv = document.getElementById('eme_person_current_image');
        const noImageDiv = document.getElementById('eme_person_no_image');
        
        if (removeButton) {
            removeButton.addEventListener('click', function() {
                imageIdField.value = '';
                imageExample.src = '';
                eme_toggle(currentImageDiv, false);
                eme_toggle(noImageDiv, true);
                eme_toggle(removeButton, false);
                personImageButton.value = emebasic.translate_chooseimg;
            });
        }
        
        personImageButton.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof wp !== 'undefined' && wp.media) {
                const custom_uploader = wp.media({
                    title: emebasic.translate_selectimg,
                    button: { text: emebasic.translate_setimg },
                    library: { type: 'image' },
                    multiple: false
                }).on('select', function() {
                    const attachment = custom_uploader.state().get('selection').first().toJSON();
                    imageIdField.value = attachment.id;
                    imageExample.src = attachment.url;
                    eme_toggle(currentImageDiv, true);
                    eme_toggle(noImageDiv, false);
                    eme_toggle(removeButton, true);
                    personImageButton.value = emebasic.translate_replaceimg;
                }).open();
            }
        });
        
        // Initialize display state
        if (parseInt(imageIdField.value) > 0) {
            eme_toggle(noImageDiv, false);
            eme_toggle(currentImageDiv, true);
            eme_toggle(removeButton, true);
            personImageButton.value = emebasic.translate_replaceimg;
        } else {
            eme_toggle(noImageDiv, true);
            eme_toggle(currentImageDiv, false);
            eme_toggle(removeButton, false);
            personImageButton.value = emebasic.translate_chooseimg;
        }
    }
    
    // Scroll to payment form if present
    const paymentForm = document.getElementById('eme-payment-form');
    if (paymentForm) {
        const offsetTop = paymentForm.getBoundingClientRect().top + window.pageYOffset;
        window.scrollTo({
            top: offsetTop - window.innerHeight / 2 + paymentForm.offsetHeight / 2,
            behavior: 'smooth'
        });
    }

    // Initialize widgets
    eme_init_widgets();
});
