function areyousure(message) {
    if (!confirm(message)) {
        return false;
    } else {
        return true;
    }
}

function activateTab(target) {
    jQuery('.eme-tab').removeClass('active');
    jQuery('.eme-tab-content').removeClass('active');

    jQuery(`.eme-tab[data-tab="${target}"]`).addClass('active');
    jQuery(`#${target}`).addClass('active');

    if (target == "tab-locationdetails" && emeadmin.translate_map_is_active === 'true') {
        // do this only when the tab is active, so leaflet knows the visible width and height of the map
        eme_SelectdisplayAddress();
        eme_displayAddress(0);
    }
    if (target == "tab-mailings" ) {
        // do this only when the tab is active, to avoid doing mail lookups if not needed
        // Delay the trigger to ensure the tab content is fully rendered
        setTimeout(function() {
            jQuery('#searchmailingsButton').trigger('click');
        }, 100); // Adjust the delay as necessary
    }
    if (target == "tab-mailingsarchive" ) {
        // do this only when the tab is active, to avoid doing mail lookups if not needed
        // Delay the trigger to ensure the tab content is fully rendered
        setTimeout(function() {
            jQuery('#searchmailingsarchiveButton').trigger('click');
        }, 100); // Adjust the delay as necessary
    }
    if (target == "tab-sentmail" ) {
        // do this only when the tab is active, to avoid doing mail lookups if not needed
        // Delay the trigger to ensure the tab content is fully rendered
        setTimeout(function() {
            jQuery('#searchmailButton').trigger('click');
        }, 100); // Adjust the delay as necessary
    }
}

jQuery(document).ready( function($) {
    $('.eme-tab').on('click', function(e) {
        let target = $(this).data('tab');
        activateTab(target);
    });

    if ($('.eme-tabs').length) {
        // Activate tab based on data
        const preferredtab = $('.eme-tabs').data('showtab');
        if (preferredtab) {
            activateTab(preferredtab);
        } else {
            activateTab($('.eme-tab').first().data('tab')); // Default tab
        }
    }

    // let's set the default size to match placeholders if present
    $("input[placeholder]").each(function () {
        if ($(this).attr('placeholder').length>$(this).attr('size')) {
            $(this).attr('size', $(this).attr('placeholder').length);
        }
    });

    // using this on-syntax also works for selects added to the page afterwards
    $(document).on('click', 'input.select-all', function() {
        $('input.row-selector').prop('checked', this.checked)
    });
    $(document).on('click', 'input.row-selector', function() {
        if($("input.row-selector").length==$(".row-selector:checked").length) {
            $("input.select-all").prop("checked",true);
        } else {
            $("input.select-all").prop("checked",false);
        }
    });

    $('div[data-dismissible] button.notice-dismiss').on("click",function (event) {
        event.preventDefault();
        let $el = $('div[data-dismissible]');

        let attr_value, option_name, dismissible_length;

        attr_value = $el.attr('data-dismissible').split('-');

        // remove the dismissible length from the attribute value and rejoin the array.
        dismissible_length = attr_value.pop();

        option_name = attr_value.join('-');

        let ajaxdata = {
            'action': 'eme_dismiss_admin_notice',
            'option_name': option_name,
            'dismissible_length': dismissible_length,
            'eme_admin_nonce': emeadmin.translate_adminnonce
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        $.post(ajaxurl, ajaxdata);

    });

    $('#eme_attr_add_tag').on("click",function(event) {
        event.preventDefault();
        //Get All meta rows
        let metas = $('#eme_attr_body').children();
        //Copy first row and change values
        let metaCopy = $(metas[0]).clone(true);
        let newId = metas.length + 1;
        metaCopy.attr('id', 'eme_attr_'+newId);
        metaCopy.find('a').attr('rel', newId);
        metaCopy.find('[name=eme_attr_1_ref]').attr({
            name:'eme_attr_'+newId+'_ref' ,
            value:'' 
        });
        metaCopy.find('[name=eme_attr_1_content]').attr({ 
            name:'eme_attr_'+newId+'_content' , 
            value:'' 
        });
        metaCopy.find('[name=eme_attr_1_name]').attr({ 
            name:'eme_attr_'+newId+'_name' ,
            value:'' 
        });
        //Insert into end of file
        $('#eme_attr_body').append(metaCopy);
        //Duplicate the last entry, remove values and rename id
    });

    $('#eme_attr_body a').on("click",function(event) {
        event.preventDefault();
        //Only remove if there's more than 1 meta tag
        if($('#eme_attr_body').children().length > 1){
            //Remove the item
            $($(this).parent().parent().get(0)).remove();
            //Renumber all the items
            $('#eme_attr_body').children().each( function(id){
                let metaCopy = $(this);
                let oldId = metaCopy.attr('id').replace('eme_attr_','');
                let newId = id+1;
                metaCopy.attr('id', 'eme_attr_'+newId);
                metaCopy.find('a').attr('rel', newId);
                metaCopy.find('[name=eme_attr_'+ oldId +'_ref]').attr('name', 'eme_attr_'+newId+'_ref');
                metaCopy.find('[name=eme_attr_'+ oldId +'_content]').attr('name', 'eme_attr_'+newId+'_content');
                metaCopy.find('[name=eme_attr_'+ oldId +'_name]').attr( 'name', 'eme_attr_'+newId+'_name');
            });
        } else {
            let metaCopy = $($(this).parent().parent().get(0));
            metaCopy.find('[name=eme_attr_1_ref]').attr('value', '');
            metaCopy.find('[name=eme_attr_1_content]').attr('value', '');
            metaCopy.find('[name=eme_attr_1_name]').attr( 'value', '');
        }
    });

    if ($('#eme_dyndata_tbody').length) {
        new Sortable(document.getElementById('eme_dyndata_tbody'), {
            handle: '.eme-sortable-handle',
            onStart: function (evt) {
                evt.from.style.opacity = '0.6';
            },
            onEnd: function (evt) {
                evt.from.style.opacity = '1';
            }
        });
    }

    $('.eme_dyndata_add_tag').on("click",function(event) {
        event.preventDefault();
        //Get All meta rows
        let metas = $('#eme_dyndata_tbody').children();
        //Copy first row and change values
        let metaCopy = $(metas[0]).clone(true);
        let newId = 0;
        // make sure the newId doesn't exist yet
        while ($('#eme_dyndata_'+newId).length) {
            newId++;
        }
        let currentId = metaCopy.attr('id').replace('eme_dyndata_','');
        metaCopy.attr('id', 'eme_dyndata_'+newId);
        metaCopy.find('a').attr('rel', newId);
        // lets change the name, id and value for all text fields
        let metafields=['field','condition','condval','template_id_header','template_id','template_id_footer','repeat','grouping'];
        let arrayLength = metafields.length;
        for (let i = 0; i < arrayLength; i++) {
            metaCopy.find('[name="eme_dyndata['+currentId+']['+metafields[i]+']"]').attr({
                'name':'eme_dyndata['+newId+']['+metafields[i]+']' ,
                'id':'eme_dyndata['+newId+']['+metafields[i]+']'
            });
        }
        // set all values to defaults
        metaCopy.find('[name="eme_dyndata['+newId+'][field]"]').val('');
        metaCopy.find('[name="eme_dyndata['+newId+'][condition]"]').val('eq');
        metaCopy.find('[name="eme_dyndata['+newId+'][condval]"]').val('');
        metaCopy.find('[name="eme_dyndata['+newId+'][template_id_header]"]').val('0');
        metaCopy.find('[name="eme_dyndata['+newId+'][template_id]"]').val('0');
        metaCopy.find('[name="eme_dyndata['+newId+'][template_id_footer]"]').val('0');
        metaCopy.find('[name="eme_dyndata['+newId+'][repeat]"]').val('0');
        // set the html of the parent of the grouping field to empty
        // this also removes the hidden grouping field, it will dynamically added and set by EME
        metaCopy.find('[name="eme_dyndata['+newId+'][grouping]"]').parent().html('');
        // Insert at end of table
        $('#eme_dyndata_tbody').append(metaCopy);
    });

    $('.eme_remove_dyndatacondition').on("click",function(event) {
        event.preventDefault();
        //Get All meta rows
        let metas = $('#eme_dyndata_tbody').children();
        //Only remove if there's more than 1 meta tag
        if(metas.length > 1){
            //Remove the item
            $($(this).parent().parent().get(0)).remove();
        } else {
            // Get first row and change values (no clone this time)
            let metaCopy = $($(this).parent().parent().get(0));
            let newId = 0;
            // make sure the newId doesn't exist yet
            while ($('#eme_dyndata_'+newId).length) {
                newId++;
            }
            let currentId = metaCopy.attr('id').replace('eme_dyndata_','');
            metaCopy.attr('id', 'eme_dyndata_'+newId);
            metaCopy.find('a').attr('rel', newId);
            // lets change the name, id and value for all text fields
            let metafields=['field','condition','condval','template_id_header','template_id','template_id_footer','repeat','grouping'];
            let arrayLength = metafields.length;
            for (let i = 0; i < arrayLength; i++) {
                metaCopy.find('[name="eme_dyndata['+currentId+']['+metafields[i]+']"]').attr({
                    'name':'eme_dyndata['+newId+']['+metafields[i]+']' ,
                    'id':'eme_dyndata['+newId+']['+metafields[i]+']'
                });
            }
            // set all values to defaults
            metaCopy.find('[name="eme_dyndata['+newId+'][field]"]').val('');
            metaCopy.find('[name="eme_dyndata['+newId+'][condition]"]').val('eq');
            metaCopy.find('[name="eme_dyndata['+newId+'][condval]"]').val('');
            metaCopy.find('[name="eme_dyndata['+newId+'][template_id_header]"]').val('0');
            metaCopy.find('[name="eme_dyndata['+newId+'][template_id]"]').val('0');
            metaCopy.find('[name="eme_dyndata['+newId+'][template_id_footer]"]').val('0');
            metaCopy.find('[name="eme_dyndata['+newId+'][repeat]"]').val('0');
            // set the html of the parent of the grouping field to empty
            // this also removes the hidden grouping field, it will dynamically added and set by EME
            metaCopy.find('[name="eme_dyndata['+newId+'][grouping]"]').parent().html('');
            // since it is the first row, don't put stuff as required, it would prevent form submit
            for (let i = 0; i < arrayLength; i++) {
                metaCopy.find('[name="eme_dyndata['+newId+']['+metafields[i]+']"]').prop('required',false);
            }
        }
    });

    if ($('#eme_tasks_tbody').length) {
        new Sortable(document.getElementById('eme_tasks_tbody'), {
            handle: '.eme-sortable-handle',
            onStart: function (evt) {
                evt.from.style.opacity = '0.6';
            },
            onEnd: function (evt) {
                evt.from.style.opacity = '1';
            }
        });
    }
    if ($('#eme_todos_tbody').length) {
        new Sortable(document.getElementById('eme_todos_tbody'), {
            handle: '.eme-sortable-handle',
            onStart: function (evt) {
                evt.from.style.opacity = '0.6';
            },
            onEnd: function (evt) {
                evt.from.style.opacity = '1';
            }
        });
    }

    // since we don't clone the events when adding a row (because that causes trouble for cloned datepickers),
    //   we need to re-add the events on the new row (like the datepickers and the add/remove)
    //   So we'll define the eme_add_task_function/eme_remove_task_function
    $('.eme_add_task').on("click",function(event) {
        event.preventDefault();
        eme_add_task_function($(this));
    });
    $('.eme_remove_task').on("click",function(event) {
        event.preventDefault();
        eme_remove_task_function($(this));
    });
    function eme_add_task_function(myel) {
        let selectedItem = $(myel.parent().parent().get(0));
        //Get All meta rows
        //let metas = $('#eme_tasks_tbody').children();
        //Copy first row and change values, but not the events (that causes trouble for cloned datepickers)
        //let metaCopy = $(metas[0]).clone(false);
        let metaCopy = selectedItem.clone(false);
        let newId = 0;
        // make sure the newId doesn't exist yet
        while ($('#eme_row_task_'+newId).length) {
            newId++;
        }
        let currentId = metaCopy.attr('id').replace('eme_row_task_','');
        metaCopy.attr('id', 'eme_row_task_'+newId);
        metaCopy.find('a').attr('rel', newId);
        // lets change the name, id and value for all text fields
        let metafields=['task_id','name','task_start','task_end','spaces','dp_task_start','dp_task_end','description'];
        let arrayLength = metafields.length;
        for (let i = 0; i < arrayLength; i++) {
            metaCopy.find('[name="eme_tasks['+currentId+']['+metafields[i]+']"]').attr({
                'name':'eme_tasks['+newId+']['+metafields[i]+']' ,
                'id':'eme_tasks['+newId+']['+metafields[i]+']'
            });
        }
        // for the date fields
        metaCopy.find('[name="eme_tasks['+newId+'][dp_task_start]"]').attr({
            'data-alt-field':'#eme_tasks['+newId+'][task_start]'
        });
        metaCopy.find('[name="eme_tasks['+newId+'][dp_task_end]"]').attr({
            'data-alt-field':'#eme_tasks['+newId+'][task_end]'
        });
        // set all values to defaults
        metaCopy.find('[name="eme_tasks['+newId+'][name]"]').val('');
        metaCopy.find('[name="eme_tasks['+newId+'][spaces]"]').val('1');
        metaCopy.find('[name="eme_tasks['+newId+'][description]"]').val('');
        // set the html of the parent of the task_id field to empty
        // this also removes the hidden task_id field, it will dynamically added and set by EME
        metaCopy.find('[name="eme_tasks['+newId+'][task_id]"]').parent().html('');
        // Insert at end of table body
        $('#eme_tasks_tbody').append(metaCopy);
        // Now we set the datepickers and add/remove events
        $('#eme_row_task_'+newId+' .eme_formfield_fdatetime').fdatepicker({
            todayButton: new Date(),
            clearButton: true,
            closeButton: true,
            timepicker: true,
            minutesStep: parseInt(emeadmin.translate_minutesStep),
            language: emeadmin.translate_flanguage,
            firstDay: parseInt(emeadmin.translate_firstDayOfWeek),
            altFieldDateFormat: 'Y-m-d H:i:00',
            dateFormat: emeadmin.translate_fdateformat,
            timeFormat: emeadmin.translate_ftimeformat
        });
        current_start=metaCopy.find('[name="eme_tasks['+newId+'][task_start]"]').val();
        if (current_start != '') {
            js_start_obj=new Date(metaCopy.find('[name="eme_tasks['+newId+'][task_start]"]').val());
            metaCopy.find('[name="eme_tasks['+newId+'][dp_task_start]"]').fdatepicker().data('fdatepicker').selectDate(js_start_obj);
        }
        current_end=metaCopy.find('[name="eme_tasks['+newId+'][task_end]"]').val();
        if (current_end != '') {
            js_end_obj=new Date(metaCopy.find('[name="eme_tasks['+newId+'][task_end]"]').val());
            metaCopy.find('[name="eme_tasks['+newId+'][dp_task_end]"]').fdatepicker().data('fdatepicker').selectDate(js_end_obj);
        }
        $('#eme_row_task_'+newId+' .eme_add_task').on("click",function(event) {
            event.preventDefault();
            eme_add_task_function($(this));
        });
        $('#eme_row_task_'+newId+' .eme_remove_task').on("click",function(event) {
            event.preventDefault();
            eme_remove_task_function($(this));
        });
    }

    function eme_remove_task_function(myel) {
        //Get All meta rows
        let metas = $('#eme_tasks_tbody').children();
        //Only remove if there's more than 1 meta tag
        if(metas.length > 1){
            //Remove the item
            $(myel.parent().parent().get(0)).remove();
        } else {
            // Get first row and change values (no clone this time)
            let metaCopy = $(myel.parent().parent().get(0));
            let newId = 0;
            // make sure the newId doesn't exist yet
            while ($('#eme_row_task_'+newId).length) {
                newId++;
            }
            let currentId = metaCopy.attr('id').replace('eme_row_task_','');
            metaCopy.attr('id', 'eme_row_task_'+newId);
            metaCopy.find('a').attr('rel', newId);
            // lets change the name, id and value for all text fields
            let metafields=['task_id','name','task_start','task_end','spaces','dp_task_start','dp_task_end','description'];
            let arrayLength = metafields.length;
            for (let i = 0; i < arrayLength; i++) {
                metaCopy.find('[name="eme_tasks['+currentId+']['+metafields[i]+']"]').attr({
                    'name':'eme_tasks['+newId+']['+metafields[i]+']' ,
                    'id':'eme_tasks['+newId+']['+metafields[i]+']'
                });
            }
            // for the date fields
            metaCopy.find('[name="eme_tasks['+newId+'][dp_task_start]"]').attr({
                'data-alt-field':'#eme_tasks['+newId+'][task_start]'
            });
            metaCopy.find('[name="eme_tasks['+newId+'][dp_task_end]"]').attr({
                'data-alt-field':'#eme_tasks['+newId+'][task_end]'
            });
            // set all values to defaults
            metaCopy.find('[name="eme_tasks['+newId+'][name]"]').val('');
            metaCopy.find('[name="eme_tasks['+newId+'][spaces]"]').val('1');
            metaCopy.find('[name="eme_tasks['+newId+'][description]"]').val('');
            // set the html of the parent of the task_id field to empty
            // this also removes the hidden task_id field, it will dynamically added and set by EME
            metaCopy.find('[name="eme_tasks['+newId+'][task_id]"]').parent().html('');
            // since it is the first row, don't put stuff as required, it would prevent form submit
            for (let i = 0; i < arrayLength; i++) {
                metaCopy.find('[name="eme_tasks['+newId+']['+metafields[i]+']"]').prop('required',false);
            }
        }
    }
    $('#change_task_days').on("click",function (e) {
        e.preventDefault();
        let offset= parseInt($('#task_offset').val());

        let myId=0;
        while ($('[name="eme_tasks['+myId+'][task_start]"]').length) {
            current_start=$('[name="eme_tasks['+myId+'][task_start]"]').val();
            current_end=$('[name="eme_tasks['+myId+'][task_end]"]').val();
            start_obj = new Date(current_start);
            end_obj = new Date(current_end);
            start_obj.setDate(start_obj.getDate() + offset);
            end_obj.setDate(end_obj.getDate() + offset);
            $('[name="eme_tasks['+myId+'][dp_task_start]"]').fdatepicker().data('fdatepicker').selectDate(start_obj);
            $('[name="eme_tasks['+myId+'][dp_task_end]"]').fdatepicker().data('fdatepicker').selectDate(end_obj);
            myId = myId +1;
        }
    });

    // since we don't clone the events when adding a row (because that causes trouble for cloned datepickers),
    //   we need to re-add the events on the new row (like the datepickers and the add/remove)
    //   So we'll define the eme_add_todo_function/eme_remove_todo_function
    $('.eme_add_todo').on("click",function(event) {
        event.preventDefault();
        eme_add_todo_function($(this));
    });
    $('.eme_remove_todo').on("click",function(event) {
        event.preventDefault();
        eme_remove_todo_function($(this));
    });
    function eme_add_todo_function(myel) {
        let selectedItem = $(myel.parent().parent().get(0));
        //Get All meta rows
        //let metas = $('#eme_todos_tbody').children();
        //Copy first row and change values, but not the events (that causes trouble for cloned datepickers)
        //let metaCopy = $(metas[0]).clone(false);
        let metaCopy = selectedItem.clone(false);
        let newId = 0;
        // make sure the newId doesn't exist yet
        while ($('#eme_row_todo_'+newId).length) {
            newId++;
        }
        let currentId = metaCopy.attr('id').replace('eme_row_todo_','');
        metaCopy.attr('id', 'eme_row_todo_'+newId);
        metaCopy.find('a').attr('rel', newId);
        // lets change the name, id and value for all text fields
        let metafields=['todo_id','name','todo_offset','description'];
        let arrayLength = metafields.length;
        for (let i = 0; i < arrayLength; i++) {
            metaCopy.find('[name="eme_todos['+currentId+']['+metafields[i]+']"]').attr({
                'name':'eme_todos['+newId+']['+metafields[i]+']' ,
                'id':'eme_todos['+newId+']['+metafields[i]+']'
            });
        }
        // set all values to defaults
        metaCopy.find('[name="eme_todos['+newId+'][name]"]').val('');
        metaCopy.find('[name="eme_todos['+newId+'][todo_offset]"]').val('0');
        metaCopy.find('[name="eme_todos['+newId+'][description]"]').val('');
        // set the html of the parent of the todo_id field to empty
        // this also removes the hidden todo_id field, it will dynamically added and set by EME
        metaCopy.find('[name="eme_todos['+newId+'][todo_id]"]').parent().html('');
        // Insert at end of table body
        $('#eme_todos_tbody').append(metaCopy);
        // Now we add/remove events
        $('#eme_row_todo_'+newId+' .eme_add_todo').on("click",function(event) {
            event.preventDefault();
            eme_add_todo_function($(this));
        });
        $('#eme_row_todo_'+newId+' .eme_remove_todo').on("click",function(event) {
            event.preventDefault();
            eme_remove_todo_function($(this));
        });
    }

    function eme_remove_todo_function(myel) {
        //Get All meta rows
        let metas = $('#eme_todos_tbody').children();
        //Only remove if there's more than 1 meta tag
        if(metas.length > 1){
            //Remove the item
            $(myel.parent().parent().get(0)).remove();
        } else {
            // Get first row and change values (no clone this time)
            let metaCopy = $(myel.parent().parent().get(0));
            let newId = 0;
            // make sure the newId doesn't exist yet
            while ($('#eme_row_todo_'+newId).length) {
                newId++;
            }
            let currentId = metaCopy.attr('id').replace('eme_row_todo_','');
            metaCopy.attr('id', 'eme_row_todo_'+newId);
            metaCopy.find('a').attr('rel', newId);
            // lets change the name, id and value for all text fields
            let metafields=['todo_id','name','todo_offset','description'];
            let arrayLength = metafields.length;
            for (let i = 0; i < arrayLength; i++) {
                metaCopy.find('[name="eme_todos['+currentId+']['+metafields[i]+']"]').attr({
                    'name':'eme_todos['+newId+']['+metafields[i]+']' ,
                    'id':'eme_todos['+newId+']['+metafields[i]+']'
                });
            }
            // set all values to defaults
            metaCopy.find('[name="eme_todos['+newId+'][name]"]').val('');
            metaCopy.find('[name="eme_todos['+newId+'][todo_offset]"]').val('0');
            metaCopy.find('[name="eme_todos['+newId+'][description]"]').val('');
            // set the html of the parent of the todo_id field to empty
            // this also removes the hidden todo_id field, it will dynamically added and set by EME
            metaCopy.find('[name="eme_todos['+newId+'][todo_id]"]').parent().html('');
            // since it is the first row, don't put stuff as required, it would prevent form submit
            for (let i = 0; i < arrayLength; i++) {
                metaCopy.find('[name="eme_todos['+newId+']['+metafields[i]+']"]').prop('required',false);
            }
        }
    }

    $('.showhidebutton').on("click",function (e) {
        e.preventDefault();
        let elname= $(this).data( 'showhide' );
        $('#'+elname).toggle();
    });

    $('.eme_select2_members_class').select2({
        width: 'style',
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 1000,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page || 1,
                    pagesize: 30,
                    action: 'eme_members_select2',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                // parse the results into the format expected by Select2
                // since we are using custom formatting functions we do not need to
                // alter the remote JSON data, except to indicate that infinite
                // scrolling can be used
                params.page = params.page || 1;
                return {
                    results: data.Records,
                    pagination: {
                        more: (params.page * 30) < data.TotalRecordCount
                    }
                };
            },
            cache: true
        },
        placeholder: emeadmin.translate_selectmembers
    });
    $('.eme_select2_people_class').select2({
        width: 'style',
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 1000,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page || 1,
                    pagesize: 30,
                    action: 'eme_people_select2',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                // parse the results into the format expected by Select2
                // since we are using custom formatting functions we do not need to
                // alter the remote JSON data, except to indicate that infinite
                // scrolling can be used
                params.page = params.page || 1;
                return {
                    results: data.Records,
                    pagination: {
                        more: (params.page * 30) < data.TotalRecordCount
                    }
                };
            },
            cache: true
        },
        placeholder: emeadmin.translate_selectpersons
    });
    $('.eme_select2_groups_class').select2({
        placeholder: emeadmin.translate_selectgroups,
        width: 'style'
    });
    $('.eme_select2_people_groups_class').select2({
        width: 'style',
        placeholder: emeadmin.translate_anygroup
    });
    $('.eme_select2_memberstatus_class').select2({
        placeholder: emeadmin.translate_selectmemberstatus,
        width: 'style'
    });
    $('.eme_select2_memberships_class').select2({
        placeholder: emeadmin.translate_selectmemberships,
        width: 'style'
    });
    $('.eme_select2_discounts_class').select2({
        // ajax based results mess up the width, so we need to set it
        width: '100%',
        allowClear: true,
        placeholder: emeadmin.translate_selectdiscount,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 1000,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page || 1,
                    pagesize: 30,
                    action: 'eme_discounts_select2',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                // parse the results into the format expected by Select2
                // since we are using custom formatting functions we do not need to
                // alter the remote JSON data, except to indicate that infinite
                // scrolling can be used
                params.page = params.page || 1;
                return {
                    results: data.Records,
                    pagination: {
                        more: (params.page * 30) < data.TotalRecordCount
                    }
                };
            },
            cache: true
        }
    });

    $('.eme_select2_dgroups_class').select2({
        // ajax based results mess up the width, so we need to set it
        width: '100%',
        allowClear: true,
        placeholder: emeadmin.translate_selectdiscountgroup,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 1000,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page || 1,
                    pagesize: 30,
                    action: 'eme_dgroups_select2',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                // parse the results into the format expected by Select2
                // since we are using custom formatting functions we do not need to
                // alter the remote JSON data, except to indicate that infinite
                // scrolling can be used
                params.page = params.page || 1;
                return {
                    results: data.Records,
                    pagination: {
                        more: (params.page * 30) < data.TotalRecordCount
                    }
                };
            },
            cache: true
        }
    });

    // to make sure the placeholder shows after a hidden select2 is shown (bug workaround)
    $('.select2-search--inline, .select2-search__field').css('width', '100%');

    // we add the on-click to the body and limit to the .eme_del_upload-button class, so that even del-buttons that are only added dynamically are handled
    // the on-syntax propagates to dynamically added fields, but the base selector must exist (so we use body)
    $('body').on('click', '.eme_del_upload-button', function() {
        event.preventDefault();
        if (confirm(emeadmin.translate_areyousuretodeletefile)) {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let type = $(this).data('type');
            let random_id = $(this).data('random_id');
            let field_id = $(this).data('field_id');
            let extra_id = $(this).data('extra_id');
            $.post(ajaxurl, {'id': id, 'name': name, 'type': type, 'field_id': field_id, 'random_id': random_id, 'extra_id': extra_id, 'action': 'eme_del_upload', 'eme_admin_nonce': eme.translate_adminnonce }, function(data) {
                // we will delete the span, but the parent contains also the input-file field, so first count it: if the length of the parent is 2 (2 elements), show the input field too and then delete the span
                if ($('span#span_'+random_id).parent().children().length == 2) {
                    $('span#span_'+random_id).siblings("input").show();
                }
                $('span#span_'+random_id).remove();
            });
        }
    });

    $('#booking_attach_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: emeadmin.translate_addattachments,
            button: {
                text: emeadmin.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#booking_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_booking_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_booking_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_booking_attach_ids').val(tmp_ids_val);
                $('#booking_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_booking_attach_ids').val() != '') {
        $('#booking_remove_attach_button').show();
    } else {
        $('#booking_remove_attach_button').hide();
    }
    $('#booking_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#booking_attach_links').html('');
        $('#eme_booking_attach_ids').val('');
        $('#booking_remove_attach_button').hide();
    });
    $('#pending_attach_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: emeadmin.translate_addattachments,
            button: {
                text: emeadmin.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#pending_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_pending_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_pending_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_pending_attach_ids').val(tmp_ids_val);
                $('#pending_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_pending_attach_ids').val() != '') {
        $('#pending_remove_attach_button').show();
    } else {
        $('#pending_remove_attach_button').hide();
    }
    $('#pending_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#pending_attach_links').html('');
        $('#eme_pending_attach_ids').val('');
        $('#pending_remove_attach_button').hide();
    });
    $('#paid_attach_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: emeadmin.translate_addattachments,
            button: {
                text: emeadmin.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#paid_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_paid_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_paid_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_paid_attach_ids').val(tmp_ids_val);
                $('#paid_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_paid_attach_ids').val() != '') {
        $('#paid_remove_attach_button').show();
    } else {
        $('#paid_remove_attach_button').hide();
    }
    $('#paid_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#paid_attach_links').html('');
        $('#eme_paid_attach_ids').val('');
        $('#paid_remove_attach_button').hide();
    });
    $('#subscribe_attach_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: emeadmin.translate_addattachments,
            button: {
                text: emeadmin.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#subscribe_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_subscribe_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_subscribe_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_subscribe_attach_ids').val(tmp_ids_val);
                $('#subscribe_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_subscribe_attach_ids').val() != '') {
        $('#subscribe_remove_attach_button').show();
    } else {
        $('#subscribe_remove_attach_button').hide();
    }
    $('#subscribe_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#subscribe_attach_links').html('');
        $('#eme_subscribe_attach_ids').val('');
        $('#subscribe_remove_attach_button').hide();
    });

    $('#fs_ipn_attach_button').on("click",function(e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: emeadmin.translate_addattachments,
            button: {
                text: emeadmin.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            let selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // let attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#fs_ipn_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_fs_ipn_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_fs_ipn_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_fs_ipn_attach_ids').val(tmp_ids_val);
                $('#fs_ipn_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_fs_ipn_attach_ids').val() != '') {
        $('#fs_ipn_remove_attach_button').show();
    } else {
        $('#fs_ipn_remove_attach_button').hide();
    }
    $('#fs_ipn_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#fs_ipn_attach_links').html('');
        $('#eme_fs_ipn_attach_ids').val('');
        $('#fs_ipn_remove_attach_button').hide();
    });

    //$("input[placeholder]").each(function () {
    //	$(this).attr('size', $(this).attr('placeholder').length);
    //});

    // animate details summary with slidedown/up and opacity
    // we need to do this like this because css transitions don't work reliably for details/summary on all browsers for now
    $('details summary').each(function() {
        let $Wrapper = $(this).nextAll().wrapAll('<div></div>').parent();
        // Hide elements that are not open by default
        if(!$(this).parent('details').attr('open'))
            $Wrapper.hide();
        $(this).click(function(e) {
            e.preventDefault();
            if($(this).parent('details').attr('open')) {
                $Wrapper.slideUp(function() {
                    // Remove the open attribute after sliding so, so the animation is visible in browsers supporting the <details> element
                    $(this).parent('details').removeAttr('open');
                });
            } else {
                // Add the open attribute before sliding down, so the animation is visible in browsers supporting the <details> element
                $(this).parent('details').attr('open', true);
                $Wrapper
                    .hide()
                    .css('opacity', 0)
                    .slideDown('slow')
                    .animate(
                        { opacity: 1 },
                        { queue: false, duration: 'slow' }
                    );
            }
        });
    });
});

// the next is a Jtable CSV export function
function jtable_csv(container,csv_name) {
    // create a copy to avoid messing with visual layout
    let newTable = jQuery(container).clone();
    // fix HTML table

    let csvData = [];
    let delimiter = emeadmin.translate_delimiter

    //header
    let tmpRow = []; // construct header avalible array

    // th - remove attributes and header divs from jTable
    // newTable.find('th').each(function () {
    // use slice(1) to remove the first column, since that is the select box
    jQuery.each(newTable.find('th').slice(1),function () {
        if (jQuery(this).css('display') != 'none') {
            let val = jQuery(this).find('.jtable-column-header-text').text();
            tmpRow[tmpRow.length] = formatcsv(val);
        }
    });
    csvData[csvData.length] = tmpRow.join(delimiter);

    // tr - remove attributes
    //newTable.find('tr').each(function () {
    jQuery.each(newTable.find('tr'),function () {
        let tmpRow = [];
        //jQuery(this).find('td').each(function() {
        // use slice(1) to remove the first column, since that is the select box
        jQuery.each(jQuery(this).find('td').slice(1),function() {
            if (jQuery(this).css('display') != 'none') {
                if (jQuery(this).find('img').length > 0)
                    jQuery(this).html('');
                if (jQuery(this).find('button').length > 0)
                    jQuery(this).html('');
                // we take the html and replace br
                let val = jQuery(this).html();
                let regexp = new RegExp(/\<br ?\/?\>/g);
                val = val.replace(regexp, '\n');
                jQuery(this).html(val);
                tmpRow[tmpRow.length] = formatcsv(jQuery(this).text());
            }
        });
        if (tmpRow.length>0) {
            csvData[csvData.length] = tmpRow.join(delimiter);
        }
    });

    // we create a link and click on it. window.open-call to 'data:' fails on some browsers due to security limitations with the 
    // error: "Not allowed to navigate top frame to data URL 'data:text/csv;charset=utf8...."
    let mydata = csvData.join('\r\n');
    let blob = new Blob([mydata], { type: 'text/csv;charset=utf-8' });
    let link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = csv_name+'_data.csv'; // Specify the file name
    link.click();
    link.remove();

    //let url='data:text/csv;charset=utf8,' + encodeURIComponent(mydata);
    //window.open(url);
    return true;
}

function formatcsv(input) {
    // double " according to rfc4180
    let regexp = new RegExp(/["]/g);
    let output = input.replace(regexp, '""');
    //HTML
    regexp = new RegExp(/\<[^\<]+\>/g);
    output = output.replace(regexp, "");
    output = output.replace(/&nbsp;/gi,' '); //replace &nbsp;
    if (output == "") return '';
    return '"' + output.trim() + '"';
}
