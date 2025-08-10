document.addEventListener('DOMContentLoaded', function () {
    const FormfieldsTableContainer = $('#FormfieldsTableContainer');
    let FormfieldsTable;

    // --- Field Type Groups ---
    const multiValueTypes = [
        'dropdown',
        'dropdown_multi',
        'radiobox',
        'radiobox_vertical',
        'checkbox',
        'checkbox_vertical',
        'datalist'
    ];

    // List of fields that change format based on type
    const fields = [
        'field_values',
        'field_tags',
        'admin_values',
        'admin_tags'
    ];

    // --- Format Helpers ---
    function formatToTextarea(value) {
        let converted = (value || '').replace(/^\|\|/, '\n').replace(/\|\|/g, '\n');
        if (value?.startsWith('||')) {
            converted = '\n' + converted;
        }
        return converted;
    }

    function formatToInput(value) {
        return (value || '').replace(/\r\n|\r|\n/g, '||');
    }

    // --- Update Field Inputs Based on Type ---
    function updateFieldInputs() {
        const fieldTypeSelect = $('#field_type');
        if (!fieldTypeSelect) return;

        const selectedType = fieldTypeSelect.value;
        const isMulti = multiValueTypes.includes(selectedType);

        fields.forEach(fieldId => {
            const container = $(`#${fieldId}_container`);
            if (!container) return;

            const existingInput = container.querySelector('input, textarea');
            const currentValue = existingInput ? existingInput.value : '';

            // Re-render the input
            if (isMulti) {
                const textareaVal = formatToTextarea(currentValue);
                container.innerHTML = `
                    <textarea name="${fieldId}" id="${fieldId}" rows="5" cols="40">${textareaVal}</textarea>
                `;
            } else {
                const inputVal = formatToInput(currentValue);
                container.innerHTML = `
                    <input type="text" name="${fieldId}" id="${fieldId}" size="40" value="${inputVal}" />
                `;
            }
        });
    }

    // --- Conditional Row Visibility ---
    function updateRowVisibility() {
        const fieldTypeSelect = $('#field_type');
        const fieldPurposeSelect = $('#field_purpose') || $('#field_purpose_hidden');
        if (!fieldTypeSelect || !fieldPurposeSelect) return;

        const fieldType = fieldTypeSelect.value;
        const fieldPurpose = fieldPurposeSelect.value;

        // Hide all conditional rows first
        const rows = {
            extra_charge: $('#tr_extra_charge'),
            field_tags: $('#tr_field_tags'),
            admin_tags: $('#tr_admin_tags'),
            field_values: $('#tr_field_values'),
            admin_values: $('#tr_admin_values'),
            searchable: $('#tr_searchable'),
            field_condition: $('#tr_field_condition'),
            export: $('#tr_export')
        };

        // Reset display
        Object.values(rows).forEach(row => {
            if (row) eme_toggle(row,true);
        });

        // Apply rules
        if (fieldType === 'file') {
            eme_toggle(rows.extra_charge, false);
            eme_toggle(rows.field_tags, false);
            eme_toggle(rows.admin_tags, false);
            eme_toggle(rows.field_values, false);
            eme_toggle(rows.admin_values, false);
            eme_toggle(rows.searchable, false);
        } else {
            if (fieldPurpose === 'people') {
                eme_toggle(rows.extra_charge, false);
            }
            // Otherwise, default visibility applies
        }

        if (fieldPurpose === 'people') {
            eme_toggle(rows.field_condition,true);
            eme_toggle(rows.export, true);
        } else {
            eme_toggle(rows.field_condition, false);
            eme_toggle(rows.export, false);
        }
    }

    // --- Initialize Dynamic Field Behavior ---
    const fieldTypeSelect = $('#field_type');
    const fieldPurposeSelect = $('#field_purpose') || $('#field_purpose_hidden');

    if (fieldTypeSelect) {
        fieldTypeSelect.addEventListener('change', () => {
            updateFieldInputs();
            updateRowVisibility();
        });
    }

    if (fieldPurposeSelect) {
        fieldPurposeSelect.addEventListener('change', updateRowVisibility);
    }

    // Initial setup
    updateFieldInputs();
    updateRowVisibility();

    // --- Initialize Form Fields Table ---
    if (FormfieldsTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'fieldstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        FormfieldsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        FormfieldsTable = new FTable('#FormfieldsTableContainer', {
            title: emeformfields.translate_formfields,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'field_name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl,
                deleteAction: ajaxurl + '?action=eme_manage_formfields&do_action=deleteFormfield&eme_admin_nonce=' + emeformfields.translate_adminnonce
            },
            listQueryParams: () => ({
                action: 'eme_formfields_list',
                search_name: $('#search_name')?.value || '',
                search_type: $('#search_type')?.value || '',
                search_purpose: $('#search_purpose')?.value || '',
                eme_admin_nonce: emeformfields.translate_adminnonce
            }),
            fields: {
                field_id: {
                    key: true,
                    title: emeformfields.translate_id,
                    width: '1%',
                    columnResizable: false,
                    visibility: 'hidden'
                },
                field_name: {
                    title: emeformfields.translate_name,
                    visibility: 'fixed',
                },
                copy: {
                    title: emeformfields.translate_copy,
                    sorting: false,
                    width: '2%',
                    listClass: 'eme-jtable-center'
                },
                field_type: {
                    title: emeformfields.translate_type
                },
                field_required: {
                    title: emeformfields.translate_required,
                    width: '2%'
                },
                field_purpose: {
                    title: emeformfields.translate_purpose
                },
                extra_charge: {
                    title: emeformfields.translate_extracharge,
                    visibility: 'hidden'
                },
                searchable: {
                    title: emeformfields.translate_searchable,
                    visibility: 'hidden'
                },
                used: {
                    title: emeformfields.translate_used,
                    sorting: false,
                    width: '2%'
                }
            },
            sortingInfoSelector: '#fieldstablesortingInfo',
            messages: { sortingInfoNone: '' }
        });
        FormfieldsTable.load();
    }

    // --- Bulk Actions ---
    const actionsButton = $('#FormfieldsActionsButton');
    if (actionsButton) {
        actionsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = FormfieldsTable.getSelectedRows();
            const doAction = $('#eme_admin_action').value;
            if (selectedRows.length === 0 || !doAction) return;

            // only one action
            if (!confirm(emeformfields.translate_areyousuretodeleteselected)) return;

            actionsButton.textContent = emeformfields.translate_pleasewait;
            actionsButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('field_id', idsJoined);
            formData.append('action', 'eme_manage_formfields');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emeformfields.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                FormfieldsTable.load();
                actionsButton.textContent = emeformfields.translate_apply;
                actionsButton.disabled = false;
                const msg = $('div#formfields-message');
                if (msg) {
                    msg.textContent = data.Message;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                }
            });
        });
    }

    // --- Reload Button ---
    const loadButton = $('#FormfieldsLoadRecordsButton');
    if (loadButton) {
        loadButton.addEventListener('click', e => {
            e.preventDefault();
            FormfieldsTable.load();
        });
    }
});
