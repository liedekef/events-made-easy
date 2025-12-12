document.addEventListener('DOMContentLoaded', function () {
    const TemplatesTableContainer = EME.$('#TemplatesTableContainer');
    let TemplatesTable;

    // --- ftable: Initialize Templates Table ---
    if (TemplatesTableContainer) {
        // Insert sorting info element before table container
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'templatestablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        TemplatesTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        TemplatesTable = new FTable('#TemplatesTableContainer', {
            title: emetemplates.translate_templates,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            deleteConfirmation: function(data) {
                data.deleteConfirmMessage = emetemplates.translate_pressdeletetoremove + ' "' + data.record.name + '"'
            },
            actions: {
                listAction: ajaxurl,
                deleteAction: ajaxurl+'?action=eme_manage_templates&do_action=deleteTemplates&eme_admin_nonce='+emetemplates.translate_adminnonce,
            },
            listQueryParams: function () {
                return {
                    action: 'eme_templates_list',
                    eme_admin_nonce: emetemplates.translate_adminnonce,
                    search_name: EME.$('#search_name')?.value || '',
                    search_type: EME.$('#search_type')?.value || ''
                };
            },
            fields: {
                id: {
                    key: true,
                    list: true,
                    width: '1%',
                    columnResizable: false,
                    title: emetemplates.translate_id
                },
                name: {
                    visibility: 'fixed',
                    title: emetemplates.translate_name
                },
                description: {
                    title: emetemplates.translate_description
                },
                type: {
                    title: emetemplates.translate_type
                },
                copy: {
                    title: emetemplates.translate_copy,
                    sorting: false,
                    width: '1%',
                    listClass: 'eme-ftable-center',
                    columnResizable: false
                }
            },
            sortingInfoSelector: '#templatestablesortingInfo',
            messages: {
                sortingInfoNone: ''
            }
        });

        // Load the table data
        TemplatesTable.load();
    }

    // --- Templates Actions Button (Bulk Actions) ---
    const actionsButton = EME.$('#TemplatesActionsButton');
    if (actionsButton) {
        actionsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = TemplatesTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (doAction === 'deleteTemplates' && !confirm(emetemplates.translate_areyousuretodeleteselected)) return;

            actionsButton.textContent = emetemplates.translate_pleasewait;
            actionsButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('id', idsJoined);
            formData.append('action', 'eme_manage_templates');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emetemplates.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                TemplatesTable.reload();
                actionsButton.textContent = emetemplates.translate_apply;
                actionsButton.disabled = false;

                const messageDiv = EME.$('#templates-message');
                if (messageDiv) {
                    messageDiv.innerHTML = data.htmlmessage;
                    eme_toggle(messageDiv, true);
                    if (doAction === 'deleteTemplates') {
                        setTimeout(() => { eme_toggle(messageDiv, false); }, 5000);
                    }
                }
            });
        });
    }

    // --- Reload Button ---
    const loadRecordsButton = EME.$('#TemplatesLoadRecordsButton');
    if (loadRecordsButton) {
        loadRecordsButton.addEventListener('click', function (e) {
            e.preventDefault();
            TemplatesTable.load();
        });
    }

    // --- Conditional UI: Show/hide PDF properties ---
    const pdfsizeName = 'properties[pdf_size]';
    const typeSelect = EME.$('#type');
    const pdfSizeSelect = EME.$(`select[name="${pdfsizeName}"]`);
    function updateShowHideStuff() {
        const pdfPropertiesTable = EME.$('#pdf_properties');
        const customPdfRow = EME.$('tr.template-pdf-custom');

        if (typeSelect && pdfPropertiesTable) {
            eme_toggle(pdfPropertiesTable, typeSelect.value === 'pdf');
        }

        if (pdfSizeSelect && customPdfRow) {
            eme_toggle(customPdfRow, pdfSizeSelect.value === 'custom');
        }
    }

    // Attach event listeners
    if (typeSelect) {
        typeSelect.addEventListener('change', updateShowHideStuff);
    }

    if (pdfSizeSelect) {
        pdfSizeSelect.addEventListener('change', updateShowHideStuff);
    }

    // Initial call
    updateShowHideStuff();
});
