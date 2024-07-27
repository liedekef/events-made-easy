jQuery(document).ready(function($) {
	if (jQuery("input#location_name").length > 0) {
		jQuery("input#location_name").autocomplete({
			source: function(request, response) {
				jQuery.post(emefs.translate_ajax_url,
					{ frontend_nonce: emefs.translate_frontendnonce, q: request.term, action: 'eme_autocomplete_locations'},
					function(data){
						response(jQuery.map(data, function(item) {
							return {
								location_id: item.location_id,
								label: eme_htmlDecode(item.name),
								name: eme_htmlDecode(item.name),
								address1: eme_htmlDecode(item.address1),
								address2: eme_htmlDecode(item.address2),
								city: eme_htmlDecode(item.city),
								state: eme_htmlDecode(item.state),
								zip: eme_htmlDecode(item.zip),
								country: eme_htmlDecode(item.country),
								latitude: eme_htmlDecode(item.latitude),
								longitude: eme_htmlDecode(item.longitude),
							};
						}));
					}, "json");

			},
			select:function(evt, ui) {
				// when a product is selected, populate related fields in this form
				jQuery('input#location_id').val(ui.item.location_id).attr("readonly", true);
				jQuery('input#location_name').val(ui.item.name).attr("readonly", true);
				jQuery('input#location_address1').val(ui.item.address1).attr("readonly", true);
				jQuery('input#location_address2').val(ui.item.address2).attr("readonly", true);
				jQuery('input#location_city').val(ui.item.city).attr("readonly", true);
				jQuery('input#location_state').val(ui.item.state).attr("readonly", true);
				jQuery('input#location_zip').val(ui.item.zip).attr("readonly", true);
				jQuery('input#location_country').val(ui.item.country).attr("readonly", true);
				jQuery('input#location_latitude').val(ui.item.latitude).attr("readonly", true);
				jQuery('input#location_longitude').val(ui.item.longitude).attr("readonly", true);
				if (typeof L !== 'undefined' && emefs.translate_map_is_active==="true") {
					eme_displayAddress(0);
				}
				return false;
			},
			minLength: 1
		}).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
			return jQuery( "<li></li>" )
				.append("<a><strong>"+eme_htmlDecode(item.name)+'</strong><br /><small>'+eme_htmlDecode(item.address1)+' - '+eme_htmlDecode(item.city)+ '</small></a>')
				.appendTo( ul );
		};
		jQuery("input#location_name").change(function(){
			if (jQuery("input#location_name").val()=='') {
				jQuery('input#location_id').val('');
				jQuery('input#location_name').val('').attr("readonly", false);
				jQuery('input#location_address1').val('').attr("readonly", false);
				jQuery('input#location_address2').val('').attr("readonly", false);
				jQuery('input#location_city').val('').attr("readonly", false);
				jQuery('input#location_state').val('').attr("readonly", false);
				jQuery('input#location_zip').val('').attr("readonly", false);
				jQuery('input#location_country').val('').attr("readonly", false);
				jQuery('input#location_latitude').val('').attr("readonly", false);
				jQuery('input#location_longitude').val('').attr("readonly", false);
			}
		});
	}
});
