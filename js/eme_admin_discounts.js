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
            },
            bulkActions: {
                select: '#eme_admin_action_discounts',
                button: '#DiscountsActionsButton',
                idField: 'id',
                action: ajaxurl,
                confirmActions: ['deleteDiscounts'],
                confirmTitle: emeadmin.translate_confirmdelete,
                confirmMessage: emeadmin.translate_areyousuretodeleteselected,
                visibleWhen: {
                    '#span_addtogroup': ['addToGroup'],
                    '#span_removefromgroup': ['removeFromGroup'],
                    'span#span_newvalidfrom': ['changeValidFrom'],
                    'span#span_newvalidto': ['changeValidTo']
                },
                extraData: () => ({
                    action: 'eme_manage_discounts',
                    lang: emeadmin.translate_locale,
                    addtogroup: EME.$('#addtogroup')?.value || '',
                    removefromgroup: EME.$('#removefromgroup')?.value || '',
                    new_validfrom: EME.$('#new_validfrom')?.value || '',
                    new_validto: EME.$('#new_validto')?.value || '',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                })
            },
            bulkActionComplete: ({ data }) => {
                eme_show_ftable_bulk_result(DiscountsTable, data);
            },
            bulkActionError: ({ data }) => {
                DiscountsTable.showError(emeadmin.translate_problem);
            }
        });

        // Don't auto-load: the active tab handler will trigger the load
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
            },
            bulkActions: {
                select: '#eme_admin_action_discountgroups',
                button: '#DiscountGroupsActionsButton',
                idField: 'id',
                action: ajaxurl,
                confirmActions: ['deleteDiscountGroups'],
                confirmTitle: emeadmin.translate_confirmdelete,
                confirmMessage: emeadmin.translate_areyousuretodeleteselected,
                extraData: () => ({
                    action: 'eme_manage_discountgroups',
                    lang: emeadmin.translate_locale,
                    eme_admin_nonce: emeadmin.translate_adminnonce
                })
            },
            bulkActionComplete: ({ data }) => {
                eme_show_ftable_bulk_result(DiscountGroupsTable, data);
            },
            bulkActionError: ({ data }) => {
                DiscountGroupsTable.showError(emeadmin.translate_problem);
            }
        });

        // Don't auto-load: the active tab handler will trigger the load
    }
});
