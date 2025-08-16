document.addEventListener('DOMContentLoaded', function () {
    const HolidaysTableContainer = EME.$('#HolidaysTableContainer');
    let HolidaysTable;

    if (HolidaysTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'holidaystablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        HolidaysTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        HolidaysTable = new FTable('#HolidaysTableContainer', {
            title: emeholidays.translate_holidaylists,
            paging: true,
            sorting: true,
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
                eme_admin_nonce: emeholidays.translate_adminnonce
            }),
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emeholidays.translate_id,
                    list: false
                },
                name: {
                    title: emeholidays.translate_name
                }
            },
            sortingInfoSelector: '#holidaystablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        HolidaysTable.load();
    }

    // --- Bulk Actions ---
    const actionsButton = EME.$('#HolidaysActionsButton');
    if (actionsButton) {
        actionsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = HolidaysTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (doAction==='deleteHolidays' && !confirm(emeholidays.translate_areyousuretodeleteselected)) return;

            actionsButton.textContent = emeholidays.translate_pleasewait;
            actionsButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('id', idsJoined);
            formData.append('action', 'eme_manage_holidays');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emeholidays.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                HolidaysTable.reload();
                actionsButton.textContent = emeholidays.translate_apply;
                actionsButton.disabled = false;

                const msg = EME.$('#holidays-message');
                if (msg) {
                    msg.innerHTML = data.htmlmessage;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                }
            });
        });
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
