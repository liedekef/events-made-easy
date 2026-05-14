document.addEventListener('DOMContentLoaded', function () {
    const DiscountsTableContainer = EME.$('#DiscountsTableContainer');
    let DiscountsTable;
    const DiscountGroupsTableContainer = EME.$('#DiscountGroupsTableContainer');
    let DiscountGroupsTable;

    // --- Initialize Discounts Table ---
    if (DiscountsTableContainer) {
        DiscountsTable = new FTable('#DiscountsTableContainer', {
            title: emeadmin.translate_discounts,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            toolbarsearch: true,
            selecting: true,
            multiselect: true,
            defaultDateFormat: emeadmin.translate_fdateformat,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl+'?action=eme_discounts_list&eme_admin_nonce='+emeadmin.translate_adminnonce,
                deleteAction: ajaxurl+'?action=eme_manage_discounts&do_action=deleteDiscounts&eme_admin_nonce='+emeadmin.translate_adminnonce
            },
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    list: false
                },
                name: {
                    title: emeadmin.translate_name
                },
                description: {
                    title: emeadmin.translate_description
                },
                dgroup: {
                    title: emeadmin.translate_discountgroups
                },
                coupon: {
                    title: emeadmin.translate_coupon
                },
                strcase: {
                    title: emeadmin.translate_casesensitive,
                    searchable: false
                },
                use_per_seat: {
                    title: emeadmin.translate_use_per_seat,
                    searchable: false
                },
                value: {
                    title: emeadmin.translate_value
                },
                type: {
                    title: emeadmin.translate_type,
                    searchable: false
                },
                maxcount: {
                    title: emeadmin.translate_maxusage
                },
                count: {
                    title: emeadmin.translate_usage
                },
                valid_from: {
                    title: emeadmin.translate_validfrom,
                    type: 'date',
                    dateFormat: emeadmin.translate_fdatetimeformat
                },
                valid_to: {
                    title: emeadmin.translate_validto,
                    type: 'date',
                    dateFormat: emeadmin.translate_fdatetimeformat,
                }
            }
        });

        DiscountsTable.load();
    }

    // --- Initialize Discount Groups Table ---
    if (DiscountGroupsTableContainer) {
        DiscountGroupsTable = new FTable('#DiscountGroupsTableContainer', {
            title: emeadmin.translate_discountgroups,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            defaultSorting: 'name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            actions: {
                listAction: ajaxurl+'?action=eme_discountgroups_list&eme_admin_nonce='+emeadmin.translate_adminnonce,
                deleteAction: ajaxurl+'?action=eme_manage_discountgroups&do_action=deleteDiscountGroups&eme_admin_nonce='+emeadmin.translate_adminnonce
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
                description: { title: emeadmin.translate_description },
                maxdiscounts: { title: emeadmin.translate_maxdiscounts }
            }
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
        discountsButton.addEventListener('click', async function (e) {
            e.preventDefault();
            const selectedRows = DiscountsTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (doAction === 'deleteDiscounts') {
                const ok = await FTable.confirm(emeadmin.translate_confirmdelete, emeadmin.translate_areyousuretodeleteselected);
                if (!ok) return;
            }

            discountsButton.textContent = emeadmin.translate_pleasewait;
            discountsButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('id', idsJoined);
            formData.append('action', 'eme_manage_discounts');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emeadmin.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                DiscountsTable.reload();
                discountsButton.textContent = emeadmin.translate_apply;
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
        groupsButton.addEventListener('click', async function (e) {
            e.preventDefault();
            const selectedRows = DiscountGroupsTable.getSelectedRows();
            const doAction = EME.$('#eme_admin_action').value;

            if (selectedRows.length === 0 || !doAction) return;

            if (doAction === 'deleteDiscountGroups') {
                const ok = await FTable.confirm(emeadmin.translate_confirmdelete, emeadmin.translate_areyousuretodeleteselected);
                if (!ok) return;
            }

            groupsButton.textContent = emeadmin.translate_pleasewait;
            groupsButton.disabled = true;

            const ids = selectedRows.map(row => row.dataset.recordKey);
            const idsJoined = ids.join(',');

            const formData = new FormData();
            formData.append('id', idsJoined);
            formData.append('action', 'eme_manage_discountgroups');
            formData.append('do_action', doAction);
            formData.append('eme_admin_nonce', emeadmin.translate_adminnonce);

            eme_postJSON(ajaxurl, formData, (data) => {
                DiscountGroupsTable.reload();
                groupsButton.textContent = emeadmin.translate_apply;
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
