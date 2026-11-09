const dialogElement = document.querySelector("#cmnstr-dialog");

const SETTINGS_PARAM = "cmnstr-settings";
const STATIC_ATTR = "data-static";
const STATIC_ENTER = 0.45;
const STATIC_LEAVE = 0.35;

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
	return [
		...dialog.querySelectorAll(`.cmnstr-group-checkbox[data-parent-category="${checkbox.id}"]`),
	].filter((service) => service instanceof HTMLInputElement);
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
	const categoryCheckboxes = dialog.querySelectorAll(".cmnstr-category-checkbox");

	for (const checkbox of categoryCheckboxes) {
		if (!(checkbox instanceof HTMLInputElement)) {
			continue;
		}

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

/** @returns {void} */
function trackAlertHeight() {
	const alert = document.querySelector("#cmnstr-alert");
	if (!alert) {
		return;
	}

	const content = alert.querySelector(".cmnstr-content");
	const root = document.documentElement;
	const update = () => {
		const rect = alert.getBoundingClientRect();

		if (rect.height === 0) {
			root.style.removeProperty("--cmnstr-scroll-padding");

			return;
		}

		const hiddenText = content ? content.scrollHeight - content.clientHeight : 0;
		const limit = alert.hasAttribute(STATIC_ATTR) ? STATIC_LEAVE : STATIC_ENTER;
		const isPortrait = window.matchMedia("(orientation: portrait)").matches;

		alert.toggleAttribute(
			STATIC_ATTR,
			!isPortrait && alert.scrollHeight + hiddenText > window.innerHeight * limit,
		);

		if (alert.hasAttribute(STATIC_ATTR)) {
			root.style.removeProperty("--cmnstr-scroll-padding");

			return;
		}

		root.style.setProperty(
			"--cmnstr-scroll-padding",
			`${Math.ceil(rect.height + Math.max(0, window.innerHeight - rect.bottom))}px`,
		);
	};

	new ResizeObserver(update).observe(alert);
	window.addEventListener("resize", update);
}

/**
 * @param {HTMLDialogElement} dialog
 * @returns {void}
 */
function trackHeaderHeight(dialog) {
	const header = dialog.querySelector(".cmnstr-header");

	if (!(header instanceof HTMLElement)) {
		return;
	}

	new ResizeObserver(() => {
		dialog.style.setProperty("--cmnstr-header-height", `${header.offsetHeight}px`);
	}).observe(header);
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

	trackAlertHeight();

	trackHeaderHeight(dialogElement);

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

	// Return focus to the mask that opened the dialogElement after saving
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

	try {
		sessionStorage.setItem(CONSENT_SUBMIT_KEY, String(Date.now()));
	} catch {
		// Storage blocked: bfcache page stays as is
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
