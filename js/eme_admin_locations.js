document.addEventListener('DOMContentLoaded', function () {
    const LocationsTableContainer = $('#LocationsTableContainer');
    let LocationsTable;

    // --- Initialize Locations Table ---
    if (LocationsTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'locationstablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        LocationsTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        let locationFields = {
            location_id: {
                key: true,
                width: '1%',
                columnResizable: false,
                title: emelocations.translate_id,
                visibility: 'hidden'
            },
            location_name: {
                title: emelocations.translate_name
            },
            view: {
                title: emelocations.translate_view,
                sorting: false,
                listClass: 'eme-ftable-center'
            },
            copy: {
                title: emelocations.translate_copy,
                sorting: false,
                width: '2%',
                columnResizable: false,
                listClass: 'eme-ftable-center'
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
        };

        // Add extra fields if present
        const extraFieldsAttr = LocationsTableContainer.dataset.extrafields;
        const extraFieldNamesAttr = LocationsTableContainer.dataset.extrafieldnames;
        const extraFieldSearchableAttr = LocationsTableContainer.dataset.extrafieldsearchable;
        if (extraFieldsAttr && extraFieldNamesAttr) {
            const extraFields = extraFieldsAttr.split(',');
            const extraNames = extraFieldNamesAttr.split(',');
            const extraSearches = extraFieldSearchableAttr.split(',');
            extraFields.forEach((value, index) => {
                if (value == 'SEPARATOR') {
                    let fieldindex = 'SEPARATOR_'+value;
                    memberFields[fieldindex] = { title: extraNames[index], sorting: false, visibility: 'separator' };
                } else {
                    let fieldindex = 'FIELD_'+value;
                    memberFields[fieldindex] = { title: extraNames[index], sorting: extraSearches[index]=='1', visibility: 'hidden' };
                }
            });
        }

        LocationsTable = new FTable('#LocationsTableContainer', {
            title: emelocations.translate_locations,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'location_name ASC',
            selecting: true,
            multiselect: true,
            selectingCheckboxes: true,
            csvExport: true,
            printTable: true,
            actions: {
                listAction: ajaxurl
            },
            listQueryParams: () => ({
                action: 'eme_locations_list',
                eme_admin_nonce: emelocations.translate_adminnonce,
                search_name: $('#search_name')?.value || '',
                search_customfields: $('#search_customfields')?.value || '',
                search_customfieldids: eme_getValue($('#search_customfieldids'))
            }),
            fields: locationFields,
            sortingInfoSelector: '#locationstablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        LocationsTable.load();
    }

    // --- Conditional UI: Show/hide transfer field ---
    function updateShowHideStuff() {
        const action = $('#eme_admin_action')?.value || '';
        eme_toggle($('#span_transferto'), ['trashLocations', 'deleteLocations'].includes(action));
    }

    $('#eme_admin_action')?.addEventListener('change', updateShowHideStuff);
    updateShowHideStuff();

    // --- Bulk Actions ---
    const actionsButton = $('#LocationsActionsButton');
    if (actionsButton) {
        actionsButton.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedRows = LocationsTable.getSelectedRows();
            const doAction = $('#eme_admin_action').value;
            const transfertoId = $('#transferto_id')?.value || '';

            if (selectedRows.length === 0 || !doAction) return;

            let proceed = true;
            if (['trashLocations', 'deleteLocations'].includes(doAction) && !confirm(emelocations.translate_areyousuretodeleteselected)) {
                proceed = false;
            }

            if (proceed) {
                actionsButton.textContent = emelocations.translate_pleasewait;
                actionsButton.disabled = true;

                const ids = selectedRows.map(row => row.dataset.recordKey);
                const idsJoined = ids.join(',');

                const formData = new FormData();
                formData.append('location_id', idsJoined);
                formData.append('action', 'eme_manage_locations');
                formData.append('do_action', doAction);
                formData.append('transferto_id', transfertoId);
                formData.append('eme_admin_nonce', emelocations.translate_adminnonce);

                eme_postJSON(ajaxurl, formData, (data) => {
                    LocationsTable.reload();
                    actionsButton.textContent = emelocations.translate_apply;
                    actionsButton.disabled = false;

                    const msg = $('div#locations-message');
                    if (msg) {
                        msg.textContent = data.Message;
                        eme_toggle(msg, true);
                        setTimeout(() => eme_toggle(msg, false), 5000);
                    }
                });
            }
        });
    }

    // --- Reload Button ---
    $('#LocationsLoadRecordsButton')?.addEventListener('click', e => {
        e.preventDefault();
        LocationsTable.load();
    });

    $('#locationForm')?.addEventListener('submit', function(event) {
        const form = this.form;
        // Manually trigger HTML5 validation
        if (!form.checkValidity()) {
            event.preventDefault(); // Stop submission

            // Find the first invalid field
            const invalidField = form.querySelector(':invalid');
            if (invalidField) {
                eme_scrollToInvalidInput(invalidField); // this switches to the correct tab
            }
            return;
        }
    });

    // Image handling
    const imageButton = $('#location_image_button');
    const removeImageBtn = $('#location_remove_image_button');
    const imageUrl = $('#location_image_url');
    const imageExample = $('#eme_location_image_example');
    const imageId = $('#location_image_id');
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', (e) => {
            e.preventDefault();
            
            if (imageUrl) imageUrl.value = '';
            if (imageId) imageId.value = '';
            if (imageExample) {
                imageExample.src = '';
                eme_toggle(imageExample, false);
            }
            if (imageButton) eme_toggle(imageButton, true);
            eme_toggle(removeImageBtn, false);
        });
    }

    if (imageButton) {
        imageButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (window.wp && window.wp.media) {
                const customUploader = window.wp.media({
                    title: emelocations.translate_selectfeaturedimg || 'Select Featured Image',
                    button: { text: emelocations.translate_setfeaturedimg || 'Set Featured Image' },
                    library: { type: 'image' },
                    multiple: false
                }).on('select', function() {
                    const selection = customUploader.state().get('selection');
                    selection.map(function(attach) {
                        const attachment = attach.toJSON();
                        
                        if (imageUrl) imageUrl.value = attachment.url;
                        if (imageId) imageId.value = attachment.id;
                        if (imageExample) {
                            imageExample.src = attachment.url;
                            eme_toggle(imageExample, true);
                        }
                        eme_toggle(imageButton, false);
                        if (removeBtn) eme_toggle(removeImageBtn, true);
                    });
                }).open();
            }
        });
    }

    if (imageUrl) {
        if (imageUrl.value !== '') {
            if (imageButton) eme_toggle(imageButton, false);
            if (removeImageBtn) eme_toggle(removeImageBtn, true);
            if (imageExample) eme_toggle(imageExample, true);
        } else {
            if (imageButton) eme_toggle(imageButton, true);
            if (removeImageBtn) eme_toggle(removeImageBtn, false);
            if (imageExample) eme_toggle(imageExample, false);
        }
    }
});
