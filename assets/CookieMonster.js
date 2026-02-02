/**
 * Setzt einen Cookie-String auf `document.cookie`.
 * @param {string} cookie - Der zu setzende Cookie-String.
 * @returns {void}
 */
function setDocumentCookie(cookie) {
	document.cookie = cookie;
}

/**
 * Polyfill für die native Cookie Store API.
 */
const cmnstr = {
	/**
	 * Ruft einen Cookie nach Namen oder Optionen ab.
	 * @param {string | { name?: string }} [options] - Der Name des Cookies oder ein Optionen-Objekt.
	 * @returns {Promise<{ name: string, value: string } | null>} Ein Promise, das ein Cookie-Objekt oder `null` zurückgibt.
	 */
	async get(options) {
		const name = typeof options === "string" ? options : options?.name;
		if (!name) return null;

		const value = document.cookie
			.split("; ")
			.find((row) => row.startsWith(`${name}=`))
			?.split("=")
			.at(1);

		return value ? { name, value: decodeURIComponent(value) } : null;
	},

	/**
	 * Setzt einen Cookie nach Namen und Wert oder einem Optionen-Objekt.
	 * @param {string | { name: string, value: string, expires?: number | null, sameSite?: string, path?: string, domain?: string | null }} nameOrOptions - Der Name des Cookies oder ein Optionen-Objekt.
	 * @param {string} [value] - Der Wert des Cookies (nur wenn `nameOrOptions` ein String ist).
	 * @returns {Promise<void>}
	 */
	async set(nameOrOptions, value) {
		const options =
			typeof nameOrOptions === "string"
				? {
						name: nameOrOptions,
						value: value ?? "",
						expires: undefined,
						sameSite: "lax",
						path: "/",
				  }
				: nameOrOptions;

		const {
			name,
			value: cookieValue,
			expires,
			sameSite = "lax",
			path = "/",
			domain,
		} = options;

		let cookie = `${name}=${encodeURIComponent(cookieValue)}`;

		if (expires) {
			cookie += `; expires=${new Date(expires).toUTCString()}`;
		}

		cookie += `; path=${path}`;
		cookie += `; SameSite=${sameSite}`;

		if (domain) {
			cookie += `; domain=${domain}`;
		}

		if (location.protocol === "https:") {
			cookie += "; Secure";
		}

		setDocumentCookie(cookie);
	},

	/**
	 * Löscht einen Cookie nach Namen oder Optionen.
	 * @param {string | { name?: string }} [options] - Der Name des Cookies oder ein Optionen-Objekt.
	 * @returns {Promise<void>}
	 */
	async delete(options) {
		const name = typeof options === "string" ? options : options?.name;
		if (!name) return;

		setDocumentCookie(
			`${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax`,
		);
	},
};

const dialog = /** @type {HTMLDialogElement} */ (
	document.getElementById("cmnstr-dialog")
);
if (!dialog) throw new Error("Cookie-Banner fehlt.");

const infoAlert = /** @type {HTMLDialogElement} */ (
	document.getElementById("cmnstr-alert")
);

/**
 * Aktualisiert den Status einer einzelnen Kategorie-Checkbox.
 * Prüft alle Kinder und setzt die Checkbox auf checked/indeterminate.
 * @param {string | null} categoryKey - Der Key der Kategorie.
 * @returns {void}
 */
function updateCheckboxState(categoryKey) {
	if (!categoryKey) return;

	const checkbox = /** @type {HTMLInputElement | null} */ (
		document.querySelector(`.cmnstr-category-checkbox[name="${categoryKey}"]`)
	);

	/** @type {NodeListOf<HTMLInputElement>} */
	const children = document.querySelectorAll(
		`.cmnstr-group-checkbox[data-parent-category="${categoryKey}"]`,
	);

	if (!checkbox || children.length === 0) return;

	const checkedCount = Array.from(children).filter(
		(child) => child.checked,
	).length;

	if (checkedCount === 0) {
		checkbox.checked = false;
		checkbox.indeterminate = false;
	} else if (checkedCount === children.length) {
		checkbox.checked = true;
		checkbox.indeterminate = false;
	} else {
		checkbox.checked = false;
		checkbox.indeterminate = true;
	}
}

/**
 * Aktualisiert den Status aller Kategorie-Checkboxen auf der Seite.
 * @returns {void}
 */
function refreshAllCheckboxStates() {
	const categoryCheckboxes = document.querySelectorAll(
		".cmnstr-category-checkbox",
	);
	for (const checkbox of categoryCheckboxes) {
		updateCheckboxState(checkbox.getAttribute("name"));
	}
}

/**
 * Initialisiert die Event-Listener für die Checkbox-Logik.
 * @returns {void}
 */
function initCheckboxLogic() {
	const categoryCheckboxes = /** @type {HTMLInputElement[]} */ ([
		...dialog.querySelectorAll(".cmnstr-category-checkbox"),
	]);

	for (const checkbox of categoryCheckboxes) {
		const categoryKey = checkbox.name;

		/** @type {NodeListOf<HTMLInputElement>} */
		const children = document.querySelectorAll(
			`.cmnstr-group-checkbox[data-parent-category="${categoryKey}"]:not(:disabled)`,
		);

		// Master-Checkbox ändert alle Kinder
		checkbox.addEventListener("change", () => {
			for (const child of children) {
				child.checked = checkbox.checked;
			}
		});
	}

	// Kind-Checkbox aktualisiert Master
	const groupCheckboxes = dialog.querySelectorAll(".cmnstr-group-checkbox");
	for (const child of groupCheckboxes) {
		child.addEventListener("change", () => {
			updateCheckboxState(child.getAttribute("data-parent-category"));
		});
	}
}

/**
 * Erstellt eine hierarchische Struktur aus flachen Cookie-Optionen.
 * @param {Record<string, boolean>} flatOptions - Flache Cookie-Optionen (z.B. "external-youtube": true).
 * @returns {Record<string, boolean | Record<string, any>>} Hierarchische Struktur.
 */
function buildHierarchicalStructure(flatOptions) {
	/** @type {Record<string, boolean | Record<string, any>>} */
	const result = {};

	/** @type {Map<string, Map<string, boolean>>} */
	const groups = new Map();

	// Gruppiere nach Hierarchie
	for (const [key, value] of Object.entries(flatOptions)) {
		const dashIndex = key.indexOf("-");

		if (dashIndex === -1) {
			// Top-Level (z.B. "essential")
			result[key] = value;
		} else {
			// Verschachtelt (z.B. "external-youtube")
			const groupName = key.slice(0, dashIndex);
			const subKey = key.slice(dashIndex + 1);

			if (!groups.has(groupName)) {
				groups.set(groupName, new Map());
			}
			groups.get(groupName)?.set(subKey, value);
		}
	}

	// Verarbeite Gruppen
	for (const [groupName, subOptions] of groups) {
		// false-Einträge entfernen
		const clean = Object.fromEntries(
			[...subOptions].filter(([, value]) => value),
		);

		// Das heißt, alle verbleibenden Werte sind true
		const allTrue = Object.values(clean).every((v) => v === true);

		if (allTrue && Object.keys(clean).length === subOptions.size) {
			result[groupName] = true;
		} else {
			// rekursiv oder leeres Objekt, falls keine Kinder übrig
			result[groupName] =
				Object.keys(clean).length === 0
					? false
					: buildHierarchicalStructure(clean);
		}
	}

	return result;
}

/**
 * Macht eine hierarchische Struktur wieder flach für Checkbox-Verarbeitung.
 * @param {Record<string, boolean | Record<string, any>>} preferences - Hierarchische Präferenzen.
 * @param {string} [prefix] - Aktuelles Präfix für rekursive Verarbeitung.
 * @returns {Record<string, boolean>} Flache Struktur.
 */
function flattenPreferences(preferences, prefix = "") {
	/** @type {Record<string, boolean>} */
	const result = {};

	for (const [key, value] of Object.entries(preferences)) {
		const fullKey = prefix ? `${prefix}-${key}` : key;

		if (typeof value === "boolean") {
			result[fullKey] = value;
		} else if (typeof value === "object" && value !== null) {
			Object.assign(result, flattenPreferences(value, fullKey));
		}
	}

	return result;
}

/**
 * Lädt gespeicherte Cookie-Präferenzen und setzt die Checkboxen entsprechend.
 * @returns {Promise<void>}
 */
async function loadCookiePreferences() {
	const cookie = await cmnstr.get("cmnstr");
	if (!cookie) return;

	try {
		const preferences = JSON.parse(cookie.value);
		const flatPreferences = flattenPreferences(preferences);

		// Setze Checkbox-Werte
		for (const [key, value] of Object.entries(flatPreferences)) {
			const checkbox = /** @type {HTMLInputElement | null} */ (
				document.querySelector(`.cmnstr-checkbox[name="cmnstr-${key}"]`)
			);
			if (checkbox && !checkbox.disabled) {
				checkbox.checked = value;
			}
		}

		refreshAllCheckboxStates();
	} catch (error) {
		console.error("Fehler beim Laden der Cookie-Präferenzen:", error);
	}
}

/**
 * Setzt die Cookie-Zustimmung und speichert sie hierarchisch.
 * @param {boolean} [setAll] - `true` für alle akzeptieren, `false` für alle ablehnen, `undefined` für aktuelle Auswahl.
 * @returns {Promise<void>}
 */
async function setCookieMonster(setAll) {
	/** @type {Record<string, boolean>} */
	const flatOptions = {};

	/** @type {NodeListOf<HTMLInputElement>} */
	const cookieOptions = document.querySelectorAll(
		".cmnstr-checkbox:not(:disabled)",
	);

	for (const option of cookieOptions) {
		const key = option.name.replace("cmnstr-", "");
		flatOptions[key] = setAll ?? option.checked;
	}

	flatOptions.essential = true;

	// Erstelle hierarchische Struktur
	const hierarchicalOptions = buildHierarchicalStructure(flatOptions);

	// Lösche Analytics-Cookies wenn statistics nicht akzeptiert
	const statisticsValue = hierarchicalOptions.statistics;
	if (!statisticsValue) {
		await Promise.all([
			cmnstr.delete("_gat"),
			cmnstr.delete("_ga"),
			cmnstr.delete("_gid"),
		]);
	}

	// Speichere Cookie
	const host = location.hostname;
	const maxExpires = 180 * 24 * 60 * 60 * 1000;
	const expiresDate = Date.now() + maxExpires;

	const value = { ...hierarchicalOptions, _version: 423 };

	await cmnstr.set({
		domain: host,
		name: "cmnstr",
		value: JSON.stringify(value),
		expires: expiresDate,
		sameSite: "lax",
	});

	location.reload();
}

/**
 * Initialisiert CookieMonster, sobald DOM bereit ist.
 * @returns {Promise<void>}
 */
async function initCookieMonster() {
	initCheckboxLogic();
	await loadCookiePreferences();

	// Event-Delegation für alle Cookie-Buttons
	document.addEventListener("click", (event) => {
		if (!event.target) return;

		const button = /** @type {Element} */ (event.target).closest(
			"[data-cmnstr-action]",
		);
		if (!button) return;

		const action = button.getAttribute("data-cmnstr-action");

		switch (action) {
			case "decline":
				setCookieMonster(false);
				break;
			case "accept":
				setCookieMonster(true);
				break;
			case "edit":
				dialog.showModal();
				refreshAllCheckboxStates();
				break;
			default:
				setCookieMonster();
		}
	});
}

document.addEventListener("DOMContentLoaded", initCookieMonster, {
	once: true,
});

/**
 * Öffnet den Cookie-Banner programmatisch.
 * @returns {void}
 */
// @ts-expect-error Extending Window Object
window.showCookieBanner = () => {
	dialog.showModal();
	refreshAllCheckboxStates();
};
