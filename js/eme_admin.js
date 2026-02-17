// Main functions
function eme_activateTab(target) {
    EME.$$('.eme-tab').forEach(tab => tab.classList.remove('active'));
    EME.$$('.eme-tab-content').forEach(content => content.classList.remove('active'));

    const targetTab = EME.$(`.eme-tab[data-tab="${target}"]`);
    const targetContent = EME.$(`#${target}`);

    if (targetTab) targetTab.classList.add('active');
    if (targetContent) targetContent.classList.add('active');

    if (target === "tab-locationdetails" && emeadmin.translate_map_is_active === 'true') {
        setTimeout(() => {
            eme_SelectdisplayAddress();
            eme_displayAddress(0);
        }, 100);
    }

    if (target === "tab-mailings") {
        setTimeout(() => {
            const container = EME.$('#MailingsTableContainer');
            if (container && container.ftableInstance) {
                //container.ftableInstance.recalcColumnWidthsOnce();
                const loadButton = EME.$('#MailingsLoadRecordsButton');
                if (loadButton) loadButton.click();
            }
        }, 100);
    }

    if (target === "tab-mailingsarchive") {
        setTimeout(() => {
            const container = EME.$('#ArchivedMailingsTableContainer');
            if (container && container.ftableInstance) {
                //container.ftableInstance.recalcColumnWidthsOnce();
                const loadButton = EME.$('#ArchivedMailingsLoadRecordsButton');
                if (loadButton) loadButton.click();
            }
        }, 100);
    }

    if (target === "tab-allmail") {
        setTimeout(() => {
            const container = EME.$('#MailsTableContainer');
            if (container && container.ftableInstance) {
                //container.ftableInstance.recalcColumnWidthsOnce();
                const loadButton = EME.$('#MailsLoadRecordsButton');
                if (loadButton) loadButton.click();
            }
        }, 100);
    }
}

// Task management functions
function eme_add_task_function(element) {
    const selectedItem = element.closest('tr');
    const metaCopy = selectedItem.cloneNode(true);
    let newId = 0;
    while (EME.$(`#eme_row_task_${newId}`)) newId++;

    const currentId = metaCopy.id.replace('eme_row_task_', '');
    metaCopy.id = `eme_row_task_${newId}`;

    const relElements = metaCopy.querySelectorAll('a');
    relElements.forEach(a => a.setAttribute('rel', newId));

    // Remove signup_count field
    const signupCount = metaCopy.querySelector(`[name="eme_tasks[${currentId}][signup_count]"]`);
    if (signupCount) signupCount.remove();

    const metafields = ['task_id', 'name', 'task_start', 'task_end', 'spaces', 'dp_task_start', 'dp_task_end', 'description'];
    metafields.forEach(f => {
        const field = metaCopy.querySelector(`[name="eme_tasks[${currentId}][${f}]"]`);
        if (field) {
            field.name = `eme_tasks[${newId}][${f}]`;
            field.id = `eme_tasks[${newId}][${f}]`;
        }
    });

    // Update data-alt-field attributes
    const dpStart = metaCopy.querySelector(`[name="eme_tasks[${newId}][dp_task_start]"]`);
    const dpEnd = metaCopy.querySelector(`[name="eme_tasks[${newId}][dp_task_end]"]`);
    if (dpStart) dpStart.setAttribute('data-alt-field', `eme_tasks[${newId}][task_start]`);
    if (dpEnd) dpEnd.setAttribute('data-alt-field', `eme_tasks[${newId}][task_end]`);

    // Clear values
    const nameField = metaCopy.querySelector(`[name="eme_tasks[${newId}][name]"]`);
    const spacesField = metaCopy.querySelector(`[name="eme_tasks[${newId}][spaces]"]`);
    const descField = metaCopy.querySelector(`[name="eme_tasks[${newId}][description]"]`);
    const taskIdField = metaCopy.querySelector(`[name="eme_tasks[${newId}][task_id]"]`);

    if (nameField) nameField.value = '';
    if (spacesField) spacesField.value = '1';
    if (descField) descField.value = '';
    if (taskIdField && taskIdField.parentNode) taskIdField.parentNode.innerHTML = '';

    const tbody = EME.$('#eme_tasks_tbody');
    if (tbody) tbody.appendChild(metaCopy);

    // Initialize date picker for added row
    eme_init_widgets();

    // Set existing dates
    const currentStart = metaCopy.querySelector(`[name="eme_tasks[${newId}][task_start]"]`)?.value;
    if (currentStart) {
        const jsStartObj = new Date(currentStart);
        const dpStartField = metaCopy.querySelector(`[name="eme_tasks[${newId}][dp_task_start]"]`);
        if (dpStartField && dpStartField._fdatepicker) {
            dpStartField._fdatepicker.setDate(jsStartObj);
        }
    }

    const currentEnd = metaCopy.querySelector(`[name="eme_tasks[${newId}][task_end]"]`)?.value;
    if (currentEnd) {
        const jsEndObj = new Date(currentEnd);
        const dpEndField = metaCopy.querySelector(`[name="eme_tasks[${newId}][dp_task_end]"]`);
        if (dpEndField && dpEndField._fdatepicker) {
            dpEndField._fdatepicker.setDate(jsEndObj);
        }
    }
}

function eme_remove_task_function(element) {
    const tbody = EME.$('#eme_tasks_tbody');
    const rows = tbody ? tbody.children : [];

    if (rows.length > 1) {
        element.closest('tr').remove();
    } else {
        const metaCopy = element.closest('tr');
        let newId = 0;
        while (EME.$(`#eme_row_task_${newId}`)) newId++;

        const currentId = metaCopy.id.replace('eme_row_task_', '');
        metaCopy.id = `eme_row_task_${newId}`;

        const relElements = metaCopy.querySelectorAll('a');
        relElements.forEach(a => a.setAttribute('rel', newId));

        const metafields = ['task_id', 'name', 'task_start', 'task_end', 'spaces', 'dp_task_start', 'dp_task_end', 'description'];
        metafields.forEach(f => {
            const field = metaCopy.querySelector(`[name="eme_tasks[${currentId}][${f}]"]`);
            if (field) {
                field.name = `eme_tasks[${newId}][${f}]`;
                field.id = `eme_tasks[${newId}][${f}]`;
            }
        });

        // Update data-alt-field attributes
        const dpStart = metaCopy.querySelector(`[name="eme_tasks[${newId}][dp_task_start]"]`);
        const dpEnd = metaCopy.querySelector(`[name="eme_tasks[${newId}][dp_task_end]"]`);
        if (dpStart) dpStart.setAttribute('data-alt-field', `eme_tasks[${newId}][task_start]`);
        if (dpEnd) dpEnd.setAttribute('data-alt-field', `eme_tasks[${newId}][task_end]`);

        // Clear values
        const nameField = metaCopy.querySelector(`[name="eme_tasks[${newId}][name]"]`);
        const spacesField = metaCopy.querySelector(`[name="eme_tasks[${newId}][spaces]"]`);
        const descField = metaCopy.querySelector(`[name="eme_tasks[${newId}][description]"]`);
        const taskIdField = metaCopy.querySelector(`[name="eme_tasks[${newId}][task_id]"]`);

        if (nameField) nameField.value = '';
        if (spacesField) spacesField.value = '1';
        if (descField) descField.value = '';
        if (taskIdField && taskIdField.parentNode) taskIdField.parentNode.innerHTML = '';

        // Remove required attributes
        metafields.forEach(f => {
            const field = metaCopy.querySelector(`[name="eme_tasks[${newId}][${f}]"]`);
            if (field) field.removeAttribute('required');
        });
    }
}

// Todo management functions
function eme_add_todo_function(element) {
    const selectedItem = element.closest('tr');
    const metaCopy = selectedItem.cloneNode(true);
    let newId = 0;
    while (EME.$(`#eme_row_todo_${newId}`)) newId++;

    const currentId = metaCopy.id.replace('eme_row_todo_', '');
    metaCopy.id = `eme_row_todo_${newId}`;

    const relElements = metaCopy.querySelectorAll('a');
    relElements.forEach(a => a.setAttribute('rel', newId));

    const metafields = ['todo_id', 'name', 'todo_offset', 'description'];
    metafields.forEach(f => {
        const field = metaCopy.querySelector(`[name="eme_todos[${currentId}][${f}]"]`);
        if (field) {
            field.name = `eme_todos[${newId}][${f}]`;
            field.id = `eme_todos[${newId}][${f}]`;
        }
    });

    // Clear values
    const nameField = metaCopy.querySelector(`[name="eme_todos[${newId}][name]"]`);
    const offsetField = metaCopy.querySelector(`[name="eme_todos[${newId}][todo_offset]"]`);
    const descField = metaCopy.querySelector(`[name="eme_todos[${newId}][description]"]`);
    const todoIdField = metaCopy.querySelector(`[name="eme_todos[${newId}][todo_id]"]`);

    if (nameField) nameField.value = '';
    if (offsetField) offsetField.value = '0';
    if (descField) descField.value = '';
    if (todoIdField && todoIdField.parentNode) todoIdField.parentNode.innerHTML = '';

    const tbody = EME.$('#eme_todos_tbody');
    if (tbody) tbody.appendChild(metaCopy);
}

function eme_remove_todo_function(element) {
    const tbody = EME.$('#eme_todos_tbody');
    const rows = tbody ? tbody.children : [];

    if (rows.length > 1) {
        element.closest('tr').remove();
    } else {
        const metaCopy = element.closest('tr');
        let newId = 0;
        while (EME.$(`#eme_row_todo_${newId}`)) newId++;

        const currentId = metaCopy.id.replace('eme_row_todo_', '');
        metaCopy.id = `eme_row_todo_${newId}`;

        const relElements = metaCopy.querySelectorAll('a');
        relElements.forEach(a => a.setAttribute('rel', newId));

        const metafields = ['todo_id', 'name', 'todo_offset', 'description'];
        metafields.forEach(f => {
            const field = metaCopy.querySelector(`[name="eme_todos[${currentId}][${f}]"]`);
            if (field) {
                field.name = `eme_todos[${newId}][${f}]`;
                field.id = `eme_todos[${newId}][${f}]`;
            }
        });

        // Clear values
        const nameField = metaCopy.querySelector(`[name="eme_todos[${newId}][name]"]`);
        const offsetField = metaCopy.querySelector(`[name="eme_todos[${newId}][todo_offset]"]`);
        const descField = metaCopy.querySelector(`[name="eme_todos[${newId}][description]"]`);
        const todoIdField = metaCopy.querySelector(`[name="eme_todos[${newId}][todo_id]"]`);

        if (nameField) nameField.value = '';
        if (offsetField) offsetField.value = '0';
        if (descField) descField.value = '';
        if (todoIdField && todoIdField.parentNode) todoIdField.parentNode.innerHTML = '';

        // Remove required attributes
        metafields.forEach(f => {
            const field = metaCopy.querySelector(`[name="eme_todos[${newId}][${f}]"]`);
            if (field) field.removeAttribute('required');
        });
    }
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
                    selection.map(function(attach) {
                        const attachment = attach.toJSON();
                        if (links) {
                            links.innerHTML += `<a target='_blank' href='${attachment.url}'>${attachment.title}</a><br>`;
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
        const preferredTab = tabsContainer.dataset.showtab;
        if (preferredTab) {
            eme_activateTab(preferredTab);
        } else if ($_GET['page'] && $_GET['page']=='eme-emails') {
            eme_activateTab('tab-genericmails');
        } else {
            const firstTab = EME.$('.eme-tab');
            if (firstTab) {
                eme_activateTab(firstTab.dataset.tab);
            }
        }
    }

    // Input placeholder sizing
    EME.$$("input[placeholder]").forEach(input => {
        const placeholder = input.getAttribute('placeholder');
        const size = parseInt(input.getAttribute('size')) || 0;
        if (placeholder && placeholder.length > size) {
            input.setAttribute('size', placeholder.length);
        }
    });

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

    const changeTaskDaysBtn = EME.$('#change_task_days');
    if (changeTaskDaysBtn) {
        changeTaskDaysBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const offset = parseInt(EME.$('#task_offset').value);
            let myId = 0;

            while (document.querySelector(`[name="eme_tasks[${myId}][task_start]"]`)) {
                const dpStartField = document.querySelector(`[name="eme_tasks[${myId}][dp_task_start]"]`);
                if (dpStartField && dpStartField._fdatepicker) {
                    const startObj = dpStartField._fdatepicker.selectedDate;
                    startObj.setDate(startObj.getDate() + offset);
                    dpStartField._fdatepicker.setDate(startObj);
                }

                const dpEndField = document.querySelector(`[name="eme_tasks[${myId}][dp_task_end]"]`);
                if (dpEndField && dpEndField._fdatepicker) {
                    const endObj = dpEndField._fdatepicker.selectedDate;
                    endObj.setDate(endObj.getDate() + offset);
                    dpEndField._fdatepicker.setDate(endObj);
                }
                myId++;
            }
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

    initSnapSelectRemote('.eme_select2_members_class', {
        allowEmpty: true,
        placeholder: emeadmin.translate_selectmembers,
        data: {
            action: 'eme_members_select2',
            eme_admin_nonce: emeadmin.translate_adminnonce,
        }
    });
    initSnapSelectRemote('.eme_select2_people_class', {
        allowEmpty: true,
        placeholder: emeadmin.translate_selectpersons,
        data: {
            action: 'eme_people_select2',
            eme_admin_nonce: emeadmin.translate_adminnonce,
        }
    });
    initSnapSelectRemote('.eme_select2_discounts_class', {
        allowEmpty: true,
        placeholder: emeadmin.translate_selectdiscount,
        data: {
            action: 'eme_discounts_select2',
            eme_admin_nonce: emeadmin.translate_adminnonce,
        }
    });
    initSnapSelectRemote('.eme_select2_dgroups_class', {
        allowEmpty: true,
        placeholder: emeadmin.translate_selectdiscountgroup,
        data: {
            action: 'eme_dgroups_select2',
            eme_admin_nonce: emeadmin.translate_adminnonce,
        }
    });
   
    document.addEventListener('click', (e) => {
        if (e.target.matches('.eme-dismiss-notice')) {
            e.preventDefault();
            const notice = e.target.dataset.notice;
            const noticeDiv = e.target.closest('.notice');

            const formData = new URLSearchParams({
                action: 'eme_dismiss_notice',
                notice: notice,
                eme_admin_nonce: emeadmin.translate_adminnonce || ''
            });

            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            }).then(response => response.json()).then(response => {
                if (response.success && noticeDiv) {
                    noticeDiv.style.transition = 'opacity 300ms';
                    noticeDiv.style.opacity = '0';
                    setTimeout(() => eme_toggle(noticeDiv, false), 300);
                }
            });
        }
        if (e.target.matches('.eme_add_todo')) {
            e.preventDefault();
            eme_add_todo_function(e.target);
        }
        if (e.target.matches('.eme_remove_todo')) {
            e.preventDefault();
            eme_remove_todo_function(e.target);
        }
        if (e.target.matches('.eme_add_task')) {
            e.preventDefault();
            eme_add_task_function(e.target);
        }
        if (e.target.matches('.eme_remove_task')) {
            e.preventDefault();
            eme_remove_task_function(e.target);
        }
        if (e.target.matches('.eme_dyndata_add_tag')) {
            e.preventDefault();
            const tbody = EME.$('#eme_dyndata_tbody');
            const metas = tbody.children;
            const metaCopy = metas[0].cloneNode(true);
            let newId = 0;
            while (EME.$(`#eme_dyndata_${newId}`)) newId++;

            const currentId = metaCopy.id.replace('eme_dyndata_', '');
            metaCopy.id = `eme_dyndata_${newId}`;

            const relElements = metaCopy.querySelectorAll('a');
            relElements.forEach(a => a.setAttribute('rel', newId));

            const metafields = ['field', 'condition', 'condval', 'template_id_header', 'template_id', 'template_id_footer', 'repeat', 'grouping'];
            metafields.forEach(f => {
                const field = metaCopy.querySelector(`[name="eme_dyndata[${currentId}][${f}]"]`);
                if (field) {
                    field.name = `eme_dyndata[${newId}][${f}]`;
                    field.id = `eme_dyndata[${newId}][${f}]`;
                }
            });

            // Set default values
            const fieldField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][field]"]`);
            const conditionField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][condition]"]`);
            const condvalField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][condval]"]`);
            const headerField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][template_id_header]"]`);
            const templateField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][template_id]"]`);
            const footerField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][template_id_footer]"]`);
            const repeatField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][repeat]"]`);
            const groupingField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][grouping]"]`);

            if (fieldField) fieldField.value = '';
            if (conditionField) conditionField.value = 'eq';
            if (condvalField) condvalField.value = '';
            if (headerField) headerField.value = '0';
            if (templateField) templateField.value = '0';
            if (footerField) footerField.value = '0';
            if (repeatField) repeatField.value = '0';
            if (groupingField && groupingField.parentNode) groupingField.parentNode.innerHTML = '';

            tbody.appendChild(metaCopy);
        }
 
        // DynData remove functionality
        if (e.target.matches('.eme_remove_dyndatacondition')) {
            e.preventDefault();
            const tbody = EME.$('#eme_dyndata_tbody');
            const rows = tbody.children;

            if (rows.length > 1) {
                e.target.closest('tr').remove();
            } else {
                const metaCopy = e.target.closest('tr');
                let newId = 0;
                while (EME.$(`#eme_dyndata_${newId}`)) newId++;

                const currentId = metaCopy.id.replace('eme_dyndata_', '');
                metaCopy.id = `eme_dyndata_${newId}`;

                const relElements = metaCopy.querySelectorAll('a');
                relElements.forEach(a => a.setAttribute('rel', newId));

                const metafields = ['field', 'condition', 'condval', 'template_id_header', 'template_id', 'template_id_footer', 'repeat', 'grouping'];
                metafields.forEach(f => {
                    const field = metaCopy.querySelector(`[name="eme_dyndata[${currentId}][${f}]"]`);
                    if (field) {
                        field.name = `eme_dyndata[${newId}][${f}]`;
                        field.id = `eme_dyndata[${newId}][${f}]`;
                    }
                });

                // Clear values and remove required attributes
                const fieldField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][field]"]`);
                const conditionField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][condition]"]`);
                const condvalField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][condval]"]`);
                const headerField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][template_id_header]"]`);
                const templateField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][template_id]"]`);
                const footerField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][template_id_footer]"]`);
                const repeatField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][repeat]"]`);
                const groupingField = metaCopy.querySelector(`[name="eme_dyndata[${newId}][grouping]"]`);

                if (fieldField) fieldField.value = '';
                if (conditionField) conditionField.value = 'eq';
                if (condvalField) condvalField.value = '';
                if (headerField) headerField.value = '0';
                if (templateField) templateField.value = '0';
                if (footerField) footerField.value = '0';
                if (repeatField) repeatField.value = '0';
                if (groupingField && groupingField.parentNode) groupingField.parentNode.innerHTML = '';

                metafields.forEach(f => {
                    const field = metaCopy.querySelector(`[name="eme_dyndata[${newId}][${f}]"]`);
                    if (field) field.removeAttribute('required');
                });
            }
        }

        if (e.target.matches('.eme_iban_button')) {
            e.preventDefault();
            const formData = new URLSearchParams({
                action: 'eme_get_bancontactwero_iban',
                pg_pid: e.target.dataset.pg_pid,
                eme_admin_nonce: emeadmin.translate_adminnonce
            });
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            }).then(response => response.json()).then(response => {
                const paymentbutton = EME.$('#button_'+response.payment_id);
                if (paymentbutton) eme_toggle(paymentbutton, false);
                const paymentspan = EME.$('span#bancontactwero_'+response.payment_id);
                if (paymentspan) paymentspan.innerHTML=response.iban;
            });
            // return false to make sure the real form doesn't submit
            return false;
        }
        if (e.target.matches('.eme_del_upload-button')) {
            e.preventDefault();
            if (confirm(emeadmin.translate_areyousuretodeletefile || 'Are you sure you want to delete this file?')) {
                const id = e.target.dataset.id;
                const name = e.target.dataset.name;
                const type = e.target.dataset.type;
                const randomId = e.target.dataset.random_id;
                const fieldId = e.target.dataset.field_id;
                const extraId = e.target.dataset.extra_id;

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
    EME.$$('details summary').forEach(summary => {
        const details = summary.parentNode;
        const wrapper = document.createElement('div');

        // Wrap all content after summary in a div
        let nextSibling = summary.nextSibling;
        while (nextSibling) {
            const current = nextSibling;
            nextSibling = nextSibling.nextSibling;
            if (current !== summary) {
                wrapper.appendChild(current);
            }
        }
        details.appendChild(wrapper);

        if (!details.hasAttribute('open')) {
            eme_toggle(wrapper, false);
        }

        summary.addEventListener('click', (e) => {
            e.preventDefault();
            if (details.hasAttribute('open')) {
                wrapper.style.transition = `height 300ms ease`;
                wrapper.style.overflow = 'hidden';
                wrapper.style.height = wrapper.offsetHeight + 'px';
                
                requestAnimationFrame(() => {
                    wrapper.style.height = '0';
                });
                
                setTimeout(() => {
                    eme_toggle(wrapper, false);
                    details.removeAttribute('open');
                }, 300);
            } else {
                details.setAttribute('open', 'true');
                eme_toggle(wrapper, true);
                const height = wrapper.scrollHeight;
                wrapper.style.height = '0';
                wrapper.style.overflow = 'hidden';
                wrapper.style.transition = `height 300ms ease, opacity 300ms`;
                wrapper.style.opacity = '0';

                requestAnimationFrame(() => {
                    wrapper.style.height = height + 'px';
                    wrapper.style.opacity = '1';
                });

                setTimeout(() => {
                    wrapper.style.height = '';
                    wrapper.style.overflow = '';
                    wrapper.style.transition = '';
                }, 300);
            }
        });
    });
});
