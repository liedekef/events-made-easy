document.addEventListener('DOMContentLoaded', function () {
    const CountriesTableContainer = $('#CountriesTableContainer');
    let CountriesTable;
    const StatesTableContainer = $('#StatesTableContainer');
    let StatesTable;

    // --- Initialize Countries Table ---
    if (CountriesTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'countriestablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        CountriesTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        CountriesTable = new FTable('#CountriesTableContainer', {
            title: emecountries.translate_countries,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl+'?action=eme_countries_list&eme_admin_nonce='+emecountries.translate_adminnonce,
                deleteAction: ajaxurl+'?action=eme_manage_countries&do_action=deleteCountries&eme_admin_nonce='+emecountries.translate_adminnonce
                }
            },
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emecountries.translate_id,
                    list: false
                },
                name: { title: emecountries.translate_name },
                alpha_2: { title: emecountries.translate_alpha_2 },
                alpha_3: { title: emecountries.translate_alpha_3 },
                num_3: { title: emecountries.translate_num_3 },
                lang: { title: emecountries.translate_lang }
            },
            sortingInfoSelector: '#countriestablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        CountriesTable.load();
    }

    // --- Initialize States Table ---
    if (StatesTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'statestablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        StatesTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        StatesTable = new FTable('#StatesTableContainer', {
            title: emecountries.translate_states,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl+'?action=eme_states_list&eme_admin_nonce='+emecountries.translate_adminnonce,
                deleteAction: ajaxurl+'?action=eme_manage_states&do_action=deleteStates&eme_admin_nonce='+emecountries.translate_adminnonce
            },
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emecountries.translate_id,
                    list: false
                },
                name: {
                    title: emecountries.translate_name,
                    value: record => {
                        if (record.country_id == 0) {
                            return `${record.name} ${emecountries.translate_missingcountry}`;
                        }
                        return record.name;
                    }
                },
                code: { title: emecountries.translate_code },
                country_name: { title: emecountries.translate_country },
                locale: { title: emecountries.translate_locale }
            },
            sortingInfoSelector: '#statestablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        StatesTable.load();
    }

    // --- Countries Bulk Actions ---
    const countriesButton = $('#CountriesActionsButton');
    if (countriesButton) {
        countriesButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = CountriesTable.getSelectedRows();
            const doAction = $('#eme_admin_action').value;

            if (selectedRows.length === 0 || doAction !== 'deleteCountries') return;

            if (!confirm(emecountries.translate_areyousuretodeleteselected)) return;

            countriesButton.textContent = emecountries.translate_pleasewait;
            countriesButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('id', idsJoined);
            formData.append('action', 'eme_manage_countries');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emecountries.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                CountriesTable.load();
                countriesButton.textContent = emecountries.translate_apply;
                countriesButton.disabled = false;

                const msg = $('div#countries-message');
                if (msg) {
                    msg.textContent = data.Message;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                }
            });
        });
    }

    // --- States Bulk Actions ---
    const statesButton = $('#StatesActionsButton');
    if (statesButton) {
        statesButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = StatesTable.getSelectedRows();
            const doAction = $('#eme_admin_action').value;

            if (selectedRows.length === 0 || doAction !== 'deleteStates') return;

            if (!confirm(emecountries.translate_areyousuretodeleteselected)) return;

            statesButton.textContent = emecountries.translate_pleasewait;
            statesButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('id', idsJoined);
            formData.append('action', 'eme_manage_states');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emecountries.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                StatesTable.load();
                statesButton.textContent = emecountries.translate_apply;
                statesButton.disabled = false;

                const msg = $('div#states-message');
                if (msg) {
                    msg.textContent = data.Message;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                }
            });
        });
    }
});
