jQuery(document).ready( function($) {
    if ($('#TemplatesTableContainer').length) {
        $('#TemplatesTableContainer').jtable({
            title: emetemplates.translate_templates,
            paging: true,
            sorting: true,
            defaultSorting: 'name ASC',
            selecting: true, //Enable selecting
            multiselect: true, //Allow multiple selecting
            selectingCheckboxes: true, //Show checkboxes on first column
            selectOnRowClick: true,
            deleteConfirmation: function(data) {
                data.deleteConfirmMessage = emetemplates.translate_pressdeletetoremove + ' "' + data.record.name + '"';
            },
            actions: {
                listAction: ajaxurl,
                deleteAction: ajaxurl+'?action=eme_manage_templates&do_action=deleteTemplates&eme_admin_nonce='+emetemplates.translate_adminnonce,
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_templates_list",
                    'eme_admin_nonce': emetemplates.translate_adminnonce,
                    'search_name': $('#search_name').val(),
                    'search_type': $('#search_type').val(),
                }
                return params;
            },

            fields: {
                id: {
                    key: true,
                    title: emetemplates.translate_id
                },
                name: {
                    visibility: 'fixed',
                    title: emetemplates.translate_name
                },
                description: {
                    title: emetemplates.translate_description
                },
                type: {
                    title: emetemplates.translate_type
                },
                copy: {
                    title: emetemplates.translate_copy,
                    sorting: false,
                    width: '2%',
                    listClass: 'eme-jtable-center'
                }
            }
        });

        $('#TemplatesTableContainer').jtable('load');
    }

    // Actions button
    $('#TemplatesActionsButton').on("click",function (e) {
        e.preventDefault();
        let selectedRows = $('#TemplatesTableContainer').jtable('selectedRows');
        let do_action = $('#eme_admin_action').val();
        let action_ok=1;
        if (selectedRows.length > 0 && do_action != '') {
            if ((do_action=='deleteTemplates') && !confirm(emetemplates.translate_areyousuretodeleteselected)) {
                action_ok=0;
            }
            if (action_ok==1) {
                $('#TemplatesActionsButton').text(emetemplates.translate_pleasewait);
                let ids = [];
                selectedRows.each(function () {
                    ids.push($(this).data('record')['id']);
                });

                let idsjoined = ids.join(); //will be such a string '2,5,7'
                $.post(ajaxurl, {'id': idsjoined, 'action': 'eme_manage_templates', 'do_action': do_action, 'eme_admin_nonce': emetemplates.translate_adminnonce }, function() {
                    $('#TemplatesTableContainer').jtable('reload');
                    $('#TemplatesActionsButton').text(emetemplates.translate_apply);
                    if (do_action=='deleteTemplates') {
                        $('div#templates-message').html(emetemplates.translate_deleted);
                        $('div#templates-message').show();
                        $('div#templates-message').delay(3000).fadeOut('slow');
                    }
                });
            }
        }
        // return false to make sure the real form doesn't submit
        return false;
    });
    //
    // Re-load records when user click 'load records' button.
    $('#TemplatesLoadRecordsButton').on("click",function (e) {
        e.preventDefault();
        $('#TemplatesTableContainer').jtable('load');
        // return false to make sure the real form doesn't submit
        return false;
    });

    let pdfsize_name = 'properties[pdf_size]';
    function updateShowHideStuff () {
        if ($('select#type').val() == 'pdf') {
            $('table#pdf_properties').show();
        } else {
            $('table#pdf_properties').hide();
        }
        // because the fieldname contains a '[' we do it a bit differently
        if ($('select[name="' + pdfsize_name + '"]').val() == 'custom') {
            $('tr.template-pdf-custom').show();
        } else {
            $('tr.template-pdf-custom').hide();
        }
    }
    $('select#type').on("change",updateShowHideStuff);
    $('select[name="' + pdfsize_name + '"]').on("change",updateShowHideStuff);
    updateShowHideStuff();
});
