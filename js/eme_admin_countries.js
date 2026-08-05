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
                    eme_admin_nonce: emeadmin.translate_adminnonce
                })
            },
            bulkActionComplete: ({ data }) => {
                const msg = EME.$('#countries-message');
                if (msg) {
                    msg.textContent = data?.Message;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                }
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
                    eme_admin_nonce: emeadmin.translate_adminnonce
                })
            },
            bulkActionComplete: ({ data }) => {
                const msg = EME.$('#states-message');
                if (msg) {
                    msg.textContent = data?.Message;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                }
            }
        });

        // Don't auto-load: the active tab handler will trigger the load
    }
});
