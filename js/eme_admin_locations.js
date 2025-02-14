jQuery(document).ready(function ($) { 
    if ($('#LocationsTableContainer').length) {
        let locationfields = {
            location_id: {
                key: true,
                title: emelocations.translate_id,
                visibility: 'hidden'
            },
            location_name: {
                title: emelocations.translate_name
            },
            view: {
                title: emelocations.translate_view,
                sorting: false,
                listClass: 'eme-jtable-center'
            },
            copy: {
                title: emelocations.translate_copy,
                sorting: false,
                width: '2%',
                listClass: 'eme-jtable-center'
            },
            location_address1: {
                title: emelocations.translate_address1,
                visibility: 'hidden'
            },
            location_address2: {
                title: emelocations.translate_address2,
                visibility: 'hidden'
            },
            location_zip: {
                title: emelocations.translate_zip,
                visibility: 'hidden'
            },
            location_city: {
                title: emelocations.translate_city,
                visibility: 'hidden'
            },
            location_state: {
                title: emelocations.translate_state,
                visibility: 'hidden'
            },
            location_country: {
                title: emelocations.translate_country,
                visibility: 'hidden'
            },
            location_longitude: {
                title: emelocations.translate_longitude,
                visibility: 'hidden'
            },
            location_latitude: {
                title: emelocations.translate_latitude,
                visibility: 'hidden'
            },
            external_url: {
                title: emelocations.translate_external_url,
                visibility: 'hidden'
            },
            online_only: {
                sorting: false,
                title: emelocations.translate_online_only,
                visibility: 'hidden'
            }
        }

        let extrafields=$('#LocationsTableContainer').data('extrafields').toString().split(',');
        let extrafieldnames=$('#LocationsTableContainer').data('extrafieldnames').toString().split(',');
        let extrafieldsearchable=$('#LocationsTableContainer').data('extrafieldsearchable').toString().split(',');
        $.each(extrafields, function( index, value ) {
            if (value != '') {
                let fieldindex='FIELD_'+value;
                let extrafield = {};
                if (extrafieldsearchable[index]=='1') {
                    sorting=true;
                } else {
                    sorting=false;
                }
                extrafield[fieldindex] = {
                    title: extrafieldnames[index],
                    sorting: sorting,
                    visibility: 'hidden'
                };
                $.extend(locationfields,extrafield);
            }
        });

        //Prepare jtable plugin
        $('#LocationsTableContainer').jtable({
            title: emelocations.translate_locations,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'location_name ASC',
            selecting: true, // Enable selecting
            multiselect: true, // Allow multiple selecting
            selectingCheckboxes: true, // Show checkboxes on first column
            toolbar: {
                items: [{
                    text: emelocations.translate_csv,
                    click: function () {
                        jtable_csv('#LocationsTableContainer','locations');
                    }
                },
                    {
                        text: emelocations.translate_print,
                        click: function () {
                            $('#LocationsTableContainer').find('table:first').printElement();
                        }
                    }
                ]
            },
            actions: {
                listAction: ajaxurl
            },
            listQueryParams: function () {
                let params = {
                    'action': "eme_locations_list",
                    'eme_admin_nonce': emelocations.translate_adminnonce,
                    'search_name': $('#search_name').val(),
                    'search_customfields': $('#search_customfields').val(),
                    'search_customfieldids': $('#search_customfieldids').val()
                }
                return params;
            },
            fields: locationfields
        });

        $('#LocationsTableContainer').jtable('load');
    }

    function updateShowHideStuff () {
        let $action=$('select#eme_admin_action').val();
        if ($action == 'deleteLocations') {
            $('span#span_transferto').show();
        } else {
            $('span#span_transferto').hide();
        }
        // online locations don't need an address or map icon
        if ($('input#eme_loc_prop_online_only').is(':checked')) {
            $('div#loc_address').hide();
            $('div#loc_map_icon').hide();
        } else {
            $('div#loc_address').show();
            $('div#loc_map_icon').show();
        }
    }
    updateShowHideStuff();
    $('select#eme_admin_action').on("change",updateShowHideStuff);
    $('input#eme_loc_prop_online_only').on("change",updateShowHideStuff);

    function changeLocationAdminPageTitle() {
        let locationame=$('input[name=location_name]').val();
        if (!locationame) {
            title=emelocations.translate_insertnewlocation;
        } else {
            title=emelocations.translate_editlocationstring;
            title=title.replace(/%s/g, locationame);
        }
        jQuery(document).prop('title', eme_htmlDecode(title));
    }
    if ($('input[name=location_name]').length) {
        changeLocationAdminPageTitle();
        $('input[name=location_name]').on("keyup",changeLocationAdminPageTitle);
    }

    // for autocomplete to work, the element needs to exist, otherwise JS errors occur
    // we check for that using length
    if ($('input[name=chooselocation]').length) {
        let emeadmin_chooselocation_timeout; // Declare a variable to hold the timeout ID
        $("input[name=chooselocation]").on("input", function(e) {
            clearTimeout(emeadmin_chooselocation_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                emeadmin_chooselocation_timeoutsetTimeout(function() {
                    $.post(ajaxurl,
                        { 
                            'name': inputValue,
                            'eme_admin_nonce': emelocations.translate_adminnonce,
                            'action': 'eme_autocomplete_locations'
                        },
                        function(data) {
                            suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                            $.each(data, function(index, item) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>#"+eme_htmlDecode(item.location_id)+' '+eme_htmlDecode(item.name)+'</strong><br /><small>'+eme_htmlDecode(item.address1)+' - '+eme_htmlDecode(item.city)+'</small>')
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        if (item.person_id) {
                                            $('input[name=transferto_id]').val(eme_htmlDecode(item.person_id));
                                            inputField.val(eme_htmlDecode(item.name)+'  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+emelocations.translate_nomatchlocation+'</strong>')
                                );
                            }
                            $('.eme-autocomplete-suggestions').remove();
                            inputField.after(suggestions);
                        }, "json");
                }, 500); // Delay of 0.5 second
            }
        });
        $(document).on("click", function() {
            $(".eme-autocomplete-suggestions").remove();
        });

        // if manual input: set the hidden field empty again
        $('input[name=chooselocation]').on("keyup",function() {
            $('input[name=transferto_id]').val('');
        }).on("change",function() {
            if ($(this).val()=='') {
                $(this).attr('readonly', false).removeClass('clearable');
                $('input[name=transferto_id]').val('');
            }
        });
    }

    // Actions button
    $('#LocationsActionsButton').on("click",function (e) {
        e.preventDefault();
        let selectedRows = $('#LocationsTableContainer').jtable('selectedRows');
        let do_action = $('#eme_admin_action').val();
        let nonce = $('#eme_admin_nonce').val();
        let action_ok=1;
        if (selectedRows.length > 0 && do_action != '') {
            if ((do_action=='deleteLocations') && !confirm(emelocations.translate_areyousuretodeleteselected)) {
                action_ok=0;
            }
            if (action_ok==1) {
                $('#LocationsActionsButton').text(emelocations.translate_pleasewait);
                let ids = [];
                selectedRows.each(function () {
                    ids.push($(this).data('record')['location_id']);
                });

                let idsjoined = ids.join(); //will be such a string '2,5,7'
                $.post(ajaxurl, {
                    'location_id': idsjoined,
                    'action': 'eme_manage_locations',
                    'do_action': do_action,
                    'transferto_id': $('#transferto_id').val(),
                    'eme_admin_nonce': nonce },
                    function() {
                        $('#LocationsTableContainer').jtable('reload');
                        $('#LocationsActionsButton').text(emelocations.translate_apply);
                    });
            }
        }
        // return false to make sure the real form doesn't submit
        return false;
    });

    // Re-load records when user click 'load records' button.
    $('#LocationsLoadRecordsButton').on("click",function (e) {
        e.preventDefault();
        $('#LocationsTableContainer').jtable('load');
        // return false to make sure the real form doesn't submit
        return false;
    });

    $('#location_remove_image_button').on("click",function(e) {
        $('#location_image_url').val('');
        $('#location_image_id').val('');
        $('#eme_location_image_example' ).attr("src",'').hide();
        $('#location_image_button' ).show();
        $('#location_remove_image_button' ).hide();
    });
    $('#location_image_button').on("click",function(e) {
        e.preventDefault();

        let custom_uploader = wp.media({
            title: emelocations.translate_selectfeaturedimage,
            button: {
                text: emelocations.translate_setfeaturedimage
            },
            // Tell the modal to show only images.
            library: {
                type: 'image'
            },
            multiple: false  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#location_image_url').val(attachment.url);
                $('#location_image_id').val(attachment.id);
                $('#eme_location_image_example' ).attr("src",attachment.url).show();
                $('#location_image_button' ).hide();
                $('#location_remove_image_button' ).show();
            });
        }).open();
    });
    if ($('#location_image_url').val() != '') {
        $('#location_image_button' ).hide();
        $('#location_remove_image_button' ).show();
        $('#eme_location_image_example' ).show();
    } else {
        $('#location_image_button' ).show();
        $('#location_remove_image_button' ).hide();
        $('#eme_location_image_example' ).hide();
    }
    if ($('#locationForm').length) {
        // the validate plugin can take other tabs/hidden fields into account
        $('#locationForm').validate({
            // ignore: false is added so the fields of tabs that are not visible when editing an event are evaluated too
            ignore: false,
            focusCleanup: true,
            errorClass: "eme_required",
            invalidHandler: function(e,validator) {
                $.each(validator.invalid, function(key, value) {
                    // get the closest tabname
                    let tabname=$('[name="'+key+'"]').closest('.eme-tab-content').attr('id');
                    activateTab(tabname);
                    // break the loop, we only want to switch to the first tab with the error
                    return false;
                });
            }
        });
    }

});
