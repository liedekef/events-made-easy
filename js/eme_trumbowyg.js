jQuery(document).ready( function($) {
    $.trumbowyg.svgPath = emetrumbowyg.translate_plugin_url+'js/trumbowyg/dist/ui/icons.svg';

    // ===== Initialize Editors (Loop) =====
    $('.eme-editor').each(function () {
        const $textarea = $(this);
        const editor = $textarea.trumbowyg({
            btns: [
                ['viewHTML'],
                ['undo', 'redo'], // Only supported in Blink browsers
                ['formatting'],
                ['strong', 'em', 'del'],
                ['superscript', 'subscript'],
                ['link'],
                ['insertImage'],['table','tableCellBackgroundColor', 'tableBorderColor'],
                ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
                ['unorderedList', 'orderedList'],
                ['horizontalRule'],
                ['removeformat'],
                ['fullscreen']
            ]
        });
        $textarea.data('trumbowygEditor', editor);
    });
    $('span[data-default]').each(function () {
        const $el = $(this);
        const defaultValue = $el.data('default').replace(/<br\s*\/?>/gi, '<br>');
        const targetid = $el.data('targetid');
        const target = $('#'+targetid);
        //
        // If a Jodit instance is associated
        if (target.data('trumbowygEditor')) {
            target.data('trumbowygEditor').on('tbwfocus', function () {
                if (target.trumbowyg('html').trim() === '' || target.trumbowyg('html').trim() === '<p><br></p>') {
                    target.trumbowyg('html',defaultValue);
                }
            });

            target.data('trumbowygEditor').on('tbwblur', function () {
                if (target.trumbowyg('html').trim().replace(/<br\s*\/?>/gi, '<br>') === defaultValue || target.trumbowyg('html').trim() === '<p>'+defaultValue+'</p>') {
                    target.trumbowyg('empty');
                }
            });
        }
    });
});
