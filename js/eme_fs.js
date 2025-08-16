document.addEventListener('DOMContentLoaded', function() {
    const locationNameInput = document.querySelector("input#location_name");
    
    if (locationNameInput) {
        let frontend_submit_timeout; // Declare a variable to hold the timeout ID
        
        locationNameInput.addEventListener("input", function(e) {
            e.preventDefault();
            clearTimeout(frontend_submit_timeout); // Clear the previous timeout
            
            const inputField = this;
            const inputValue = inputField.value;
            
            // Remove existing suggestions
            EME.$$(".eme-autocomplete-suggestions").forEach(el => el.remove());
            
            if (inputValue.length >= 2) {
                frontend_submit_timeout = setTimeout(function() {
                    const formData = new FormData();
                    formData.append('frontend_nonce', emefs.translate_frontendnonce);
                    formData.append('name', inputValue);
                    formData.append('action', 'eme_autocomplete_locations');
                    
                    fetch(emefs.translate_ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        const suggestions = document.createElement('div');
                        suggestions.className = 'eme-autocomplete-suggestions';
                        
                        if (data.length === 0) {
                            const noMatchDiv = document.createElement('div');
                            noMatchDiv.className = 'eme-autocomplete-suggestion';
                            noMatchDiv.innerHTML = "<strong>" + emefs.translate_nomatchlocation + '</strong>';
                            suggestions.appendChild(noMatchDiv);
                        } else {
                            data.forEach(item => {
                                const suggestionDiv = document.createElement('div');
                                suggestionDiv.className = 'eme-autocomplete-suggestion';
                                suggestionDiv.innerHTML = "<strong>" + eme_htmlDecode(item.name) + '</strong><br><small>' + eme_htmlDecode(item.address1) + ' - ' + eme_htmlDecode(item.city) + '</small>';
                                
                                suggestionDiv.addEventListener("click", function(e) {
                                    e.preventDefault();
                                    
                                    // Helper function to set field value and readonly status
                                    function setField(selector, value, readonly = true) {
                                        const field = document.querySelector(selector);
                                        if (field) {
                                            field.value = eme_htmlDecode(value);
                                            field.readOnly = readonly;
                                        }
                                    }
                                    
                                    function setFieldDisabled(selector, value, disabled = true) {
                                        const field = document.querySelector(selector);
                                        if (field) {
                                            field.value = eme_htmlDecode(value);
                                            field.disabled = disabled;
                                        }
                                    }
                                    
                                    // Set all location fields
                                    setField('input#location_id', item.location_id);
                                    setField('input#location_name', item.name);
                                    setField('input#location_address1', item.address1);
                                    setField('input#location_address2', item.address2);
                                    setField('input#location_city', item.city);
                                    setField('input#location_state', item.state);
                                    setField('input#location_zip', item.zip);
                                    setField('input#location_country', item.country);
                                    setField('input#location_latitude', item.latitude);
                                    setField('input#location_longitude', item.longitude);
                                    setField('input#location_url', item.location_url);
                                    setField('input#eme_loc_prop_map_icon', item.map_icon);
                                    setFieldDisabled('input#eme_loc_prop_online_only', item.online_only);
                                    
                                    if (typeof L !== 'undefined' && emefs.translate_map_is_active === "true") {
                                        eme_displayAddress(0);
                                    }
                                });
                                
                                suggestions.appendChild(suggestionDiv);
                            });
                        }
                        
                        // Remove any existing suggestions and add new ones
                        EME.$$('.eme-autocomplete-suggestions').forEach(el => el.remove());
                        inputField.parentNode.insertBefore(suggestions, inputField.nextSibling);
                    })
                    .catch(error => {
                        console.error('Error fetching location autocomplete:', error);
                    });
                }, 500); // Delay of 0.5 second
            }
        });

        // Remove suggestions when clicking outside
        document.addEventListener("click", function(e) {
            if (!e.target.closest('.eme-autocomplete-suggestions') && !e.target.closest('input#location_name')) {
                EME.$$(".eme-autocomplete-suggestions").forEach(el => el.remove());
            }
        });

        // Handle location name change (clearing fields)
        locationNameInput.addEventListener("change", function(e) {
            e.preventDefault();
            
            if (this.value === '') {
                // Helper function to clear field and remove readonly status
                function clearField(selector, readonly = false, disabled = false) {
                    const field = EME.$(selector);
                    if (field) {
                        field.value = '';
                        field.readOnly = readonly;
                        if (field.type !== 'checkbox') {
                            field.disabled = disabled;
                        }
                    }
                }
                
                // Clear all location fields
                clearField('input#location_id');
                clearField('input#location_name');
                clearField('input#location_address1');
                clearField('input#location_address2');
                clearField('input#location_city');
                clearField('input#location_state');
                clearField('input#location_zip');
                clearField('input#location_country');
                clearField('input#location_latitude');
                clearField('input#location_longitude');
                clearField('input#eme_loc_prop_map_icon');
                clearField('input#eme_loc_prop_online_only', false, false);
                clearField('input#location_url');
                
                if (typeof L !== 'undefined' && emefs.translate_map_is_active === "true") {
                    eme_displayAddress(0);
                }
            }
        });
    }
});
