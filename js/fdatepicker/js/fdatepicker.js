const FDATEPICKER_DEFAULT_MESSAGES = {
    days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
    months: ['January','February','March','April','May','June', 'July','August','September','October','November','December'],
    monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    today: 'Today',
    clear: 'Clear',
    close: 'Close',
    format: 'm/d/Y h:i a',
    firstDayOfWeek: 1,
    noDatesSelected: 'No dates selected',
    singleDateSelected: '1 date selected',          // For exactly 1
    multipleDatesSelected: '{count} dates selected', // For 2, 3, etc.
    datesSelected: 'Selected dates ({0}):'
}

class FDatepicker {
    // statics for event listeners
    static _documentListenersAdded = false;
    static _openInstances = new Set();

    static setMessages(customMessages) {
        Object.assign(FDATEPICKER_DEFAULT_MESSAGES, customMessages);
    }

    static _handleGlobalClick(e) {
        const target = e.target;

        // Close all open datepickers that don't contain the click target
        for (const instance of FDatepicker._openInstances) {
            if (instance.isOpen &&
                target !== instance.input &&
                !instance.popup.contains(target)) {
                instance.close();
            }
        }
    }

    static _handleGlobalKeydown(e) {
        const key = e.key;
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '].includes(key)) {
            return;
        }

        for (const instance of FDatepicker._openInstances) {
            if (instance.isOpen &&
                (instance.grid === e.target || instance.popup.contains(e.target))) {
                e.preventDefault();
                e.stopPropagation();
                break; // Only one popup can be active
            }
        }
    }

    static _addGlobalListeners() {
        if (!FDatepicker._documentListenersAdded) {
            document.addEventListener('click', FDatepicker._handleGlobalClick);
            document.addEventListener('keydown', FDatepicker._handleGlobalKeydown);
            FDatepicker._documentListenersAdded = true;
        }
    }

    static _removeGlobalListeners() {
        if (FDatepicker._documentListenersAdded) {
            document.removeEventListener('click', FDatepicker._handleGlobalClick);
            document.removeEventListener('keydown', FDatepicker._handleGlobalKeydown);
            FDatepicker._documentListenersAdded = false;
        }
    }

    static _cleanupOpenInstances() {
        // Remove any instances that have been destroyed
        const validInstances = new Set();
        for (const instance of FDatepicker._openInstances) {
            // Check if instance is still valid (not destroyed)
            if (instance.input && instance.input._fdatepicker === instance) {
                validInstances.add(instance);
            }
        }
        FDatepicker._openInstances = validInstances;

        // Remove global listeners if no open instances remain
        if (FDatepicker._openInstances.size === 0) {
            FDatepicker._removeGlobalListeners();
        }
    }

    constructor(input, options = {}) {
        this.input = typeof input === 'string' ?
            document.querySelector(input) : input;

        if (!this.input) {
            return;
        }
        // Prevent double initialization
        if (this.input._fdatepicker) {
            return this.input._fdatepicker;
        }

        // Store a reference to this instance on the input for cleanup and easy reference
        this.input._fdatepicker = this;

        this.container = this.getOrCreateGlobalContainer();

        this.focusedDate = new Date();
        this.selectedDate = null;
        this.selectedEndDate = null;
        this.selectedDates = [];
        this.isOpen = false;
        this.currentYear = new Date().getFullYear();
        this.focusedElement = null;
        this.locale = FDATEPICKER_DEFAULT_MESSAGES;

        // Read options from input's dataset
        this.options = {
            format: this.input.dataset.format || '',
            view: this.input.dataset.view || 'days',
            minDate: this.input.dataset.minDate ? new Date(this.input.dataset.minDate) : null,
            maxDate: this.input.dataset.maxDate ? new Date(this.input.dataset.maxDate) : null,
            disabledDates: this.input.dataset.disabledDates ? this.input.dataset.disabledDates.split(',').map(d => d.trim()) : [],
            disabledDays: this.input.dataset.disabledDays ? this.input.dataset.disabledDays.split(',').map(d => d.trim()) : [],
            weekendDays: this.input.dataset.weekendDays ? this.input.dataset.weekendDays.split(',').map(d => d.trim()) : [0,6],
            altField: this.input.dataset.altField || null,
            altFormat: this.input.dataset.altFormat || 'Y-m-d',
            range: this.input.dataset.range === 'true',
            multiple: this.input.dataset.multiple === 'true',
            multipleSeparator: this.input.dataset.multipleSeparator || ',',
            altFieldMultipleSeparator: this.input.dataset.altFieldMultipleSeparator || ',',
            multipleDisplaySelector: this.input.dataset.multipleDisplaySelector || '',
            autoClose: this.input.dataset.autoClose !== 'false', // default true
            firstDayOfWeek: this.input.dataset.firstDayOfWeek !== undefined ? parseInt(this.input.dataset.firstDayOfWeek) : null,
            todayButton: this.input.dataset.todayButton !== 'false',
            clearButton: this.input.dataset.clearButton !== 'false',
            closeButton: this.input.dataset.closeButton !== 'false',
            timepicker: this.input.dataset.timepicker === 'true', // default false, no timepicker
            timeOnly: this.input.dataset.timeOnly === 'true', // default false
            timepickerDefaultNow: this.input.dataset.timepickerDefaultNow !== 'false', // default true
            ampm: this.input.dataset.ampm === 'true',
            hoursStep: parseInt(this.input.dataset.hoursStep) || 1,
            minutesStep: parseInt(this.input.dataset.minutesStep) || 1,
            minHours: this.input.dataset.minHours !== undefined ? parseInt(this.input.dataset.minHours) : null,
            maxHours: this.input.dataset.maxHours !== undefined ? parseInt(this.input.dataset.maxHours) : null,
            minMinutes: this.input.dataset.minMinutes !== undefined ? parseInt(this.input.dataset.minMinutes) : 0,
            maxMinutes: this.input.dataset.maxMinutes !== undefined ? parseInt(this.input.dataset.maxMinutes) : 59,
            ...options
        };


        // some validation
        if (isNaN(this.options.minutesStep)) this.options.minutesStep=1;
        if (isNaN(this.options.hoursStep)) this.options.hoursStep=1;
        if (isNaN(this.options.minMinutes)) this.options.minMinutes=0;
        if (isNaN(this.options.maxMinutes)) this.options.maxMinutes=59;
        if (isNaN(this.options.minHours)) this.options.minHours = null;
        if (isNaN(this.options.maxHours)) this.options.maxHours = null;

        if (
            this.options.firstDayOfWeek === null ||
            this.options.firstDayOfWeek === undefined ||
            isNaN(this.options.firstDayOfWeek)
        ) {
            this.options.firstDayOfWeek = this.locale.firstDayOfWeek;
            if (
                this.options.firstDayOfWeek == null ||
                isNaN(this.options.firstDayOfWeek)
            ) {
                this.options.firstDayOfWeek = 1;
            }
        }

        if (!Array.isArray(this.options.weekendDays)) {
            this.options.weekendDays = [0, 6]; // Default: Sun and Sat
        }

        // get the default view to start with
        if (this.input.value || this.input.dataset.date) {
            this.options.view = 'days';
        }
        this.view = this.options.view === 'years' ? 'years' :
            this.options.view === 'months' ? 'months' : 'days';

        // Prevent autoClose if timeOnly is active
        // This ensures the popup stays open so the user can interact with the timepicker.
        if (this.options.timeOnly) {
            this.options.autoClose = false;
            this.options.todayButton = false;
            this.options.clearButton = false;
        }
        if (!this.input.dataset.format && !this.options.format) {
            this.options.format = this.locale.format || 'm/d/Y';
        }
        this.options.ampmuppercase = false;
        if (this.options.format.includes('a') || this.options.format.includes('A')) {
            this.options.ampm = true;
            if (this.options.format.includes('A')) {
                this.options.ampmuppercase = true;
            }
        }

        this.boundHandlers = {
            paste: (e) => e.preventDefault(),
            drop: (e) => e.preventDefault(),
            click: () => this.toggle(),
            input: (e) => {
                if (this.input.value && !this.selectedDate) {
                    this.updateInput();
                }
                e.preventDefault();
                e.stopPropagation();
            },
            keydown: (e) => {
                if (!this.isOpen && e.key !== 'Escape') {
                    if (['ArrowDown', ' ', 'Enter'].includes(e.key)) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.open();
                    }
                    return;
                }

                switch (e.key) {
                    case 'Escape':
                        e.preventDefault();
                        e.stopPropagation();
                        this.close();
                        this.input.focus();
                        break;

                    case 'Tab':
                        // Allow normal tab behavior within popup
                        if (!this.popup.contains(e.target)) {
                            this.close();
                        }
                        break;

                    case 'ArrowLeft':
                        this.keyboardNavigate(-1, 'horizontal');
                        break;

                    case 'ArrowRight':
                        this.keyboardNavigate(1, 'horizontal');
                        break;

                    case 'ArrowUp':
                        this.keyboardNavigate(-1, 'vertical');
                        break;

                    case 'ArrowDown':
                        this.keyboardNavigate(1, 'vertical');
                        break;

                    case 'PageUp':
                        if (this.view === 'days') {
                            if (e.shiftKey) {
                                this.focusedDate.setFullYear(this.focusedDate.getFullYear() - 1);
                            } else {
                                this.focusedDate.setMonth(this.focusedDate.getMonth() - 1);
                            }
                            this.render();
                            this.setInitialFocus();
                        }
                        break;

                    case 'PageDown':
                        if (this.view === 'days') {
                            if (e.shiftKey) {
                                this.focusedDate.setFullYear(this.focusedDate.getFullYear() + 1);
                            } else {
                                this.focusedDate.setMonth(this.focusedDate.getMonth() + 1);
                            }
                            this.render();
                            this.setInitialFocus();
                        }
                        break;

                    case 'Home':
                        if (this.view === 'days') {
                            this.focusedDate.setDate(1);
                            this.render();
                            this.setDayFocus();
                        }
                        break;

                    case 'End':
                        if (this.view === 'days') {
                            const lastDay = new Date(this.focusedDate.getFullYear(), this.focusedDate.getMonth() + 1, 0).getDate();
                            this.focusedDate.setDate(lastDay);
                            this.render();
                            this.setDayFocus();
                        }
                        break;

                    case 'Enter':
                    case ' ':
                        this.keyboardHandleSelection();
                        break;
                }
            }
        };

        this.init();
    }

    isDateDisabled(date) {
        if (!date || isNaN(date.getTime())) {
            return true; // Invalid dates are disabled
        }

        const dateString = this.formatDate(date, 'Y-m-d');
        const dayOfWeek = date.getDay();

        // Create normalized date for comparison (start of day)
        const normalizedDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const normalizedTime = normalizedDate.getTime();

        // Check min/max dates - create copies to avoid mutation
        if (this.options.minDate) {
            const minDateCopy = new Date(this.options.minDate);
            const minTime = new Date(minDateCopy.getFullYear(), minDateCopy.getMonth(), minDateCopy.getDate()).getTime();
            if (normalizedTime < minTime) return true;
        }

        if (this.options.maxDate) {
            const maxDateCopy = new Date(this.options.maxDate);
            const maxTime = new Date(maxDateCopy.getFullYear(), maxDateCopy.getMonth(), maxDateCopy.getDate()).getTime();
            if (normalizedTime > maxTime) return true;
        }

        // Check disabled dates array
        if (this.options.disabledDates && this.options.disabledDates.includes(dateString)) {
            return true;
        }

        if (this.options.disabledDays && this.options.disabledDays.includes(dayOfWeek)) {
            return true;
        }

        return false;
    }

    getOrCreateGlobalContainer() {
        let container = document.getElementById('fdatepicker-global-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'fdatepicker-global-container';
            container.className = 'fdatepicker-global-container';
            document.body.appendChild(container);
        }
        return container;
    }

    wrapInput() {
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        wrapper.style.display = 'inline-block';
        wrapper.style.width = '100%';

        // Wrap the input
        this.input.parentNode.insertBefore(wrapper, this.input);
        wrapper.appendChild(this.input);

        return wrapper;
    }

    init() {
        // Remove readonly so browser treats the field as properly interactive
        // This helps with HTML5 validation, focus, and accessibility
        this.input.removeAttribute('readonly');

        // Handle pre-filled dates
        this.initializePrefilledDates();

        this.bindInputEvents();
        this.updateInput();
    }

    initializePrefilledDates() {
        if (this.options.multiple && this.input.dataset.date) {
            // Multiple dates
            const dates = this.input.dataset.date.split(',');
            this.selectedDates = dates.map(dateStr => new Date(dateStr.trim())).filter(date => !isNaN(date));
            if (this.selectedDates.length > 0) {
                this.focusedDate = new Date(this.selectedDates[0]);
            }
        } else if (this.input.dataset.date) {
            // Single date
            const date = new Date(this.input.dataset.date);
            if (!isNaN(date)) {
                this.selectedDate = date;
                this.focusedDate = new Date(date);
            }
        }
    }

    getInitialTimeValues() {
        let hours = 12;
        let minutes = 0;
        let isAM = true;

        // If we have a selected date, use its time
        if (this.selectedDate) {
            hours = this.selectedDate.getHours();
            minutes = this.selectedDate.getMinutes();
            isAM = hours < 12;
        } else if (this.options.timepickerDefaultNow) {
            // Use current time if timepickerDefaultNow is true
            const now = new Date();
            hours = now.getHours();
            minutes = now.getMinutes();
            isAM = hours < 12;
        }

        // Convert to 12-hour format if needed
        let displayHours = hours;
        if (this.options.ampm) {
            displayHours = hours === 0 ? 12 : (hours > 12 ? hours - 12 : hours);
        }

        return { hours: displayHours, minutes, isAM };
    }

    createPopup() {
        const popup = document.createElement('div');
        popup.className = 'fdatepicker-popup';

        if (!this.options.timeOnly) {
            popup.innerHTML = `
        <div class="fdatepicker-header">
            <button class="fdatepicker-nav" data-action="prev" aria-label="Previous" tabindex="0">‹</button>
            <div class="fdatepicker-title" tabindex="0"></div>
            <button class="fdatepicker-nav" data-action="next" aria-label="Next" tabindex="0">›</button>
        </div>
        <div class="fdatepicker-content">
            <div class="fdatepicker-grid">
                <!-- Days headers will be added dynamically -->
            </div>
        </div>
        `;
        }

        // Add timepicker if needed
        if (this.options.timepicker) {
            const initialTime = this.getInitialTimeValues();
            const is24Hour = !this.options.ampm;

            // Calculate min/max values for hours
            let minHours, maxHours;
            if (this.options.minHours !== null && this.options.maxHours !== null) {
                minHours = this.options.minHours;
                maxHours = this.options.maxHours;
            } else if (is24Hour) {
                minHours = this.options.minHours !== null ? this.options.minHours : 0;
                maxHours = this.options.maxHours !== null ? this.options.maxHours : 23;
            } else {
                minHours = this.options.minHours !== null ? this.options.minHours : 1;
                maxHours = this.options.maxHours !== null ? this.options.maxHours : 12;
            }

            // Create the time inputs and AM/PM container
            const timeInputs = document.createElement('div');
            timeInputs.className = 'fdatepicker-time-inputs';

            // Add hours input
            const hoursInput = document.createElement('input');
            hoursInput.type = 'number';
            hoursInput.className = 'fdatepicker-time-input';
            hoursInput.dataset.time = 'hours';
            hoursInput.min = minHours;
            hoursInput.max = maxHours;
            hoursInput.step = this.options.hoursStep;
            hoursInput.value = String(initialTime.hours).padStart(2, '0');
            hoursInput.tabIndex = 0; // Ensure it's focusable
            timeInputs.appendChild(hoursInput);

            // Add separator
            const separator = document.createElement('span');
            separator.className = 'fdatepicker-time-separator';
            separator.textContent = ':';
            timeInputs.appendChild(separator);

            // Add minutes input
            const minutesInput = document.createElement('input');
            minutesInput.type = 'number';
            minutesInput.className = 'fdatepicker-time-input';
            minutesInput.dataset.time = 'minutes';
            minutesInput.min = this.options.minMinutes;
            minutesInput.max = this.options.maxMinutes;
            minutesInput.step = this.options.minutesStep;
            minutesInput.value = String(initialTime.minutes).padStart(2, '0');
            minutesInput.tabIndex = 0; // Ensure it's focusable
            timeInputs.appendChild(minutesInput);

            // --- Create AM/PM buttons as DOM elements and attach listeners ---
            if (this.options.ampm) {
                const amButton = document.createElement('div');
                amButton.className = `fdatepicker-time-ampm ${initialTime.isAM ? 'active' : ''}`;
                amButton.dataset.ampm = 'AM';
                if (this.options.ampmuppercase)
                    amButton.textContent = 'AM';
                else
                    amButton.textContent = 'am';
                amButton.tabIndex = 0; // Make it focusable
                amButton.addEventListener('click', () => {
                    // Deactivate all and activate this one
                    amButton.classList.add('active');
                    pmButton.classList.remove('active');
                    this.updateSelectedTime();
                });
                // Add keyboard support to AM button
                amButton.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        amButton.click();
                    }
                });
                timeInputs.appendChild(amButton);

                const pmButton = document.createElement('div');
                pmButton.className = `fdatepicker-time-ampm ${!initialTime.isAM ? 'active' : ''}`;
                pmButton.dataset.ampm = 'PM';
                if (this.options.ampmuppercase)
                    pmButton.textContent = 'PM';
                else
                    pmButton.textContent = 'pm';
                pmButton.tabIndex = 0; // Make it focusable
                pmButton.addEventListener('click', () => {
                    // Deactivate all and activate this one
                    pmButton.classList.add('active');
                    amButton.classList.remove('active');
                    this.updateSelectedTime();
                });
                // Add keyboard support to PM button
                pmButton.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        pmButton.click();
                    }
                });
                timeInputs.appendChild(pmButton);
            }

            const timepicker = document.createElement('div');
            timepicker.className = 'fdatepicker-timepicker';
            timepicker.appendChild(timeInputs);
            popup.appendChild(timepicker);

            popup.setAttribute('role', 'application');
            popup.setAttribute('aria-label', 'Date picker');
        }

        if (this.options.todayButton || this.options.clearButton || this.options.closeButton) {
            const buttonRow = document.createElement('div');
            buttonRow.className = 'fdatepicker-buttons';

            if (this.options.todayButton) {
                const todayBtn = document.createElement('button');
                todayBtn.type = 'button';
                todayBtn.className = 'fdatepicker-button-text';
                todayBtn.textContent = this.locale.today || 'Today';
                todayBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const today = new Date();
                    this.focusedDate = new Date(today); // order matters
                    this.selectDate(today.getDate());
                    this.render();
                });
                buttonRow.appendChild(todayBtn);
            }

            if (this.options.clearButton) {
                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'fdatepicker-button-text';
                clearBtn.textContent = this.locale.clear || 'Clear';
                clearBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.selectedDate = null;
                    this.selectedEndDate = null;
                    this.selectedDates = [];
                    this.updateInput();
                    this.render();
                });
                buttonRow.appendChild(clearBtn);
            }

            if (this.options.closeButton) {
                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'fdatepicker-button-text';
                closeBtn.textContent = this.locale.close || 'Close';
                closeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.close();
                });
                buttonRow.appendChild(closeBtn);
            }

            popup.appendChild(buttonRow);
        }

        this.container.appendChild(popup);
        return popup;
    }

    bindInputEvents() {
        this.input.addEventListener('input', this.boundHandlers.input);
        this.input.addEventListener('click', this.boundHandlers.click);
        this.input.addEventListener('paste', this.boundHandlers.paste);
        this.input.addEventListener('drop', this.boundHandlers.drop);
        this.input.addEventListener('keydown', this.boundHandlers.keydown);
    }

    bindGridAndPopupEvents() {
        // Mouseover for hover/focus
        this.grid?.addEventListener('mouseover', (e) => {
            if (e.target.classList.contains('fdatepicker-day')) {
                if (e.target.classList.contains('other-month')) {
                    e.target.classList.add('hover');
                } else {
                    this.setFocus(e.target);
                }
            } else if (e.target.classList.contains('fdatepicker-month') ||
                e.target.classList.contains('fdatepicker-year')) {
                this.setFocus(e.target);
            }
        });

        // Clear focus when mouse leaves the popup
        this.grid?.addEventListener('mouseleave', () => {
            this.clearFocus();
        });
 
        // Keyboard nav inside grid
        this.grid?.addEventListener('keydown', (e) => {
            const keys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'PageUp', 'PageDown', 'Home', 'End', ' ', 'Enter', 'Escape'];
            if (keys.includes(e.key)) {
                e.preventDefault();
                e.stopPropagation();

                const syntheticEvent = new KeyboardEvent('keydown', {
                    key: e.key,
                    shiftKey: e.shiftKey,
                    ctrlKey: e.ctrlKey,
                    altKey: e.altKey,
                    bubbles: false,
                    cancelable: true
                });
                this.input.dispatchEvent(syntheticEvent);
            }
        });

        this.popup.addEventListener('click', (e) => {
            e.stopPropagation();
            const action = e.target.dataset.action;
            if (action === 'prev') {
                this.navigateView(-1);
                this.setInitialFocus();
            }
            if (action === 'next') {
                this.navigateView(1);
                this.setInitialFocus();
            }

            if (e.target === this.title) {
                if (this.view === 'days') this.view = 'months';
                else if (this.view === 'months') this.view = 'years';
                this.render();
                this.setInitialFocus();
            }
            if (e.target.classList.contains('fdatepicker-day') && !e.target.classList.contains('other-month')) {
                this.selectDate(parseInt(e.target.textContent));
            }

            if (e.target.classList.contains('fdatepicker-month')) {
                this.selectMonth(parseInt(e.target.dataset.month));
            }

            if (e.target.classList.contains('fdatepicker-year') && !e.target.classList.contains('other-decade')) {
                this.selectYear(parseInt(e.target.dataset.year));
            }
        });

        // Time inputs
        if (this.options.timepicker) {
            [this.hoursInput, this.minutesInput].forEach(input => {
                if (input) {
                    input.addEventListener('keydown', (e) => {
                        // Only stop propagation for arrow keys to prevent grid navigation
                        if (['Enter', 'Escape'].includes(e.key)) {
                            e.preventDefault(); // Prevent any default browser behavior
                            e.stopPropagation(); // Stop the event from bubbling and being handled elsewhere
                            this.close(); // Close the datepicker
                            this.input.focus(); // Return focus to the original input
                            return;
                        }
                        if (['ArrowUp', 'ArrowDown'].includes(e.key)) {
                            e.stopPropagation();
                        }
                    });
                    input.addEventListener('change', () => this.updateSelectedTime());
                    input.addEventListener('blur', () => this.updateSelectedTime());
                }
            });
        }
 
    }

    // keyboard navigation function
    keyboardNavigate(direction, orientation) {
        if (this.view === 'days') {
            if (orientation === 'horizontal') {
                this.focusedDate.setDate(this.focusedDate.getDate() + direction);
            } else {
                this.focusedDate.setDate(this.focusedDate.getDate() + (direction * 7));
            }
            this.render();
            this.setDayFocus();
        } else if (this.view === 'months') {
            const currentMonth = this.focusedDate.getMonth();
            let newMonth;
            if (orientation === 'horizontal') {
                newMonth = currentMonth + direction;
            } else {
                newMonth = currentMonth + (direction * 3);
            }

            if (newMonth < 0) {
                this.focusedDate.setFullYear(this.focusedDate.getFullYear() - 1);
                this.focusedDate.setMonth(11 + newMonth + 1);
            } else if (newMonth > 11) {
                this.focusedDate.setFullYear(this.focusedDate.getFullYear() + 1);
                this.focusedDate.setMonth(newMonth - 12);
            } else {
                this.focusedDate.setMonth(newMonth);
            }
            this.render();
            this.setMonthFocus();
        } else if (this.view === 'years') {
            if (orientation === 'horizontal') {
                this.currentYear += direction;
            } else {
                this.currentYear += direction * 3;
            }
            this.render();
            this.setYearFocus();
        }
    }

    // keyboard selection
    keyboardHandleSelection() {
        if (this.view === 'days') {
            const focusedDay = this.popup.querySelector('.fdatepicker-day:focus, .fdatepicker-day.focus');
            if (focusedDay && !focusedDay.classList.contains('other-month') && !focusedDay.classList.contains('disabled')) {
                this.selectDate(parseInt(focusedDay.textContent));
            }
        } else if (this.view === 'months') {
            const focusedMonth = this.popup.querySelector('.fdatepicker-month:focus, .fdatepicker-month.focus');
            if (focusedMonth) {
                this.selectMonth(parseInt(focusedMonth.dataset.month));
            }
        } else if (this.view === 'years') {
            const focusedYear = this.popup.querySelector('.fdatepicker-year:focus, .fdatepicker-year.focus');
            if (focusedYear && !focusedYear.classList.contains('other-decade') && !focusedYear.classList.contains('disabled')) {
                this.selectYear(parseInt(focusedYear.dataset.year));
            }
        }
    }

    setInitialFocus() {
        setTimeout(() => {
            if (this.options.timeOnly) {
                // Focus the hours input in timeOnly mode
                if (this.hoursInput) {
                    this.hoursInput.focus();
                } else if (this.minutesInput) {
                    this.minutesInput.focus();
                }
                return;
            }
            if (this.view === 'days') this.setDayFocus();
            else if (this.view === 'months') this.setMonthFocus();
            else if (this.view === 'years') this.setYearFocus();
        }, 0);
    }

    setDayFocus() {
        if (this.view !== 'days') return;

        // Remove existing focus
        this.clearFocus();

        if (this.popup) {
            const day = this.focusedDate.getDate();
            // Find the day element that matches current date and is not from other month
            const dayElements = Array.from(this.popup.querySelectorAll('.fdatepicker-day:not(.other-month)'));
            const targetDay = dayElements.find(el => parseInt(el.textContent) === day);

            if (targetDay) {
                this.setFocus(targetDay);
            }
        }
    }

    setMonthFocus() {
        if (this.view !== 'months') return;

        // Remove existing focus
        this.clearFocus();

        if (this.popup) {
            const month = this.focusedDate.getMonth();
            const monthElement = this.popup.querySelector(`[data-month="${month}"]`);

            if (monthElement) {
                this.setFocus(monthElement);
            }
        }
    }

    setYearFocus() {
        if (this.view !== 'years') return;
 
        // Remove existing focus
        this.clearFocus();

        if (this.popup) {
            const year = this.focusedDate.getFullYear();
            const startDecade = Math.floor(this.currentYear / 10) * 10;
            const minYear = startDecade;
            const maxYear = startDecade + 9;

            // Only focus if year is in valid range
            if (year >= minYear && year <= maxYear) {
                const yearElement = this.popup.querySelector(`[data-year="${year}"]`);
                if (yearElement) {
                    this.setFocus(yearElement);
                }
            }
        }
    }

    setFocus(element) {
        if (this.focusedElement) {
            this.focusedElement.setAttribute('tabindex', '-1');
            this.focusedElement.classList.remove('focus');
        }

        this.focusedElement = element;
        element.setAttribute('tabindex', '0');
        element.classList.add('focus');
        element.focus();

        // Range preview logic
        if (this.options.range && this.selectedDate && !this.selectedEndDate) {
            // Clear all 'in-range' classes first
            if (this.popup) {
                this.popup.querySelectorAll('.fdatepicker-day.in-range').forEach(day => {
                    day.classList.remove('in-range');
                });
            }
            const day = parseInt(element.textContent);
            const month = this.focusedDate.getMonth();
            const year = this.focusedDate.getFullYear();
            const focusedDate = new Date(year, month, day);

            // Make sure element is a valid day (not "other-month", disabled, etc.)
            if (isNaN(focusedDate.getTime()) ||
                element.classList.contains('other-month') ||
                element.classList.contains('disabled')) {
                return;
            }

            const startDate = this.selectedDate;
            const endDate = focusedDate;

            // Determine range bounds
            const start = startDate < endDate ? startDate : endDate;
            const end = startDate < endDate ? endDate : startDate;

            if (this.popup) {
                // Get all day elements in the current view
                const dayElements = Array.from(this.popup.querySelectorAll('.fdatepicker-day:not(.other-month)'));

                dayElements.forEach(dayEl => {
                    const dayNum = parseInt(dayEl.textContent);
                    const dayDate = new Date(year, month, dayNum);

                    // Check if day is strictly between start and end
                    if (dayDate > start && dayDate < end) {
                        dayEl.classList.add('in-range');
                    }
                });
            }
        }
    }

    clearFocus() {
        if (this.popup) {
            this.popup.querySelectorAll('.fdatepicker-day, .fdatepicker-month, .fdatepicker-year').forEach(el => {
                el.classList.remove('focus');
                el.classList.remove('hover');
                el.setAttribute('tabindex', '-1');
            });
        }
        this.focusedElement = null;
    }

    getDaysInMonth(year, monthIndex) {
        // MonthIndex 0 = Jan, 11 = Dec. Next month (monthIndex + 1) with day 0 = last day of target month.
        return new Date(year, monthIndex + 1, 0).getDate();
    }

    navigateView(direction) {
        if (this.view === 'days') {
            //this.focusedDate.setMonth(this.focusedDate.getMonth() + direction);
            const targetMonth = this.focusedDate.getMonth() + direction;
            const targetYear = this.focusedDate.getFullYear();
            const maxDay = this.getDaysInMonth(targetYear, targetMonth);
            this.focusedDate.setMonth(targetMonth,Math.min(this.focusedDate.getDate(), maxDay));
        } else if (this.view === 'months') {
            this.focusedDate.setFullYear(this.focusedDate.getFullYear() + direction);
        } else if (this.view === 'years') {
            // Move by 10 years per navigation
            this.currentYear += direction * 10;
            // Also update focusedDate for consistency
            this.focusedDate.setFullYear(this.currentYear);
        }
        this.render();
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        if (this.isOpen) return;

        // Reset focusedDate to selected date if available, otherwise use current date
        if (this.selectedDate) {
            this.focusedDate = new Date(this.selectedDate);
            if (this.view === 'years') {
                // For years view, set currentYear to the year of selected date
                this.currentYear = this.selectedDate.getFullYear();
            }
        } else {
            this.focusedDate = new Date();
            if (this.view === 'years') {
                this.currentYear = this.focusedDate.getFullYear();
            }
        }

        // For multiple selection mode, use first selected date
        if (this.options.multiple && this.selectedDates.length > 0) {
            this.focusedDate = new Date(this.selectedDates[0]);
            if (this.view === 'years') {
                this.currentYear = this.focusedDate.getFullYear();
            }
        }

        // Create popup and attach it
        this.popup = this.createPopup();
 
        // Now query elements inside popup
        this.title = this.popup.querySelector('.fdatepicker-title');
        this.content = this.popup.querySelector('.fdatepicker-content');
        this.grid = this.popup.querySelector('.fdatepicker-grid');
        this.hoursInput = this.popup.querySelector('[data-time="hours"]');
        this.minutesInput = this.popup.querySelector('[data-time="minutes"]');

        this.bindGridAndPopupEvents();

        // Add to global open instances and ensure listeners are added
        FDatepicker._openInstances.add(this);
        FDatepicker._addGlobalListeners();

        this.isOpen = true;
        this.render();

        this.popup.classList.add('active');

        // Set initial focus after render
        this.setInitialFocus();

        this.setPosition();

        if (this.options.onOpen && typeof this.options.onOpen === 'function') this.options.onOpen.call(this.input, this);
    }

    // Simplified positioning - let CSS and browser handle most of it
    setPosition() {
        const inputRect = this.input.getBoundingClientRect();
        const inputBounds = this.input.getBoundingClientRect();
        const offset = 4;

        // Get the input's position relative to the document (not the viewport)
        const inputTop = inputRect.top + window.scrollY;
        const inputLeft = inputRect.left + window.scrollX;

        // Calculate space above and below relative to the *document* and viewport
        const spaceBelow = window.innerHeight - inputBounds.bottom;
        const spaceAbove = inputBounds.top; // This is still relative to viewport

        let top, left;

        this.popup.classList.remove('fdatepicker-popup-top', 'fdatepicker-popup-bottom', 'fdatepicker-popup-middle');
        
        if (spaceAbove > spaceBelow && spaceAbove > 300) {
            // Position above
            this.popup.classList.add('fdatepicker-popup-top');
            top = inputTop - offset;
        } else if ((spaceAbove > spaceBelow && spaceAbove > 150) || spaceBelow < 150) {
            // Position above (middle variant)
            this.popup.classList.add('fdatepicker-popup-middle');
            top = inputTop - offset;
        } else {
            // Position below (default)
            this.popup.classList.add('fdatepicker-popup-bottom');
            top = inputTop + inputBounds.height + offset;
        }
        
        // Horizontal: align with input's left edge, relative to the document
        left = inputLeft;

        // Basic bounds checking - keep on screen
        // Ensure the popup doesn't go off the left or right edge of the viewport
        const popupWidth = 320; // Assuming a standard width, adjust if needed
        if (left < 10) left = 10;
        if (left > window.innerWidth - popupWidth) left = window.innerWidth - popupWidth;

        // Ensure the popup doesn't go off the top of the document
        if (top < 10) top = 10;

        // Apply position using document coordinates
        this.popup.style.top = `${top}px`;
        this.popup.style.left = `${left}px`;
    }

    close() {
        if (!this.isOpen) return;

        this.isOpen = false;
        this.popup.classList.remove('active');
        this.clearFocus();
 
        // Remove popup from DOM
        if (this.popup && this.popup.parentNode) {
            this.popup.parentNode.removeChild(this.popup);
        }

        // Null it out so a new one is created on next open
        this.popup = null;

        // Remove from open instances first
        FDatepicker._openInstances.delete(this);

        // Clean up global listeners if no instances are open
        // Use a timeout to ensure any pending operations complete
        setTimeout(() => {
            FDatepicker._cleanupOpenInstances();
        }, 0);

        if (this.options.onClose && typeof this.options.onClose === 'function') this.options.onClose.call(this.input, this);
    }

    destroy() {
        // Close the popup first
        if (this.isOpen) {
            this.close();
        }

        // Remove the instance reference from input
        if (this.input && this.input._fdatepicker === this) {
            delete this.input._fdatepicker;
        }

        this.input.removeEventListener('input', this.boundHandlers.input);
        this.input.removeEventListener('click', this.boundHandlers.click);
        this.input.removeEventListener('paste', this.boundHandlers.paste);
        this.input.removeEventListener('drop', this.boundHandlers.drop);
        this.input.removeEventListener('keydown', this.boundHandlers.keydown);

        this.input = null;
        this.popup = null;
        this.container = null;
        this.title = null;
        this.content = null;
        this.grid = null;
        this.hoursInput = null;
        this.minutesInput = null;
        this.focusedElement = null;
        this.selectedDate = null;
        this.selectedEndDate = null;
        this.selectedDates = [];
        this.options = null;
        this.locale = null;

        // Remove from _openInstances in case close wasn't called
        FDatepicker._openInstances.delete(this);
        // Clean up global container if no more popups
        this.cleanupGlobalContainer();

        // Clean up global listeners if needed
        setTimeout(() => {
            FDatepicker._cleanupOpenInstances();
        }, 0);
    }
 
    static destroyAll() {
        // Create a copy of the set to avoid modification during iteration
        const instancesToDestroy = Array.from(FDatepicker._openInstances);
        
        // Also find instances by DOM query
        const inputs = document.querySelectorAll('input[data-fdatepicker]');
        inputs.forEach(input => {
            if (input._fdatepicker) {
                instancesToDestroy.push(input._fdatepicker);
            }
        });

        // Destroy all found instances
        instancesToDestroy.forEach(instance => {
            if (instance && typeof instance.destroy === 'function') {
                instance.destroy();
            }
        });

        // Force cleanup of global state
        FDatepicker._openInstances.clear();
        FDatepicker._removeGlobalListeners();
    }

    // Utility method to check if global cleanup is needed
    static cleanup() {
        FDatepicker._cleanupOpenInstances();
    }

    // helper method to clean dom
    cleanupGlobalContainer() {
        const container = document.getElementById('fdatepicker-global-container');
        if (container && container.children.length === 0) {
            // No more popups, remove the container
            container.parentNode.removeChild(container);
        }
    }

    triggerOnSelect() {
        if (typeof this.options.onSelect !== 'function') return;

        let date = null;
        let formattedDate = '';

        if (this.options.multiple) {
            date = this.selectedDates;
            formattedDate = this.selectedDates.map(d => this.formatDate(d)).join(this.options.multipleSeparator);
        } else if (this.options.range) {
            date = [this.selectedDate, this.selectedEndDate].filter(Boolean);
            formattedDate = date.map(d => this.formatDate(d)).join(' - ');
        } else {
            date = this.selectedDate;
            formattedDate = this.selectedDate ? this.formatDate(this.selectedDate) : '';
        }

        // Call the onSelect callback
        this.options.onSelect.call(this.input,formattedDate, date, this);
    }

    /**
     * Public method to set the selected date(s)
     * @param {Date|Date[]|Array<Date>} date - Single Date, array of Dates (multiple), or [start, end] (range)
     * @param boolean doTriggerOnSelect - if true, runs triggerOnSelect when setting the date
     */
    setDate(date, doTriggerOnSelect = true) {
        // Helper function to safely create a Date
        const safeDate = (d) => {
            const dateObj = new Date(d);
            return isNaN(dateObj.getTime()) ? null : dateObj;
        };

        if (!date) {
            this.selectedDate = null;
            this.selectedEndDate = null;
            this.selectedDates = [];
        } else if (this.options.multiple) {
            if (Array.isArray(date)) {
                this.selectedDates = date.map(safeDate).filter(d => d !== null);
            } else {
                // Single date in multiple mode
                this.selectedDates = [safeDate(date)].filter(d => d !== null);
            }
            this.selectedDate = null;
            this.selectedEndDate = null;
        } else if (this.options.range) {
            if (Array.isArray(date) && date.length >= 2) {
                this.selectedDate = safeDate(date[0]);
                this.selectedEndDate = safeDate(date[1]);
            } else {
                this.selectedDate = safeDate(date);
                this.selectedEndDate = null;
            }
        } else {
            this.selectedDate = safeDate(date);
            this.selectedEndDate = null;
            this.selectedDates = [];
        }

        // Update input, UI, and trigger onSelect
        this.focusedDate = this.selectedDate ? new Date(this.selectedDate) : new Date(); // make sure the popup shows a relevant date
        this.updateInput();
 
        // Only update UI if popup is open
        if (this.isOpen) {
            this.render();
            this.setDayFocus();
        }

        // Auto-close if not using timepicker
        if (this.options.autoClose && !this.options.timepicker) {
            this.close();
        }

        if (doTriggerOnSelect) {
            this.triggerOnSelect();
        }
    }

    selectDate(day) {
        if (this.options.timeOnly) {
            // We don't want to change the selected day.
            // Instead, just ensure the time is updated based on the inputs.
            this.updateSelectedTime();
            // We don't call close() here because autoClose is already false.
            return;
        }

        const year = this.focusedDate.getFullYear();
        const month = this.focusedDate.getMonth();

        let hours = 0, minutes = 0;

        // If there's already a selected date, use its time
        if (this.selectedDate) {
            hours = this.selectedDate.getHours();
            minutes = this.selectedDate.getMinutes();
        }
        // Otherwise, try to get time from timepicker inputs
        else if (this.options.timepicker) {
            hours = parseInt(this.hoursInput?.value) || 0;
            minutes = parseInt(this.minutesInput?.value) || 0;
            // Handle AM/PM
            if (this.options.ampm) {
                const isAM = this.popup.querySelector('[data-ampm].active')?.dataset.ampm === 'AM';
                if (!isAM && hours < 12) {
                    hours += 12;
                } else if (isAM && hours === 12) {
                    hours = 0;
                }
            }
        }

        // Create new date with preserved time
        const selectedDate = new Date(year, month, day, hours, minutes);

        if (this.isDateDisabled(selectedDate)) {
            return;
        }

        if (this.options.multiple) {
            // Multiple selection
            const existingIndex = this.selectedDates.findIndex(date => 
                date.toDateString() === selectedDate.toDateString()
            );

            if (existingIndex >= 0) {
                // Remove if already selected
                this.selectedDates.splice(existingIndex, 1);
            } else {
                // Add to selection
                this.selectedDates.push(selectedDate);
                this.selectedDates.sort((a, b) => a - b);
            }

            this.updateInput();
            this.render();
            this.updateMultipleDisplay();
            this.setDayFocus();

        } else if (this.options.range) {
            // Range selection - fixed to stay open
            if (!this.selectedDate || this.selectedEndDate) {
                // Start new range
                this.selectedDate = selectedDate;
                this.selectedEndDate = null;
                this.render();
                this.setDayFocus();
            } else {
                // Complete range
                if (selectedDate < this.selectedDate) {
                    this.selectedEndDate = this.selectedDate;
                    this.selectedDate = selectedDate;
                } else {
                    this.selectedEndDate = selectedDate;
                }
                this.updateInput();
                if (!this.options.timepicker) {
                    if (this.options.autoClose) {
                        this.close();
                    }
                } else {
                    this.render();
                    this.setDayFocus();
                }
            }
        } else {
            // Single selection
            if (this.selectedDate && this.selectedDate.toDateString() === selectedDate.toDateString()) {
                this.selectedDate = null; // selecting an already selected day: deselect it
            } else {
                this.selectedDate = selectedDate;
            }
            this.updateInput();
            if (!this.options.timepicker) {
                if (this.options.autoClose) {
                    this.close();
                }
            } else {
                this.render();
                this.setDayFocus();
            }
        }
        this.triggerOnSelect();
    }

    selectMonth(month) {
        this.focusedDate.setMonth(month);
        this.view = 'days';
        this.render();
        this.setInitialFocus();
    }

    selectYear(year) {
        this.focusedDate.setFullYear(year);
        this.currentYear = year;
        this.view = 'months';
        this.render();
        this.setInitialFocus();
    }

    validateTimeInput(type, value) {
        if (type === 'hours') {
            const is24Hour = !this.options.ampm;
            let min = is24Hour ? 0 : 1;
            let max = is24Hour ? 23 : 12;

            if (this.options.minHours !== null) min = this.options.minHours;
            if (this.options.maxHours !== null) max = this.options.maxHours;

            if (value < min) return min;
            if (value > max) return max;

            // Apply step constraint
            const remainder = (value - min) % this.options.hoursStep;
            if (remainder !== 0) {
                return value - remainder;
            }
        } else if (type === 'minutes') {
            if (value < this.options.minMinutes) return this.options.minMinutes;
            if (value > this.options.maxMinutes) return this.options.maxMinutes;

            // Apply step constraint
            const remainder = (value - this.options.minMinutes) % this.options.minutesStep;
            if (remainder !== 0) {
                return value - remainder;
            }
        }

        return value;
    }

    updateSelectedTime() {
        let target;
        // Handle timeOnly mode specially
        if (this.options.timeOnly) {
            // Create a date object for today if no date is selected
            if (!this.selectedDate) {
                this.selectedDate = new Date();
            }
            target = this.selectedDate;
        } else {
            // Original logic for non-timeOnly modes
            target = this.options.multiple ? this.selectedDates[this.selectedDates.length - 1] :
                this.selectedEndDate ? this.selectedEndDate :
                this.selectedDate ? this.selectedDate : null;
        }

        if (!target) return;

        let hours = parseInt(this.hoursInput?.value) || 0;
        let minutes = parseInt(this.minutesInput?.value) || 0;

        // Validate inputs
        hours = this.validateTimeInput('hours', hours);
        minutes = this.validateTimeInput('minutes', minutes);

        // Update inputs with validated values
        if (this.hoursInput) {
            this.hoursInput.value = String(hours).padStart(2, '0');
        }
        if (this.minutesInput) {
            this.minutesInput.value = String(minutes).padStart(2, '0');
        }

        // Handle AM/PM conversion
        if (this.options.ampm) {
            const isAM = this.popup.querySelector('[data-ampm].active')?.dataset.ampm === 'AM';
            if (isAM && hours === 12) hours = 0;
            else if (!isAM && hours !== 12) hours += 12;
        }

        /*targets.forEach(date => {
            if (date) {
                date.setHours(hours, minutes);
            }
        });*/
        target.setHours(hours, minutes);

        this.updateInput();
    }

    updateMultipleDisplay() {
        if (!this.options.multiple) {
            return;
        }
        if (!this.options.multipleDisplaySelector) {
            return;
        }
        const display = document.querySelector(this.options.multipleDisplaySelector);
        if (display && this.options.multiple) {
            if (this.selectedDates.length === 0) {
                display.textContent = this.locale.noDatesSelected;
            } else {
                const dateStrings = this.selectedDates.map(date => this.formatDate(date));
                const selectedString = this.locale.datesSelected;
                display.innerHTML = "<strong>" + selectedString.replace(/\{0\}/g, this.selectedDates.length) + `</strong><br>${dateStrings.join(', ')}`;
            }
        }
    }

    formatDate(date, format = null) {
        if (!date) return '';
        format = format || this.options.format;
        return FDatepicker.formatDate(date, format, this.locale);
    }

    static formatDate(date, format = 'm/d/Y', locale = null) {
        if (!date) return '';
        const loc = locale || FDATEPICKER_DEFAULT_MESSAGES;
        const hours = date.getHours();
        const minutes = date.getMinutes();
        const seconds = date.getSeconds();

        // Compute 12-hour clock values
        const isPM = hours >= 12;
        const hour12 = hours % 12 === 0 ? 12 : hours % 12; // 12-hour clock: 1-12

        const formatMap = {
            'd': String(date.getDate()).padStart(2, '0'),
            'j': date.getDate(),
            'l': loc.days[date.getDay()],
            'D': loc.daysShort[date.getDay()],
            'S': FDatepicker.getOrdinalSuffix(date.getDate()),
            'm': String(date.getMonth() + 1).padStart(2, '0'),
            'n': date.getMonth() + 1,
            'F': loc.months[date.getMonth()],
            'M': loc.monthsShort[date.getMonth()],
            'Y': date.getFullYear(),
            'y': String(date.getFullYear()).slice(-2),

            // 24-hour format
            'H': String(hours).padStart(2, '0'),
            'G': hours,

            // 12-hour format (newly added)
            'h': String(hour12).padStart(2, '0'),  // 12-hour with leading zero: 01-12
            'g': hour12,                            // 12-hour without leading zero: 1-12

            // Minutes and seconds
            'i': String(minutes).padStart(2, '0'),
            's': String(seconds).padStart(2, '0'),

            // AM/PM
            'A': isPM ? 'PM' : 'AM',
            'a': isPM ? 'pm' : 'am'
        };

        // Handle escaped characters by replacing \X with a placeholder, then restoring after formatting
        const escapedChars = {};
        let placeholderIndex = 0;

        // First pass: replace escaped characters with unique placeholders
        let processedFormat = format.replace(/\\(.)/g, (match, char) => {
            const placeholder = `___${placeholderIndex}___`;
            escapedChars[placeholder] = char;
            placeholderIndex++;
            return placeholder;
        });

        // Second pass: replace format characters
        processedFormat = processedFormat.replace(/d|j|l|D|S|m|n|F|M|Y|y|H|G|h|g|i|s|A|a/g, match => formatMap[match] || '');

        // Third pass: restore escaped characters
        Object.keys(escapedChars).forEach(placeholder => {
            processedFormat = processedFormat.replace(placeholder, escapedChars[placeholder]);
        });

        return processedFormat;
    }

    static getOrdinalSuffix(day) {
        if (day > 3 && day < 21) return 'th';
        switch (day % 10) {
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
            default: return 'th';
        }
    }

    updateInput() {
        let value = '';

        if (this.options.multiple) {
            const count = this.selectedDates.length;
            if (count > 0) {
                if (this.options.altField && this.options.multipleDisplaySelector) {
                    if (count === 1) {
                        // Use the exact single date message
                        value = this.locale.singleDateSelected || '1 date selected';
                    } else {
                        // Use the plural pattern with placeholder
                        value = this.locale.multipleDatesSelected || '{count} dates selected';
                        value = value.replace('{count}', count);
                    }
                } else {
                    value = this.selectedDates.map(date => this.formatDate(date)).join(this.options.multipleSeparator);
                }
            }
        } else if (this.options.range && this.selectedDate && this.selectedEndDate) {
            if (this.options.altField) {
                // we have an altField, so we can be pretty here
                value = `${this.formatDate(this.selectedDate)} - ${this.formatDate(this.selectedEndDate)}`;
            } else {
                value = this.formatDate(this.selectedDate) + this.options.multipleSeparator + this.formatDate(this.selectedEndDate);
            }
        } else if (this.selectedDate) {
            value = this.formatDate(this.selectedDate);
        }

        this.input.value = value;

        // Update alt field
        if (this.options.altField) {
            const altField = document.getElementById(this.options.altField);
            if (altField) {
                let altValue = '';
                if (this.options.multiple) {
                    altValue = this.selectedDates.map(date => this.formatDate(date, this.options.altFormat)).join(this.options.altFieldMultipleSeparator);
                } else if (this.options.range && this.selectedDate && this.selectedEndDate) {
                    altValue = this.formatDate(this.selectedDate, this.options.altFormat) + this.options.altFieldMultipleSeparator + this.formatDate(this.selectedEndDate, this.options.altFormat);
                } else if (this.selectedDate) {
                    altValue = this.formatDate(this.selectedDate, this.options.altFormat);
                }
                altField.value = altValue;
                altField.dispatchEvent(new Event('change', { bubbles: true }));
                altField.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        // Update multiple display
        this.updateMultipleDisplay();

        // Trigger change event
        if (!this.options.altField) {
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    render() {
        if (!this.popup || this.options.timeOnly) return;
        this.renderTitle();
        if (this.view === 'days') {
            this.renderDays();
        } else if (this.view === 'months') {
            this.renderMonths();
        } else if (this.view === 'years') {
            this.renderYears();
        }
    }

    renderTitle() {
        let title = '';
        if (this.view === 'days') {
            title = `${this.locale.months[this.focusedDate.getMonth()]} ${this.focusedDate.getFullYear()}`;
        } else if (this.view === 'months') {
            title = this.focusedDate.getFullYear();
        } else if (this.view === 'years') {
            const startDecade = Math.floor(this.currentYear / 10) * 10;
            const endDecade = startDecade + 9;
            title = `${startDecade} - ${endDecade}`;
        }
        this.title.textContent = title;
    }

    renderDays() {
        this.grid.className = 'fdatepicker-grid';

        // Build day headers based on first day of week
        let headerHtml = '';
        for (let i = 0; i < 7; i++) {
            const dayIndex = (this.options.firstDayOfWeek + i) % 7;
            headerHtml += `<div class="fdatepicker-day-header">${this.locale.daysMin[dayIndex]}</div>`;
        }
        this.grid.innerHTML = headerHtml;

        const firstDayOfWeek = new Date(this.focusedDate.getFullYear(), this.focusedDate.getMonth(), 1);
        const lastDay = new Date(this.focusedDate.getFullYear(), this.focusedDate.getMonth() + 1, 0);
        const today = new Date();

        // Calculate days from previous month based on first day of week setting
        let prevMonthDays = (firstDayOfWeek.getDay() - this.options.firstDayOfWeek + 7) % 7;
        const prevMonth = new Date(this.focusedDate.getFullYear(), this.focusedDate.getMonth() - 1, 0);

        for (let i = prevMonthDays - 1; i >= 0; i--) {
            const day = document.createElement('div');
            day.className = 'fdatepicker-day other-month';
            day.textContent = prevMonth.getDate() - i;
            day.setAttribute('tabindex', '-1');
            this.grid.appendChild(day);
        }

        // Days of current month
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dayEl = document.createElement('div');
            dayEl.className = 'fdatepicker-day';
            dayEl.textContent = day;
            dayEl.setAttribute('tabindex', '-1');

            const dayDate = new Date(this.focusedDate.getFullYear(), this.focusedDate.getMonth(), day);

            // Add weekend class
            const dayOfWeek = dayDate.getDay();
            if (this.options.weekendDays.includes(dayOfWeek)) {
                dayEl.classList.add('weekend');
            }

            // Add today class
            if (dayDate.toDateString() === today.toDateString()) {
                dayEl.classList.add('today');
            }

            if (this.isDateDisabled(dayDate)) {
                dayEl.classList.add('disabled');
                dayEl.setAttribute('aria-disabled', 'true');
            }

            // Handle different selection modes
            if (this.options.multiple) {
                // Multiple selection
                const isSelected = this.selectedDates.some(date => 
                    date.toDateString() === dayDate.toDateString()
                );
                if (isSelected) {
                    dayEl.classList.add('multi-selected');
                }
            } else {
                // Single or range selection
                if (this.selectedDate && dayDate.toDateString() === this.selectedDate.toDateString()) {
                    dayEl.classList.add(this.options.range ? 'range-start' : 'selected');
                    dayEl.setAttribute('aria-selected', 'true');
                }

                if (this.options.range && this.selectedEndDate && dayDate.toDateString() === this.selectedEndDate.toDateString()) {
                    dayEl.classList.add('range-end');
                    dayEl.setAttribute('aria-selected', 'true');
                }

                if (this.options.range && this.selectedDate && this.selectedEndDate && 
                    dayDate > this.selectedDate && dayDate < this.selectedEndDate) {
                    dayEl.classList.add('in-range');
                    dayEl.setAttribute('aria-selected', 'true');
                }
            }

            this.grid.appendChild(dayEl);
        }

        // Days from next month
        const cellsWithoutHeaders = this.grid.children.length - 7; // Remove 7 header cells
        const hasSixRows = cellsWithoutHeaders > 35;
        const totalCellsNeeded = hasSixRows ? 42 : 35; // put 42 to always have 6 rows
        const remainingCells = totalCellsNeeded - cellsWithoutHeaders;

        for (let day = 1; day <= remainingCells; day++) {
            const dayEl = document.createElement('div');
            dayEl.className = 'fdatepicker-day other-month';
            dayEl.textContent = day;
            dayEl.setAttribute('tabindex', '-1');
            dayEl.setAttribute('aria-disabled', 'true');
            this.grid.appendChild(dayEl);
        }
    }

    renderMonths() {
        this.grid.className = 'fdatepicker-grid months';
        this.grid.innerHTML = '';

        for (let month = 0; month < 12; month++) {
            const monthEl = document.createElement('div');
            monthEl.className = 'fdatepicker-month';
            monthEl.textContent = this.locale.monthsShort[month];
            monthEl.dataset.month = month;
            monthEl.setAttribute('tabindex', '-1');

            if (month === new Date().getMonth() && this.focusedDate.getFullYear() === new Date().getFullYear()) {
                monthEl.classList.add('current');
            }

            if (this.selectedDate && month === this.selectedDate.getMonth() && 
                this.focusedDate.getFullYear() === this.selectedDate.getFullYear()) {
                monthEl.classList.add('selected');
                monthEl.setAttribute('aria-selected', 'true');
            }

            this.grid.appendChild(monthEl);
        }
    }

    renderYears() {
        this.grid.className = 'fdatepicker-grid years';
        this.grid.innerHTML = '';

        const startDecade = Math.floor(this.currentYear / 10) * 10; // e.g., 2020
        const years = [
            startDecade - 1, // Previous decade end (e.g., 2019)
            ...Array.from({ length: 10 }, (_, i) => startDecade + i), // 2020–2029
            startDecade + 10  // Next decade start (e.g., 2030)
        ];

        years.forEach(year => {
            const yearEl = document.createElement('div');
            yearEl.className = 'fdatepicker-year';
            yearEl.textContent = year;
            yearEl.dataset.year = year;
            yearEl.setAttribute('tabindex', '-1');

            // Mark current year
            if (year === new Date().getFullYear()) {
                yearEl.classList.add('current');
            }

            // Mark selected year
            if (this.selectedDate && year === this.selectedDate.getFullYear()) {
                yearEl.classList.add('selected');
                yearEl.setAttribute('aria-selected', 'true');
            }

            // Disable placeholder years (first and last)
            if (year === startDecade - 1 || year === startDecade + 10) {
                yearEl.classList.add('disabled','other-decade');
                yearEl.setAttribute('aria-disabled', 'true');
            }

            this.grid.appendChild(yearEl);
        });
    }

    /* the next 2 function are there so you can call update via JS to change options */
    update(options, value) {
        if (typeof options === 'string') {
            // Single option
            this.setOption(options, value);
        } else {
            // Multiple options
            Object.entries(options).forEach(([key, val]) => {
                this.setOption(key, val);
            });
        }
    }

    setOption(option, value) {
        const prevValue = this.options[option];
        if (prevValue === value) return; // No change

        this.options[option] = value;

        // Handle special cases
        switch (option) {
            case 'multiple':
                if (value) {
                    // Switch to multiple mode
                    this.selectedDates = this.selectedDate ? [this.selectedDate] : [];
                    this.selectedDate = null;
                    this.selectedEndDate = null;
                } else {
                    // Switch to single mode
                    this.selectedDate = this.selectedDates.length > 0 ? this.selectedDates[0] : null;
                    this.selectedDates = [];
                }
                break;

            case 'range':
                if (value) {
                    // Ensure we don't conflict with multiple
                    if (this.options.multiple) {
                        this.options.multiple = false;
                        this.selectedDates = [];
                    }
                }
                break;
            case 'format':
            case 'altFormat':
            case 'todayButton':
            case 'clearButton':
            case 'closeButton':
            case 'firstDay':
            case 'timepickerDefaultNow':
                // These don't require immediate DOM changes
                break;
        }

        // Refresh UI and input
        this.updateInput();
        this.render();
        this.updateMultipleDisplay();
    }
}
