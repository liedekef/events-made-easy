document.addEventListener('DOMContentLoaded', function () {
    const CountriesTableContainer = EME.$('#CountriesTableContainer');
    let CountriesTable;
    const StatesTableContainer = EME.$('#StatesTableContainer');
    let StatesTable;

    // --- Initialize Countries Table ---
    if (CountriesTableContainer) {
        CountriesTable = new FTable('#CountriesTableContainer', {
            title: emeadmin.translate_countries,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl+'?action=eme_countries_list&eme_admin_nonce='+emeadmin.translate_adminnonce,
                deleteAction: ajaxurl+'?action=eme_manage_countries&do_action=deleteCountries&eme_admin_nonce='+emeadmin.translate_adminnonce
            },
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emeadmin.translate_id,
                    list: false
                },
                name: { title: emeadmin.translate_name },
                alpha_2: { title: emeadmin.translate_alpha_2 },
                alpha_3: { title: emeadmin.translate_alpha_3 },
                num_3: { title: emeadmin.translate_num_3 },
                lang: { title: emeadmin.translate_lang }
            },
            bulkActions: {
                select: '#eme_admin_action_countries',
                button: '#CountriesActionsButton',
                idField: 'id',
                action: ajaxurl,
                confirmActions: ['deleteCountries'],
                confirmTitle: emeadmin.translate_confirmdelete,
                confirmMessage: emeadmin.translate_areyousuretodeleteselected,
                extraData: () => ({
                    action: 'eme_manage_countries',
                    lang: emeadmin.translate_locale,
                    eme_admin_nonce: emeadmin.translate_adminnonce
                })
            },
            bulkActionComplete: ({ data }) => {
                eme_show_ftable_bulk_result(CountriesTable, data);
            },
            bulkActionError: ({ data }) => {
                CountriesTable.showError(emeadmin.translate_problem);
            }
        });

        // Don't auto-load: the active tab handler will trigger the load
    }

    // --- Initialize States Table ---
    if (StatesTableContainer) {
        StatesTable = new FTable('#StatesTableContainer', {
            title: emeadmin.translate_states,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl+'?action=eme_states_list&eme_admin_nonce='+emeadmin.translate_adminnonce,
                deleteAction: ajaxurl+'?action=eme_manage_states&do_action=deleteStates&eme_admin_nonce='+emeadmin.translate_adminnonce
            },
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emeadmin.translate_id,
                    list: false
                },
                name: {
                    title: emeadmin.translate_name,
                    value: record => {
                        if (record.country_id == 0) {
                            return `${record.name} ${emeadmin.translate_missingcountry}`;
                        }
                        return record.name;
                    }
                },
                code: { title: emeadmin.translate_code },
                country_name: { title: emeadmin.translate_country },
                locale: { title: emeadmin.translate_locale }
            },
            bulkActions: {
                select: '#eme_admin_action_states',
                button: '#StatesActionsButton',
                idField: 'id',
                action: ajaxurl,
                confirmActions: ['deleteStates'],
                confirmTitle: emeadmin.translate_confirmdelete,
                confirmMessage: emeadmin.translate_areyousuretodeleteselected,
                extraData: () => ({
                    action: 'eme_manage_states',
                    lang: emeadmin.translate_locale,
                    eme_admin_nonce: emeadmin.translate_adminnonce
                })
            },
            bulkActionComplete: ({ data }) => {
                eme_show_ftable_bulk_result(StatesTable, data);
            },
            bulkActionError: ({ data }) => {
                StatesTable.showError(emeadmin.translate_problem);
            }
        });

        // Don't auto-load: the active tab handler will trigger the load
    }
});
