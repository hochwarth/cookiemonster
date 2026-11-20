// Loaded only with live unlock enabled, after CookieMonster.js
const dialogElement = document.querySelector("#cmnstr-dialog");
const statusRegion = document.querySelector("#cmnstr-status");

/**
 * @typedef {object} ConsentState
 * @property {boolean} reload Consent was narrowed, the page has to reload
 * @property {boolean} decided Visitor has made a decision
 * @property {string[]} unlocked Unlocked category and service keys
 * @property {string[]} once Service keys unlocked for this page view only
 * @property {string[]} tags Unlocked tag methods
 * @property {Record<string, "granted" | "denied">} consentMode Google Consent Mode update
 * @property {{ decided: boolean } & Record<string, unknown>} dataLayer Consent per service for the
 *   dataLayer (`cmnstr`)
 * @property {string} message Status message for the live region
 */

/**
 * Sequential like the parser: each script waits for the previous one.
 *
 * @param {[HTMLScriptElement, Comment][]} scripts
 * @returns {Promise<void>}
 */
async function runScripts(scripts) {
	if (scripts.length === 0) {
		return;
	}

	const [[original, placeholder], ...rest] = scripts;
	const script = document.createElement("script");

	for (const { name, value } of original.attributes) {
		script.setAttribute(name, value);
	}

	// Browsers hide the nonce attribute, the property keeps the value
	script.nonce = original.nonce;
	script.text = original.text;

	const blocking = script.src !== "" && !script.hasAttribute("async");

	if (blocking) {
		script.async = false;
	}

	const done = blocking
		? new Promise((resolve) => {
				script.addEventListener("load", resolve, { once: true });
				script.addEventListener("error", resolve, { once: true });
			})
		: null;

	placeholder.replaceWith(script);
	await done;
	await runScripts(rest);
}

/**
 * @param {HTMLTemplateElement} template
 * @param {Element} target Replaced by the template content
 * @param {HTMLElement | null} [submitter]
 * @returns {[HTMLScriptElement, Comment][]} Scripts to run via runScripts()
 */
function insertTemplate(template, target, submitter = null) {
	const content = document.importNode(template.content, true);
	const first = content.firstElementChild;
	// Inserted as is, the scripts would run at once and out of order
	const scripts = [...content.querySelectorAll("script")].map((script) => {
		const placeholder = document.createComment("cmnstr-script");
		script.replaceWith(placeholder);

		return /** @type {[HTMLScriptElement, Comment]} */ ([script, placeholder]);
	});
	// Safari on macOS does not focus buttons on click, so the submitter counts as well
	const hadFocus = target.contains(document.activeElement) || target.contains(submitter);
	const parent = target.parentElement;

	target.replaceWith(content);

	if (hadFocus) {
		for (const element of [first, parent]) {
			if (!(element instanceof HTMLElement)) {
				continue;
			}

			if (element.tabIndex < 0) {
				element.tabIndex = -1;
			}

			element.focus();

			if (document.activeElement === element) {
				break;
			}
		}
	}

	return scripts;
}

/**
 * @param {ConsentState} state
 * @param {HTMLElement | null} submitter
 * @returns {Promise<void>}
 */
async function applyConsent(state, submitter) {
	const unlocked = new Set([...state.unlocked, ...state.once]);

	// Consent Mode first, so tags loaded below start with the new state
	if ("gtag" in window && typeof window.gtag === "function") {
		window.gtag("consent", "update", state.consentMode);
	}

	if ("dataLayer" in window && Array.isArray(window.dataLayer)) {
		window.dataLayer.push({ cmnstr: state.dataLayer });
		window.dataLayer.push({ event: "cmnstr_consent" });
	}

	if (dialogElement instanceof HTMLDialogElement) {
		/** @type {NodeListOf<HTMLInputElement>} */
		const services = dialogElement.querySelectorAll(
			"input.cmnstr-group-checkbox:not(:disabled)",
		);
		for (const service of services) {
			service.checked = state.unlocked.includes(service.name.replace(/^cmnstr-/, ""));
			// CookieMonster.js updates the category checkbox on change
			service.dispatchEvent(new Event("change"));
		}

		/** @type {NodeListOf<HTMLInputElement>} */
		const checkboxes = dialogElement.querySelectorAll(
			"input.cmnstr-category-checkbox:not(:disabled)",
		);
		for (const checkbox of checkboxes) {
			const category = checkbox.id.replace(/^cmnstr-/, "");

			if (
				!dialogElement.querySelector(
					`.cmnstr-group-checkbox[data-parent-category="${checkbox.id}"]`,
				)
			) {
				checkbox.checked = state.unlocked.includes(category);
			}

			const categoryState = checkbox
				.closest(".cmnstr-category-details")
				?.querySelector(".cmnstr-category-state");

			if (categoryState instanceof HTMLElement) {
				categoryState.dataset.active = String(
					state.unlocked.some(
						(key) => key === category || key.startsWith(`${category}-`),
					),
				);
			}
		}

		dialogElement.close();
	}

	const alert = document.querySelector("#cmnstr-alert");

	if (state.decided && alert instanceof HTMLDialogElement) {
		alert.close();
	}

	if (statusRegion instanceof HTMLElement && state.message) {
		statusRegion.textContent = "";

		setTimeout(() => {
			statusRegion.textContent = state.message;
		}, 250);
	}

	document.dispatchEvent(new CustomEvent("cmnstr:consent", { detail: state }));

	/** @type {[HTMLScriptElement, Comment][]} */
	const scripts = [];

	/** @type {NodeListOf<HTMLElement>} */
	const masks = document.querySelectorAll(".cmnstr-content-mask");
	for (const mask of masks) {
		const template = mask.querySelector(".cmnstr-mask-template");

		if (!(template instanceof HTMLTemplateElement)) {
			continue;
		}

		const keys = (mask.dataset.cmnstrCategory ?? "").split(" ").filter(Boolean);
		const matches =
			mask.dataset.cmnstrMatch === "any"
				? keys.some((key) => unlocked.has(key))
				: keys.every((key) => unlocked.has(key));

		if (keys.length > 0 && matches) {
			scripts.push(...insertTemplate(template, mask, submitter));
		}
	}

	/** @type {NodeListOf<HTMLTemplateElement>} */
	const tagTemplates = document.querySelectorAll("template[data-cmnstr-tag]");
	for (const template of tagTemplates) {
		if (state.tags.includes(template.dataset.cmnstrTag ?? "")) {
			scripts.push(...insertTemplate(template, template));
		}
	}

	await runScripts(scripts);
}

/**
 * @param {HTMLFormElement} form
 * @param {HTMLElement | null} submitter
 * @returns {Promise<void>}
 */
async function submitLive(form, submitter) {
	if (form.getAttribute("aria-busy") === "true") {
		return;
	}

	form.setAttribute("aria-busy", "true");

	/** @type {ConsentState} */
	let state;

	try {
		const response = await fetch(form.action, {
			method: "POST",
			body: new FormData(form, submitter),
			headers: { Accept: "application/json" },
		});

		if (!response.ok) {
			throw new Error(`HTTP ${response.status}`);
		}

		state = await response.json();
	} catch {
		// Fall back to the regular POST with redirect; submit() does not send the submitter
		if (
			(submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) &&
			submitter.name
		) {
			const field = document.createElement("input");
			field.type = "hidden";
			field.name = submitter.name;
			field.value = submitter.value;
			form.append(field);
		}

		form.submit();

		return;
	} finally {
		form.removeAttribute("aria-busy");
	}

	if (state.reload) {
		location.reload();

		return;
	}

	await applyConsent(state, submitter);
}

// Dispatched by CookieMonster.js before the regular POST
document.addEventListener("cmnstr:submit", (event) => {
	if (!(event instanceof CustomEvent)) {
		return;
	}

	event.preventDefault();
	void submitLive(event.detail.form, event.detail.submitter);
});
