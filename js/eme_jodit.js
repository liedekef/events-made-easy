jQuery(document).ready( function($) {
    if (typeof getQueryParams === 'undefined') {
        function getQueryParams(qs) {
            qs = qs.split('+').join(' ');
            let params = {},
                tokens,
                re = /[?&]?([^=]+)=([^&]*)/g;

            while (tokens = re.exec(qs)) {
                params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
            }
            return params;
        }
    }

    let $_GET = getQueryParams(document.location.search);

    // ===== Reusable Functions (Outside Loop) =====
    const findMatchingFont = (computedFont, fontList) => {
        const computedFonts = computedFont.split(',');
        for (const computedSingleFont of computedFonts) {
            for (const [fontValue, fontDisplay] of Object.entries(fontList)) {
                if (fontValue.includes(computedSingleFont)) {
                    return fontDisplay + ' ';
                }
            }
        }
        return '';
    };

    const findMatchingTagName = (element, controlList) => {
        const tagName = element.tagName.toLowerCase().trim();
        for (const [value, text] of Object.entries(controlList)) {
            if (value.toLowerCase().trim() === tagName) {
                return text + ' ';
            }
        }
        return '';
    };

    // Debounced to avoid performance issues (e.g., during rapid selection changes)
    const updateFontButtons = _.debounce((editor) => {
        let current = editor.s.current();
        if (!current) return;

        // Handle text nodes
        if (current.nodeType === Node.TEXT_NODE) {
            current = current.parentElement;
        }
        if (!(current instanceof Element)) return;

        const computedStyle = window.getComputedStyle(current);
        const defaultStyle = window.getComputedStyle(editor.editor);

        // --- Font Family Button ---
        const fontButton = editor.toolbar.buttons.find(btn => btn.name === 'font');
        if (fontButton?.control?.list) {
            const computedFont = computedStyle.fontFamily;
            const matchedFont = (computedFont === defaultStyle.fontFamily)
                ? ''
                : findMatchingFont(computedFont, fontButton.control.list);
            fontButton.text.textContent = matchedFont;
        }

        // --- Font Size Button (with Jodit bug workaround) ---
        const fontSizeButton = editor.toolbar.buttons.find(btn => btn.name === 'fontsize');
        if (fontSizeButton) {
            const computedSize = computedStyle.fontSize;
            if (computedSize === defaultStyle.fontSize) {
                //fontSizeButton.state.activated = false; // Fix for Jodit bug
                //fontSizeButton.state.active = false;
                fontSizeButton.text.textContent = '';
            } else {
                fontSizeButton.text.textContent = computedSize + ' ';
            }
        }

        // --- Paragraph/Heading Button ---
        const fontParagraphButton = editor.toolbar.buttons.find(btn => btn.name === 'paragraph');
        if (fontParagraphButton?.control?.list) {
            let currentElement = current;
            let displayText = '';
            let maxDepth = 10;

            while (maxDepth-- > 0 && currentElement && currentElement !== editor.editor) {
                const match = findMatchingTagName(currentElement, fontParagraphButton.control.list);
                if (match) {
                    displayText = (currentElement?.tagName?.toLowerCase().trim() === 'p') ? '' : match;
                    break;
                }
                currentElement = currentElement.parentElement;
            }
            fontParagraphButton.text.textContent = displayText;
        }
    }, 100); // Debounce delay (ms)

    // ===== Jodit Default Configs (Outside Loop) =====
    Jodit.modules.Icon.set('insertNbsp', '<svg viewBox="0 0 100 40" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="96" height="36" rx="6" ry="6" fill="#f0f0f0" stroke="#333" stroke-width="3"/></svg>');

    Jodit.defaultOptions.controls.insertNbsp = {
        icon: 'insertNbsp',
        tooltip: emejodit.translate_insertnbsp,
        exec: (editor) => editor.selection.insertHTML('&nbsp;'),
    };

    Jodit.defaultOptions.controls.insertFromMediaLibrary = {
        template: () => '<span style="display: flex; align-items: center;"><span style="font-size: 1.1em;">🎵 🖼️ 📎</span></span>',
        exec: (editor) => {
            const frame = wp.media({ multiple: true });
            frame.on('select', () => {
                const selection = frame.state().get('selection');
                selection.each((attachment) => {
                    const file = attachment.toJSON();
                    const img = file.sizes?.medium || file;
                    editor.selection.insertHTML(`<img src="${img.url}" width="${img.width}" height="${img.height}" alt="${file.alt || ''}"/>`);
                });
                frame.off('select');
            });
            frame.open();
        },
        tooltip: emejodit.translate_insertfrommedia,
    };

    Jodit.defaultOptions.controls.preview = {
        icon: 'eye',
        tooltip: emejodit.translate_preview,
        exec: async (editor) => {
            try {
                const formData = new FormData();
                formData.append('action', 'eme_jodit_preview_render');
                //formData.append('html', DOMPurify.sanitize(editor.value)); // Sanitize output
                formData.append('html', editor.value); // Sanitize output
                formData.append('screen_id', pagenow);
                if ($_GET['tab']) formData.append('eme_tab', $_GET['tab']);
                formData.append('eme_admin_nonce', emejodit.translate_adminnonce);

                const response = await fetch(ajaxurl, { method: 'POST', body: formData });
                const result = await response.json();
                const rendered = result.success ? result.data.html : `<pre>Error: ${result.data}</pre>`;

                const dialog = editor.dlg();
                dialog.setHeader(emejodit.translate_preview);
                dialog.setContent(rendered);
                dialog.open();
            } catch (err) {
                editor.alert('Preview failed: ' + err.message);
            }
        },
    };

    // ===== Initialize Editors (Loop) =====
    $('.eme-editor').each(function () {
        const $textarea = $(this);
        const editor = new Jodit($textarea[0], {
            height: 300,
            toolbarSticky: false,
            toolbarAdaptive: false,
            showCharsCounter: false,
            showWordsCounter: false,
            hidePoweredByJodit: true,
            language: emejodit.translate_flanguage,
            enter: 'br',
            cleanHTML: {
                replaceNBSP: false,
                removeEmptyElements: false,
                removeEmptyAttributes: false,
                fillEmptyParagraph: false,
            },
            allowTagsWithoutClosing: true,
            buttons: [
                'undo', 'redo',
                '|', 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript',
                '|', 'paragraph', 'fontsize', 'font', 'lineHeight',
                '|', 'brush',
                '---', 'source', 'fullsize',
                '\n',
                'align', 'outdent', 'indent',
                '|', 'ul', 'ol',
                '|', 'table', 'symbols',
                '|', 'link', 'image', 'video', 'insertFromMediaLibrary',
                '|', 'hr', 'insertNbsp', 'eraser',
                '---', 'preview',
            ],
        });

        // ===== Editor-Specific Events (Inside Loop) =====
        //editor.events.on('processHTML', (html) => DOMPurify.sanitize(html));
        editor.events.on('afterUpdateToolbar', () => updateFontButtons(editor));
        editor.events.on('focus', () => (editor.options.enter = 'p'));

        // Link Popup Logic
        editor.events.on('afterOpenPopup.link', (popup) => {
            const urlField = popup.container.querySelector('input[data-ref="url_input"]');
            if (!urlField) return;

            const urlval = urlField.value.trim();
            if (!urlval) {
                const contentval = popup.container.querySelector('input[data-ref="content_input"]').value.trim();
                if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contentval)) {
                    urlField.value = 'mailto:' + contentval;
                } else if (/^(https?:\/\/)/i.test(contentval)) {
                    urlField.value = contentval;
                } else {
                    urlField.value = 'https://' + contentval;
                }
            }
        });

        $textarea.data('joditEditor', editor);
    });

    $('.eme-fs-editor').each(function () {
        const $textarea = $(this);
        const allowupload = ($textarea.data('allowupload')==='yes')

        const editor = new Jodit($textarea[0], {
            height: 300,
            toolbarSticky: false,
            toolbarAdaptive: false,
            language: emejodit.translate_flanguage,
            showCharsCounter: false,
            showWordsCounter: false,
            hidePoweredByJodit: true,
            buttons: [
                'undo', 'redo',
                '|', 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript',
                '|', 'paragraph', 'fontsize', 'font', 'lineHeight',
                '|', 'brush',
                '---', 'source', 'fullsize',
                '\n',
                'align', 'outdent', 'indent',
                '|', 'ul', 'ol',
                '|', 'table', 'symbols',
                '|', 'link', 'image', 'insertFromMediaLibrary',
                '|', 'hr', 'insertNbsp', 'eraser'
            ],
            //events: {
                // Sanitize all HTML output (optional but recommended)
             //   processHTML: (html) => DOMPurify.sanitize(html),
            //}
        });

        // ===== Editor-Specific Events (Inside Loop) =====
        editor.events.on('afterUpdateToolbar', () => updateFontButtons(editor));
        editor.events.on('focus', () => (editor.options.enter = 'p'));

        // Link Popup Logic
        editor.events.on('afterOpenPopup.link', (popup) => {
            const urlField = popup.container.querySelector('input[data-ref="url_input"]');
            if (!urlField) return;

            const urlval = urlField.value.trim();
            if (!urlval) {
                const contentval = popup.container.querySelector('input[data-ref="content_input"]').value.trim();
                if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contentval)) {
                    urlField.value = 'mailto:' + contentval;
                } else if (/^(https?:\/\/)/i.test(contentval)) {
                    urlField.value = contentval;
                } else {
                    urlField.value = 'https://' + contentval;
                }
            }
        });
    });
});
