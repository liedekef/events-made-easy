// Helper function to get/create map for a container
function createMapForContainer(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return null;

    // Check if map already exists on this container
    //if (container._leaflet_map) {
     //   container._leaflet_map.off();
      //  container._leaflet_map.remove();
      //  delete container._leaflet_map;
   // }
    destroyMap(containerId);

    // Create new map
    const map = L.map(containerId, {
        zoom: 13,
        scrollWheelZoom: emeeditmaps.translate_map_zooming,
        doubleClickZoom: false
    });

    // create the tile layer with correct attribution
    let osmUrl = emeeditmaps.translate_osm_url || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    let osmAttrib = emeeditmaps.translate_osm_attribution || 'Map data &copy; <a href="https://openstreetmap.org">OpenStreetMap</a>';
    let osm = new L.TileLayer(osmUrl, {attribution: osmAttrib});

    map.addLayer(osm);
    container._leaflet_map = map;
    return map;
}

function destroyMap(containerId) {
    const container = document.getElementById(containerId);
    if (container && container._leaflet_map) {
        container._leaflet_map.off();
        container._leaflet_map.remove();
        delete container._leaflet_map;
    }
}

// Browsers can fill several address fields in one go (chrome autofill) and then fire a
// burst of input/change events for them, sometimes only input, and sometimes for a partly
// filled address. That used to start one geocode request per event, on an incomplete
// address, and a late answer could hide the map again or overwrite a newer result.
// So: the field listeners coalesce the burst, one request is in flight at a time, and
// only the answer of the newest request is used.
let eme_geocodeRequest = 0;
let eme_geocodeController = null;
let eme_geocodeTimer = null;

// a new call makes the answer of a running request outdated, and stops that request
function eme_cancelGeocode() {
    eme_geocodeRequest++;
    if (eme_geocodeController) {
        eme_geocodeController.abort();
        eme_geocodeController = null;
    }
    return eme_geocodeRequest;
}

// react once the typing/autofill burst is over, reading the field values at that moment
function eme_scheduleAddressRefresh(ignore_coord) {
    clearTimeout(eme_geocodeTimer);
    eme_geocodeTimer = setTimeout(() => eme_displayAddress(ignore_coord), EME.locationGeocodeDelay);
}

// Leaflet can only measure the container when it is visible, and the map can be created
// while it sits in a hidden admin tab. Then only part of the tiles are drawn, so recalculate
// the size once the browser had the chance to lay the container out.
function eme_invalidateMapSize(containerId, map) {
    if (!map || typeof map.invalidateSize !== 'function') return;
    const container = document.getElementById(containerId);
    if (!container || container._leaflet_map !== map) return;
    requestAnimationFrame(() => {
        if (container._leaflet_map === map) map.invalidateSize();
    });
}

// A request can come back empty or fail (offline, or nominatim refusing a burst of
// requests) while the coordinates in the form are perfectly fine. Show the map on those
// known coordinates instead of hiding it: the form keeps those values either way.
function eme_showKnownCoordinates(containerId, loc_name, address1, address2, city, state, zip, country, map_icon) {
    const latInput = EME.$('input#location_latitude');
    const lonInput = EME.$('input#location_longitude');
    const lat = parseFloat(latInput ? latInput.value : '');
    const lng = parseFloat(lonInput ? lonInput.value : '');
    if (!lat || !lng) {
        const mapContainer = document.getElementById(containerId);
        if (mapContainer) eme_toggle(mapContainer, false);
        return;
    }
    // non zero coordinates take the direct path in loadMapLatLong, so no endless geocoding
    loadMapLatLong(loc_name, address1, address2, city, state, zip, country, lat, lng, map_icon);
}

// The map needs the location values, but which field to read depends on the form:
// the location form uses element ids, the location select uses field names.
// selectorFor gets both the element id and the key as used in the autocomplete result
function eme_locationMapValues(selectorFor) {
    const values = {};
    Object.entries(EME.locationFields).forEach(([id, key]) => {
        const field = EME.$(selectorFor(id, key));
        if (field) values[id] = field.value;
    });
    return values;
}

function eme_displayAddress(ignore_coord){
    if (EME.$('input#location_name')) {
        const v = eme_locationMapValues(id => '#' + id);

        let eventLat, eventLong;
        const overrideLocCheckbox = EME.$('input#eme_loc_prop_override_loc');
        if (ignore_coord && (!overrideLocCheckbox || !overrideLocCheckbox.checked)) {
            eventLat = 0;
            eventLong = 0;
        } else {
            eventLat = v.location_latitude || 0;
            eventLong = v.location_longitude || 0;
        }
        loadMapLatLong(v.location_name || '', v.location_address1 || '', v.location_address2 || '', v.location_city || '', v.location_state || '', v.location_zip || '', v.location_country || '', eventLat, eventLong, v.eme_loc_prop_map_icon || '');
    }
}

function eme_SelectdisplayAddress(){
    if (EME.$('input[name="location-select-name"]')) {
        const v = eme_locationMapValues((id, key) => `input[name="location-select-${key}"]`);
        const mapIcon = EME.$('input#eme_loc_prop_map_icon');
        loadMapLatLong(v.location_name || '', v.location_address1 || '', v.location_address2 || '', v.location_city || '', v.location_state || '', v.location_zip || '', v.location_country || '', v.location_latitude || 0, v.location_longitude || 0, mapIcon ? mapIcon.value : '');
    }
}

function loadMap(loc_name, address1, address2, city, state, zip, country, map_icon) {
    if (map_icon === undefined) {
        map_icon = '';
    }

    const containerId = 'eme-edit-location-map';
    const mapContainer = document.getElementById(containerId);
    if (!mapContainer) return;

    // a new call makes the answer of a running request outdated, and stops that request
    const request = eme_cancelGeocode();

    // first we show the container, so leaflet can check the size
    eme_toggle(mapContainer, true);

    // Create new map
    const map = createMapForContainer(containerId);
    if (!map) return;
    eme_invalidateMapSize(containerId, map);

    let searchKey_arr = [];
    if (address1) {
        searchKey_arr.push(address1);
    }
    if (address2) {
        searchKey_arr.push(address2);
    }
    if (city) {
        searchKey_arr.push(city);
    }
    if (state) {
        searchKey_arr.push(state);
    }
    if (zip) {
        searchKey_arr.push(zip);
    }
    if (country) {
        searchKey_arr.push(country);
    }
    let searchKey = searchKey_arr.join(', ');

    const onlineOnlyCheckbox = EME.$('input#eme_loc_prop_online_only');
    if (!searchKey && (!onlineOnlyCheckbox || !onlineOnlyCheckbox.checked)) {
        searchKey = loc_name;
    }

    if (searchKey) {
        let geocode_url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(searchKey);
        // one request at a time, nominatim is not fond of bursts
        eme_geocodeController = (typeof AbortController !== 'undefined') ? new AbortController() : null;

        fetch(geocode_url, eme_geocodeController ? { signal: eme_geocodeController.signal } : undefined)
            .then(response => response.json())
            .then(data => {
                // a newer keystroke, autofill or coordinate change took over meanwhile
                if (request !== eme_geocodeRequest) return;
                if (!data || data.length === 0) {
                    eme_showKnownCoordinates(containerId, loc_name, address1, address2, city, state, zip, country, map_icon);
                    return;
                }
                map.panTo([data[0].lat, data[0].lon]);
                let myIcon;
                if (map_icon !== '') {
                    myIcon = L.icon({iconUrl: map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
                } else if (emeeditmaps.translate_default_map_icon !== '') {
                    myIcon = L.icon({iconUrl: emeeditmaps.translate_default_map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
                } else {
                    myIcon = new L.Icon.Default();
                }
                let marker = L.marker([data[0].lat, data[0].lon], {icon: myIcon}).addTo(map);
                let pop_content = '<div class="eme-location-balloon"><strong>' + loc_name + '</strong><p>' + address1 + ' ' + address2 + '<br>' + city + ' ' + state + ' ' + zip + ' ' + country + '</p></div>';
                marker.bindPopup(pop_content).openPopup();

                const latInput = EME.$('input#location_latitude');
                const lonInput = EME.$('input#location_longitude');
                const changedDiv = EME.$('div#eme-location-changed');

                if (latInput) latInput.value = data[0].lat;
                if (lonInput) lonInput.value = data[0].lon;
                if (changedDiv) eme_toggle(changedDiv, true);

                eme_toggle(mapContainer, true);
                eme_invalidateMapSize(containerId, map);
            })
            .catch(() => {
                // aborted, or superseded: nothing to do, the newest request is in charge
                if (request !== eme_geocodeRequest) return;
                // offline or refused, but the coordinates in the form are still the best we have
                eme_showKnownCoordinates(containerId, loc_name, address1, address2, city, state, zip, country, map_icon);
            });
    } else {
        eme_toggle(mapContainer, false);
        const changedDiv = EME.$('div#eme-location-changed');
        if (changedDiv) eme_toggle(changedDiv, false);
    }
}

function loadMapLatLong(loc_name, address1, address2, city, state, zip, country, lat, lng, map_icon) {
    if (lat === undefined) {
        lat = 0;
    }
    if (lng === undefined) {
        lng = 0;
    }
    if (map_icon === undefined) {
        map_icon = '';
    }

    if (lat != 0 && lng != 0) {
        // a manual coordinate change is more recent than any running geocode request
        eme_cancelGeocode();
        let latlng = L.latLng(lat, lng);

        const containerId = 'eme-edit-location-map';
        // first we show the map, so leaflet can check the size
        const mapContainer = document.getElementById(containerId);
        if (!mapContainer) return;

        // first we show the container, so leaflet can check the size
        eme_toggle(mapContainer, true);

        // Create new map
        const map = createMapForContainer(containerId);
        if (!map) return;
        eme_invalidateMapSize(containerId, map);

        // go to the coordinates
        map.panTo(latlng);

        let myIcon;
        if (map_icon !== '') {
            myIcon = L.icon({iconUrl: map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
        } else if (emeeditmaps.translate_default_map_icon !== '') {
            myIcon = L.icon({iconUrl: emeeditmaps.translate_default_map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
        } else {
            myIcon = new L.Icon.Default();
        }
        let marker = L.marker(latlng, {icon: myIcon}).addTo(map);
        let pop_content = '<div class="eme-location-balloon"><strong>' + loc_name + '</strong><p>' + address1 + ' ' + address2 + '<br>' + city + ' ' + state + ' ' + zip + ' ' + country + '</p></div>';
        marker.bindPopup(pop_content).openPopup();
    } else {
        loadMap(loc_name, address1, address2, city, state, zip, country, map_icon);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    function updateOnlineOnly() {
        const onlineOnlyCheckbox = EME.$('input#eme_loc_prop_online_only');
        const locationUrlInput = EME.$('input#location_url');
        const mapContainer = EME.$('#eme-edit-location-map');
        const onlineOnly = !!(onlineOnlyCheckbox && onlineOnlyCheckbox.checked);

        if (onlineOnly) {
            // the address and coordinates are meaningless for an online-only location
            eme_clearFields(EME.locationNoGeoFields);
        }
        eme_updateLocationFieldState();

        if (mapContainer)
            eme_toggle(mapContainer, !onlineOnly);

        if (locationUrlInput) locationUrlInput.required = onlineOnly;

        if (!onlineOnly) eme_displayAddress(0);
    }

    function updateOverrideLoc() {
        const overrideLocCheckbox = EME.$('input#eme_loc_prop_override_loc');
        const overrideLoc = !!(overrideLocCheckbox && overrideLocCheckbox.checked);

        eme_lockFields(EME.locationCoordinateFields, !overrideLoc);
    }

    const mapContainer = EME.$('#eme-edit-location-map');
    if (mapContainer) eme_toggle(mapContainer, false);

    //eme_displayAddress(0); // already called in eme_activateTab

    // Event listeners
    const mapIconInput = EME.$('input[name="eme_loc_prop_map_icon"]');
    if (mapIconInput) {
        mapIconInput.addEventListener("change", function() {
            eme_displayAddress(0);
        });
    }

    // the location name change only needs to be trapped when not in frontend form
    // in the frontend form, this is already handled
    const frontendForm = EME.$('form[name=eme-fs-form]');
    if (!frontendForm) {
        const locationNameInput = EME.$('input#location_name');
        if (locationNameInput) {
            // an explicit action (like picking a location) shows the map right away, but a
            // typed or autofilled name waits for the burst to be over
            locationNameInput.addEventListener("change", function() {
                eme_scheduleAddressRefresh(0);
            });
            locationNameInput.addEventListener("input", function() {
                eme_scheduleAddressRefresh(0);
            });
        }
    }

    // Address field listeners
    EME.locationAddressFields.forEach(id => {
        const field = EME.$('input#' + id);
        if (field) {
            // chrome autofill fills these in one go and can fire only input events, or a
            // change event while the other fields are not filled yet, so react on both and
            // let eme_scheduleAddressRefresh collapse it into one geocode of the final values
            field.addEventListener("change", function() {
                eme_scheduleAddressRefresh(1);
            });
            field.addEventListener("input", function() {
                eme_scheduleAddressRefresh(1);
            });
        }
    });

    // Coordinate field listeners
    EME.locationCoordinateFields.forEach(id => {
        const field = EME.$('input#' + id);
        if (field) {
            field.addEventListener("change", function() {
                eme_displayAddress(0);
            });
        }
    });

    // Checkbox listeners
    const onlineOnlyCheckbox = EME.$('input#eme_loc_prop_online_only');
    if (onlineOnlyCheckbox) {
        onlineOnlyCheckbox.addEventListener("change", updateOnlineOnly);
        //updateOnlineOnly(); we don't do this by default, otherwise it will interfere with eme_admin_locations
    }

    const overrideLocCheckbox = EME.$('input#eme_loc_prop_override_loc');
    if (overrideLocCheckbox) {
        overrideLocCheckbox.addEventListener("change", updateOverrideLoc);
        //updateOverrideLoc();
    }
});
