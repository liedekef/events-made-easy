document.addEventListener('DOMContentLoaded', function () {
    const HolidaysTableContainer = EME.$('#HolidaysTableContainer');
    let HolidaysTable;

    if (HolidaysTableContainer) {
        HolidaysTable = new FTable('#HolidaysTableContainer', {
            title: emeadmin.translate_holidaylists,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl
            },
            listQueryParams: () => ({
                action: 'eme_holidays_list',
                lang: emeadmin.translate_locale,
                eme_admin_nonce: emeadmin.translate_adminnonce
            }),
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emeadmin.translate_id,
                    list: false
                },
                name: {
                    title: emeadmin.translate_name
                }
            },
            bulkActions: {
                select: '#eme_admin_action_holidays',
                button: '#HolidaysActionsButton',
                idField: 'holidays_ids',
                action: ajaxurl,
                confirmActions: ['deleteHolidays'],
                confirmTitle: emeadmin.translate_confirmdelete,
                confirmMessage: emeadmin.translate_areyousuretodeleteselected,
                extraData: () => ({
                    action: 'eme_manage_holidays',
                    lang: emeadmin.translate_locale,
                    eme_admin_nonce: emeadmin.translate_adminnonce
                })
            },
            bulkActionComplete: ({ data }) => {
                eme_show_ftable_bulk_result(HolidaysTable, data);
            },
            bulkActionError: ({ data }) => {
                HolidaysTable.showError(emeadmin.translate_problem);
            }
        });

        HolidaysTable.load();
    }

    // --- Reload Button ---
    const loadButton = EME.$('#HolidaysLoadRecordsButton');
    if (loadButton) {
        loadButton.addEventListener('click', e => {
            e.preventDefault();
            HolidaysTable.load();
        });
    }
});
