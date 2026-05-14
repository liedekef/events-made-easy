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
            }
        });

        CountriesTable.load();
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
            }
        });

        StatesTable.load();
    }

    // --- Countries Bulk Actions ---
    const countriesButton = EME.$('#CountriesActionsButton');
    if (countriesButton) {
        countriesButton.addEventListener('click', async function (e) {
            e.preventDefault();
            const selectedRows = CountriesTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (doAction==='deleteCountries') {
                const ok = await FTable.confirm(emeadmin.translate_confirmdelete, emeadmin.translate_areyousuretodeleteselected);
                if (!ok) return;
            }

            countriesButton.textContent = emeadmin.translate_pleasewait;
            countriesButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('id', idsJoined);
            formData.append('action', 'eme_manage_countries');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emeadmin.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                CountriesTable.reload();
                countriesButton.textContent = emeadmin.translate_apply;
                countriesButton.disabled = false;

                const msg = EME.$('#countries-message');
                if (msg) {
                    msg.textContent = data.Message;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                }
            });
        });
    }

    // --- States Bulk Actions ---
    const statesButton = EME.$('#StatesActionsButton');
    if (statesButton) {
        statesButton.addEventListener('click', async function (e) {
            e.preventDefault();
            const selectedRows = StatesTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (doAction==='deleteStates') {
                const ok = await FTable.confirm(emeadmin.translate_confirmdelete, emeadmin.translate_areyousuretodeleteselected);
                if (!ok) return;
            }

            statesButton.textContent = emeadmin.translate_pleasewait;
            statesButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('id', idsJoined);
            formData.append('action', 'eme_manage_states');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emeadmin.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                StatesTable.reload();
                statesButton.textContent = emeadmin.translate_apply;
                statesButton.disabled = false;

                const msg = EME.$('#states-message');
                if (msg) {
                    msg.textContent = data.Message;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                }
            });
        });
    }
});
