<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * CookieMonster Module
 *
 * Verwaltet die Cookie-Einwilligung, das Tracking und HTTP-Sicherheits-Header.
 */
class CookieMonster extends WireData implements Module, ConfigurableModule
{
	/**
	 * CSP Nonce für die aktuelle Anfrage.
	 *
	 * @var string
	 */
	private string $cspNonce = '';

	/**
	 * Speichert die Schlüssel der Cookie-Kategorien, für die der Benutzer seine Zustimmung erteilt hat
	 * Format: ['essential', 'statistics', 'external-youtube', 'external-jobmorgen']
	 *
	 * @var string[]
	 */
	private array $consentedCategories = [];

	/**
	 * Zeigt an, ob Tracking (z.B. Google statistics) basierend auf der Nutzerzustimmung erlaubt ist
	 *
	 * @var bool
	 */
	private bool $allowTracking = false;

	/**
	 * Speichert die Zustände der Google Consent Mode v2 Typen.
	 *
	 * @var array<string, string>
	 */
	private array $googleConsentStates = [];

	/**
	 * Liefert die statischen Basisdaten aller Cookie-Kategorien (Schlüssel und Standardtitel)
	 *
	 * @return array<string, string> Array von Kategorie-Schlüsseln und deren Standardtitel
	 */
	private function _getBaseCategories()
	{
		return [
			'essential' => [
				'title' => $this->_('Notwendig Cookies'),
				'consent' => ['security_storage', 'functionality_storage'],
			],
			'functional' => [
				'title' => $this->_('Funktionelle Cookies'),
				'consent' => ['functionality_storage', 'personalization_storage'],
			],
			'statistics' => [
				'title' => $this->_('Statistik-Cookies'),
				'consent' => ['analytics_storage'],
			],
			'marketing' => [
				'title' => $this->_('Marketing-Cookies'),
				'consent' => ['ad_storage', 'ad_user_data', 'ad_personalization'],
			],
			'external' => [
				'title' => $this->_('Externe Inhalte'),
				'consent' => ['personalization_storage'],
			],
		];
	}

	/**
	 * Installiert das Modul und richtet benötigte Berechtigungen und Felder ein
	 *
	 * @return void
	 */
	public function ___install()
	{
		if (!$this->permissions->get('manage-cookie-monster')) {
			$permission = $this->permissions->add('manage-cookie-monster');
			$permission->title = $this->_('CookieMonster-Einstellungen verwalten');
			$permission->save();
		}

		// Feld anlegen + Optionen füllen
		$fieldName = 'cookiemonster_category';
		$field = $this->fields->get($fieldName);

		if (!$field || !$field->id) {
			$field = new Field();
			$field->type = $this->fieldtypes->get('FieldtypeOptions');
			$field->name = $fieldName;
			$field->label = $this->_('Zugehörige Cookie-Kategorie');
			$field->description = $this->_('Wählen Sie die Cookie-Kategorie, der dieser Inhalt zugeordnet ist.');
			$field->inputfield = 'InputfieldSelect';
			$this->fields->save($field);
		}

		// Optionen setzen (auch bei bereits vorhandenem Feld)
		$baseCategories = $this->_getBaseCategories();
		$optionsArray = new SelectableOptionArray();
		$optionsStringLines = [];
		$index = 1;

		foreach ($baseCategories as $key => $category) {
			$option = new SelectableOption();
			$option->value = $key;
			$option->name = $key;
			$option->title = $category['title'];
			$optionsArray->add($option);
			$optionsStringLines[] = "{$index}={$key}|{$category['title']}";
			$index++;
		}

		$field->type->setOptions($field, $optionsArray);
		$field->set('_options', implode("\n", $optionsStringLines));
		$this->fields->save($field);
	}

	/**
	 * Deinstalliert das Modul und entfernt zugehörige Berechtigungen und Felder
	 *
	 * @return void
	 */
	public function ___uninstall()
	{
		$permission = $this->permissions->get('manage-cookie-monster');
		if ($permission->id) {
			$this->permissions->delete($permission);
			$this->message($this->_('Berechtigung "manage-cookie-monster" erfolgreich gelöscht.'));
		}

		$fieldName = 'cookiemonster_category';
		$field = $this->fields->get($fieldName);
		if ($field->id) {
			foreach ($field->getFieldgroups() as $fieldgroup) {
				$fieldgroup->remove($field);
				$fieldgroup->save();
				$this->message($this->_("Feld '{$fieldName}' von Fieldgroup '{$fieldgroup->name}' entfernt."));
			}

			$this->fields->delete($field);
			$this->message($this->_("Feld '{$fieldName}' erfolgreich gelöscht."));
		}
	}

	/**
	 * Initialisiert das Modul, hängt Hooks ein und verarbeitet die Cookie-Zustimmung
	 *
	 * @return void
	 */
	public function init()
	{
		// Globale Variable setzen
		$this->wire('cmnstr', $this, true);

		$this->cspNonce = \bin2hex(\random_bytes(16));
		$this->addHookAfter('Page::render', $this, 'sendSecurityHeaders');

		$this->processCookieConsent();

		$this->addHookAfter('Page::render', $this, 'addCookieBanner');

		if ($this->allowTracking && $this->get('ga_property_id')) {
			$this->addHookAfter('Page::render', $this, 'addTrackingCode');
		}

		$this->addHook('Pages::saveReady', $this, 'checkForExternalContent');

		// Hook zum Aktualisieren der Feld-Optionen nach Modul-Konfiguration
		$this->addHookAfter('Modules::saveConfig', $this, 'onConfigSave');
	}

	/**
	 * Hook: Wird ausgeführt, wenn die Modulkonfiguration gespeichert wird
	 * Aktualisiert die verfügbaren Cookie-Kategorie-Optionen
	 *
	 * @param HookEvent $event
	 * @return void
	 */
	public function onConfigSave($event)
	{
		$moduleName = $event->arguments(0);
		$data = $event->arguments(1);

		// Nur ausführen, wenn CookieMonster-Config gespeichert wurde
		if ($moduleName === $this->className) {
			$this->updateCookieCategoryField($data);
		}
	}

	/**
	 * Hook: Wird ausgeführt, wenn eine Seite gespeichert wird
	 * Prüft, ob ggf. externe Inhalte vorhanden sind
	 *
	 * @param HookEvent $event
	 * @return void
	 */
	public function checkForExternalContent($event)
	{
		$page = $event->arguments(0);

		$keywords = ['code', 'iframe', 'script', 'video', 'embed', 'external'];
		$hasExternalContent = false;

		/** @var ?RepeaterMatrixPageArray $contentblocks */
		$contentblocks = $page->get('contentblocks|content_blocks');

		if ($contentblocks) {
			foreach ($contentblocks as $block) {
                if (!isset($block->type) || empty($block->type)) {
                    continue;
                }

				/** @var ?RepeaterMatrixPage $block */
				foreach ($keywords as $keyword) {
					if ($block->status !== Page::statusUnpublished && \strpos($block->type, $keyword) !== false) {
						$hasExternalContent = true;
						break 2;
					}
				}
			}
		}

		if ($hasExternalContent) {
			$this->warning('Es liegen ggf. externe Inhalte vor – Bitte Cookies prüfen!');
		}
	}

	private function checkCookieExists()
	{
		return isset($_COOKIE['cmnstr']) && !empty($_COOKIE['cmnstr']);
	}

	/**
	 * Macht eine hierarchische Cookie-Struktur flach für die interne Verarbeitung.
	 * Konvertiert z.B. {"external":{"youtube":true}} zu ["external-youtube" => true]
	 *
	 * @param array<string, mixed> $structure Hierarchische Cookie-Struktur
	 * @param string $prefix Aktuelles Präfix für rekursive Verarbeitung
	 * @return array<string, bool> Flache Struktur mit kombinierten Keys
	 */
	private function flattenCookieStructure($structure, $prefix = '')
	{
		$result = [];

		foreach ($structure as $key => $value) {
			$fullKey = $prefix ? "{$prefix}-{$key}" : $key;

			if ($fullKey === '_version') {
				continue;
			}

			if (\is_bool($value)) {
				$result[$fullKey] = $value;
			} elseif (\is_array($value)) {
				$result = \array_merge($result, $this->flattenCookieStructure($value, $fullKey));
			}
		}

		return $result;
	}

	/**
	 * Prüft, ob eine Kategorie vollständig akzeptiert wurde.
	 * Eine Kategorie gilt als vollständig akzeptiert, wenn:
	 * - Sie direkt als true gespeichert ist, ODER
	 * - Alle ihre Unterkategorien true sind
	 *
	 * @param string $categoryKey Der Kategorie-Schlüssel
	 * @param array<string, bool> $flatConsent Flache Consent-Struktur
	 * @return bool true wenn vollständig akzeptiert
	 */
	private function isCategoryFullyConsented($categoryKey, $flatConsent)
	{
		// Direkt als true gespeichert?
		if (isset($flatConsent[$categoryKey]) && $flatConsent[$categoryKey] === true) {
			return true;
		}

		// Prüfe ob alle Unterkategorien true sind
		$prefix = "{$categoryKey}-";
		$hasSubcategories = false;
		$allSubcategoriesTrue = true;

		foreach ($flatConsent as $key => $value) {
			if (\strpos($key, $prefix) === 0) {
				$hasSubcategories = true;
				if ($value !== true) {
					$allSubcategoriesTrue = false;
					break;
				}
			}
		}

		return $hasSubcategories && $allSubcategoriesTrue;
	}

	/**
	 * Verarbeitet die gespeicherte Cookie-Zustimmung aus dem 'cmnstr'-Cookie
	 *
	 * @return void
	 */
	private function processCookieConsent()
	{
		$this->consentedCategories = ['essential'];
		$this->allowTracking = false;

		// Alle Google Consent Mode Zustände standardmäßig ablehnen
		$this->googleConsentStates = [
			'ad_storage' => 'denied',
			'ad_user_data' => 'denied',
			'ad_personalization' => 'denied',
			'analytics_storage' => 'denied',
			'functionality_storage' => 'denied',
			'personalization_storage' => 'denied',
			'security_storage' => 'denied',
		];

		$baseCategories = $this->_getBaseCategories();

		// Essential ist immer erlaubt
		$essentialConsent = $baseCategories['essential']['consent'];
		if (isset($essentialConsent) && \is_array($essentialConsent)) {
			foreach ($essentialConsent as $googleConsentType) {
				if (isset($this->googleConsentStates[$googleConsentType])) {
					$this->googleConsentStates[$googleConsentType] = 'granted';
				}
			}
		}

		if (!$this->checkCookieExists()) {
			return;
		}

		try {
			$cookieValues = \json_decode($_COOKIE['cmnstr'], true, 512, \JSON_THROW_ON_ERROR);

			// Alten Cookie ohne _cmnstr_version löschen und abbrechen
			if (!isset($cookieValues['_version'])) {
				$host = $_SERVER['HTTP_HOST'];
				\setcookie(
					'cmnstr',
					'',
					[
						'expires' => 1,
						'path' => '/',
						'domain' => ".{$host}",
					]
				);

				$host = str_replace("www.", '', $host);
				\setcookie(
					'cmnstr',
					'',
					[
						'expires' => 1,
						'path' => '/',
						'domain' => ".{$host}",
					]
				);
			}

			// Flatten der hierarchischen Struktur
			$flatConsent = $this->flattenCookieStructure($cookieValues);

			// Verarbeite Google Consent Mode für Base-Kategorien
			foreach ($baseCategories as $key => $data) {
				if ($key === 'essential') {
					continue;
				}

				// Prüfe ob die Kategorie vollständig akzeptiert wurde
				if ($this->isCategoryFullyConsented($key, $flatConsent)) {
					$consent = $data['consent'];
					if (isset($consent) && \is_array($consent)) {
						foreach ($consent as $googleConsentType) {
							if (isset($this->googleConsentStates[$googleConsentType])) {
								$this->googleConsentStates[$googleConsentType] = 'granted';
							}
						}
					}
				}
			}

			// Alle Schlüssel aus dem Cookie, die TRUE sind, in consentedCategories aufnehmen
			// Dies inkludiert nun auch verschachtelte Keys wie "external-youtube"
			foreach ($flatConsent as $key => $value) {
				if ($value === true && !\in_array($key, $this->consentedCategories, true)) {
					$this->consentedCategories[] = $key;
				}
			}

			// Tracking erlauben wenn statistics vollständig akzeptiert
			if ($this->isCategoryFullyConsented('statistics', $flatConsent)) {
				$this->allowTracking = true;
			}
		} catch (\JsonException $e) {
			$this->error($this->_('Malformed CookieMonster consent cookie detected. Resetting to default.'));
		}
	}

	/**
	 * Prüft, ob eine spezifische Cookie-Kategorie vom Benutzer zugestimmt wurde
	 *
	 * @param string|SelectableOptionArray|null $categoryKey Der Schlüssel der Cookie-Kategorie (z.B. 'essential', 'external-youtube')
	 * @return bool `true`, wenn die Kategorie freigeschaltet ist, sonst `false`
	 */
	public function isUnlocked($categoryKey)
	{
		if ($categoryKey instanceof SelectableOptionArray) {
			$categoryKey = $categoryKey->value;
		}

		if (empty($categoryKey)) {
			return false;
		}

		// Exakte Übereinstimmung
		if (\in_array($categoryKey, $this->consentedCategories, true)) {
			return true;
		}

		// Prüfe ob eine übergeordnete Kategorie vollständig akzeptiert wurde
		// Z.B. wenn "external" akzeptiert ist, ist auch "external-youtube" erlaubt
		$parts = \explode('-', $categoryKey);
		if (\count($parts) > 1) {
			$parentKey = $parts[0];

			// Wenn die Parent-Kategorie vollständig akzeptiert ist
			if (\in_array($parentKey, $this->consentedCategories, true)) {
				// Prüfe ob es keine anderen Unterkategorien mit false gibt
				$prefix = "{$parentKey}-";
				foreach ($this->consentedCategories as $consentedKey) {
					if (\strpos($consentedKey, $prefix) === 0) {
						// Es gibt spezifische Unterkategorien, also keine pauschale Erlaubnis
						return false;
					}
				}

				// Parent ist akzeptiert und es gibt keine spezifischen Unter-Keys
				return true;
			}
		}

		return false;
	}

	/**
	 * Gibt ein Array aller Cookie-Kategorien zurück, denen der Benutzer zugestimmt hat
	 *
	 * @return string[] Array der zugestimmten Cookie-Kategorien-Schlüssel
	 */
	public function getUnlockedCategories()
	{
		return $this->consentedCategories;
	}

	/**
	 * Öffentlicher Getter für den Tracking-Zustimmungsstatus
	 *
	 * @return bool `true`, wenn Tracking erlaubt ist, sonst `false`
	 */
	public function allowTracking()
	{
		return $this->allowTracking;
	}

	/**
	 * Öffentlicher Getter für die Google Consent Mode v2 Zustände
	 *
	 * @return array<string, string> Die aktuellen Google Consent Mode v2 Zustände.
	 */
	public function getGoogleConsentStates()
	{
		return $this->googleConsentStates;
	}

	/**
	 * Hook: Fügt den Cookie-Banner zum gerenderten Seiten-Output hinzu
	 *
	 * @param HookEvent $event Das ProcessWire `HookEvent`-Objekt
	 * @return void
	 */
	public function addCookieBanner($event)
	{
		/** @var Page $page */
		$page = $event->object;

		if (\in_array($page->template->name, ['admin', 'form-builder'], true)) {
			return;
		}

		$output = (string) $event->return;
		$moduleUrl = $this->config->urls->{$this};
		$version = $this->modules->getModuleInfo($this)['version'];

		if ((bool) $this->get('use_stylesheet')) {
			$output = \str_replace(
				'</head>',
				"<link rel='stylesheet' nonce='{$this->cspNonce}' href='{$moduleUrl}assets/CookieMonster.css?v={$version}'></head>",
				$output
			);
		}

		$output = \str_replace(
			'</head>',
			"<script nonce='{$this->cspNonce}' src='{$moduleUrl}assets/CookieMonster.js?v={$version}' type='module'></script></head>",
			$output
		);

		$bannerHtml = $this->renderBanner();
		$output = \preg_replace('/(<body[^>]*>)/m', '$1' . $bannerHtml, $output);

		$event->return = $output;
	}

	/**
	 * Rendert das HTML des Cookie-Banners unter Verwendung einer Template-Datei
	 *
	 * @return string Cookie-Banner HTML-Code
	 */
	private function renderBanner()
	{
		$categories = $this->buildCategoriesData();
		$lang = $this->getLanguageSuffix();

		$imprintPageId = (int) $this->get('imprint_page');
		$privacyPageId = (int) $this->get('privacy_page');

		/** @var Page|NullPage $imprintPage */
		$imprintPage = $this->pages->get($imprintPageId);
		/** @var Page|NullPage $privacyPage */
		$privacyPage = $this->pages->get($privacyPageId);

		$data = [
			'title' => (string) $this->get("titletext{$lang}"),
			'label' => (string) $this->get("infolabel{$lang}"),
			'body' => (string) $this->get("infotext{$lang}"),
			'lang' => $lang,
			'open' => $this->checkCookieExists(),
			'buttonEdit' => (string) $this->get("buttontext_edit{$lang}"),
			'buttonConfirm' => (string) $this->get("buttontext_confirm{$lang}"),
			'buttonDecline' => (string) $this->get("buttontext_decline{$lang}"),
			'buttonAccept' => (string) $this->get("buttontext_accept{$lang}"),
			'imprintPage' => $imprintPage,
			'privacyPage' => $privacyPage,
			'categories' => $categories,
		];

		return $this->renderTemplate('banner.php', $data);
	}

	/**
	 * Erstellt ein Array von Cookie-Kategorien mit ihren Einstellungen und Beschreibungen
	 *
	 * @return array<array{key: string, enabled: bool, title: string, description: string, cookies: string, checked: bool}> Cookie-Kategorie-Daten
	 */
	private function buildCategoriesData()
	{
		$lang = $this->getLanguageSuffix();
		$baseCategories = $this->_getBaseCategories();
		$categories = [];

		foreach ($baseCategories as $key => $category) {
			$baseTitle = $category['title'];

			$title = (string) $this->get("{$key}_title{$lang}") ?: $baseTitle;
			$description = (string) $this->get("{$key}_description{$lang}") ?: $title;
			$cookies = (string) $this->get("{$key}_cookies{$lang}") ?: '';
			$enabled = ($key === 'essential') ? true : (bool) $this->get("{$key}_enabled");

			$categories[] = [
				'key' => $key,
				'enabled' => $enabled,
				'title' => $title,
				'description' => $description,
				'cookies' => $cookies,
				'checked' => $this->isUnlocked($key),
			];
		}

		return $categories;
	}

	/**
	 * Parst den Cookie-String in eine gruppierte Struktur.
	 *
	 * @param string $text Der rohe Text aus dem Textarea-Feld
	 * @return array Gruppierte Cookie-Daten
	 */
	private function parseCookieData($text)
	{
		$groups = [];
		$headers = [
			'name' => $this->_('Name'),
			'provider' => $this->_('Anbieter'),
			'purpose' => $this->_('Zweck'),
			'duration' => $this->_('Dauer'),
		];

		// Header-Keys für die Zuordnung
		$headerKeys = array_keys($headers);

		// Standard-Gruppe für Cookies, die vor der ersten Definition stehen
		$currentGroup = [
			'id' => 'default',
			'title' => '',
			'description' => '',
			'notice' => '',
			'cookies' => [],
		];

		$lines = \explode("\n", $text);

		foreach ($lines as $line) {
			$line = \trim($line);
			if (empty($line)) {
				continue;
			}

			// Prüfen auf Gruppen-Start: "---id|Titel|Beschreibung|Hinweis"
			if (\strpos($line, '---') === 0) {
				// Wenn die aktuelle Gruppe Cookies hat, speichern wir sie ab
				if (!empty($currentGroup['cookies']) || $currentGroup['id'] !== 'default') {
					$groups[] = $currentGroup;
				}

				// Neue Gruppe initialisieren
				// Entferne '---' und teile auf
				$groupParts = \explode('|', \substr($line, 3));

				$currentGroup = [
					'id' => \trim($groupParts[0] ?? uniqid('grp_')),
					'title' => \trim($groupParts[1] ?? ''),
					'description' => \trim($groupParts[2] ?? ''),
					'notice' => \trim($groupParts[3] ?? ''),
					'cookies' => [],
				];

				continue;
			}

			$columns = \array_map('\trim', \explode('|', $line));

			// Auf 4 Spalten auffüllen (Name, Anbieter, Zweck, Dauer)
			$columns = \array_pad($columns, 4, '');

			// Assoziatives Array erstellen: ['name' => '...', 'provider' => '...']
			$cookieData = \array_combine($headerKeys, \array_slice($columns, 0, 4));

			$currentGroup['cookies'][] = $cookieData;
		}

		// Die letzte Gruppe hinzufügen, falls vorhanden
		if (!empty($currentGroup['cookies']) || $currentGroup['id'] !== 'default') {
			$groups[] = $currentGroup;
		}

		return $groups;
	}

	/**
	 * Hook: Fügt den Google Analytics Tracking-Code zum gerenderten Seiten-Output hinzu
	 *
	 * @param HookEvent $event
	 * @return void
	 */
	public function addTrackingCode($event)
	{
		/** @var Page $page */
		$page = $event->object;

		if (\in_array($page->template->name, ['admin', 'form-builder'], true)) {
			return;
		}

		$trackingCode = $this->renderTemplate('ga-tracking.php', [
			'propertyId' => (string) $this->get('ga_property_id'),
			'googleConsentStates' => $this->getGoogleConsentStates(),
			'cspNonce' => (string) $this->cspNonce,
		]);

		$event->return = \str_replace('</body>', $trackingCode . '</body>', (string) $event->return);
	}

	/**
	 * Hook: Sendet HTTP-Sicherheits-Header
	 *
	 * @param HookEvent $event Das ProcessWire `HookEvent`-Objekt.
	 * @return void
	 */
	public function sendSecurityHeaders($event)
	{
		if ($this->config->admin || $this->config->ajax) {
			return;
		}

		if (\headers_sent()) {
			return;
		}

		$this->sendReferrerPolicyHeader();
		$this->sendContentTypeOptionsHeader();
		$this->sendHSTSHeader();
		$this->sendCSPHeader($event);
		$this->sendCrossOriginHeaders();
		$this->sendPermissionsPolicyHeader();
	}

	/**
	 * Sendet den `Referrer-Policy` HTTP-Header basierend auf Moduleinstellungen
	 *
	 * @return void
	 */
	private function sendReferrerPolicyHeader()
	{
		if ($policy = $this->get('referrer_policy')) {
			\header("Referrer-Policy: {$policy}");
		}
	}

	/**
	 * Sendet den `X-Content-Type-Options` HTTP-Header basierend auf Moduleinstellungen
	 *
	 * @return void
	 */
	private function sendContentTypeOptionsHeader()
	{
		if ((bool) $this->get('x_content_type_options_enabled')) {
			\header('X-Content-Type-Options: nosniff');
		}
	}

	/**
	 * Sendet den `Strict-Transport-Security` (HSTS) HTTP-Header
	 *
	 * @return void
	 */
	private function sendHSTSHeader()
	{
		if (!(bool) $this->get('hsts_enabled') || !$this->config->https) {
			return;
		}

		$maxAge = (int) $this->get('hsts_max_age') ?: 31536000;
		$parts = ["max-age={$maxAge}"];

		if ((bool) $this->get('hsts_include_subdomains')) {
			$parts[] = 'includeSubDomains';
		}
		if ((bool) $this->get('hsts_preload')) {
			$parts[] = 'preload';
		}

		\header('Strict-Transport-Security: ' . \implode('; ', $parts));
	}

	/**
	 * Sendet den `Content-Security-Policy` (CSP) HTTP-Header zur Minderung von XSS-Angriffen
	 * Unterstützt Report-Only-Modus und Nonce-Injektion für Inline-Skripte und -Stile
	 *
	 * @param HookEvent $event Das `HookEvent` zum Modifizieren des Seiten-Outputs für Nonces
	 * @return void
	 */
	private function sendCSPHeader($event)
	{
		if (!(bool) $this->get('csp_enabled')) {
			return;
		}

		$headerName = (bool) $this->get('csp_report_only')
			? 'Content-Security-Policy-Report-Only'
			: 'Content-Security-Policy';

		$policy = \trim((string) $this->get('csp_policy'));
		$policy = \preg_replace('#[\n\r]#', ' ', $policy);
		$cspParts = [];

		if (\strpos($policy, 'nonce-proxy') !== false) {
			$policy = \str_replace('nonce-proxy', "nonce-{$this->cspNonce}", $policy);

			$output = (string) $event->return;
			$output = \preg_replace('#nonce=["\']proxy["\']#', "nonce=\"{$this->cspNonce}\"", $output);
			$event->return = $output;
		}

		if ($policy) {
			$cspParts[] = $policy;
		}

		if (\strpos($policy, 'frame-ancestors') === false) {
			$frameValue = $this->getFrameAncestorsValue();
			if ($frameValue) {
				$cspParts[] = "frame-ancestors {$frameValue}";
			}
		}

		if ($reportTo = $this->get('csp_report_to')) {
			$cspParts[] = 'report-uri ' . $this->sanitizer->url((string) $reportTo);
		}

		if (!empty($cspParts)) {
			\header($headerName . ': ' . \implode('; ', \array_filter($cspParts)));
		}
	}

	/**
	 * Bestimmt den Wert für die `frame-ancestors` CSP-Direktive basierend auf Moduleinstellungen
	 *
	 * @return string Der Wert für die `frame-ancestors`-Direktive.
	 */
	private function getFrameAncestorsValue()
	{
		$policy = (string) $this->get('framing_policy');

		switch ($policy) {
			case 'self':
				return "'self'";
			case 'custom':
				return (string) $this->get('framing_custom_origins');
			default:
				return "'none'";
		}
	}

	/**
	 * Sendet `Cross-Origin-Opener-Policy` (COOP), `Cross-Origin-Embedder-Policy` (COEP),
	 * und `Cross-Origin-Resource-Policy` (CORP) HTTP-Header basierend auf Moduleinstellungen
	 *
	 * @return void
	 */
	private function sendCrossOriginHeaders()
	{
		if ($coop = $this->get('coop_policy')) {
			\header("Cross-Origin-Opener-Policy: {$coop}");
		}
		if ($coep = $this->get('coep_policy')) {
			\header("Cross-Origin-Embedder-Policy: {$coep}");
		}
		if ($corp = $this->get('corp_policy')) {
			\header("Cross-Origin-Resource-Policy: {$corp}");
		}
	}

	/**
	 * Sendet den `Permissions-Policy` HTTP-Header basierend auf Moduleinstellungen
	 *
	 * @return void
	 */
	private function sendPermissionsPolicyHeader()
	{
		if ((bool) $this->get('permissions_policy_enabled') && $policy = $this->get('permissions_policy')) {
			\header("Permissions-Policy: {$policy}");
		}
	}

	/**
	 * Rendert eine HTML-Tabelle von Cookies aus einem formatierten String-Feld
	 *
	 * @param array|null $category Cookie-Kategorie-Daten
	 * @return string Die gerenderte HTML-Tabelle oder leerer String
	 */
	public function renderCookieTable($category = [])
	{
		return $this->renderCookieTemplate($category, 'table.php');
	}

	/**
	 * Rendert eine HTML-Liste von Cookies aus einem formatierten String-Feld
	 *
	 * @param array|null $category Cookie-Kategorie-Daten
	 * @return string Die gerenderte HTML-Tabelle oder leerer String
	 */
	public function renderCookieList($category = [])
	{
		return $this->renderCookieTemplate($category, 'list.php');
	}

	/**
	 * Rendert ein HTML-Template mit Cookies aus einem formatierten String-Feld
	 *
	 * @param array $category Cookie-Kategorie-Daten
	 * @param string $template Template-Pfad
	 * @return string Die gerenderte HTML-Tabelle oder leerer String
	 */
	private function renderCookieTemplate($category = [], $template)
	{
		$cookies = [];
		$cookieField = '';

		if (!empty($category['cookies'])) {
			$cookieField = $category['cookies'];
		}

		// Wenn kein spezifisches Cookie-Feld bereitgestellt wird, sammeln wir alle Cookies
		if (empty($cookieField)) {
			$categoriesData = $this->buildCategoriesData();

			foreach ($categoriesData as $category) {
				if (isset($category['enabled']) && $category['enabled'] === false) {
					continue;
				}

				// Parse den Textinhalt dieser Kategorie in Gruppen
				$parsedGroups = $this->parseCookieData($category['cookies']);

				if (!empty($parsedGroups)) {
					$cookies[] = [
						'type' => 'category',
						'key' => $category['key'],
						'title' => $category['title'],
						'description' => $category['description'],
						'groups' => $parsedGroups,
					];
				}
			}
		} else {
			$parsedGroups = $this->parseCookieData($cookieField);

			if (!empty($parsedGroups)) {
				$cookies[] = [
					'type' => 'category',
					'key' => $category['key'] ?? '',
					'title' => $category['title'] ?? '',
					'description' => $category['description'] ?? '',
					'groups' => $parsedGroups,
				];
			}
		}

		if (empty($cookies)) {
			return '';
		}

		return $this->renderTemplate($template, [
			'headers' => [
				'name' => $this->_('Name'),
				'provider' => $this->_('Anbieter'),
				'purpose' => $this->_('Zweck'),
				'duration' => $this->_('Dauer'),
			],
			'cookies' => $cookies,
		]);
	}

	/**
	 * Prüft, ob eine Kategorie (oder mindestens eines ihrer Kinder) aktiv ist.
	 * Nutzt die bereits gespeicherten Cookie-Präferenzen.
	 *
	 * @param string $categoryKey
	 * @return bool
	 */
	public function isCategoryActive($categoryKey)
	{
		if (!$this->checkCookieExists()) {
			return false;
		}

		// JSON decodieren & flach konvertieren
		try {
			$hierarchical = \json_decode($_COOKIE['cmnstr'], true, 512, \JSON_THROW_ON_ERROR);
			$flat = $this->flattenCookieStructure($hierarchical);
		} catch (\JsonException $e) {
			return false;
		}

		if (!empty($flat[$categoryKey])) {
			return true;
		}

		// Schauen ob eine aktivierte Gruppe dabei ist
		foreach ($flat as $key => $value) {
			if (\strpos($key, $categoryKey . '-') === 0 && $value === true) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Maskiert externen Inhalt bis zur Zustimmung
	 *
	 * @param string $html Der zu maskierende HTML-Inhalt
	 * @param string|SelectableOptionArray|null $category Cookie-Kategorie (z.B. 'external', 'external-youtube')
	 * @return string Maskierter oder normaler Inhalt
	 */
	public function maskContent($html, $category) {
		if ($category instanceof SelectableOptionArray) {
			$category = $category->value;
		}

		// Essential als Kategorie hinterlegen, sofern keine angegeben wurde.
		if (empty($category)) {
			$category = 'essential';
		}

		if ($this->isUnlocked($category)) {
			return $html;
		}

		$lang = $this->getLanguageSuffix();
		$parts = \explode('-', $category);
		$mainCategory = $parts[0];
		$subCategoryKey = $parts[1] ?? null;

		$baseCategories = $this->_getBaseCategories();
		$baseTitle = $baseCategories[$mainCategory]['title'] ?? $category;
		$categoryTitle = (string) $this->get("{$mainCategory}_title{$lang}") ?: $baseTitle;

		// Versuchen, den spezifischen Hinweis aus der Gruppen-Definition zu extrahieren
		$customNotice = '';
		$groups = [];

		if ($subCategoryKey) {
			$cookieText = (string) $this->get("{$mainCategory}_cookies{$lang}");
			if ($cookieText) {
				$groups = $this->parseCookieData($cookieText);
				foreach ($groups as $group) {
					// Prüfen, ob die Gruppen-ID mit der Subkategorie übereinstimmt
					if ($group['id'] === $subCategoryKey && !empty($group['notice'])) {
						$customNotice = $group['notice'];
						break;
					}
				}
			}

			// Falls kein Hinweis gefunden wurde, hängen wir den Sub-Namen an den Titel für den Standard-Prompt an
			$subTitle = $subCategoryKey;
			// Optional: Suche den Klarnamen der Gruppe für den Titel-Fallback
			foreach ($groups as $group) {
				if ($group['id'] === $subCategoryKey) {
					$subTitle = $group['title'];
					break;
				}
			}
			$categoryTitle .= " ({$subTitle})";
		}

		$privacyPageId = (int) $this->get('privacy_page');
		/** @var Page|NullPage $privacyPage */
		$privacyPage = $this->pages->get($privacyPageId);

		// Entscheidung: Eigener Hinweis (notice) ODER Standard-Prompt
		if (!empty($customNotice)) {
			$prompt = $customNotice;
		} else {
			$promptKey = "{$mainCategory}_prompt{$lang}";
			// Falls kein spezifischer Prompt für die Kategorie existiert, globalen Standard nutzen
			$prompt = (string) $this->get($promptKey) ?: ((string) $this->get("mask_prompt{$lang}") ?: $this->_('Dieser Inhalt benötigt deine Zustimmung zu {category}'));

			$prompt = \str_replace('{category}', "<strong>{$categoryTitle}</strong>", $prompt);
		}

		return $this->renderTemplate('mask.php', [
			'category' => $category,
			'buttonEdit' => (string) $this->get("buttontext_edit{$lang}"),
			'buttonAccept' => (string) $this->get("buttontext_accept{$lang}"),
			'privacyPage' => $privacyPage,
			'prompt' => $prompt,
		]);
	}

	/**
	 * Ermittelt den Sprach-Suffix für mehrsprachige Felder, falls Sprachen aktiviert sind
	 * Gibt einen Suffix im Format '__ID' zurück oder einen leeren String für die Standardsprache
	 *
	 * @return string Der Sprach-Suffix.
	 */
	private function getLanguageSuffix()
	{
		if (!$this->languages) {
			return '';
		}

		$userLanguage = $this->user->language;

		return $userLanguage->isDefault() ? '' : '__' . $userLanguage->id;
	}

	/**
	 * Synchronisiert die Cookie-Kategorien mit dem Options-Feld.
	 * Behält IDs stabil und erzwingt die Sortierung (Kategorie -> Alphabetisch).
	 *
	 * @param array $data Aktuelle Modul-Daten vom Hook
	 * @return void
	 */
	private function updateCookieCategoryField($data = [])
	{
		$field = $this->fields->get('cookiemonster_category');
		if (!($field instanceof Field && $field->type instanceof FieldtypeOptions)) {
			return;
		}

		$data = !empty($data) ? $data : $this->data;
		$lang = $this->getLanguageSuffix();
		$baseCategories = $this->_getBaseCategories();

		$wanted = [];
		$seenTitles = [];

		// Soll-Zustand aufbauen und Titel-Eindeutigkeit sicherstellen
		foreach ($baseCategories as $key => $cat) {
			if ($key !== 'essential' && empty($data["{$key}_enabled"])) {
				continue;
			}

			$mainTitle = (string) ($data["{$key}_title{$lang}"] ?? '') ?: $cat['title'];

			$safeMain = in_array($mainTitle, $seenTitles) ? "$mainTitle ($key)" : $mainTitle;
			$seenTitles[] = $safeMain;
			$wanted[$key] = $safeMain;

			$cookieVal = (string) ($data["{$key}_cookies{$lang}"] ?? '');
			if ($cookieVal) {
				$subs = $this->extractSubCategories($cookieVal, $key);

				asort($subs);  // Kinder alphabetisch sortieren

				foreach ($subs as $subKey => $subTitle) {
					$fullKey = "$key-$subKey";
					$fullTitle = "$mainTitle: $subTitle";
					$safeSub = in_array($fullTitle, $seenTitles) ? "$fullTitle ($fullKey)" : $fullTitle;
					$seenTitles[] = $safeSub;
					$wanted[$fullKey] = $safeSub;
				}
			}
		}

		/** @var SelectableOptionArray $currentOptions */
		$currentOptions = $field->type->getOptions($field);
		$toDelete = new SelectableOptionArray();
		$toAdd = new SelectableOptionArray();

		// Differenz für native API-Methoden ermitteln
		foreach ($currentOptions as $opt) {
			if (!isset($wanted[$opt->value])) {
				$toDelete->add($opt);
			}
		}
		foreach ($wanted as $key => $title) {
			if (!$currentOptions->get("value=$key")) {
				$opt = new SelectableOption();
				$opt->value = $opt->name = $key;
				$opt->title = $title;
				$toAdd->add($opt);
			}
		}

		// Native API für stabile Datenbank-Änderungen
		if ($toDelete->count()) {
			$field->type->deleteOptions($field, $toDelete);
		}
		if ($toAdd->count()) {
			$field->type->addOptions($field, $toAdd);
		}

		// Manuelle SQL-Updates für Sortierung und Titel (umgeht Manager-Kollisionen)
		$db = $this->wire('database');
		$table = 'fieldtype_options';
		$sortIndex = 1;

		foreach ($wanted as $key => $title) {
			$query = $db->prepare("UPDATE `$table` SET `sort` = :sort, `title` = :title WHERE `fields_id` = :fid AND `value` = :val");
			$query->execute([
				':sort' => $sortIndex++,
				':title' => $title,
				':fid' => $field->id,
				':val' => $key
			]);
		}

		// Feld-Metadaten säubern und speichern
		$field->set('_options', '');
		$this->fields->save($field);
	}

	/**
	 * Extrahiert Subkategorien aus einer Cookie-Definition.
	 * Sucht nach Gruppen-Definitionen (---id|Titel|Beschreibung|Hinweis) und gibt sie zurück.
	 *
	 * @param string $cookieText Der Cookie-Text aus der Konfiguration
	 * @param string $parentKey Der Schlüssel der übergeordneten Kategorie
	 * @return array<string, string> Array von Subkategorie-Keys und Titeln
	 */
	private function extractSubCategories($cookieText, $parentKey)
	{
		$subCategories = [];
		$lines = \explode("\n", $cookieText);

		foreach ($lines as $line) {
			$line = \trim($line);

			// Prüfe auf Gruppen-Definition: "---id|Titel|Beschreibung"
			if (\strpos($line, '---') === 0) {
				$groupParts = \explode('|', \substr($line, 3));
				$groupId = \trim($groupParts[0] ?? '');
				$groupTitle = \trim($groupParts[1] ?? '');

				// Nur hinzufügen, wenn ID und Titel vorhanden sind
				if (!empty($groupId) && !empty($groupTitle)) {
					// Normalisiere die ID (entferne Parent-Prefix falls vorhanden)
					$normalizedId = \str_replace("{$parentKey}-", '', $groupId);
					$subCategories[$normalizedId] = $groupTitle;
				}
			}
		}

		return $subCategories;
	}

	/**
	 * Rendert eine Template-Datei aus dem 'templates'-Verzeichnis des Moduls
	 *
	 * @param string $filename Der Name der Template-Datei
	 * @param array<string, mixed> $data Ein assoziatives Array von Daten, das an das Template übergeben wird
	 * @return string Der gerenderte Template-Inhalt oder ein leerer String
	 */
	private function renderTemplate($filename, $data = [])
	{
		$templatePath = $this->config->paths->{$this} . "templates/{$filename}";

		if (!@$this->files->exists($templatePath)) {
			$this->error($this->_('Template nicht gefunden') . ": {$filename}");

			return '';
		}

		return $this->files->render($templatePath, $data);
	}

	/**
	 * Erlaubt das Hinzufügen von JS in der ProcessWire Modulconfig
	 * 
	 * @param InputfieldWrapper $inputfields 
	 * @return InputfieldWrapper 
	 */
	public function getModuleConfigInputfields($inputfields)
	{
		wire()->modules->get('JqueryWireTabs');
		wire()->config->scripts->add(wire()->urls->get('CookieMonster') . '/assets/CookieMonster.config.js');

		return $inputfields;
	}
}
