/** @returns {boolean} */
const supportsPlaintextOnly = () => {
	try {
		const probe = document.createElement("div");
		probe.contentEditable = "plaintext-only";

		return probe.contentEditable === "plaintext-only";
	} catch {
		return false;
	}
};

/**
 * @typedef {object} Editor
 * @property {HTMLElement} element Contenteditable that replaces the textarea visually
 * @property {HTMLTextAreaElement} textarea Original field, still submitted with the form
 * @property {[Highlight, Range][]} ranges Painted ranges, to remove them on the next paint
 */

/**
 * @typedef {object} Segment
 * @property {Node} node Text node containing the characters
 * @property {number} start Offset of the node's first character in the editor text
 * @property {number} length Number of characters of the node
 */

/**
 * @param {HTMLElement} element
 * @returns {{ text: string; segments: Segment[] }}
 */
const read = (element) => {
	const walker = document.createTreeWalker(
		element,
		NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT,
	);
	/** @type {Segment[]} */
	const segments = [];
	let text = "";

	for (let node = walker.nextNode(); node; node = walker.nextNode()) {
		if (node instanceof Text) {
			segments.push({ node, start: text.length, length: node.length });
			text += node.data;
		} else if (node instanceof HTMLBRElement) {
			segments.push({ node, start: text.length, length: 1 });
			text += "\n";
		} else if (node instanceof HTMLDivElement || node instanceof HTMLParagraphElement) {
			// Streuende Blöcke (z. B. aus Browser-Eigenheiten) zählen als Zeilenumbruch
			if (text !== "" && !text.endsWith("\n")) {
				text += "\n";
			}
		}
	}

	return { text, segments };
};

/**
 * @param {HTMLElement} element
 * @param {Segment[]} segments
 * @param {number} offset
 * @param {boolean} end
 * @returns {[Node, number]}
 */
const point = (element, segments, offset, end) => {
	for (const { node, start, length } of segments) {
		if (offset > start + length || (!end && offset === start + length)) {
			continue;
		}

		if (node instanceof Text) {
			return [node, Math.max(0, offset - start)];
		}

		const parent = node.parentNode;

		if (parent === null) {
			continue;
		}

		const index = Array.prototype.indexOf.call(parent.childNodes, node);

		return [parent, index + (offset > start ? 1 : 0)];
	}

	return [element, element.childNodes.length];
};

/**
 * Per execCommand, damit Undo/Redo erhalten bleibt; "\n" in insertText erzeugt in Chrome ein <div>.
 * execCommand ist deprecated, hat aber keinen Ersatz mit nativem Undo-Stack (EditContext ersetzt es
 * nicht).
 *
 * @param {string} text
 */
const insertText = (text) => {
	for (const [index, line] of text.split("\n").entries()) {
		if (index > 0) {
			document.execCommand("insertLineBreak");
		}
		if (line !== "") {
			document.execCommand("insertText", false, line);
		}
	}
};

/**
 * @param {HTMLTextAreaElement} textarea
 * @returns {string}
 */
const languageName = (textarea) => {
	const pane = textarea.closest('[id^="langTab_"], [id^="wrap_"]');
	const link = pane?.id
		? textarea.closest(".langTabs")?.querySelector(`a[href="#${CSS.escape(pane.id)}"]`)
		: null;

	return link?.textContent.trim() ?? "";
};

$(() => {
	$("#ModuleEditForm").WireTabs({
		items: $("#ModuleEditForm > .Inputfields > .InputfieldWrapper"),
	});

	// Kategorien als Tabs im Stil der Sprach-Tabs, ohne JavaScript bleiben es Fieldsets
	const $list = $(".Inputfield_cookie_categories > .InputfieldContent > .Inputfields");
	const $panels = $list.children(".InputfieldFieldset");

	for (const panel of $panels.get()) {
		panel.title = $(panel).children(".InputfieldHeader").hide().text().trim();
	}

	// PHP serialisiert ein leeres Array als [], das darf nicht in das Objekt gespreadet werden
	const configuredTabs = Reflect.get(window, "ProcessWire").config.CookieMonster?.tabs;
	const tabOptions = Array.isArray(configuredTabs) ? {} : configuredTabs;

	// langTabsInit hält setupLanguageTabs() aus dem Core fern
	$list
		.wrap("<div class='langTabs langTabsInit'></div>")
		.parent()
		.WireTabs({
			...tabOptions,
			items: $panels,
			id: "cmnstr-category-tabs",
			rememberTabs: -1,
		});

	// Ausgeschaltete Kategorien wie leere Sprach-Tabs abblenden
	const $checkboxes = $panels.find('input[type="checkbox"][name$="_enabled"]');

	for (const checkbox of $checkboxes.get()) {
		if (!(checkbox instanceof HTMLInputElement)) {
			continue;
		}

		const $tab = $(`#_${$(checkbox).closest(".InputfieldFieldset").attr("id")}`).parent();
		const mark = () => $tab.toggleClass("langTabEmpty", !checkbox.checked);
		mark();
		$(checkbox).on("change", mark);
	}

	// Highlight-Ranges erreichen keinen Textarea-Inhalt, daher übernimmt ein contenteditable die Eingabe.
	// Ohne Highlight-API oder plaintext-only bleibt das Textarea unverändert sichtbar.
	if ("highlights" in CSS && supportsPlaintextOnly()) {
		// Später registrierte Highlights liegen oben, die Zeilenfarbe zuerst
		/** @type {Map<string, Highlight>} */
		const highlights = new Map();
		for (const name of [
			"cookie",
			"service",
			"name",
			"provider",
			"wildcard",
			"separator",
			"duration",
		]) {
			const highlight = new Highlight();

			highlights.set(name, highlight);
			CSS.highlights.set(`cmnstr-${name}`, highlight);
		}

		// Theme-Regeln für das textarea-Element übertragen, damit das Feld auch im Fokus 1:1 aussieht
		const textareaPattern = /(?<![\w.#-])textarea(?![\w-])/g;

		/**
		 * @param {CSSRuleList} rules
		 * @returns {string}
		 */
		const copyRules = (rules) => {
			let css = "";

			for (const rule of rules) {
				if (rule instanceof CSSStyleRule) {
					if (textareaPattern.test(rule.selectorText)) {
						css += `${rule.selectorText.replaceAll(textareaPattern, "div:where(.cmnstr-hl-editor)")} { ${rule.style.cssText} }\n`;
					}
				} else if (rule instanceof CSSGroupingRule) {
					// @media, @supports, @layer: Kopf der Regel übernehmen, damit die Bedingung erhalten bleibt
					const inner = copyRules(rule.cssRules);
					if (inner !== "") {
						css += `${rule.cssText.slice(0, rule.cssText.indexOf("{") + 1)}\n${inner}}\n`;
					}
				}
			}

			return css;
		};

		let css = "";
		for (const styleSheet of document.styleSheets) {
			try {
				css += copyRules(styleSheet.cssRules);
			} catch {
				// Fremde Stylesheets sind nicht lesbar
			}
		}
		// replaceSync() verwirft ungültige Regeln wie fremde Pseudo-Elemente einzeln
		const sheet = new CSSStyleSheet();
		sheet.replaceSync(css);
		document.adoptedStyleSheets = [...document.adoptedStyleSheets, sheet];

		/** @param {Editor} editor */
		const paint = (editor) => {
			const { element, textarea } = editor;

			for (const [highlight, range] of editor.ranges) {
				highlight.delete(range);
			}
			editor.ranges = [];

			const { text, segments } = read(element);
			// Browser hängen für eine leere letzte Zeile einen Umbruch an
			textarea.value = text.replace(/\n+$/, "");
			element.classList.toggle("cmnstr-hl-empty", textarea.value === "");

			/**
			 * @param {string | undefined} name
			 * @param {number} start
			 * @param {number} length
			 */
			const add = (name, start, length) => {
				const highlight = name ? highlights.get(name) : undefined;
				if (!highlight || !length) {
					return;
				}

				const range = new Range();
				range.setStart(...point(element, segments, start, false));
				range.setEnd(...point(element, segments, start + length, true));
				highlight.add(range);
				editor.ranges.push([highlight, range]);
			};

			let pos = 0;
			for (const line of text.split("\n")) {
				const service = line.trimStart().startsWith("---");
				const columns = [service ? "service" : "name", "provider", "", "duration"];
				if (!service) {
					add("cookie", pos, line.length);
				}

				for (const [index, cell] of line.split("|").entries()) {
					if (index > 0) {
						add("separator", pos - 1, 1);
					}
					add(columns[index], pos, cell.length);
					for (const match of columns[index] === "name" ? cell.matchAll(/\*/g) : []) {
						add("wildcard", pos + (match.index || 0), 1);
					}
					pos += cell.length + 1;
				}
			}
		};

		/** @type {Editor[]} */
		const editors = [];

		const $textareas = $panels.find('textarea[name*="_cookies"]');

		for (const textarea of $textareas.get()) {
			if (!(textarea instanceof HTMLTextAreaElement)) {
				continue;
			}

			/**
			 * @param {JQuery} $elements
			 * @param {string} suffix
			 * @returns {string | null}
			 */
			const ids = ($elements, suffix) =>
				$elements
					.map(
						(/** @type {number} */ index, /** @type {HTMLElement} */ label) =>
							(label.id ||= `${textarea.id}_${suffix}${index}`),
					)
					.get()
					.join(" ") || null;

			const category = $(textarea).closest(".InputfieldFieldset").prop("title") || "";
			const extra = [category, languageName(textarea)].filter(Boolean).join(", ");
			const editorId = `${textarea.id}_editor`;
			const labelledby = ids($(textarea.labels), "label");

			const $editor = $("<div>", {
				id: editorId,
				class: "uk-textarea cmnstr-hl-editor",
				style: `--cmnstr-rows: ${textarea.rows}`,
				contenteditable: "plaintext-only",
				role: "textbox",
				"aria-multiline": "true",
				// Das Element verweist auf sich selbst, damit sein aria-label den Namen ergänzt
				"aria-label": extra || null,
				"aria-labelledby": labelledby && extra ? `${labelledby} ${editorId}` : labelledby,
				"aria-describedby": ids($(textarea).siblings(".description, .notes"), "desc"),
				"aria-placeholder": textarea.placeholder || null,
				"aria-required": textarea.required ? "true" : null,
				"aria-invalid": textarea.closest(".InputfieldStateError") ? "true" : null,
				spellcheck: "false",
				autocorrect: "off",
				autocapitalize: "off",
				text: textarea.value,
			});

			$(textarea.labels).on("click", () => $editor.trigger("focus"));

			/** @type {Editor} */
			const editor = {
				element: $editor[0],
				textarea,
				ranges: [],
			};
			let valueOnFocus = textarea.value;

			$editor.insertAfter(textarea);

			editor.element.addEventListener("beforeinput", (event) => {
				if (
					!event.isComposing &&
					(event.inputType === "insertParagraph" || event.inputType === "insertLineBreak")
				) {
					event.preventDefault();
					insertText("\n");
				}
			});

			editor.element.addEventListener("paste", (event) => {
				const data = event.clipboardData;

				if (!data) {
					return;
				}

				event.preventDefault();
				insertText(data.getData("text/plain").replaceAll(/\r\n?/g, "\n"));
			});

			editor.element.addEventListener("input", () => paint(editor));

			editor.element.addEventListener("focus", () => {
				valueOnFocus = textarea.value;
			});

			editor.element.addEventListener("blur", () => {
				if (textarea.value !== valueOnFocus) {
					$(textarea).trigger("change");
				}
			});

			editors.push(editor);
		}

		for (const editor of editors) {
			paint(editor);
		}
	}
});
