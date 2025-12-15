document.addEventListener('DOMContentLoaded', function () {
    const CategoriesTableContainer = EME.$('#CategoriesTableContainer');
    let CategoriesTable;

    if (CategoriesTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'categoriestablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        CategoriesTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        CategoriesTable = new FTable('#CategoriesTableContainer', {
            title: emecategories.translate_categories,
            paging: true,
            sorting: true,
            multiSorting: true,
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl
            },
            listQueryParams: () => ({
                action: 'eme_categories_list',
                eme_admin_nonce: emecategories.translate_adminnonce
            }),
            fields: {
                category_id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emecategories.translate_id
                },
                category_name: {
                    title: emecategories.translate_name
                }
            },
            sortingInfoSelector: '#categoriestablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        CategoriesTable.load();

        // --- Bulk Actions ---
        const actionsButton = EME.$('#CategoriesActionsButton');
        if (actionsButton) {
            actionsButton.addEventListener('click', function (e) {
                e.preventDefault();
                const selectedRows = CategoriesTable.getSelectedRows();
                const doAction = EME.$('#eme_admin_action').value;

                if (selectedRows.length === 0 || !doAction) return;

                if (doAction === 'deleteCategories' && !confirm(emecategories.translate_areyousuretodeleteselected)) return;

                actionsButton.textContent = emecategories.translate_pleasewait;
                actionsButton.disabled = true;

                const ids = selectedRows.map(row => row.dataset.recordKey);
                const idsJoined = ids.join(',');

                const formData = new FormData();
                formData.append('category_ids', idsJoined);
                formData.append('action', 'eme_manage_categories');
                formData.append('do_action', doAction);
                formData.append('eme_admin_nonce', emecategories.translate_adminnonce);

                eme_postJSON(ajaxurl, formData, (data) => {
                    CategoriesTable.reload();
                    actionsButton.textContent = emecategories.translate_apply;
                    actionsButton.disabled = false;

                    const msg = EME.$('#categories-message');
                    if (msg) {
                        msg.innerHTML = data.htmlmessage;
                        eme_toggle(msg, true);
                        setTimeout(() => eme_toggle(msg, false), 3000);
                    }
                });
            });
        }

        // --- Reload Button ---
        const loadButton = EME.$('#CategoriesLoadRecordsButton');
        if (loadButton) {
            loadButton.addEventListener('click', e => {
                e.preventDefault();
                CategoriesTable.load();
            });
        }
    }
});
