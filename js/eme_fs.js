jQuery(document).ready(function($) {
    if ($("input#location_name").length > 0) {
        let frontend_submit_timeout; // Declare a variable to hold the timeout ID
        $("input#location_name").on("input", function(e) {
            e.preventDefault();
            clearTimeout(frontend_submit_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                frontend_submit_timeout = setTimeout(function() {
                    $.post(emefs.translate_ajax_url,
                        { 'frontend_nonce': emefs.translate_frontendnonce, 'name': inputValue, 'action': 'eme_autocomplete_locations'},
                        function(data) {
                            suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                            $.each(data, function(index, item) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+EME.utils.htmlDecode(item.name)+'</strong><br /><small>'+EME.utils.htmlDecode(item.address1)+' - '+EME.utils.htmlDecode(item.city)+ '</small>')
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        $('input#location_id').val(EME.utils.htmlDecode(item.location_id)).attr("readonly", true);
                                        $('input#location_name').val(EME.utils.htmlDecode(item.name)).attr("readonly", true);
                                        $('input#location_address1').val(EME.utils.htmlDecode(item.address1)).attr("readonly", true);
                                        $('input#location_address2').val(EME.utils.htmlDecode(item.address2)).attr("readonly", true);
                                        $('input#location_city').val(EME.utils.htmlDecode(item.city)).attr("readonly", true);
                                        $('input#location_state').val(EME.utils.htmlDecode(item.state)).attr("readonly", true);
                                        $('input#location_zip').val(EME.utils.htmlDecode(item.zip)).attr("readonly", true);
                                        $('input#location_country').val(EME.utils.htmlDecode(item.country)).attr("readonly", true);
                                        $('input#location_latitude').val(EME.utils.htmlDecode(item.latitude)).attr("readonly", true);
                                        $('input#location_longitude').val(EME.utils.htmlDecode(item.longitude)).attr("readonly", true);
                                        $('input#location_url').val(EME.utils.htmlDecode(item.location_url)).attr("readonly", true);
                                        $('input#eme_loc_prop_map_icon').val(EME.utils.htmlDecode(item.map_icon)).attr("readonly", true);
                                        $('input#eme_loc_prop_online_only').val(EME.utils.htmlDecode(item.online_only)).attr("disabled", true);
                                        if (typeof L !== 'undefined' && emefs.translate_map_is_active==="true") {
                                            eme_displayAddress(0);
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+emefs.translate_nomatchlocation+'</strong>')
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

        $("input#location_name").change(function(e){
            e.preventDefault();
            if ($("input#location_name").val()=='') {
                $('input#location_id').val('');
                $('input#location_name').val('').attr("readonly", false);
                $('input#location_address1').val('').attr("readonly", false);
                $('input#location_address2').val('').attr("readonly", false);
                $('input#location_city').val('').attr("readonly", false);
                $('input#location_state').val('').attr("readonly", false);
                $('input#location_zip').val('').attr("readonly", false);
                $('input#location_country').val('').attr("readonly", false);
                $('input#location_latitude').val('').attr("readonly", false);
                $('input#location_longitude').val('').attr("readonly", false);
                $('input#eme_loc_prop_map_icon').val('').attr('readonly', false);
                $('input#eme_loc_prop_online_only').attr('disabled',false);
                $('input#location_url').val('').attr('readonly', false);
                if (typeof L !== 'undefined' && emefs.translate_map_is_active==="true") {
                    eme_displayAddress(0);
                }
            }
        });
    }
});
