jQuery(document).ready(function($) {
	if (jQuery("input#location_name").length > 0) {
		jQuery('<input>').attr({
			type: 'hidden',
			id: 'location_id',
			name: 'event[location_id]'
		}).appendTo(jQuery("input#location_name").parents('form:first'));
		jQuery("input#location_name").addClass( "clearable" );
		jQuery("input#location_name").autocomplete({
			source: function(request, response) {
				jQuery.post(emefs.translate_ajax_url,
					{ frontend_nonce: emefs.translate_frontendnonce, q: request.term, action: 'eme_locations_autocomplete_ajax'},
					function(data){
						response(jQuery.map(data, function(item) {
							return {
								id: item.id,
								label: item.name,
								name: eme_htmlDecode(item.name),
								address1: item.address1,
								address2: item.address2,
								city: item.city,
								state: item.state,
								zip: item.zip,
								country: item.country,
								latitude: item.latitude,
								longitude: item.longitude,
							};
						}));
					}, "json");

			},
			select:function(evt, ui) {
				// when a product is selected, populate related fields in this form
				jQuery('input#location_id').val(ui.item.id).attr("readonly", true);
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
					emefs_displayAddress(0);
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

	if (typeof L !== 'undefined' && emefs.translate_map_is_active==="true") {
		jQuery("input#location_name").change(function(){
			emefs_displayAddress(0);
		});
		jQuery("input#location_city").change(function(){
			emefs_displayAddress(1);
		});
		jQuery("input#location_state").change(function(){
			emefs_displayAddress(1);
		});
		jQuery("input#location_zip").change(function(){
			emefs_displayAddress(1);
		});
		jQuery("input#location_country").change(function(){
			emefs_displayAddress(1);
		});
		jQuery("input#location_address1").change(function(){
			emefs_displayAddress(1);
		});
		jQuery("input#location_address2").change(function(){
			emefs_displayAddress(1);
		});

		jQuery("input#location_latitude").change(function(){
			emefs_displayAddress(0);
		});
		jQuery("input#location_longitude").change(function(){
			emefs_displayAddress(0);
		});
	}

	function emefs_displayAddress(ignore_coord) {
		eventLocation = jQuery("input#location_name").val() || ""; 
		eventAddress1 = jQuery("input#location_address1").val() || "";
		eventAddress2 = jQuery("input#location_address2").val() || "";
		eventCity = jQuery("input#location_city").val() || "";
		eventState = jQuery("input#location_state").val() || "";
		eventZip = jQuery("input#location_zip").val() || "";
		eventCountry = jQuery("input#location_country").val() || "";

		if (ignore_coord) {
			emefs_loadMap(eventLocation, eventAddress1, eventAddress2,eventCity,eventState,eventZip,eventCountry);
		} else {
			eventLat = jQuery("input#location_latitude").val();
			eventLong = jQuery("input#location_longitude").val();
			emefs_loadMapLatLong(eventLocation, eventAddress1, eventAddress2,eventCity,eventState,eventZip,eventCountry, eventLat,eventLong);
		}
	}

	var osm;
	var map;
	// create the tile layer with correct attribution
	var osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	var osmAttrib='Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
	if (typeof L !== 'undefined' && emefs.translate_map_is_active==="true") {
		osm = new L.TileLayer(osmUrl, {attribution: osmAttrib});
	}

	function emefs_loadMap(loc_name, address1, address2, city, state, zip, country) {
		var emefs_mapCenter = L.latLng(-34.397, 150.644);
		var emefs_mapOptions = {
			zoom: 13,
			center: emefs_mapCenter,
			scrollWheelZoom: emefs.translate_map_zooming,
			doubleClickZoom: false
		}

		if (address1 !="" || address2 != "" || city!="" || state != "" || zip != "" || country != "") {
			searchKey = address1 + ", " + address2 + "," + city + ", " + zip + ", " + state + ", " + country;
		} else {
			searchKey = loc_name + ', ' + address1 + ", " + address2 + "," + city + ", " + zip + ", " + state + ", " + country;
		}

		if (searchKey) {
			var geocode_url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1';
			jQuery.getJSON( geocode_url, { 'q': searchKey}, function(data) {
				if (data.length===0) {
					jQuery("#eme-edit-location-map").hide();
				} else {
					// first we show the map, so leaflet can check the size
					//jQuery('#eme-edit-location-map').show();
					jQuery("#eme-edit-location-map").show();
					// to avoid the leaflet error 'Map container is already initialized'
					if (map) {
						map.off();
						map.remove();
					}
					map = L.map('eme-edit-location-map', emefs_mapOptions);
					map.addLayer(osm);
					map.panTo([data[0].lat, data[0].lon]);
					var marker = L.marker(data[0]).addTo(map);
					var pop_content='<div class=\"eme-location-balloon\"><strong>' + loc_name +'</strong><p>' + address1 + ' ' + address2 + '<br />' + city + ' ' + state + ' ' + zip + ' ' + country + '</p></div>';
					marker.bindPopup(pop_content).openPopup();
					jQuery('input#location_latitude').val(data[0].lat);
					jQuery('input#location_longitude').val(data[0].lon);
				}
			});
		}
	}

	function emefs_loadMapLatLong(loc_name, address1, address2, city, state, zip, country, lat, lon) {
		if (lat === undefined) {
			lat = 0;
		}
		if (lon === undefined) {
			lon = 0;
		}

		if (lat != 0 && lon != 0) {
			var emefs_mapCenter = L.latLng(lat, lon);
			var emefs_mapOptions = {
				zoom: 13,
				center: emefs_mapCenter,
				scrollWheelZoom: emefs.translate_map_zooming,
				doubleClickZoom: false
			}
			// to avoid the leaflet error 'Map container is already initialized'
			if (map) {
				map.off();
				map.remove();
			}
			// first we show the map, so leaflet can check the size
			//jQuery("#eme-edit-location-map").slideDown('fast');
			jQuery("#eme-edit-location-map").show();
			map = L.map('eme-edit-location-map', emefs_mapOptions);
			map.addLayer(osm);
			var marker = L.marker(emefs_mapCenter).addTo(map);
			var pop_content='<div class=\"eme-location-balloon\"><strong>' + loc_name +'</strong><p>' + address1 + ' ' + address2 + '<br />' + city + ' ' + state + ' ' + zip + ' ' + country + '</p></div>';
			marker.bindPopup(pop_content).openPopup();
		} else {
			emefs_loadMap(loc_name, address1, address2, city, state, zip, country);
		}
	}
});
