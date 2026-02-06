
(function (global) {
    const FTABLE_DEFAULT_MESSAGES = {
    serverCommunicationError: 'An error occurred while communicating to the server.',
    loadingMessage: 'Loading records...',
    noDataAvailable: 'No data available!',
    addNewRecord: 'Add new record',
    editRecord: 'Edit record',
    areYouSure: 'Are you sure?',
    deleteConfirmation: 'This record will be deleted. Are you sure?',
    yes: 'Yes',
    no: 'No',
    save: 'Save',
    saving: 'Saving',
    cancel: 'Cancel',
    deleteText: 'Delete',
    deleting: 'Deleting',
    error: 'An error has occured',
    close: 'Close',
    cannotLoadOptionsFor: 'Cannot load options for field {0}!',
    pagingInfo: 'Showing {0}-{1} of {2}',
    canNotDeletedRecords: 'Can not delete {0} of {1} records!',
    deleteProgress: 'Deleting {0} of {1} records, processing...',
    pageSizeChangeLabel: 'Row count',
    gotoPageLabel: 'Go to page',
    sortingInfoPrefix: 'Sorting applied: ',
    sortingInfoSuffix: '', // optional
    ascending: 'Ascending',
    descending: 'Descending',
    sortingInfoNone: 'No sorting applied',
    resetSorting: 'Reset sorting',
    csvExport: 'CSV',
    printTable: '🖨️ Print',
    cloneRecord: 'Clone Record',
    resetTable: 'Reset table',
    resetTableConfirm: 'This will reset all columns, pagesize, sorting to their defaults. Do you want to continue?',
    resetSearch: 'Reset'
};

class FTableOptionsCache {
    constructor() {
        this.cache = new Map();
        this.pendingRequests = new Map(); // Track ongoing requests
    }

    generateKey(url, params) {
        const sortedParams = Object.keys(params || {})
            .sort()
            .map(key => `${key}=${params[key]}`)
            .join('&');
        return `${url}?${sortedParams}`;
    }

    get(url, params) {
        const key = this.generateKey(url, params);
        return this.cache.get(key);
    }

    set(url, params, data) {
        const key = this.generateKey(url, params);
        this.cache.set(key, data);
    }

    clear(url = null, params = null) {
        if (url) {
            if (params) {
                const key = this.generateKey(url, params);
                this.cache.delete(key);
            } else {
                // Clear all entries that start with this URL
                const urlPrefix = url.split('?')[0];
                for (const [key] of this.cache) {
                    if (key.startsWith(urlPrefix)) {
                        this.cache.delete(key);
                    }
                }
            }
        } else {
            this.cache.clear();
        }
    }

    async getOrCreate(url, params, fetchFn) {
        const key = this.generateKey(url, params);

        // Return cached result if available
        const cached = this.cache.get(key);
        if (cached) return cached;

        // Check if same request is already in progress
        if (this.pendingRequests.has(key)) {
            // Wait for the existing request to complete
            return await this.pendingRequests.get(key);
        }

        // Create new request
        const requestPromise = (async () => {
            try {
                const result = await fetchFn();
                this.cache.set(key, result);
                return result;
            } finally {
                // Clean up pending request tracking
                this.pendingRequests.delete(key);
            }
        })();

        // Track this request
        this.pendingRequests.set(key, requestPromise);

        return await requestPromise;
    }

    size() {
        return this.cache.size;
    }
}

class FTableEventEmitter {
    constructor() {
        this.events = {};
    }

    on(event, callback) {
        if (!this.events[event]) {
            this.events[event] = [];
        }
        this.events[event].push(callback);
        return this;
    }

    once(event, callback) {
        // Create a wrapper that removes itself after first call
        const wrapper = (...args) => {
            this.off(event, wrapper);
            callback.apply(this, args);
        };

        // Store reference to wrapper so it can be removed
        wrapper.fn = callback; // for off() to match

        this.on(event, wrapper);
        return this;
    }

    emit(event, data = {}) {
        if (this.events[event]) {
            this.events[event].forEach(callback => callback(data));
        }
        return this;
    }

    off(event, callback) {
        if (this.events[event]) {
            this.events[event] = this.events[event].filter(cb => cb !== callback);
        }
        return this;
    }
}

class FTableLogger {
    static LOG_LEVELS = {
        DEBUG: 0,
        INFO: 1,
        WARN: 2,
        ERROR: 3,
        NONE: 4
    };

    constructor(level = FTableLogger.LOG_LEVELS.WARN) {
        this.level = level;
    }

    log(level, message) {
        if (!window.console || level < this.level) return;
        
        const levelName = Object.keys(FTableLogger.LOG_LEVELS)
            .find(key => FTableLogger.LOG_LEVELS[key] === level);
        //console.trace();
        console.log(`fTable ${levelName}: ${message}`);
    }

    debug(message) { this.log(FTableLogger.LOG_LEVELS.DEBUG, message); }
    info(message) { this.log(FTableLogger.LOG_LEVELS.INFO, message); }
    warn(message) { this.log(FTableLogger.LOG_LEVELS.WARN, message); }
    error(message) { this.log(FTableLogger.LOG_LEVELS.ERROR, message); }
}

class FTableDOMHelper {
    static PROPERTY_ATTRIBUTES = new Set([
        'value', 'checked', 'selected', 'disabled', 'readOnly',
        'name', 'id', 'type', 'placeholder', 'min', 'max',
        'step', 'required', 'multiple', 'accept', 'className',
        'textContent', 'innerHTML', 'title'
    ]);

    static create(tag, options = {}) {
        const element = document.createElement(tag);
        
        // Handle special cases first
        if (options.style !== undefined) {
            element.style.cssText = options.style;
        }
        
        FTableDOMHelper.PROPERTY_ATTRIBUTES.forEach(prop => {
            if (prop in options && options[prop] !== null) {
                element[prop] = options[prop];
            }
        });

        if (options.parent !== undefined) {
            options.parent.appendChild(element);
        }

        // the attributes last, so we can override stuff if needed
        if (options.attributes) {
            Object.entries(options.attributes).forEach(([key, value]) => {
                if (value !== null) {
                    // Use property if it exists on the element, otherwise use setAttribute
                    if (FTableDOMHelper.PROPERTY_ATTRIBUTES.has(key)) {
                        element[key] = value;
                    } else {
                        element.setAttribute(key, value);
                    }
                }
            });
        }
        
        return element;
    }

    static find(selector, parent = document) {
        return parent.querySelector(selector);
    }

    static findAll(selector, parent = document) {
        return Array.from(parent.querySelectorAll(selector));
    }

    static addClass(element, className) {
        element.classList.add(...className.split(' '));
    }

    static removeClass(element, className) {
        element.classList.remove(...className.split(' '));
    }

    static toggleClass(element, className) {
        element.classList.toggle(className);
    }

    static show(element) {
        element.style.display = '';
    }

    static hide(element) {
        element.style.display = 'none';
    }

    static escapeHtml(text) {
        if (!text) return text;
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
}

class FTableHttpClient {
    static async request(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {}
        };

        const config = { ...defaults, ...options };

        // Merge headers properly
        if (options.headers) {
            config.headers = { ...defaults.headers, ...options.headers };
        }

        try {
            const response = await fetch(url, config);
            
            if (response.status === 401) {
                throw new Error('Unauthorized');
            }
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Try to parse as JSON, fallback to text
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch {
                    return { Result: 'OK', Message: text };
                }
            }
        } catch (error) {
            throw error;
        }
    }

    static async get(url, params = {}) {
        // Handle relative URLs by using the current page's base
        let fullUrl = new URL(url, window.location.href);
        
        Object.entries(params).forEach(([key, value]) => {
            if (value === null || value === undefined) {
                return; // Skip null or undefined values
            }

            if (Array.isArray(value)) {
                // Clean key: remove trailing [] if present
                const cleanKey = key.replace(/\[\]$/, '');
                const paramKey = cleanKey + '[]'; // Always use [] suffix once

                // Append each item in the array with the same key
                // This generates query strings like `key=val1&key=val2&key=val3`
                value.forEach(item => {
                    if (item !== null && item !== undefined) { // Ensure array items are also not null/undefined
                        fullUrl.searchParams.append(paramKey, item);
                    }
                });
            } else {
                // Append single values normally
                fullUrl.searchParams.append(key, value);
            }

        });
        
        return this.request(fullUrl.toString(), {
            method: 'GET',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded'}
        });
    }

    static async post(url, data = {}) {
        // Handle relative URLs
        let fullUrl = new URL(url, window.location.href);
        
        let formData = new FormData();
        Object.entries(data).forEach(([key, value]) => {
            if (value === null || value === undefined) {
                return; // Skip null or undefined values
            }
            if (Array.isArray(value)) {
                // Clean key: remove trailing [] if present
                const cleanKey = key.replace(/\[\]$/, '');
                const paramKey = cleanKey + '[]'; // Always use [] suffix once

                // Append each item in the array with the same key
                // This generates query strings like `key=val1&key=val2&key=val3`
                value.forEach(item => {
                    if (item !== null && item !== undefined) { // Ensure array items are also not null/undefined
                        formData.append(paramKey, item);
                    }
                });
            } else {
                // Append single values normally
                formData.append(key, value);
            }
        });
        
        return this.request(fullUrl.toString(), {
            method: 'POST',
            body: formData
        });
    }
}

class FTableUserPreferences {
    constructor(prefix, method = 'localStorage') {
        this.prefix = prefix;
        this.method = method;
    }

    set(key, value) {
        const fullKey = `${this.prefix}${key}`;
        
        if (this.method === 'localStorage') {
            localStorage.setItem(fullKey, value);
        } else {
            // Cookie fallback
            const expireDate = new Date();
            expireDate.setDate(expireDate.getDate() + 30);
            document.cookie = `${fullKey}=${value}; expires=${expireDate.toUTCString()}; path=/`;
        }
    }

    get(key) {
        const fullKey = `${this.prefix}${key}`;
        
        if (this.method === 'localStorage') {
            return localStorage.getItem(fullKey);
        } else {
            // Cookie fallback
            const name = fullKey + "=";
            const decodedCookie = decodeURIComponent(document.cookie);
            const ca = decodedCookie.split(';');
            for (let c of ca) {
                while (c.charAt(0) === ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) === 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return null;
        }
    }

    remove(key) {
        const fullKey = `${this.prefix}${key}`;
        
        if (this.method === 'localStorage') {
            localStorage.removeItem(fullKey);
        } else {
            document.cookie = `${fullKey}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        }
    }

    generatePrefix(tableId, fieldNames) {
        const simpleHash = (value) => {
            let hash = 0;
            if (value.length === 0) return hash;
            
            for (let i = 0; i < value.length; i++) {
                const ch = value.charCodeAt(i);
                hash = ((hash << 5) - hash) + ch;
                hash = hash & hash;
            }
            return hash;
        };

        let strToHash = tableId ? `${tableId}#` : '';
        strToHash += fieldNames.join('$') + '#c' + fieldNames.length;
        return `ftable#${simpleHash(strToHash)}`;
    }
}

class FtableModal {
    constructor(options = {}) {
        this.options = {
            title: 'Modal',
            content: '',
            buttons: [],
            className: 'ftable-modal',
            parent: document.body,
            ...options
        };
        
        this.overlay = null;
        this.modal = null;
        this.isOpen = false;
    }

    create() {
        // Create overlay
        this.overlay = FTableDOMHelper.create('div', {
            className: 'ftable-modal-overlay',
            parent: this.options.parent
        });

        // Create modal
        this.modal = FTableDOMHelper.create('div', {
            className: `ftable-modal ${this.options.className}`,
            parent: this.overlay
        });

        // Header
        const header = FTableDOMHelper.create('h2', {
            className: 'ftable-modal-header',
            textContent: this.options.title,
            parent: this.modal
        });

        // Close button
        const closeBtn = FTableDOMHelper.create('span', {
            className: 'ftable-modal-close',
            innerHTML: '&times;',
            parent: this.modal
        });

        closeBtn.addEventListener('click', () => this.close());

        // Body
        const body = FTableDOMHelper.create('div', {
            className: 'ftable-modal-body',
            parent: this.modal
        });

        if (typeof this.options.content === 'string') {
            body.innerHTML = this.options.content;
        } else {
            body.appendChild(this.options.content);
        }

        // Footer with buttons
        if (this.options.buttons.length > 0) {
            const footer = FTableDOMHelper.create('div', {
                className: 'ftable-modal-footer',
                parent: this.modal
            });

            this.options.buttons.forEach(button => {
                const btn = FTableDOMHelper.create('button', {
                    className: `ftable-dialog-button ${button.className || ''}`,
                    innerHTML: `<span>${button.text}</span>`,
                    parent: footer
                });

                if (button.onClick) {
                    // Store original handler
                    btn._originalOnClick = button.onClick;
                    // Attach wrapped handler
                    btn.addEventListener('click', this._createWrappedClickHandler(btn));
                }
            });
        }

        // Close on overlay click
        if (this.options.closeOnOverlayClick) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.close();
                }
            });
        }

        this.hide();
        return this;
    }

    show() {
        if (!this.modal) this.create();
        this.overlay.style.display = 'flex';
        this.isOpen = true;

        // Enable all ftable-dialog-button buttons
        const buttons = this.modal.querySelectorAll('.ftable-dialog-button');
        buttons.forEach(btn => {
            btn.disabled = false;
        });

        return this;
    }

    hide() {
        if (this.overlay) {
            this.overlay.style.display = 'none';
        }
        this.isOpen = false;
        return this;
    }

    close() {
        this.hide();
        if (this.options.onClose) {
            this.options.onClose();
        }
        return this;
    }

    destroy() {
        if (this.overlay) {
            this.overlay.remove();
        }
        this.isOpen = false;
        return this;
    }

    setContent(content) {
        this.options.content = content;

        const body = this.modal.querySelector('.ftable-modal-body');
        if (!body) return;

        // Clear old content
        body.innerHTML = '';
        if (typeof content === 'string') {
            body.innerHTML = content;
        } else {
            body.appendChild(content);
        }
    }

    _createWrappedClickHandler(buttonElement) {
        return async (event) => {
            // Disable immediately
            buttonElement.disabled = true;

            try {
                const handler = buttonElement._originalOnClick;
                if (typeof handler === 'function') {
                    const result = handler.call(buttonElement, event);
                    if (result instanceof Promise) {
                        await result;
                    }
                }
            } catch (error) {
                console.error('Modal button action failed:', error);
            } finally {
                // Re-enable regardless of outcome
                buttonElement.disabled = false;
            }
        };
    }
}

class FTableFormBuilder {
    constructor(options) {
        this.options = options;
        this.dependencies = new Map(); // Track field dependencies
        this.optionsCache = new FTableOptionsCache();
        this.originalFieldOptions = new Map(); // Store original field.options
        this.resolvedFieldOptions = new Map(); // Store resolved options per context

        // Initialize with empty cache objects
        Object.keys(this.options.fields || {}).forEach(fieldName => {
            this.resolvedFieldOptions.set(fieldName, {});
        });
        Object.entries(this.options.fields).forEach(([fieldName, field]) => {
            this.originalFieldOptions.set(fieldName, field.options);
        });
    }

    // Get options for specific context
    async getFieldOptions(fieldName, context = 'table', params = {}) {
        const field = this.options.fields[fieldName];
        const originalOptions = this.originalFieldOptions.get(fieldName);
        
        // If no options or already resolved for this context with same params, return cached
        if (!originalOptions) {
            return null;
        }

        // Determine if we should skip caching for this specific context
        const shouldSkipCache = this.shouldForceRefreshForContext(field, context, params);
        const cacheKey = this.generateOptionsCacheKey(context, params);
        // Skip cache if configured or forceRefresh requested
        if (!shouldSkipCache && !params.forceRefresh) {
            const cached = this.resolvedFieldOptions.get(fieldName)[cacheKey];
            if (cached) return cached;
        }

        try {
            // Create temp field with original options for resolution
            const tempField = { ...field, options: originalOptions };
            const resolved = await this.resolveOptions(tempField, {
                ...params
            }, context, shouldSkipCache);
            
            // we store the resolved option always
            this.resolvedFieldOptions.get(fieldName)[cacheKey] = resolved;
            return resolved;
        } catch (err) {
            console.error(`Failed to resolve options for ${fieldName} (${context}):`, err);
            return originalOptions;
        }
    }

    /**
     * Clear resolved options for specific field or all fields
     * @param {string|null} fieldName - Field name to clear, or null for all fields
     * @param {string|null} context - Context to clear ('table', 'create', 'edit'), or null for all contexts
     */
    clearResolvedOptions(fieldName = null, context = null) {
        if (fieldName) {
            // Clear specific field
            if (this.resolvedFieldOptions.has(fieldName)) {
                if (context) {
                    // Clear specific context for specific field
                    this.resolvedFieldOptions.get(fieldName)[context] = null;
                } else {
                    // Clear all contexts for specific field
                    this.resolvedFieldOptions.set(fieldName, { table: null, create: null, edit: null });
                }
            }
        } else {
            // Clear all fields
            if (context) {
                // Clear specific context for all fields
                this.resolvedFieldOptions.forEach((value, key) => {
                    this.resolvedFieldOptions.get(key)[context] = null;
                });
            } else {
                // Clear everything
                this.resolvedFieldOptions.forEach((value, key) => {
                    this.resolvedFieldOptions.set(key, { table: null, create: null, edit: null });
                });
            }
        }
    }

    // Helper method to determine caching behavior
    shouldForceRefreshForContext(field, context, params) {
        // Rename to reflect what it actually does now
        if (!field.noCache) return false;

        if (typeof field.noCache === 'boolean') return field.noCache;
        if (typeof field.noCache === 'function') return field.noCache({ context, ...params });
        if (typeof field.noCache === 'object') return field.noCache[context] === true;

        return false;
    }

    generateOptionsCacheKey(context, params) {
        // Create a unique key based on context and dependency values
        const keyParts = [context];

        if (params.dependedValues) {
            // Include relevant dependency values in the cache key
            Object.keys(params.dependedValues).sort().forEach(key => {
                keyParts.push(`${key}=${params.dependedValues[key]}`);
            });
        }

        return keyParts.join('|');
    }

    shouldIncludeField(field, formType) {
        if (formType === 'create') {
            return field.create !== false && !(field.key === true && field.create !== true);
        } else if (formType === 'edit') {
            return field.edit !== false;
        }
        return true;
    }

    createFieldContainer(fieldName, field, record, formType) {
        // in this function, field.options already contains the resolved values
        const container = FTableDOMHelper.create('div', {
            className: (field.type != 'hidden' ? 'ftable-input-field-container' : ''),
            attributes: {
                id: `ftable-input-field-container-div-${fieldName}`,
            }
        });

        if (field.type != 'hidden') {
            // Label
            const label = FTableDOMHelper.create('div', {
                className: 'ftable-input-label',
                textContent: field.inputTitle || field.title,
                parent: container
            });
        }

        // Input
        const inputContainer = this.createInput(fieldName, field, record[fieldName], formType);
        container.appendChild(inputContainer);

        return container;
    }

    async createForm(formType = 'create', record = {}) {

        this.currentFormRecord = record;

        const form = FTableDOMHelper.create('form', {
            className: `ftable-dialog-form ftable-${formType}-form`
        });

        // Build dependency map first
        this.buildDependencyMap();

        // Create form fields using for...of instead of forEach, this allows the await to work
        for (const [fieldName, field] of Object.entries(this.options.fields)) {
            if (this.shouldIncludeField(field, formType)) {
                let fieldWithOptions = { ...field };
                if (!field.dependsOn) {
                    const contextOptions = await this.getFieldOptions(fieldName, formType, {
                        record,
                        source: formType
                    });
                    fieldWithOptions.options = contextOptions;
                } else {
                    // For dependent fields, use placeholder or original options
                    // They will be resolved when dependencies change
                    fieldWithOptions.options = field.options;
                }

                const fieldContainer = this.createFieldContainer(fieldName, fieldWithOptions, record, formType);
                form.appendChild(fieldContainer);
            }
        }

        // Set up dependency listeners after all fields are created
        this.setupDependencyListeners(form);

        return form;
    }

    shouldResolveOptions(options) {
        return options &&
               (typeof options === 'function' || typeof options === 'string') &&
               !Array.isArray(options) &&
               !(typeof options === 'object' && !Array.isArray(options) && Object.keys(options).length > 0);
    }

    buildDependencyMap() {
        this.dependencies.clear();

        Object.entries(this.options.fields).forEach(([fieldName, field]) => {
            if (field.dependsOn) {
                // Normalize dependsOn to array
                let dependsOnFields;
                if (typeof field.dependsOn === 'string') {
                    // Handle CSV: 'field1, field2' → ['field1', 'field2']
                    dependsOnFields = field.dependsOn
                        .split(',')
                        .map(name => name.trim())
                        .filter(name => name);
                } else {
                    return; // Invalid type
                }

                // Register this field as dependent on each master
                dependsOnFields.forEach(dependsOnField => {
                    if (!this.dependencies.has(dependsOnField)) {
                        this.dependencies.set(dependsOnField, []);
                    }
                    this.dependencies.get(dependsOnField).push(fieldName);
                });
            }
        });
    }

    setupDependencyListeners(form) {
        // Collect all master fields (any field that is depended on)
        const masterFieldNames = Array.from(this.dependencies.keys());

        masterFieldNames.forEach(masterFieldName => {
            const masterInput = form.querySelector(`[name="${masterFieldName}"]`);
            if (!masterInput) return;

            // Listen for changes
            masterInput.addEventListener('change', () => {
                // Re-evaluate dependent fields (they’ll check their own dependsOn)
                this.handleDependencyChange(form, masterFieldName);
            });
        });

        // Trigger initial update
        this.handleDependencyChange(form);
    }

    async resolveOptions(field, params = {}, source = '', noCache = false) {
        if (!field.options) return [];

        // Case 1: Direct options (array or object)
        if (Array.isArray(field.options) || typeof field.options === 'object') {
            return field.options;
        }

        let result;

        // Enhance params with clearCache() method
        const enhancedParams = {
            ...params,
            source: source,
            clearCache: () => {
                noCache = true;
                // Also update the field's noCache setting for future calls
                this.updateFieldCacheSetting(field, source, true);
            }
        };

        if (typeof field.options === 'function') {
            result = await field.options(enhancedParams);
            //result = await field.options(params); // Can return string or { url, noCache }
        } else if (typeof field.options === 'string') {
            result = field.options;
        } else {
            return [];
        }

        // --- Handle result ---
        const isObjectResult = result && typeof result === 'object' && result.url;
        const url = isObjectResult ? result.url : result;
        noCache = isObjectResult && result.noCache !== undefined ? result.noCache : noCache;

        if (typeof url !== 'string') return [];

        // Only use cache if noCache is NOT set
        if (noCache) {
            try {
                const response = this.options.forcePost
                    ? await FTableHttpClient.post(url)
                    : await FTableHttpClient.get(url);
                return response.Options || response.options || response || [];
            } catch (error) {
                console.error(`Failed to load options from ${url}:`, error);
                return [];
            }
        } else {
            // Use getOrCreate to prevent duplicate requests
            return await this.optionsCache.getOrCreate(url, {}, async () => {
                try {
                    const response = this.options.forcePost
                        ? await FTableHttpClient.post(url)
                        : await FTableHttpClient.get(url);
                    return response.Options || response.options || response || [];
                } catch (error) {
                    console.error(`Failed to load options from ${url}:`, error);
                    return [];
                }
            });
        }

    }

    updateFieldCacheSetting(field, context, skipCache) {
        if (!field.noCache) {
            // Initialize noCache as object for this context
            field.noCache = { [context]: skipCache };
        } else if (typeof field.noCache === 'boolean') {
            // Convert boolean to object, preserving existing behavior for other contexts
            field.noCache = {
                'table': field.noCache,
                'create': field.noCache,
                'edit': field.noCache,
                [context]: skipCache // Override for this context
            };
        } else if (typeof field.noCache === 'object') {
            // Update specific context
            field.noCache[context] = skipCache;
        }
        // Function-based noCache remains unchanged (runtime decision)
    }

    clearOptionsCache(url = null, params = null) {
        this.optionsCache.clear(url, params);
    }

    getFormValues(form) {
        const values = {};

        // Get all form elements
        const elements = form.elements;

        for (let i = 0; i < elements.length; i++) {
            const element = elements[i];
            const name = element.name;

            if (!name || element.disabled) continue;

            switch (element.type) {
                case 'checkbox':
                    values[name] = element.checked ? element.value || '1' : '0';
                    break;

                case 'radio':
                    if (element.checked) {
                        values[name] = element.value;
                    }
                    break;

                case 'select-multiple':
                    values[name] = Array.from(element.selectedOptions).map(option => option.value);
                    break;

                default:
                    values[name] = element.value;
                    break;
            }
        }

        return values;
    }

    async handleDependencyChange(form, changedFieldname = '') {
        // Build dependedValues: { field1: value1, field2: value2 }
        const dependedValues = this.getFormValues(form);
        const formType = form.classList.contains('ftable-create-form') ? 'create' : 'edit';
        const record = this.currentFormRecord || {};

        const baseParams = {
            record,
            source: formType,
            form,
            dependedValues
        };

        for (const [fieldName, field] of Object.entries(this.options.fields)) {
            if (!field.dependsOn) continue;
            
            if (changedFieldname !== '') {
                let dependsOnFields = field.dependsOn
                    .split(',')
                    .map(name => name.trim())
                    .filter(name => name);
                if (!dependsOnFields.includes(changedFieldname)) {
                    continue;
                }
            }

            const input = form.querySelector(`[name="${fieldName}"]`);
            if (!input || !this.shouldIncludeField(field, formType)) continue;

            try {
                // Clear current options
                if (input.tagName === 'SELECT') {
                    input.innerHTML = '<option value="">Loading...</option>';
                } else if (input.tagName === 'INPUT' && input.list) {
                    const datalist = document.getElementById(input.list.id);
                    if (datalist) datalist.innerHTML = '';
                }

                // Get current field value BEFORE resolving new options
                const currentValue = input.value || record[fieldName] || '';

                // Resolve options with current context
                const params = {
                    ...baseParams,
                    dependsOnField: field.dependsOn,
                    dependsOnValue: dependedValues[field.dependsOn]
                };

                const newOptions = await this.getFieldOptions(fieldName, formType, params);

                // Populate the input
                if (input.tagName === 'SELECT') {
                    this.populateSelectOptions(input, newOptions, currentValue);
                } else if (input.tagName === 'INPUT' && input.list) {
                    this.populateDatalistOptions(input.list, newOptions);
                    // For datalist, set the value directly
                    if (currentValue) input.value = currentValue;
                }

                setTimeout(() => {
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }, 0);

            } catch (error) {
                console.error(`Error loading options for ${fieldName}:`, error);
                if (input.tagName === 'SELECT') {
                    input.innerHTML = '<option value="">Error</option>';
                }
            }
        }
    }

    parseInputAttributes(inputAttributes) {
        if (typeof inputAttributes === 'string') {
            const parsed = {};
            const regex = /(\w+)(?:=("[^"]*"|'[^']*'|\S+))?/g;
            let match;
            while ((match = regex.exec(inputAttributes)) !== null) {
                const key = match[1];
                const value = match[2] ? match[2].replace(/^["']|["']$/g, '') : '';
                    parsed[key] = value === '' ? 'true' : value;
                }
            return parsed;
        }
        return inputAttributes || {};
    }

    createInput(fieldName, field, value, formType) {
        const container = FTableDOMHelper.create('div', {
            className: `ftable-input ftable-${field.type || 'text'}-input`
        });

        let input;

        if (value === undefined) {
            value = null;
        }
        if (value === null && field.defaultValue ) {
            value = field.defaultValue;
        }
        // Auto-detect select type if options are provided
        if (!field.type && field.options) {
            field.type = 'select';
        }

        // Create the input based on type
        switch (field.type) {
            case 'hidden':
                input = this.createHiddenInput(fieldName, field, value);
                break;
            case 'textarea':
                input = this.createTextarea(fieldName, field, value);
                break;
            case 'select':
                input = this.createSelect(fieldName, field, value);
                break;
            case 'checkbox':
                input = this.createCheckbox(fieldName, field, value);
                break;
            case 'radio':
                input = this.createRadioGroup(fieldName, field, value);
                break;
            case 'datalist':
                input = this.createDatalistInput(fieldName, field, value);
                break;
            case 'file':
                input = this.createFileInput(fieldName, field, value);
                break;
            case 'date':
            case 'datetime':
            case 'datetime-local':
                input = this.createDateInput(fieldName, field, value);
                break;
            default:
                input = this.createTypedInput(fieldName, field, value);
        }

        // Allow field.input function to customize or replace the input
        if (typeof field.input === 'function') {
            const data = {
                field: field,
                record: this.currentFormRecord,
                inputField: input,
                formType: formType
            };

            const result = field.input(data);

            // If result is a string, set as innerHTML
            if (typeof result === 'string') {
                container.innerHTML = result;
            }
            // If result is a DOM node, append it
            else if (result instanceof Node) {
                container.appendChild(result);
            }
            // Otherwise, fallback to default
            else {
                container.appendChild(input);
                if (input.datalistElement && input.datalistElement instanceof Node) {
                    container.appendChild(input.datalistElement);
                }
            }
        } else {
            // No custom input function — just add the default input
            container.appendChild(input);
            if (input.datalistElement && input.datalistElement instanceof Node) {
                container.appendChild(input.datalistElement);
            }
        }

        // Add explanation if provided
        if (field.explain) {
            const explain = FTableDOMHelper.create('div', {
                className: 'ftable-field-explain',
                innerHTML: `<small>${field.explain}</small>`,
                parent: container
            });
        }

        return container;
    }

    createDateInput(fieldName, field, value) {
        // Check if FDatepicker is available
        if (typeof FDatepicker !== 'undefined') {
            const dateFormat = field.dateFormat || this.options.defaultDateFormat;

            const container = document.createElement('div');
            // Create hidden input
            const hiddenInput = FTableDOMHelper.create('input', {
                id: 'real-' + fieldName,
                type: 'hidden',
                value: value,
                name: fieldName
            });

            // Create visible input
            const attributes = {
                'data-date': value
            };
            // Set any additional attributes
            if (field.inputAttributes) {
                const parsed = this.parseInputAttributes(field.inputAttributes);
                Object.assign(attributes, parsed);
            }

            const visibleInput = FTableDOMHelper.create('input', { 
                attributes: attributes,
                id: `Edit-${fieldName}`,
                type: 'text',
                placeholder: field.placeholder || null,
                className: field.inputClass || 'datepicker-input',
                readOnly: true
            });

            // Append both inputs
            container.appendChild(hiddenInput);
            container.appendChild(visibleInput);

            // Apply FDatepicker
            // Initialize FDatepicker AFTER the container is in the DOM
            // We'll use a small timeout to ensure DOM attachment
            switch (field.type) {
                case 'date':
                    setTimeout(() => { 
                        const picker = new FDatepicker(visibleInput, {
                            format: dateFormat,
                            altField: 'real-' + fieldName,
                            altFormat: 'Y-m-d'
                        });
                    }, 0);
                    break;
                case 'datetime':
                case 'datetime-local':
                    setTimeout(() => { 
                        const picker = new FDatepicker(visibleInput, {
                            format: dateFormat,
                            timepicker: true,
                            altField: 'real-' + fieldName,
                            altFormat: 'Y-m-d H:i:00'
                        });
                    }, 0);
                    break;
            }
            return container;
        } else {
            return createTypedInput(fieldName, field, value);
        }
    }

    createTypedInput(fieldName, field, value) {
        const inputType = field.type || 'text';
        const attributes = { };

        // extra check for name and multiple
        let name = fieldName;
        // Apply inputAttributes from field definition
        if (field.inputAttributes) {
            let hasMultiple = false;

            const parsed = this.parseInputAttributes(field.inputAttributes);
            Object.assign(attributes, parsed);

            hasMultiple = parsed.multiple !== undefined && parsed.multiple !== false;

            if (hasMultiple) {
                name = `${fieldName}[]`;
            }
        }

        const input = FTableDOMHelper.create('input', { 
            attributes: attributes,
            type: inputType,
            id: `Edit-${fieldName}`,
            className: field.inputClass || null,
            placeholder: field.placeholder || null,
            value: value,
            name: name
        });

        // Prevent form submit on Enter, trigger change instead
        input.addEventListener('keypress', (e) => {
            const keyPressed = e.keyCode || e.which;
            if (keyPressed === 13) { // Enter key
                e.preventDefault();
                input.dispatchEvent(new Event('change', { bubbles: true }));
                return false;
            }
        });

        return input;
    }

    createDatalistInput(fieldName, field, value) {
        const attributes = {
            list: `${fieldName}-datalist`
        };

        // Apply inputAttributes
        if (field.inputAttributes) {
            const parsed = this.parseInputAttributes(field.inputAttributes);
            Object.assign(attributes, parsed);
        }

        const input = FTableDOMHelper.create('input', {
            attributes: attributes,
            type: 'search',
            name: fieldName,
            id: `Edit-${fieldName}`,
            className: field.inputClass || null,
            placeholder: field.placeholder || null,
            value: value
        });

        // Create the datalist element
        const datalist = FTableDOMHelper.create('datalist', {
            id: `${fieldName}-datalist`
        });

        // Populate datalist options
        if (field.options) {
            this.populateDatalistOptions(datalist, field.options);
        }

        // Store reference
        input.datalistElement = datalist;

        return input;
    }

    populateDatalistOptions(datalist, options) {
        datalist.innerHTML = ''; // Clear existing options

        if (Array.isArray(options)) {
            options.forEach(option => {
                FTableDOMHelper.create('option', {
                    value: option.Value || option.value || option,
                    textContent: option.DisplayText || option.text || option,
                    parent: datalist
                });
            });
        } else if (typeof options === 'object') {
            Object.entries(options).forEach(([key, text]) => {
                FTableDOMHelper.create('option', {
                    value: key,
                    textContent: text,
                    parent: datalist
                });
            });
        }
    }

    createHiddenInput(fieldName, field, value) {
        const attributes = { };

        // Apply inputAttributes
        if (field.inputAttributes) {
            const parsed = this.parseInputAttributes(field.inputAttributes);
            Object.assign(attributes, parsed);
        }

        return FTableDOMHelper.create('input', {
            attributes: attributes,
            type: 'hidden',
            name: fieldName,
            id: `Edit-${fieldName}`,
            value: value
        });
    }

    createTextarea(fieldName, field, value) {
        const attributes = { };

        // Apply inputAttributes
        if (field.inputAttributes) {
            const parsed = this.parseInputAttributes(field.inputAttributes);
            Object.assign(attributes, parsed);
        }

        return FTableDOMHelper.create('textarea', {
            attributes: attributes,
            name: fieldName,
            id: `Edit-${fieldName}`,
            className: field.inputClass || null,
            placeholder: field.placeholder || null,
            value: value
        });
    }

    createSelect(fieldName, field, value) {
        const attributes = { };

        // extra check for name and multiple
        let name = fieldName;
        let hasMultiple = false;
        
        // Apply inputAttributes from field definition
        if (field.inputAttributes) {
            const parsed = this.parseInputAttributes(field.inputAttributes);
            Object.assign(attributes, parsed);

            hasMultiple = parsed.multiple !== undefined && parsed.multiple !== false;

            if (hasMultiple) {
                name = `${fieldName}[]`;
            }
        }
        
        // If multiple select, create custom UI
        if (hasMultiple) {
            return this.createCustomMultiSelect(fieldName, field, value, attributes, name);
        }
        
        // Standard single select
        attributes.name = name;

        const select = FTableDOMHelper.create('select', {
            attributes: attributes,
            name: fieldName,
            id: `Edit-${fieldName}`,
            className: field.inputClass || null
        });

        if (field.options) {
            this.populateSelectOptions(select, field.options, value);
        }

        return select;
    }

    createCustomMultiSelect(fieldName, field, value, attributes, name) {
        // Create container
        const container = FTableDOMHelper.create('div', {
            className: 'ftable-multiselect-container',
            attributes: { 'data-field-name': fieldName }
        });

        // Create hidden input to store selected values
        const hiddenInput = FTableDOMHelper.create('input', {
            type: 'hidden',
            name: name,
            id: `Edit-${fieldName}`,
            value: Array.isArray(value) ? value.join(',') : value || ''
        });
        container.appendChild(hiddenInput);

        // Create display area
        const display = FTableDOMHelper.create('div', {
            className: 'ftable-multiselect-display',
            parent: container
        });

        const selectedDisplay = FTableDOMHelper.create('div', {
            className: 'ftable-multiselect-selected',
            parent: display
        });

        const placeholderText = field.placeholder || this.options.messages.multiSelectPlaceholder || 'Click to select options...';
        const placeholder = FTableDOMHelper.create('span', {
            className: 'ftable-multiselect-placeholder',
            textContent: placeholderText,
            parent: selectedDisplay
        });

        // Create dropdown toggle button
        const toggleBtn = FTableDOMHelper.create('button', {
            type: 'button',
            className: 'ftable-multiselect-toggle',
            innerHTML: '▼',
            parent: display
        });

        // Create options dropdown
        const dropdown = FTableDOMHelper.create('div', {
            className: 'ftable-multiselect-dropdown',
            parent: container,
            style: 'display: none;'
        });

        // Store selected values and checkbox references
        const selectedValues = new Set(
            Array.isArray(value) ? value : 
            value ? value.toString().split(',').filter(v => v) : []
        );
        const checkboxMap = new Map(); // Map of value -> checkbox element

        // Function to update display
        const updateDisplay = () => {
            selectedDisplay.innerHTML = '';
            
            if (selectedValues.size === 0) {
                placeholder.textContent = placeholderText;
                selectedDisplay.appendChild(placeholder);
            } else {
                const selectedArray = Array.from(selectedValues);
                const optionsMap = new Map();
                
                // Build options map
                if (field.options) {
                    const options = Array.isArray(field.options) ? field.options : 
                        Object.entries(field.options).map(([k, v]) => ({Value: k, DisplayText: v}));
                    
                    options.forEach(opt => {
                        const val = opt.Value !== undefined ? opt.Value : 
                            opt.value !== undefined ? opt.value : opt;
                        const text = opt.DisplayText || opt.text || opt;
                        optionsMap.set(val.toString(), text);
                    });
                }
                
                selectedArray.forEach(val => {
                    const tag = FTableDOMHelper.create('span', {
                        className: 'ftable-multiselect-tag',
                        parent: selectedDisplay
                    });
                    
                    FTableDOMHelper.create('span', {
                        className: 'ftable-multiselect-tag-text',
                        textContent: optionsMap.get(val.toString()) || val,
                        parent: tag
                    });
                    
                    const removeBtn = FTableDOMHelper.create('span', {
                        className: 'ftable-multiselect-tag-remove',
                        innerHTML: '×',
                        parent: tag
                    });
                    
                    removeBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        selectedValues.delete(val);
                        // Update the checkbox state
                        const checkbox = checkboxMap.get(val.toString());
                        if (checkbox) {
                            checkbox.checked = false;
                        }
                        updateDisplay();
                        hiddenInput.value = Array.from(selectedValues).join(',');
                    });
                });
            }
            
            hiddenInput.value = Array.from(selectedValues).join(',');
        };

        // Populate options
        const populateOptions = () => {
            if (!field.options) return;
            
            const options = Array.isArray(field.options) ? field.options : 
                Object.entries(field.options).map(([k, v]) => ({Value: k, DisplayText: v}));
            
            options.forEach(option => {
                const optValue = option.Value !== undefined ? option.Value : 
                    option.value !== undefined ? option.value : option;
  
                // Skip if value is empty
                if (optValue == null || optValue === '') {
                    return; // This continues to the next iteration
                }

                const optText = option.DisplayText || option.text || option;

                const optionDiv = FTableDOMHelper.create('div', {
                    className: 'ftable-multiselect-option',
                    parent: dropdown
                });
                
                const checkbox = FTableDOMHelper.create('input', {
                    type: 'checkbox',
                    className: 'ftable-multiselect-checkbox',
                    checked: selectedValues.has(optValue.toString()),
                    parent: optionDiv
                });
                
                // Store checkbox reference
                checkboxMap.set(optValue.toString(), checkbox);
                
                const label = FTableDOMHelper.create('label', {
                    className: 'ftable-multiselect-label',
                    textContent: optText,
                    parent: optionDiv
                });
                
                // Click anywhere on the option to toggle
                optionDiv.addEventListener('click', (e) => {
                    e.stopPropagation();
                    
                    if (selectedValues.has(optValue.toString())) {
                        selectedValues.delete(optValue.toString());
                        checkbox.checked = false;
                    } else {
                        selectedValues.add(optValue.toString());
                        checkbox.checked = true;
                    }
                    
                    updateDisplay();
                });
            });
        };

        // Toggle dropdown
        const toggleDropdown = (e) => {
            if (e) e.stopPropagation();
            const isVisible = dropdown.style.display !== 'none';
            dropdown.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                // Close other dropdowns
                document.querySelectorAll('.ftable-multiselect-dropdown').forEach(dd => {
                    if (dd !== dropdown) dd.style.display = 'none';
                });
            }
        };

        display.addEventListener('click', toggleDropdown);
        toggleBtn.addEventListener('click', toggleDropdown);

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Initialize
        populateOptions();
        updateDisplay();

        return container;
    }

    createRadioGroup(fieldName, field, value) {
        const wrapper = FTableDOMHelper.create('div', {
            className: 'ftable-radio-group'
        });

        if (field.options) {
            const options = Array.isArray(field.options) ? field.options :
                typeof field.options === 'object' ? Object.entries(field.options).map(([k, v]) => ({Value: k, DisplayText: v})) : [];

            options.forEach((option, index) => {
                const radioWrapper = FTableDOMHelper.create('div', {
                    className: 'ftable-radio-wrapper',
                    parent: wrapper
                });

                const radioId = `${fieldName}_${index}`;
                const radioAttributes = { };

                // Apply inputAttributes
                if (field.inputAttributes) {
                    const parsed = this.parseInputAttributes(field.inputAttributes);
                    Object.assign(radioAttributes, parsed);
                }

                const fieldValue = option.Value !== undefined ? option.Value :
                    option.value !== undefined ? option.value :
                    option; // fallback for string

                const radio = FTableDOMHelper.create('input', {
                    attributes: radioAttributes,
                    type: 'radio',
                    name: fieldName,
                    id: radioId,
                    value: fieldValue,
                    className: field.inputClass || null,
                    checked: fieldValue == value,
                    parent: radioWrapper
                });

                const label = FTableDOMHelper.create('label', {
                    attributes: { for: radioId },
                    textContent: option.DisplayText || option.text || option,
                    parent: radioWrapper
                });
            });
        }

        return wrapper;
    }

    createCheckbox(fieldName, field, value) {
        const wrapper = FTableDOMHelper.create('div', {
            className: 'ftable-yesno-check-wrapper'
        });

        const isChecked = [1, '1', true, 'true'].includes(value);

        // Determine "Yes" and "No" labels
        let dataNo = this.options.messages.no;
        let dataYes = this.options.messages.yes;

        if (field.values && typeof field.values === 'object') {
            if (field.values['0'] !== undefined) dataNo = field.values['0'];
            if (field.values['1'] !== undefined) dataYes = field.values['1'];
        }

        // Create the checkbox
        const checkbox = FTableDOMHelper.create('input', {
            className: ['ftable-yesno-check-input', field.inputClass || ''].filter(Boolean).join(' '),
            type: 'checkbox',
            name: fieldName,
            id: `Edit-${fieldName}`,
            value: '1',
            parent: wrapper
        });
        checkbox.checked = isChecked;

        if (field.label) {
            // Optional: Add a static form label (e.g., "Is Active?")
            const label = FTableDOMHelper.create('label', {
                className: 'ftable-yesno-check-fixedlabel',
                attributes: {
                    for: `Edit-${fieldName}`,
                },
                textContent: field.label,
                parent: wrapper
            });
        } else {
            // Create the label with data attributes
            const label = FTableDOMHelper.create('label', {
                className: 'ftable-yesno-check-text',
                attributes: {
                    for: `Edit-${fieldName}`,
                    'data-yes': dataYes,
                    'data-no': dataNo
                },
                parent: wrapper
            });
        }
        return wrapper;
    }

    populateSelectOptions(select, options, selectedValue) {
        select.innerHTML = ''; // Clear existing options

        if (Array.isArray(options)) {
            options.forEach(option => {
                const value = option.Value !== undefined ? option.Value :
                    option.value !== undefined ? option.value :
                    option; // fallback for string
                const optionElement = FTableDOMHelper.create('option', {
                    value: value,
                    textContent: option.DisplayText || option.text || option,
                    selected: value == selectedValue,
                    parent: select
                });

                if (option.Data && typeof option.Data === 'object') {
                    Object.entries(option.Data).forEach(([key, dataValue]) => {
                        optionElement.setAttribute(`data-${key}`, dataValue);
                    });
                }

            });
        } else if (typeof options === 'object') {
            Object.entries(options).forEach(([key, text]) => {
                const optionElement = FTableDOMHelper.create('option', {
                    value: key,
                    textContent: text,
                    selected: key == selectedValue,
                    parent: select
                });
            });
        }
    }

    createFileInput(fieldName, field, value) {
        const attributes = { };
 
        // extra check for name and multiple
        let name = fieldName;
        // Apply inputAttributes from field definition
        if (field.inputAttributes) {
            let hasMultiple = false;

            const parsed = this.parseInputAttributes(field.inputAttributes);
            Object.assign(attributes, parsed);
            hasMultiple = parsed.multiple !== undefined && parsed.multiple !== false;

            if (hasMultiple) {
                name = `${fieldName}[]`;
            }
        }

        return FTableDOMHelper.create('input', {
            type: 'file',
            id: `Edit-${fieldName}`,
            name: name,
            className: field.inputClass || null,
            attributes: attributes
        });
    }
}

// Enhanced FTable class with search functionality
class FTable extends FTableEventEmitter {
    constructor(element, options = {}) {
        super();
        
        this.element = typeof element === 'string' ? 
            document.querySelector(element) : element;

        if (!this.element) {
            return;
        }

        // Prevent double initialization
        if (this.element.ftableInstance) {
            //console.warn('FTable is already initialized on this element. Using that.');
            return this.element.ftableInstance;
        }
        
        this.options = this.mergeOptions(options);
        this.verifyOptions();
        this.logger = new FTableLogger(this.options.logLevel);
        this.userPrefs = new FTableUserPreferences('', this.options.saveUserPreferencesMethod);
        this.formBuilder = new FTableFormBuilder(this.options, this);
        
        this.state = {
            records: [],
            totalRecordCount: 0,
            currentPage: 1,
            isLoading: false,
            selectedRecords: new Set(),
            sorting: [],
            searchQueries: {}, // Stores current search terms per field
        };

        this.elements = {};
        this.modals = {};
        this.searchTimeout = null; // For debouncing
        this.lastSortEvent = null;
        this._recalculatedOnce = false;
        this.shiftKeyDown = false; // for shift-select
        this.lastSelectedRow = null; // for shift-select

        // store it on the DOM too, so people can access it
        this.element.ftableInstance = this;
        
        this.init();
    }

    mergeOptions(options) {
        const defaults = {
            tableId: undefined,
            logLevel: FTableLogger.LOG_LEVELS.WARN,
            actions: {},
            fields: {},
            forcePost: true,
            closeOnOverlayClick: true,
            animationsEnabled: true,
            loadingAnimationDelay: 1000,
            defaultDateLocale: '',
            defaultDateFormat: 'Y-m-d',
            saveUserPreferences: true,
            saveUserPreferencesMethod: 'localStorage',
            defaultSorting: '',
            tableReset: false,
            
            // Paging
            paging: false,
            pageList: 'normal',
            pageSize: 10,
            pageSizes: [10, 25, 50, 100, 250, 500],
            gotoPageArea: 'combobox',
            
            // Sorting
            sorting: false,
            multiSorting: false,
            multiSortingCtrlKey: true,
            
            // Selection
            selecting: false,
            multiselect: false,

            // child tables
            openChildAsAccordion: false,

            // Toolbar search
            toolbarsearch: false, // Enable/disable toolbar search row
            toolbarreset: true,   // Show reset button
            searchDebounceMs: 300, // Debounce time for search input
            
            // Caching
            listCache: 30000, // or listCache: 30000 (duration in ms)

            // Messages
            messages: { ...FTABLE_DEFAULT_MESSAGES } // Safe copy
        };

        return this.deepMerge(defaults, options);
    }

    deepMerge(target, source) {
        const result = { ...target };
        
        for (const key in source) {
            if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                result[key] = this.deepMerge(result[key] || {}, source[key]);
            } else {
                result[key] = source[key];
            }
        }
        
        return result;
    }

    verifyOptions() {
        if (this.options.pageSize && !this.options.pageSizes.includes(this.options.pageSize)) {
            this.options.pageSize = this.options.pageSizes[0];
        }
    }

    // Public
    static setMessages(customMessages) {
        Object.assign(FTABLE_DEFAULT_MESSAGES, customMessages);
    }

    init() {
        this.processFieldDefinitions();
        this.createMainStructure();
        this.setupFTableUserPreferences();

        this.createTable();
        this.createModals();
        
        // Create paging UI if enabled
        if (this.options.paging) {
            this.createPagingUI();
        }

        // Start resolving in background
        this.resolveAsyncFieldOptions().then(() => {
            // re-render dynamic options rows — no server call
            // this is needed so that once options are resolved, the table shows correct display values
            // why: load() can actually finish faster than option resolving (and calling refreshDisplayValues
            //      there is then pointless, since the resolving hasn't finished yet),
            //      so we need to do it when the options are actually resolved (here)
            //  We could call await this.resolveAsyncFieldOptions() during load, but that would slow down the loading ...
            setTimeout(() => {
                this.refreshDisplayValues();
            }, 0);
        }).catch(console.error);
        
        this.bindEvents();
        
        this.updateSortingHeaders();
        this.renderSortingInfo();

        // Add essential CSS if not already present
        //this.addEssentialCSS();

        // now make sure all tables have a % width
        this.initColumnWidths();
    }

    initColumnWidths() {
        const visibleFields = this.columnList.filter(fieldName => {
            const field = this.options.fields[fieldName];
            return field.visibility !== 'hidden' && field.visibility !== 'separator';
        });

        const count = visibleFields.length;
        visibleFields.forEach(fieldName => {
            const field = this.options.fields[fieldName];
            // Use configured width or equal distribution
            //field.width = field.width || `${(100 / count).toFixed(2)}%`;
            field.width = field.width || `${(100 / count)}%`;
        });
    }

    normalizeColumnWidths() {
        const container = this.elements.mainContainer;
        const visibleHeaders = this.columnList
            .map(fieldName => ({
                th: this.elements.table.querySelector(`[data-field-name="${fieldName}"]`),
                field: this.options.fields[fieldName]
            }))
            .filter(item => item.th && item.field.visibility !== 'hidden' && item.field.visibility !== 'separator');

        if (visibleHeaders.length === 0) return;

        const totalContainerWidth = container.offsetWidth;
        let totalPercent = 0;

        visibleHeaders.forEach(item => {
            const widthPct = (item.th.offsetWidth / totalContainerWidth) * 100;
            //item.field.width = `${widthPct.toFixed(2)}%`;
            item.field.width = `${widthPct}%`;
            item.th.style.width = item.field.width;
            totalPercent += widthPct;
        });

        // Optional: adjust for rounding drift
        // (not critical, but can help)
    }

    parseDefaultSorting(sortStr) {
        const result = [];
        if (!sortStr || typeof sortStr !== 'string') return result;

        sortStr.split(',').forEach(part => {
            const trimmed = part.trim();
            if (!trimmed) return;

            const descIndex = trimmed.toUpperCase().indexOf(' DESC');
            const ascIndex = trimmed.toUpperCase().indexOf(' ASC');

            let direction = 'ASC';
            let fieldName = trimmed;

            if (descIndex > 0) {
                fieldName = trimmed.slice(0, descIndex).trim();
                direction = 'DESC';
            } else if (ascIndex > 0) {
                fieldName = trimmed.slice(0, ascIndex).trim();
                direction = 'ASC';
            } else {
                fieldName = trimmed.trim();
                direction = 'ASC';
            }

            const field = this.options.fields[fieldName];
            if (field && field.sorting !== false) {
                result.push({ fieldName, direction });
            }
        });

        return result;
    }

    addEssentialCSS() {
        // Check if our CSS is already added
        if (document.querySelector('#ftable-essential-css')) return;
        
        const css = `
            .ftable-row-animation {
                transition: background-color 0.3s ease;
            }

            .ftable-row-added {
                background-color: #d4edda !important;
            }

            .ftable-row-edited {
                background-color: #d1ecf1 !important;
            }

            .ftable-row-deleted {
                opacity: 0;
                transform: translateY(-10px);
                transition: opacity 0.3s ease, transform 0.3s ease;
            }

            .ftable-toolbarsearch {
                width: 90%;
            }
        `;

        const style = document.createElement('style');
        style.id = 'ftable-essential-css';
        style.textContent = css;
        document.head.appendChild(style);
    }

    createPagingUI() {
        this.elements.bottomPanel = FTableDOMHelper.create('div', {
            className: 'ftable-bottom-panel',
            parent: this.elements.mainContainer
        });

        this.elements.leftArea = FTableDOMHelper.create('div', {
            className: 'ftable-left-area',
            parent: this.elements.bottomPanel
        });

        this.elements.rightArea = FTableDOMHelper.create('div', {
            className: 'ftable-right-area', 
            parent: this.elements.bottomPanel
        });

        // Page list area
        this.elements.pagingListArea = FTableDOMHelper.create('div', {
            className: 'ftable-page-list',
            parent: this.elements.leftArea
        });

        // Page Goto area
        this.elements.pagingGotoArea = FTableDOMHelper.create('div', {
            className: 'ftable-page-goto',
            parent: this.elements.leftArea
        });

        // Page info area
        this.elements.pageInfoSpan = FTableDOMHelper.create('div', {
            className: 'ftable-page-info',
            parent: this.elements.rightArea
        });

        // Page size selector if enabled
        if (this.options.pageSizeChangeArea !== false) {
            this.createPageSizeSelector();
        }

    }

    createPageSizeSelector() {
        const container = FTableDOMHelper.create('span', {
            className: 'ftable-page-size-change',
            parent: this.elements.leftArea
        });

        FTableDOMHelper.create('span', {
            textContent: this.options.messages.pageSizeChangeLabel,
            parent: container
        });

        const select = FTableDOMHelper.create('select', {
            className: 'ftable-page-size-select',
            parent: container
        });

        const pageSizes = this.options.pageSizes || [10, 25, 50, 100, 250, 500];
        pageSizes.forEach(size => {
            const option = FTableDOMHelper.create('option', {
                attributes: { value: size },
                textContent: size.toString(),
                parent: select
            });

            if (size === this.state.pageSize) {
                option.selected = true;
            }
        });

        select.addEventListener('change', (e) => {
            this.changePageSize(parseInt(e.target.value));
        });
    }

    processFieldDefinitions() {
        this.fieldList = Object.keys(this.options.fields);

        // Set default values for each field
        this.fieldList.forEach(fieldName => {
            const field = this.options.fields[fieldName];
            const isKeyField = field.key === true;

            if (isKeyField) {
                if (field.create === undefined || !field.create) {
                    field.create = true;
                    field.type = 'hidden';
                }
                if (field.edit === undefined || !field.edit) {
                    field.edit = true;
                    field.type = 'hidden';
                }
                field.visibility = field.visibility ?? 'visible';
            } else {
                field.create = field.create ?? true;
                field.edit = field.edit ?? true;
                field.list = field.list ?? true;
                field.sorting = field.sorting ?? true;
                field.visibility = field.visibility ?? 'visible';
            }
        });

        // Build column list (visible, listable, non-hidden) fields
        this.columnList = this.fieldList.filter(name => {
            const field = this.options.fields[name];
            return field.list !== false;
        });

        // Find key field
        this.keyField = this.fieldList.find(name => this.options.fields[name].key === true);
        if (!this.keyField) {
            this.logger.info('No key field defined');
        }
    }

    async resolveAsyncFieldOptions() {
        const promises = this.columnList.map(async (fieldName) => {
            const field = this.options.fields[fieldName];
            const originalOptions = this.formBuilder.originalFieldOptions.get(fieldName);

            if (this.formBuilder.shouldResolveOptions(originalOptions)) {
                try {
                    // Check if already resolved to avoid duplicate work
                    const cacheKey = this.formBuilder.generateOptionsCacheKey('table', {});
                    if (!this.formBuilder.resolvedFieldOptions.get(fieldName)?.[cacheKey]) {
                        await this.formBuilder.getFieldOptions(fieldName, 'table');
                    }
                } catch (err) {
                    console.error(`Failed to resolve table options for ${fieldName}:`, err);
                }
            }
        });

        await Promise.all(promises);
    }

    async refreshDisplayValues() {
        const rows = this.elements.tableBody.querySelectorAll('.ftable-data-row');
        if (rows.length === 0) return;

        for (const row of rows) {
            for (const fieldName of this.columnList) {
                const field = this.options.fields[fieldName];
                if (!field.options) continue;

                const cell = row.querySelector(`td[data-field-name="${fieldName}"]`);
                if (!cell) continue;

                // Get table-specific options
                const cacheKey = this.formBuilder.generateOptionsCacheKey('table', {});
                const resolvedOptions = this.formBuilder.resolvedFieldOptions.get(fieldName)?.[cacheKey];
                const value = this.getDisplayText(row.recordData, fieldName, resolvedOptions);
                cell.innerHTML = field.listEscapeHTML ? FTableDOMHelper.escapeHtml(value) : value;
            }
        }
    }

    createMainStructure() {
        this.elements.mainContainer = FTableDOMHelper.create('div', {
            className: 'ftable-main-container',
            parent: this.element
        });

        // Title
        if (this.options.title) {
            this.elements.titleDiv = FTableDOMHelper.create('div', {
                className: 'ftable-title',
                parent: this.elements.mainContainer
            });

            FTableDOMHelper.create('div', {
                className: 'ftable-title-text',
                innerHTML: this.options.title,
                parent: this.elements.titleDiv
            });
        }

        // Toolbar
        this.elements.toolbarDiv = FTableDOMHelper.create('div', {
            className: 'ftable-toolbar',
            parent: this.elements.titleDiv || this.elements.mainContainer
        });

        // Table container
        this.elements.tableDiv = FTableDOMHelper.create('div', {
            className: 'ftable-table-div',
            parent: this.elements.mainContainer
        });
    }

    createTable() {
        this.elements.table = FTableDOMHelper.create('table', {
            className: 'ftable',
            parent: this.elements.tableDiv
        });

        if (this.options.tableId) {
            this.elements.table.id = this.options.tableId;
        }

        this.createTableHeader();
        this.createTableBody();
        this.addNoDataRow();
    }

    createTableHeader() {
        const thead = FTableDOMHelper.create('thead', {
            parent: this.elements.table
        });

        const headerRow = FTableDOMHelper.create('tr', {
            parent: thead
        });

        // Add selecting column if enabled
        if (this.options.selecting && this.options.selectingCheckboxes) {
            const selectHeader = FTableDOMHelper.create('th', {
                className: `ftable-command-column-header ftable-column-header-select`,
                parent: headerRow
            });

            if (this.options.multiselect) {
                const selectAllCheckbox = FTableDOMHelper.create('input', {
                    attributes: { type: 'checkbox' },
                    parent: selectHeader
                });

                selectAllCheckbox.addEventListener('change', () => {
                    this.toggleSelectAll(selectAllCheckbox.checked);
                });
            }
        }

        // Add data columns
        this.columnList.forEach(fieldName => {
            const field = this.options.fields[fieldName];
            const th = FTableDOMHelper.create('th', {
                className: `ftable-column-header ${field.listClass || ''} ${field.listClassHeader || ''}`,
                attributes: { 'data-field-name': fieldName },
                parent: headerRow
            });

            // Set width if specified
            if (field.width) {
                th.style.width = field.width;
            }

            const container = FTableDOMHelper.create('div', {
                className: 'ftable-column-header-container',
                parent: th
            });

            if (field.tooltip) {
                container.setAttribute('title', field.tooltip);
            }

            const textHeader = FTableDOMHelper.create('span', {
                className: 'ftable-column-header-text',
                innerHTML: field.title || fieldName,
                parent: container
            });

            // Make sortable if enabled
            if (this.options.sorting && field.sorting !== false) {
                FTableDOMHelper.addClass(textHeader, 'ftable-sortable-text'); // Add class for spacing
                FTableDOMHelper.addClass(th, 'ftable-column-header-sortable');
                th.addEventListener('click', (e) => {
                    e.preventDefault();
                    const isCtrlPressed = e.ctrlKey || e.metaKey; // metaKey for Mac
                    this.sortByColumn(fieldName, isCtrlPressed);
                });
            }

            // Add resize handler if column resizing is enabled
            if (this.options.columnResizable !== false && field.columnResizable !== false) {
                this.makeColumnResizable(th, container);
            }

            // Hide column if needed
            if (field.visibility === 'hidden' || field.visibility === 'separator') {
                FTableDOMHelper.hide(th);
            }
        });

        // Add action columns
        if (this.options.actions.updateAction) {
            FTableDOMHelper.create('th', {
                className: 'ftable-command-column-header ftable-column-header-edit',
                parent: headerRow
            });
        }

        if (this.options.actions.cloneAction) {
            FTableDOMHelper.create('th', {
                className: 'ftable-command-column-header ftable-column-header-clone',
                parent: headerRow
            });
        }

        if (this.options.actions.deleteAction) {
            FTableDOMHelper.create('th', {
                className: 'ftable-command-column-header ftable-column-header-delete',
                parent: headerRow
            });
        }

        if (this.options.toolbarsearch) {
            // Handle async search row without blocking by using catch
            this.createSearchHeaderRow(thead).catch(err => {
                console.error('Failed to create search header row:', err);
            });
        }
    }

    async createSearchHeaderRow(theadParent) {
        const searchRow = FTableDOMHelper.create('tr', {
            className: 'ftable-toolbarsearch-row',
            parent: theadParent
        });

        // Add empty cell for selecting column if enabled
        if (this.options.selecting && this.options.selectingCheckboxes) {
            FTableDOMHelper.create('th', {
                className: 'ftable-toolbarsearch-column-header',
                parent: searchRow
            });
        }

        // Add search input cells for data columns
        for (const fieldName of this.columnList) {
            const field = this.options.fields[fieldName];
            const isSearchable = field.searchable !== false;

            const th = FTableDOMHelper.create('th', {
                className: 'ftable-toolbarsearch-column-header',
                parent: searchRow
            });

            if (isSearchable) {
                const container = FTableDOMHelper.create('div', {
                    className: 'ftable-column-header-container',
                    parent: th
                });

                let input;
                let searchType = 'text';

                // Auto-detect select type if options are provided
                if (field.searchType) {
                    searchType = field.searchType;
                } else if (!field.type && field.options) {
                    searchType = 'select';
                } else if (field.type) {
                    searchType = field.type;
                }
                const fieldSearchName = 'ftable-toolbarsearch-' + fieldName;

                switch (searchType) {
                    case 'date':
                    case 'datetime':
                    case 'datetime-local':
                        if (typeof FDatepicker !== 'undefined') {
                            const dateFormat = field.searchDateFormat || field.dateFormat || this.options.defaultDateFormat;
                            const containerDiv = document.createElement('div');
                            // Create hidden input
                            const hiddenInput = FTableDOMHelper.create('input', {
                                className: 'ftable-toolbarsearch-extra',
                                type: 'hidden',
                                id: 'ftable-toolbarsearch-extra-' + fieldName,
                                attributes: {
                                    'data-field-name': fieldName,
                                }
                            });
                            // Create visible input
                            const visibleInput = FTableDOMHelper.create('input', {
                                className: 'ftable-toolbarsearch',
                                id: 'ftable-toolbarsearch-' + fieldName,
                                type: 'text',
                                placeholder: field.searchPlaceholder || field.placeholder || '',
                                readOnly: true
                            });
                            // Append both inputs
                            containerDiv.appendChild(hiddenInput);
                            containerDiv.appendChild(visibleInput);

                            // Apply FDatepicker
                            // Initialize FDatepicker AFTER the container is in the DOM
                            // We'll use a small timeout to ensure DOM attachment
                            switch (searchType) {
                                case 'date':
                                    setTimeout(() => { 
                                        const picker = new FDatepicker(visibleInput, {
                                            format: dateFormat,
                                            altField: 'ftable-toolbarsearch-extra-' + fieldName,
                                            altFormat: 'Y-m-d',
                                            autoClose: true
                                        });
                                    }, 0);
                                    break;
                                case 'datetime':
                                case 'datetime-local':
                                    setTimeout(() => { 
                                        const picker = new FDatepicker(visibleInput, {
                                            format: dateFormat,
                                            timepicker: true,
                                            altField: 'ftable-toolbarsearch-extra-' + fieldName,
                                            altFormat: 'Y-m-d H:i:00'
                                        });
                                    }, 0);
                                    break;
                            }
                            input = containerDiv;

                        } else {
                            input = FTableDOMHelper.create('input', {
                                className: 'ftable-toolbarsearch',
                                type: searchType,
                                id: fieldSearchName,
                                attributes: {
                                    'data-field-name': fieldName,
                                }
                            });
                        }
                        break;

                    case 'checkbox':
                        if (!field.values) {
                            field.values = { '0' : this.options.messages.no, '1' : this.options.messages.yes }
                        }
                        input = await this.createSelectForSearch(fieldName, field, true);
                        break;

                    case 'select':
                        if (field.options) {
                            input = await this.createSelectForSearch(fieldName, field, false);
                        } else {
                            input = FTableDOMHelper.create('input', {
                                className: 'ftable-toolbarsearch',
                                type: 'text',
                                id: fieldSearchName,
                                placeholder: field.searchPlaceholder || field.placeholder || 'Search...',
                                attributes: {
                                    'data-field-name': fieldName
                                }
                            });
                        }
                        break;

                    case 'datalist':
                        input = await this.createDatalistForSearch(fieldName, field);
                        break;

                    default:
                        input = FTableDOMHelper.create('input', {
                            className: 'ftable-toolbarsearch',
                            type: 'text',
                            id: fieldSearchName,
                            placeholder: field.searchPlaceholder || field.placeholder || 'Search...',
                            attributes: {
                                'data-field-name': fieldName
                            }
                        });
                }

                if (input) {
                    container.appendChild(input);
                    if (input.datalistElement && input.datalistElement instanceof Node) {
                        container.appendChild(input.datalistElement);
                    }

                    // Handle event listeners - check if it's a custom multiselect container
                    let targetElement = input;
                    if (input.classList && input.classList.contains('ftable-multiselect-container') && input.hiddenSelect) {
                        // It's a custom multiselect - attach listener to the hidden select
                        targetElement = input.hiddenSelect;
                    }

                    if (targetElement.tagName === 'SELECT') {
                        targetElement.addEventListener('change', (e) => {
                            this.handleSearchInputChange(e);
                        });
                    } else {
                        targetElement.addEventListener('input', (e) => {
                            this.handleSearchInputChange(e);
                        });
                    }
                }
            }

            // Hide search cell if column is hidden
            if (field.visibility === 'hidden' || field.visibility === 'separator') {
                FTableDOMHelper.hide(th);
            }
        }

        if (this.options.toolbarsearch && this.options.toolbarreset) {
            // Add reset button cell
            const resetTh = FTableDOMHelper.create('th', {
                className: 'ftable-toolbarsearch-column-header ftable-toolbarsearch-reset',
                parent: searchRow
            });

            const actionCount = (this.options.actions.updateAction ? 1 : 0) + 
                (this.options.actions.deleteAction ? 1 : 0) +
                (this.options.actions.cloneAction ? 1 : 0);

            if (actionCount > 0) {
                resetTh.colSpan = actionCount;
            } else {
                FTableDOMHelper.addClass(resetTh, 'ftable-command-column-header');
            }

            const resetButton = FTableDOMHelper.create('button', {
                className: 'ftable-toolbarsearch-reset-button',
                textContent: this.options.messages.resetSearch,
                attributes : {
                    id: 'ftable-toolbarsearch-reset-button'
                },
                parent: resetTh
            });
            resetButton.addEventListener('click', () => this.resetSearch());
        }
    }

    async createSelectForSearch(fieldName, field, isCheckboxValues) {
        const fieldSearchName = 'ftable-toolbarsearch-' + fieldName;
        const attributes = {
        };

        // extra check for name and multiple
        let name = fieldName;
        let hasMultiple = false;
        // Apply inputAttributes from field definition
        if (field.searchAttributes) {
            const parsed = this.formBuilder.parseInputAttributes(field.searchAttributes);
            Object.assign(attributes, parsed);
            hasMultiple = parsed.multiple !== undefined && parsed.multiple !== false;
        } else if (field.inputAttributes) {
            const parsed = this.formBuilder.parseInputAttributes(field.inputAttributes);
            Object.assign(attributes, parsed);
            hasMultiple = parsed.multiple !== undefined && parsed.multiple !== false;
        }
        if (hasMultiple) {
            name = `${fieldName}[]`;
        }
        attributes['data-field-name'] = name;

        let optionsSource;
        if (isCheckboxValues && field.values) {
            optionsSource = Object.entries(field.values).map(([value, displayText]) => ({
                Value: value,
                DisplayText: displayText
            }));
        } else if (field.options) {
            optionsSource = await this.formBuilder.getFieldOptions(fieldName);
        }

        // If multiple, create custom UI
        if (hasMultiple) {
            return this.createCustomMultiSelectForSearch(
                fieldSearchName, 
                fieldName, 
                field, 
                optionsSource, 
                attributes
            );
        }

        // Standard single select
        const select = FTableDOMHelper.create('select', {
            attributes: attributes,
            id: fieldSearchName,
            className: 'ftable-toolbarsearch'
        });

        // Add empty option only if first option is not already empty
        const hasEmptyFirst = optionsSource?.length > 0 &&
            (optionsSource[0].Value === '' ||
                optionsSource[0].value === '' ||
                optionsSource[0] === '' ||
                (optionsSource[0].DisplayText === '' && optionsSource[0].Value == null));

        if (!hasEmptyFirst) {
            FTableDOMHelper.create('option', {
                value: '',
                innerHTML: '&nbsp;',
                parent: select
            });
        }

        if (optionsSource && Array.isArray(optionsSource)) {
            optionsSource.forEach(option => {
                const optionElement = FTableDOMHelper.create('option', {
                    value: option.Value !== undefined ? option.Value : option.value !== undefined ? option.value : option,
                    textContent: option.DisplayText || option.text || option,
                    parent: select
                });
            });
        } else if (optionsSource && typeof optionsSource === 'object') {
            Object.entries(optionsSource).forEach(([key, text]) => {
                FTableDOMHelper.create('option', {
                    value: key,
                    textContent: text,
                    parent: select
                });
            });
        }

        return select;
    }

    createCustomMultiSelectForSearch(fieldSearchName, fieldName, field, optionsSource, attributes) {
        // Create container
        const container = FTableDOMHelper.create('div', {
            className: 'ftable-multiselect-container ftable-multiselect-search ftable-toolbarsearch',
            attributes: { 'data-field-name': attributes['data-field-name'] }
        });

        // Create hidden select to maintain compatibility with existing search logic
        const hiddenSelect = FTableDOMHelper.create('select', {
            id: fieldSearchName,
            multiple: true,
            style: 'display: none;',
            attributes: attributes
        });
        container.appendChild(hiddenSelect);

        // Expose hidden select for external access (needed for event listeners and reset)
        container.hiddenSelect = hiddenSelect;

        // Create display area
        const display = FTableDOMHelper.create('div', {
            className: 'ftable-multiselect-display',
            parent: container
        });

        const selectedDisplay = FTableDOMHelper.create('div', {
            className: 'ftable-multiselect-selected',
            parent: display
        });

        const placeholderText = field.searchPlaceholder || field.placeholder || this.options.messages.multiSelectPlaceholder || 'Click to select options...';
        const placeholder = FTableDOMHelper.create('span', {
            className: 'ftable-multiselect-placeholder',
            textContent: placeholderText,
            parent: selectedDisplay
        });

        // Create dropdown toggle button
        const toggleBtn = FTableDOMHelper.create('button', {
            type: 'button',
            className: 'ftable-multiselect-toggle',
            innerHTML: '▼',
            parent: display
        });

        // Create options dropdown
        const dropdown = FTableDOMHelper.create('div', {
            className: 'ftable-multiselect-dropdown',
            parent: container,
            style: 'display: none;'
        });

        // Store selected values and checkbox references
        const selectedValues = new Set();
        const checkboxMap = new Map(); // Map of value -> checkbox element

        // Function to update display and hidden select
        const updateDisplay = () => {
            selectedDisplay.innerHTML = '';
            
            // Update hidden select
            Array.from(hiddenSelect.options).forEach(opt => {
                opt.selected = selectedValues.has(opt.value);
            });
            
            // Trigger change event on hidden select for search functionality
            hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
            
            if (selectedValues.size === 0) {
                placeholder.textContent = placeholderText;
                selectedDisplay.appendChild(placeholder);
            } else {
                const selectedArray = Array.from(selectedValues);
                const optionsMap = new Map();
                
                // Build options map
                if (optionsSource && Array.isArray(optionsSource)) {
                    optionsSource.forEach(opt => {
                        const val = opt.Value !== undefined ? opt.Value : 
                            opt.value !== undefined ? opt.value : opt;
                        const text = opt.DisplayText || opt.text || opt;
                        optionsMap.set(val.toString(), text);
                    });
                }
                
                selectedArray.forEach(val => {
                    const tag = FTableDOMHelper.create('span', {
                        className: 'ftable-multiselect-tag',
                        parent: selectedDisplay
                    });
                    
                    FTableDOMHelper.create('span', {
                        className: 'ftable-multiselect-tag-text',
                        textContent: optionsMap.get(val.toString()) || val,
                        parent: tag
                    });
                    
                    const removeBtn = FTableDOMHelper.create('span', {
                        className: 'ftable-multiselect-tag-remove',
                        innerHTML: '×',
                        parent: tag
                    });
                    
                    removeBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        selectedValues.delete(val);
                        // Update the checkbox state
                        const checkbox = checkboxMap.get(val.toString());
                        if (checkbox) {
                            checkbox.checked = false;
                        }
                        updateDisplay();
                    });
                });
            }
        };

        // Add reset method to container
        container.resetMultiSelect = () => {
            selectedValues.clear();
            checkboxMap.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateDisplay();
        };

        // Populate options in both hidden select and dropdown
        const populateOptions = () => {
            if (!optionsSource) return;
            
            const options = Array.isArray(optionsSource) ? optionsSource : 
                Object.entries(optionsSource).map(([k, v]) => ({Value: k, DisplayText: v}));
            
            options.forEach(option => {
                const optValue = option.Value !== undefined ? option.Value : 
                    option.value !== undefined ? option.value : option;

                // Skip if value is empty
                if (optValue == null || optValue === '') {
                    return; // This continues to the next iteration
                }

                const optText = option.DisplayText || option.text || option;

                // Add to hidden select
                FTableDOMHelper.create('option', {
                    value: optValue,
                    textContent: optText,
                    parent: hiddenSelect
                });
                
                // Add to visual dropdown
                const optionDiv = FTableDOMHelper.create('div', {
                    className: 'ftable-multiselect-option',
                    parent: dropdown
                });
                
                const checkbox = FTableDOMHelper.create('input', {
                    type: 'checkbox',
                    className: 'ftable-multiselect-checkbox',
                    parent: optionDiv
                });
                
                // Store checkbox reference
                checkboxMap.set(optValue.toString(), checkbox);
                
                const label = FTableDOMHelper.create('label', {
                    className: 'ftable-multiselect-label',
                    textContent: optText,
                    parent: optionDiv
                });
                
                // Click anywhere on the option to toggle
                optionDiv.addEventListener('click', (e) => {
                    e.stopPropagation();
                    
                    if (selectedValues.has(optValue.toString())) {
                        selectedValues.delete(optValue.toString());
                        checkbox.checked = false;
                    } else {
                        selectedValues.add(optValue.toString());
                        checkbox.checked = true;
                    }
                    
                    updateDisplay();
                });
            });
        };

        // Toggle dropdown
        const toggleDropdown = (e) => {
            if (e) e.stopPropagation();
            const isVisible = dropdown.style.display !== 'none';
            dropdown.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                // Close other dropdowns
                document.querySelectorAll('.ftable-multiselect-dropdown').forEach(dd => {
                    if (dd !== dropdown) dd.style.display = 'none';
                });
            }
        };

        display.addEventListener('click', toggleDropdown);
        toggleBtn.addEventListener('click', toggleDropdown);

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Initialize
        populateOptions();
        updateDisplay();

        return container;
    }

    async createDatalistForSearch(fieldName, field) {
        const fieldSearchName = 'ftable-toolbarsearch-' + fieldName;

        // Create the datalist element first
        const datalistId = `${fieldSearchName}-datalist`;
        const datalist = FTableDOMHelper.create('datalist', {
            attributes: { id: datalistId }
        });

        // Create the input that uses the datalist
        const input = FTableDOMHelper.create('input', {
            className: 'ftable-toolbarsearch',
            type: 'search',
            id: fieldSearchName,
            placeholder: field.searchPlaceholder || field.placeholder || 'Type or select...',
            attributes: {
                'data-field-name': fieldName,
                list: datalistId
            }
        });

        // Store reference to datalist on the input element
        input.datalistElement = datalist;

        // Load options for the datalist
        let optionsSource;

        // Use search-specific options if available
        if (field.searchOptions) {
            optionsSource = field.searchOptions;
        } else if (field.options) {
            optionsSource = await this.formBuilder.getFieldOptions(fieldName, 'table');
        }

        // Populate datalist with options
        if (optionsSource) {
            this.formBuilder.populateDatalistOptions(datalist, optionsSource);
        }

        return input;
    }

    handleSearchInputChange(event) {
        const input = event.target;
        const fieldName = input.getAttribute('data-field-name');
        let value;
        // Handle multiple select or multiple file inputs
        if (input.multiple && input.options) {
            // It's a <select multiple>
            value = Array.from(input.selectedOptions)
                .map(option => option.value)
                .filter(v => v.trim() !== '') // optional: filter out empty values
                .map(v => v.trim());
        } else {
            // Regular input (text, number, etc.) or single select
            value = input.value.trim();
        }

        // reset paging to page 1
        this.state.currentPage = 1;
        // Update internal search state
        if (Array.isArray(value) && value.length > 0) {
            this.state.searchQueries[fieldName] = value;
        } else if (!Array.isArray(value) && value) {
            this.state.searchQueries[fieldName] = value;
        } else {
            delete this.state.searchQueries[fieldName];
        }

        // Debounce the load call
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
           this.load();
        }, this.options.searchDebounceMs);
    }

    resetSearch() {
        // Clear internal search state
        this.state.searchQueries = {};

        // Clear input values in the search row
        const searchInputs = this.elements.table.querySelectorAll('.ftable-toolbarsearch');
        searchInputs.forEach(input => {
            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0; // Select the first (empty) option
            } else {
                input.value = '';
            }
        });

        // Clear custom multiselect containers
        const multiSelectContainers = this.elements.table.querySelectorAll('.ftable-multiselect-container');
        multiSelectContainers.forEach(container => {
            if (typeof container.resetMultiSelect === 'function') {
                container.resetMultiSelect();
            }
        });

        // Reload data without search parameters
        this.load();
    }

    getNextResizableHeader(th) {
        const headers = Array.from(this.elements.table.querySelectorAll('thead th.ftable-column-header-resizable'));
        const index = headers.indexOf(th);
        for (let i = index + 1; i < headers.length; i++) {
            if (headers[i].offsetParent !== null) { // visible
                return headers[i];
            }
        }
        return null;
    }

    makeColumnResizable(th, container) {
        FTableDOMHelper.addClass(th, 'ftable-column-header-resizable');
        if (!this.elements.resizeBar) {
            this.elements.resizeBar = FTableDOMHelper.create('div', {
                className: 'ftable-column-resize-bar',
                parent: this.elements.mainContainer
            });
            FTableDOMHelper.hide(this.elements.resizeBar);
        }

        const resizeHandler = FTableDOMHelper.create('div', {
            className: 'ftable-column-resize-handler',
            parent: container
        });

        let isResizing = false;
        let startX = 0;
        let startWidth = 0;
        let containerRect;
        let nextTh = null;
        let nextStartWidth = 0;
        let nextField = null;

        resizeHandler.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();

            isResizing = true;

            // Capture layout
            containerRect = this.elements.mainContainer.getBoundingClientRect();
            startX = e.clientX;
            startWidth = th.offsetWidth;

            // Find next visible column
            nextTh = this.getNextResizableHeader(th);
            if (nextTh) {
                nextStartWidth = nextTh.offsetWidth;
                const fieldName = nextTh.dataset.fieldName;
                nextField = this.options.fields[fieldName];
         //  } else {
           //     return;
            }

            // Position resize bar
            const thRect = th.getBoundingClientRect();
            this.elements.resizeBar.style.left = (thRect.right - containerRect.left) + 'px';
            this.elements.resizeBar.style.top = (thRect.top - containerRect.top) + 'px';
            this.elements.resizeBar.style.height = this.elements.table.offsetHeight + 'px';

            FTableDOMHelper.show(this.elements.resizeBar);

            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });

        const handleMouseMove = (e) => {
            if (!isResizing) return;

            // Move resize bar with mouse
            this.elements.resizeBar.style.left = (e.clientX - containerRect.left) + 'px';
        };

        const handleMouseUp = (e) => {
            if (!isResizing) return;
            isResizing = false;

            const diff = e.clientX - startX; // px
            const totalWidth = containerRect.width;

            // Current column new width in px
            const newCurrentPx = Math.max(50, startWidth + diff);
            const newCurrentPct = (newCurrentPx / totalWidth) * 100;

            // Next column adjustment
            if (nextTh) {
                const newNextPx = Math.max(50, nextStartWidth - diff); // opposite delta
                const newNextPct = (newNextPx / totalWidth) * 100;

                // Apply to next
                nextField.width = `${newNextPct.toFixed(2)}%`;
                nextTh.style.width = nextField.width;
            }

            // Apply to current
            const field = this.options.fields[th.dataset.fieldName];
            field.width = `${newCurrentPct.toFixed(2)}%`;
            th.style.width = field.width;

            // Final normalization (optional, but safe)
            this.normalizeColumnWidths();

            // Save
            if (this.options.saveUserPreferences) {
                this.saveColumnSettings();
            }

            FTableDOMHelper.hide(this.elements.resizeBar);
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        };
    }

    saveColumnSettings() {
        if (!this.options.saveUserPreferences) return;
        
        const settings = {};
        this.columnList.forEach(fieldName => {
            const th = this.elements.table.querySelector(`[data-field-name="${fieldName}"]`);
            if (th) {
                const field = this.options.fields[fieldName];
                settings[fieldName] = {
                    width: th.style.width || field.width || 'auto',
                    visibility: field.visibility || 'visible'
                };
            }
        });
        
        this.userPrefs.set('column-settings', JSON.stringify(settings));
    }

    saveState() {
        if (!this.options.saveUserPreferences) return;

        const state = {
            sorting: this.state.sorting,
            pageSize: this.state.pageSize
        };

        this.userPrefs.set('table-state', JSON.stringify(state));
    }

    loadColumnSettings() {
        if (!this.options.saveUserPreferences) return;
        
        const settingsJson = this.userPrefs.get('column-settings');
        if (!settingsJson) return;
        
        try {
            const settings = JSON.parse(settingsJson);
            Object.entries(settings).forEach(([fieldName, config]) => {
                const field = this.options.fields[fieldName];
                if (field) {
                    if (config.width) field.width = config.width;
                    if (config.visibility) field.visibility = config.visibility;
                }
            });
        } catch (error) {
            this.logger.warn('Failed to load column settings:', error);
        }
    }

    loadState() {
        if (!this.options.saveUserPreferences) return;

        const stateJson = this.userPrefs.get('table-state');
        if (!stateJson) return;

        try {
            const state = JSON.parse(stateJson);
            if (Array.isArray(state.sorting)) {
                this.state.sorting = state.sorting;
            }
            if (state.pageSize && this.options.pageSizes.includes(state.pageSize)) {
                this.state.pageSize = state.pageSize;
            }
        } catch (error) {
            this.logger.warn('Failed to load table state:', error);
        }
    }

    createTableBody() {
        this.elements.tableBody = FTableDOMHelper.create('tbody', {
            parent: this.elements.table
        });
    }

    addNoDataRow() {
        if (this.elements.tableBody.querySelector('.ftable-no-data-row')) return;
        
        const row = FTableDOMHelper.create('tr', {
            className: 'ftable-no-data-row',
            parent: this.elements.tableBody
        });
        
        const colCount = this.elements.table.querySelector('thead tr').children.length;
        FTableDOMHelper.create('td', {
            attributes: { colspan: colCount },
            textContent: this.options.messages.noDataAvailable,
            parent: row
        });
    }

    removeNoDataRow() {
        const noDataRow = this.elements.tableBody.querySelector('.ftable-no-data-row');
        if (noDataRow) noDataRow.remove();
    }

    createModals() {
        // Create modals for different operations
        if (this.options.actions.createAction) {
            this.createAddRecordModal();
        }
        
        if (this.options.actions.updateAction) {
            this.createEditRecordModal();
        }
        
        if (this.options.actions.deleteAction) {
            this.createDeleteConfirmModal();
        }

        this.createErrorModal();
        this.createInfoModal();
        this.createLoadingModal();

        // Initialize them (create DOM) once
        Object.values(this.modals).forEach(modal => modal.create());
    }

    createAddRecordModal() {
        this.modals.addRecord = new FtableModal({
            parent: this.elements.mainContainer,
            title: this.options.messages.addNewRecord,
            className: 'ftable-add-modal',
            closeOnOverlayClick: this.options.closeOnOverlayClick,
            buttons: [
                {
                    text: this.options.messages.cancel,
                    className: 'ftable-dialog-cancelbutton',
                    onClick: () => {
                        this.modals.addRecord.close();
                        this.emit('formClosed', { form: this.currentForm, formType: 'create', record: null });

						// Verwijder formulier bij cancel
						if (this.currentForm && this.currentForm.parentNode) {
							this.currentForm.remove();
						}
						this.currentForm = null;
                    }
                },
                {
                    text: this.options.messages.save,
                    className: 'ftable-dialog-savebutton',
                    onClick: () => this.saveNewRecord()
                }
            ]
        });
    }

    createEditRecordModal() {
        this.modals.editRecord = new FtableModal({
            parent: this.elements.mainContainer,
            title: this.options.messages.editRecord,
            className: 'ftable-edit-modal',
            closeOnOverlayClick: this.options.closeOnOverlayClick,
            buttons: [
                {
                    text: this.options.messages.cancel,
                    className: 'ftable-dialog-cancelbutton',
                    onClick: () => { 
                        this.modals.editRecord.close();
                        this.emit('formClosed', { form: this.currentForm, formType: 'edit', record: null });

						// Verwijder formulier bij cancel
						if (this.currentForm && this.currentForm.parentNode) {
							this.currentForm.remove();
						}
						this.currentForm = null;
                    }
                },
                {
                    text: this.options.messages.save,
                    className: 'ftable-dialog-savebutton',
                    onClick: () => this.saveEditedRecord()
                }
            ]
        });
    }

    createDeleteConfirmModal() {
        this.modals.deleteConfirm = new FtableModal({
            parent: this.elements.mainContainer,
            title: this.options.messages.areYouSure,
            className: 'ftable-delete-modal',
            closeOnOverlayClick: this.options.closeOnOverlayClick,
            buttons: [
                {
                    text: this.options.messages.cancel,
                    className: 'ftable-dialog-cancelbutton',
                    onClick: () => this.modals.deleteConfirm.close()
                },
                {
                    text: this.options.messages.deleteText,
                    className: 'ftable-dialog-deletebutton',
                    onClick: () => this.confirmDelete()
                }
            ]
        });
    }

    createErrorModal() {
        this.modals.error = new FtableModal({
            parent: this.elements.mainContainer,
            title: this.options.messages.error,
            className: 'ftable-error-modal',
            closeOnOverlayClick: this.options.closeOnOverlayClick,
            buttons: [
                {
                    text: this.options.messages.close,
                    className: 'ftable-dialog-closebutton',
                    onClick: () => this.modals.error.close()
                }
            ]
        });
    }

    createInfoModal() {
        this.modals.info = new FtableModal({
            parent: this.elements.mainContainer,
            title: '',
            className: 'ftable-info-modal',
            closeOnOverlayClick: this.options.closeOnOverlayClick,
            buttons: [
                {
                    text: this.options.messages.close,
                    className: 'ftable-dialog-closebutton',
                    onClick: () => this.modals.info.close()
                }
            ]
        });
    }

    createLoadingModal() {
        this.modals.loading = new FtableModal({
            parent: this.elements.mainContainer,
            title: '',
            className: 'ftable-loading-modal',
            closeOnOverlayClick: false,
            content: `<div class="ftable-loading-message">${this.options.messages.loadingMessage}</div>`
        });
    }

    bindEvents() {
        // Subscribe all event handlers from options
        this.subscribeOptionEvents();

        // Add toolbar buttons
        this.createCustomToolbarItems();
        this.createToolbarButtons();
        
        // Keyboard shortcuts
        this.bindKeyboardEvents();
        
        // Column selection context menu
        if (this.options.columnSelectable !== false) {
            this.createColumnSelectionMenu();
        }
    }

    subscribeOptionEvents() {
        const events = [
            //'closeRequested', NOT EMITTED
            'formCreated',
            // 'formSubmitting', NOT EMITTED
            'formClosed',
            //'loadingRecords', NOT EMITTED
            'recordsLoaded', // { records: data.Records, serverResponse: data }
            // 'rowInserted', NOT EMITTED
            // 'rowsRemoved', NOT EMITTED
            'recordAdded', // { record: result.Record }
            // 'rowUpdated', NOT EMITTED
            'recordUpdated', // { record: result.Record || formData }
            'recordDeleted', // { record: this.currentDeletingRow.recordData }
            'selectionChanged', // { selectedRows: this.getSelectedRows() }
            //'bulkDelete', // NOT USEFULL { results: results, successful: successfulDeletes.length, failed: failed }
            //'columnVisibilityChanged', // NOT USEFULL { field: field }
        ];

        events.forEach(event => {
            if (typeof this.options[event] === 'function') {
                this.on(event, this.options[event]);
            }
        });
    }

    createColumnSelectionMenu() {
        // Create column selection overlay and menu
        this.elements.columnSelectionOverlay = null;
        this.elements.columnSelectionMenu = null;

        // Bind right-click event to table header
        const thead = this.elements.table.querySelector('thead');
        thead.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.showColumnSelectionMenu(e);
        });
    }

    showColumnSelectionMenu(e) {
        // Remove existing menu if any
        this.hideColumnSelectionMenu();

        // Create overlay to capture clicks outside menu
        this.elements.columnSelectionOverlay = FTableDOMHelper.create('div', {
            className: 'ftable-contextmenu-overlay',
            //parent: this.elements.mainContainer
            parent: document.body
        });

        // Create the menu
        this.elements.columnSelectionMenu = FTableDOMHelper.create('div', {
            className: 'ftable-column-selection-container',
            //parent: this.elements.columnSelectionOverlay
            parent: document.body
        });

        // Populate menu with column options
        this.populateColumnSelectionMenu();

        // Position the menu
        this.positionColumnSelectionMenu(e);

        // Add event listeners
        this.elements.columnSelectionOverlay.addEventListener('click', (event) => {
            if (event.target === this.elements.columnSelectionOverlay) {
                this.hideColumnSelectionMenu();
            }
        });

        this.elements.columnSelectionOverlay.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            this.hideColumnSelectionMenu();
        });
    }

    populateColumnSelectionMenu() {
        const menuList = FTableDOMHelper.create('ul', {
            className: 'ftable-column-select-list',
            parent: this.elements.columnSelectionMenu
        });

        this.columnList.forEach(fieldName => {
            const field = this.options.fields[fieldName];
            const isVisible = field.visibility !== 'hidden';
            const isFixed = field.visibility === 'fixed';
            const isSeparator = field.visibility === 'separator';
            const isSorted = this.isFieldSorted(fieldName);

            const listItem = FTableDOMHelper.create('li', {
                className: 'ftable-column-select-item',
                parent: menuList
            });

            const label = FTableDOMHelper.create('label', {
                className: 'ftable-column-select-label',
                parent: listItem
            });

            if (!isSeparator) {
                const checkbox = FTableDOMHelper.create('input', {
                    attributes: { 
                        type: 'checkbox',
                        id: `column-${fieldName}`
                    },
                    parent: label
                });
                checkbox.checked = isVisible;

                // Disable checkbox if column is fixed or currently sorted
                if (isFixed || (isSorted && isVisible)) {
                    checkbox.disabled = true;
                    listItem.style.opacity = '0.6';
                }
                // Handle checkbox change
                if (!checkbox.disabled) {
                    checkbox.addEventListener('change', () => {
                        this.setColumnVisibility(fieldName, checkbox.checked);
                    });
                }
            }

            const labelText = FTableDOMHelper.create('span', {
                textContent: field.title || fieldName,
                style: isSeparator ? 'font-weight: bold;' : null,
                parent: label
            });

            // Add sorted indicator
            if (isSorted) {
                const sortIndicator = FTableDOMHelper.create('span', {
                    className: 'ftable-sort-indicator',
                    textContent: ' (sorted)',
                    parent: labelText
                });
                sortIndicator.style.fontSize = '0.8em';
                sortIndicator.style.color = '#666';
            }

        });
    }

    positionColumnSelectionMenu(e) {
        const self = this;

        // menu is bounded to the body for absolute positioning above other content, so safest is to use pageX/Y
        let left = e.pageX;
        let top = e.pageY;

        // Define minimum width
        const minWidth = 100;

        // Position the menu
        self.elements.columnSelectionMenu.style.position = 'absolute';
        self.elements.columnSelectionMenu.style.left = `${left}px`;
        self.elements.columnSelectionMenu.style.top = `${top}px`;
        self.elements.columnSelectionMenu.style.minWidth = `${minWidth}px`;
        self.elements.columnSelectionMenu.style.boxSizing = 'border-box';

        // Optional: Adjust if menu would overflow right edge
        const menuWidth = self.elements.columnSelectionMenu.offsetWidth;
        const windowWidth = window.innerWidth;

        if (left + menuWidth > windowWidth) {
            left = Math.max(10, windowWidth - menuWidth - 10); // 10px margin
            self.elements.columnSelectionMenu.style.left = `${left}px`;
        }
    }

    hideColumnSelectionMenu() {
        if (this.elements.columnSelectionOverlay) {
            this.elements.columnSelectionOverlay.remove();
            this.elements.columnSelectionMenu.remove();
            this.elements.columnSelectionOverlay = null;
            this.elements.columnSelectionMenu = null;
        }
    }

    isFieldSorted(fieldName) {
        return this.state.sorting.some(sort => sort.fieldName === fieldName);
    }

    createToolbarButtons() {
        // CSV Export Button
        if (this.options.csvExport) {
            this.addToolbarButton({
                text: this.options.messages.csvExport,
                className: 'ftable-toolbar-item-csv',
                onClick: () => {
                    const filename = this.options.title
                        ? `${this.options.title.replace(/[^a-z0-9]/gi, '-').toLowerCase()}.csv`
                        : 'table-export.csv';
                    this.exportToCSV(filename);
                }
            });
        }
        // Print Button
        if (this.options.printTable) {
            this.addToolbarButton({
                text: this.options.messages.printTable,
                className: 'ftable-toolbar-item-print',
                onClick: () => {
                    this.printTable();
                }
            });
        }

        if (this.options.actions.createAction) {
            this.addToolbarButton({
                text: this.options.messages.addNewRecord,
                className: 'ftable-toolbar-item-add-record',
                addIconSpan: true,
                onClick: () => this.showAddRecordForm()
            });
        }
    }

    addToolbarButton(options) {
        const button = FTableDOMHelper.create('button', {
            className: `ftable-toolbar-item ${options.className || ''}`,
            id: options.id || null,
            title: options.title || null,
            textContent: options.text || null,
            type: 'button',  // Prevent accidental form submission
            parent: this.elements.toolbarDiv
        });

        if (options.addIconSpan) {
            // just the span, the rest is CSS here
            const iconSpan = FTableDOMHelper.create('span', {
                className: `ftable-toolbar-item-icon ${options.className || ''}`,
                parent: button
            });
            // If we want icon before text, we need to insert it
            // Since textContent replaces everything, we need to append text node
            if (options.text) {
                // Remove the textContent we set earlier
                button.textContent = '';
                button.append(iconSpan, options.text || '');
            }
        }

        // Add icon if provided
        if (options.icon) {
            const img = FTableDOMHelper.create('img', {
                attributes: {
                    src: options.icon,
                    alt: '',
                    width: 16,
                    height: 16,
                    style: 'margin-right: 6px; vertical-align: middle;'
                },
                parent: button
            });
            // If we want icon before text, we need to insert it
            // Since textContent replaces everything, we need to append text node
            if (options.text) {
                // Remove the textContent we set earlier
                button.textContent = '';
                button.append(img, options.text || '');
            }
        }

        if (options.onClick) {
            button.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    options.onClick(e);
            });
        }

        if (options.disabled) {
            button.disabled = true;
        }

        return button;
    }

    createCustomToolbarItems() {
        if (!this.options.toolbar || !this.options.toolbar.items) return;

        this.options.toolbar.items.forEach((item, index) => {
            this.addToolbarButton({
                text: item.text || '',
                className: `ftable-toolbar-item-custom ${item.buttonClass || ''}`,
                id: item.buttonId || `ftable-toolbar-item-custom-id-${index}`,
                title: item.tooltip || '',
                icon: item.icon || null,
                onClick: typeof item.click === 'function' ? item.click : null
            });
        });
    }

    bindKeyboardEvents() {
        if (this.options.selecting) {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Shift') this.shiftKeyDown = true;
            });
            
            document.addEventListener('keyup', (e) => {
                if (e.key === 'Shift') this.shiftKeyDown = false;
            });
        }
    }

    setupFTableUserPreferences() {
        if (this.options.saveUserPreferences) {
            const prefix = this.userPrefs.generatePrefix(
                this.options.tableId || '',
                this.fieldList
            );
            this.userPrefs = new FTableUserPreferences(prefix, this.options.saveUserPreferencesMethod);
            
            // Load saved column settings
            this.loadState();
            this.loadColumnSettings();
        }
    }

    async load(queryParams = {}) {
        if (this.state.isLoading) return;
        
        this.state.isLoading = true;
        this.showLoadingIndicator();

        
        try {
            const params = {
                ...queryParams,
                ...this.buildLoadParams()
            };
            
            const data = await this.performLoad(params);
            this.processLoadedData(data);
            this.emit('recordsLoaded', { records: data.Records, serverResponse: data });
        } catch (error) {
            this.showError(this.options.messages.serverCommunicationError);
            this.logger.error(`Load failed: ${error.message}`);
        } finally {
            this.state.isLoading = false;
            this.hideLoadingIndicator();
        }
        // Update sorting display
        this.renderSortingInfo();

        this.normalizeColumnWidths();
    }

    buildLoadParams() {
        const params = {};
        
        if (this.options.paging) {
            if (!this.state.pageSize) {
                this.state.pageSize = this.options.pageSize;
            }
            params.jtStartIndex = (this.state.currentPage - 1) * this.state.pageSize;
            params.jtPageSize = this.state.pageSize;
        }
        
        if (this.options.sorting) {
            if (this.state.sorting.length > 0) {
                params.jtSorting = this.state.sorting
                    .map(sort => `${sort.fieldName} ${sort.direction}`)
                    .join(', ');
            } else if (this.options.defaultSorting) {
                params.jtSorting = this.parseDefaultSorting(this.options.defaultSorting)
                    .map(sort => `${sort.fieldName} ${sort.direction}`)
                    .join(', ');
            }
        }
        if (this.options.toolbarsearch && Object.keys(this.state.searchQueries).length > 0) {
             const queries = [];
             const searchFields = [];

             Object.entries(this.state.searchQueries).forEach(([fieldName, query]) => {
                 if (query !== '') { // Double check it's not empty
                     queries.push(query);
                     searchFields.push(fieldName);
                 }
             });

             if (queries.length > 0) {
                 params['q'] = queries;
                 params['opt'] = searchFields;
             }
        }

        // support listQueryParams
        if (typeof this.options.listQueryParams === 'function') {
            const customParams = this.options.listQueryParams();
            Object.assign(params, customParams);
        }
        
        return params;
    }

    isCacheExpired(cacheEntry, cacheDuration) {
        if (!cacheEntry || !cacheEntry.timestamp) return true;
        const age = Date.now() - cacheEntry.timestamp;
        return age > cacheDuration;
    }

    async performLoad(params) {
        const listAction = this.options.actions.listAction;

        // Check if caching is enabled
        if (this.options.listCache && typeof listAction === 'string') {
            // Try to get from cache
            const cached = this.formBuilder.optionsCache.get(listAction, params);
            if (cached && !this.isCacheExpired(cached, this.options.listCache)) {
                return cached.data;
            }
        }

        let data;
        if (typeof listAction === 'function') {
            data = await listAction(params);
        } else if (typeof listAction === 'string') {
            data = this.options.forcePost
                ? await FTableHttpClient.post(listAction, params)
                : await FTableHttpClient.get(listAction, params);
        } else {
            throw new Error('No valid listAction provided');
        }

        // Validate response
        if (!data || data.Result !== 'OK') {
            throw new Error(data?.Message || 'Invalid response from server');
        }

        // Cache if enabled
        if (this.options.listCache && typeof listAction === 'string') {
            this.formBuilder.optionsCache.set(listAction, params, {
                data: data,
                timestamp: Date.now()
            });
        }

        return data;
    }

    processLoadedData(data) {
        if (data.Result !== 'OK') {
            this.showError(data.Message || 'Unknown error occurred');
            return;
        }

        this.state.records = data.Records || [];
        this.state.totalRecordCount = data.TotalRecordCount || this.state.records.length;
        
        this.renderTableData();
        this.updatePagingInfo();
    }

    renderTableData() {
        // Clear existing data rows
        const dataRows = this.elements.tableBody.querySelectorAll('.ftable-data-row');
        dataRows.forEach(row => row.remove());

        if (this.state.records.length === 0) {
            this.addNoDataRow();
            return;
        }

        this.removeNoDataRow();

        // Add new rows
        this.state.records.forEach(record => {
            const row = this.createTableRow(record);
            this.elements.tableBody.appendChild(row);
        });

        this.refreshRowStyles();
        // the next call might not do anything if option resolving hasn't finished yet
        // in fact, it might even not be needed, since we call it when option resolving finishes (see init)
        //this.refreshDisplayValues(); // for options that uses functions/url's
    }

    createTableRow(record) {
        const row = FTableDOMHelper.create('tr', {
            className: 'ftable-data-row',
            attributes: { 'data-record-key': this.getKeyValue(record) }
        });

        // Store record data
        row.recordData = record;

        // Add selecting checkbox if enabled
        if (this.options.selecting && this.options.selectingCheckboxes) {
            this.addSelectingCell(row);
        }

        // Add data cells
        this.columnList.forEach(fieldName => {
            this.addDataCell(row, record, fieldName);
        });

        // Add action cells
        if (this.options.actions.updateAction) {
            this.addEditCell(row);
        }

        if (this.options.actions.cloneAction) {
            this.addCloneCell(row);
        }

        if (this.options.actions.deleteAction) {
            this.addDeleteCell(row);
        }

        // Make row selectable if enabled
        if (this.options.selecting) {
            this.makeRowSelectable(row);
        }

        return row;
    }

    addSelectingCell(row) {
        const cell = FTableDOMHelper.create('td', {
            className: 'ftable-command-column ftable-selecting-column',
            parent: row
        });

        const checkbox = FTableDOMHelper.create('input', {
            className: 'norowselectonclick', // this prevents clicks on the select to also become clicks on the row
            attributes: { type: 'checkbox' },
            parent: cell
        });

        checkbox.addEventListener('change', (e) => {
            this.toggleRowSelection(row);
        });
    }

    addDataCell(row, record, fieldName) {
        const field = this.options.fields[fieldName];
        const cacheKey = this.formBuilder.generateOptionsCacheKey('table', {});
        const resolvedOptions = this.formBuilder.resolvedFieldOptions.get(fieldName)?.[cacheKey];
        const value = this.getDisplayText(record, fieldName, resolvedOptions);
        
        const cell = FTableDOMHelper.create('td', {
            className: `${field.listClass || ''} ${field.listClassEntry || ''}`,
            innerHTML: field.listEscapeHTML ? FTableDOMHelper.escapeHtml(value) : value,
            attributes: { 'data-field-name': fieldName },
            parent: row
        });
        if (field.visibility === 'fixed') {
            return;
        }
        if (field.visibility === 'hidden' || field.visibility === 'separator') {
            FTableDOMHelper.hide(cell);
        }
    }

    addEditCell(row) {
        const cell = FTableDOMHelper.create('td', {
            className: 'ftable-command-column',
            parent: row
        });

        const button = FTableDOMHelper.create('button', {
            className: 'ftable-command-button ftable-edit-command-button',
            attributes: { title: this.options.messages.editRecord },
            innerHTML: `<span>${this.options.messages.editRecord}</span>`,
            parent: cell
        });

        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.editRecord(row);
        });
    }

    addCloneCell(row) {
        const cell = FTableDOMHelper.create('td', {
            className: 'ftable-command-column',
            parent: row
        });
        const button = FTableDOMHelper.create('button', {
            className: 'ftable-command-button ftable-clone-command-button',
            attributes: { title: this.options.messages.cloneRecord || 'Clone' },
            innerHTML: `<span>${this.options.messages.cloneRecord || 'Clone'}</span>`,
            parent: cell
        });
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.cloneRecord(row);
        });
    }

    addDeleteCell(row) {
        const cell = FTableDOMHelper.create('td', {
            className: 'ftable-command-column',
            parent: row
        });

        const button = FTableDOMHelper.create('button', {
            className: 'ftable-command-button ftable-delete-command-button',
            attributes: { title: this.options.messages.deleteText },
            innerHTML: `<span>${this.options.messages.deleteText}</span>`,
            parent: cell
        });

        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.deleteRecord(row);
        });
    }

    getDisplayText(record, fieldName, customOptions = null) {
        const field = this.options.fields[fieldName];
        const value = record[fieldName];
        const options = customOptions || field.options;
        let displayValue = value;

        if (field.type === 'date' && value) {
            if (typeof FDatepicker !== 'undefined') {
                displayValue = FDatepicker.formatDate(this._parseDate(value), field.dateFormat || this.options.defaultDateFormat);
            } else {
                displayValue = this.formatDate(value, field.dateLocale || this.options.defaultDateLocale );
            }
        }

        if ((field.type === 'datetime-local' || field.type === 'datetime') && value) {
            if (typeof FDatepicker !== 'undefined') {
                displayValue = FDatepicker.formatDate(this._parseDate(value), field.dateFormat || this.options.defaultDateFormat);
            } else {
                displayValue = this.formatDateTime(value, field.dateLocale || this.options.defaultDateLocale );
            }
        }

        if (field.type === 'checkbox') {
            displayValue = this.getCheckboxText(fieldName, value);
        }

        if (options) {
            const option = this.findOptionByValue(options, value);
            displayValue = option ? option.DisplayText || option.text || option : value;
        }

        if (field.display && typeof field.display === 'function') {
            displayValue = field.display({ record, value, displayValue });
        }


        return displayValue || '';
    }

    _parseDate(dateString) {
        if (dateString.includes('Date')) { // Format: /Date(1320259705710)/
            return new Date(
                parseInt(dateString.substr(6), 10)
            );
        } else if (dateString.length == 10) { // Format: 2011-01-01
            return new Date(
                parseInt(dateString.substr(0, 4), 10),
                parseInt(dateString.substr(5, 2), 10) - 1,
                parseInt(dateString.substr(8, 2), 10)
            );
        } else if (dateString.length == 19) { // Format: 2011-01-01 20:32:42
            return new Date(
                parseInt(dateString.substr(0, 4), 10),
                parseInt(dateString.substr(5, 2), 10) - 1,
                parseInt(dateString.substr(8, 2), 10),
                parseInt(dateString.substr(11, 2), 10),
                parseInt(dateString.substr(14, 2), 10),
                parseInt(dateString.substr(17, 2), 10)
            );
        } else {
            return new Date(dateString);
        }
    }

    formatDate(dateValue, format) {
        if (!dateValue) return '';

        const date = this._parseDate(dateValue);
        try {
            if (isNaN(date.getTime())) return dateValue;
            return date.toLocaleDateString(format,{ year: "numeric", month: "2-digit", day: "2-digit" });
        } catch {
            return dateValue;
        }
    }

    formatDateTime(dateValue, format) {
        if (!dateValue) return '';

        const date = this._parseDate(dateValue);
        try {
            if (isNaN(date.getTime())) return dateValue;
            return date.toLocaleString(format);
        } catch {
            return dateValue;
        }
    }

    getCheckboxText(fieldName, value) {
        const field = this.options.fields[fieldName];
        if (field.values && field.values[value]) {
            return field.values[value];
        }
        return value ? this.options.messages.yes : this.options.messages.no;
    }

    findOptionByValue(options, value) {
        if (Array.isArray(options)) {
            return options.find(opt =>
                (opt.Value || opt.value) === value || opt === value
            );
        }

        if (typeof options === 'object' && options !== null) {
            if (options.hasOwnProperty(value)) {
                return options[value]
            }
        }
        return null;
    }

    refreshRowStyles() {
        const rows = this.elements.tableBody.querySelectorAll('.ftable-data-row');
        rows.forEach((row, index) => {
            if (index % 2 === 0) {
                FTableDOMHelper.addClass(row, 'ftable-row-even');
            } else {
                FTableDOMHelper.removeClass(row, 'ftable-row-even');
            }
        });
    }

    getKeyValue(record) {
        return this.keyField ? record[this.keyField] : null;
    }

    // CRUD Operations
    async showAddRecordForm() {
        const form = await this.formBuilder.createForm('create');
        this.modals.addRecord.setContent(form);
        this.modals.addRecord.show();
        this.currentForm = form;
        this.emit('formCreated', { form: form, formType: 'create', record: null });
    }

    async saveNewRecord() {
        if (!this.currentForm) return;

        // Check validity
        if (!this.currentForm.checkValidity()) {
            // Triggers browser to show native validation messages
            this.currentForm.reportValidity();
            return;
        }

        const formData = this.getFormData(this.currentForm);
        
        try {
            const result = await this.performCreate(formData);
            
            if (result.Result === 'OK') {
                this.clearListCache();
                this.modals.addRecord.close();
				
				// Verwijder het formulier
				if (this.currentForm && this.currentForm.parentNode) {
					this.currentForm.remove();
				}
				this.currentForm = null;

                // Call formClosed
                this.emit('formClosed', { form: this.currentForm, formType: 'create', record: null });

                if (result.Message) {
                    this.showInfo(result.Message);
                }
                await this.load(); // Reload to show new record
                this.emit('recordAdded', { record: result.Record });
            } else {
                this.showError(result.Message || 'Create failed');
            }
        } catch (error) {
            this.showError(this.options.messages.serverCommunicationError);
            this.logger.error(`Create failed: ${error.message}`);
        }
    }

    async editRecord(row) {
        const record = row.recordData;
        const form = await this.formBuilder.createForm('edit', record);
        
        this.modals.editRecord.setContent(form);
        this.modals.editRecord.show();
        this.currentForm = form;
        this.currentEditingRow = row;
        this.emit('formCreated', { form: form, formType: 'edit', record: record });
    }

    async saveEditedRecord() {
        if (!this.currentForm || !this.currentEditingRow) return;

        // Check validity
        if (!this.currentForm.checkValidity()) {
            // Triggers browser to show native validation messages
            this.currentForm.reportValidity();
            return;
        }

        const formData = this.getFormData(this.currentForm);
        
        try {
            const result = await this.performUpdate(formData);
            
            if (result.Result === 'OK') {
                this.clearListCache();
                this.modals.editRecord.close();
				
				// Verwijder het formulier
				if (this.currentForm && this.currentForm.parentNode) {
					this.currentForm.remove();
				}
				this.currentForm = null;

                // Call formClosed
                this.emit('formClosed', { form: this.currentForm, formType: 'edit', record: this.currentEditingRow.recordData });

                // Update the row with new data
                this.updateRowData(this.currentEditingRow, result.Record || formData);
                if (result.Message) {
                    this.showInfo(result.Message);
                }
                this.emit('recordUpdated', { record: result.Record || formData, row: this.currentEditingRow });
            } else {
                this.showError(result.Message || 'Update failed');
            }
        } catch (error) {
            this.showError(this.options.messages.serverCommunicationError);
            this.logger.error(`Update failed: ${error.message}`);
        }
    }

    async cloneRecord(row) {
        const record = { ...row.recordData };

        // Clear key field to allow creation
        if (this.keyField) {
            record[this.keyField] = '';
        }

        const form = await this.formBuilder.createForm('create', record);
        this.modals.addRecord.options.content = form;
        this.modals.addRecord.setContent(form);
        this.modals.addRecord.show();

        this.currentForm = form;
        this.emit('formCreated', { form: form, formType: 'create', record: record });
    }

    async deleteRows(keys) {
        if (!keys.length) return;
        const confirmMsg = this.options.messages.areYouSure;
        if (!confirm(confirmMsg)) {
            return;
        }
        const results = [];
        for (const key of keys) {
            try {
                const result = await this.performDelete(key);
                results.push({ key: key, success: result.Result === 'OK', result: result });
            } catch (error) {
                results.push({ key: key, success: false, error: error.message });
            }
        }

        // Remove successful deletions from table
        const successfulDeletes = results.filter(r => r.success);
        successfulDeletes.forEach(({ key }) => {
            const row = this.getRowByKey(key);
            if (row) this.removeRowFromTable(row);
        });

        // Show summary
        const failed = results.filter(r => !r.success).length;
        if (failed > 0) {
            this.showError(`${failed} of ${results.length} records could not be deleted`);
        }

        // Refresh UI state
        this.refreshRowStyles();
        this.updatePagingInfo();

        // this.emit('bulkDelete', { results: results, successful: successfulDeletes.length, failed: failed });
    }

    deleteRecord(row) {
        const record = row.recordData;
        let deleteConfirmMessage = this.options.messages.deleteConfirmation; // Default

        // If deleteConfirmation is a function, call it
        if (typeof this.options.deleteConfirmation === 'function') {
            const data = {
                row: row,
                record: record,
                deleteConfirmMessage: deleteConfirmMessage,
                cancel: false,
                cancelMessage: this.options.messages.cancel
            };
            this.options.deleteConfirmation(data);

            // Respect cancellation
            if (data.cancel) {
                if (data.cancelMessage) {
                    this.showError(data.cancelMessage);
                }
                return;
            }

            // Use updated message
            deleteConfirmMessage = data.deleteConfirmMessage;
        }
        this.modals.deleteConfirm.setContent(`<p>${deleteConfirmMessage}</p>`);
        this.modals.deleteConfirm.show();
        this.currentDeletingRow = row;
    }

    async confirmDelete() {
        if (!this.currentDeletingRow) return;

        const keyValue = this.getKeyValue(this.currentDeletingRow.recordData);
        
        try {
            const result = await this.performDelete(keyValue);
            
            if (result.Result === 'OK') {
                this.clearListCache();
                this.modals.deleteConfirm.close();
                this.removeRowFromTable(this.currentDeletingRow);
                if (result.Message) {
                    this.showInfo(result.Message);
                }
                this.emit('recordDeleted', { record: this.currentDeletingRow.recordData });
            } else {
                this.showError(result.Message || 'Delete failed');
            }
        } catch (error) {
            this.showError(this.options.messages.serverCommunicationError);
            this.logger.error(`Delete failed: ${error.message}`);
        }
    }

    async performCreate(data) {
        const createAction = this.options.actions.createAction;
        
        if (typeof createAction === 'function') {
            return await createAction(data);
        } else if (typeof createAction === 'string') {
            return await FTableHttpClient.post(createAction, data);
        }
        
        throw new Error('No valid createAction provided');
    }

    async performUpdate(data) {
        const updateAction = this.options.actions.updateAction;
        
        if (typeof updateAction === 'function') {
            return await updateAction(data);
        } else if (typeof updateAction === 'string') {
            return await FTableHttpClient.post(updateAction, data);
        }
        
        throw new Error('No valid updateAction provided');
    }

    async performDelete(keyValueOrData) {
        const deleteAction = this.options.actions.deleteAction;

        let data;
        if (keyValueOrData !== null && typeof keyValueOrData === 'object' && !Array.isArray(keyValueOrData)) {
            // If an object is passed, use it directly.
            // It's the caller's responsibility to ensure it contains the needed info
            data = keyValueOrData;
        } else {
            // If a primitive value is passed, wrap it using the key field
            data = { [this.keyField]: keyValueOrData };
        }
        
        if (typeof deleteAction === 'function') {
            return await deleteAction(data);
        } else if (typeof deleteAction === 'string') {
            return await FTableHttpClient.post(deleteAction, data);
        }
        
        throw new Error('No valid deleteAction provided');
    }

    getFormData(form) {
        const formData = new FormData(form);
        const data = {};

        for (const [key, value] of formData.entries()) {
            if (key.endsWith('[]')) {
                const baseKey = key.slice(0, -2); // Remove '[]'
                if (!data[baseKey]) {
                    data[baseKey] = [];
                }
                data[baseKey].push(value);
            } else {
                // For regular fields, if a key already exists, convert it to array
                if (data.hasOwnProperty(key)) {
                    if (Array.isArray(data[key])) {
                        data[key].push(value);
                    } else {
                        data[key] = [data[key], value];
                    }
                } else {
                    data[key] = value;
                }
            }
        }

        return data;
    }

    updateRowData(row, newData) {
        row.recordData = { ...row.recordData, ...newData };
        //Object.assign(row.recordData, newData);

        // Update only the fields that were changed
        Object.keys(newData).forEach(fieldName => {
            const field = this.options.fields[fieldName];
            if (!field) return;

            // Find the cell for this field
            const cell = row.querySelector(`td[data-field-name="${fieldName}"]`);
            if (!cell) return;

            // Get display text
            const cacheKey = this.formBuilder.generateOptionsCacheKey('table', {});
            const resolvedOptions = this.formBuilder.resolvedFieldOptions.get(fieldName)?.[cacheKey];
            const value = this.getDisplayText(row.recordData, fieldName, resolvedOptions);
            cell.innerHTML = field.listEscapeHTML ? FTableDOMHelper.escapeHtml(value) : value;
            cell.className = `${field.listClass || ''} ${field.listClassEntry || ''}`.trim();
        });

    }

    removeRowFromTable(row) {
        row.remove();
        
        // Check if we need to show no data row
        const remainingRows = this.elements.tableBody.querySelectorAll('.ftable-data-row');
        if (remainingRows.length === 0) {
            this.addNoDataRow();
        }
        
        this.refreshRowStyles();
    }

    // Selection Methods
    makeRowSelectable(row) {
        if (this.options.selectOnRowClick !== false) {
            row.addEventListener('click', (e) => {
                // input elements can't select the row, nor norowselectonclick class
                if (!['INPUT', 'BUTTON', 'SELECT', 'TEXTAREA', 'A'].includes(e.target.tagName) &&
                    !e.target.classList.contains('norowselectonclick')) {
                    this.toggleRowSelection(row);
                }
            });
        }
    }

    toggleRowSelection(row) {
        const isSelected = row.classList.contains('ftable-row-selected');
        
        if (this.shiftKeyDown && this.lastSelectedRow && this.options.multiselect) {
            // Shift + Click: select range
            this.clearAllSelections(); // Optional: clear previous selection, or keep it
            const rows = Array.from(this.elements.tableBody.querySelectorAll('.ftable-data-row'));
            const startIndex = rows.indexOf(this.lastSelectedRow);
            const endIndex = rows.indexOf(row);
            const [start, end] = startIndex < endIndex ? [startIndex, endIndex] : [endIndex, startIndex];

            for (let i = start; i <= end; i++) {
                this.selectRow(rows[i]);
            }
        } else {
            if (!this.options.multiselect) {
                // Clear all other selections
                this.clearAllSelections();
            }

            if (isSelected) {
                this.deselectRow(row);
            } else {
                this.selectRow(row);
            }
        }

        // Always update last selected row (even if deselected, but usually only on select)
        if (!isSelected || this.shiftKeyDown) {
            this.lastSelectedRow = row;
        }

        this.emit('selectionChanged', { selectedRows: this.getSelectedRows() });
    }

    selectRow(row) {
        FTableDOMHelper.addClass(row, 'ftable-row-selected');
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (checkbox) checkbox.checked = true;
        
        const keyValue = this.getKeyValue(row.recordData);
        if (keyValue) this.state.selectedRecords.add(keyValue);
    }

    deselectRow(row) {
        FTableDOMHelper.removeClass(row, 'ftable-row-selected');
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (checkbox) checkbox.checked = false;
        
        const keyValue = this.getKeyValue(row.recordData);
        if (keyValue) this.state.selectedRecords.delete(keyValue);
    }

    recalcColumnWidths() {
        this.columnList.forEach(fieldName => {
            const field = this.options.fields[fieldName];
            const th = this.elements.table.querySelector(`[data-field-name="${fieldName}"]`);
            if (th && field.width) {
                th.style.width = field.width;
            }
        });
        // Trigger reflow
        this.elements.table.offsetHeight;
    }

    recalcColumnWidthsOnce() {
        if (!this._recalculatedOnce) {
            this.recalcColumnWidths();
            this._recalculatedOnce = true;
        }
    }

    clearAllSelections() {
        const selectedRows = this.elements.tableBody.querySelectorAll('.ftable-row-selected');
        selectedRows.forEach(row => this.deselectRow(row));
    }

    toggleSelectAll(selectAll) {
        const rows = this.elements.tableBody.querySelectorAll('.ftable-data-row');
        rows.forEach(row => {
            if (selectAll) {
                this.selectRow(row);
            } else {
                this.deselectRow(row);
            }
        });
        
        this.emit('selectionChanged', { selectedRows: this.getSelectedRows() });
    }

    getSelectedRows() {
        return Array.from(this.elements.tableBody.querySelectorAll('.ftable-row-selected'));
    }

    // Sorting Methods
    sortByColumn(fieldName, isCtrlPressed = false) {
        const field = this.options.fields[fieldName];

        if (!field || field.sorting === false) return;

        const existingSortIndex = this.state.sorting.findIndex(s => s.fieldName === fieldName);
        let isSorted = true;
        let newDirection = 'ASC';
        if (existingSortIndex >= 0) {
            const wasAsc = this.state.sorting[existingSortIndex].direction === 'ASC';
            if (wasAsc) {
                newDirection = 'DESC';
                this.state.sorting[existingSortIndex].direction = newDirection;
            } else {
                this.state.sorting.splice(existingSortIndex,1);
                isSorted = false;
            }
        } else {
            this.state.sorting.push({ fieldName, direction: newDirection });
        }

        if (this.options.multiSorting) {
            // If multiSorting is enabled, respect multiSortingCtrlKey
            if (this.options.multiSortingCtrlKey && !isCtrlPressed) {
                // Not using Ctrl → treat as single sort (clear others)
                this.state.sorting = isSorted ? [{ fieldName, direction: newDirection }] : [];
            }
        } else {
            // If multiSorting is disabled, always clear other sorts
            this.state.sorting = isSorted ? [{ fieldName, direction: newDirection }] : [];
        }

        this.updateSortingHeaders();
        this.load();
        this.saveState();
    }

    updateSortingHeaders() {
        // Clear all sorting classes
        const headers = this.elements.table.querySelectorAll('.ftable-column-header-sortable');
        headers.forEach(header => {
            FTableDOMHelper.removeClass(header, 'ftable-column-header-sorted-asc ftable-column-header-sorted-desc');
        });
        
        // Apply current sorting classes
        this.state.sorting.forEach(sort => {
            const header = this.elements.table.querySelector(`[data-field-name="${sort.fieldName}"]`);
            if (header) {
                FTableDOMHelper.addClass(header, `ftable-column-header-sorted-${sort.direction.toLowerCase()}`);
            }
        });
    }

    // Paging Methods
    updatePagingInfo() {
        if (!this.options.paging || !this.elements.pageInfoSpan) return;
        
        if (this.state.totalRecordCount <= 0) {
            this.elements.pageInfoSpan.textContent = '';
            this.elements.pagingListArea.innerHTML = '';
            return;
        }

        // Update page info
        const startRecord = (this.state.currentPage - 1) * this.state.pageSize + 1;
        const endRecord = Math.min(this.state.currentPage * this.state.pageSize, this.state.totalRecordCount);
        
        const pagingInfoMsg = this.options.messages.pagingInfo || 'Showing {0}-{1} of {2}';
        // Format with placeholders
        this.elements.pageInfoSpan.textContent = pagingInfoMsg
            .replace(/\{0\}/g, startRecord)
            .replace(/\{1\}/g, endRecord)
            .replace(/\{2\}/g, this.state.totalRecordCount);

        // Update page navigation
        this.createPageListNavigation();
        this.createPageGotoNavigation();
    }

    createPageListNavigation() {
        if (!this.elements.pagingListArea) return;
        
        this.elements.pagingListArea.innerHTML = '';
        
        const totalPages = Math.ceil(this.state.totalRecordCount / this.state.pageSize);
        if (totalPages <= 1) return;

        // First and Previous buttons
        this.createPageButton('&laquo;', 1, this.state.currentPage === 1, 'ftable-page-number-first');
        this.createPageButton('&lsaquo;', this.state.currentPage - 1, this.state.currentPage === 1, 'ftable-page-number-previous');

        // Page numbers
        if (this.options.pageList == 'normal') {
            const pageNumbers = this.calculatePageNumbers(totalPages);
            let lastNumber = 0;

            pageNumbers.forEach(pageNum => {
                if (pageNum - lastNumber > 1) {
                    FTableDOMHelper.create('span', {
                        className: 'ftable-page-number-space',
                        textContent: '...',
                        parent: this.elements.pagingListArea
                    });
                }

                this.createPageButton(
                    pageNum.toString(), 
                    pageNum, 
                    false, 
                    pageNum === this.state.currentPage ? 'ftable-page-number ftable-page-number-active' : 'ftable-page-number'
                );

                lastNumber = pageNum;
            });
        }

        // Next and Last buttons
        this.createPageButton('&rsaquo;', this.state.currentPage + 1, this.state.currentPage >= totalPages, 'ftable-page-number-next');
        this.createPageButton('&raquo;', totalPages, this.state.currentPage >= totalPages, 'ftable-page-number-last');
    }

    createPageGotoNavigation() {
        if (!this.options.paging || this.options.gotoPageArea === 'none') {
            this.elements.pagingGotoArea.style.display = 'none';
            this.elements.pagingGotoArea.innerHTML = '';
            return;
        }

        const totalPages = Math.ceil(this.state.totalRecordCount / this.state.pageSize);
        if (totalPages <= 1) {
            this.elements.pagingGotoArea.style.display = 'none';
            this.elements.pagingGotoArea.innerHTML = '';
            return;
        }

        this.elements.pagingGotoArea.style.display = 'inline-block';
        this.elements.pagingGotoArea.innerHTML = ''; // Clear

        // Label
        const label = FTableDOMHelper.create('span', {
            textContent: this.options.messages.gotoPageLabel + ': ',
            parent: this.elements.pagingGotoArea
        });

        const gotoPageInputId = `ftable-goto-page-${this.options.tableId || 'default'}`;

        if (this.options.gotoPageArea === 'combobox') {
            // --- COMBOBOX (dropdown) ---
            this.elements.gotoPageSelect = FTableDOMHelper.create('select', {
                id: gotoPageInputId,
                className: 'ftable-page-goto-select',
                parent: this.elements.pagingGotoArea
            });

            for (let i = 1; i <= totalPages; i++) {
                FTableDOMHelper.create('option', {
                    attributes: { value: i },
                    textContent: i,
                    parent: this.elements.gotoPageSelect
                });
            }

            this.elements.gotoPageSelect.value = this.state.currentPage;

            this.elements.gotoPageSelect.addEventListener('change', (e) => {
                const page = parseInt(e.target.value);
                if (page >= 1 && page <= totalPages) {
                    this.changePage(page);
                }
            });

        } else if (this.options.gotoPageArea === 'textbox') {
            // --- TEXTBOX (number input) ---
            this.elements.gotoPageInput = FTableDOMHelper.create('input', {
                attributes: {
                    type: 'number',
                    id: gotoPageInputId,
                    min: '1',
                    max: totalPages,
                    value: this.state.currentPage,
                    className: 'ftable-page-goto-input',
                    style: 'width: 65px; margin-left: 4px;',
                },
                parent: this.elements.pagingGotoArea
            });

            // Handle change (user types, uses spinner, or presses Enter)
            this.elements.gotoPageInput.addEventListener('change', (e) => {
                const page = parseInt(e.target.value);
                if (page >= 1 && page <= totalPages) {
                    this.changePage(page);
                } else {
                    e.target.value = this.state.currentPage; // Revert if invalid
                }
            });
        }
    }

    createPageButton(text, pageNumber, disabled, className) {
        const button = FTableDOMHelper.create('span', {
            className: className + (disabled ? ' ftable-page-number-disabled' : ''),
            innerHTML: text,
            parent: this.elements.pagingListArea
        });

        if (!disabled) {
            button.style.cursor = 'pointer';
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.changePage(pageNumber);
            });
        }
    }

    calculatePageNumbers(totalPages) {
        if (totalPages <= 7) {
            return Array.from({ length: totalPages }, (_, i) => i + 1);
        }

        const current = this.state.currentPage;
        const pages = new Set([1, 2, totalPages - 1, totalPages]);
        
        // Add current page and neighbors
        for (let i = Math.max(1, current - 1); i <= Math.min(totalPages, current + 1); i++) {
            pages.add(i);
        }
        
        return Array.from(pages).sort((a, b) => a - b);
    }

    changePage(pageNumber) {
        const totalPages = Math.ceil(this.state.totalRecordCount / this.state.pageSize);
        pageNumber = Math.max(1, Math.min(pageNumber, totalPages));
        
        if (pageNumber === this.state.currentPage) return;
        
        this.state.currentPage = pageNumber;
        this.load();
    }

    changePageSize(newSize) {
        this.state.pageSize = newSize;
        this.state.currentPage = 1; // Reset to first page
        this.load();
        this.saveState();
    }

    // Utility Methods
    showLoadingIndicator() {
        if (this.options.loadingAnimationDelay === 0) {
            if (this.modals.loading) {
                this.modals.loading.show();
            }
            return;
        }

        this.loadingTimeout = setTimeout(() => {
            if (this.modals.loading) {
                this.modals.loading.show();
            }
            this.loadingShownAt = Date.now(); // Track when shown
        }, this.options.loadingAnimationDelay || 500);
    }

    hideLoadingIndicator() {
        if (this.loadingTimeout) {
            clearTimeout(this.loadingTimeout);
            this.loadingTimeout = null;
        }

        const minDisplayTime = 200;
        const timeShown = this.loadingShownAt ? (Date.now() - this.loadingShownAt) : 0;

        if (this.modals.loading) {
            if (timeShown < minDisplayTime) {
                setTimeout(() => {
                    this.modals.loading.hide();
                }, minDisplayTime - timeShown);
            } else {
                this.modals.loading.hide();
            }
        }

        this.loadingShownAt = null;
    }

    showError(message) {
        if (this.modals.error) {
            this.modals.error.setContent(`<p>${message}</p>`);
            this.modals.error.show();
        } else {
            alert(message); // Fallback
        }
    }

    showInfo(message) {
        if (this.modals.info) {
            this.modals.info.setContent(`<p>${message}</p>`);
            this.modals.info.show();
        } else {
            alert(message); // Fallback
        }
    }

    // Public API Methods
    reload(preserveSelection = false) {
        this.clearListCache();
        if (preserveSelection) {
            this.preservedSelections = new Set(this.state.selectedRecords);
        } else {
            this.state.selectedRecords.clear();
        }

        return this.load().then(() => {
            if (preserveSelection && this.preservedSelections) {
                this.restoreSelections();
                this.preservedSelections = null;
            }
            return this;
        });
    }

    clearListCache() {
        if (this.options.actions.listAction && typeof this.options.actions.listAction === 'string') {
            this.formBuilder.optionsCache.clear(this.options.actions.listAction);
        }
    }

    restoreSelections() {
        if (!this.preservedSelections) return;

        const rows = this.elements.tableBody.querySelectorAll('.ftable-data-row');
        rows.forEach(row => {
            const keyValue = this.getKeyValue(row.recordData);
            if (keyValue && this.preservedSelections.has(keyValue)) {
                this.selectRow(row);
            }
        });
        return this;
    }

    getRowByKey(key) {
        return this.elements.tableBody.querySelector(`[data-record-key="${key}"]`);
    }

    destroy() {
        // Remove the instance reference from the DOM
        if (this.element && this.element.ftableInstance) {
            this.element.ftableInstance = null;
        }

        // Clean up modals
        Object.values(this.modals).forEach(modal => modal.destroy());
        
        // Remove main container
        if (this.elements.mainContainer) {
            this.elements.mainContainer.remove();
        }

        // Clean timeouts and listeners
        this.searchTimeout && clearTimeout(this.searchTimeout);
        this.loadingTimeout && clearTimeout(this.loadingTimeout);
        window.removeEventListener('resize', this.handleResize);
        
        // Clear state
        this.options = null;
        this.state = null;
        this.elements = null;
        this.formBuilder = null;
        this.modals = null;
    }

    // Chainable method for setting options
    setOption(key, value) {
        this.options[key] = value;
        return this;
    }

    // Method to get current state
    getState() {
        return { ...this.state };
    }

    // Advanced filtering and search
    addFilter(fieldName, value, operator = 'equals') {
        if (!this.state.filters) {
            this.state.filters = [];
        }
        
        // Remove existing filter for this field
        this.state.filters = this.state.filters.filter(f => f.fieldName !== fieldName);
        
        if (value !== null && value !== undefined && value !== '') {
            this.state.filters.push({ fieldName, value, operator });
        }
        
        return this;
    }

    clearFilters() {
        this.state.filters = [];
        return this;
    }

    // CSV Export functionality
    exportToCSV(filename = 'table-data.csv') {
        // Create a clean table clone from the DOM
        const tableClone = this.elements.table.cloneNode(true);
        const csvRows = [];

        // Helper to format CSV cell (escape quotes, wrap in quotes)
        const formatCSV = (text) => {
            const str = String(text || '').replace(/"/g, '""');
                return `"${str}"`;
            };

        // 1. Extract headers from visible, non-command columns
        const headerCells = tableClone.querySelectorAll('thead th');
        const headerRow = [];
        for (const th of headerCells) {
            if (th.classList.contains('ftable-command-column-header') ||
                th.classList.contains('ftable-toolbarsearch-column-header') ||
                th.style.display === 'none') {
                continue;
            }
            const text = th.textContent.trim();
            headerRow.push(formatCSV(text));
        }
        csvRows.push(headerRow.join(','));

        // 2. Extract rows from visible, non-command cells
        const dataRows = tableClone.querySelectorAll('tbody tr');
        for (const tr of dataRows) {
            // Skip hidden rows
            if (tr.style.display === 'none') {
                continue;
            }

            const rowCells = tr.querySelectorAll('td');
            const csvRow = [];
            let hasData = false;

            for (const td of rowCells) {
                if (td.classList.contains('ftable-command-column') ||
                    td.style.display === 'none') {
                    continue;
                }

                // Clean up: remove buttons, images
                if (td.querySelector('img, button, input, select')) {
                    td.innerHTML = td.textContent; // Strip HTML
                }

                // Replace <br> with \n
                const html = td.innerHTML;
                const withLineBreaks = html.replace(/<br\s*\/?>/gi, '\n');
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = withLineBreaks;
                const text = tempDiv.textContent || '';

                csvRow.push(formatCSV(text));
                hasData = true;
            }

            if (hasData) {
                csvRows.push(csvRow.join(','));
            }
        }

        const csvContent = csvRows.join('\n');

        // Create and trigger download
        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        link.remove();
    }

    // Print functionality
    printTable() {
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        const tableHtml = this.elements.table.outerHTML;
        
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${this.options.title || 'Table Data'}</title>
                <style>
                    body { margin: 0; padding: 10px; font-size: 10px; }
                    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                    th, td { border: 1px solid #000; padding: 4px; word-wrap: break-word; }
                    tr {
                        page-break-inside: avoid;
                        break-inside: avoid;
                    }
                    th { background: #eee; }
                    .no-print { display: none; }
                    .ftable-toolbarsearch, .ftable-toolbarsearch-reset-button { display: none; }
                    .ftable-command-column { display: none !important; }
                    .ftable-command-column-header { display: none !important; }
                    .ftable-selecting-column { display: none !important; }
                    .ftable-column-header-select { display: none !important; }
                    .ftable-toolbarsearch-column-header { display: none !important; }
                </style>
            </head>
            <body>
                <h1>${this.options.title || 'Table Data'}</h1>
                ${tableHtml}
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(() => window.close(), 100);
                    };
                </script>
            </body>
            </html>
        `;
        
        printWindow.document.write(printContent);
        printWindow.document.close();
    }

    // Bulk operations
    async bulkDelete(confirmMessage = 'Delete selected records?') {
        const selectedRows = this.getSelectedRows();
        if (selectedRows.length === 0) {
            this.showError('No records selected');
            return;
        }

        if (!confirm(confirmMessage)) return;

        const keyValues = selectedRows.map(row => this.getKeyValue(row.recordData));
        const results = [];

        for (const keyValue of keyValues) {
            try {
                const result = await this.performDelete(keyValue);
                results.push({ key: keyValue, success: result.Result === 'OK', result });
            } catch (error) {
                results.push({ key: keyValue, success: false, error: error.message });
            }
        }

        // Remove successful deletions from table
        const successfulDeletes = results.filter(r => r.success);
        successfulDeletes.forEach(({ key }) => {
            const row = this.getRowByKey(key);
            if (row) this.removeRowFromTable(row);
        });

        // Show summary
        const failed = results.filter(r => !r.success).length;
        if (failed > 0) {
            this.showError(`${failed} of ${results.length} records could not be deleted`);
        }

        // this.emit('bulkDelete', { results: results, successful: successfulDeletes.length, failed: failed });
    }

    // Column management
    showColumn(fieldName) {
        this.setColumnVisibility(fieldName, true);
    }

    hideColumn(fieldName) {
        this.setColumnVisibility(fieldName, false);
    }

    setColumnVisibility(fieldName, visible) {
        const field = this.options.fields[fieldName];
        if (!field) return;

        // Don't allow hiding sorted columns
        if (!visible && this.isFieldSorted(fieldName)) {
            this.showError(`Cannot hide column "${field.title || fieldName}" because it is currently sorted`);
            return;
        }

        // Don't allow changing fixed columns
        if (field.visibility === 'fixed') {
            return;
        }

        field.visibility = visible ? 'visible' : 'hidden';
        
        // Update existing table
        const columnIndex = this.columnList.indexOf(fieldName);
        if (columnIndex >= 0) {
            // Calculate actual column index (accounting for selecting column)
            let actualIndex = columnIndex + 1; // CSS nth-child is 1-based
            if (this.options.selecting && this.options.selectingCheckboxes) {
                actualIndex += 1; // Account for selecting column
            }
            
            const selector = `th:nth-child(${actualIndex}), td:nth-child(${actualIndex})`;
            const cells = this.elements.table.querySelectorAll(selector);
            
            cells.forEach(cell => {
                if (visible) {
                    FTableDOMHelper.show(cell);
                } else {
                    FTableDOMHelper.hide(cell);
                }
            });
        }

        // Save column settings
        if (this.options.saveUserPreferences) {
            this.saveColumnSettings();
            this.saveState(); // sorting might affect state
        }

        // Hide the column selection menu
        //this.hideColumnSelectionMenu();

        // Emit event
        // this.emit('columnVisibilityChanged', { field: field });
    }

    // Responsive helpers
/*
    makeResponsive() {
        // Add responsive classes and behavior
        FTableDOMHelper.addClass(this.elements.mainContainer, 'ftable-responsive');
        
        // Handle window resize
        const handleResize = () => {
            const containerWidth = this.elements.mainContainer.offsetWidth;
            
            if (containerWidth < 768) {
                FTableDOMHelper.addClass(this.elements.table, 'ftable-mobile');
                this.handleMobileView();
            } else {
                FTableDOMHelper.removeClass(this.elements.table, 'ftable-mobile');
                this.handleDesktopView();
            }
        };

        window.addEventListener('resize', handleResize);
        handleResize(); // Initial call
    }

    handleMobileView() {
        // In mobile view, could stack cells vertically or hide less important columns
        // This is a simplified version
        const lessPriorityColumns = this.columnList.filter(fieldName => {
            const field = this.options.fields[fieldName];
            return field.priority === 'low' || field.mobileHidden === true;
        });

        lessPriorityColumns.forEach(fieldName => {
            this.setColumnVisibility(fieldName, false);
        });
    }

    handleDesktopView() {
        // Restore all columns in desktop view
        this.columnList.forEach(fieldName => {
            const field = this.options.fields[fieldName];
            if (field.visibility !== 'hidden') {
                this.setColumnVisibility(fieldName, true);
            }
        });
    }

    // Advanced search functionality
    enableSearch(options = {}) {
        const searchOptions = {
            placeholder: 'Search...',
            debounceMs: 300,
            searchFields: this.columnList,
            ...options
        };

        const searchContainer = FTableDOMHelper.create('div', {
            className: 'ftable-search-container',
            parent: this.elements.toolbarDiv
        });

        const searchInput = FTableDOMHelper.create('input', {
            attributes: {
                type: 'text',
                placeholder: searchOptions.placeholder,
                class: 'ftable-search-input'
            },
            parent: searchContainer
        });

        // Debounced search
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value, searchOptions.searchFields);
            }, searchOptions.debounceMs);
        });

        return searchInput;
    }

    async performSearch(query, searchFields) {
        if (!query.trim()) {
            return this.load(); // Clear search
        }

        const searchParams = {
            search: query,
            searchFields: searchFields.join(',')
        };

        return this.load(searchParams);
    }

    // Keyboard shortcuts
    enableKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Only handle shortcuts when table has focus or is active
            if (!this.elements.mainContainer.contains(document.activeElement)) return;

            switch (e.key) {
                case 'n':
                    if (e.ctrlKey && this.options.actions.createAction) {
                        e.preventDefault();
                        this.showAddRecordForm();
                    }
                    break;
                case 'r':
                    if (e.ctrlKey) {
                        e.preventDefault();
                        this.reload();
                    }
                    break;
                case 'Delete':
                    if (this.options.actions.deleteAction) {
                        const selectedRows = this.getSelectedRows();
                        if (selectedRows.length > 0) {
                            e.preventDefault();
                            this.bulkDelete();
                        }
                    }
                    break;
                case 'a':
                    if (e.ctrlKey && this.options.selecting && this.options.multiselect) {
                        e.preventDefault();
                        this.toggleSelectAll(true);
                    }
                    break;
                case 'Escape':
                    // Close any open modals
                    Object.values(this.modals).forEach(modal => {
                        if (modal.isOpen) modal.close();
                    });
                    break;
            }
        });
    }

    // Real-time updates via WebSocket
    enableRealTimeUpdates(websocketUrl) {
        if (!websocketUrl) return;

        this.websocket = new WebSocket(websocketUrl);
        
        this.websocket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.handleRealTimeUpdate(data);
            } catch (error) {
                this.logger.error('Failed to parse WebSocket message', error);
            }
        };

        this.websocket.onerror = (error) => {
            this.logger.error('WebSocket error', error);
        };

        this.websocket.onclose = () => {
            this.logger.info('WebSocket connection closed');
            // Attempt to reconnect after delay
            setTimeout(() => {
                if (this.websocket.readyState === WebSocket.CLOSED) {
                    this.enableRealTimeUpdates(websocketUrl);
                }
            }, 5000);
        };
    }

    handleRealTimeUpdate(data) {
        switch (data.type) {
            case 'record_added':
                this.addRecordToTable(data.record);
                break;
            case 'record_updated':
                this.updateRecordInTable(data.record);
                break;
            case 'record_deleted':
                this.removeRecordFromTable(data.recordKey);
                break;
            case 'refresh':
                this.reload();
                break;
        }
    }

    addRecordToTable(record) {
        const row = this.createTableRow(record);
        
        // Add to beginning or end based on sorting
        if (this.state.sorting.length > 0) {
            // Would need to calculate correct position based on sort
            this.elements.tableBody.appendChild(row);
        } else {
            this.elements.tableBody.appendChild(row);
        }
        
        this.state.records.push(record);
        this.removeNoDataRow();
        this.refreshRowStyles();
        
        // Show animation
        if (this.options.animationsEnabled) {
            this.showRowAnimation(row, 'added');
        }
    }

    updateRecordInTable(record) {
        const keyValue = this.getKeyValue(record);
        const existingRow = this.getRowByKey(keyValue);
        
        if (existingRow) {
            this.updateRowData(existingRow, record);
            
            if (this.options.animationsEnabled) {
                this.showRowAnimation(existingRow, 'updated');
            }
        }
    }

    removeRecordFromTable(keyValue) {
        const row = this.getRowByKey(keyValue);
        if (row) {
            this.removeRowFromTable(row);
            
            // Remove from state
            this.state.records = this.state.records.filter(r => 
                this.getKeyValue(r) !== keyValue
            );
        }
    }

    showRowAnimation(row, type) {
        const animationClass = `ftable-row-${type}`;
        FTableDOMHelper.addClass(row, animationClass);
        
        setTimeout(() => {
            FTableDOMHelper.removeClass(row, animationClass);
        }, 2000);
    }

    // Plugin system for extensions
    use(plugin, options = {}) {
        if (typeof plugin === 'function') {
            plugin(this, options);
        } else if (plugin && typeof plugin.install === 'function') {
            plugin.install(this, options);
        }
        return this;
    }

    // Event delegation for dynamic content
    delegate(selector, event, handler) {
        this.elements.mainContainer.addEventListener(event, (e) => {
            const target = e.target.closest(selector);
            if (target) {
                handler.call(target, e);
            }
        });
        return this;
    }
    */

    editRecordByKey(keyValue) {
        const row = this.getRowByKey(keyValue);
        if (row) {
            this.editRecord(row);
        } else {
            this.showError(`Record with key '${keyValue}' not found`);
        }
    }

    async editRecordViaAjax(recordId, url, params = {}) {
        try {
            // Get the actual key field name (e.g., 'asset_id', 'user_id', etc.)
            const keyFieldName = this.keyField;
            if (!keyFieldName) {
                throw new Error('No key field defined in fTable options');
            }

            // Build parameters using the correct key field name
            const fullParams = { 
                [keyFieldName]: recordId, 
                ...params 
            };

            const response = this.options.forcePost
                ? await FTableHttpClient.post(url, fullParams)
                : await FTableHttpClient.get(url, fullParams);

            if (!response || !response.Record) {
                throw new Error('Invalid response or missing Record');
            }

            const record = response.Record;

            // Find or create a row
            const row = this.getRowByKey(recordId) || FTableDOMHelper.create('tr', {
                className: 'ftable-data-row',
                attributes: { 'data-record-key': recordId }
            });

            row.recordData = { ...record };

            // Open the edit form
            await this.editRecord(row);
        } catch (error) {
            this.showError('Failed to load record for editing.');
            this.logger.error(`editRecordViaAjax failed: ${error.message}`);
        }
    }
    
    openChildTable(parentRow, childOptions, onInit) {
        // Close any open child tables if accordion mode
        if (this.options.openChildAsAccordion) {
            this.closeAllChildTables();
        }
        // Prevent multiple child tables
        this.closeChildTable(parentRow);

        // Create container for child table
        const childContainer = FTableDOMHelper.create('tr', {
            className: 'ftable-child-row'
        });

        const cell = FTableDOMHelper.create('td', {
            attributes: { colspan: this.getVisibleColumnCount() },
            parent: childContainer
        });

        // Create the child table wrapper
        const childWrapper = FTableDOMHelper.create('div', {
            className: 'ftable-child-table-container',
            parent: cell
        });

        // Insert after parent row
        parentRow.parentNode.insertBefore(childContainer, parentRow.nextSibling);

        // Store reference
        parentRow.childRow = childContainer;
        childContainer.parentRow = parentRow;

        // Initialize child table
        const childTable = new FTable(childWrapper, {
            ...childOptions,
            // Inherit some parent settings
            paging: childOptions.paging !== undefined ? childOptions.paging : true,
            pageSize: childOptions.pageSize || 10,
            sorting: childOptions.sorting !== undefined ? childOptions.sorting : true,
            selecting: false,
            toolbarsearch: true,
            messages: {
                ...this.options.messages,
                ...childOptions.messages
            }
        });

        // Hook into close events
        const originalClose = childTable.close;
        childTable.close = () => {
            this.closeChildTable(parentRow);
        };

        // Init and load
        childTable.init();
        if (onInit) onInit(childTable);

        // Store reference
        parentRow.childTable = childTable;

        return childTable;
    }

    closeChildTable(parentRow) {
        if (parentRow.childRow) {
            if (parentRow.childTable && typeof parentRow.childTable.destroy === 'function') {
                parentRow.childTable.destroy();
            }
            parentRow.childRow.remove();
            parentRow.childRow = null;
            parentRow.childTable = null;
        }
    }

    closeAllChildTables() {
        Object.values(this.elements.tableRows).forEach(row => {
            if (row.childTable) {
                this.closeChildTable(row);
            }
        });
    }

    getSortingInfo() {
        // Build sorted fields list with translated directions
        const messages = this.options.messages || {};
        const sortingInfo = this.state.sorting.map(s => {
            const field = this.options.fields[s.fieldName];
            const title = field?.title || s.fieldName;

            // Translate direction
            const directionText = s.direction === 'ASC'
                ? (messages.ascending || 'ascending')
                : (messages.descending || 'descending');

            return `${title} (${directionText})`;
        }).join(', ');
        return sortingInfo;
    }

    renderSortingInfo() {
        if (!this.options.sortingInfoSelector || !this.options.sorting) return;

        const container = document.querySelector(this.options.sortingInfoSelector);
        if (!container) return;

        // Clear existing content
        container.innerHTML = '';

        const messages = this.options.messages || {};

        // Get prefix/suffix if defined
        const prefix = messages.sortingInfoPrefix ? `<span class="ftable-sorting-prefix">${messages.sortingInfoPrefix}</span> ` : '';
        const suffix = messages.sortingInfoSuffix ? ` <span class="ftable-sorting-suffix">${messages.sortingInfoSuffix}</span>` : '';

        if (this.state.sorting.length === 0) {
            container.innerHTML = messages.sortingInfoNone || '';
            return;
        }

        // Build sorted fields list with translated directions
        const sortingInfo = this.getSortingInfo();

        // Combine with prefix and suffix
        container.innerHTML = `${prefix}${sortingInfo}${suffix}`;

        // Add reset sorting button
        if (this.state.sorting.length > 0) {
            const resetSortBtn = document.createElement('button');
            resetSortBtn.textContent = messages.resetSorting || 'Reset Sorting';
            resetSortBtn.style.marginLeft = '10px';
            resetSortBtn.classList.add('ftable-sorting-reset-btn');
            resetSortBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.state.sorting = [];
                this.updateSortingHeaders();
                this.load();
                this.saveState();
            });
            container.appendChild(resetSortBtn);
        }

        // Add reset table button if enabled
        if (this.options.tableReset) {
            const resetTableBtn = document.createElement('button');
            resetTableBtn.textContent = messages.resetTable || 'Reset Table';
            resetTableBtn.style.marginLeft = '10px';
            resetTableBtn.classList.add('ftable-table-reset-btn');
            resetTableBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const confirmMsg = messages.resetTableConfirm;
                if (confirm(confirmMsg)) {
                    this.userPrefs.remove('column-settings');
                    this.userPrefs.remove('table-state');

                    // Clear any in-memory state that might affect rendering
                    this.state.sorting = [];
                    this.state.pageSize = this.options.pageSize;

                    // Reset field visibility to default
                    this.columnList.forEach(fieldName => {
                        const field = this.options.fields[fieldName];
                        // Reset to default: hidden only if explicitly set
                        field.visibility = field.visibility === 'fixed' ? 'fixed' : 'visible';
                    });
                    location.reload();
                }
            });
            container.appendChild(resetTableBtn);
        }
    }

    /**
     * Waits for a specific option value to be available in a select field.
     * Useful for pre-filling forms with async-loaded options.
     *
     * @param {string} fieldName - The name of the field
     * @param {string|number} value - The option value to wait for
     * @param {Function} callback - Called when the option is available
     * @param {Object} [options] - Optional settings
     * @param {HTMLElement} [options.form] - Form to search in (default: current form)
     * @param {number} [options.timeout=5000] - Max wait time in ms
     */
    static _waitForFieldReady(fieldName, form, callback, timeout = 5000) {
        if (!form) {
            console.warn(`FTable: No form provided for waitForFieldReady('${fieldName}')`);
            return;
        }

        const select = form.querySelector(`[name="${fieldName}"]`);
        if (!select || select.tagName !== 'SELECT') {
            console.warn(`FTable: Field '${fieldName}' not found or not a <select>`);
            return;
        }

        // Wait for more than just placeholder/loading option
        if (select.options.length > 1) {
            callback();
            return;
        }

        // Observe when real options are added
        const observer = new MutationObserver(() => {
            if (select.options.length > 1) {
                observer.disconnect();
                callback();
            }
        });

        observer.observe(select, { childList: true });

        // Timeout fallback
        if (timeout > 0) {
            setTimeout(() => {
                observer.disconnect();
                if (select.options.length > 1) {
                    callback();
                } else {
                    console.warn(`FTable: Timeout waiting for field '${fieldName}' to load options`);
                }
            }, timeout);
        }
    }
    static async waitForFieldReady(fieldName, form, timeout = 5000) {
        return new Promise((resolve) => {
            FTable._waitForFieldReady(fieldName, form, resolve, timeout);
        });
    }
}


    // Expose classes globally
    global.FTable = FTable;
    global.FtableModal = FtableModal;
    global.FTableHttpClient = FTableHttpClient;

    // For CommonJS
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = FTable;
        module.exports.FtableModal = FtableModal;
        module.exports.FTableHttpClient = FTableHttpClient;
    }
}(typeof globalThis !== 'undefined' ? globalThis :
  typeof window !== 'undefined' ? window :
  typeof global !== 'undefined' ? global : this));
