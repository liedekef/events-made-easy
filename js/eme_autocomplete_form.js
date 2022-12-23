jQuery(document).ready( function($) {
    function eme_tasklastname_clearable() {
                if ($('input[name=task_lastname]').val()=='') {
                        $('input[name=task_lastname]').attr('readonly', false).removeClass('clearable');
                        $('input[name=task_firstname]').val('').attr('readonly', false);
                        $('input[name=task_email]').val('').attr('readonly', false);
                }
                if ($('input[name=task_lastname]').val()!='') {
                        $('input[name=task_lastname]').addClass('clearable x');
                }
    }

    // for autocomplete to work, the element needs to exist, otherwise JS errors occur
    // we check for that using length
    if ($("input[name=lastname]").length) {
	    $("input[name=lastname]").autocomplete({
		    source: function(request, response) {
			    alldata = $("input[name=lastname]").parents('form:first').serializeArray();
			    alldata.push({name: 'eme_ajax_action', value: 'rsvp_autocomplete_people'});
			    $.post(self.location.href, alldata,
				    function(data){
					    response($.map(data, function(item) {
						    return {
							    person_id: eme_htmlDecode(item.person_id),
							    lastname: eme_htmlDecode(item.lastname),
							    firstname: eme_htmlDecode(item.firstname),
							    address1: eme_htmlDecode(item.address1),
							    address2: eme_htmlDecode(item.address2),
							    city: eme_htmlDecode(item.city),
							    state: eme_htmlDecode(item.state),
							    zip: eme_htmlDecode(item.zip),
							    country: eme_htmlDecode(item.country),
							    wp_id: item.wp_id,
							    email: eme_htmlDecode(item.email),
							    phone: eme_htmlDecode(item.phone)
						    };
					    }));
				    }, "json");
		    },
		    select:function(evt, ui) {
			    // when a person is selected, populate related fields in this form
			    $('input[name=lastname]').attr('readonly', true).val(ui.item.lastname);
			    $('input[name=firstname]').attr('readonly', true).val(ui.item.firstname);
			    $('input[name=address1]').attr('readonly', true).val(ui.item.address1);
			    $('input[name=address2]').attr('readonly', true).val(ui.item.address2);
			    $('input[name=city]').attr('readonly', true).val(ui.item.city);
			    $('input[name=state]').attr('readonly', true).val(ui.item.state);
			    $('input[name=zip]').attr('readonly', true).val(ui.item.zip);
			    $('input[name=country]').attr('readonly', true).val(ui.item.country);
			    $('input[name=email]').attr('readonly', true).val(ui.item.email);
			    $('input[name=phone]').attr('readonly', true).val(ui.item.phone);
			    $('input[name=wp_id]').attr('readonly', true).val(ui.item.wp_id);
			    $('input[name=person_id]').attr('readonly', true).val(ui.item.person_id);
			    $("input[name=lastname]").addClass('clearable x');
			    $('input[name=wp_id]').trigger('input');
			    return false;
		    },
		    minLength: 2
	    }).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
		    return $( "<li></li>" )
			    .append("<a><strong>"+item.lastname+' '+item.firstname+'</strong><br /><small>'+item.email+' - '+item.phone+ '</small></a>')
			    .appendTo( ul );
	    };

	    // if this js gets loaded, the lastname is always clearable, so call those functions
	    $('input[name=lastname]').on("change",eme_lastname_clearable);
	    eme_lastname_clearable();
    }

    if ($("input[name=task_lastname]").length) {
	    $("input[name=task_lastname]").autocomplete({
		    source: function(request, response) {
			    alldata = $("input[name=task_lastname]").parents('form:first').serializeArray();
			    alldata.push({name: 'eme_ajax_action', value: 'task_autocomplete_people'});
			    $.post(self.location.href, alldata,
				    function(data){
					    response($.map(data, function(item) {
						    return {
							    lastname: eme_htmlDecode(item.lastname),
							    firstname: eme_htmlDecode(item.firstname),
							    email: eme_htmlDecode(item.email),
						    };
					    }));
				    }, "json");
		    },
		    select:function(evt, ui) {
			    // when a person is selected, populate related fields in this form
			    $('input[name=task_lastname]').attr('readonly', true).val(ui.item.lastname);
			    $('input[name=task_firstname]').attr('readonly', true).val(ui.item.firstname);
			    $('input[name=task_email]').attr('readonly', true).val(ui.item.email);
			    $('input[name=task_lastname]').addClass('clearable x');
			    return false;
		    },
		    minLength: 2
	    }).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
		    return $( "<li></li>" )
			    .append("<a><strong>"+item.lastname+' '+item.firstname+'</strong><br /><small>'+item.email+ '</small></a>')
			    .appendTo( ul );
	    };

	    // if this js gets loaded, the lastname is always clearable, so call those functions
	    //$('input[name=task_lastname]').on("input",eme_tasklastname_clearable).on("change",eme_tasklastname_clearable);
	    $('input[name=task_lastname]').on("change",eme_tasklastname_clearable);
	    eme_tasklastname_clearable();
    }
});
