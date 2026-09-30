document.addEventListener('DOMContentLoaded', function() {
    eme_initLocationAutocomplete({
        url: emefs.translate_ajax_url,
        nonceField: 'frontend_nonce',
        nonceValue: emefs.translate_frontendnonce,
        nomatchText: emefs.translate_nomatchlocation,
        // a prefilled location name shows the suggestions straight away
        prefillSuggestions: true,
        afterSelect: () => {
            if (typeof L !== 'undefined' && emefs.translate_map_is_active === "true") {
                eme_displayAddress(0);
            }
        },
        // in the frontend form the map is not refreshed by eme_edit_maps.js
        afterClear: () => {
            if (typeof L !== 'undefined' && emefs.translate_map_is_active === "true") {
                eme_displayAddress(0);
            }
        }
    });
});
