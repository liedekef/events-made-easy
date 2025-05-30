document.addEventListener('DOMContentLoaded', function () {
	// ===== Reusable Functions (Outside Loop) =====
	const findMatchingFont = (computedFont, fontList) => {
		const computedFonts = computedFont.split(',');
		for (const computedSingleFont of computedFonts) {
			for (const [fontValue, fontDisplay] of Object.entries(fontList)) {
				if (fontValue.includes(computedSingleFont)) {
					return fontDisplay + ' \u00A0';
				}
			}
		}
		return '';
	};

	const findMatchingTagName = (element, controlList) => {
		const tagName = element.tagName.toLowerCase().trim();
		for (const [value, text] of Object.entries(controlList)) {
			if (value.toLowerCase().trim() === tagName) {
				return text + ' \u00A0';
			}
		}
		return '';
	};

	// Debounced to avoid performance issues (e.g., during rapid selection changes)
	const updateFontButtons = eme_debounce((editor) => {
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
				fontSizeButton.text.textContent = computedSize + ' \u00A0';
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

	Jodit.defaultOptions.controls.insertFromMediaLibrary2 = {
		template: () => '<span style="display: flex; align-items: center;"><span style="font-size: 1.1em;">🎵 🖼️ 📎</span></span>',
		exec: (editor) => {
			const escapeHtml = (text) => $('<div>').text(text).html();
			// Store original handler per editor instance
			if (!editor.mediaHandlers) {
				editor.mediaHandlers = {
					originalSend: wp.media.editor.send.attachment,
					cleanup: function() {
						wp.media.editor.send.attachment = editor.mediaHandlers.originalSend;
						$(document).off('click', editor.mediaHandlers.closeHandler);
					}
				}
			}

			// Override the default WordPress media insertion handler
			wp.media.editor.send.attachment = function(props, file) {
				let html;

				// Handle images (WordPress already applies selected size to file.url)
				if (file.type === 'image') {
					html = `<img src="${file.url}" alt="${escapeHtml(file.alt) || ''}" width="${file.width || ''}" height="${file.height || ''}" />`;
				} 
				// Handle audio/video
				else if (file.type === 'audio') {
					html = `<audio controls src="${file.url}"></audio>`;
				} 
				else if (file.type === 'video') {
					html = `<video controls width="640" height="360" src="${file.url}"></video>`;
				} 
				// Handle documents/other files
				else {
					html = `<a href="${file.url}" target="_blank" rel="noopener noreferrer">${escapeHtml(file.filename)}</a>`;
				}

				// Insert into Jodit
				editor.selection.insertHTML(html);
			};

			// Open the CLASSIC WordPress media modal
			wp.media.editor.open(editor.id);

			// Restore original send.attachment when modal closes
			$(document).on('click', '.media-modal-close, .media-modal-backdrop', editor.mediaHandlers.cleanup);
		},
		tooltip: emejodit.translate_insertfrommedia,
	};

	Jodit.defaultOptions.controls.insertFromMediaLibrary = {
		template: () => '<span style="display: flex; align-items: center;"><span style="font-size: 1.1em;">🎵 🖼️ 📎</span></span>',
		exec: (editor) => {

			const escapeHtml = (text) => {
				const div = document.createElement('div');
				div.textContent = text;
				return div.innerHTML;
			};

			const frame = wp.media({ multiple: true, library: { type: '' } });

			frame.on('select', () => {
				const selection = frame.state().get('selection').toArray();
				const files = selection.map((attachment) => attachment.toJSON());

				const processNext = () => {
					if (files.length === 0) return;

					const file = files.shift();

					// Non-image logic
					if (file.type !== 'image') {
						if (file.type === 'audio') {
							editor.selection.insertHTML(`<audio controls src="${file.url}"></audio>`);
						} else if (file.type === 'video') {
							editor.selection.insertHTML(`<video controls width="640" height="360" src="${file.url}"></video>`);
						} else {
							editor.selection.insertHTML(`<a href="${file.url}" target="_blank" rel="noopener noreferrer">${escapeHtml(file.filename)}</a>`);
						}
						processNext();
						return;
					}

					// Image with size selector
					const sizes = file.sizes || {};
					const hasFull = sizes.hasOwnProperty('full');
					const sizeKeys = Object.keys(sizes);

					if (sizeKeys.length === 0 || sizeKeys.length === 1) {
						const size = sizes.medium || sizes[sizeKeys[0]] || null;
						const url = size ? size.url : file.url;
						const width = size ? size.width : file.width;
						const height = size ? size.height : file.height;

						editor.selection.insertHTML(`<img src="${url}" width="${width}" height="${height}" alt="${escapeHtml(file.alt || '')}"/>`);
						processNext();
						return;
					}

					// Build dialog content
					let sizeOptions = '';
					for (let size in sizes) {
						const s = sizes[size];
						const selected = size === 'medium' ? 'selected' : '';
						sizeOptions += `<option value="${s.url}" data-width="${s.width}" data-height="${s.height}" ${selected}>${size} (${s.width}x${s.height})</option>`;
					}

					if (!hasFull) {
						const selected = !sizes.medium ? 'selected' : '';
						sizeOptions += `<option value="${file.url}" data-width="${file.width}" data-height="${file.height}" ${selected}>full (${file.width}x${file.height})</option>`;
					}

					const dialogContent = document.createElement('div');
					dialogContent.style.padding = '12px';
					dialogContent.style.margin = '0';
					dialogContent.style.boxSizing = 'border-box';
					dialogContent.style.fontSize = '14px';  // Optional: makes form elements cleaner

					dialogContent.innerHTML = `
		    <label style="display: block; margin-bottom: 10px;">
			Choose size for image: <strong>${escapeHtml(file.filename)}</strong><br>
			<select style="maxwidth: 90%; margin-top: 5px;">
			    ${sizeOptions}
			</select>
		    </label>
		    <div style="text-align: right; margin-top: 10px;">
			<button type="button" class="jodit-button jodit-button_primary eme-dialog-insert">${emejodit.translate_insert}</button>
			<button type="button" class="jodit-button jodit-button_secondary eme-dialog-cancel" style="margin-left: 8px;">${emejodit.translate_cancel}</button>
		    </div>
		`;

					const select = dialogContent.querySelector('select');
					const insertBtn = dialogContent.querySelector('.eme-dialog-insert');
					const cancelBtn = dialogContent.querySelector('.eme-dialog-cancel');

					// Show Jodit dialog
					const dialog = editor.dlg({
						buttons: [], // use custom buttons
						resizable: false,
						draggable: true,
					});
					dialog.setHeader(emejodit.translate_insertimage);
					dialog.setContent(dialogContent);
					dialog.setSize('300px','');
					dialog.open();

					select.focus();

					insertBtn.addEventListener('click', () => {
						const selectedOption = select.options[select.selectedIndex];
						const url = selectedOption.value;
						const width = selectedOption.dataset.width;
						const height = selectedOption.dataset.height;

						editor.selection.insertHTML(`<img src="${url}" width="${width}" height="${height}" alt="${escapeHtml(file.alt || '')}"/>`);
						dialog.close();
						processNext();
					});

					cancelBtn.addEventListener('click', () => {
						dialog.close();
						processNext();
					});
				};

				processNext();
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
				formData.append('editor_id', editor.id);
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

	// ===== Editor Loader with Jodit + HTML Source Toggle =====
	const loadToggleEditor = (textarea, initialValue) => {
		const wrapper = document.createElement('div');
		wrapper.className = 'eme-editor-wrapper';

		const tabBar = document.createElement('div');
		tabBar.className = 'eme-editor-tabs';
		const visualBtn = document.createElement('button');
		const textBtn = document.createElement('button');
		visualBtn.type = 'button'; // this prevents form submit
		visualBtn.textContent = emejodit.translate_visual;
		textBtn.type = 'button'; // this prevents form submit
		textBtn.textContent = emejodit.translate_code;
		visualBtn.classList.add('active');
		tabBar.append(visualBtn, textBtn);

		const joditParentDiv = document.createElement('div');
		const joditDiv = document.createElement('div');
		joditDiv.id = `joditdiv_${textarea.id}`; // this allows us to use editor.id with a predictable name in the preview code
		joditParentDiv.append(joditDiv);

		textarea.style.display = 'none';
		textarea.style.width = '100%';
		textarea.rows = 10;  // sets visible rows to 10
		textarea.style.boxSizing = 'border-box';

		textarea.parentNode.insertBefore(wrapper, textarea);
		wrapper.append(tabBar, joditParentDiv, textarea);

		const editor = new Jodit(joditDiv, {
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
				'|', 'link', 'image', 'insertFromMediaLibrary',
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
					urlField.value = 'https://'; // default: let's give a good start
				}
			}
		});
		editor.value = initialValue;

		visualBtn.addEventListener('click', () => {
			visualBtn.classList.add('active');
			textBtn.classList.remove('active');
			editor.value = textarea.value;
			joditParentDiv.style.display = 'block';
			textarea.style.display = 'none';
		});

		textBtn.addEventListener('click', () => {
			textBtn.classList.add('active');
			visualBtn.classList.remove('active');
			textarea.value = editor.value;
			textarea.style.display = 'block';
			joditParentDiv.style.display = 'none';
		});

		const form = textarea.closest('form');
		if (form) {
			form.addEventListener('submit', () => {
				textarea.value = visualBtn.classList.contains('active') ? editor.value : textarea.value;
			});
		}

		textarea._joditInstance = editor; // store jodit instance on the dom
	};

	// ===== Initialize Editors (Loop) =====
	document.querySelectorAll('.eme-editor').forEach(textarea => {
		loadToggleEditor(textarea, textarea.value);
	});

	document.querySelectorAll('.eme-fs-editor').forEach(textarea => {
		const allowUpload = textarea.dataset.allowupload === 'yes';

		const editor = new Jodit(textarea, {
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
				'---', 'fullsize',
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

	document.querySelectorAll('span[data-default]').forEach(el => {
		const defaultValue = el.dataset.default.replace(/<br\s*\/?>(?!<\/p>)/gi, '<br>');
		const targetId = el.dataset.targetid;
		const target = document.getElementById(targetId); // the textarea
		const editorInstance = Jodit.instances[`joditdiv_${targetId}`]; // the jodit editor

		//if (target && target._joditInstance) {
		if (target && editorInstance ) {
			editorInstance.events.on('focus', function () {
				if (editorInstance.value.trim() === '' || editorInstance.value.trim() === '<p><br></p>') {
					editorInstance.value = defaultValue;
				}
			});
			editorInstance.events.on('blur', function () {
				const val = editorInstance.value.trim().replace(/<br\s*\/?>(?!<\/p>)/gi, '<br>');
				if (val === defaultValue || val === '<p>' + defaultValue + '</p>') {
					editorInstance.value = '';
				}
			});
		}
	});
});
