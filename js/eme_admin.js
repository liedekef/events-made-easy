function eme_activateTab(target) {
    jQuery('.eme-tab').removeClass('active');
    jQuery('.eme-tab-content').removeClass('active');

    jQuery(`.eme-tab[data-tab="${target}"]`).addClass('active');
    jQuery(`#${target}`).addClass('active');

    if (target == "tab-locationdetails" && emeadmin.translate_map_is_active === 'true') {
        // do this only when the tab is active, so leaflet knows the visible width and height of the map
        // Delay the display to ensure the tab content is fully rendered
        setTimeout(function() {
            eme_SelectdisplayAddress();
            eme_displayAddress(0);
        }, 100); // Adjust the delay as necessary
    }
    if (target == "tab-mailings" ) {
        // do this only when the tab is active, to avoid doing mail lookups if not needed
        // Delay the trigger to ensure the tab content is fully rendered
        setTimeout(function() {
            jQuery('#MailingsTableContainer').jtable('recalcColumnWidths');
            jQuery('#MailingsLoadRecordsButton').trigger('click');
        }, 100); // Adjust the delay as necessary
    }
    if (target == "tab-mailingsarchive" ) {
        // do this only when the tab is active, to avoid doing mail lookups if not needed
        // Delay the trigger to ensure the tab content is fully rendered
        setTimeout(function() {
            jQuery('#ArchivedMailingsTableContainer').jtable('recalcColumnWidths');
            jQuery('#ArchivedMailingsLoadRecordsButton').trigger('click');
        }, 100); // Adjust the delay as necessary
    }
    if (target == "tab-allmail" ) {
        // do this only when the tab is active, to avoid doing mail lookups if not needed
        // Delay the trigger to ensure the tab content is fully rendered
        setTimeout(function() {
            jQuery('#MailsTableContainer').jtable('recalcColumnWidths');
            jQuery('#MailsLoadRecordsButton').trigger('click');
        }, 100); // Adjust the delay as necessary
    }
}

jQuery(document).ready(function($) {
    // --- Tasks Add/Remove (from previous version) ---
    function eme_add_task_function(myel) {
        let selectedItem = $(myel.parent().parent().get(0));
        let metaCopy = selectedItem.clone(false);
        let newId = 0;
        while ($('#eme_row_task_'+newId).length) newId++;
        let currentId = metaCopy.attr('id').replace('eme_row_task_','');
        metaCopy.attr('id', 'eme_row_task_'+newId);
        metaCopy.find('a').attr('rel', newId);
        metaCopy.find('[name="eme_tasks['+currentId+'][signup_count]"]').remove();
        let metafields=['task_id','name','task_start','task_end','spaces','dp_task_start','dp_task_end','description'];
        metafields.forEach(f => {
            metaCopy.find('[name="eme_tasks['+currentId+']['+f+']"]').attr({
                'name':'eme_tasks['+newId+']['+f+']',
                'id':'eme_tasks['+newId+']['+f+']'
            });
        });
        metaCopy.find('[name="eme_tasks['+newId+'][dp_task_start]"]').attr({
            'data-alt-field':'#eme_tasks['+newId+'][task_start]'
        });
        metaCopy.find('[name="eme_tasks['+newId+'][dp_task_end]"]').attr({
            'data-alt-field':'#eme_tasks['+newId+'][task_end]'
        });
        metaCopy.find('[name="eme_tasks['+newId+'][name]"]').val('');
        metaCopy.find('[name="eme_tasks['+newId+'][spaces]"]').val('1');
        metaCopy.find('[name="eme_tasks['+newId+'][description]"]').val('');
        metaCopy.find('[name="eme_tasks['+newId+'][task_id]"]').parent().html('');
        $('#eme_tasks_tbody').append(metaCopy);
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
        let current_start = metaCopy.find('[name="eme_tasks['+newId+'][task_start]"]').val();
        if (current_start != '') {
            let js_start_obj = new Date(current_start);
            metaCopy.find('[name="eme_tasks['+newId+'][dp_task_start]"]').fdatepicker().data('fdatepicker').selectDate(js_start_obj);
        }
        let current_end = metaCopy.find('[name="eme_tasks['+newId+'][task_end]"]').val();
        if (current_end != '') {
            let js_end_obj = new Date(current_end);
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
        let metas = $('#eme_tasks_tbody').children();
        if(metas.length > 1){
            $(myel.parent().parent().get(0)).remove();
        } else {
            let metaCopy = $(myel.parent().parent().get(0));
            let newId = 0;
            while ($('#eme_row_task_'+newId).length) newId++;
            let currentId = metaCopy.attr('id').replace('eme_row_task_','');
            metaCopy.attr('id', 'eme_row_task_'+newId);
            metaCopy.find('a').attr('rel', newId);
            let metafields=['task_id','name','task_start','task_end','spaces','dp_task_start','dp_task_end','description'];
            metafields.forEach(f => {
                metaCopy.find('[name="eme_tasks['+currentId+']['+f+']"]').attr({
                    'name':'eme_tasks['+newId+']['+f+']',
                    'id':'eme_tasks['+newId+']['+f+']'
                });
            });
            metaCopy.find('[name="eme_tasks['+newId+'][dp_task_start]"]').attr({
                'data-alt-field':'#eme_tasks['+newId+'][task_start]'
            });
            metaCopy.find('[name="eme_tasks['+newId+'][dp_task_end]"]').attr({
                'data-alt-field':'#eme_tasks['+newId+'][task_end]'
            });
            metaCopy.find('[name="eme_tasks['+newId+'][name]"]').val('');
            metaCopy.find('[name="eme_tasks['+newId+'][spaces]"]').val('1');
            metaCopy.find('[name="eme_tasks['+newId+'][description]"]').val('');
            metaCopy.find('[name="eme_tasks['+newId+'][task_id]"]').parent().html('');
            metafields.forEach(f => {
                metaCopy.find('[name="eme_tasks['+newId+']['+f+']"]').prop('required',false);
            });
        }
    }

    function eme_add_todo_function(myel) {
        let selectedItem = $(myel.parent().parent().get(0));
        let metaCopy = selectedItem.clone(false);
        let newId = 0;
        while ($('#eme_row_todo_'+newId).length) newId++;
        let currentId = metaCopy.attr('id').replace('eme_row_todo_','');
        metaCopy.attr('id', 'eme_row_todo_'+newId);
        metaCopy.find('a').attr('rel', newId);
        let metafields=['todo_id','name','todo_offset','description'];
        metafields.forEach(f => {
            metaCopy.find('[name="eme_todos['+currentId+']['+f+']"]').attr({
                'name':'eme_todos['+newId+']['+f+']',
                'id':'eme_todos['+newId+']['+f+']'
            });
        });
        metaCopy.find('[name="eme_todos['+newId+'][name]"]').val('');
        metaCopy.find('[name="eme_todos['+newId+'][todo_offset]"]').val('0');
        metaCopy.find('[name="eme_todos['+newId+'][description]"]').val('');
        metaCopy.find('[name="eme_todos['+newId+'][todo_id]"]').parent().html('');
        $('#eme_todos_tbody').append(metaCopy);
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
        let metas = $('#eme_todos_tbody').children();
        if(metas.length > 1){
            $(myel.parent().parent().get(0)).remove();
        } else {
            let metaCopy = $(myel.parent().parent().get(0));
            let newId = 0;
            while ($('#eme_row_todo_'+newId).length) newId++;
            let currentId = metaCopy.attr('id').replace('eme_row_todo_','');
            metaCopy.attr('id', 'eme_row_todo_'+newId);
            metaCopy.find('a').attr('rel', newId);
            let metafields=['todo_id','name','todo_offset','description'];
            metafields.forEach(f => {
                metaCopy.find('[name="eme_todos['+currentId+']['+f+']"]').attr({
                    'name':'eme_todos['+newId+']['+f+']',
                    'id':'eme_todos['+newId+']['+f+']'
                });
            });
            metaCopy.find('[name="eme_todos['+newId+'][name]"]').val('');
            metaCopy.find('[name="eme_todos['+newId+'][todo_offset]"]').val('0');
            metaCopy.find('[name="eme_todos['+newId+'][description]"]').val('');
            metaCopy.find('[name="eme_todos['+newId+'][todo_id]"]').parent().html('');
            metafields.forEach(f => {
                metaCopy.find('[name="eme_todos['+newId+']['+f+']"]').prop('required',false);
            });
        }
    }

    // --- Tab Binding and Default Activation ---
    $('.eme-tab').on('click', function(e) {
        let target = $(this).data('tab');
        eme_activateTab(target);
    });
    if ($('.eme-tabs').length) {
        const preferredtab = $('.eme-tabs').data('showtab');
        if (preferredtab) {
            eme_activateTab(preferredtab);
        } else if ($_GET['page'] && $_GET['page']=='eme-emails') {
            eme_activateTab('tab-genericmails');
        } else {
            eme_activateTab($('.eme-tab').first().data('tab'));
        }
    }

    // --- Input Placeholder Sizing ---
    $("input[placeholder]").each(function () {
        if ($(this).attr('placeholder').length > $(this).attr('size')) {
            $(this).attr('size', $(this).attr('placeholder').length);
        }
    });

    $(document).on('click', '.eme-dismiss-notice', function(e) {
        e.preventDefault();
        var notice = $(this).data('notice');
        var noticeDiv = $(this).closest('.notice');
        
        $.post(ajaxurl, {
            action: 'eme_dismiss_notice',
            notice: notice,
            eme_admin_nonce: emeadmin.translate_adminnonce
        }, function(response) {
            if (response.success) {
                noticeDiv.fadeOut();
            }
        });
    });

    // --- Attribute metabox add/remove ---
    $('#eme_attr_add_tag').on("click",function(event) {
        event.preventDefault();
        let metas = $('#eme_attr_body').children();
        let metaCopy = $(metas[0]).clone(true);
        let newId = metas.length + 1;
        metaCopy.attr('id', 'eme_attr_'+newId);
        metaCopy.find('a').attr('rel', newId);
        metaCopy.find('[name=eme_attr_1_ref]').attr({ name:'eme_attr_'+newId+'_ref', value:'' });
        metaCopy.find('[name=eme_attr_1_content]').attr({ name:'eme_attr_'+newId+'_content', value:'' });
        metaCopy.find('[name=eme_attr_1_name]').attr({ name:'eme_attr_'+newId+'_name', value:'' });
        $('#eme_attr_body').append(metaCopy);
    });

    $('#eme_attr_body').on("click", "a", function(event) {
        event.preventDefault();
        let $body = $('#eme_attr_body');
        if($body.children().length > 1){
            $($(this).parent().parent().get(0)).remove();
            $body.children().each(function(id){
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
            metaCopy.find('[name=eme_attr_1_name]').attr('value', '');
        }
    });

    // --- DynData add/remove and sortable ---
    if ($('#eme_dyndata_tbody').length) {
        new Sortable(document.getElementById('eme_dyndata_tbody'), {
            handle: '.eme-sortable-handle',
            onStart: function (evt) { evt.from.style.opacity = '0.6'; },
            onEnd: function (evt) { evt.from.style.opacity = '1'; }
        });
    }
    $('.eme_dyndata_add_tag').on("click",function(event) {
        event.preventDefault();
        let metas = $('#eme_dyndata_tbody').children();
        let metaCopy = $(metas[0]).clone(true);
        let newId = 0; while ($('#eme_dyndata_'+newId).length) newId++;
        let currentId = metaCopy.attr('id').replace('eme_dyndata_','');
        metaCopy.attr('id', 'eme_dyndata_'+newId);
        metaCopy.find('a').attr('rel', newId);
        let metafields=['field','condition','condval','template_id_header','template_id','template_id_footer','repeat','grouping'];
        metafields.forEach(f => {
            metaCopy.find('[name="eme_dyndata['+currentId+']['+f+']"]').attr({
                'name':'eme_dyndata['+newId+']['+f+']' ,
                'id':'eme_dyndata['+newId+']['+f+']'
            });
        });
        metaCopy.find('[name="eme_dyndata['+newId+'][field]"]').val('');
        metaCopy.find('[name="eme_dyndata['+newId+'][condition]"]').val('eq');
        metaCopy.find('[name="eme_dyndata['+newId+'][condval]"]').val('');
        metaCopy.find('[name="eme_dyndata['+newId+'][template_id_header]"]').val('0');
        metaCopy.find('[name="eme_dyndata['+newId+'][template_id]"]').val('0');
        metaCopy.find('[name="eme_dyndata['+newId+'][template_id_footer]"]').val('0');
        metaCopy.find('[name="eme_dyndata['+newId+'][repeat]"]').val('0');
        metaCopy.find('[name="eme_dyndata['+newId+'][grouping]"]').parent().html('');
        $('#eme_dyndata_tbody').append(metaCopy);
    });
    $('.eme_remove_dyndatacondition').on("click",function(event) {
        event.preventDefault();
        let metas = $('#eme_dyndata_tbody').children();
        if(metas.length > 1){
            $($(this).parent().parent().get(0)).remove();
        } else {
            let metaCopy = $($(this).parent().parent().get(0));
            let newId = 0; while ($('#eme_dyndata_'+newId).length) newId++;
            let currentId = metaCopy.attr('id').replace('eme_dyndata_','');
            metaCopy.attr('id', 'eme_dyndata_'+newId);
            metaCopy.find('a').attr('rel', newId);
            let metafields=['field','condition','condval','template_id_header','template_id','template_id_footer','repeat','grouping'];
            metafields.forEach(f => {
                metaCopy.find('[name="eme_dyndata['+currentId+']['+f+']"]').attr({
                    'name':'eme_dyndata['+newId+']['+f+']' ,
                    'id':'eme_dyndata['+newId+']['+f+']'
                });
            });
            metaCopy.find('[name="eme_dyndata['+newId+'][field]"]').val('');
            metaCopy.find('[name="eme_dyndata['+newId+'][condition]"]').val('eq');
            metaCopy.find('[name="eme_dyndata['+newId+'][condval]"]').val('');
            metaCopy.find('[name="eme_dyndata['+newId+'][template_id_header]"]').val('0');
            metaCopy.find('[name="eme_dyndata['+newId+'][template_id]"]').val('0');
            metaCopy.find('[name="eme_dyndata['+newId+'][template_id_footer]"]').val('0');
            metaCopy.find('[name="eme_dyndata['+newId+'][repeat]"]').val('0');
            metaCopy.find('[name="eme_dyndata['+newId+'][grouping]"]').parent().html('');
            metafields.forEach(f => {
                metaCopy.find('[name="eme_dyndata['+newId+']['+f+']"]').prop('required',false);
            });
        }
    });

    // --- Tasks & Todos sortable ---
    if ($('#eme_tasks_tbody').length) {
        new Sortable(document.getElementById('eme_tasks_tbody'), {
            handle: '.eme-sortable-handle',
            onStart: function (evt) { evt.from.style.opacity = '0.6'; },
            onEnd: function (evt) { evt.from.style.opacity = '1'; }
        });
    }
    if ($('#eme_todos_tbody').length) {
        new Sortable(document.getElementById('eme_todos_tbody'), {
            handle: '.eme-sortable-handle',
            onStart: function (evt) { evt.from.style.opacity = '0.6'; },
            onEnd: function (evt) { evt.from.style.opacity = '1'; }
        });
    }

    // --- Tasks Add/Remove ---
    $('.eme_add_task').on("click",function(event) {
        event.preventDefault();
        eme_add_task_function($(this));
    });
    $('.eme_remove_task').on("click",function(event) {
        event.preventDefault();
        eme_remove_task_function($(this));
    });
    $('#change_task_days').on("click",function (e) {
        e.preventDefault();
        let offset= parseInt($('#task_offset').val());
        let myId=0;
        while ($('[name="eme_tasks['+myId+'][task_start]"]').length) {
            let current_start=$('[name="eme_tasks['+myId+'][task_start]"]').val();
            let current_end=$('[name="eme_tasks['+myId+'][task_end]"]').val();
            let start_obj = new Date(current_start);
            let end_obj = new Date(current_end);
            start_obj.setDate(start_obj.getDate() + offset);
            end_obj.setDate(end_obj.getDate() + offset);
            $('[name="eme_tasks['+myId+'][dp_task_start]"]').fdatepicker().data('fdatepicker').selectDate(start_obj);
            $('[name="eme_tasks['+myId+'][dp_task_end]"]').fdatepicker().data('fdatepicker').selectDate(end_obj);
            myId = myId +1;
        }
    });

    // --- Todos Add/Remove ---
    $('.eme_add_todo').on("click",function(event) {
        event.preventDefault();
        eme_add_todo_function($(this));
    });
    $('.eme_remove_todo').on("click",function(event) {
        event.preventDefault();
        eme_remove_todo_function($(this));
    });

    // --- Show/Hide Elements ---
    $('.showhidebutton').on("click",function (e) {
        e.preventDefault();
        let elname= $(this).data( 'showhide' );
        $('#'+elname).toggle();
    });

    // --- Select2 Initialization ---
    $('.eme_select2_members_class').select2({
        width: '100%',
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 500,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1,
                    pagesize: 30,
                    action: 'eme_members_select2',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return { results: data.Records, pagination: { more: (params.page * 30) < data.TotalRecordCount } };
            },
            cache: true
        },
        placeholder: emeadmin.translate_selectmembers
    });
    $('.eme_select2_people_class').select2({
        width: '100%',
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 500,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1,
                    pagesize: 30,
                    action: 'eme_people_select2',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return { results: data.Records, pagination: { more: (params.page * 30) < data.TotalRecordCount } };
            },
            cache: true
        },
        placeholder: emeadmin.translate_selectpersons
    });
    $('.eme_select2_groups_class').select2({ placeholder: emeadmin.translate_selectgroups, dropdownAutoWidth: true, width: 'style' });
    $('.eme_select2_people_groups_class').select2({ placeholder: emeadmin.translate_anygroup, dropdownAutoWidth: true, width: 'style' });
    $('.eme_select2_memberstatus_class').select2({ placeholder: emeadmin.translate_selectmemberstatus, dropdownAutoWidth: true, width: 'style' });
    $('.eme_select2_memberships_class').select2({ placeholder: emeadmin.translate_selectmemberships, dropdownAutoWidth: true, width: 'style' });
    $('.eme_select2_discounts_class').select2({
        width: '100%', allowClear: true, placeholder: emeadmin.translate_selectdiscount,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 500,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1,
                    pagesize: 30,
                    action: 'eme_discounts_select2',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return { results: data.Records, pagination: { more: (params.page * 30) < data.TotalRecordCount } };
            },
            cache: true
        }
    });
    $('.eme_select2_dgroups_class').select2({
        width: '100%', allowClear: true, placeholder: emeadmin.translate_selectdiscountgroup,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 500,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1,
                    pagesize: 30,
                    action: 'eme_dgroups_select2',
                    eme_admin_nonce: emeadmin.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return { results: data.Records, pagination: { more: (params.page * 30) < data.TotalRecordCount } };
            },
            cache: true
        }
    });
    $('.select2-search--inline, .select2-search__field').css('width', '100%');

    // --- File Upload/Delete for Extra Fields ---
    $('body').on('click', '.eme_del_upload-button', function() {
        event.preventDefault();
        if (confirm(emeadmin.translate_areyousuretodeletefile)) {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let type = $(this).data('type');
            let random_id = $(this).data('random_id');
            let field_id = $(this).data('field_id');
            let extra_id = $(this).data('extra_id');
            $.post(ajaxurl, {'id': id, 'name': name, 'type': type, 'field_id': field_id, 'random_id': random_id, 'extra_id': extra_id, 'action': 'eme_del_upload', 'eme_admin_nonce': emeadmin.translate_adminnonce}, function() {
                if ($('span#span_'+random_id).parent().children().length == 2) {
                    $('span#span_'+random_id).siblings("input").show();
                }
                $('span#span_'+random_id).remove();
            });
        }
    });

    // --- Attachment UI for bookings/etc ---
    function eme_admin_init_attachment_ui(btnSelector, linksSelector, idsSelector, removeBtnSelector) {
        $(btnSelector).on("click", function(e) {
            e.preventDefault();
            let custom_uploader = wp.media({
                title: emeadmin.translate_addattachments,
                button: { text: emeadmin.translate_addattachments },
                multiple: true
            }).on('select', function() {
                let selection = custom_uploader.state().get('selection');
                selection.map(function(attach) {
                    let attachment = attach.toJSON();
                    $(linksSelector).append(
                        `<a target='_blank' href='${attachment.url}'>${attachment.title}</a><br>`
                    );
                    let idsArr = $(idsSelector).val() ? $(idsSelector).val().split(',') : [];
                    idsArr.push(attachment.id);
                    $(idsSelector).val(idsArr.join(','));
                    $(removeBtnSelector).show();
                });
            }).open();
        });
        $(removeBtnSelector).on("click", function(e) {
            e.preventDefault();
            $(linksSelector).html('');
            $(idsSelector).val('');
            $(removeBtnSelector).hide();
        });
        $(removeBtnSelector).toggle($(idsSelector).val() !== '');
    }
    eme_admin_init_attachment_ui('#booking_attach_button', '#booking_attach_links', '#eme_booking_attach_ids', '#booking_remove_attach_button');
    eme_admin_init_attachment_ui('#pending_attach_button', '#pending_attach_links', '#eme_pending_attach_ids', '#pending_remove_attach_button');
    eme_admin_init_attachment_ui('#paid_attach_button', '#paid_attach_links', '#eme_paid_attach_ids', '#paid_remove_attach_button');
    eme_admin_init_attachment_ui('#subscribe_attach_button', '#subscribe_attach_links', '#eme_subscribe_attach_ids', '#subscribe_remove_attach_button');
    eme_admin_init_attachment_ui('#fs_ipn_attach_button', '#fs_ipn_attach_links', '#eme_fs_ipn_attach_ids', '#fs_ipn_remove_attach_button');

    // --- Animate details/summary blocks ---
    $('details summary').each(function() {
        let $Wrapper = $(this).nextAll().wrapAll('<div></div>').parent();
        if(!$(this).parent('details').attr('open')) $Wrapper.hide();
        $(this).click(function(e) {
            e.preventDefault();
            if($(this).parent('details').attr('open')) {
                $Wrapper.slideUp(function() { $(this).parent('details').removeAttr('open'); });
            } else {
                $(this).parent('details').attr('open', true);
                $Wrapper.hide().css('opacity', 0).slideDown('slow').animate({ opacity: 1 }, { queue: false, duration: 'slow' });
            }
        });
    });

    // END ready
});
