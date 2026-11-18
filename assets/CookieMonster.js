const dialogElement = document.querySelector("#cmnstr-dialog");

const SETTINGS_PARAM = "cmnstr-settings";

/**
 * @param {HTMLElement | null} submitter
 * @returns {boolean}
 */
function isDialogSubmitter(submitter) {
	return (
		(submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) &&
		submitter.formMethod === "dialog"
	);
}

/**
 * @param {HTMLDialogElement} dialog
 * @param {HTMLInputElement} checkbox
 * @returns {HTMLInputElement[]}
 */
function getServiceCheckboxes(dialog, checkbox) {
	/** @type {NodeListOf<HTMLInputElement>} */
	const services = dialog.querySelectorAll(
		`input.cmnstr-group-checkbox[data-parent-category="${checkbox.id}"]`,
	);

	return [...services];
}

/**
 * @param {HTMLDialogElement} dialog
 * @param {HTMLInputElement} checkbox
 * @returns {void}
 */
function updateCategoryCheckbox(dialog, checkbox) {
	const services = getServiceCheckboxes(dialog, checkbox);

	if (services.length === 0) {
		return;
	}

	const checkedCount = services.filter((service) => service.checked).length;

	checkbox.checked = checkedCount === services.length;
	checkbox.indeterminate = checkedCount > 0 && checkedCount < services.length;
}

/**
 * @param {HTMLDialogElement} dialog
 * @returns {void}
 */
function initCategoryCheckboxes(dialog) {
	/** @type {NodeListOf<HTMLInputElement>} */
	const categoryCheckboxes = dialog.querySelectorAll("input.cmnstr-category-checkbox");

	for (const checkbox of categoryCheckboxes) {
		const services = getServiceCheckboxes(dialog, checkbox);

		if (services.length === 0) {
			continue;
		}

		checkbox.hidden = false;
		updateCategoryCheckbox(dialog, checkbox);

		checkbox.addEventListener("change", () => {
			for (const service of services) {
				if (!service.disabled) {
					service.checked = checkbox.checked;
				}
			}
		});

		for (const service of services) {
			service.addEventListener("change", () => updateCategoryCheckbox(dialog, checkbox));
		}
	}
}

/**
 * @param {HTMLDialogElement} dialog
 * @returns {void}
 */
function showCookieBanner(dialog) {
	if (dialog.open && !dialog.matches(":modal")) {
		dialog.close();
	}

	if (!dialog.open) {
		dialog.showModal();
	}
}

/**
 * @param {HTMLDialogElement} dialog
 * @returns {void}
 */
function removeSettingsParam(dialog) {
	const url = new URL(window.location.href);
	if (!url.searchParams.has(SETTINGS_PARAM)) {
		return;
	}

	url.searchParams.delete(SETTINGS_PARAM);
	if (url.hash === `#${dialog.id}`) {
		url.hash = "";
	}

	window.history.replaceState(window.history.state, "", url);
}

const statusRegion = document.querySelector("#cmnstr-status");
if (statusRegion instanceof HTMLElement && statusRegion.dataset.message) {
	const message = statusRegion.dataset.message;

	setTimeout(() => {
		statusRegion.textContent = message;
	}, 250);
}

if (dialogElement instanceof HTMLDialogElement) {
	initCategoryCheckboxes(dialogElement);

	if (dialogElement.open) {
		showCookieBanner(dialogElement);
	}

	removeSettingsParam(dialogElement);

	/** @type {HTMLElement | null} */
	let opener = null;

	/** @type {string | null} */
	let maskId = null;

	document.addEventListener("click", (event) => {
		if (!(event.target instanceof Element)) {
			return;
		}

		const trigger = event.target.closest(".cmnstr-button-edit");

		if (trigger instanceof HTMLElement) {
			event.preventDefault();
			opener = trigger;
			maskId =
				trigger.closest(".cmnstr-content-mask")?.getAttribute("aria-labelledby") ?? null;
			showCookieBanner(dialogElement);
		}
	});

	// Safari does not focus links on mouse click, so the browser has nothing to restore
	dialogElement.addEventListener("close", () => {
		if (opener?.isConnected) {
			opener.focus();
		}

		opener = null;
		maskId = null;
	});

	// After saving, jump back to the mask that opened the dialog
	dialogElement.addEventListener("submit", (event) => {
		const redirect = dialogElement.querySelector('input[name="cmnstr-redirect"]');

		if (maskId && redirect instanceof HTMLInputElement && !isDialogSubmitter(event.submitter)) {
			redirect.value = `${redirect.value.split("#")[0]}#${maskId}`;
		}
	});

	Object.assign(window, { showCookieBanner: () => showCookieBanner(dialogElement) });
}

const CONSENT_SUBMIT_KEY = "cmnstr-consent-submitted";
const CONSENT_PATH = "/_cmnstr/consent";
const loadedAt = Date.now();

document.addEventListener("submit", (event) => {
	const form = event.target;

	if (
		!(form instanceof HTMLFormElement) ||
		isDialogSubmitter(event.submitter) ||
		!new URL(form.action, location.href).pathname.endsWith(CONSENT_PATH)
	) {
		return;
	}

	if (document.referrer) {
		/** @type {HTMLInputElement | null} */
		let referrerInput = form.querySelector('input[name="cmnstr-referrer"]');
		if (!referrerInput) {
			referrerInput = document.createElement("input");
			referrerInput.type = "hidden";
			referrerInput.name = "cmnstr-referrer";
			form.append(referrerInput);
		}

		referrerInput.value = document.referrer;
	}

	try {
		sessionStorage.setItem(CONSENT_SUBMIT_KEY, String(Date.now()));
	} catch {
		// Storage blocked
	}

	// CookieMonster.live.js cancels this event and submits via fetch
	const liveSubmit = new CustomEvent("cmnstr:submit", {
		cancelable: true,
		detail: { form, submitter: event.submitter },
	});

	if (!document.dispatchEvent(liveSubmit)) {
		event.preventDefault();
	}
});

// A page restored from bfcache after a consent change would show the old mask/banner state
window.addEventListener("pageshow", (event) => {
	if (!event.persisted) {
		return;
	}

	try {
		if (Number(sessionStorage.getItem(CONSENT_SUBMIT_KEY)) > loadedAt) {
			location.reload();
		}
	} catch {
		// Storage blocked
	}
});
