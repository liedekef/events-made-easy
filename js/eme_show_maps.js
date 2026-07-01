// create the tile layer with correct attribution
let osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
let osmAttrib='Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';
let osmAttribDirections= osmAttrib + ' | ' + '<a href="https://www.openstreetmap.org/fixthemap">Fix the map</a>';

document.addEventListener('DOMContentLoaded', function() {
    // first the global map (if present)
    let divs = document.getElementsByTagName('div');
    let div_arr_map = new Array();
    
    for (let i = 0; i < divs.length; i++) {
        let div_id = divs[i].id; 
        if (div_id.indexOf("eme_global_map_") === 0) { 
            let map_id = div_id.replace("eme_global_map_","");
            let data = window['global_map_info_'+map_id];
            let marker_clustering = data.marker_clustering;
            let locations = data.locations;
            let markersList = new Array();
            let max_latitude = -500.1;
            let min_latitude = 500.1;
            let max_longitude = -500.1;
            let min_longitude = 500.1;

            let default_map_icon=data.default_map_icon;
            let zoom_factor=parseInt(data.zoom_factor);
            let enable_zooming=false;
            let letter_icons=false;
            let gestures=false;
            if (data.letter_icons === 'true') {
                letter_icons = true;
            }
            if (data.enable_zooming === 'true') {
                enable_zooming = true;
            }
            if (data.gestures === 'true') {
                gestures = true;
            }

            locations.forEach(function(item, index) {
                if (parseFloat(item.location_latitude) > max_latitude) {
                    max_latitude = parseFloat(item.location_latitude);
                }
                if (parseFloat(item.location_latitude) < min_latitude) {
                    min_latitude = parseFloat(item.location_latitude);
                }
                if (parseFloat(item.location_longitude) > max_longitude) {
                    max_longitude = parseFloat(item.location_longitude);
                }
                if (parseFloat(item.location_longitude) < min_longitude) {
                    min_longitude = parseFloat(item.location_longitude); 
                }
            });

            let center_lat = min_latitude + (max_latitude - min_latitude)/2;
            let center_lon = min_longitude + (max_longitude - min_longitude)/2;

            let lat_interval = max_latitude - min_latitude;

            //vertical compensation to fit in the markers
            let vertical_compensation = lat_interval * 0.1;

            // we don't use an initial zoom level, later on we zoom using fitbounds to show all locations at max allowed zoom level
            let myOptions = {
                center: L.latLng(center_lat + vertical_compensation,center_lon),
                doubleClickZoom: false,
                scrollWheelZoom: enable_zooming,
                gestureHandling: gestures
            };
            // in JS: var keeps the scope of a variable to the FUNCTION, not just the LOOP
            // so: we can't just reuse the same name here (like e.g.: var mymap=L.map ....,
            // since "mymap" was already used in the loop before and adding "var" does not reinit the variable)
            // The simple solution: use an array to store your stuff
            div_arr_map[i] = L.map(div_id, myOptions);
            // add the title layer, we also add a class that can switch to darkmode (css) if needed
            L.tileLayer(osmUrl, {attribution: osmAttrib, className: 'eme-map-tiles'}).addTo(div_arr_map[i]);
            // if a popup contains an image, the size might be wrong, try to rectify with a popup update
            // based on https://stackoverflow.com/questions/38170366/leaflet-adjust-popup-to-picture-size
            div_arr_map[i].on("popupopen", function(e) {
                const popupImages = document.querySelectorAll(".leaflet-popup-content img");
                if (popupImages.length > 0) {
                    const lastImage = popupImages[popupImages.length - 1];
                    lastImage.addEventListener("load", function() {
                        e.popup.update();
                    });
                }
            });

            let markers;
            if (marker_clustering == 'true') {
                markers = L.markerClusterGroup();
            }

            locations.forEach(function(item, index) {
                let letter;
                let myIcon;
                if (index > 25) {
                    let rest = index % 26;
                    let firstindex = Math.floor(index/26) - 1;
                    letter = String.fromCharCode("A".charCodeAt(0) + firstindex) + String.fromCharCode("A".charCodeAt(0) + rest);
                } else {
                    letter = String.fromCharCode("A".charCodeAt(0) + index);
                }

                // create custom marker icons using the letter(s) above
                if (letter_icons) {
                    myIcon = L.divIcon({className: 'eme-map-marker', iconSize: [28,28], html: letter});
                } else {
                    if (item.map_icon != '') {
                        myIcon = L.icon({iconUrl: item.map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
                    } else if (default_map_icon != '') {
                        myIcon = L.icon({iconUrl: default_map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
                    } else {
                        myIcon = new L.Icon.Default();
                    }
                }

                let point = L.latLng(parseFloat(item.location_latitude), parseFloat(item.location_longitude));
                let balloon_content = "<div class='eme-location-balloon'>" + eme_htmlDecode(item.location_balloon) + "</div>";
                let marker = L.marker(point, {icon: myIcon});
                marker.bindPopup(balloon_content, { maxWidth: 1600 });
                if (marker_clustering == 'true' ) {
                    markers.addLayer(marker);
                } else {
                    marker.addTo(div_arr_map[i]);
                }
                // Add to list of markers
                markersList[item.location_id] = marker;
                
                const locationLink = document.getElementById('location-' + item.location_id + "_" + map_id);
                if (locationLink) {
                    const linkElement = locationLink.querySelector('a');
                    if (linkElement) {
                        linkElement.addEventListener('click', function() {
                            const mapDiv = document.getElementById('eme_global_map_' + map_id);
                            eme_scrollToEl(mapDiv);
                            if (marker_clustering == 'true' ) {
                                let m = markersList[item.location_id];
                                markers.zoomToShowLayer(m, function() {
                                    m.openPopup();
                                });
                            } else {
                                marker.openPopup();
                            }
                        });
                    }
                }
            });
            
            if (marker_clustering == 'true') {
                markers.addTo(div_arr_map[i]);
            }
            // now zoom the map to a level that shows all markers at max zoom level
            div_arr_map[i].fitBounds([
                [min_latitude,min_longitude],
                [max_latitude,max_longitude]
            ]);
        }

        // and now for the normal maps (if any)
        if(div_id.indexOf("eme-location-map_") === 0) { 
            const currentDiv = divs[i];
            let lat_id = parseFloat(currentDiv.dataset.lat);
            let lon_id = parseFloat(currentDiv.dataset.lon);
            let map_icon = currentDiv.dataset.map_icon;
            let default_map_icon = currentDiv.dataset.default_map_icon;
            let mapCenter = L.latLng(lat_id + 0.005, lon_id - 0.003);
            let myOptions = {
                zoom: parseInt(currentDiv.dataset.zoom_factor),
                center: mapCenter,
                doubleClickZoom: false,
                scrollWheelZoom: currentDiv.dataset.enable_zooming === 'true',
                gestureHandling: currentDiv.dataset.gestures === 'true'
            };
            
            let myIcon;
            if (map_icon != '') {
                myIcon = L.icon({iconUrl: map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
            } else if (default_map_icon != '') {
                myIcon = L.icon({iconUrl: default_map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
            } else {
                myIcon = new L.Icon.Default();
            }
            // in JS: var keeps the scope of a variable to the FUNCTION, not just the LOOP
            // so: we can't just reuse the same name here (like e.g.: var mymap=L.map ....,
            // since "mymap" was already used in the loop before and adding "var" does not reinit the variable)
            // The simple solution: use an array to store your stuff
            div_arr_map[i] = L.map(div_id, myOptions);
            // add the title layer, we also add a class that can switch to darkmode (css) if needed
            L.tileLayer(osmUrl, {attribution: osmAttrib, className: 'eme-map-tiles'}).addTo(div_arr_map[i]);
            // if a popup contains an image, the size might be wrong, try to rectify with a popup update
            // based on https://stackoverflow.com/questions/38170366/leaflet-adjust-popup-to-picture-size
            div_arr_map[i].on("popupopen", function(e) {
                const popupImages = document.querySelectorAll(".leaflet-popup-content img");
                if (popupImages.length > 0) {
                    const lastImage = popupImages[popupImages.length - 1];
                    lastImage.addEventListener("load", function() {
                        e.popup.update();
                    });
                }
            });
            // define the popup and marker
            let s_popcontent = "<div class='eme-location-balloon'>" + currentDiv.dataset.map_text + "</div>";
            let s_marker = L.marker(L.latLng(lat_id, lon_id), {icon: myIcon}).addTo(div_arr_map[i]);
            // now show the markter and popup
            s_marker.bindPopup(s_popcontent, { maxWidth: 1600 }).openPopup();
        }

    }
});

// directions form handling (separate event listener for the forms)
document.addEventListener('DOMContentLoaded', function() {
    let dirForms = document.querySelectorAll('.eme-directions-form');
    let dirMaps = {};

    function eme_render_instructions(instructionsDiv, route) {
        let summary = route.summary;
        let totalDist = summary.totalDistance;
        let totalTime = summary.totalTime;

        let distStr = totalDist >= 1000 ? (totalDist / 1000).toFixed(1) + ' km' : Math.round(totalDist) + ' m';
        let timeStr = '';
        if (totalTime >= 3600) {
            timeStr = Math.floor(totalTime / 3600) + ' h ' + Math.round((totalTime % 3600) / 60) + ' min';
        } else {
            timeStr = Math.round(totalTime / 60) + ' min';
        }

        let html = '<div class="eme-itinerary-summary">' + distStr + ' &mdash; ' + timeStr + '</div>';
        html += '<ol class="eme-itinerary-steps">';
        route.instructions.forEach(function(instr) {
            let instrDist = instr.distance >= 1000 ? (instr.distance / 1000).toFixed(1) + ' km' : Math.round(instr.distance) + ' m';
            html += '<li>' + eme_escapeHtml(instr.text) + ' <span class="eme-itinerary-distance">(' + instrDist + ')</span></li>';
        });
        html += '</ol>';

        instructionsDiv.innerHTML = html;
        instructionsDiv.style.display = '';
    }

    dirForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            let mapId = form.dataset.mapId;
            let instructionsId = form.dataset.instructionsId;
            let originInput = form.querySelector('.eme-directions-origin');
            let origin = originInput.value.trim();

            if (!origin) {
                return;
            }

            let destLat = parseFloat(form.querySelector('[name="eme_directions_dest_lat"]').value);
            let destLon = parseFloat(form.querySelector('[name="eme_directions_dest_lon"]').value);
            let destAddress = form.querySelector('[name="eme_directions_dest_address"]').value;

            let mapDiv = document.getElementById(mapId);
            let instructionsDiv = document.getElementById(instructionsId);

            // geocode origin via Nominatim
            let geocodeUrl = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(origin);

            fetch(geocodeUrl)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (!data || data.length === 0) {
                        alert('Address not found');
                        return;
                    }

                    let originLatLng = L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon));
                    let destLatLng = L.latLng(destLat, destLon);
                    let zoomFactor = parseInt(mapDiv.dataset.zoom_factor) || 10;
                    if (zoomFactor > 14) zoomFactor = 14;

                    // remove existing map if present
                    if (dirMaps[mapId]) {
                        dirMaps[mapId].remove();
                        delete dirMaps[mapId];
                    }

                    mapDiv.style.display = '';

                    let myOptions = {
                        zoom: zoomFactor,
                        center: originLatLng,
                        doubleClickZoom: false,
                        scrollWheelZoom: mapDiv.dataset.enable_zooming === 'true',
                        gestureHandling: mapDiv.dataset.gestures === 'true'
                    };

                    let map = L.map(mapId, myOptions);
                    L.tileLayer(osmUrl, {attribution: osmAttribDirections, className: 'eme-map-tiles'}).addTo(map);
                    L.marker(destLatLng).addTo(map).bindPopup(destAddress);

                    var router = L.Routing.osrmv1({
                        serviceUrl: mapDiv.dataset.osrmUrl
                    });

                    var routingControl = L.Routing.control({
                        router: router,
                        waypoints: [
                            originLatLng,
                            destLatLng
                        ],
                        show: false,
                        routeWhileDragging: true,
                        showAlternatives: false,
                        addWaypoints: true,
                        createMarker: function(i, wp, n) {
                            var iconHtml = '<div class="eme-directions-waypoint-icon-inner">' +
                                '<span class="eme-directions-waypoint-label">' + (i + 1) + '</span>';
                            if (n > 2 && i>0 && i<n-1) {
                                iconHtml += '<span class="eme-directions-remove-wp">&#10005;</span>';
                            }
                            iconHtml += '</div>';
                            var m = L.marker(wp.latLng, {
                                icon: L.divIcon({
                                    className: 'eme-directions-waypoint-icon',
                                    html: iconHtml,
                                    iconSize: [32, 32],
                                    iconAnchor: [16, 16]
                                }),
                                draggable: true,
                                zIndexOffset: 1000
                            });
                            m.on('add', function() {
                                var el = m.getElement();
                                if (el) {
                                    var btn = el.querySelector('.eme-directions-remove-wp');
                                    if (btn) {
                                        L.DomEvent.on(btn, 'click', function(e) {
                                            L.DomEvent.stopPropagation(e);
                                            L.DomEvent.preventDefault(e);
                                            routingControl.spliceWaypoints(i, 1);
                                        });
                                    }
                                }
                            });
                            return m;
                        }
                    }).addTo(map);

                    routingControl.on('routesfound', function(e) {
                        let routes = e.routes;
                        if (routes && routes.length > 0) {
                            eme_render_instructions(instructionsDiv, routes[0]);
                        }
                    });

                    routingControl.on('routingerror', function() {
                        instructionsDiv.innerHTML = '<div class="eme-itinerary-error">' + 'Could not calculate route' + '</div>';
                        instructionsDiv.style.display = '';
                    });

                    dirMaps[mapId] = map;

                    // scroll to the map after a brief delay to let the map render
                    setTimeout(function() {
                        mapDiv.scrollIntoView({behavior: 'smooth', block: 'start'});
                        map.invalidateSize();
                    }, 100);
                })
                .catch(function() {
                    alert('Could not find your address');
                });
        });
    });
});

function eme_escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
