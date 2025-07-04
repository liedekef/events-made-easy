jQuery(document).ready(function ($) { 
    //Prepare jtable plugin
    $('#CountriesTableContainer').jtable({
        title: emecountries.translate_countries,
        paging: true,
        sorting: true,
        multiSorting: true,
        defaultSorting: 'name ASC',
        selecting: true, // Enable selecting
        multiselect: true, // Allow multiple selecting
        selectingCheckboxes: true, // Show checkboxes on first column
        actions: {
            listAction: ajaxurl+'?action=eme_countries_list&eme_admin_nonce='+emecountries.translate_adminnonce,
            deleteAction: ajaxurl+'?action=eme_manage_countries&do_action=deleteCountries&eme_admin_nonce='+emecountries.translate_adminnonce
        },
        fields: {
            id: {
                title: emecountries.translate_id,
                key: true,
                width: '1%',
                columnResizable: false,
                list: false,
            },
            name: {
                title: emecountries.translate_name
            },
            alpha_2: {
                title: emecountries.translate_alpha_2
            },
            alpha_3: {
                title: emecountries.translate_alpha_3
            },
            num_3: {
                title: emecountries.translate_num_3
            },
            lang: {
                title: emecountries.translate_lang
            }
        },
        sortingInfoSelector: '#countriestablesortingInfo',
        messages: {
            'sortingInfoNone': ''
        }
    });

    $('#StatesTableContainer').jtable({
        title: emecountries.translate_states,
        paging: true,
        sorting: true,
        multiSorting: true,
        defaultSorting: 'name ASC',
        selecting: true, // Enable selecting
        multiselect: true, // Allow multiple selecting
        selectingCheckboxes: true, // Show checkboxes on first column
        actions: {
            listAction: ajaxurl+'?action=eme_states_list&eme_admin_nonce='+emecountries.translate_adminnonce,
            deleteAction: ajaxurl+'?action=eme_manage_states&do_action=deleteStates&eme_admin_nonce='+emecountries.translate_adminnonce
        },
        fields: {
            id: {
                title: emecountries.translate_id,
                key: true,
                width: '1%',
                columnResizable: false,
                list: false,
            },
            name: {
                title: emecountries.translate_name,
                display: function (data) {
                    if (data.record.country_id==0) {
                        return data.record.name+' '+emecountries.translate_missingcountry;
                    } else {
                        return data.record.name;
                    }
                }
            },
            code: {
                title: emecountries.translate_code,
            },
            country_name: {
                title: emecountries.translate_country
            },
            locale: {
                title: emecountries.translate_locale
            }
        },
        sortingInfoSelector: '#statestablesortingInfo',
        messages: {
            'sortingInfoNone': ''
        }
    });

    // Load list from server, but only if the container is there
    if ($('#CountriesTableContainer').length) {
        $('#CountriesTableContainer').jtable('load');
        $('<div id="countriestablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#CountriesTableContainer');

    }
    if ($('#StatesTableContainer').length) {
        $('#StatesTableContainer').jtable('load');
        $('<div id="statestablesortingInfo" style="margin-top: 0px; font-weight: bold;"></div>').insertBefore('#StatesTableContainer');
    }

    // Actions button
    $('#CountriesActionsButton').on("click",function (e) {
        e.preventDefault();
        let selectedRows = $('#CountriesTableContainer').jtable('selectedRows');
        let do_action = $('#eme_admin_action').val();
        let action_ok=1;
        if (selectedRows.length > 0 && do_action != '') {
            if ((do_action=='deleteCountries') && !confirm(emecountries.translate_areyousuretodeleteselected)) {
                action_ok=0;
            }
            if (action_ok==1) {
                $('#CountriesActionsButton').text(emecountries.translate_pleasewait);
                let ids = [];
                selectedRows.each(function () {
                    ids.push($(this).attr('data-record-key'));
                });

                let idsjoined = ids.join(); //will be such a string '2,5,7'
                $.post(ajaxurl, {'id': idsjoined, 'action': 'eme_manage_countries', 'do_action': do_action, 'eme_admin_nonce': emecountries.translate_adminnonce }, function() {
                    $('#CountriesTableContainer').jtable('reload');
                    $('#CountriesActionsButton').text(emecountries.translate_apply);
                    if (do_action=='deleteCountries') {
                        $('div#countries-message').html(emecountries.translate_deleted);
                        $('div#countries-message').show();
                        $('div#countries-message').delay(3000).fadeOut('slow');
                    }
                });
            }
        }
        // return false to make sure the real form doesn't submit
        return false;
    });

    // Actions button
    $('#StatesActionsButton').on("click",function (e) {
        e.preventDefault();
        let selectedRows = $('#StatesTableContainer').jtable('selectedRows');
        let do_action = $('#eme_admin_action').val();
        let action_ok=1;
        if (selectedRows.length > 0 && do_action != '') {
            if ((do_action=='deleteStates') && !confirm(emecountries.translate_areyousuretodeleteselected)) {
                action_ok=0;
            }
            if (action_ok==1) {
                $('#StatesActionsButton').text(emecountries.translate_pleasewait);
                let ids = [];
                selectedRows.each(function () {
                    ids.push($(this).attr('data-record-key'));
                });

                let idsjoined = ids.join(); //will be such a string '2,5,7'
                $.post(ajaxurl, {'id': idsjoined, 'action': 'eme_manage_states', 'do_action': do_action, 'eme_admin_nonce': emecountries.translate_adminnonce }, function() {
                    $('#StatesTableContainer').jtable('reload');
                    $('#StatesActionsButton').text(emecountries.translate_apply);
                    if (do_action=='deleteStates') {
                        $('div#states-message').html(emecountries.translate_deleted);
                        $('div#states-message').show();
                        $('div#states-message').delay(3000).fadeOut('slow');
                    }
                });
            }
        }
        // return false to make sure the real form doesn't submit
        return false;
    });
});
