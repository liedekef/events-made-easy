jQuery(document).ready( function($) {
    const multiValueTypes = [
        'dropdown',
        'dropdown_multi',
        'radiobox',
        'radiobox_vertical',
        'checkbox',
        'checkbox_vertical'
    ];

    // List of field IDs
    const fields = [
        'field_values',
        'field_tags',
        'admin_values',
        'admin_tags'
    ];

    function formatToTextarea(value) {
        let converted = value.replace(/^\|\|/, '\n').replace(/\|\|/g, '\n');
        if (value.startsWith('||')) {
            converted = '\n' + converted;
        }
        return converted;
    }

    function formatToInput(value) {
        return value.replace(/\r\n|\r|\n/g, '||');
    }

    function updateAllFields() {
        const selectedType = $('#field_type').val();
        const isMulti = multiValueTypes.includes(selectedType);

        fields.forEach(function (fieldId) {
            const field = $('#' + fieldId);
            const container = $('#' + fieldId + '_container');
            const value = field.val() || '';

            if (isMulti) {
                const textareaVal = formatToTextarea(value);
                container.html(
                    `<textarea name="${fieldId}" id="${fieldId}" rows="5" cols="40">${textareaVal}</textarea>`
                );
            } else {
                const inputVal = formatToInput(value);
                container.html(
                    `<input type="text" name="${fieldId}" id="${fieldId}" size="40" value="${inputVal}" />`
                );
            }
        });
    }

    // Trigger update when dropdown changes
    $('#field_type').change(updateAllFields);

    // Initial setup on page load
    updateAllFields();

    if ($('#FormfieldsTableContainer').length) {
        $('#FormfieldsTableContainer').jtable({
            title: emeformfields.translate_formfields,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'field_name ASC',
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            deleteConfirmation: function(data) {
                data.deleteConfirmMessage = emeformfields.translate_pressdeletetoremove + ' "' + data.record.field_name + '"';
            },
            actions: {
                listAction: ajaxurl,
                deleteAction: ajaxurl+'?action=eme_manage_formfields&do_action=deleteFormfield&eme_admin_nonce='+emeformfields.translate_adminnonce
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_formfields_list",
                    'eme_admin_nonce': emeformfields.translate_adminnonce,
                    'search_name': $('#search_name').val(),
                    'search_type': $('#search_type').val(),
                    'search_purpose': $('#search_purpose').val()
                }
                return params;
            },

            fields: {
                field_id: {
                    key: true,
                    title: emeformfields.translate_id,
                    visibility: 'hidden'
                },
                field_name: {
                    title: emeformfields.translate_name,
                    visibility: 'fixed',
                },
                copy: {
                    title: emeformfields.translate_copy,
                    sorting: false,
                    width: '2%',
                    listClass: 'eme-jtable-center'
                },
                field_type: {
                    title: emeformfields.translate_type
                },
                field_required: {
                    title: emeformfields.translate_required,
                    width: '2%'
                },
                field_purpose: {
                    title: emeformfields.translate_purpose
                },
                extra_charge: {
                    title: emeformfields.translate_extracharge,
                    visibility: 'hidden'
                },
                searchable: {
                    title: emeformfields.translate_searchable,
                    visibility: 'hidden'
                },
                used: {
                    title: emeformfields.translate_used,
                    sorting: false,
                    width: '2%'
                }
            },
            sortingInfoSelector: '#formfieldstablesortingInfo',
            messages: {
                'sortingInfoNone': ''
            }
        });

        $('#FormfieldsTableContainer').jtable('load');
        $('<div id="formfieldstablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#FormfieldsTableContainer');

        // Actions button
        $('#FormfieldsActionsButton').on("click",function (e) {
            e.preventDefault();
            let selectedRows = $('#FormfieldsTableContainer').jtable('selectedRows');
            let do_action = $('#eme_admin_action').val();
            let action_ok=1;
            if (selectedRows.length > 0 && do_action != '') {
                if ((do_action=='deleteFormfields') && !confirm(emeformfields.translate_areyousuretodeleteselected)) {
                    action_ok=0;
                }
                if (action_ok==1) {
                    $('#FormfieldsActionsButton').text(emeformfields.translate_pleasewait);
                    let ids = [];
                    selectedRows.each(function () {
                        ids.push($(this).attr('data-record-key'));
                    });

                    let idsjoined = ids.join(); //will be such a string '2,5,7'
                    $.post(ajaxurl, {'field_id': idsjoined, 'action': 'eme_manage_formfields', 'do_action': do_action, 'eme_admin_nonce': emeformfields.translate_adminnonce }, function(data) {
                        $('#FormfieldsTableContainer').jtable('reload');
                        $('#FormfieldsActionsButton').text(emeformfields.translate_apply);
                        $('div#formfields-message').html(data.htmlmessage);
                        $('div#formfields-message').show();
                        $('div#formfields-message').delay(3000).fadeOut('slow');
                    },'json');
                }
            }
            // return false to make sure the real form doesn't submit
            return false;
        });
        //
        // Re-load records when user click 'load records' button.
        $('#FormfieldsLoadRecordsButton').on("click",function (e) {
            e.preventDefault();
            $('#FormfieldsTableContainer').jtable('load');
            // return false to make sure the real form doesn't submit
            return false;
        });
    }

    function updateShowHideFormfields_type () {
        if (jQuery('select#field_type').val() == 'file') {
            $('tr#tr_extra_charge').hide();
            $('tr#tr_field_tags').hide();
            $('tr#tr_admin_tags').hide();
            $('tr#tr_field_values').hide();
            $('tr#tr_admin_values').hide();
            $('tr#tr_searchable').hide();
        } else {
            // field_purpose can be a select or hidden field
            if (jQuery('#field_purpose').val() == 'people') {
                $('tr#tr_extra_charge').hide();
            } else {
                $('tr#tr_extra_charge').show();
            }
            $('tr#tr_field_tags').show();
            $('tr#tr_admin_tags').show();
            $('tr#tr_field_values').show();
            $('tr#tr_admin_values').show();
            $('tr#tr_searchable').show();
        }
    }
    function updateShowHideFormfields_purpose () {
        // field_purpose can be a select or hidden field
        if (jQuery('#field_purpose').val() == 'people') {
            $('tr#tr_extra_charge').hide();
            $('tr#tr_field_condition').show();
            $('tr#tr_export').show();
        } else {
            if (jQuery('select#field_type').val() == 'file') {
                $('tr#tr_extra_charge').hide();
            } else {
                $('tr#tr_extra_charge').show();
            }
            $('tr#tr_field_condition').hide();
            $('tr#tr_export').hide();
        }
    }
    $('select#field_purpose').on("change",updateShowHideFormfields_purpose);
    $('select#field_type').on("change",updateShowHideFormfields_type);
    updateShowHideFormfields_purpose();
    updateShowHideFormfields_type();
});
