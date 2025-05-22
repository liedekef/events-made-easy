jQuery(document).ready( function($) {
    var EME_MediaPickerButton = function (context) {
        var ui = $.summernote.ui;

        // Create dropdown menu
        var button = ui.buttonGroup([
            ui.button({
                className: 'dropdown-toggle',
                contents: '📎 Insert Media <span class="caret"></span>',
                tooltip: 'Insert Media',
                data: {
                    toggle: 'dropdown'
                }
            }),
            ui.dropdown([
                ui.button({
                    className: 'note-btn',
                    contents: '📷 Insert Image',
                    click: function () {
                        let frame = wp.media({
                            title: 'Insert Image',
                            library: { type: 'image' },
                            button: { text: 'Insert Image' },
                            multiple: false
                        });

                        frame.on('select', function () {
                            let attachment = frame.state().get('selection').first().toJSON();
                            context.invoke('editor.insertImage', attachment.url, attachment.alt);
                        });

                        frame.open();
                    }
                }),
                ui.button({
                    className: 'note-btn',
                    contents: '📄 Insert File Link',
                    click: function () {
                        let frame = wp.media({
                            title: 'Insert File',
                            library: { type: ['application', 'text', 'image', 'video'] },
                            button: { text: 'Insert Link' },
                            multiple: false
                        });

                        frame.on('select', function () {
                            let attachment = frame.state().get('selection').first().toJSON();
                            let link = '<a href="' + attachment.url + '" target="_blank">' + (attachment.title || attachment.filename) + '</a>';
                            context.invoke('editor.pasteHTML', link);
                        });

                        frame.open();
                    }
                })
            ])
        ]);

        return button.render();
    };

    $('.eme-editor').summernote({
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic','underline', 'clear']],
            ['fontname', ['fontname', 'fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'table', 'ememedia', 'picture', 'video']],
            ['view', ['codeview', 'help']],
        ],
        popover: {
            table: [
                ['merge', ['jMerge']],
                ['style', ['jBackcolor', 'jBorderColor', 'jAlign', 'jAddDeleteRowCol']],
                ['info', ['jTableInfo']],
                ['delete', ['jWidthHeightReset', 'deleteTable']],
            ]
        },
        jTable : {
            /**
             * drag || dialog
             */
            mergeMode: 'drag'
        },
        buttons: {
            ememedia: EME_MediaPickerButton,
            setCellWidth: function (context) {
                let ui = $.summernote.ui;

                let button2 = ui.button({
                    className: 'note-icon-pencil',
                    contents: ' Set Cell Width',
                    tooltip: 'Set Cell Width',
                    click: function () {
                        var rng = context.invoke('editor.createRange');
                        if (rng) {
                            var node = rng.sc; // 'sc' = startContainer
                            var $cell = $(node).closest('td, th');
                            if ($cell.length) {
                                let width = prompt("Enter width (e.g., 100px or 20%)", $cell.css('width') || '');
                                if (width) $cell.css('width', width);
                            }
                        }
                    }
                });
                return button2.render();
            }
        }
    });
});
