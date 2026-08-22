// Main functions

// Fires a fetch()-based ajax POST and hands the parsed JSON response to callback.
function eme_postJSON(url, data, callback, onError = null, onFinally = null) {
    if (emeadmin.translate_locale && data instanceof FormData && !data.has('lang')) {
        data.append('lang', emeadmin.translate_locale);
    }
    fetch(url, {
        method: 'POST',
        body: data,
        credentials: 'same-origin'
    })
        .then(r => r.json())
        .then(callback)
        .catch(err => {
            console.error('AJAX request failed');
            if (onError) onError(err);
        })
        .finally(() => {
            if (onFinally) onFinally();
        });
}

// Builds & submits a real (non-ajax) POST form — used when the response must either
// navigate the browser (e.g. sendMails) or trigger a file download (e.g. pdf/html),
// neither of which fetch()-based eme_postJSON can do.
function eme_submit_hidden_form(url, fields) {
    if (emeadmin.translate_locale && !('lang' in fields)) {
        fields = { ...fields, lang: emeadmin.translate_locale };
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    Object.entries(fields).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    form.remove();
}

function eme_show_ftable_bulk_result(myftable, result) {
    if (result?.Result === 'ERROR') {
        myftable.showError(result.htmlmessage);
    } else if (result?.Result === 'WARNING') {
        myftable.showWarning(result.htmlmessage);
    } else {
        myftable.showInfo(result?.htmlmessage);
    }
}

function eme_activateTab(target) {
    EME.$$('.eme-tab').forEach(tab => tab.classList.remove('active'));
    EME.$$('.eme-tab-content').forEach(content => content.classList.remove('active'));

    const targetTab = EME.$(`.eme-tab[data-tab="${target}"]`);
    const targetContent = EME.$(`#${target}`);

    if (targetTab) targetTab.classList.add('active');
    if (targetContent) targetContent.classList.add('active');

    // Update URL hash for hash-based tab navigation
    //if (target && window.history && window.history.replaceState) {
    //    history.replaceState(null, '', '#' + target);
    //}

    if (target === "tab-locationdetails" && emeadmin.translate_map_is_active === 'true') {
        setTimeout(() => {
            eme_SelectdisplayAddress();
            eme_displayAddress(0);
        }, 100);
    }

    // Lazy-load: the ftable belonging to a tab only fetches its data once that tab becomes active.
    const tabTableContainerIds = {
        'tab-mailings':        'MailingsTableContainer',
        'tab-mailingsarchive': 'ArchivedMailingsTableContainer',
        'tab-allmail':         'MailsTableContainer',
        'tab-events':          'EventsTableContainer',
        'tab-recurrences':    'RecurrencesTableContainer',
        'tab-eventstrash':     'TrashTableContainer',
        'tab-people':          'PeopleTableContainer',
        'tab-groups':          'GroupsTableContainer',
        'tab-peopletrash':     'TrashedPeopleTableContainer',
        'tab-countries':       'CountriesTableContainer',
        'tab-states':          'StatesTableContainer',
        'tab-discounts':       'DiscountsTableContainer',
        'tab-dgroups':         'DiscountGroupsTableContainer',
    };

    const containerId = tabTableContainerIds[target];
    if (containerId) {
        setTimeout(() => {
            const container = EME.$(`#${containerId}`);
            if (container && container.ftableInstance) {
                container.ftableInstance.load();
            }
        }, 100);
    }
}

// Generic add/remove for table rows cloned from a <template> element
function eme_next_row_index(prefix) {
    let idx = 0;
    while (EME.$(`#${prefix}${idx}`)) idx++;
    return idx;
}

function eme_add_templated_row(tbodyId, templateId, rowPrefix, token = '__IDX__') {
    const tbody = EME.$('#' + tbodyId);
    const template = EME.$('#' + templateId);
    if (!tbody || !template) return null;

    const idx = eme_next_row_index(rowPrefix);
    const row = template.content.firstElementChild.cloneNode(true);
    row.id = `${rowPrefix}${idx}`;
    row.querySelectorAll('input,select,textarea').forEach(el => {
        ['name', 'id'].forEach(attr => {
            const val = el.getAttribute(attr);
            if (val) el.setAttribute(attr, val.replaceAll(token, idx));
        });
    });
    tbody.appendChild(row);
    return { row, idx };
}

function eme_remove_templated_row(row, tbodyId, templateId, rowPrefix) {
    if (!row) return;
    row.remove();
    // keep at least one row in the table
    const tbody = EME.$('#' + tbodyId);
    if (tbody && !tbody.querySelector('tr')) eme_add_templated_row(tbodyId, templateId, rowPrefix);
}

// Task management functions
function eme_add_task_function() {
    if (eme_add_templated_row('eme_tasks_tbody', 'eme_tasks_template', 'eme_row_task_')) {
        eme_init_widgets(true);
    }
}

function eme_remove_task_function(element) {
    eme_remove_templated_row(element.closest('tr'), 'eme_tasks_tbody', 'eme_tasks_template', 'eme_row_task_');
}

// Custom-field filter rows (members/people/events/locations/tasks/rsvp search forms)
function eme_add_customfieldfilter_function(button) {
    const containerId = button.dataset.target;
    const container = EME.$('#' + containerId);
    const template  = EME.$('#' + containerId + '_template');
    if (!container || !template) return;
    const rowCopy = template.content.firstElementChild.cloneNode(true);
    container.appendChild(rowCopy);
    eme_init_widgets(true);
}

function eme_remove_customfieldfilter_function(button) {
    const container = button.closest('.eme_cf_filters');
    if (!container) return;
    const rows = container.querySelectorAll('.eme_cf_filter_row');
    button.closest('.eme_cf_filter_row').remove();
}

// Reads a .eme_cf_filters container's rows into 3 index-aligned arrays for listQueryParams/store payloads.
// Skips rows where no field is selected.
function eme_get_customfieldfilter_values(containerId) {
    const container = EME.$('#' + containerId);
    const fieldids = [], values = [], exacts = [];
    if (!container) return { fieldids, values, exacts };
    container.querySelectorAll('.eme_cf_filter_row').forEach(row => {
        const fieldSelect = row.querySelector('.eme_cf_filter_field');
        const fieldId = fieldSelect ? eme_getValue(fieldSelect) : '';
        if (!fieldId) return;
        fieldids.push(fieldId);
        values.push(row.querySelector('.eme_cf_filter_value')?.value || '');
        exacts.push(row.querySelector('.eme_cf_filter_exact_input')?.checked ? 1 : 0);
    });
    return { fieldids, values, exacts };
}

// Todo management functions
function eme_add_todo_function() {
    eme_add_templated_row('eme_todos_tbody', 'eme_todos_template', 'eme_row_todo_');
}

function eme_remove_todo_function(element) {
    eme_remove_templated_row(element.closest('tr'), 'eme_todos_tbody', 'eme_todos_template', 'eme_row_todo_');
}

// DynData condition management functions
function eme_add_dyndatacondition_function() {
    eme_add_templated_row('eme_dyndata_tbody', 'eme_dyndata_template', 'eme_dyndata_');
}

function eme_remove_dyndatacondition_function(element) {
    eme_remove_templated_row(element.closest('tr'), 'eme_dyndata_tbody', 'eme_dyndata_template', 'eme_dyndata_');
}

// Attachment UI initialization function
function eme_admin_init_attachment_ui(btnSelector, linksSelector, idsSelector, removeBtnSelector) {
    const btn = EME.$(btnSelector);
    const links = EME.$(linksSelector);
    const ids = EME.$(idsSelector);
    const removeBtn = EME.$(removeBtnSelector);

    if (btn) {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (window.wp && window.wp.media) {
                const customUploader = window.wp.media({
                    title: emeadmin.translate_addattachments || 'Add attachments',
                    button: { text: emeadmin.translate_addattachments || 'Add attachments' },
                    multiple: true
                }).on('select', function() {
                    const selection = customUploader.state().get('selection');
                    links.replaceChildren();
                    selection.map(function(attach) {
                        const attachment = attach.toJSON();
                        if (links) {
                            const a = document.createElement('a');
                            a.target = '_blank';
                            a.href = attachment.url;
                            a.textContent = attachment.title;
                            links.appendChild(a);
                            links.appendChild(document.createElement('br'));
                        }
                        if (ids) {
                            const idsArr = ids.value ? ids.value.split(',') : [];
                            idsArr.push(attachment.id);
                            ids.value = idsArr.join(',');
                        }
                        if (removeBtn) {
                            eme_toggle(removeBtn, true);
                        }
                    });
                }).open();
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (links) links.innerHTML = '';
            if (ids) ids.value = '';
            eme_toggle(removeBtn, false);
        });

        // Set initial visibility
        if (ids) {
            eme_toggle(removeBtn, ids.value !== '');
        }
    }
}

// Main initialization
document.addEventListener('DOMContentLoaded', function () {
    // Tab binding and default activation
    EME.$$('.eme-tab').forEach(tab => {
        tab.addEventListener('click', (e) => {
            const target = e.target.dataset.tab;
            eme_activateTab(target);
        });
    });

    const tabsContainer = EME.$('.eme-tabs');
    if (tabsContainer) {
        // Priority: data-showtab attribute > URL hash > page-specific default > first tab
        const preferredTab = tabsContainer.dataset.showtab;
        const hashTab = window.location.hash ? window.location.hash.substring(1) : '';
        if (preferredTab) {
            eme_activateTab(preferredTab);
        } else if (hashTab && EME.$(`.eme-tab[data-tab="${hashTab}"]`)) {
            eme_activateTab(hashTab);
        } else if ($_GET['page'] && $_GET['page']=='eme-emails') {
            eme_activateTab('tab-genericmails');
        } else {
            const firstTab = EME.$('.eme-tab');
            if (firstTab) {
                eme_activateTab(firstTab.dataset.tab);
            }
        }
    }

    /*
    // Input placeholder sizing
    EME.$$("input[placeholder]").forEach(input => {
        const placeholder = input.getAttribute('placeholder');
        const size = parseInt(input.getAttribute('size')) || 0;
        if (placeholder && placeholder.length > size) {
            input.setAttribute('size', placeholder.length);
        }
    });
    */

    // Attribute metabox add/remove
    const attrAddBtn = EME.$('#eme_attr_add_tag');
    if (attrAddBtn) {
        attrAddBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const body = EME.$('#eme_attr_body');
            const metas = body.children;
            const metaCopy = metas[0].cloneNode(true);
            const newId = metas.length + 1;

            metaCopy.id = `eme_attr_${newId}`;
            const relElements = metaCopy.querySelectorAll('a');
            relElements.forEach(a => a.setAttribute('rel', newId));

            const refField = metaCopy.querySelector('[name=eme_attr_1_ref]');
            const contentField = metaCopy.querySelector('[name=eme_attr_1_content]');
            const nameField = metaCopy.querySelector('[name=eme_attr_1_name]');

            if (refField) {
                refField.name = `eme_attr_${newId}_ref`;
                refField.value = '';
            }
            if (contentField) {
                contentField.name = `eme_attr_${newId}_content`;
                contentField.value = '';
            }
            if (nameField) {
                nameField.name = `eme_attr_${newId}_name`;
                nameField.value = '';
            }

            body.appendChild(metaCopy);
        });
    }

    // Attribute removal
    const attrBody = EME.$('#eme_attr_body');
    if (attrBody) {
        attrBody.addEventListener('click', (e) => {
            if (e.target.tagName === 'A') {
                e.preventDefault();
                const body = EME.$('#eme_attr_body');
                const children = Array.from(body.children);

                if (children.length > 1) {
                    e.target.closest('tr').remove();
                    // Renumber remaining items
                    Array.from(body.children).forEach((child, id) => {
                        const newId = id + 1;
                        const oldId = child.id.replace('eme_attr_', '');
                        child.id = `eme_attr_${newId}`;

                        const relElements = child.querySelectorAll('a');
                        relElements.forEach(a => a.setAttribute('rel', newId));

                        const refField = child.querySelector(`[name=eme_attr_${oldId}_ref]`);
                        const contentField = child.querySelector(`[name=eme_attr_${oldId}_content]`);
                        const nameField = child.querySelector(`[name=eme_attr_${oldId}_name]`);

                        if (refField) refField.name = `eme_attr_${newId}_ref`;
                        if (contentField) contentField.name = `eme_attr_${newId}_content`;
                        if (nameField) nameField.name = `eme_attr_${newId}_name`;
                    });
                } else {
                    const metaCopy = e.target.closest('tr');
                    const refField = metaCopy.querySelector('[name=eme_attr_1_ref]');
                    const contentField = metaCopy.querySelector('[name=eme_attr_1_content]');
                    const nameField = metaCopy.querySelector('[name=eme_attr_1_name]');

                    if (refField) refField.value = '';
                    if (contentField) contentField.value = '';
                    if (nameField) nameField.value = '';
                }
            }
        });
    }

    // DynData sortable initialization
    const dyndataTbody = EME.$('#eme_dyndata_tbody');
    if (dyndataTbody && window.Sortable) {
        new Sortable(dyndataTbody, {
            handle: '.eme-sortable-handle',
            onStart: (evt) => { evt.from.style.opacity = '0.6'; },
            onEnd: (evt) => { evt.from.style.opacity = '1'; }
        });
    }

    // Tasks & Todos sortable
    const tasksTbody = EME.$('#eme_tasks_tbody');
    if (tasksTbody && window.Sortable) {
        new Sortable(tasksTbody, {
            handle: '.eme-sortable-handle',
            onStart: (evt) => { evt.from.style.opacity = '0.6'; },
            onEnd: (evt) => { evt.from.style.opacity = '1'; }
        });
    }

    const todosTbody = EME.$('#eme_todos_tbody');
    if (todosTbody && window.Sortable) {
        new Sortable(todosTbody, {
            handle: '.eme-sortable-handle',
            onStart: (evt) => { evt.from.style.opacity = '0.6'; },
            onEnd: (evt) => { evt.from.style.opacity = '1'; }
        });
    }

    // change_task_days: shift all task dates by the given number of days
    const changeTaskDaysBtn = EME.$('#change_task_days');
    if (changeTaskDaysBtn) {
        changeTaskDaysBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const offset = parseInt(EME.$('#task_offset').value);
            EME.$$('#eme_tasks_tbody tr').forEach(tr => {
                ['task_start', 'task_end'].forEach(f => {
                    const field = tr.querySelector(`[name*="[${f}]"]`);
                    if (field?._fdatepicker && field._fdatepicker.selectedDate) {
                        const dateObj = field._fdatepicker.selectedDate;
                        dateObj.setDate(dateObj.getDate() + offset);
                        field._fdatepicker.setDate(dateObj);
                    }
                });
            });
        });
    }

    // Show/Hide Elements
    EME.$$('.showhidebutton').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const elname = e.target.dataset.showhide;
            const targetEl = EME.$(`#${elname}`);
            if (targetEl) {
                targetEl.classList.toggle('eme-hidden');
            }
        });
    });

    // Collapsible filter panels
    EME.$$('.eme-filters-toggle').forEach(btn => {
        const targetId = btn.dataset.showhide;
        const panel = EME.$(`#${targetId}`);
        if (!panel) return;

        btn.addEventListener('click', () => {
            const isOpen = panel.classList.contains('active');
            if (isOpen) {
                panel.classList.remove('active');
                btn.classList.remove('active');
            } else {
                panel.classList.add('active');
                btn.classList.add('active');
            }
        });
    });

    initSnapSelectRemote('.eme_snapselect_members_class', {
        showClearButton: true,
        placeholder: emeadmin.translate_selectmembers,
        data: {
            action: 'eme_members_snapselect',
            eme_admin_nonce: emeadmin.translate_adminnonce,
        }
    });
    initSnapSelectRemote('.eme_snapselect_people_class', {
        showClearButton: true,
        placeholder: emeadmin.translate_selectpersons,
        data: {
            action: 'eme_chooseperson_snapselect',
            eme_admin_nonce: emeadmin.translate_adminnonce,
        }
    });
    initSnapSelectRemote('.eme_snapselect_discounts_class', {
        showClearButton: true,
        placeholder: emeadmin.translate_selectdiscount,
        data: {
            action: 'eme_discounts_snapselect',
            eme_admin_nonce: emeadmin.translate_adminnonce,
        }
    });
    initSnapSelectRemote('.eme_snapselect_dgroups_class', {
        showClearButton: true,
        placeholder: emeadmin.translate_selectdiscountgroup,
        data: {
            action: 'eme_dgroups_snapselect',
            eme_admin_nonce: emeadmin.translate_adminnonce,
        }
    });
   
    document.addEventListener('click', async (e) => {
        if (e.target.matches('.eme-dismiss-notice')) {
            e.preventDefault();
            const notice = e.target.dataset.notice;
            const noticeDiv = e.target.closest('.notice');

            const formData = new FormData();
            formData.append('action', 'eme_dismiss_notice');
            formData.append('notice', notice);
            formData.append('eme_admin_nonce', emeadmin.translate_adminnonce || '');

            eme_postJSON(ajaxurl, formData, (response) => {
                if (response.success && noticeDiv) {
                    noticeDiv.style.transition = 'opacity 300ms';
                    noticeDiv.style.opacity = '0';
                    setTimeout(() => eme_toggle(noticeDiv, false), 300);
                }
            });
        }
        if (e.target.matches('.eme_add_todo')) {
            e.preventDefault();
            eme_add_todo_function();
        }
        if (e.target.matches('.eme_remove_todo')) {
            e.preventDefault();
            eme_remove_todo_function(e.target);
        }
        if (e.target.matches('.eme_add_task')) {
            e.preventDefault();
            eme_add_task_function();
        }
        if (e.target.matches('.eme_remove_task')) {
            e.preventDefault();
            eme_remove_task_function(e.target);
        }
        if (e.target.matches('.eme_cf_filter_add')) {
            e.preventDefault();
            eme_add_customfieldfilter_function(e.target);
        }
        if (e.target.matches('.eme_cf_filter_remove')) {
            e.preventDefault();
            eme_remove_customfieldfilter_function(e.target);
        }

        if (e.target.matches('.eme_dyndata_add_tag')) {
            e.preventDefault();
            eme_add_dyndatacondition_function();
        }

        // DynData remove functionality
        if (e.target.matches('.eme_remove_dyndatacondition')) {
            e.preventDefault();
            eme_remove_dyndatacondition_function(e.target);
        }

        if (e.target.matches('.eme_iban_button')) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'eme_get_bancontactwero_iban');
            formData.append('pg_pid', e.target.dataset.pg_pid);
            formData.append('eme_admin_nonce', emeadmin.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (response) => {
                const paymentbutton = EME.$('#button_'+response.payment_id);
                if (paymentbutton) eme_toggle(paymentbutton, false);
                const paymentspan = EME.$('span#bancontactwero_'+response.payment_id);
                if (paymentspan) paymentspan.innerHTML=response.iban;
            });
            // return false to make sure the real form doesn't submit
            return false;
        }

        // closest, so the click on the img matches the parent (that has del_upload-button)
        if (e.target.closest('.eme_del_upload-button')) {
            e.preventDefault();
            if (await FTable.confirm(emeadmin.translate_confirmdelete, emeadmin.translate_areyousuretodeletefile)) {
                const parentlink = e.target.closest('.eme_del_upload-button');
                const id = parentlink.dataset.id;
                const name = parentlink.dataset.name;
                const type = parentlink.dataset.type;
                const randomId = parentlink.dataset.random_id;
                const fieldId = parentlink.dataset.field_id;
                const extraId = parentlink.dataset.extra_id;

                const formData = new URLSearchParams({
                    id: id,
                    name: name,
                    type: type,
                    field_id: fieldId,
                    random_id: randomId,
                    extra_id: extraId,
                    action: 'eme_del_upload',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                });

                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                }).then(() => {
                    const span = EME.$(`span#span_${randomId}`);
                    if (span) {
                        if (span.parentNode.children.length === 2) {
                            const sibling = span.parentNode.querySelector('input');
                            if (sibling) eme_toggle(sibling, true);
                        }
                        span.remove();
                    }
                });
            }
        }
    });

    // Initialize attachment UIs
    eme_admin_init_attachment_ui('#booking_attach_button', '#booking_attach_links', '#eme_booking_attach_ids', '#booking_remove_attach_button');
    eme_admin_init_attachment_ui('#pending_attach_button', '#pending_attach_links', '#eme_pending_attach_ids', '#pending_remove_attach_button');
    eme_admin_init_attachment_ui('#paid_attach_button', '#paid_attach_links', '#eme_paid_attach_ids', '#paid_remove_attach_button');
    eme_admin_init_attachment_ui('#subscribe_attach_button', '#subscribe_attach_links', '#eme_subscribe_attach_ids', '#subscribe_remove_attach_button');
    eme_admin_init_attachment_ui('#fs_ipn_attach_button', '#fs_ipn_attach_links', '#eme_fs_ipn_attach_ids', '#fs_ipn_remove_attach_button');

    // Animate details/summary blocks
    EME.$$('.eme_accordion > summary').forEach(summary => {
        const detailsParent = summary.parentNode;
        // Find the content div after summary (should already exist)
        let contentDiv = detailsParent.querySelector('summary + div');

        // If no content div exists yet, create one
        if (!contentDiv) {
            contentDiv = document.createElement('div');
            let next = summary.nextSibling;
            while (next) {
                const current = next;
                next = next.nextSibling;
                contentDiv.appendChild(current);
            }
            detailsParent.appendChild(contentDiv);
        }

        if (!detailsParent.hasAttribute('open')) {
            eme_toggle(contentDiv, false);
        }

        summary.addEventListener('click', (e) => {
            e.preventDefault();
            if (detailsParent.hasAttribute('open')) {
                contentDiv.style.transition = `height 300ms ease`;
                contentDiv.style.overflow = 'hidden';
                contentDiv.style.height = contentDiv.offsetHeight + 'px';
                
                requestAnimationFrame(() => {
                    contentDiv.style.height = '0';
                });
                
                setTimeout(() => {
                    eme_toggle(contentDiv, false);
                    detailsParent.removeAttribute('open');
                }, 300);
            } else {
                detailsParent.setAttribute('open', 'true');
                eme_toggle(contentDiv, true);
                const height = contentDiv.scrollHeight;
                contentDiv.style.height = '0';
                contentDiv.style.overflow = 'hidden';
                contentDiv.style.transition = `height 300ms ease, opacity 300ms`;
                contentDiv.style.opacity = '0';

                requestAnimationFrame(() => {
                    contentDiv.style.height = height + 'px';
                    contentDiv.style.opacity = '1';
                });

                setTimeout(() => {
                    contentDiv.style.height = '';
                    contentDiv.style.overflow = '';
                    contentDiv.style.transition = '';
                }, 300);
            }
        });
    });
});
