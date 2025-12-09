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
	private function _getBaseCategories(): array
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
	public function ___install(): void
	{
		if (!$this->permissions->get('manage-cookie-monster')) {
			$permission = $this->permissions->add('manage-cookie-monster');
			$permission->title = $this->_('CookieMonster-Einstellungen verwalten');
			$permission->save();
		}

		$fieldName = 'cookiemonster_category';
		$field = $this->fields->get($fieldName);

		if (!$field || !$field->id) {
			$field = new Field();
			$field->type = $this->fieldtypes->get('FieldtypeOptions');
			$field->name = $fieldName;
			$field->label = $this->_('Zugehörige Cookie-Kategorie');
			$field->description = $this->_('Wählen Sie die Cookie-Kategorie, der dieser Inhalt zugeordnet ist. Der Inhalt wird nur angezeigt, wenn der Besucher dieser Kategorie zugestimmt hat (Ausnahme: "Notwendig" ist immer erlaubt).');
			$field->inputfield = 'InputfieldSelect';

			$this->fields->save($field);
			$field = $this->fields->get($fieldName);
			if (!$field || !$field->id) {
				$this->error("Kritischer Fehler: Konnte Feld '{$fieldName}' nach der Erstellung nicht erneut laden. Installation abgebrochen.");

				return;
			}
		}

		$baseCategories = $this->_getBaseCategories();
		$optionsStringLines = [];
		$optionsArray = new SelectableOptionArray();

		$index = 1;

		if (!empty($baseCategories)) {
			foreach ($baseCategories as $key => $category) {
				$title = $category['title'];

				$option = new SelectableOption();
				$option->value = $key;
				$option->name = $key;
				$option->title = $title;
				$optionsArray->add($option);

				$optionsStringLines[] = "{$index}={$key}|{$title}";
				$index++;
			}

			$field->type->setOptions($field, $optionsArray);

			$field->set('_options', implode("\n", $optionsStringLines));
		}

		$this->fields->save($field);
	}

	/**
	 * Deinstalliert das Modul und entfernt zugehörige Berechtigungen und Felder
	 *
	 * @return void
	 */
	public function ___uninstall(): void
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
	public function init(): void
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
	}

	private function checkCookieExists(): bool
	{
		return isset($_COOKIE['cmnstr']) && !empty($_COOKIE['cmnstr']);
	}

	/**
	 * Verarbeitet die gespeicherte Cookie-Zustimmung aus dem 'cmnstr'-Cookie
	 *
	 * @return void
	 */
	private function processCookieConsent(): void
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
			$cookieValues = \json_decode($_COOKIE['cmnstr'], associative: true, flags: \JSON_THROW_ON_ERROR);

			// Iteriere über alle konfigurierbaren Kategorien (außer 'essential')
			foreach ($baseCategories as $key => $data) {
				if ($key === 'essential') {
					continue;  // Essential ist bereits behandelt
				}

				if (($cookieValues[$key] ?? false) === true) {
					$this->consentedCategories[] = $key;

					// Google Consent Mode Zustände basierend auf vom Benutzer erteilten Kategorien aktualisieren
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

			if (\in_array('statistics', $this->consentedCategories, true)) {
				$this->allowTracking = true;
			}
		} catch (\JsonException) {
			$this->error($this->_('Malformed CookieMonster consent cookie detected. Resetting to default.'));
		}
	}

	/**
	 * Prüft, ob eine spezifische Cookie-Kategorie vom Benutzer zugestimmt wurde
	 *
	 * @param string|SelectableOptionArray $categoryKey Der Schlüssel der Cookie-Kategorie (z.B. 'essential', 'statistics')
	 * @return bool `true`, wenn die Kategorie freigeschaltet ist, sonst `false`
	 */
	public function isUnlocked(string|SelectableOptionArray|null $categoryKey): bool
	{
		if ($categoryKey instanceof SelectableOptionArray) {
			$categoryKey = $categoryKey->value;
		}

		return \in_array($categoryKey, $this->consentedCategories, true);
	}

	/**
	 * Gibt ein Array aller Cookie-Kategorien zurück, denen der Benutzer zugestimmt hat
	 *
	 * @return string[] Array der zugestimmten Cookie-Kategorien-Schlüssel
	 */
	public function getUnlockedCategories(): array
	{
		return $this->consentedCategories;
	}

	/**
	 * Öffentlicher Getter für den Tracking-Zustimmungsstatus
	 *
	 * @return bool `true`, wenn Tracking erlaubt ist, sonst `false`
	 */
	public function allowTracking(): bool
	{
		return $this->allowTracking;
	}

	/**
	 * Öffentlicher Getter für die Google Consent Mode v2 Zustände
	 *
	 * @return array<string, string> Die aktuellen Google Consent Mode v2 Zustände.
	 */
	public function getGoogleConsentStates(): array
	{
		return $this->googleConsentStates;
	}

	/**
	 * Hook: Fügt den Cookie-Banner zum gerenderten Seiten-Output hinzu
	 *
	 * @param HookEvent $event Das ProcessWire `HookEvent`-Objekt
	 * @return void
	 */
	public function addCookieBanner(HookEvent $event): void
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
	private function renderBanner(): string
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
			'module' => $this,
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
	private function buildCategoriesData(): array
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
	 * Hook: Fügt den Google Analytics Tracking-Code zum gerenderten Seiten-Output hinzu
	 *
	 * @param HookEvent.
	 * @return void
	 */
	public function addTrackingCode(HookEvent $event): void
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
	public function sendSecurityHeaders(HookEvent $event): void
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
	private function sendReferrerPolicyHeader(): void
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
	private function sendContentTypeOptionsHeader(): void
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
	private function sendHSTSHeader(): void
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
	private function sendCSPHeader(HookEvent $event): void
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

		if (\str_contains($policy, 'nonce-proxy')) {
			$policy = \str_replace('nonce-proxy', "nonce-{$this->cspNonce}", $policy);

			$output = (string) $event->return;
			$output = \preg_replace('#nonce=["\']proxy["\']#', "nonce=\"{$this->cspNonce}\"", $output);
			$event->return = $output;
		}

		if ($policy) {
			$cspParts[] = $policy;
		}

		if (!\str_contains($policy, 'frame-ancestors')) {
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
	private function getFrameAncestorsValue(): string
	{
		$policy = (string) $this->get('framing_policy');

		return match ($policy) {
			'self' => "'self'",
			'custom' => (string) $this->get('framing_custom_origins'),
			default => "'none'",
		};
	}

	/**
	 * Sendet `Cross-Origin-Opener-Policy` (COOP), `Cross-Origin-Embedder-Policy` (COEP),
	 * und `Cross-Origin-Resource-Policy` (CORP) HTTP-Header basierend auf Moduleinstellungen
	 *
	 * @return void
	 */
	private function sendCrossOriginHeaders(): void
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
	private function sendPermissionsPolicyHeader(): void
	{
		if ((bool) $this->get('permissions_policy_enabled') && $policy = $this->get('permissions_policy')) {
			\header("Permissions-Policy: {$policy}");
		}
	}

	/**
	 * Rendert eine HTML-Tabelle von Cookies aus einem formatierten String-Feld
	 *
	 * @param string $cookieField Cookie-Daten
	 * @return ?string Die gerenderte HTML-Tabelle oder leerer String
	 */
	public function renderCookieTable(?string $cookieField = ''): string
	{
		return $this->renderCookieTemplate($cookieField, 'table.php');
	}

	/**
	 * Rendert eine HTML-Liste von Cookies aus einem formatierten String-Feld
	 *
	 * @param string $cookieField Cookie-Daten
	 * @return ?string Die gerenderte HTML-Tabelle oder leerer String
	 */
	public function renderCookieList(?string $cookieField = ''): string
	{
		return $this->renderCookieTemplate($cookieField, 'list.php');
	}

	/**
	 * Rendert ein HTML-Template mit Cookies aus einem formatierten String-Feld
	 *
	 * @param string $cookieField Cookie-Daten
	 * @param string $template Template-Pfad
	 * @return ?string Die gerenderte HTML-Tabelle oder leerer String
	 */
	private function renderCookieTemplate(string $cookieField = '', string $template): string
	{
		// Wenn kein spezifisches Cookie-Feld bereitgestellt wird, sammeln wir alle Cookies
		if (empty($cookieField)) {
			$allCategoryCookies = [];
			$categoriesData = $this->buildCategoriesData();

			foreach ($categoriesData as $category) {
				if (!empty($category['cookies'])) {
					$allCategoryCookies[] = "<strong>{$category['title']}</strong>";
					$allCategoryCookies[] = $category['cookies'];
				}
			}
			// Kombiniere alle Cookie-Strings zu einem einzigen
			$cookieField = \implode("\n", $allCategoryCookies);
		}

		// Wenn weiterhin keine Cookies vorhanden sind, leeren String zurückgeben
		if (empty($cookieField)) {
			return '';
		}

		$cookiesData = [];
		$headers = [
			$this->_('Name'),
			$this->_('Anbieter'),
			$this->_('Zweck'),
			$this->_('Dauer'),
		];

		$rows = \explode("\n", $cookieField);

		foreach ($rows as $row) {
			$row = \trim($row);
			if (empty($row)) {
				continue;
			}

			$columns = \array_map(fn($row) => trim($row), \explode('|', $row));

			$cookie = \array_pad($columns, \count($headers), '');
			$cookiesData[] = \array_combine($headers, \array_slice($cookie, 0, \count($headers)));
		}

		return $this->renderTemplate($template, [
			'headers' => $headers,
			'cookies' => $cookiesData,
		]);
	}

	/**
	 * Maskiert externen Inhalt bis zur Zustimmung
	 *
	 * @param string $html Der zu maskierende HTML-Inhalt
	 * @param string|SelectableOptionArray|null $category Cookie-Kategorie (z.B. 'external', 'marketing')
	 * @return string Maskierter oder normaler Inhalt
	 */
	public function maskContent(
		string $html,
		string|SelectableOptionArray|null $category,
	): string {
		if ($this->isUnlocked($category)) {
			return $html;
		}

		$lang = $this->getLanguageSuffix();
		$baseCategories = $this->_getBaseCategories();
		$baseTitle = $baseCategories[$category]['title'] ?? $category;
		$categoryTitle = (string) $this->get("{$category}_title{$lang}") ?: $baseTitle;

		$promptKey = "{$category}_prompt{$lang}";
		$prompt = (string) $this->get($promptKey) ?: $this->_('Dieser Inhalt benötigt deine Zustimmung zu {category}');
		$prompt = \str_replace('{category}', "<strong>{$categoryTitle}</strong>", $prompt);

		return $this->renderTemplate('mask.php', [
			'html' => $html,
			'category' => $category,
			'buttonEdit' => (string) $this->get("buttontext_edit{$lang}"),
			'buttonAccept' => (string) $this->get("buttontext_accept{$lang}"),
			'prompt' => $prompt,
		]);
	}

	/**
	 * Ermittelt den Sprach-Suffix für mehrsprachige Felder, falls Sprachen aktiviert sind
	 * Gibt einen Suffix im Format '__ID' zurück oder einen leeren String für die Standardsprache
	 *
	 * @return string Der Sprach-Suffix.
	 */
	private function getLanguageSuffix(): string
	{
		if (!$this->languages) {
			return '';
		}

		$userLanguage = $this->user->language;

		return $userLanguage->isDefault() ? '' : '__' . $userLanguage->id;
	}

	/**
	 * Rendert eine Template-Datei aus dem 'templates'-Verzeichnis des Moduls
	 *
	 * @param string $filename Der Name der Template-Datei
	 * @param array<string, mixed> $data Ein assoziatives Array von Daten, das an das Template übergeben wird
	 * @return string Der gerenderte Template-Inhalt oder ein leerer String
	 */
	private function renderTemplate(string $filename, array $data = []): string
	{
		$templatePath = $this->config->paths->{$this} . "templates/{$filename}";

		if (!\file_exists($templatePath)) {
			$this->error($this->_('Template nicht gefunden') . ": {$filename}");

			return '';
		}

		return $this->files->render($templatePath, $data);
	}

	public function getModuleConfigInputfields(InputfieldWrapper $inputfields): InputfieldWrapper
	{
		wire()->modules->get('JqueryWireTabs');
		wire()->config->scripts->add(wire()->urls->get('CookieMonster') . '/assets/CookieMonster.config.js');

		return $inputfields;
	}
}
