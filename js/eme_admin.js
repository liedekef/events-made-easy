const EMEAdmin = (function($) {
    // Shared utilities
    const utils = {
        areyousure: function(message) {
            return confirm(message);
        },

        formatcsv: function(input) {
            // double " according to rfc4180
            let regexp = new RegExp(/["]/g);
            let output = input.replace(regexp, '""');
            //HTML
            regexp = new RegExp(/\<[^\<]+\>/g);
            output = output.replace(regexp, "");
            output = output.replace(/&nbsp;/gi,' '); //replace &nbsp;
            if (output == "") return '';
            return '"' + output.trim() + '"';
        },

        jtable_csv: function(container, csv_name) {
            // create a copy to avoid messing with visual layout
            let newTable = $(container).clone();
            let csvData = [];
            let delimiter = emeadmin.translate_delimiter;

            //header
            let tmpRow = []; // construct header avalible array

            // th - remove attributes and header divs from jTable
            $.each(newTable.find('th').slice(1), function() {
                if ($(this).css('display') != 'none') {
                    let val = $(this).find('.jtable-column-header-text').text();
                    tmpRow.push(EMEAdmin.utils.formatcsv(val));
                }
            });
            csvData.push(tmpRow.join(delimiter));

            // tr - remove attributes
            $.each(newTable.find('tr'), function() {
                let tmpRow = [];
                $.each($(this).find('td').slice(1), function() {
                    if ($(this).css('display') != 'none') {
                        if ($(this).find('img, button').length > 0) {
                            $(this).html('');
                        }
                        // we take the html and replace br
                        let val = $(this).html();
                        let regexp = new RegExp(/\<br ?\/?\>/g);
                        val = val.replace(regexp, '\n');
                        $(this).html(val);
                        tmpRow.push(EMEAdmin.utils.formatcsv($(this).text()));
                    }
                });
                if (tmpRow.length > 0) {
                    csvData.push(tmpRow.join(delimiter));
                }
            });

            // Create and trigger download
            let mydata = csvData.join('\r\n');
            let blob = new Blob([mydata], { type: 'text/csv;charset=utf-8' });
            let link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = csv_name + '_data.csv';
            link.click();
            link.remove();

            return true;
        },

        printTable: function(containerSelector, options = {}) {
            const $table = $(containerSelector).find('table:first');
            if ($table.length === 0) {
                console.warn('No table found in the container:', containerSelector);
                return false;
            }

            const tableHtml = $table.clone().wrap('<div>').parent().html();

            const defaultOptions = {
                printMode: 'iframe', // 'popup' or 'iframe'
                pageTitle: document.title,
                extraStyles: '', // extra custom CSS
                closeDelay: 1000 // milliseconds to wait before trying to close
            };

            const opts = $.extend({}, defaultOptions, options);
            const fullHtml = `
        <html>
        <head>
            <title>${opts.pageTitle}</title>
            <style>
                body { font-family: sans-serif; padding: 10px; }
                table { width: 100%; border-collapse: collapse; }
                table, th, td { border: 1px solid black; }
                ${opts.extraStyles}
            </style>
            <base href="${window.location.href}">
        </head>
        <body>
            ${tableHtml}
            <script>
                window.onload = function() {
                    window.focus();
                    window.print();
                };
            </script>
        </body>
        </html>
    `;

            if (opts.printMode === 'popup') {
                const printWindow = window.open('', '_blank', 'width=800,height=600');
                if (!printWindow) {
                    alert('Popup blocked! Please allow popups for this site.');
                    return false;
                }

                printWindow.document.open();
                printWindow.document.write(fullHtml);
                printWindow.document.close();
            } else if (opts.printMode === 'iframe') {
                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';

                document.body.appendChild(iframe);
                const iframeWindow = iframe.contentWindow || iframe;
                const iframeDoc = iframeWindow.document;
                iframeDoc.open();
                iframeDoc.write(fullHtml);
                iframeDoc.close();
                // a timeout so the iframe doesn't get removed too fast so the print dialog has time to popup
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, opts.closeDelay);
            }

            return true;
        }

    };

    // Tab management
    const tabs = {
        activate: function(target) {
            $('.eme-tab').removeClass('active');
            $('.eme-tab-content').removeClass('active');

            $(`.eme-tab[data-tab="${target}"]`).addClass('active');
            $(`#${target}`).addClass('active');

            // Tab-specific actions
            if (target == "tab-locationdetails" && emeadmin.translate_map_is_active === 'true') {
                eme_SelectdisplayAddress();
                eme_displayAddress(0);
            }

            // Mail-related tabs
            const tabActions = {
                "tab-mailings": '#MailingsLoadRecordsButton',
                "tab-mailingsarchive": '#ArchivedMailingsLoadRecordsButton',
                "tab-allmail": '#MailsLoadRecordsButton'
            };

            if (tabActions[target]) {
                setTimeout(() => $(tabActions[target]).trigger('click'), 100);
            }

            // Jodit editor resize
            if (emeadmin.translate_htmleditor == 'jodit') {
                setTimeout(() => {
                    Object.values(Jodit.instances).forEach(editor => {
                        editor.events.fire('resize');
                    });
                }, 100);
            }
        }
    };

    // Form elements management
    const forms = {
        initInputSizes: function() {
            $("input[placeholder]").each(function() {
                if ($(this).attr('placeholder').length > $(this).attr('size')) {
                    $(this).attr('size', $(this).attr('placeholder').length);
                }
            });
        },

        setupRowSelection: function() {
            let lastChecked = null;
            
            $(document)
                .on('click', 'input.select-all', function() {
                    $('input.row-selector').prop('checked', this.checked);
                })
                .on('click', 'input.row-selector', function(e) {
                    if (!lastChecked) {
                        lastChecked = this;
                    } else if (e.shiftKey) {
                        let start = $("input.row-selector").index(this);
                        let end = $("input.row-selector").index(lastChecked);
                        $("input.row-selector")
                            .slice(Math.min(start, end), Math.max(start, end) + 1)
                            .prop('checked', lastChecked.checked);
                    }
                    lastChecked = this;
                    
                    // Update "select-all" checkbox
                    $("input.select-all").prop(
                        "checked",
                        $("input.row-selector").length == $(".row-selector:checked").length
                    );
                });
        },

        setupDismissibleNotices: function() {
            $('div[data-dismissible] button.notice-dismiss').on("click", function(event) {
                event.preventDefault();
                let $el = $(this).closest('div[data-dismissible]');
                let attr_value = $el.attr('data-dismissible').split('-');
                let dismissible_length = attr_value.pop();
                let option_name = attr_value.join('-');

                $.post(ajaxurl, {
                    'action': 'eme_dismiss_admin_notice',
                    'option_name': option_name,
                    'dismissible_length': dismissible_length,
                    'eme_admin_nonce': emeadmin.translate_adminnonce
                });
            });
        }
    };

    // Dynamic attribute management
    const attributes = {
        add: function() {
            let metas = $('#eme_attr_body').children();
            let metaCopy = $(metas[0]).clone(true);
            let newId = metas.length + 1;
            
            metaCopy.attr('id', 'eme_attr_' + newId);
            metaCopy.find('a').attr('rel', newId);
            
            ['ref', 'content', 'name'].forEach(field => {
                metaCopy.find(`[name=eme_attr_1_${field}]`).attr({
                    name: `eme_attr_${newId}_${field}`,
                    value: ''
                });
            });
            
            $('#eme_attr_body').append(metaCopy);
        },

        remove: function(element) {
            if ($('#eme_attr_body').children().length > 1) {
                $(element).closest('tr').remove();
                this.renumber();
            } else {
                this.resetFirst();
            }
        },

        renumber: function() {
            $('#eme_attr_body').children().each(function(id) {
                let metaCopy = $(this);
                let oldId = metaCopy.attr('id').replace('eme_attr_', '');
                let newId = id + 1;
                
                metaCopy.attr('id', 'eme_attr_' + newId);
                metaCopy.find('a').attr('rel', newId);
                
                ['ref', 'content', 'name'].forEach(field => {
                    metaCopy.find(`[name=eme_attr_${oldId}_${field}]`)
                        .attr('name', `eme_attr_${newId}_${field}`);
                });
            });
        },

        resetFirst: function() {
            let metaCopy = $('#eme_attr_body').children().first();
            ['ref', 'content', 'name'].forEach(field => {
                metaCopy.find(`[name=eme_attr_1_${field}]`).val('');
            });
        }
    };

    // Dynamic data management
    const dynamicData = {
        initSortable: function() {
            if ($('#eme_dyndata_tbody').length) {
                new Sortable(document.getElementById('eme_dyndata_tbody'), {
                    handle: '.eme-sortable-handle',
                    onStart: (evt) => evt.from.style.opacity = '0.6',
                    onEnd: (evt) => evt.from.style.opacity = '1'
                });
            }
        },

        add: function() {
            let metas = $('#eme_dyndata_tbody').children();
            let metaCopy = $(metas[0]).clone(true);
            let newId = 0;
            
            while ($('#eme_dyndata_' + newId).length) newId++;
            
            let currentId = metaCopy.attr('id').replace('eme_dyndata_', '');
            metaCopy.attr('id', 'eme_dyndata_' + newId);
            metaCopy.find('a').attr('rel', newId);
            
            let metafields = ['field', 'condition', 'condval', 'template_id_header', 
                            'template_id', 'template_id_footer', 'repeat', 'grouping'];
            
            metafields.forEach(field => {
                metaCopy.find(`[name="eme_dyndata[${currentId}][${field}]"]`).attr({
                    'name': `eme_dyndata[${newId}][${field}]`,
                    'id': `eme_dyndata[${newId}][${field}]`
                });
            });
            
            // Set default values
            metaCopy.find('[name="eme_dyndata[' + newId + '][field]"]').val('');
            metaCopy.find('[name="eme_dyndata[' + newId + '][condition]"]').val('eq');
            metaCopy.find('[name="eme_dyndata[' + newId + '][condval]"]').val('');
            metaCopy.find('[name="eme_dyndata[' + newId + '][template_id_header]"]').val('0');
            metaCopy.find('[name="eme_dyndata[' + newId + '][template_id]"]').val('0');
            metaCopy.find('[name="eme_dyndata[' + newId + '][template_id_footer]"]').val('0');
            metaCopy.find('[name="eme_dyndata[' + newId + '][repeat]"]').val('0');
            metaCopy.find('[name="eme_dyndata[' + newId + '][grouping]"]').parent().html('');
            
            $('#eme_dyndata_tbody').append(metaCopy);
        },

        remove: function(element) {
            if ($('#eme_dyndata_tbody').children().length > 1) {
                $(element).closest('tr').remove();
            } else {
                this.resetFirst();
            }
        },

        resetFirst: function() {
            let metaCopy = $('#eme_dyndata_tbody').children().first();
            let newId = 0;
            
            while ($('#eme_dyndata_' + newId).length) newId++;
            
            let currentId = metaCopy.attr('id').replace('eme_dyndata_', '');
            metaCopy.attr('id', 'eme_dyndata_' + newId);
            metaCopy.find('a').attr('rel', newId);
            
            let metafields = ['field', 'condition', 'condval', 'template_id_header', 
                             'template_id', 'template_id_footer', 'repeat', 'grouping'];
            
            metafields.forEach(field => {
                metaCopy.find(`[name="eme_dyndata[${currentId}][${field}]"]`).attr({
                    'name': `eme_dyndata[${newId}][${field}]`,
                    'id': `eme_dyndata[${newId}][${field}]`
                });
            });
            
            // Set default values
            metaCopy.find('[name="eme_dyndata[' + newId + '][field]"]').val('');
            metaCopy.find('[name="eme_dyndata[' + newId + '][condition]"]').val('eq');
            metaCopy.find('[name="eme_dyndata[' + newId + '][condval]"]').val('');
            metaCopy.find('[name="eme_dyndata[' + newId + '][template_id_header]"]').val('0');
            metaCopy.find('[name="eme_dyndata[' + newId + '][template_id]"]').val('0');
            metaCopy.find('[name="eme_dyndata[' + newId + '][template_id_footer]"]').val('0');
            metaCopy.find('[name="eme_dyndata[' + newId + '][repeat]"]').val('0');
            metaCopy.find('[name="eme_dyndata[' + newId + '][grouping]"]').parent().html('');
            
            // Remove required attributes
            metafields.forEach(field => {
                metaCopy.find(`[name="eme_dyndata[${newId}][${field}]"]`).prop('required', false);
            });
        }
    };

    // Task management
    const tasks = {
        initSortable: function() {
            if ($('#eme_tasks_tbody').length) {
                new Sortable(document.getElementById('eme_tasks_tbody'), {
                    handle: '.eme-sortable-handle',
                    onStart: (evt) => evt.from.style.opacity = '0.6',
                    onEnd: (evt) => evt.from.style.opacity = '1'
                });
            }
        },

        add: function(element) {
            let selectedItem = $(element).closest('tr');
            let metaCopy = selectedItem.clone(false);
            let newId = 0;
            
            while ($('#eme_row_task_' + newId).length) newId++;
            
            let currentId = metaCopy.attr('id').replace('eme_row_task_', '');
            metaCopy.attr('id', 'eme_row_task_' + newId);
            metaCopy.find('a').attr('rel', newId);
            metaCopy.find('[name="eme_tasks[' + currentId + '][signup_count]"]').remove();
            
            let metafields = ['task_id', 'name', 'task_start', 'task_end', 
                            'spaces', 'dp_task_start', 'dp_task_end', 'description'];
            
            metafields.forEach(field => {
                metaCopy.find(`[name="eme_tasks[${currentId}][${field}]"]`).attr({
                    'name': `eme_tasks[${newId}][${field}]`,
                    'id': `eme_tasks[${newId}][${field}]`
                });
            });
            
            // Date fields
            metaCopy.find('[name="eme_tasks[' + newId + '][dp_task_start]"]').attr({
                'data-alt-field': '#eme_tasks[' + newId + '][task_start]'
            });
            metaCopy.find('[name="eme_tasks[' + newId + '][dp_task_end]"]').attr({
                'data-alt-field': '#eme_tasks[' + newId + '][task_end]'
            });
            
            // Set default values
            metaCopy.find('[name="eme_tasks[' + newId + '][name]"]').val('');
            metaCopy.find('[name="eme_tasks[' + newId + '][spaces]"]').val('1');
            metaCopy.find('[name="eme_tasks[' + newId + '][description]"]').val('');
            metaCopy.find('[name="eme_tasks[' + newId + '][task_id]"]').parent().html('');
            
            $('#eme_tasks_tbody').append(metaCopy);
            
            // Initialize datepickers
            $(`#eme_row_task_${newId} .eme_formfield_fdatetime`).fdatepicker({
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
            
            let current_start = metaCopy.find('[name="eme_tasks[' + newId + '][task_start]"]').val();
            if (current_start != '') {
                let js_start_obj = new Date(current_start);
                metaCopy.find('[name="eme_tasks[' + newId + '][dp_task_start]"]')
                    .fdatepicker().data('fdatepicker').selectDate(js_start_obj);
            }
            
            let current_end = metaCopy.find('[name="eme_tasks[' + newId + '][task_end]"]').val();
            if (current_end != '') {
                let js_end_obj = new Date(current_end);
                metaCopy.find('[name="eme_tasks[' + newId + '][dp_task_end]"]')
                    .fdatepicker().data('fdatepicker').selectDate(js_end_obj);
            }
            
            // Add event handlers
            $(`#eme_row_task_${newId} .eme_add_task`).on("click", (e) => {
                e.preventDefault();
                this.add($(e.target));
            });
            
            $(`#eme_row_task_${newId} .eme_remove_task`).on("click", (e) => {
                e.preventDefault();
                this.remove($(e.target));
            });
        },

        remove: function(element) {
            if ($('#eme_tasks_tbody').children().length > 1) {
                $(element).closest('tr').remove();
            } else {
                this.resetFirst();
            }
        },

        resetFirst: function() {
            let metaCopy = $('#eme_tasks_tbody').children().first();
            let newId = 0;
            
            while ($('#eme_row_task_' + newId).length) newId++;
            
            let currentId = metaCopy.attr('id').replace('eme_row_task_', '');
            metaCopy.attr('id', 'eme_row_task_' + newId);
            metaCopy.find('a').attr('rel', newId);
            
            let metafields = ['task_id', 'name', 'task_start', 'task_end', 
                            'spaces', 'dp_task_start', 'dp_task_end', 'description'];
            
            metafields.forEach(field => {
                metaCopy.find(`[name="eme_tasks[${currentId}][${field}]"]`).attr({
                    'name': `eme_tasks[${newId}][${field}]`,
                    'id': `eme_tasks[${newId}][${field}]`
                });
            });
            
            // Date fields
            metaCopy.find('[name="eme_tasks[' + newId + '][dp_task_start]"]').attr({
                'data-alt-field': '#eme_tasks[' + newId + '][task_start]'
            });
            metaCopy.find('[name="eme_tasks[' + newId + '][dp_task_end]"]').attr({
                'data-alt-field': '#eme_tasks[' + newId + '][task_end]'
            });
            
            // Set default values
            metaCopy.find('[name="eme_tasks[' + newId + '][name]"]').val('');
            metaCopy.find('[name="eme_tasks[' + newId + '][spaces]"]').val('1');
            metaCopy.find('[name="eme_tasks[' + newId + '][description]"]').val('');
            metaCopy.find('[name="eme_tasks[' + newId + '][task_id]"]').parent().html('');
            
            // Remove required attributes
            metafields.forEach(field => {
                metaCopy.find(`[name="eme_tasks[${newId}][${field}]"]`).prop('required', false);
            });
        },

        changeDays: function() {
            let offset = parseInt($('#task_offset').val());
            let myId = 0;
            
            while ($(`[name="eme_tasks[${myId}][task_start]"]`).length) {
                let current_start = $(`[name="eme_tasks[${myId}][task_start]"]`).val();
                let current_end = $(`[name="eme_tasks[${myId}][task_end]"]`).val();
                
                let start_obj = new Date(current_start);
                let end_obj = new Date(current_end);
                
                start_obj.setDate(start_obj.getDate() + offset);
                end_obj.setDate(end_obj.getDate() + offset);
                
                $(`[name="eme_tasks[${myId}][dp_task_start]"]`)
                    .fdatepicker().data('fdatepicker').selectDate(start_obj);
                $(`[name="eme_tasks[${myId}][dp_task_end]"]`)
                    .fdatepicker().data('fdatepicker').selectDate(end_obj);
                
                myId++;
            }
        }
    };

    // Todo management
    const todos = {
        initSortable: function() {
            if ($('#eme_todos_tbody').length) {
                new Sortable(document.getElementById('eme_todos_tbody'), {
                    handle: '.eme-sortable-handle',
                    onStart: (evt) => evt.from.style.opacity = '0.6',
                    onEnd: (evt) => evt.from.style.opacity = '1'
                });
            }
        },

        add: function(element) {
            let selectedItem = $(element).closest('tr');
            let metaCopy = selectedItem.clone(false);
            let newId = 0;
            
            while ($('#eme_row_todo_' + newId).length) newId++;
            
            let currentId = metaCopy.attr('id').replace('eme_row_todo_', '');
            metaCopy.attr('id', 'eme_row_todo_' + newId);
            metaCopy.find('a').attr('rel', newId);
            
            let metafields = ['todo_id', 'name', 'todo_offset', 'description'];
            
            metafields.forEach(field => {
                metaCopy.find(`[name="eme_todos[${currentId}][${field}]"]`).attr({
                    'name': `eme_todos[${newId}][${field}]`,
                    'id': `eme_todos[${newId}][${field}]`
                });
            });
            
            // Set default values
            metaCopy.find('[name="eme_todos[' + newId + '][name]"]').val('');
            metaCopy.find('[name="eme_todos[' + newId + '][todo_offset]"]').val('0');
            metaCopy.find('[name="eme_todos[' + newId + '][description]"]').val('');
            metaCopy.find('[name="eme_todos[' + newId + '][todo_id]"]').parent().html('');
            
            $('#eme_todos_tbody').append(metaCopy);
            
            // Add event handlers
            $(`#eme_row_todo_${newId} .eme_add_todo`).on("click", (e) => {
                e.preventDefault();
                this.add($(e.target));
            });
            
            $(`#eme_row_todo_${newId} .eme_remove_todo`).on("click", (e) => {
                e.preventDefault();
                this.remove($(e.target));
            });
        },

        remove: function(element) {
            if ($('#eme_todos_tbody').children().length > 1) {
                $(element).closest('tr').remove();
            } else {
                this.resetFirst();
            }
        },

        resetFirst: function() {
            let metaCopy = $('#eme_todos_tbody').children().first();
            let newId = 0;
            
            while ($('#eme_row_todo_' + newId).length) newId++;
            
            let currentId = metaCopy.attr('id').replace('eme_row_todo_', '');
            metaCopy.attr('id', 'eme_row_todo_' + newId);
            metaCopy.find('a').attr('rel', newId);
            
            let metafields = ['todo_id', 'name', 'todo_offset', 'description'];
            
            metafields.forEach(field => {
                metaCopy.find(`[name="eme_todos[${currentId}][${field}]"]`).attr({
                    'name': `eme_todos[${newId}][${field}]`,
                    'id': `eme_todos[${newId}][${field}]`
                });
            });
            
            // Set default values
            metaCopy.find('[name="eme_todos[' + newId + '][name]"]').val('');
            metaCopy.find('[name="eme_todos[' + newId + '][todo_offset]"]').val('0');
            metaCopy.find('[name="eme_todos[' + newId + '][description]"]').val('');
            metaCopy.find('[name="eme_todos[' + newId + '][todo_id]"]').parent().html('');
            
            // Remove required attributes
            metafields.forEach(field => {
                metaCopy.find(`[name="eme_todos[${newId}][${field}]"]`).prop('required', false);
            });
        }
    };

    // UI components
    const ui = {
        setupShowHideButtons: function() {
            $('.showhidebutton').on("click", function(e) {
                e.preventDefault();
                let elname = $(this).data('showhide');
                $('#' + elname).toggle();
            });
        },

        setupDetailsAnimation: function() {
            $('details summary').each(function() {
                let $Wrapper = $(this).nextAll().wrapAll('<div></div>').parent();
                
                if (!$(this).parent('details').attr('open')) {
                    $Wrapper.hide();
                }
                
                $(this).click(function(e) {
                    e.preventDefault();
                    
                    if ($(this).parent('details').attr('open')) {
                        $Wrapper.slideUp(function() {
                            $(this).parent('details').removeAttr('open');
                        });
                    } else {
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
        }
    };

    // Select2 components
    const select2 = {
        initMembers: function() {
            $('.eme_select2_members_class').select2({
                width: 'style',
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 500,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                            pagesize: 30,
                            action: 'eme_members_select2',
                            eme_admin_nonce: emeadmin.translate_adminnonce
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.Records,
                            pagination: {
                                more: (data.Page * 30) < data.TotalRecordCount
                            }
                        };
                    },
                    cache: true
                },
                placeholder: emeadmin.translate_selectmembers
            });
        },

        initPeople: function() {
            $('.eme_select2_people_class').select2({
                width: 'style',
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 500,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                            pagesize: 30,
                            action: 'eme_people_select2',
                            eme_admin_nonce: emeadmin.translate_adminnonce
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.Records,
                            pagination: {
                                more: (data.Page * 30) < data.TotalRecordCount
                            }
                        };
                    },
                    cache: true
                },
                placeholder: emeadmin.translate_selectpersons
            });
        },

        initGroups: function() {
            $('.eme_select2_groups_class').select2({
                placeholder: emeadmin.translate_selectgroups,
                dropdownAutoWidth: true,
                width: 'style'
            });
        },

        initPeopleGroups: function() {
            $('.eme_select2_people_groups_class').select2({
                placeholder: emeadmin.translate_anygroup,
                dropdownAutoWidth: true,
                width: 'style'
            });
        },

        initMemberStatus: function() {
            $('.eme_select2_memberstatus_class').select2({
                placeholder: emeadmin.translate_selectmemberstatus,
                dropdownAutoWidth: true,
                width: 'style'
            });
        },

        initMemberships: function() {
            $('.eme_select2_memberships_class').select2({
                placeholder: emeadmin.translate_selectmemberships,
                dropdownAutoWidth: true,
                width: 'style'
            });
        },

        initDiscounts: function() {
            $('.eme_select2_discounts_class').select2({
                width: '100%',
                allowClear: true,
                placeholder: emeadmin.translate_selectdiscount,
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 500,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                            pagesize: 30,
                            action: 'eme_discounts_select2',
                            eme_admin_nonce: emeadmin.translate_adminnonce
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.Records,
                            pagination: {
                                more: (data.Page * 30) < data.TotalRecordCount
                            }
                        };
                    },
                    cache: true
                }
            });
        },

        initDiscountGroups: function() {
            $('.eme_select2_dgroups_class').select2({
                width: '100%',
                allowClear: true,
                placeholder: emeadmin.translate_selectdiscountgroup,
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 500,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                            pagesize: 30,
                            action: 'eme_dgroups_select2',
                            eme_admin_nonce: emeadmin.translate_adminnonce
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.Records,
                            pagination: {
                                more: (data.Page * 30) < data.TotalRecordCount
                            }
                        };
                    },
                    cache: true
                }
            });
        },

        fixPlaceholderWidth: function() {
            $('.select2-search--inline, .select2-search__field').css('width', '100%');
        }
    };

    // File upload management
    const uploads = {
        setupDeleteButtons: function() {
            $('body').on('click', '.eme_del_upload-button', function(event) {
                event.preventDefault();
                if (confirm(emeadmin.translate_areyousuretodeletefile)) {
                    let $button = $(this);
                    let id = $button.data('id');
                    let name = $button.data('name');
                    let type = $button.data('type');
                    let random_id = $button.data('random_id');
                    let field_id = $button.data('field_id');
                    let extra_id = $button.data('extra_id');
                    
                    $.post(ajaxurl, {
                        'id': id,
                        'name': name,
                        'type': type,
                        'field_id': field_id,
                        'random_id': random_id,
                        'extra_id': extra_id,
                        'action': 'eme_del_upload',
                        'eme_admin_nonce': eme.translate_adminnonce
                    }, function() {
                        let $span = $('#span_' + random_id);
                        if ($span.parent().children().length == 2) {
                            $span.siblings("input").show();
                        }
                        $span.remove();
                    });
                }
            });
        },

        setupMediaUploaders: function() {
            const uploadTypes = [
                'booking', 'pending', 'paid', 'subscribe', 'fs_ipn'
            ];
            
            uploadTypes.forEach(type => {
                const prefix = type === 'fs_ipn' ? 'fs_ipn' : type;
                
                // Setup upload button
                $(`#${prefix}_attach_button`).on("click", function(e) {
                    e.preventDefault();
                    let custom_uploader = wp.media({
                        title: emeadmin.translate_addattachments,
                        button: { text: emeadmin.translate_addattachments },
                        multiple: true
                    }).on('select', function() {
                        let selection = custom_uploader.state().get('selection');
                        let tmp_ids_arr = $(`#eme_${prefix}_attach_ids`).val() 
                            ? $(`#eme_${prefix}_attach_ids`).val().split(',') 
                            : [];
                        
                        selection.map(function(attach) {
                            let attachment = attach.toJSON();
                            $(`#${prefix}_attach_links`).append(
                                `<a target='_blank' href='${attachment.url}'>${attachment.title}</a><br />`
                            );
                            tmp_ids_arr.push(attachment.id);
                        });
                        
                        $(`#eme_${prefix}_attach_ids`).val(tmp_ids_arr.join(','));
                        $(`#${prefix}_remove_attach_button`).show();
                    }).open();
                });
                
                // Initial state
                if ($(`#eme_${prefix}_attach_ids`).val() != '') {
                    $(`#${prefix}_remove_attach_button`).show();
                } else {
                    $(`#${prefix}_remove_attach_button`).hide();
                }
                
                // Setup remove button
                $(`#${prefix}_remove_attach_button`).on("click", function(e) {
                    e.preventDefault();
                    $(`#${prefix}_attach_links`).html('');
                    $(`#eme_${prefix}_attach_ids`).val('');
                    $(this).hide();
                });
            });
        }
    };

    // Initialization
    const init = {
        initialize: function() {
            // Tab management
            $('.eme-tab').on('click', (e) => {
                let target = $(e.currentTarget).data('tab');
                tabs.activate(target);
            });

            // Activate default tab
            if ($('.eme-tabs').length) {
                const preferredtab = $('.eme-tabs').data('showtab');
                tabs.activate(preferredtab || $('.eme-tab').first().data('tab'));
            }

            // Form elements
            forms.initInputSizes();
            forms.setupRowSelection();
            forms.setupDismissibleNotices();

            // Attribute management
            $('#eme_attr_add_tag').on("click", (e) => {
                e.preventDefault();
                attributes.add();
            });
            $('#eme_attr_body').on("click", "a", (e) => {
                e.preventDefault();
                attributes.remove(e.currentTarget);
            });

            // Dynamic data management
            dynamicData.initSortable();
            $('.eme_dyndata_add_tag').on("click", (e) => {
                e.preventDefault();
                dynamicData.add();
            });
            $('#eme_dyndata_tbody').on("click", ".eme_remove_dyndatacondition", (e) => {
                e.preventDefault();
                dynamicData.remove(e.currentTarget);
            });

            // Task management
            tasks.initSortable();
            $('.eme_add_task').on("click", (e) => {
                e.preventDefault();
                tasks.add(e.currentTarget);
            });
            $('#eme_tasks_tbody').on("click", ".eme_remove_task", (e) => {
                e.preventDefault();
                tasks.remove(e.currentTarget);
            });
            $('#change_task_days').on("click", (e) => {
                e.preventDefault();
                tasks.changeDays();
            });

            // Todo management
            todos.initSortable();
            $('.eme_add_todo').on("click", (e) => {
                e.preventDefault();
                todos.add(e.currentTarget);
            });
            $('#eme_todos_tbody').on("click", ".eme_remove_todo", (e) => {
                e.preventDefault();
                todos.remove(e.currentTarget);
            });

            // UI components
            ui.setupShowHideButtons();
            ui.setupDetailsAnimation();

            // Select2 components
            select2.initMembers();
            select2.initPeople();
            select2.initGroups();
            select2.initPeopleGroups();
            select2.initMemberStatus();
            select2.initMemberships();
            select2.initDiscounts();
            select2.initDiscountGroups();
            select2.fixPlaceholderWidth();

            // File uploads
            uploads.setupDeleteButtons();
            uploads.setupMediaUploaders();
        }
    };

    // Public API
    return {
        utils,
        tabs,
        forms,
        dynamicData,
        tasks,
        todos,
        ui,
        select2,
        uploads,
        init
    };
})(jQuery);

// Initialize when DOM is ready
jQuery(document).ready(function() {
    EMEAdmin.init.initialize();
});

