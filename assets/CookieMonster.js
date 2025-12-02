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
if (!infoAlert) throw new Error("Cookie-Alert fehlt.");

/**
 * Setzt die Cookie-Zustimmung
 * @param {boolean} [setAll] - `true`, um alle Cookies zu akzeptieren; `false`, um alle abzulehnen
 * @returns {Promise<void>}
 */
async function setCookieMonster(setAll) {
	/** @type {Record<string, boolean>} */
	const optionValues = {};
	/** @type {NodeListOf<HTMLInputElement>} */
	const cookieOptions = document.querySelectorAll(
		".cmnstr-checkbox:not(:disabled)",
	);

	for (const option of cookieOptions) {
		const categoryKey = option.name.replace("cmnstr-", "");
		optionValues[categoryKey] = setAll ?? option.checked;
	}

	optionValues.essential = true;

	const optionString = JSON.stringify(optionValues);
	const host = location.hostname;
	const maxExpires = 180 * 24 * 60 * 60 * 1000;
	const expiresDate = Date.now() + maxExpires;

	if (!optionValues.statistics) {
		await Promise.all([
			cmnstr.delete("_gat"),
			cmnstr.delete("_ga"),
			cmnstr.delete("_gid"),
		]);
	}

	await cmnstr.set({
		domain: host,
		name: "cmnstr",
		value: optionString,
		expires: expiresDate,
		sameSite: "lax",
	});

	location.reload();
}

/**
 * Initialisiert CookieMonster, sobald DOM bereit ist
 * @returns {Promise<void>}
 */
async function initCookieMonster() {
	document.addEventListener("click", (event) => {
		if (!event.target) return;

		const button = /** @type {Element} */ (event.target).closest(
			"[data-cmnstr-action]",
		);
		if (!button) return;

		const action = button.getAttribute("data-cmnstr-action");

		if (action === "decline") {
			setCookieMonster(false);
		} else if (action === "accept") {
			setCookieMonster(true);
		} else if (action === "edit") {
			dialog.showModal();
		} else {
			setCookieMonster();
		}
	});
}

document.addEventListener("DOMContentLoaded", initCookieMonster, {
	once: true,
});

// @ts-expect-error Extending Window Object
window.showCookieBanner = () => {
	dialog.showModal();
};
