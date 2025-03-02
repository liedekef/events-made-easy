jQuery(document).ready( function($) {
    if ($('#CategoriesTableContainer').length) {
        $('#CategoriesTableContainer').jtable({
            title: emecategories.translate_categories,
            paging: true,
            sorting: true,
            multiSorting: true,
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            defaultSorting: '',
            actions: {
                listAction: ajaxurl,
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_categories_list",
                    'eme_admin_nonce': emecategories.translate_adminnonce,
                }
                return params;
            },
            fields: {
                category_id: {
                    key: true,
                    title: emecategories.translate_id,
                },
                category_name: {
                    title: emecategories.translate_name,
                },
            },
            sortingInfoSelector: '#categoriestablesortingInfo',
            messages: {
                'sortingInfoNone': ''
            }
        });
        $('#CategoriesTableContainer').jtable('load');
        $('<div id="categoriestablesortingInfo" style="margin-top: 10px; font-weight: bold;"></div>').insertBefore('#CategoriesTableContainer');

        // Actions button
        $('#CategoriesActionsButton').on("click",function (e) {
            e.preventDefault();
            let selectedRows = $('#CategoriesTableContainer').jtable('selectedRows');
            let do_action = $('#eme_admin_action').val();
            let action_ok=1;
            if (selectedRows.length > 0 && do_action != '') {
                if ((do_action=='deleteCategories') && !confirm(emecategories.translate_areyousuretodeleteselected)) {
                    action_ok=0;
                }
                if (action_ok==1) {
                    $('#CategoriesActionsButton').text(emecategories.translate_pleasewait);
                    $('#CategoriesActionsButton').prop('disabled', true);
                    let ids = [];
                    selectedRows.each(function () {
                        ids.push($(this).attr('data-record-key'));
                    });

                    let idsjoined = ids.join(); //will be such a string '2,5,7'
                    let params = {
                        'category_ids': idsjoined,
                        'action': 'eme_manage_categories',
                        'do_action': do_action,
                        'eme_admin_nonce': emecategories.translate_adminnonce };

                    $.post(ajaxurl, params, function(data) {
                        $('#CategoriesTableContainer').jtable('reload');
                        $('#CategoriesActionsButton').text(emecategories.translate_apply);
                        $('#CategoriesActionsButton').prop('disabled', false);
                        $('div#categories-message').html(data.htmlmessage);
                        $('div#categories-message').show();
                        $('div#categories-message').delay(3000).fadeOut('slow');
                    }, 'json');
                }
            }
            // return false to make sure the real form doesn't submit
            return false;
        });

        // Re-load records when user click 'load records' button.
        $('#CategoriesLoadRecordsButton').on("click",function (e) {
            e.preventDefault();
            $('#CategoriesTableContainer').jtable('load');
            // return false to make sure the real form doesn't submit
            return false;
        });
    }
});
