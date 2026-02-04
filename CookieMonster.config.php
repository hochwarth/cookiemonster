<?php

declare(strict_types=1);

namespace ProcessWire;

$config = [
	[
		'type' => 'wrapper',
		'label' => __('Cookie-Banner'),
		'name' => 'cookie_banner',
		'attr' => [
			'title' => __('Cookie-Banner'),
			'class' => 'WireTab',
		],
		'children' => [
			// --- General Settings ---
			[
				'type' => 'fieldset',
				'label' => __('Allgemeine Einstellungen'),
				'icon' => 'cogs',
				'collapsed' => false,
				'children' => [
					[
						'name' => 'is_active',
						'type' => 'checkbox',
						'label' => __('Cookie-Banner aktiviert'),
						'label2' => __('Zeige das Cookie-Banner auf allen Seiten an'),
						'value' => true,
						'columnWidth' => 50,
					],
					[
						'name' => 'use_stylesheet',
						'type' => 'checkbox',
						'label' => __('Standard-Stylesheet verwenden'),
						'label2' => __('Bietet einfache Standard-Formatierung für das Cookie-Banner'),
						'value' => true,
						'columnWidth' => 50,
					],
				]
			],
			// --- Banner Content ---
			[
				'type' => 'fieldset',
				'label' => __('Banner-Inhalt'),
				'icon' => 'align-left',
				'children' => [
					[
						'name' => 'titletext',
						'type' => 'text',
						'label' => __('Banner-Überschrift'),
						'value' => __('Cookie-Präferenzen'),
						'useLanguages' => true,
						'columnWidth' => 100,
					],
					[
						'name' => 'infolabel',
						'type' => 'text',
						'label' => __('Info-Überschrift'),
						'value' => __('Ihre Privatsphäre'),
						'useLanguages' => true,
						'columnWidth' => 100,
					],
					[
						'name' => 'infotext',
						'type' => 'textarea',
						'label' => __('Info-Text'),
						'value' => __("Wir verwenden Cookies, um Ihnen ein optimales Webseitenerlebnis zu bieten.\nDazu zählen Cookies, die für den Betrieb der Seite notwendig sind, sowie solche, die lediglich zu anonymen Statistikzwecken genutzt werden.\nSie können Ihre Auswahl jederzeit in den Cookie-Einstellungen anpassen oder widerrufen."),
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 5,
					],
					[
						'name' => 'buttontext_edit',
						'type' => 'text',
						'label' => __('Beschriftung Einstellungen-Button'),
						'value' => __('Cookie-Einstellungen'),
						'useLanguages' => true,
						'columnWidth' => 25,
					],
					[
						'name' => 'buttontext_confirm',
						'type' => 'text',
						'label' => __('Beschriftung Bestätigung-Button'),
						'value' => __('Auswahl bestätigen'),
						'useLanguages' => true,
						'columnWidth' => 25,
					],
					[
						'name' => 'buttontext_decline',
						'type' => 'text',
						'label' => __('Beschriftung Ablehnen-Button'),
						'value' => __('Alle ablehnen'),
						'useLanguages' => true,
						'columnWidth' => 25,
					],
					[
						'name' => 'buttontext_accept',
						'type' => 'text',
						'label' => __('Beschriftung Alles-akzeptieren-Button'),
						'value' => __('Alle akzeptieren'),
						'useLanguages' => true,
						'columnWidth' => 25,
					],
					[
						'name' => 'imprint_page',
						'type' => 'pageListSelect',
						'label' => __('Impressum-Seite'),
						'notes' => __('Optionaler Link zu deiner Impressum-Seite'),
						'columnWidth' => 50,
					],
					[
						'name' => 'privacy_page',
						'type' => 'pageListSelect',
						'label' => __('Datenschutz-Seite'),
						'notes' => __('Optionaler Link zu deiner Datenschutz-Seite'),
						'columnWidth' => 50,
					],
				]
			],
		]
	],
	// --- Cookies ---
	[
		'type' => 'wrapper',
		'label' => __('Cookies'),
		'name' => 'cookies',
		'attr' => [
			'title' => __('Cookies'),
			'class' => 'WireTab',
		],
		'children' => [
			[
				'name' => 'mask_prompt',
				'type' => 'text',
				'label' => __('Info-Text für blockierte Inhalte'),
				'value' => __('Dieser Inhalt benötigt deine Zustimmung zu {category}'),
				'description' => __('Wird mit einem Button angezeigt, falls der entsprechende Inhalt blockiert wird'),
				'notes' => __('Verwende {category} als Platzhalter für den Kategorienamen'),
				'useLanguages' => true,
				'columnWidth' => 100,
			],
			// --- Notwendige Cookies ---
			[
				'type' => 'fieldset',
				'label' => __('Notwendige Cookies'),
				'icon' => 'shield',
				'collapsed' => false,
				'children' => [
					[
						'name' => 'essential_title',
						'type' => 'text',
						'label' => __('Kategorie-Titel'),
						'value' => __('Notwendige Cookies'),
						'useLanguages' => true,
						'columnWidth' => 100,
					],
					[
						'name' => 'essential_description',
						'type' => 'textarea',
						'label' => __('Kategorie-Beschreibung'),
						'value' => __('Notwendige Cookies helfen dabei, eine Webseite nutzbar zu machen, indem sie Grundfunktionen wie Seitennavigation und Zugriff auf sichere Bereiche der Webseite ermöglichen. Die Webseite kann ohne diese Cookies nicht richtig funktionieren.'),
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 3,
					],
					[
						'type' => 'textarea',
						'name' => 'essential_cookies',
						'label' => __('Cookies'),
						'description' => __('Cookie-Format: `Name|Anbieter|Zweck|Dauer` bzw. Cookie-Gruppe: `---Name|Titel|Beschreibung|Hinweis (optional)` (Ein Eintrag pro Zeile)'),
						'notes' => __('Beispiel: `csrf|example.com|Sicherheit|Session` bzw. `---sys|System|Notwendige Cookies für den Betrieb.`'),
						'value' => "wires|example.com|Der Cookie ist für die sichere Anmeldung und die Erkennung von Spam oder Missbrauch der Webseite erforderlich.|Sitzung\ncmnstr|example.com|Speichert den Zustimmungsstatus des Benutzers für Cookies.|180 Tag(e)",
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 5,
					],
				]
			],
			// --- Funktionelle Cookies ---
			[
				'type' => 'fieldset',
				'label' => __('Funktionelle Cookies'),
				'icon' => 'sliders',
				'collapsed' => true,
				'children' => [
					[
						'name' => 'functional_enabled',
						'type' => 'checkbox',
						'label' => __('Funktionelle Cookies aktiviert'),
						'value' => false,
					],
					[
						'name' => 'functional_title',
						'type' => 'text',
						'label' => __('Kategorie-Titel'),
						'value' => __('Funktionelle Cookies'),
						'useLanguages' => true,
						'columnWidth' => 100,
						'showIf' => 'functional_enabled=1',
					],
					[
						'name' => 'functional_description',
						'type' => 'textarea',
						'label' => __('Kategorie-Beschreibung'),
						'value' => __('Funktionelle Cookies ermöglichen erweiterte Funktionalitäten und Personalisierungen wie Spracheinstellungen oder Dark Mode. Sie verbessern die Benutzerfreundlichkeit und ermöglichen ein individualisiertes Nutzererlebnis. Ohne diese Cookies funktioniert die Webseite zwar, bietet aber weniger komfortable Funktionen.'),
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 3,
						'showIf' => 'functional_enabled=1',
					],
					[
						'type' => 'textarea',
						'name' => 'functional_cookies',
						'label' => __('Cookies'),
						'description' => __('Cookie-Format: `Name|Anbieter|Zweck|Dauer` bzw. Cookie-Gruppe: `---Name|Titel|Beschreibung|Hinweis (optional)` (Ein Eintrag pro Zeile)'),
						'notes' => __('Beispiel: `lang|example.com|Sprachauswahl|1 Jahr` bzw. `---usrgrp|Nutzereinstellungen|Zur Verwaltung von Nutzervoreinstellungen.`'),
						'value' => 'cookie1|example.com|Dies ist ein beispielhafter Cookie.|180 Tag(e)',
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 5,
						'showIf' => 'functional_enabled=1',
					],
				]
			],
			// --- Statistik-Cookies ---
			[
				'type' => 'fieldset',
				'label' => __('Statistik-Cookies'),
				'icon' => 'bar-chart',
				'collapsed' => true,
				'children' => [
					[
						'name' => 'statistics_enabled',
						'type' => 'checkbox',
						'label' => __('Statistik-Cookies aktiviert'),
						'value' => false,
					],
					[
						'name' => 'statistics_title',
						'type' => 'text',
						'label' => __('Kategorie-Titel'),
						'value' => __('Statistik-Cookies'),
						'useLanguages' => true,
						'columnWidth' => 100,
						'showIf' => 'statistics_enabled=1',
					],
					[
						'name' => 'statistics_description',
						'type' => 'textarea',
						'label' => __('Kategorie-Beschreibung'),
						'value' => __('Statistik-Cookies helfen uns zu verstehen, wie Besucher die Webseite nutzen, indem sie Daten zu Seitenaufrufen, Verweildauer und Nutzerverhalten sammeln. Diese Daten werden anonymisiert und dienen ausschließlich zur Optimierung und Verbesserung der Webseite.'),
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 3,
						'showIf' => 'statistics_enabled=1',
					],
					[
						'type' => 'textarea',
						'name' => 'statistics_cookies',
						'label' => __('Cookies'),
						'description' => __('Cookie-Format: `Name|Anbieter|Zweck|Dauer` bzw. Cookie-Gruppe: `---Name|Titel|Beschreibung|Hinweis (optional)` (Ein Eintrag pro Zeile)'),
						'notes' => __('Beispiel: `_ga|Google|Analyse|2 Jahre` bzw. `---matomo|Matomo|Eigener Analyse-Dienst zur Reichweitenmessung.`'),
						'value' => 'cookie1|example.com|Dies ist ein beispielhafter Cookie.|180 Tag(e)',
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 5,
						'showIf' => 'statistics_enabled=1',
					],
				]
			],
			// --- Marketing-Cookies ---
			[
				'type' => 'fieldset',
				'label' => __('Marketing-Cookies'),
				'icon' => 'bullhorn',
				'collapsed' => true,
				'children' => [
					[
						'name' => 'marketing_enabled',
						'type' => 'checkbox',
						'label' => __('Marketing-Cookies aktiviert'),
						'value' => false,
					],
					[
						'name' => 'marketing_title',
						'type' => 'text',
						'label' => __('Kategorie-Titel'),
						'value' => __('Marketing-Cookies'),
						'useLanguages' => true,
						'columnWidth' => 100,
						'showIf' => 'marketing_enabled=1',
					],
					[
						'name' => 'marketing_description',
						'type' => 'textarea',
						'label' => __('Kategorie-Beschreibung'),
						'value' => __('Marketing-Cookies ermöglichen personalisierte Werbeinhalte und Remarketing-Kampagnen. Sie verfolgen die Nutzeraktivitäten über mehrere Webseiten hinweg, um Ihnen relevante Anzeigen anzuzeigen und Conversions zu messen. Diese Cookies werden oft von Werbenetzwerken und Social-Media-Plattformen gesetzt.'),
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 3,
						'showIf' => 'marketing_enabled=1',
					],
					[
						'type' => 'textarea',
						'name' => 'marketing_cookies',
						'label' => __('Cookies'),
						'description' => __('Cookie-Format: `Name|Anbieter|Zweck|Dauer` bzw. Cookie-Gruppe: `---Name|Titel|Beschreibung|Hinweis (optional)` (Ein Eintrag pro Zeile)'),
						'notes' => __('Beispiel: `_fbp|Facebook|Marketing|3 Monate` bzw. `---ads|Google Ads|Hilft uns Werbeanzeigen relevanter zu gestalten.`'),
						'value' => 'cookie1|example.com|Dies ist ein beispielhafter Cookie.|180 Tag(e)',
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 5,
						'showIf' => 'marketing_enabled=1',
					],
				]
			],
			// --- External Inhalte ---
			[
				'type' => 'fieldset',
				'label' => __('Externe Inhalte'),
				'icon' => 'external-link',
				'collapsed' => true,
				'children' => [
					[
						'name' => 'external_enabled',
						'type' => 'checkbox',
						'label' => __('Externe Inhalte aktiviert'),
						'value' => false,
					],
					[
						'name' => 'external_title',
						'type' => 'text',
						'label' => __('Kategorie-Titel'),
						'value' => __('Externe Inhalte'),
						'useLanguages' => true,
						'columnWidth' => 100,
						'showIf' => 'external_enabled=1',
					],
					[
						'name' => 'external_description',
						'type' => 'textarea',
						'label' => __('Kategorie-Beschreibung'),
						'value' => __('Externe Inhalte wie eingebettete Videos, Karten und Widgets setzen zusätzliche Cookies, um ihre Funktionalität bereitzustellen. Die Anbieter dieser Services können Daten über Nutzer sammeln und Daten auch außerhalb dieser Webseite austauschen und verarbeiten.'),
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 3,
						'showIf' => 'external_enabled=1',
					],
					[
						'type' => 'textarea',
						'name' => 'external_cookies',
						'label' => __('Inhalte'),
						'description' => __('Cookie-Format: `Name|Anbieter|Zweck|Dauer` bzw. Cookie-Gruppe: `---Name|Titel|Beschreibung|Hinweis (optional)` (Ein Eintrag pro Zeile)'),
						'notes' => __('Beispiel: `PREF|YouTube|Video-Präferenz|8 Monate` bzw. `---yt|YouTube|Wird zum Abspielen von eingebetteten Videos benötigt.`'),
						'value' => 'cookie1|example.com|Dies ist ein beispielhafter Cookie.|180 Tag(e)',
						'useLanguages' => true,
						'columnWidth' => 100,
						'rows' => 5,
						'showIf' => 'external_enabled=1',
					],
				]
			],
		]
	],
	// --- Security Headers ---
	[
		'type' => 'wrapper',
		'label' => __('Security Headers'),
		'name' => 'security_headers',
		'attr' => [
			'title' => __('Security Headers'),
			'class' => 'WireTab',
		],
		'children' => [
			// --- HSTS ---
			[
				'type' => 'fieldset',
				'label' => __('HSTS (HTTP Strict-Transport-Security)'),
				'icon' => 'lock',
				'collapsed' => true,
				'description' => __('Erzwingt sichere (HTTPS) Verbindungen. Warnung: Nur aktivieren, wenn alle Subdomains HTTPS unterstützen. [MDN Web Docs: HSTS](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security)'),
				'children' => [
					[
						'name' => 'hsts_enabled',
						'type' => 'checkbox',
						'label' => __('HSTS aktivieren'),
						'label2' => __('Erzwinge HTTPS-Verbindungen'),
						'value' => false,
					],
					[
						'name' => 'hsts_max_age',
						'type' => 'integer',
						'label' => __('max-age (Sekunden)'),
						'notes' => __('Standard: 31536000 (1 Jahr)'),
						'value' => 31536000,
						'columnWidth' => 50,
						'showIf' => 'hsts_enabled=1',
					],
					[
						'name' => 'hsts_include_subdomains',
						'type' => 'checkbox',
						'label' => __('Subdomains einschließen'),
						'value' => false,
						'columnWidth' => 25,
						'showIf' => 'hsts_enabled=1',
					],
					[
						'name' => 'hsts_preload',
						'type' => 'checkbox',
						'label' => __('Preload'),
						'notes' => __('Erlaube die Aufnahme in Browser-Preload-Listen (siehe hstspreload.org)'),
						'value' => false,
						'columnWidth' => 25,
						'showIf' => 'hsts_enabled=1',
					],
				]
			],
			// --- CSP ---
			[
				'type' => 'fieldset',
				'label' => __('Content Security Policy (CSP)'),
				'icon' => 'shield',
				'collapsed' => true,
				'description' => __('Schutz vor XSS-Angriffen. Warnung: Falsche Konfiguration kann die Website unzugänglich machen. [MDN Web Docs: CSP](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy)'),
				'children' => [
					[
						'name' => 'csp_enabled',
						'type' => 'checkbox',
						'label' => __('CSP aktivieren'),
						'label2' => __('Schütze vor XSS-Angriffen'),
						'value' => false,
					],
					[
						'name' => 'csp_report_only',
						'type' => 'checkbox',
						'label' => __('Report-Only-Modus'),
						'label2' => __('Protokolliere Verstöße, ohne Ressourcen zu blockieren'),
						'value' => false,
						'columnWidth' => 100,
						'showIf' => 'csp_enabled=1',
					],
					[
						'name' => 'csp_policy',
						'type' => 'textarea',
						'label' => __('CSP-Richtlinien'),
						'description' => __('Verwende den Platzhalter „nonce-proxy" dort, wo eine pro-Request generierte Nonce eingefügt werden soll'),
						'notes' => __('`nonce-proxy` wird durch eine serverseitig generierte Nonce ersetzt. [MDN Web Docs: CSP: nonce](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/nonce)'),
						'value' => "default-src 'self' 'nonce-proxy' 'strict-dynamic'; style-src-attr 'unsafe-inline'; img-src 'self' data:; form-action 'self'; upgrade-insecure-requests; block-all-mixed-content;",
						'columnWidth' => 100,
						'rows' => 8,
						'showIf' => 'csp_enabled=1',
					],
					[
						'name' => 'csp_report_to',
						'type' => 'text',
						'label' => __('Report-To-URI'),
						'notes' => __('URL, an die CSP-Verstöße gemeldet werden sollen'),
						'value' => '',
						'columnWidth' => 100,
						'showIf' => 'csp_enabled=1',
					],
					[
						'name' => 'framing_policy',
						'type' => 'radios',
						'label' => __('Framing-Richtlinie (frame-ancestors)'),
						'description' => __('Kontrolliere das Einbetten in iFrames. [MDN Web Docs: CSP: frame-ancestors](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/frame-ancestors)'),
						'options' => [
							'none' => __('Verbiete alles Framing'),
							'self' => __('Erlaube Framing von der gleichen Herkunft (empfohlen)'),
							'custom' => __('Erlaube bestimmte Herkünfte'),
						],
						'value' => 'self',
						'columnWidth' => 100,
						'showIf' => 'csp_enabled=1',
					],
					[
						'name' => 'framing_custom_origins',
						'type' => 'textarea',
						'label' => __('Benutzerdefinierte erlaubte Herkünfte'),
						'notes' => __('Durch Leerzeichen getrennte Liste von Herkünften, z.B. https://example.com https://other.com'),
						'value' => '',
						'columnWidth' => 100,
						'rows' => 3,
						'showIf' => 'csp_enabled=1&framing_policy=custom',
					],
				]
			],
			// --- Other Security Headers ---
			[
				'type' => 'fieldset',
				'label' => __('Weitere Sicherheits-Header'),
				'icon' => 'cogs',
				'collapsed' => true,
				'children' => [
					[
						'name' => 'x_content_type_options_enabled',
						'type' => 'checkbox',
						'label' => __('X-Content-Type-Options'),
						'label2' => __('Setze auf „nosniff" um MIME-Sniffing-Angriffe zu verhindern'),
						'notes' => __('[MDN Web Docs: X-Content-Type-Options](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options)'),
						'value' => true,
						'columnWidth' => 100,
					],
					[
						'name' => 'referrer_policy',
						'type' => 'select',
						'label' => __('Referrer-Policy'),
						'options' => [
							'no-referrer' => 'no-referrer',
							'no-referrer-when-downgrade' => 'no-referrer-when-downgrade',
							'origin' => 'origin',
							'origin-when-cross-origin' => 'origin-when-cross-origin',
							'same-origin' => 'same-origin',
							'strict-origin' => 'strict-origin',
							'strict-origin-when-cross-origin' => __('strict-origin-when-cross-origin (empfohlen)'),
							'unsafe-url' => 'unsafe-url',
						],
						'value' => 'strict-origin-when-cross-origin',
						'columnWidth' => 100,
						'notes' => __('[MDN Web Docs: Referrer-Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy)'),
					],
					[
						'name' => 'coop_policy',
						'type' => 'select',
						'label' => __('Cross-Origin-Opener-Policy (COOP)'),
						'options' => [
							'' => __('Nicht gesetzt'),
							'unsafe-none' => 'unsafe-none',
							'same-origin-allow-popups' => __('same-origin-allow-popups (empfohlen)'),
							'same-origin' => 'same-origin',
						],
						'value' => 'same-origin-allow-popups',
						'columnWidth' => 33,
						'notes' => __('[MDN Web Docs: Cross-Origin-Opener-Policy (COOP)](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cross-Origin-Opener-Policy)'),
					],
					[
						'name' => 'coep_policy',
						'type' => 'select',
						'label' => __('Cross-Origin-Embedder-Policy (COEP)'),
						'options' => [
							'' => __('Nicht gesetzt'),
							'unsafe-none' => 'unsafe-none',
							'require-corp' => 'require-corp',
							'credentialless' => 'credentialless',
						],
						'value' => '',
						'columnWidth' => 33,
						'notes' => __('[MDN Web Docs: Cross-Origin-Embedder-Policy (COEP)](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cross-Origin-Embedder-Policy)'),
					],
					[
						'name' => 'corp_policy',
						'type' => 'select',
						'label' => __('Cross-Origin-Resource-Policy (CORP)'),
						'options' => [
							'' => __('Nicht gesetzt'),
							'same-site' => 'same-site',
							'same-origin' => __('same-origin (empfohlen)'),
							'cross-origin' => 'cross-origin',
						],
						'value' => 'same-origin',
						'columnWidth' => 34,
						'notes' => __('[MDN Web Docs: Cross-Origin-Resource-Policy (CORP)](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cross-Origin-Resource-Policy)'),
					],
				]
			],
			// --- Permissions Policy ---
			[
				'type' => 'fieldset',
				'label' => __('Permissions-Policy'),
				'icon' => 'camera-retro',
				'collapsed' => true,
				'description' => __('Kontrolliere den Zugriff auf Browser-Funktionen und APIs (Kamera, Mikrofon, Geolokalisierung, etc.). [MDN Web Docs: Permissions-Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Permissions-Policy)'),
				'children' => [
					[
						'name' => 'permissions_policy_enabled',
						'type' => 'checkbox',
						'label' => __('Permissions-Policy aktivieren'),
						'value' => false,
					],
					[
						'name' => 'permissions_policy',
						'type' => 'textarea',
						'label' => __('Permissions-Policy-Richtlinien'),
						'description' => __('Verwende „()" zum Deaktivieren, „(self)" zum Erlauben für die gleiche Herkunft, oder „(self "https://example.com")" für spezifische Herkünfte'),
						'value' => 'fullscreen=(), geolocation=(), camera=(), microphone=(), usb=()',
						'columnWidth' => 100,
						'rows' => 5,
						'showIf' => 'permissions_policy_enabled=1',
					],
				]
			],
		]
	],
	// --- Google Analytics ---
	[
		'type' => 'wrapper',
		'label' => __('Google Analytics'),
		'name' => 'google_analytics',
		'attr' => [
			'title' => __('Google Analytics'),
			'class' => 'WireTab',
		],
		'children' => [
			[
				'name' => 'ga_property_id',
				'type' => 'text',
				'label' => __('Property-ID'),
				'description' => __('Gib eine Property-ID ein um Tracking zu aktivieren'),
				'notes' => __('Unterstützt: UA-XXXXXXXX (Universal), G-XXXXXXXX (GA4), AW-XXXXXXXX (Ads), DC-XXXXXXXX (Floodlight)'),
				'value' => '',
			],
		]
	],
];
