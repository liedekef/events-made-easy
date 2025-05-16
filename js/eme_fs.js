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
                                    .html("<strong>"+eme_htmlDecode(item.name)+'</strong><br /><small>'+eme_htmlDecode(item.address1)+' - '+eme_htmlDecode(item.city)+ '</small>')
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        $('input#location_id').val(eme_htmlDecode(item.location_id)).attr("readonly", true);
                                        $('input#location_name').val(eme_htmlDecode(item.name)).attr("readonly", true);
                                        $('input#location_address1').val(eme_htmlDecode(item.address1)).attr("readonly", true);
                                        $('input#location_address2').val(eme_htmlDecode(item.address2)).attr("readonly", true);
                                        $('input#location_city').val(eme_htmlDecode(item.city)).attr("readonly", true);
                                        $('input#location_state').val(eme_htmlDecode(item.state)).attr("readonly", true);
                                        $('input#location_zip').val(eme_htmlDecode(item.zip)).attr("readonly", true);
                                        $('input#location_country').val(eme_htmlDecode(item.country)).attr("readonly", true);
                                        $('input#location_latitude').val(eme_htmlDecode(item.latitude)).attr("readonly", true);
                                        $('input#location_longitude').val(eme_htmlDecode(item.longitude)).attr("readonly", true);
                                        $('input#location_url').val(eme_htmlDecode(item.location_url)).attr("readonly", true);
                                        $('input#eme_loc_prop_map_icon').val(eme_htmlDecode(item.map_icon)).attr("readonly", true);
                                        $('input#eme_loc_prop_online_only').val(eme_htmlDecode(item.online_only)).attr("disabled", true);
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

    if (emefs.translate_htmleditor=='jodit') {
	    Jodit.modules.Icon.set('insertNbsp','<svg viewBox="0 0 100 40" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="96" height="36" rx="6" ry="6" fill="#f0f0f0" stroke="#333" stroke-width="3"/></svg>');
	    Jodit.modules.Icon.set('insertFromMediaLibrary','<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M20 3H4C2.897 3 2 3.897 2 5v14c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V5c0-1.103-.897-2-2-2zM4 5h16v8.586l-3.293-3.293a1 1 0 0 0-1.414 0L11 14l-2.293-2.293a1 1 0 0 0-1.414 0L4 14.586V5zm0 14v-2.586l4-4 2.293 2.293a1 1 0 0 0 1.414 0L16 11.414l4 4V19H4z"/></svg>');
	    Jodit.defaultOptions.controls.insertNbsp = {
		    icon: 'insertNbsp',
		    tooltip: emefs.translate_insertnbsp,
		    exec: function (editor) {
			    editor.selection.insertHTML('&nbsp;');
		    }
	    };
	    Jodit.defaultOptions.controls.insertFromMediaLibrary = {
		    icon: 'insertFromMediaLibrary',
		    exec: function (editor) {
			    const frame = wp.media({
				    multiple: true
			    });
			    frame.on('select', function () {
				    const selection = frame.state().get('selection');
				    selection.each(function (attachment) {
					    const file = attachment.toJSON();
					    const img = file.sizes && file.sizes.medium ? file.sizes.medium : file;
					    const imgHTML = `<img src="${img.url}" width="${img.width}" height="${img.height}" alt="${file.alt || ''}"/>`;
					    editor.selection.insertHTML(imgHTML);
				    });
				    frame.off('select'); // Avoid duplicate inserts
			    });
			    frame.open();
		    },
		    tooltip: emefs.translate_insertfrommedia
	    };

	    $('.eme-fs-editor').each(function () {
		    const $textarea = $(this);
		    const allowupload = ($textarea.data('allowupload')==='yes')

		    const editor = new Jodit($textarea[0], {
			    height: 300,
			    toolbarSticky: false,
			    toolbarAdaptive: false,
			    language: emefs.translate_flanguage,
			    showCharsCounter: false,
			    showWordsCounter: false,
			    hidePoweredByJodit: true,
			    buttons: [
				    'undo', 'redo',
				    '|', 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript',
				    '|', 'paragraph', 'fontsize', 'font', 'lineHeight',
				    '|', 'brush',
				    '|', 'source', 'fullsize',
				    '\n',
				    '|', 'align', 'outdent', 'indent',
				    '|', 'ul', 'ol',
				    '|', 'table', 'symbols',
				    '|', 'link', 'image', 'insertFromMediaLibrary',
				    '|', 'hr', 'insertNbsp', 'eraser'
			    ]
		    });
		    editor.events.on('afterOpenPopup.link', popup => {
			    const popupEl = popup.container;    // <-- the real DOM element
			    // find the URL and content input
			    const urlField = popupEl.querySelector('input[data-ref="url_input"]');
			    const urlval = urlField.value.trim();
			    // if empty url, we'll add something usefull based on content_input
			    if (!urlval) {
				    const contentval = popupEl.querySelector('input[data-ref="content_input"]').value.trim();
				    // Email case
				    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				    if (emailRegex.test(contentval)) {
					    urlField.value = 'mailto:' + contentval;
					    return;
				    }
				    // URL case
				    const urlRegex = /^(https?:\/\/)/i;
				    if (urlRegex.test(contentval)) {
					    urlField.value = contentval;
					    return;
				    }
				    // Default: add https://
				    urlField.value = 'https://' + contentval;
			    }
		    });
	    });
    }
});
