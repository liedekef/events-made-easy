document.addEventListener('DOMContentLoaded', function () {
    const CategoriesTableContainer = EME.$('#CategoriesTableContainer');
    let CategoriesTable;

    if (CategoriesTableContainer) {
        CategoriesTable = new FTable('#CategoriesTableContainer', {
            title: emeadmin.translate_categories,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl
            },
            listQueryParams: () => ({
                action: 'eme_categories_list',
                eme_admin_nonce: emeadmin.translate_adminnonce
            }),
            fields: {
                category_id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emeadmin.translate_id
                },
                category_name: {
                    title: emeadmin.translate_name
                }
            },
            bulkActions: {
                select: '#eme_admin_action_categories',
                button: '#CategoriesActionsButton',
                idField: 'category_ids',
                action: ajaxurl,
                confirmActions: ['deleteCategories'],
                confirmTitle: emeadmin.translate_confirmdelete,
                confirmMessage: emeadmin.translate_areyousuretodeleteselected,
                extraData: () => ({
                    action: 'eme_manage_categories',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                })
            },
            bulkActionComplete: ({ data }) => {
                eme_show_ftable_bulk_result(CategoriesTable, data);
            }
        });

        CategoriesTable.load();

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
