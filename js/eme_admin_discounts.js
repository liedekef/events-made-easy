document.addEventListener('DOMContentLoaded', function () {
    const DiscountsTableContainer = EME.$('#DiscountsTableContainer');
    let DiscountsTable;
    const DiscountGroupsTableContainer = EME.$('#DiscountGroupsTableContainer');
    let DiscountGroupsTable;

    // --- Initialize Discounts Table ---
    if (DiscountsTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'discountstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        DiscountsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        DiscountsTable = new FTable('#DiscountsTableContainer', {
            title: emediscounts.translate_discounts,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            toolbarsearch: true,
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl+'?action=eme_discounts_list&eme_admin_nonce='+emediscounts.translate_adminnonce,
                deleteAction: ajaxurl+'?action=eme_manage_discounts&do_action=deleteDiscounts&eme_admin_nonce='+emediscounts.translate_adminnonce
            },
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    list: false
                },
                name: {
                    title: emediscounts.translate_name
                },
                description: {
                    title: emediscounts.translate_description
                },
                dgroup: {
                    title: emediscounts.translate_discountgroups
                },
                coupon: {
                    title: emediscounts.translate_coupon
                },
                strcase: {
                    title: emediscounts.translate_casesensitive,
                    searchable: false
                },
                use_per_seat: {
                    title: emediscounts.translate_use_per_seat,
                    searchable: false
                },
                value: {
                    title: emediscounts.translate_value
                },
                type: {
                    title: emediscounts.translate_type,
                    searchable: false
                },
                maxcount: {
                    title: emediscounts.translate_maxusage
                },
                count: {
                    title: emediscounts.translate_usage
                },
                valid_from: {
                    title: emediscounts.translate_validfrom
                },
                valid_to: {
                    title: emediscounts.translate_validto
                }
            },
            sortingInfoSelector: '#discountstablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        DiscountsTable.load();
    }

    // --- Initialize Discount Groups Table ---
    if (DiscountGroupsTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'discountgroupstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        DiscountGroupsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        DiscountGroupsTable = new FTable('#DiscountGroupsTableContainer', {
            title: emediscounts.translate_discountgroups,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl+'?action=eme_discountgroups_list&eme_admin_nonce='+emediscounts.translate_adminnonce,
                deleteAction: ajaxurl+'?action=eme_manage_discountgroups&do_action=deleteDiscountGroups&eme_admin_nonce='+emediscounts.translate_adminnonce
            },
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emediscounts.translate_id,
                    list: false
                },
                name: { title: emediscounts.translate_name },
                description: { title: emediscounts.translate_description },
                maxdiscounts: { title: emediscounts.translate_maxdiscounts }
            },
            sortingInfoSelector: '#discountgroupstablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        DiscountGroupsTable.load();
    }

        // --- Conditional UI: Show/hide based on action ---
    function updateShowHideStuff() {
        const action = EME.$('#eme_admin_action')?.value || '';
        eme_toggle(EME.$('span#span_newvalidfrom'), action === 'changeValidFrom');
        eme_toggle(EME.$('span#span_newvalidto'), action === 'changeValidTo');
        eme_toggle(EME.$('#span_removefromgroup'), action === 'removeFromGroup');
        eme_toggle(EME.$('#span_pdftemplate'), action === 'pdf');
        eme_toggle(EME.$('#span_htmltemplate'), action === 'html');
        eme_toggle(EME.$('span#span_transferto'), ['trashPeople', 'deletePeople'].includes(action));
    }

    EME.$('#eme_admin_action')?.addEventListener('change', updateShowHideStuff);
    updateShowHideStuff();

    // --- Discounts Bulk Actions ---
    const discountsButton = EME.$('#DiscountsActionsButton');
    if (discountsButton) {
        discountsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = DiscountsTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (doAction === 'deleteDiscounts' && !confirm(emediscounts.translate_areyousuretodeleteselected)) return;

            discountsButton.textContent = emediscounts.translate_pleasewait;
            discountsButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('id', idsJoined);
            formData.append('action', 'eme_manage_discounts');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emediscounts.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                DiscountsTable.reload();
                discountsButton.textContent = emediscounts.translate_apply;
                discountsButton.disabled = false;

                const msg = EME.$('#discounts-message');
                if (msg) {
                    msg.textContent = data.Message;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                }
            });
        });
    }

    // --- Discount Groups Bulk Actions ---
    const groupsButton = EME.$('#DiscountGroupsActionsButton');
    if (groupsButton) {
        groupsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = DiscountGroupsTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (doAction === 'deleteDiscountGroups' && !confirm(emecountries.translate_areyousuretodeleteselected)) return;

            groupsButton.textContent = emediscounts.translate_pleasewait;
            groupsButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('id', idsJoined);
            formData.append('action', 'eme_manage_discountgroups');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emediscounts.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                DiscountGroupsTable.reload();
                groupsButton.textContent = emediscounts.translate_apply;
                groupsButton.disabled = false;

                const msg = EME.$('#discountgroups-message');
                if (msg) {
                    msg.textContent = data.Message;
                    eme_toggle(msg, true);
                    setTimeout(() => eme_toggle(msg, false), 3000);
                }
            });
        });
    }
});
