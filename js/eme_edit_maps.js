// to avoid the leaflet error 'Map container is already initialized' we use a global var to create the map later on
let map;
// create the tile layer with correct attribution
let osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
let osmAttrib='Map data &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
let osm = new L.TileLayer(osmUrl, {attribution: osmAttrib});

function eme_displayAddress(ignore_coord){
    const locationNameInput = document.querySelector('input#location_name');
    if (locationNameInput) {
        const eventLocation = document.querySelector('input#location_name')?.value || '';
        const eventAddress1 = document.querySelector('input#location_address1')?.value || '';
        const eventAddress2 = document.querySelector('input#location_address2')?.value || '';
        const eventCity = document.querySelector('input#location_city')?.value || '';
        const eventState = document.querySelector('input#location_state')?.value || '';
        const eventZip = document.querySelector('input#location_zip')?.value || '';
        const eventCountry = document.querySelector('input#location_country')?.value || '';
        const map_icon = document.querySelector('input#eme_loc_prop_map_icon')?.value || '';
        
        let eventLat, eventLong;
        const overrideLocCheckbox = document.querySelector('input#eme_loc_prop_override_loc');
        if (ignore_coord && (!overrideLocCheckbox || !overrideLocCheckbox.checked)) {
            eventLat = 0;
            eventLong = 0;
        } else {
            eventLat = document.querySelector('input#location_latitude')?.value || 0;
            eventLong = document.querySelector('input#location_longitude')?.value || 0;
        }
        loadMapLatLong(eventLocation, eventAddress1, eventAddress2, eventCity, eventState, eventZip, eventCountry, eventLat, eventLong, map_icon);
    }
}

function eme_SelectdisplayAddress(){
    const locationSelectName = document.querySelector('input[name="location-select-name"]');
    if (locationSelectName) {
        const eventLocation = document.querySelector('input[name="location-select-name"]')?.value || '';
        const eventAddress1 = document.querySelector('input[name="location-select-address1"]')?.value || '';
        const eventAddress2 = document.querySelector('input[name="location-select-address2"]')?.value || '';
        const eventCity = document.querySelector('input[name="location-select-city"]')?.value || '';
        const eventState = document.querySelector('input[name="location-select-state"]')?.value || '';
        const eventZip = document.querySelector('input[name="location-select-zip"]')?.value || '';
        const eventCountry = document.querySelector('input[name="location-select-country"]')?.value || '';
        const eventLat = document.querySelector('input[name="location-select-latitude"]')?.value || 0;
        const eventLong = document.querySelector('input[name="location-select-longitude"]')?.value || 0;
        const map_icon = document.querySelector('input#eme_loc_prop_map_icon')?.value || '';
        loadMapLatLong(eventLocation, eventAddress1, eventAddress2, eventCity, eventState, eventZip, eventCountry, eventLat, eventLong, map_icon);
    }
}

function loadMap(loc_name, address1, address2, city, state, zip, country, map_icon) {
    if (map_icon === undefined) {
        map_icon = '';
    }
    let myOptions = {
        zoom: 13,
        scrollWheelZoom: emeeditmaps.translate_map_zooming,
        doubleClickZoom: false
    }
    // to avoid the leaflet error 'Map container is already initialized'
    if (map) {
        map.off();
        map.remove();
    }
    
    // first we show the map, so leaflet can check the size
    const mapContainer = document.querySelector('#eme-edit-location-map');
    eme_toggle(mapContainer, true);
    map = L.map('eme-edit-location-map', myOptions);
    map.addLayer(osm);
    
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
    
    const onlineOnlyCheckbox = document.querySelector('input#eme_loc_prop_online_only');
    if (!searchKey && (!onlineOnlyCheckbox || !onlineOnlyCheckbox.checked)) {
        searchKey = loc_name;
    }

    if (searchKey) {
        let geocode_url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(searchKey);
        
        fetch(geocode_url)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    eme_toggle(mapContainer, false);
                } else {
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
                    
                    const latInput = document.querySelector('input#location_latitude');
                    const lonInput = document.querySelector('input#location_longitude');
                    const changedDiv = document.querySelector('div#eme-location-changed');
                    
                    if (latInput) latInput.value = data[0].lat;
                    if (lonInput) lonInput.value = data[0].lon;
                    if (changedDiv) eme_toggle(changedDiv, true);
                    
                    eme_toggle(mapContainer, true);
                }
            })
            .catch(() => {
                eme_toggle(mapContainer, false);
            });
    } else {
        eme_toggle(mapContainer, false);
        const changedDiv = document.querySelector('div#eme-location-changed');
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
        let latlng = L.latLng(lat, lng);
        let myOptions = {
            zoom: 13,
            center: latlng,
            scrollWheelZoom: emeeditmaps.translate_map_zooming,
            doubleClickZoom: false
        }
        // to avoid the leaflet error 'Map container is already initialized'
        if (map) {
            map.off();
            map.remove();
        }
        
        // first we show the map, so leaflet can check the size
        const mapContainer = document.querySelector('#eme-edit-location-map');
        eme_toggle(mapContainer, true);
        map = L.map('eme-edit-location-map', myOptions);
        map.addLayer(osm);
        
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
        const onlineOnlyCheckbox = document.querySelector('input#eme_loc_prop_online_only');
        const locationUrlInput = document.querySelector("input#location_url");
        const mapContainer = document.querySelector('#eme-edit-location-map');
        
        if (onlineOnlyCheckbox && onlineOnlyCheckbox.checked) {
            // Helper function to clear and set readonly
            function clearAndSetReadonly(selector) {
                const field = document.querySelector(selector);
                if (field) {
                    field.value = '';
                    field.readOnly = true;
                }
            }
            
            clearAndSetReadonly('input#location_address1');
            clearAndSetReadonly('input#location_address2');
            clearAndSetReadonly('input#location_city');
            clearAndSetReadonly('input#location_state');
            clearAndSetReadonly('input#location_zip');
            clearAndSetReadonly('input#location_country');
            clearAndSetReadonly('input#eme_loc_prop_map_icon');
            clearAndSetReadonly('input#location_latitude');
            clearAndSetReadonly('input#location_longitude');
            
            if (mapContainer)
                eme_toggle(mapContainer, false);
            
            if (locationUrlInput) locationUrlInput.required = true;
        } else {
            // Helper function to remove readonly
            function removeReadonly(selector) {
                const field = document.querySelector(selector);
                if (field) {
                    field.readOnly = false;
                }
            }
            
            removeReadonly('input#location_address1');
            removeReadonly('input#location_address2');
            removeReadonly('input#location_city');
            removeReadonly('input#location_state');
            removeReadonly('input#location_zip');
            removeReadonly('input#location_country');
            removeReadonly('input#eme_loc_prop_map_icon');
            removeReadonly('input#location_latitude');
            removeReadonly('input#location_longitude');
            
            if (mapContainer)
                eme_toggle(mapContainer, true);
            if (locationUrlInput) locationUrlInput.required = false;
            eme_displayAddress(0);
        }
    }
    
    function updateOverrideLoc() {
        const overrideLocCheckbox = document.querySelector('input#eme_loc_prop_override_loc');
        const latInput = document.querySelector('input#location_latitude');
        const lonInput = document.querySelector('input#location_longitude');
        
        if (overrideLocCheckbox && overrideLocCheckbox.checked) {
            if (latInput) latInput.readOnly = false;
            if (lonInput) lonInput.readOnly = false;
        } else {
            if (latInput) latInput.readOnly = true;
            if (lonInput) lonInput.readOnly = true;
        }
    }
    
    const mapContainer = document.querySelector('#eme-edit-location-map');
    if (mapContainer) eme_toggle(mapContainer, false);
    
    eme_displayAddress(0);
    
    // Event listeners
    const mapIconInput = document.querySelector('input[name="eme_loc_prop_map_icon"]');
    if (mapIconInput) {
        mapIconInput.addEventListener("change", function() {
            eme_displayAddress(0);
        });
    }
    
    // the location name change only needs to be trapped when not in frontend form
    // in the frontend form, this is already handled
    const frontendForm = document.querySelector('form[name=eme-fs-form]');
    if (!frontendForm) {
        const locationNameInput = document.querySelector('input#location_name');
        if (locationNameInput) {
            locationNameInput.addEventListener("change", function() {
                eme_displayAddress(0);
            });
        }
    }
    
    // Address field listeners
    const addressFields = [
        'input#location_city',
        'input#location_state', 
        'input#location_zip',
        'input#location_country',
        'input#location_address1',
        'input#location_address2'
    ];
    
    addressFields.forEach(selector => {
        const field = document.querySelector(selector);
        if (field) {
            field.addEventListener("change", function() {
                eme_displayAddress(1);
            });
        }
    });
    
    // Coordinate field listeners
    const coordinateFields = ['input#location_latitude', 'input#location_longitude'];
    coordinateFields.forEach(selector => {
        const field = document.querySelector(selector);
        if (field) {
            field.addEventListener("change", function() {
                eme_displayAddress(0);
            });
        }
    });
    
    // Checkbox listeners
    const onlineOnlyCheckbox = document.querySelector('input#eme_loc_prop_online_only');
    if (onlineOnlyCheckbox) {
        onlineOnlyCheckbox.addEventListener("change", updateOnlineOnly);
        //updateOnlineOnly(); we don't do this by default, otherwise it will interfere with eme_admin_locations
    }
    
    const overrideLocCheckbox = document.querySelector('input#eme_loc_prop_override_loc');
    if (overrideLocCheckbox) {
        overrideLocCheckbox.addEventListener("change", updateOverrideLoc);
        //updateOverrideLoc();
    }
});
