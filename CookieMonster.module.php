<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @method void upgrade(int|string $fromVersion, int|string $toVersion)
 */
class CookieMonster extends WireData implements Module, ConfigurableModule
{
	private const COOKIE_NAME = 'cmnstr';

	public const CONSENT_LOG = 'cookiemonster-consent';

	public const CONFIG_LOG = 'cookiemonster-config';

	private const CONFIG_SNAPSHOT_KEYS = [
		'essential_cookies',
		'functional_cookies',
		'statistics_cookies',
		'marketing_cookies',
		'external_cookies',
		'essential_title',
		'functional_title',
		'statistics_title',
		'marketing_title',
		'external_title',
		'essential_description',
		'functional_description',
		'statistics_description',
		'marketing_description',
		'external_description',
		'functional_enabled',
		'statistics_enabled',
		'marketing_enabled',
		'external_enabled',
		'is_active',
		'infotext',
		'titletext',
		'infolabel',
		'infodetails',
		'buttontext_edit',
		'buttontext_confirm',
		'buttontext_decline',
		'buttontext_accept',
		'mask_prompt',
		'mask_buttontext_required',
		'mask_buttontext_all',
		'mask_buttontext_once',
		'mask_info',
		'missing_service_mode',
		'imprint_page',
		'privacy_page',
		'respect_gpc',
		'gpc_categories',
		'gpc_notice',
		'respect_dnt',
		'dnt_notice',
		'ga_property_id',
		'ga_cookie_service',
		'gtm_id',
		'gtm_cookie_service',
		'matomo_enabled',
		'matomo_url',
		'matomo_siteid',
		'matomo_cookieless',
		'matomo_sharetracking',
		'matomo_sharedomain',
		'matomo_documenttitle',
		'matomo_cookie_service',
		'meta_pixel_id',
		'meta_pixel_event',
		'meta_pixel_cookie_service',
		'linkedin_insight_id',
		'linkedin_insight_cookie_service',
	];

	private const CONFIG_HASH_LENGTH = 12;

	/**
	 * Danach wird der Snapshot beim Protokollieren einer Einwilligung erneut geschrieben (Lücke durch Pruning).
	 */
	private const CONFIG_SNAPSHOT_REFRESH = 30 * 24 * 60 * 60;

	private const CONFIG_DIFF_VALUE_LENGTH = 500;

	private const CONFIG_HISTORY_LIMIT = 50;

	public const LOG = 'cookiemonster';

	public const UPGRADE_LOG = 'cookiemonster-upgrade';

	private const UPGRADE_LOCK = 'cookiemonster-upgrade.lock';

	private const MIGRATION_INFO_LENGTH = 1000;

	private const CATEGORY_CONSENT = [
		'essential' => ['security_storage'],
		'functional' => ['functionality_storage', 'personalization_storage'],
		'statistics' => ['analytics_storage'],
		'marketing' => ['ad_storage', 'ad_user_data', 'ad_personalization'],
		'external' => ['personalization_storage'],
	];

	public const CONSENT_PATH = '/_cmnstr/consent';

	private const STATS_CHUNK = 65536;

	public const REPORT_PATH = '/_cmnstr/report';

	public const REPORT_LOG = 'cookiemonster-report';

	public const DEFAULT_CSP_POLICY = "default-src 'self' 'nonce-proxy' 'strict-dynamic'; style-src 'self' 'nonce-proxy' https:; style-src-attr 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data: https:; media-src 'self' https:; frame-src https:; connect-src 'self' https:; object-src 'none'; base-uri 'self'; form-action 'self'; upgrade-insecure-requests";

	private const LEGACY_CSP_POLICY = "default-src 'self' 'nonce-proxy' 'strict-dynamic'; style-src-attr 'unsafe-inline'; img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'; upgrade-insecure-requests";

	private const LEGACY_CSP_45 = "default-src 'self' 'nonce-proxy' 'strict-dynamic'; style-src-attr 'unsafe-inline'; img-src 'self' data:; form-action 'self'; upgrade-insecure-requests; block-all-mixed-content;";

	private const REFERRER_POLICIES = [
		'no-referrer',
		'no-referrer-when-downgrade',
		'origin',
		'origin-when-cross-origin',
		'same-origin',
		'strict-origin',
		'strict-origin-when-cross-origin',
		'unsafe-url',
	];

	private const COOP_POLICIES = ['', 'unsafe-none', 'same-origin-allow-popups', 'same-origin'];

	private const COEP_POLICIES = ['', 'unsafe-none', 'require-corp', 'credentialless'];

	private const CORP_POLICIES = ['', 'same-site', 'same-origin', 'cross-origin'];

	private const FRAMING_POLICIES = ['none', 'self', 'custom'];

	private const FRAME_SOURCE_PATTERN = '#^https?://(\*\.)?[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?(:(\d{1,5}|\*))?$#';

	private const REPORT_MAX_BYTES = 16384;

	private const REPORT_MAX_ITEMS = 10;

	private const REPORT_WINDOW = 3600;

	private const REPORT_WINDOW_LIMIT = 200;

	private const REPORT_STATE = 'cookiemonster-report-limit.json';

	private const REPORT_LOG_DAYS = 30;

	private const REPORT_FIELD_LENGTH = 200;

	/**
	 * Aufbewahrung nach Ablauf der Einwilligung in Tagen; Pruning läuft ab Ablauf des Cookies plus diese Frist.
	 */
	private const CONSENT_LOG_DEFAULT_DAYS = 3 * 365;

	private const CONSENT_ID_BEGIN = '{{cmnstr-id:begin}}';

	private const CONSENT_ID_END = '{{cmnstr-id:end}}';

	private const CONSENT_ID_VALUE = '{{cmnstr-id:value}}';

	private const CONSENT_LOG_OPTIONS = ['180', '365', '730', '1095', '1460', '1825'];

	private const COOKIE_NAME_PATTERN = '/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/';

	private const SETTINGS_PARAM = 'cmnstr-settings';

	private const COOKIE_LIFETIME = 180 * 24 * 60 * 60;

	private const COOKIE_REFRESH_INTERVAL = 24 * 60 * 60;

	private const EXCLUDED_TEMPLATES = ['admin', 'form-builder'];

	private const BANNER_REGION_ID = 'cmnstr-banner';

	private const BANNER_ROOT = '<div id="cmnstr">';

	public const SERVICE_FIELD_NAME = 'cmnstr_service';

	public const SERVICE_FIELDTYPE = 'FieldtypeCookieService';

	private const TAG_LABELS = [
		'ga' => 'Google Analytics',
		'gtm' => 'Google Tag Manager',
		'linkedin_insight' => 'LinkedIn Insight',
		'matomo' => 'Matomo',
		'meta_pixel' => 'Meta Pixel',
	];

	/**
	 * Kategorie, auf die ein Tag ohne ausgewählten Dienst zurückfällt; nicht aufgeführte Tags nutzen `statistics`.
	 */
	private const TAG_FALLBACK_CATEGORIES = [
		'linkedin_insight' => 'marketing',
		'meta_pixel' => 'marketing',
	];

	public const ALL_SERVICES = 'all';

	public const MATCH_ANY = 'any';

	private const LEGACY_FIELD_NAME = 'cookiemonster_category';

	private const LEGACY_PERMISSION = 'manage-cookie-monster';

	private string $cspNonce = '';

	/**
	 * @var array<string, string>
	 */
	private array $upgradeNotices = [];

	private bool $migratedSettings = false;

	/**
	 * @var string[] z.B. ['essential', 'external-youtube']
	 */
	private array $consentedCategories = [];

	private bool $allowTracking = false;

	/**
	 * @var array<string, string>
	 */
	private array $googleConsentStates = [];

	/**
	 * @var array<string, mixed>|null
	 */
	private ?array $consent = null;

	/**
	 * @var array<string, bool> z.B. ["statistics-ga" => true]
	 */
	private array $flatConsent = [];

	/**
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private array $categoryGroups = [];

	/**
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private array $localizedGroups = [];

	/**
	 * @var array<string, string[]>|null
	 */
	private ?array $offeredServices = null;

	/**
	 * @var array<int, array{service: ?string, name: string}>|null
	 */
	private ?array $cookieEntries = null;

	private bool $skipConsent = false;

	private bool $gpc = false;

	private bool $dnt = false;

	private int $maskCount = 0;

	/**
	 * @var array<string, true>
	 */
	private array $missingKeyWarned = [];

	private ?string $moduleVersion = null;

	private bool $securityHeadersSent = false;

	private bool $consentLoaded = false;

	private bool $syncingConfig = false;

	/**
	 * @var array<int, array<string, string>>
	 */
	private array $texts = [];

	/**
	 * @return array<string, string>
	 */
	private function getDefaultCategoryTitles(): array
	{
		return [
			'essential' => $this->text('category_essential'),
			'functional' => $this->text('category_functional'),
			'statistics' => $this->text('category_statistics'),
			'marketing' => $this->text('category_marketing'),
			'external' => $this->text('category_external'),
		];
	}

	public function ___install(): void
	{
		$this->installServiceField();
	}

	/**
	 * Erst das Feld löschen: ProcessWire verweigert sonst die Deinstallation des Fieldtypes.
	 */
	public function ___uninstall(): void
	{
		$field = $this->fields->get(self::SERVICE_FIELD_NAME);

		if ($field instanceof Field) {
			foreach ($field->getFieldgroups() as $fieldgroup) {
				/** @var Fieldgroup $fieldgroup */
				$fieldgroup->remove($field);
				$fieldgroup->save();
				$this->message(\sprintf($this->_('Feld „%1$s“ aus Fieldgroup „%2$s“ entfernt.'), $field->name, $fieldgroup->name));
			}

			$this->fields->delete($field);
			$this->message(\sprintf($this->_('Feld „%s“ gelöscht.'), self::SERVICE_FIELD_NAME));
		}

		if ($this->modules->isInstalled(self::SERVICE_FIELDTYPE)) {
			$this->modules->uninstall(self::SERVICE_FIELDTYPE);
		}
	}

	/**
	 * Setzt bei einem Versionswechsel die Consent-Epoche (`consent_epoch`); der Wiederholungslauf aus ready() mit gleicher Version ändert sie nicht.
	 *
	 * @param int|string $fromVersion
	 * @param int|string $toVersion
	 */
	public function ___upgrade($fromVersion, $toVersion): void
	{
		$lock = null;

		if (!$this->acquireUpgradeLock($lock)) {
			return;
		}

		try {
			$failures = [];

			// Marker vorab: Bricht der Lauf hart ab (Zeitlimit, Speicher), bleibt er stehen und ready() wiederholt die Migration
			$announce = (int) $this->get('migration_pending') > 0;

			if (!$announce) {
				$this->setMigrationMarker($this->_('Der Upgrade-Lauf wurde nicht abgeschlossen (Zeitlimit oder Speicher).'));
			}

			if ((string) $fromVersion !== (string) $toVersion) {
				$this->setConsentEpoch();
			}

			// 'installs' greift nur bei einer Neuinstallation
			$steps = [
				'modules' => $this->installUpgradeModules(...),
				'service field' => $this->installServiceField(...),
				'legacy config' => $this->migrateLegacyConfig(...),
				'category field' => $this->migrateLegacyCategoryField(...),
				'tag settings' => $this->migrateLegacyTagSettings(...),
				'upgrade notices' => $this->collectUpgradeNotices(...),
				'csp policy' => $this->upgradeCspPolicy(...),
				'permission' => $this->removeLegacyPermission(...),
				'config history' => $this->syncConfigHistory(...),
			];

			foreach ($steps as $label => $step) {
				$failures = \array_merge($failures, $this->runUpgradeStep($label, $step));
			}

			$this->resetMemo();
			$this->storeUpgradeNotices();
			$this->updateMigrationMarker($failures, $announce);
		} catch (\Throwable $e) {
			$this->log->save(self::UPGRADE_LOG, 'Upgrade aborted: ' . $e->getMessage());
		} finally {
			$this->releaseUpgradeLock($lock);
		}
	}

	private function setConsentEpoch(): void
	{
		$data = $this->modules->getConfig($this);
		$data['consent_epoch'] = \time();
		$this->modules->saveConfig($this, $data);
		$this->set('consent_epoch', $data['consent_epoch']);
	}

	/**
	 * @param resource|null $handle null = keine Sperrdatei möglich, der Lauf geht ungesperrt weiter
	 * @return bool false = ein anderer Prozess führt das Upgrade aus
	 */
	private function acquireUpgradeLock(mixed &$handle): bool
	{
		$handle = null;
		$dir = $this->config->paths->logs;

		if (!\is_dir($dir)) {
			$this->files->mkdir($dir, true);
		}

		$file = @\fopen($dir . self::UPGRADE_LOCK, 'c');

		if ($file === false) {
			return true;
		}

		if (!\flock($file, \LOCK_EX | \LOCK_NB)) {
			\fclose($file);

			return false;
		}

		$handle = $file;

		return true;
	}

	/**
	 * @param resource|null $handle
	 */
	private function releaseUpgradeLock(mixed $handle): void
	{
		if ($handle !== null) {
			\flock($handle, \LOCK_UN);
			\fclose($handle);
		}
	}

	/**
	 * @return string[] Fehlermeldungen
	 */
	private function runUpgradeStep(string $label, callable $step): array
	{
		try {
			$result = $step();

			return \is_array($result) ? $result : [];
		} catch (\Throwable $e) {
			return ["{$label}: {$e->getMessage()}"];
		}
	}

	private function setMigrationMarker(string $info): void
	{
		$data = $this->modules->getConfig($this);
		$data['migration_pending'] = \time();
		$data['migration_pending_info'] = \substr($info, 0, self::MIGRATION_INFO_LENGTH);
		$this->modules->saveConfig($this, $data);
		$this->setArray($data);
	}

	/**
	 * @param string[] $failures
	 * @param bool $announce false = der Marker stammt aus diesem Lauf, ein Erfolg bleibt ohne Meldung
	 */
	private function updateMigrationMarker(array $failures, bool $announce = true): void
	{
		$data = $this->modules->getConfig($this);
		$pending = (int) ($data['migration_pending'] ?? 0) > 0;

		if ($failures === []) {
			if (!$pending) {
				return;
			}

			unset($data['migration_pending'], $data['migration_pending_info']);
			$this->modules->saveConfig($this, $data);
			$this->remove('migration_pending');
			$this->remove('migration_pending_info');

			if ($announce) {
				$this->log->save(self::UPGRADE_LOG, 'Migration completed');
				$this->message($this->_('CookieMonster: Migration abgeschlossen.'));
			}

			return;
		}

		$info = \substr(\implode("\n", $failures), 0, self::MIGRATION_INFO_LENGTH);

		if ($pending && ($data['migration_pending_info'] ?? '') === $info) {
			return;
		}

		foreach ($failures as $failure) {
			$this->log->save(self::UPGRADE_LOG, $failure);
		}

		$data['migration_pending'] = \time();
		$data['migration_pending_info'] = $info;
		$this->modules->saveConfig($this, $data);
		$this->setArray($data);
	}

	private function installUpgradeModules(): void
	{
		foreach (['LazyCron', 'InputfieldCookieService', 'TextformatterCookieTable'] as $moduleName) {
			$this->installModuleOnce($moduleName);
		}
	}

	/**
	 * Files added by an update are unknown to ProcessWire until the module cache is refreshed, which the upgrade can run into before anyone clicks "Refresh".
	 */
	private function installModuleOnce(string $moduleName): void
	{
		if ($this->modules->isInstalled($moduleName)) {
			return;
		}

		if (!\array_key_exists($moduleName, $this->modules->getInstallable())) {
			$this->modules->refresh();
		}

		$this->modules->install($moduleName);
	}

	/**
	 * Ersetzt nur den unveränderten alten Standard (Leerraum ignoriert), nie eine angepasste Richtlinie.
	 */
	private function upgradeCspPolicy(): void
	{
		$data = $this->modules->getConfig($this);
		$stored = $this->toHeaderValue((string) ($data['csp_policy'] ?? ''));

		$legacy = \array_map(
			fn(string $policy): string => \rtrim($this->toHeaderValue($policy), '; '),
			[self::LEGACY_CSP_POLICY, self::LEGACY_CSP_45]
		);

		if (!\in_array(\rtrim($stored, '; '), $legacy, true)) {
			return;
		}

		$data['csp_policy'] = self::DEFAULT_CSP_POLICY;
		$this->modules->saveConfig($this, $data);
		$this->setArray($data);
		$this->message($this->_('Standard der Content Security Policy aktualisiert: externe Inhalte und Tracker sind jetzt erlaubt.'));
	}

	private function resetMemo(): void
	{
		$this->categoryGroups = [];
		$this->localizedGroups = [];
		$this->offeredServices = null;
		$this->cookieEntries = null;
	}

	public function hookConfigSaved(HookEvent $event): void
	{
		$class = $event->arguments(0);
		$name = $class instanceof Module ? $class->className() : (string) $class;

		if ($name === $this->className() && !$this->syncingConfig) {
			$this->resetMemo();
			$this->syncConfigHistory();
		}
	}

	private function installServiceField(): void
	{
		$this->installModuleOnce(self::SERVICE_FIELDTYPE);

		if ($this->fields->get(self::SERVICE_FIELD_NAME) instanceof Field) {
			return;
		}

		/** @var Field $field */
		$field = $this->wire(new Field());
		$field->type = $this->modules->get(self::SERVICE_FIELDTYPE);
		$field->name = self::SERVICE_FIELD_NAME;
		$field->label = $this->_('Zugehörige Cookie-Kategorie');
		$field->description = $this->_('Wählen Sie die Cookie-Kategorie bzw. den Dienst, dem dieser Inhalt zugeordnet ist.');
		$field->tags = 'cookiemonster';
		$this->fields->save($field);

		$this->message(\sprintf($this->_('Feld „%s“ angelegt.'), self::SERVICE_FIELD_NAME));
	}

	/**
	 * Schlägt eine Seite fehl, bleibt das alte Feld erhalten. Übernommen wird der Feldkontext je Template
	 *
	 * @return string[] Fehlermeldungen
	 */
	private function migrateLegacyCategoryField(): array
	{
		$legacyField = $this->fields->get(self::LEGACY_FIELD_NAME);

		if (!$legacyField instanceof Field) {
			return [];
		}

		$serviceField = $this->fields->get(self::SERVICE_FIELD_NAME);

		if (!$serviceField instanceof Field) {
			return [\sprintf('Field %s is missing', self::SERVICE_FIELD_NAME)];
		}

		foreach ($legacyField->getFieldgroups() as $fieldgroup) {
			/** @var Fieldgroup $fieldgroup */
			if (!$fieldgroup->hasField($serviceField)) {
				$fieldgroup->insertAfter($serviceField, $legacyField);
				$fieldgroup->save();

				// Nur allgemeine Einstellungen; der alte Kontext stammt von einem Options-Feld
				$context = \array_intersect_key(
					$fieldgroup->getFieldContextArray($legacyField->id),
					\array_flip(['label', 'description', 'notes', 'collapsed', 'columnWidth', 'showIf'])
				);

				if (!empty($context) && empty($fieldgroup->getFieldContextArray($serviceField->id))) {
					$fieldgroup->setFieldContextArray($serviceField->id, $context);
					$fieldgroup->saveContext();
				}
			}
		}

		$this->addServiceFieldToMatrixTypes($legacyField, $serviceField);

		$migrated = 0;
		$failures = [];
		$selector = self::LEGACY_FIELD_NAME . '.count>0, include=all, check_access=0';

		foreach ($this->pages->findMany($selector) as $page) {
			// Bereits befüllt: Redakteurswerte und frühere Läufe nicht überschreiben
			if ((string) $page->getUnformatted($serviceField->name) !== '') {
				continue;
			}

			$keys = $this->getLegacyServiceKeys($page->getUnformatted(self::LEGACY_FIELD_NAME));

			if ($keys === []) {
				continue;
			}

			try {
				$page->of(false);
				$page->set($serviceField->name, $keys);

				if ($page->save($serviceField->name, ['quiet' => true]) === false) {
					$failures[] = "page {$page->id}: save failed";

					continue;
				}

				// save() meldet auch dann Erfolg, wenn die Seite das Feld nicht führt (z.B. Matrix-Typ)
				if ($this->readStoredServiceValue($serviceField, (int) $page->id) !== \implode('|', $keys)) {
					$failures[] = "page {$page->id}: value not persisted";

					continue;
				}

				$migrated++;
			} catch (\Throwable $e) {
				$failures[] = "page {$page->id}: {$e->getMessage()}";
			}
		}

		if ($migrated > 0) {
			$this->log->save(self::UPGRADE_LOG, "{$migrated} pages migrated");
			$this->message(\sprintf($this->_('%1$d Seiten von „%2$s“ nach „%3$s“ übertragen.'), $migrated, $legacyField->name, $serviceField->name));
		}

		$unmigrated = $this->countUnmigratedLegacyPages($legacyField, $serviceField);

		if ($unmigrated > 0) {
			$failures[] = "{$unmigrated} pages still without a stored value";
		}

		if ($failures !== []) {
			return $failures;
		}

		foreach ($legacyField->getFieldgroups() as $fieldgroup) {
			/** @var Fieldgroup $fieldgroup */
			$fieldgroup->remove($legacyField);
			$fieldgroup->save();
		}

		$this->fields->delete($legacyField);
		$this->log->save(self::UPGRADE_LOG, 'Field ' . self::LEGACY_FIELD_NAME . ' deleted');
		$this->message(\sprintf($this->_('Feld „%s“ gelöscht.'), self::LEGACY_FIELD_NAME));

		return [];
	}

	/**
	 * Matrix-Items liefern nur Felder, die in `matrixN_fields` ihres Typs stehen; die Fieldgroup genügt nicht.
	 */
	private function addServiceFieldToMatrixTypes(Field $legacyField, Field $serviceField): void
	{
		foreach ($this->fields->findByType('FieldtypeRepeaterMatrix') as $matrixField) {
			/** @var Field $matrixField */
			$changed = false;

			foreach ($matrixField->getArray() as $key => $ids) {
				if (!\preg_match('/^matrix\d+_fields$/', (string) $key) || !\is_array($ids)) {
					continue;
				}

				$ids = \array_map('intval', $ids);
				$position = \array_search((int) $legacyField->id, $ids, true);

				if ($position === false || \in_array((int) $serviceField->id, $ids, true)) {
					continue;
				}

				\array_splice($ids, $position + 1, 0, [(int) $serviceField->id]);
				$matrixField->set($key, $ids);
				$changed = true;
			}

			if ($changed) {
				$this->fields->save($matrixField);
				$this->log->save(self::UPGRADE_LOG, "Field {$serviceField->name} added to matrix types of {$matrixField->name}");
			}
		}
	}

	private function readStoredServiceValue(Field $serviceField, int $pageId): ?string
	{
		$query = $this->wire()->database->prepare('SELECT `data` FROM `' . $serviceField->getTable() . '` WHERE `pages_id` = :id');
		$query->execute([':id' => $pageId]);
		$value = $query->fetchColumn();

		return $value === false ? null : (string) $value;
	}

	private function countUnmigratedLegacyPages(Field $legacyField, Field $serviceField): int
	{
		$query = $this->wire()->database->query(
			'SELECT COUNT(DISTINCT l.`pages_id`) FROM `' . $legacyField->getTable() . '` l'
				. ' LEFT JOIN `' . $serviceField->getTable() . '` s ON s.`pages_id` = l.`pages_id`'
				. " WHERE s.`pages_id` IS NULL OR s.`data` = ''"
		);

		return (int) $query->fetchColumn();
	}

	/**
	 * Mehrere Werte gelten wie bei Tracking-Codes als ODER (bis 4.5 genügte eine Option).
	 *
	 * @return string[]
	 */
	private function getLegacyServiceKeys(mixed $options): array
	{
		if (!$options instanceof SelectableOptionArray) {
			return [];
		}

		$keys = [];

		foreach ($options as $option) {
			$key = $option instanceof SelectableOption ? $this->normalizeServiceId((string) $option->value, '') : '';

			if ($key !== '') {
				$keys[$key] = $key;
			}
		}

		$keys = \array_values($keys);

		if (\count($keys) > 1) {
			\array_unshift($keys, self::MATCH_ANY);
		}

		return $keys;
	}

	/**
	 * Vorhandene 4.6-Werte bleiben unberührt; nur Unübertragbares wird protokolliert und entfernt.
	 */
	private function migrateLegacyConfig(): void
	{
		$data = $this->modules->getConfig($this);
		$moves = [
			'bodytext' => 'infotext',
			'buttontext' => 'buttontext_accept',
			'introtext_necessary' => 'essential_description',
			'introtext_statistics' => 'statistics_description',
			'introtext_ads' => 'marketing_description',
			'cookies_necessary' => 'essential_cookies',
			'cookies_statistics' => 'statistics_cookies',
			'cookies_ads' => 'marketing_cookies',
			'target_page' => 'privacy_page',
			'google_site_veficiation' => 'google_site_verification',
		];
		$dropped = ['autolink', 'target_string', 'table_placeholder', 'custom_css', 'google_ads_conversions', 'conversion_count', 'multilanguage'];
		$headers = ['referrer_policy_header', 'strict_transport_security_header', 'x_content_type_options_header'];
		$legacyKeys = \array_merge(\array_keys($moves), $dropped, $headers);
		$isUnset = static fn(mixed $value): bool => $value === null || $value === '' || $value === [];
		$changed = false;
		$unsupported = [];
		$cookieLists = [];
		$texts = false;

		foreach (\array_keys($data) as $key) {
			if (!\preg_match('/^(.+?)(__\d+)?$/', (string) $key, $match)) {
				continue;
			}

			[, $base] = $match;
			$suffix = $match[2] ?? '';

			if (!\in_array($base, $legacyKeys, true)) {
				continue;
			}

			$changed = true;
			$value = $data[$key];
			unset($data[$key]);

			if (isset($moves[$base]) && !$isUnset($value)) {
				if (\str_starts_with($base, 'cookies_')) {
					$cookieLists[$moves[$base]] = $moves[$base];
				} elseif ($base !== 'target_page' && $base !== 'google_site_veficiation') {
					$texts = true;
				}
			}

			if (isset($moves[$base])) {
				$target = $moves[$base] . $suffix;

				if ($isUnset($data[$target] ?? null) && !$isUnset($value)) {
					$data[$target] = $value;
				}
			} elseif (\in_array($base, $dropped, true) && !$isUnset($value) && !\in_array($base, ['multilanguage', 'autolink', 'target_string', 'table_placeholder'], true)) {
				$unsupported[] = "{$key}=" . \substr(\is_scalar($value) ? (string) $value : \json_encode($value), 0, 500);
			} elseif ($base === 'referrer_policy_header') {
				$policies = ['no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin', 'same-origin', 'strict-origin', 'strict-origin-when-cross-origin', 'unsafe-url'];

				if ($isUnset($data['referrer_policy'] ?? null) && \in_array($value, $policies, true)) {
					$data['referrer_policy'] = $value;
				}
			} elseif ($base === 'strict_transport_security_header') {
				$maxAge = (int) $value;
				$steps = [2592000, 5184000, 7776000, 10368000, 12960000, 15552000, 31536000, 63072000];

				if ($maxAge > 0 && !\array_key_exists('hsts_enabled', $data)) {
					$fitting = \array_filter($steps, static fn(int $step): bool => $step <= $maxAge);
					$data['hsts_enabled'] = 1;
					$data['hsts_max_age'] = (string) ($fitting === [] ? $steps[0] : \max($fitting));
				}
			} elseif ($base === 'x_content_type_options_header') {
				if (!\array_key_exists('x_content_type_options_enabled', $data)) {
					$data['x_content_type_options_enabled'] = (int) (bool) $value;
				}
			}
		}

		if (!$changed) {
			return;
		}

		$hasText = static function (string $prefix) use ($data): bool {
			foreach ($data as $key => $value) {
				if (\str_starts_with((string) $key, $prefix) && \is_string($value) && \trim($value) !== '') {
					return true;
				}
			}

			return false;
		};

		if (!\array_key_exists('statistics_enabled', $data) && ($hasText('statistics_cookies') || !$isUnset($data['ga_property_id'] ?? null))) {
			$data['statistics_enabled'] = 1;
		}

		if (!\array_key_exists('marketing_enabled', $data) && $hasText('marketing_cookies')) {
			$data['marketing_enabled'] = 1;
		}

		$this->migratedSettings = true;

		if ($cookieLists !== []) {
			$this->upgradeNotices['cookie_groups'] = \implode(', ', $cookieLists);
		}

		if ($texts) {
			$this->upgradeNotices['texts'] = '';
		}

		$this->modules->saveConfig($this, $data);
		$this->setArray($data);
		$this->resetMemo();
		$this->log->save(self::UPGRADE_LOG, 'Legacy settings migrated to the 4.6 schema');
		$this->message($this->_('Einstellungen der Vorversion auf das aktuelle Schema übertragen.'));

		if ($unsupported !== []) {
			$this->log->save(self::UPGRADE_LOG, 'Settings without 4.6 equivalent dropped: ' . \implode('; ', $unsupported));
			$this->warning($this->_('Einige Einstellungen der Vorversion gibt es nicht mehr und wurden nicht übernommen (siehe Log cookiemonster-upgrade).'));
		}
	}

	/**
	 * z.B. ['statistics'] + 'ga' -> ['statistics-ga']; ohne Cookie-ID gilt die gewählte Kategorie selbst als Schlüssel.
	 */
	private function migrateLegacyTagSettings(): void
	{
		$data = $this->modules->getConfig($this);
		$changed = false;

		foreach (\array_keys(self::TAG_LABELS) as $prefix) {
			$categoryKey = "{$prefix}_cookie_category";
			$idKey = "{$prefix}_cookie_id";

			if (!\array_key_exists($categoryKey, $data) && !\array_key_exists($idKey, $data)) {
				continue;
			}

			$cookieId = \trim((string) ($data[$idKey] ?? ''));
			$services = [];

			foreach ((array) ($data[$categoryKey] ?? []) as $category) {
				$category = $this->normalizeServiceId((string) $category, '');

				if ($category === '') {
					continue;
				}

				if ($cookieId !== '') {
					$id = $this->normalizeServiceId($cookieId, $category);

					if ($id !== '') {
						$services[] = "{$category}-{$id}";
					}
				} else {
					$services[] = $category;
				}
			}

			$data["{$prefix}_cookie_service"] = \array_values(\array_unique(\array_merge((array) ($data["{$prefix}_cookie_service"] ?? []), $services)));

			// Bis 4.5 genügte eine der Kategorien
			if (\count($data["{$prefix}_cookie_service"]) > 1 && !\in_array(self::MATCH_ANY, $data["{$prefix}_cookie_service"], true)) {
				\array_unshift($data["{$prefix}_cookie_service"], self::MATCH_ANY);
			}
			unset($data[$categoryKey], $data[$idKey]);
			$changed = true;
		}

		if ($changed) {
			$this->migratedSettings = true;
			$this->modules->saveConfig($this, $data);
			$this->setArray($data);
			$this->resetMemo();
			$this->message($this->_('Zuordnung der Tracking-Codes auf Cookie-Dienste umgestellt.'));
		}
	}

	private function removeLegacyPermission(): void
	{
		$permission = $this->permissions->get(self::LEGACY_PERMISSION);

		if ($permission instanceof Permission && $permission->id) {
			$this->permissions->delete($permission);
			$this->message(\sprintf($this->_('Berechtigung „%s“ gelöscht.'), self::LEGACY_PERMISSION));
		}
	}

	public function init(): void
	{
		$this->wire('cmnstr', $this, true);

		$this->cspNonce = \bin2hex(\random_bytes(16));

		$this->gpc = ($this->get('respect_gpc') ?? false) && ($_SERVER['HTTP_SEC_GPC'] ?? '') === '1';
		$this->dnt = ($this->get('respect_dnt') ?? false) && ($_SERVER['HTTP_DNT'] ?? '') === '1';

		$this->addHook(self::CONSENT_PATH, $this, 'handleConsentRequest');
		$this->addHook(self::REPORT_PATH, $this, 'handleReportRequest');
		$this->addHook('LazyCron::everyDay', $this, 'pruneReportLog');
		$this->addHook('LazyCron::everyDay', $this, 'pruneConsentLog');
		$this->addHookAfter('Modules::saveConfig', $this, 'hookConfigSaved');
		$this->addHookBefore('Page::renderPage', $this, 'hookBeforeRenderPage');
		$this->addHookAfter('TemplateFile::render', $this, 'hookTemplateFileRender');
		$this->addHookAfter('Page::render', $this, 'hookPageRender');
	}

	public function ready(): void
	{
		$this->retryPendingMigration();
		$this->showUpgradeNotices();

		// Wurde bereits von handleConsentRequest() erledigt (Path-Hooks laufen vor ready())
		if ($this->consentLoaded || $this->skipConsent) {
			return;
		}

		$this->loadConsent();

		// Bei jedem Aufruf, z.B. falls ein offener Tab sie neu gesetzt hat
		$this->purgeUnconsentedCookies();
	}

	/**
	 * Tags mit ID, deren Kategorie keine Dienste anbietet, laden nie: die Zustimmung ist nicht möglich.
	 */
	private function collectUpgradeNotices(): void
	{
		if (!$this->migratedSettings) {
			return;
		}

		$ids = ['ga' => 'ga_property_id', 'gtm' => 'gtm_id', 'linkedin_insight' => 'linkedin_insight_id', 'meta_pixel' => 'meta_pixel_id'];
		$offered = $this->getOfferedServices();
		$blocked = [];

		foreach ($ids as $prefix => $idKey) {
			if ((string) $this->get($idKey) === '') {
				continue;
			}

			$keys = $this->normalizeServiceKeys((array) $this->get("{$prefix}_cookie_service"));
			$categories = $keys === [] ? [self::TAG_FALLBACK_CATEGORIES[$prefix] ?? 'statistics'] : \array_map(fn(string $key): string => $this->splitServiceKey($key)[0], $keys);

			foreach ($categories as $category) {
				if ($category !== self::MATCH_ANY && !isset($offered[$category])) {
					$blocked[$prefix] = self::TAG_LABELS[$prefix];
				}
			}
		}

		if ($blocked !== []) {
			$this->upgradeNotices['tag_without_services'] = \implode(', ', $blocked);
		}
	}

	private function storeUpgradeNotices(): void
	{
		if ($this->upgradeNotices === []) {
			return;
		}

		$data = $this->modules->getConfig($this);
		$data['upgrade_notice'] = $this->upgradeNotices + (array) ($data['upgrade_notice'] ?? []);
		$this->modules->saveConfig($this, $data);
		$this->setArray($data);
		$this->log->save(self::UPGRADE_LOG, 'Upgrade notices: ' . \implode(', ', \array_keys($this->upgradeNotices)));
		$this->upgradeNotices = [];
	}

	private function showUpgradeNotices(): void
	{
		$notices = (array) $this->get('upgrade_notice');
		$page = $this->wire()->page;

		if ($notices === [] || !$this->user->isSuperuser() || !$page instanceof Page || $page->template->name !== 'admin') {
			return;
		}

		$messages = [
			'cookie_groups' => $this->_('Die übernommenen Cookie-Listen (%s) haben noch keine Dienst-Gruppen („---id|Titel|…“). Ohne Gruppen steht ein Schalter für alle Cookies der Kategorie; bitte je Dienst gruppieren.'),
			'texts' => $this->_('Die Texte der Vorversion wurden unverändert übernommen. Bitte Zwecke, Anbieter und Hinweise prüfen.'),
			'tag_without_services' => $this->_('Für %s ist keine passende Cookie-Liste hinterlegt; der Tracking-Code wird deshalb nie geladen. Dienste in der zugehörigen Kategorie eintragen (und die Kategorie aktivieren).'),
		];

		foreach ($notices as $code => $detail) {
			if (isset($messages[$code])) {
				$this->warning(\sprintf($messages[$code], (string) $detail));
			}
		}

		$data = $this->modules->getConfig($this);
		unset($data['upgrade_notice']);
		$this->modules->saveConfig($this, $data);
		$this->remove('upgrade_notice');
	}

	private function retryPendingMigration(): void
	{
		if ((int) $this->get('migration_pending') <= 0 || !$this->user->isSuperuser()) {
			return;
		}

		// Höchstens einmal pro Minute: ein dauerhaft scheiternder Schritt soll nicht jeden Request belasten
		$cacheKey = 'cmnstr_migration_retry';

		if ($this->wire()->cache->get($cacheKey) === null) {
			$this->wire()->cache->save($cacheKey, 1, 60);

			$version = $this->getModuleVersion();
			$this->upgrade($version, $version);
		}

		$page = $this->wire()->page;

		if ((int) $this->get('migration_pending') > 0 && $page instanceof Page && $page->template->name === 'admin') {
			$this->warning(\sprintf(
				$this->_('Die Migration von CookieMonster ist unvollständig und wird als Superuser regelmäßig wiederholt (Log „%1$s“): %2$s'),
				self::UPGRADE_LOG,
				(string) $this->get('migration_pending_info')
			));
		}
	}

	/**
	 * z.B. {"external":{"youtube":true}} -> ["external-youtube" => true]
	 *
	 * @param array<string, mixed> $structure
	 * @return array<string, bool>
	 */
	private function flattenCookieStructure(array $structure, string $prefix = ''): array
	{
		$result = [];

		foreach ($structure as $key => $value) {
			$key = (string) $key;
			$fullKey = $prefix !== '' ? "{$prefix}-{$key}" : $key;

			// Meta-Felder (_version, _id, _t, _r, _gpc, _c) sind keine Entscheidungen
			if ($prefix === '' && \str_starts_with($key, '_')) {
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
	 * @param array<string, bool> $flatConsent
	 */
	private function isCategoryFullyConsented(string $categoryKey, array $flatConsent): bool
	{
		// Deaktivierte Kategorien fehlen hier, auch wenn ein Alt-Eintrag im Cookie true ist
		$serviceIds = $this->getOfferedServices()[$categoryKey] ?? [];

		if (empty($serviceIds)) {
			return false;
		}

		foreach ($serviceIds as $serviceId) {
			if (($flatConsent["{$categoryKey}-{$serviceId}"] ?? false) !== true) {
				return false;
			}
		}

		return true;
	}

	private function getHost(): string
	{
		return (string) \preg_replace('/:\d+$/', '', \strtolower((string) $this->config->httpHost));
	}

	/**
	 * @return string[]
	 */
	private function getCookieDomainVariants(): array
	{
		$host = $this->getHost();

		if ($host === '' || \filter_var($host, \FILTER_VALIDATE_IP)) {
			return [];
		}

		$labels = \explode('.', $host);
		$variants = [];

		for ($i = 0; $i <= \count($labels) - 2; $i++) {
			$variants[] = \implode('.', \array_slice($labels, $i));
		}

		return $variants;
	}

	/**
	 * Nur, wenn sie dem Host entspricht oder übergeordnet ist; sonst '' (Host-only).
	 */
	private function getSharedCookieDomain(): string
	{
		$domain = \strtolower(\trim((string) $this->get('cookie_domain'), " \t\n\r\0\v."));

		return \in_array($domain, $this->getCookieDomainVariants(), true) ? $domain : '';
	}

	/**
	 * @return array<string, mixed>
	 */
	private function expiredCookieOptions(?string $domain = null): array
	{
		$options = [
			'expires' => 1,
			'path' => '/',
			'secure' => (bool) $this->config->https,
			'samesite' => 'Lax',
		];

		if ($domain !== null) {
			$options['domain'] = $domain;
		}

		return $options;
	}

	/**
	 * @param string|null $keepDomain nicht löschen ('' = Host-only-Cookie behalten)
	 */
	private function expireCookie(string $name, ?string $keepDomain = null): void
	{
		if (!$this->isValidCookieName($name)) {
			return;
		}

		if ($keepDomain !== '') {
			\setcookie($name, '', $this->expiredCookieOptions());
		}

		foreach ($this->getCookieDomainVariants() as $domain) {
			if ($domain !== $keepDomain) {
				\setcookie($name, '', $this->expiredCookieOptions($domain));
			}
		}
	}

	private function isValidCookieName(string $name): bool
	{
		return \preg_match(self::COOKIE_NAME_PATTERN, $name) === 1;
	}

	private function purgeConsentCookie(): void
	{
		if (!\headers_sent()) {
			$this->expireCookie(self::COOKIE_NAME);
		}
	}

	/**
	 * @return string[]
	 */
	private function getRawCookieValues(string $name): array
	{
		$rawHeader = (string) ($_SERVER['HTTP_COOKIE'] ?? '');

		if ($rawHeader === '') {
			return [];
		}

		$prefix = "{$name}=";
		$values = [];

		foreach (\explode(';', $rawHeader) as $pair) {
			$pair = \ltrim($pair);

			if (!\str_starts_with($pair, $prefix)) {
				continue;
			}
			$values[] = \urldecode(\substr($pair, \strlen($prefix)));
		}

		return $values;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function findValidDuplicateCookieValue(): ?array
	{
		foreach ($this->getRawCookieValues(self::COOKIE_NAME) as $raw) {
			try {
				$decoded = \json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
			} catch (\JsonException) {
				continue;
			}

			if ($this->isUsableConsent($decoded)) {
				return $decoded;
			}
		}

		return null;
	}

	private function getModuleVersion(): string
	{
		if ($this->moduleVersion === null) {
			$this->moduleVersion = $this->normalizeVersion($this->modules->getModuleInfoProperty($this, 'version'));
		}

		return $this->moduleVersion;
	}

	private function normalizeVersion(mixed $version): string
	{
		return $this->modules->formatVersion((string) $version);
	}

	private function isCurrentCookieVersion(mixed $cookieValues): bool
	{
		// Neuere Versionen (z.B. von einer anderen Subdomain) nicht zurückmigrieren
		return \is_array($cookieValues) &&
			\version_compare($this->normalizeVersion($cookieValues['_version'] ?? 0), $this->getModuleVersion(), '>=');
	}

	/**
	 * Gespeicherte Entscheidungen vor der Consent-Epoche (gesetzt beim Modul-Upgrade) und Cookies ohne Zeitpunkt (4.5) gelten als nicht vorhanden.
	 *
	 * @param array<string, mixed> $cookieValues
	 */
	private function isConsentSuperseded(array $cookieValues): bool
	{
		$issuedAt = (int) ($cookieValues['_t'] ?? 0);

		return $issuedAt <= 0 || $issuedAt < (int) $this->get('consent_epoch');
	}

	private function isUsableConsent(mixed $cookieValues): bool
	{
		return $this->isCurrentCookieVersion($cookieValues) && !$this->isConsentSuperseded($cookieValues);
	}

	/**
	 * @param array<string, mixed> $cookieValues
	 */
	private function isConsentExpired(array $cookieValues): bool
	{
		$issuedAt = (int) ($cookieValues['_t'] ?? 0);

		return $issuedAt > 0 && $issuedAt + self::COOKIE_LIFETIME <= \time();
	}

	/**
	 * @throws \JsonException
	 */
	private function decodeConsentCookie(string $raw): mixed
	{
		$cookieValues = \json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);

		if (!$this->isUsableConsent($cookieValues) && \count($this->getRawCookieValues(self::COOKIE_NAME)) > 1) {
			$cookieValues = $this->findValidDuplicateCookieValue() ?? $cookieValues;
		}

		return $cookieValues;
	}

	/**
	 * @return array<string, bool>|null null = keine Entscheidung
	 */
	private function getFlatConsent(): ?array
	{
		return $this->consent === null ? null : $this->flatConsent;
	}

	public function getCspNonce(): string
	{
		return $this->cspNonce;
	}

	public function getConsentId(): ?string
	{
		$id = $this->consent['_id'] ?? null;

		return $this->isValidConsentId($id) ? $id : null;
	}

	private function isValidConfigHash(mixed $hash): bool
	{
		return \is_string($hash) && \preg_match('/^[0-9a-f]{' . self::CONFIG_HASH_LENGTH . '}$/', $hash) === 1;
	}

	private function isValidConsentId(mixed $id): bool
	{
		return \is_string($id) && \preg_match('/^[0-9a-f]{32}$/', $id) === 1;
	}

	private function loadConsent(): void
	{
		$this->consentLoaded = true;
		$this->applyConsent(null);

		$raw = $_COOKIE[self::COOKIE_NAME] ?? null;

		if ($raw === null || $raw === '') {
			return;
		}

		if (!\is_string($raw)) {
			$this->purgeConsentCookie();

			return;
		}

		try {
			$cookieValues = $this->decodeConsentCookie($raw);
		} catch (\JsonException) {
			// Kein Notice: Besucher könnten sonst die Meldungswarteschlange füllen
			$this->purgeConsentCookie();

			return;
		}

		if (!\is_array($cookieValues) || $this->isConsentExpired($cookieValues)) {
			$this->purgeConsentCookie();

			return;
		}

		// Das alte Cookie bleibt bis zur nächsten Entscheidung liegen und wird dann überschrieben
		if (!$this->isUsableConsent($cookieValues)) {
			return;
		}

		if (!$this->isValidConfigHash($cookieValues['_c'] ?? null)) {
			unset($cookieValues['_c']);
		}

		$this->applyConsent($cookieValues);
		// Domain-Duplikate: verwendeten Wert unter der konfigurierten Domain neu setzen
		$this->refreshConsentCookie(\count($this->getRawCookieValues(self::COOKIE_NAME)) > 1);
	}

	/**
	 * @param array<string, mixed>|null $consent
	 */
	private function applyConsent(?array $consent): void
	{
		$this->consent = $consent;
		$this->flatConsent = $consent === null ? [] : $this->applyGpc($this->flattenCookieStructure($consent), $consent);
		$this->consentedCategories = ['essential'];

		$this->googleConsentStates = \array_fill_keys([
			'ad_storage',
			'ad_user_data',
			'ad_personalization',
			'analytics_storage',
			'functionality_storage',
			'personalization_storage',
			'security_storage',
		], 'denied');

		$this->grantGoogleConsent(self::CATEGORY_CONSENT['essential']);

		foreach (self::CATEGORY_CONSENT as $key => $types) {
			if ($key !== 'essential' && $this->isCategoryFullyConsented($key, $this->flatConsent)) {
				$this->consentedCategories[] = $key;
				$this->grantGoogleConsent($types);
			}
		}

		// Entfernte Dienste aus älteren Cookies gelten nicht mehr als freigeschaltet
		$offered = $this->getOfferedServices();
		// Ohne angebotene Dienste gibt es nichts, dem zugestimmt werden könnte (auch „Alle ablehnen“ legt ein Cookie an)
		$allConsented = $consent !== null && $offered !== [];

		foreach ($offered as $key => $serviceIds) {
			foreach ($serviceIds as $serviceId) {
				if (($this->flatConsent["{$key}-{$serviceId}"] ?? false) === true) {
					$this->consentedCategories[] = "{$key}-{$serviceId}";
				} else {
					$allConsented = false;
				}
			}
		}

		if ($allConsented) {
			$this->consentedCategories[] = self::ALL_SERVICES;
		}

		$this->allowTracking = $this->isCategoryFullyConsented('statistics', $this->flatConsent);
	}

	/**
	 * @return string[]
	 */
	private function getGpcCategories(): array
	{
		$categories = $this->get('gpc_categories') ?? ['marketing'];

		return \array_values(\array_intersect((array) $categories, \array_keys(self::CATEGORY_CONSENT)));
	}

	/**
	 * Wurde der Consent bei aktivem GPC erteilt (`_gpc`), gilt er unverändert.
	 *
	 * @param array<string, bool> $flatConsent
	 * @param array<string, mixed> $consent
	 * @return array<string, bool>
	 */
	private function applyGpc(array $flatConsent, array $consent): array
	{
		if (!$this->gpc || ($consent['_gpc'] ?? false) === true) {
			return $flatConsent;
		}

		$categories = $this->getGpcCategories();

		foreach ($flatConsent as $key => $value) {
			if ($value && \in_array($this->splitServiceKey($key)[0], $categories, true)) {
				$flatConsent[$key] = false;
			}
		}

		return $flatConsent;
	}

	private function getPrivacySignalNotice(): string
	{
		if ($this->gpc) {
			$affected = \array_intersect($this->getGpcCategories(), \array_keys($this->getOfferedServices()));
			$titles = [];

			foreach (\array_keys(self::CATEGORY_CONSENT) as $key) {
				if (\in_array($key, $affected, true)) {
					$titles[] = $this->getCategoryTitle($key);
				}
			}

			if (!empty($titles)) {
				return \str_replace('{categories}', \implode(', ', $titles), $this->getText('gpc_notice', $this->text('gpc_notice')));
			}
		}

		return $this->dnt ? $this->getText('dnt_notice', $this->text('dnt_notice')) : '';
	}

	/**
	 * @param string[] $types
	 */
	private function grantGoogleConsent(array $types): void
	{
		foreach ($types as $type) {
			if (isset($this->googleConsentStates[$type])) {
				$this->googleConsentStates[$type] = 'granted';
			}
		}
	}

	/**
	 * @param string|string[]|null $keys leer = keine Einschränkung
	 */
	public function isUnlocked(string|array|null $keys, bool $any = false): bool
	{
		$missing = $keys === null;
		$any = $any || $this->isMatchAny($keys);
		$keys = $this->normalizeServiceKeys($keys);

		if (!$any || \count($keys) < 2) {
			return empty($this->getLockedServiceKeys($missing ? null : $keys, $missing));
		}

		foreach ($keys as $key) {
			if (empty($this->getLockedServiceKeys($key))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Noch nicht freigegebene Schlüssel (aufgelöst); `all` verdrängt alle anderen, Dienste einer ebenfalls gesperrten Kategorie entfallen.
	 *
	 * Nur ein explizites `null` (`$missing`, z.B. entferntes Feld) unterliegt der Einstellung `missing_service_mode`; leere Werte sind unbeschränkt.
	 *
	 * @param string|string[]|null $keys
	 * @return string[]
	 */
	private function getLockedServiceKeys(string|array|null $keys, bool $missing = false): array
	{
		if ($missing && $keys === null) {
			$this->warnMissingServiceKey();

			if ($this->get('missing_service_mode') === 'closed') {
				$keys = self::ALL_SERVICES;
			}
		}

		$resolved = [];

		foreach ($this->normalizeServiceKeys($keys) as $key) {
			$key = $this->resolveServiceKey($key);

			if ($this->splitServiceKey($key)[0] !== 'essential' && !\in_array($key, $this->consentedCategories, true)) {
				$resolved[$key] = $key;
			}
		}

		if (isset($resolved[self::ALL_SERVICES])) {
			return [self::ALL_SERVICES];
		}

		return \array_values(\array_filter($resolved, function (string $key) use ($resolved): bool {
			[$category, $serviceId] = $this->splitServiceKey($key);

			return $serviceId === null || !isset($resolved[$category]);
		}));
	}

	private function warnMissingServiceKey(): void
	{
		$caller = '';

		foreach (\debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6) as $frame) {
			if (isset($frame['file']) && $frame['file'] !== __FILE__) {
				$caller = $frame['file'] . ':' . ($frame['line'] ?? 0);
				break;
			}
		}

		$page = $this->wire()->page;
		$id = $page instanceof Page ? (int) $page->id : 0;
		$key = $id . '|' . $caller;

		if (isset($this->missingKeyWarned[$key])) {
			return;
		}

		$this->missingKeyWarned[$key] = true;

		// Pro Seite und Aufrufstelle höchstens einmal am Tag, sonst wächst das Log mit jedem Besuch
		$cacheKey = 'cmnstr_missing_key_' . \md5($key);

		if ($this->wire()->cache->get($cacheKey) !== null) {
			return;
		}

		$this->wire()->cache->save($cacheKey, 1, 86400);
		$mode = $this->get('missing_service_mode') === 'closed' ? 'masked' : 'not masked';

		$this->log->save(self::LOG, "Service key is null (field missing?), content {$mode}: page {$id}, {$caller}");
	}

	/**
	 * @param string|string[]|null $keys
	 */
	private function isMatchAny(string|array|null $keys): bool
	{
		if (!\is_array($keys)) {
			$keys = \preg_split('/[\s,|]+/', (string) $keys) ?: [];
		}

		return \in_array(self::MATCH_ANY, $keys, true);
	}

	/**
	 * @param string|string[]|null $keys
	 * @return string[]
	 */
	public function normalizeServiceKeys(string|array|null $keys): array
	{
		if (!\is_array($keys)) {
			$keys = \preg_split('/[\s,|]+/', (string) $keys) ?: [];
		}

		return \array_values(\array_unique(\array_filter(\array_map(static function ($key): string {
			return \trim((string) $key);
		}, \array_filter($keys, \is_scalar(...))), static function (string $key): bool {
			return $key !== '' && $key !== self::MATCH_ANY;
		})));
	}

	public function resolveServiceKey(string $key): string
	{
		if ($key === self::ALL_SERVICES) {
			return $key;
		}

		[$category, $serviceId] = $this->splitServiceKey($key);

		if ($category === 'essential') {
			return $serviceId === null || $this->categoryExists($key) ? $key : 'essential';
		}

		$offered = $this->getOfferedServices();

		if (!isset($offered[$category])) {
			return self::ALL_SERVICES;
		}

		return $serviceId === null || \in_array($serviceId, $offered[$category], true) ? $key : $category;
	}

	public function getServiceLabel(string $key): string
	{
		if ($key === self::ALL_SERVICES) {
			return $this->text('all_categories');
		}

		return $this->getServiceOptions()[$key] ?? $this->getCategoryTitle($this->splitServiceKey($key)[0]);
	}

	private function isTagUnlocked(string $prefix): bool
	{
		$value = (array) $this->get("{$prefix}_cookie_service");

		if (!empty($this->normalizeServiceKeys($value))) {
			return $this->isUnlocked($value);
		}

		$category = self::TAG_FALLBACK_CATEGORIES[$prefix] ?? 'statistics';

		return $category === 'statistics' ? $this->allowTracking : \in_array($category, $this->consentedCategories, true);
	}

	/**
	 * @return string[]
	 */
	public function getUnlockedCategories(): array
	{
		return $this->consentedCategories;
	}

	public function allowTracking(): bool
	{
		return $this->allowTracking;
	}

	/**
	 * @return array<string, string>
	 */
	public function getGoogleConsentStates(): array
	{
		return $this->googleConsentStates;
	}

	public function getConsentUrl(): string
	{
		return \rtrim($this->config->urls->root, '/') . self::CONSENT_PATH;
	}

	/**
	 * @return array{consentUrl: string, redirectUrl: string, settingsUrl: string, languageId: int}
	 */
	private function getConsentFormData(): array
	{
		$query = $this->input->get->getArray();
		unset($query[self::SETTINGS_PARAM]);

		$url = $this->input->url();

		return [
			'consentUrl' => $this->getConsentUrl(),
			'redirectUrl' => empty($query) ? $url : "{$url}?" . \http_build_query($query),
			'settingsUrl' => "{$url}?" . \http_build_query($query + [self::SETTINGS_PARAM => 1]),
			'languageId' => $this->languages ? (int) $this->user->language->id : 0,
		];
	}

	private function isSettingsRequest(): bool
	{
		return $this->input->get(self::SETTINGS_PARAM) !== null;
	}

	public function handleConsentRequest(HookEvent $event): void
	{
		$this->sendEndpointHeaders();

		if (!$this->input->requestMethod('POST')) {
			\header('Allow: POST');
			\http_response_code(405);
			$event->return = ['success' => false, 'error' => 'Method Not Allowed'];

			return;
		}

		if (!$this->isSameOriginRequest()) {
			\http_response_code(403);
			$event->return = ['success' => false, 'error' => 'Forbidden'];

			return;
		}

		$this->applyRequestLanguage();
		$this->loadConsent();

		$redirectUrl = $this->getConsentRedirectUrl();
		$fragment = $this->getRedirectFragment();

		if ($this->input->post('cmnstr-once') !== null) {
			$this->handleOnceRequest($redirectUrl, $fragment);
		}

		$consent = $this->buildConsentFromRequest();

		// Ohne gültigen Eintrag bleibt der Consent unverändert, die Seite zeigt die aktuelle Maske
		if ($consent !== null) {
			$before = $this->flattenCookieStructure($this->consent ?? []);
			$after = $this->flattenCookieStructure($consent);
			\ksort($before);
			\ksort($after);

			$this->session->setFor($this, 'saved', $fragment);

			// Eine unveränderte Entscheidung erneuert das Cookie und setzt den Ablauf neu
			$changed = $this->consent === null || $before !== $after;
			$this->storeConsentCookie($consent, $changed ? $this->getRequestAction() : 'renew');
		}

		$purged = $this->purgeUnconsentedCookies();

		$consentId = $this->getConsentId();

		if (!empty($purged) && $consentId !== null) {
			$this->saveConsentLog("{$consentId} purge " . \implode(',', $purged));
		}

		$this->session->location($redirectUrl . ($consent !== null && $fragment !== '' ? "#{$fragment}" : ''), 303);
	}

	/**
	 * "Einmal laden": gibt Dienste nur bis Sitzungsende frei, ohne Consent-Cookie, mit einer Zeile `once` im Protokoll (nur mit vorhandener Cookie-ID).
	 */
	private function handleOnceRequest(string $redirectUrl, string $fragment): void
	{
		$keys = $this->getRequestOnceKeys();

		if (!empty($keys)) {
			$current = $this->getOnceKeys();
			$added = \array_values(\array_diff($keys, $current));

			$this->session->setFor($this, 'once', \array_values(\array_unique(\array_merge($current, $keys))));
			$this->session->setFor($this, 'saved', $fragment);
			$this->session->setFor($this, 'status', 'once');

			// Wiederholte Anfragen für denselben Dienst füllen das Protokoll nicht
			$consentId = $this->getConsentId();

			// Ohne Cookie-ID lässt sich die Zeile keiner Entscheidung zuordnen, daher kein Eintrag
			if (!empty($added) && $consentId !== null) {
				$names = \array_map(fn(string $key): string => $this->sanitizer->name($key, false, 64), $added);

				$this->saveConsentLog("{$consentId} once " . \implode(',', $names));
			}
		}

		$this->session->location($redirectUrl . (!empty($keys) && $fragment !== '' ? "#{$fragment}" : ''), 303);
	}

	/**
	 * @return string[]
	 */
	private function getOnceKeys(): array
	{
		$keys = $this->session->getFor($this, 'once');

		return \is_array($keys) ? \array_values(\array_filter($keys, \is_string(...))) : [];
	}

	/**
	 * @param string[] $keys
	 */
	private function isOnceAllowed(array $keys): bool
	{
		if (empty($keys)) {
			return false;
		}

		$gpcCategories = $this->gpc ? $this->getGpcCategories() : [];

		foreach ($keys as $key) {
			[$category, $serviceId] = $this->splitServiceKey($key);

			if ($serviceId === null || $category === 'essential' || \in_array($category, $gpcCategories, true)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return string[]
	 */
	private function getRequestOnceKeys(): array
	{
		$keys = \array_slice($this->normalizeServiceKeys($this->getPostString('cmnstr-once')), 0, 10);

		return \array_values(\array_filter($keys, function (string $key): bool {
			return $this->resolveServiceKey($key) === $key && $this->isOnceAllowed([$key]);
		}));
	}

	private function getRedirectFragment(): string
	{
		$fragment = (string) \parse_url($this->getPostString('cmnstr-redirect'), \PHP_URL_FRAGMENT);

		return \preg_match('/^cmnstr-mask-\d{1,4}$/', $fragment) === 1 ? $fragment : '';
	}

	private function takeStatusMessage(): string
	{
		if ($this->session->getFor($this, 'saved') === null) {
			return '';
		}

		$once = $this->session->getFor($this, 'status') === 'once';
		$this->session->removeFor($this, 'saved');
		$this->session->removeFor($this, 'status');

		return $once
			? $this->text('saved_once')
			: $this->text('saved');
	}

	/**
	 * Hook: Nimmt CSP-Verstöße (`report-uri` und Reporting API) entgegen. Das Modul setzt hier kein eigenes Cookie; das Session-Cookie von ProcessWire kann trotzdem gesetzt werden.
	 */
	public function handleReportRequest(HookEvent $event): void
	{
		$this->skipConsent = true;
		$this->sendEndpointHeaders();

		$event->return = true;

		if (!$this->input->requestMethod('POST')) {
			\header('Allow: POST');
			\http_response_code(405);

			return;
		}

		$type = \strtolower(\trim(\explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));

		if (!\in_array($type, ['application/csp-report', 'application/reports+json'], true)) {
			\http_response_code(415);

			return;
		}

		if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > self::REPORT_MAX_BYTES) {
			\http_response_code(413);

			return;
		}

		$raw = (string) \file_get_contents('php://input', false, null, 0, self::REPORT_MAX_BYTES + 1);

		if (\strlen($raw) > self::REPORT_MAX_BYTES) {
			\http_response_code(413);

			return;
		}

		$data = \json_decode($raw, true, 6);

		if (!\is_array($data)) {
			\http_response_code(400);

			return;
		}

		$lines = [];

		foreach ($this->extractReports($data, $type) as $report) {
			$line = $this->formatReport($report);

			if ($line !== '') {
				$lines[] = $line;
			}
		}

		$result = $this->reserveReportLines($lines);

		if ($result === null) {
			\http_response_code(503);

			return;
		}

		foreach ($result['accepted'] as $line) {
			$this->log->save(self::REPORT_LOG, $line, ['showUser' => false, 'showURL' => false]);
		}

		if ($result['limited']) {
			\http_response_code(429);

			return;
		}

		\http_response_code(204);
	}

	/**
	 * Globales Zeitfenster-Limit statt Session: gleiche Zeilen im Fenster zählen einmal, danach höchstens {@see self::REPORT_WINDOW_LIMIT} neue.
	 *
	 * @param string[] $lines
	 * @return array{accepted: string[], limited: bool}|null null = Zustand nicht lesbar oder nicht schreibbar
	 */
	private function reserveReportLines(array $lines): ?array
	{
		$dir = $this->config->paths->logs;

		if (!\is_dir($dir)) {
			$this->files->mkdir($dir, true);
		}

		$file = @\fopen($dir . self::REPORT_STATE, 'c+');

		if ($file === false) {
			return null;
		}

		if (!\flock($file, \LOCK_EX)) {
			\fclose($file);

			return null;
		}

		$now = \time();
		$state = \json_decode((string) \stream_get_contents($file), true);
		$hashes = \is_array($state) && \is_array($state['h'] ?? null) ? $state['h'] : [];
		$start = \is_array($state) ? (int) ($state['s'] ?? 0) : 0;

		if ($start <= 0 || $start > $now || $now - $start >= self::REPORT_WINDOW) {
			$start = $now;
			$hashes = [];
		}

		$accepted = [];
		$limited = false;

		foreach ($lines as $line) {
			$key = \md5($line);

			if (isset($hashes[$key])) {
				continue;
			}

			if (\count($hashes) >= self::REPORT_WINDOW_LIMIT) {
				$limited = true;

				break;
			}

			$hashes[$key] = 1;
			$accepted[] = $line;
		}

		$written = false;

		if ($accepted !== []) {
			$json = \json_encode(['s' => $start, 'h' => $hashes]);
			$written = $json !== false && \ftruncate($file, 0) && \rewind($file) && \fwrite($file, $json) !== false;
		} else {
			$written = true;
		}

		\flock($file, \LOCK_UN);
		\fclose($file);

		return $written ? ['accepted' => $accepted, 'limited' => $limited] : null;
	}

	public function pruneReportLog(HookEvent $event): void
	{
		$this->log->prune(self::REPORT_LOG, self::REPORT_LOG_DAYS);
	}

	/**
	 * Läuft auch bei "Kein Logging" mit dem Standardwert, damit Altbestand ausläuft.
	 */
	public function pruneConsentLog(HookEvent $event): void
	{
		$days = ($this->getConsentLogDays() ?: self::CONSENT_LOG_DEFAULT_DAYS) + \intdiv(self::COOKIE_LIFETIME, 86400);
		$this->log->prune(self::CONSENT_LOG, $days);
		$this->log->prune(self::CONFIG_LOG, $days);
	}

	/**
	 * @return int Tage nach Ablauf der Einwilligung, 0 = kein Logging
	 */
	private function getConsentLogDays(?string $value = null): int
	{
		$value ??= (string) $this->get('consent_log_retention');

		if ($value === 'none') {
			return 0;
		}

		return \in_array($value, self::CONSENT_LOG_OPTIONS, true) ? (int) $value : self::CONSENT_LOG_DEFAULT_DAYS;
	}

	private function isConsentLogEnabled(): bool
	{
		return $this->getConsentLogDays() > 0;
	}

	/**
	 * @param array<mixed> $data
	 * @return array<int, array<mixed>>
	 */
	private function extractReports(array $data, string $type): array
	{
		$reports = [];

		if ($type === 'application/csp-report') {
			if (\is_array($data['csp-report'] ?? null)) {
				$reports[] = $data['csp-report'];
			}
		} else {
			$items = \array_is_list($data) ? $data : [$data];

			foreach ($items as $item) {
				if (\is_array($item) && ($item['type'] ?? '') === 'csp-violation' && \is_array($item['body'] ?? null)) {
					$reports[] = $item['body'];
				}
			}
		}

		return \array_slice($reports, 0, self::REPORT_MAX_ITEMS);
	}

	/**
	 * Nur eine Whitelist an Feldern; URLs ohne Query und Fragment, alles hart gekürzt.
	 *
	 * @param array<mixed> $report
	 */
	private function formatReport(array $report): string
	{
		$fields = [
			'blocked' => [['blocked-uri', 'blockedURL'], true],
			'directive' => [['effective-directive', 'effectiveDirective', 'violated-directive', 'violatedDirective'], false],
			'document' => [['document-uri', 'documentURL'], true],
			'disposition' => [['disposition'], false],
			'source' => [['source-file', 'sourceFile'], true],
			'line' => [['line-number', 'lineNumber'], false],
			'status' => [['status-code', 'statusCode'], false],
		];

		$parts = [];

		foreach ($fields as $label => [$keys, $isUrl]) {
			$value = '';

			foreach ($keys as $key) {
				if (isset($report[$key]) && \is_scalar($report[$key])) {
					$value = (string) $report[$key];
					break;
				}
			}

			if ($isUrl) {
				$value = (string) (\preg_match('/^(data|blob|javascript):/i', $value, $scheme) === 1 ? $scheme[1] : \preg_split('/[?#]/', $value, 2)[0]);
			}

			$value = \substr(\str_replace(' ', '%20', (string) \preg_replace('/[^\x20-\x7E]/', '', $value)), 0, self::REPORT_FIELD_LENGTH);

			if ($value !== '') {
				$parts[] = "{$label}={$value}";
			}
		}

		return \implode(' ', $parts);
	}

	private function isSameOriginRequest(): bool
	{
		$fetchSite = (string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');

		if ($fetchSite !== '') {
			return $fetchSite === 'same-origin' || $fetchSite === 'none';
		}

		$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');

		// Ältere Clients ohne Origin-Header zulassen
		if ($origin === '') {
			return true;
		}

		$originHost = \strtolower((string) \parse_url($origin, \PHP_URL_HOST));

		return $originHost !== '' && $originHost === $this->getHost();
	}

	private function applyRequestLanguage(): void
	{
		$languageId = (int) $this->getPostString('cmnstr-lang');

		if (!$this->languages || $languageId < 1) {
			return;
		}

		$language = $this->languages->get($languageId);

		if ($language instanceof Language && $language->id) {
			$this->user->language = $language;
		}
	}

	private function getPostString(string $name): string
	{
		$value = $this->input->post($name);

		return \is_string($value) ? $value : '';
	}

	private function getRequestAction(): string
	{
		$grants = \array_map(function (string $key): string {
			return $this->sanitizer->name($key, false, 64);
		}, $this->getRequestGrants());
		$grant = \implode(',', \array_slice($grants, 0, 10));

		if ($grant !== '') {
			return "grant:{$grant}";
		}

		$all = $this->getPostString('cmnstr-all');

		return match ($all) {
			'1' => 'accept-all',
			'0' => 'decline-all',
			default => 'custom',
		};
	}

	/**
	 * Nur unverändert angebotene Schlüssel: Zustimmung gilt nur für das Angezeigte.
	 *
	 * @return string[]
	 */
	private function getRequestGrants(): array
	{
		return \array_values(\array_filter($this->normalizeServiceKeys($this->input->post('cmnstr-grant')), function (string $key): bool {
			return $this->resolveServiceKey($key) === $key;
		}));
	}

	/**
	 * @return array<string, mixed>|null null = nur nicht mehr angebotene Einträge
	 */
	private function buildConsentFromRequest(): ?array
	{
		$grants = $this->getRequestGrants();
		$grantAll = \in_array(self::ALL_SERVICES, $grants, true);

		if (empty($grants) && !empty($this->normalizeServiceKeys($this->input->post('cmnstr-grant')))) {
			return null;
		}

		if (!empty($grants) && !$grantAll) {
			$grants = \array_map($this->splitServiceKey(...), $grants);
			$previousConsent = $this->getFlatConsent();
			$flatConsent = $previousConsent ?? [];
			$firstDecision = $previousConsent === null;

			// Erste Entscheidung über eine Maske: alles außer dem freigegebenen Dienst gilt als abgelehnt
			return $this->buildConsent(static function (string $key, string $serviceId) use ($grants, $flatConsent, $firstDecision): ?bool {
				foreach ($grants as [$grantKey, $grantService]) {
					if ($key === $grantKey && ($grantService === null || $grantService === $serviceId)) {
						return true;
					}
				}

				return $flatConsent["{$key}-{$serviceId}"] ?? ($firstDecision ? false : null);
			}, $this->consent ?? []);
		}

		$all = $grantAll ? '1' : $this->getPostString('cmnstr-all');
		$setAll = $all === '' ? null : $all === '1';

		// Bei GPC gilt "Alle akzeptieren" nicht für die betroffenen Kategorien
		$gpcCategories = $setAll && $this->gpc ? $this->getGpcCategories() : [];

		return $this->buildConsent(function (string $key, string $serviceId) use ($setAll, $gpcCategories): bool {
			if (\in_array($key, $gpcCategories, true)) {
				return false;
			}

			return $setAll ?? $this->input->post("cmnstr-{$key}-{$serviceId}") !== null;
		}, $this->consent ?? []);
	}

	/**
	 * Angebotene Dienste explizit true/false (oder offen), damit neue Dienste erkennbar sind.
	 * Entscheidungen zu Diensten, die hier nicht angeboten werden (z.B. von einer anderen
	 *
	 * @param callable(string, string): ?bool $decide null = Dienst bleibt offen
	 * @param array<string, mixed> $previous bisheriger Consent
	 * @return array<string, mixed>
	 */
	private function buildConsent(callable $decide, array $previous = []): array
	{
		$offered = $this->getOfferedServices();
		$consent = ['essential' => true];

		foreach ($offered as $key => $serviceIds) {
			foreach ($serviceIds as $serviceId) {
				$value = $decide($key, $serviceId);

				if ($value !== null) {
					$consent[$key][$serviceId] = $value;
				}
			}
		}

		foreach ($previous as $key => $services) {
			if (!\is_array($services) || $key === 'essential' || !\array_key_exists((string) $key, self::CATEGORY_CONSENT)) {
				continue;
			}

			foreach ($services as $serviceId => $value) {
				if (\is_bool($value) && \preg_match('/^[A-Za-z0-9_-]+$/', (string) $serviceId) === 1 && !\in_array((string) $serviceId, $offered[$key] ?? [], true)) {
					$consent[$key][$serviceId] = $value;
				}
			}
		}

		return $consent + ['_version' => $this->getModuleVersion()];
	}

	/**
	 * @return array<string, string[]>
	 */
	private function getOfferedServices(): array
	{
		if ($this->offeredServices !== null) {
			return $this->offeredServices;
		}

		$services = [];

		foreach (\array_keys(self::CATEGORY_CONSENT) as $key) {
			if ($key === 'essential' || !$this->get("{$key}_enabled")) {
				continue;
			}

			$serviceIds = $this->getServiceIds($key);

			if (!empty($serviceIds)) {
				$services[$key] = $serviceIds;
			}
		}

		return $this->offeredServices = $services;
	}

	/**
	 * @return string[]
	 */
	private function getServiceIds(string $categoryKey): array
	{
		$ids = \array_column($this->parseCategory($categoryKey, ''), 'id');

		return \array_values(\array_filter($ids, static function (string $id): bool {
			return $id !== '';
		}));
	}

	/**
	 * @param string[] $keepKeys erscheinen auch, wenn nicht mehr konfiguriert
	 * @return array<string, string>
	 */
	public function getServiceOptions(array $keepKeys = []): array
	{
		$options = [];

		foreach ($this->getServiceOptionGroups($keepKeys) as $group) {
			$options += $group['options'];
		}

		return $options;
	}

	/**
	 * @param string[] $keepKeys
	 * @return array<string, array{label: string, options: array<string, string>}>
	 */
	public function getServiceOptionGroups(array $keepKeys = []): array
	{
		$groups = [];
		$known = [];

		foreach (\array_keys(self::CATEGORY_CONSENT) as $key) {
			if ($key !== 'essential' && !$this->get("{$key}_enabled")) {
				continue;
			}

			$title = $this->getCategoryTitle($key);
			$options = [$key => "{$title} ({$key})"];

			$services = $this->extractSubCategories($this->getCategoryGroups($key));
			\asort($services);

			foreach ($services as $serviceId => $serviceTitle) {
				$options["{$key}-{$serviceId}"] = "{$title}: {$serviceTitle} ({$key}-{$serviceId})";
			}

			$groups[$key] = ['label' => $title, 'options' => $options];
			$known += $options;
		}

		$stale = [];

		foreach ($keepKeys as $keepKey) {
			$keepKey = (string) $keepKey;

			if ($keepKey !== '' && !isset($known[$keepKey])) {
				$stale[$keepKey] = \sprintf($this->_('%s (nicht mehr konfiguriert)'), $keepKey);
			}
		}

		if (!empty($stale)) {
			$groups['_stale'] = ['label' => $this->_('Nicht mehr konfiguriert'), 'options' => $stale];
		}

		return $groups;
	}

	public function hasUndecidedServices(): bool
	{
		$flatConsent = $this->getFlatConsent();

		if ($flatConsent === null) {
			return false;
		}

		foreach ($this->getOfferedServices() as $key => $serviceIds) {
			foreach ($serviceIds as $serviceId) {
				if (!isset($flatConsent["{$key}-{$serviceId}"])) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $consent
	 * @param int $issuedAt 0 = neue Entscheidung
	 */
	private function storeConsentCookie(array $consent, string $action, ?string $id = null, int $issuedAt = 0): void
	{
		$this->session->removeFor($this, 'once');
		$this->session->removeFor($this, 'status');

		$previousRefresh = (int) ($this->consent['_r'] ?? 0);

		$consent['_id'] = $this->isValidConsentId($id) ? $id : ($this->getConsentId() ?? \bin2hex(\random_bytes(16)));
		$consent['_t'] = $issuedAt > 0 ? $issuedAt : \time();
		$consent['_r'] = \time();

		if ($issuedAt === 0 && $this->gpc) {
			$consent['_gpc'] = true;
		}

		$configHash = $this->ensureConfigHistory();

		if ($configHash !== '') {
			$consent['_c'] = $configHash;
		} else {
			unset($consent['_c']);
		}

		$this->writeConsentCookie($consent);
		$this->applyConsent($consent);

		$logged = $consent;
		unset($logged['_id'], $logged['_t'], $logged['_r'], $logged['_gpc'], $logged['_c']);
		$signals = ($this->gpc ? ' gpc=1' : '') . ($this->dnt ? ' dnt=1' : '') . ($configHash !== '' ? " cfg={$configHash}" : '');

		// Eine unveränderte Bestätigung höchstens einmal pro Cookie-Auffrischung protokollieren
		if ($action !== 'renew' || \time() - $previousRefresh >= self::COOKIE_REFRESH_INTERVAL) {
			$this->saveConsentLog("{$consent['_id']} {$action}{$signals} " . \json_encode($logged, \JSON_THROW_ON_ERROR));
		}
	}

	private function saveConsentLog(string $text): void
	{
		if (!$this->isConsentLogEnabled()) {
			return;
		}

		$this->log->save(self::CONSENT_LOG, $text, ['showUser' => false, 'showURL' => false]);
	}

	/**
	 * Max. einmal täglich (oder erzwungen), mit unverändertem Ablauf: stellt gekürzte Laufzeiten wieder her.
	 */
	private function refreshConsentCookie(bool $force = false): void
	{
		$consent = $this->consent;

		if ($consent === null || \headers_sent() || (!$force && \time() - (int) ($consent['_r'] ?? 0) < self::COOKIE_REFRESH_INTERVAL)) {
			return;
		}

		// Ältere Cookies ohne Zeitstempel: Laufzeit ab jetzt
		$consent['_t'] = (int) ($consent['_t'] ?? 0) ?: \time();
		$consent['_r'] = \time();

		$this->writeConsentCookie($consent);
		$this->consent = $consent;
	}

	/**
	 * @param array<string, mixed> $consent
	 */
	private function writeConsentCookie(array $consent): void
	{
		// Ohne Protokoll braucht das Cookie keine Kennung
		if (!$this->isConsentLogEnabled()) {
			unset($consent['_id'], $consent['_c']);
		}

		$value = \json_encode($consent, \JSON_THROW_ON_ERROR);
		$options = [
			'expires' => (int) $consent['_t'] + self::COOKIE_LIFETIME,
			'path' => '/',
			'secure' => (bool) $this->config->https,
			'httponly' => true,
			'samesite' => 'Lax',
		];

		$domain = $this->getSharedCookieDomain();

		if ($domain !== '') {
			$options['domain'] = $domain;
		}

		$this->expireCookie(self::COOKIE_NAME, $domain);
		\setcookie(self::COOKIE_NAME, $value, $options);

		$_COOKIE[self::COOKIE_NAME] = $value;
	}

	/**
	 * @return string[]
	 */
	private function purgeUnconsentedCookies(): array
	{
		$rules = $this->getCookieRules();
		$denied = $rules['denied'];

		if (empty($denied) || \headers_sent()) {
			return [];
		}

		$allowed = \array_merge($rules['allowed'], $this->getProtectedCookieNames());
		$purged = [];

		foreach ($this->getRawCookieNames() as $name) {
			if ($this->matchesCookiePattern($name, $denied) && !$this->matchesCookiePattern($name, $allowed)) {
				$this->expireCookie($name);
				$purged[] = $name;
			}
		}

		return $purged;
	}

	/**
	 * @return array<int, array{service: ?string, name: string}> service null = notwendig
	 */
	private function getCookieEntries(): array
	{
		if ($this->cookieEntries !== null) {
			return $this->cookieEntries;
		}

		$entries = [];

		foreach (\array_keys(self::CATEGORY_CONSENT) as $key) {
			if ($key !== 'essential' && !$this->get("{$key}_enabled")) {
				continue;
			}

			foreach ($this->parseCategory($key, '') as $group) {
				foreach ($group['cookies'] as $cookie) {
					// Browser-Speicher lässt sich serverseitig nicht löschen
					if ($cookie['type'] === 'Cookie' && $this->isValidCookiePattern($cookie['name'])) {
						$entries[] = ['service' => $key === 'essential' ? null : "{$key}-{$group['id']}", 'name' => $cookie['name']];
					}
				}
			}
		}

		return $this->cookieEntries = $entries;
	}

	/**
	 * @return array{allowed: string[], denied: string[]}
	 */
	private function getCookieRules(): array
	{
		$rules = ['allowed' => [], 'denied' => []];
		$onceKeys = $this->getOnceKeys();

		foreach ($this->getCookieEntries() as $entry) {
			$allowed = $entry['service'] === null ||
				\in_array($entry['service'], $this->consentedCategories, true) ||
				\in_array($entry['service'], $onceKeys, true);
			$rules[$allowed ? 'allowed' : 'denied'][] = $entry['name'];
		}

		return $rules;
	}

	private function isValidCookiePattern(string $pattern): bool
	{
		return \trim($pattern, '*') !== '' && !\preg_match('/[\s;=,]/', $pattern);
	}

	/**
	 * @param string[] $patterns `*` = beliebige Zeichen, z.B. "_ga_*"
	 */
	private function matchesCookiePattern(string $name, array $patterns): bool
	{
		foreach ($patterns as $pattern) {
			if (\preg_match('/^' . \str_replace('\*', '.*', \preg_quote($pattern, '/')) . '$/', $name)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string[]
	 */
	private function getProtectedCookieNames(): array
	{
		$names = [self::COOKIE_NAME];
		$sessionName = (string) $this->config->sessionName;

		// Ohne eigenen Namen nutzt ProcessWire für HTTPS-Sessions sessionName + 's'
		$secureName = (string) $this->config->sessionNameSecure ?: "{$sessionName}s";

		foreach ([$sessionName, $secureName] as $name) {
			if ($name !== '') {
				$names[] = $name;
				$names[] = "{$name}_challenge";
			}
		}

		return $names;
	}

	/**
	 * Anders als `$_COOKIE` mit Punkten und Leerzeichen (z.B. Matomos `_pk_id.1.abcd`).
	 *
	 * @return string[]
	 */
	private function getRawCookieNames(): array
	{
		$names = [];

		foreach (\explode(';', (string) ($_SERVER['HTTP_COOKIE'] ?? '')) as $pair) {
			$name = \trim(\explode('=', $pair, 2)[0]);

			if ($this->isValidCookieName($name)) {
				$names[$name] = $name;
			}
		}

		return \array_values($names);
	}

	/**
	 * Nur lokale Pfade (Open-Redirect-Schutz): `cmnstr-redirect`, Referer, sonst Startseite.
	 */
	private function getConsentRedirectUrl(): string
	{
		$candidates = [
			$this->getPostString('cmnstr-redirect'),
			(string) ($_SERVER['HTTP_REFERER'] ?? ''),
		];

		foreach ($candidates as $candidate) {
			$parts = \parse_url($candidate);

			if (!$parts || empty($parts['path'])) {
				continue;
			}

			if (isset($parts['host']) && \strtolower($parts['host']) !== $this->getHost()) {
				continue;
			}

			$path = $parts['path'];

			// Protokoll-relative URLs ("//evil.tld") und Backslash-Tricks ausschließen
			if ($path[0] !== '/' || \preg_match('#^/[/\\\\]#', $path) || \str_contains($path, '/_cmnstr/')) {
				continue;
			}

			\parse_str($parts['query'] ?? '', $query);
			unset($query[self::SETTINGS_PARAM]);

			return empty($query) ? $path : "{$path}?" . \http_build_query($query);
		}

		return $this->config->urls->root;
	}

	/**
	 * Hook: Der Template-Cache würde Masken, Nonces und Consent-Daten für alle Besucher speichern.
	 */
	public function hookBeforeRenderPage(HookEvent $event): void
	{
		if ($this->config->admin || (bool) $this->get('keep_template_cache')) {
			return;
		}

		/** @var Page $page */
		$page = $event->object;

		if (\in_array($page->template->name, self::EXCLUDED_TEMPLATES, true) || (int) $page->template->cache_time < 1) {
			return;
		}

		$options = $event->arguments(0);
		$options = \is_array($options) ? $options : [];
		$options['allowCache'] = false;
		$event->arguments(0, $options);
	}

	public function hookTemplateFileRender(HookEvent $event): void
	{
		$output = $event->return;

		if (!\is_string($output) || !\str_contains($output, self::BANNER_REGION_ID)) {
			return;
		}

		if (!$this->config->useMarkupRegions || $this->config->admin || $this->config->ajax || !(bool) $this->get('is_active')) {
			return;
		}

		$page = $this->wire()->page;

		if (!$page instanceof Page || !$page->id || \in_array($page->template->name, self::EXCLUDED_TEMPLATES, true)) {
			return;
		}

		$contentType = (string) $page->template->contentType;

		if ($contentType !== '' && \stripos($contentType, 'html') === false) {
			return;
		}

		$templateFile = (string) $page->template->filename;
		$relativeFile = \substr($templateFile, \strlen((string) $this->config->paths->root));

		if ($relativeFile === '' || !\str_ends_with((string) $event->object->get('filename'), '/' . \ltrim($relativeFile, '/'))) {
			return;
		}

		$markup = \preg_replace('~<!--.*?-->|<(script|style)\b.*?</\1\s*>~is', '', $output) ?? $output;
		$regionPattern = '~<pw-region\b[^>]*\bid=(["\'])' . \preg_quote(self::BANNER_REGION_ID, '~') . '\1~i';

		if (\preg_match($regionPattern, $markup) !== 1) {
			return;
		}

		$event->return = '<pw-append id="' . self::BANNER_REGION_ID . '">' . $this->renderBanner() . '</pw-append>' . $output;
	}

	public function hookPageRender(HookEvent $event): void
	{
		/** @var Page $page */
		$page = $event->object;
		$output = $this->replaceConsentIdPlaceholders((string) $event->return);

		if (!$this->config->admin && !$this->config->ajax && !$this->securityHeadersSent) {
			if (!\headers_sent()) {
				$mainPage = $this->wire()->page;

				$this->sendCacheHeaders();
				$this->sendSecurityHeaders($mainPage instanceof Page && $mainPage->id ? $mainPage : $page);
			} else {
				$marker = 'cmnstr_headers_late';

				if ($this->wire()->cache->get($marker) === null) {
					$this->wire()->cache->save($marker, 1, 3600);
					$this->log->save(self::LOG, 'Security headers not sent, output has already started');
				}
			}

			$this->securityHeadersSent = true;
		}

		if (!\in_array($page->template->name, self::EXCLUDED_TEMPLATES, true)) {
			$output = $this->replaceConsentIdPlaceholders($this->injectFrontendCode($output));
		}

		$event->return = $output;
	}

	public function renderConsentIdPlaceholder(): string
	{
		$label = $this->sanitizer->entities($this->text('consent_id'));

		return self::CONSENT_ID_BEGIN
			. "<p class='cmnstr-consent-id'>{$label}: <code>" . self::CONSENT_ID_VALUE . '</code></p>'
			. self::CONSENT_ID_END;
	}

	private function replaceConsentIdPlaceholders(string $output): string
	{
		if (!\str_contains($output, self::CONSENT_ID_BEGIN)) {
			return $output;
		}

		$id = $this->isConsentLogEnabled() ? $this->getConsentId() : null;
		$pattern = '/' . \preg_quote(self::CONSENT_ID_BEGIN, '/') . '(.*?)' . \preg_quote(self::CONSENT_ID_END, '/') . '/s';

		return \preg_replace_callback(
			$pattern,
			static fn(array $match): string => $id === null ? '' : \str_replace(self::CONSENT_ID_VALUE, $id, $match[1]),
			$output
		) ?? $output;
	}

	private function injectFrontendCode(string $output): string
	{
		$bannerInRegion = \str_contains($output, self::BANNER_ROOT);

		$headPos = \strpos($output, '</head>');
		$bodyEndPos = \strrpos($output, '</body>');
		$hasBodyStart = \preg_match('/<body\b[^>]*>/i', $output, $bodyStart, \PREG_OFFSET_CAPTURE) === 1;

		if ($headPos === false && $bodyEndPos === false && !$hasBodyStart) {
			return $output;
		}

		$isActive = (bool) $this->get('is_active');
		$head = '';
		$body = '';

		if ($isActive) {
			if ((bool) $this->get('use_stylesheet')) {
				$head .= "<link rel='stylesheet' nonce='{$this->cspNonce}' href='{$this->getAssetUrl('CookieMonster.css')}'>";
			}

			$head .= "<script nonce='{$this->cspNonce}' src='{$this->getAssetUrl('CookieMonster.js')}' type='module'></script>";
		}

		if ($this->get('google_site_verification')) {
			$head .= $this->addGSVHeadCode();
		}

		if ($this->get('gtm_id') || $this->get('ga_property_id')) {
			$head .= $this->addConsentDefaultCode();
		}

		foreach ($this->getActiveTagMethods() as $method) {
			$head .= $this->$method();
			$body .= $this->$method(true);
		}

		// Von hinten nach vorne einfügen, damit die ermittelten Positionen gültig bleiben
		if ($bodyEndPos !== false) {
			$output = \substr_replace($output, $body, $bodyEndPos, 0);
		}

		if ($isActive && $hasBodyStart && !$bannerInRegion) {
			$output = \substr_replace($output, $this->renderBanner(), $bodyStart[0][1] + \strlen($bodyStart[0][0]), 0);
		}

		if ($headPos !== false) {
			$output = \substr_replace($output, $head, $headPos, 0);
		}

		return $output;
	}

	private function getAssetUrl(string $filename): string
	{
		$mtime = (int) \filemtime($this->config->paths->{$this} . "assets/{$filename}");

		return $this->config->urls->{$this} . "assets/{$filename}?v={$mtime}";
	}

	/**
	 * @return string[]
	 */
	private function getActiveTagMethods(): array
	{
		$matomoCookieless = \in_array($this->get('matomo_cookieless'), ['cookieless', 'fallback'], true);

		$tags = [
			'addGATrackingCode' => $this->get('ga_property_id') && $this->isTagUnlocked('ga'),
			'addGTMTrackingCode' => $this->get('gtm_id') && $this->isTagUnlocked('gtm'),
			'addLinkedInTrackingCode' => $this->get('linkedin_insight_id') && $this->isTagUnlocked('linkedin_insight'),
			'addMatomoTrackingCode' => $this->get('matomo_enabled') && ($matomoCookieless || $this->isTagUnlocked('matomo')),
			'addMetaTrackingCode' => $this->get('meta_pixel_id') && $this->isTagUnlocked('meta_pixel'),
		];

		return \array_keys(\array_filter($tags));
	}

	private function renderBanner(): string
	{
		$imprintPageId = (int) $this->get('imprint_page');
		$privacyPageId = (int) $this->get('privacy_page');

		/** @var Page|NullPage $imprintPage */
		$imprintPage = $this->pages->get($imprintPageId);
		/** @var Page|NullPage $privacyPage */
		$privacyPage = $this->pages->get($privacyPageId);

		$isLegalPage = $this->page->id === $imprintPageId || $this->page->id === $privacyPageId;
		$decided = $this->consent !== null && !$this->hasUndecidedServices();
		$body = $this->getText('infotext');

		$data = [
			'title' => $this->getText('titletext'),
			'label' => $this->getText('infolabel'),
			'body' => $body,
			'details' => $this->getText('infodetails') ?: $body,
			'open' => !$decided && !$isLegalPage,
			'settingsOpen' => $this->isSettingsRequest(),
			'status' => $this->takeStatusMessage(),
			'signalNotice' => $this->getPrivacySignalNotice(),
			'buttonEdit' => $this->getText('buttontext_edit'),
			'buttonConfirm' => $this->getText('buttontext_confirm'),
			'buttonDecline' => $this->getText('buttontext_decline'),
			'buttonAccept' => $this->getText('buttontext_accept'),
			'imprintPage' => $imprintPage,
			'privacyPage' => $privacyPage,
			'categories' => $this->buildCategoriesData(),
		];

		return $this->renderTemplate('banner.php', $data + $this->getConsentFormData());
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function buildCategoriesData(): array
	{
		$categories = [];

		foreach (\array_keys(self::CATEGORY_CONSENT) as $key) {
			$title = $this->getCategoryTitle($key);
			$groups = $this->getCategoryGroups($key);

			$categories[] = [
				'key' => $key,
				'enabled' => $key === 'essential' || (bool) $this->get("{$key}_enabled"),
				'title' => $title,
				'description' => $this->getText("{$key}_description") ?: $title,
				'cookies' => empty($groups) ? '' : (string) $this->get("{$key}_cookies"),
				'groups' => $groups,
				'checked' => (bool) $this->isUnlocked($key),
			];
		}

		return $categories;
	}

	private function getCategoryTitle(string $categoryKey): string
	{
		return $this->getText("{$categoryKey}_title") ?: ($this->getDefaultCategoryTitles()[$categoryKey] ?? $categoryKey);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function parseCategory(string $categoryKey, string $suffix): array
	{
		$memoKey = "{$categoryKey}|{$suffix}";

		if (!isset($this->categoryGroups[$memoKey])) {
			$this->categoryGroups[$memoKey] = $this->parseCookieData((string) $this->get("{$categoryKey}_cookies{$suffix}"), $categoryKey);
		}

		return $this->categoryGroups[$memoKey];
	}

	/**
	 * Struktur (IDs, Cookie-Namen) aus der Standardsprache; Texte der Sprache, wo ausgefüllt.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function getCategoryGroups(string $categoryKey, ?string $suffix = null): array
	{
		$suffix ??= $this->getLanguageSuffix();
		$memoKey = "{$categoryKey}|{$suffix}";

		if (isset($this->localizedGroups[$memoKey])) {
			return $this->localizedGroups[$memoKey];
		}

		$groups = $this->parseCategory($categoryKey, '');

		if ($suffix !== '') {
			$groups = $this->overlayGroups($groups, $this->parseCategory($categoryKey, $suffix));
		}

		return $this->localizedGroups[$memoKey] = $this->finalizeGroups($groups);
	}

	/**
	 * @param array<int, array<string, mixed>> $base
	 * @param array<int, array<string, mixed>> $translated
	 * @return array<int, array<string, mixed>>
	 */
	private function overlayGroups(array $base, array $translated): array
	{
		$byId = [];

		foreach ($translated as $group) {
			$byId[$group['id']] = $group;
		}

		$sameGroupCount = \count($base) === \count($translated);

		foreach ($base as $index => $group) {
			$match = $byId[$group['id']] ?? ($sameGroupCount ? $translated[$index] : null);

			if ($match === null) {
				continue;
			}

			foreach (['title', 'description', 'notice'] as $field) {
				if ($match[$field] !== '') {
					$base[$index][$field] = $match[$field];
				}
			}

			$sameCookieCount = \count($match['cookies']) === \count($group['cookies']);

			foreach ($group['cookies'] as $cookieIndex => $cookie) {
				$other = null;

				foreach ($match['cookies'] as $candidate) {
					if ($candidate['name'] === $cookie['name']) {
						$other = $candidate;
						break;
					}
				}

				$other ??= $sameCookieCount ? $match['cookies'][$cookieIndex] : null;

				if ($other === null) {
					continue;
				}

				foreach (['provider', 'purpose', 'duration'] as $field) {
					if ($other[$field] !== '') {
						$base[$index]['cookies'][$cookieIndex][$field] = $other[$field];
					}
				}
			}
		}

		return $base;
	}

	/**
	 * @param array<int, array<string, mixed>> $groups
	 * @return array<int, array<string, mixed>>
	 */
	private function finalizeGroups(array $groups): array
	{
		foreach ($groups as $index => $group) {
			foreach ($group['cookies'] as $cookieIndex => $cookie) {
				if ($cookie['duration'] === '') {
					$groups[$index]['cookies'][$cookieIndex]['duration'] = \in_array($cookie['type'], ['Cookie', 'sessionStorage'], true)
						? $this->text('duration_session')
						: $this->text('duration_undefined');
				}
			}
		}

		return $groups;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function parseCookieData(string $text, string $parentKey = ''): array
	{
		$groups = [];

		// Standard-Gruppe für Cookies, die vor der ersten Definition stehen
		$currentGroup = ['id' => 'default', 'title' => '', 'description' => '', 'notice' => '', 'cookies' => []];

		// Gleiche ID mehrfach: Cookies wandern in die erste Gruppe (sonst doppelte Checkboxen und HTML-IDs)
		$addGroup = static function (array $group) use (&$groups): void {
			if (empty($group['cookies']) && $group['id'] === 'default') {
				return;
			}

			foreach ($groups as $index => $existing) {
				if ($existing['id'] === $group['id']) {
					foreach (['title', 'description', 'notice'] as $field) {
						if ($existing[$field] === '') {
							$groups[$index][$field] = $group[$field];
						}
					}
					$groups[$index]['cookies'] = \array_merge($existing['cookies'], $group['cookies']);

					return;
				}
			}

			$groups[] = $group;
		};

		foreach (\explode("\n", $text) as $line) {
			$line = \trim($line);

			if ($line === '') {
				continue;
			}

			// Gruppen-Start: "---id|Titel|Beschreibung|Hinweis"
			if (\str_starts_with($line, '---')) {
				$addGroup($currentGroup);

				$groupParts = \array_pad(\array_map(\trim(...), \explode('|', \substr($line, 3))), 4, '');
				$groupId = $this->normalizeServiceId($groupParts[0], $parentKey);

				// Ohne ID aus dem Titel ableiten, sonst gehört die Gruppe zu "default"
				if ($groupId === '') {
					$groupId = $this->normalizeServiceId($this->sanitizer->pageName($groupParts[1], Sanitizer::translate), $parentKey);
				}

				$currentGroup = [
					'id' => $groupId !== '' ? $groupId : 'default',
					'title' => $groupParts[1],
					'description' => $groupParts[2],
					'notice' => $groupParts[3],
					'cookies' => [],
				];

				continue;
			}

			// Cookie-Zeile: "Name|Anbieter|Zweck|Dauer", Browser-Speicher mit „localStorage“ o.ä. als Dauer
			$columns = \array_pad(\array_map(\trim(...), \explode('|', $line)), 4, '');
			$cookie = \array_combine(['name', 'provider', 'purpose', 'duration'], \array_slice($columns, 0, 4));
			$cookie['type'] = 'Cookie';
			$storage = ['sessionstorage' => 'sessionStorage', 'localstorage' => 'localStorage', 'indexeddb' => 'IndexedDB'];
			$type = $storage[\strtolower($cookie['duration'])] ?? null;

			if ($type !== null) {
				$cookie['type'] = $type;
				$cookie['duration'] = '';
			}

			$currentGroup['cookies'][] = $cookie;
		}

		$addGroup($currentGroup);

		return $groups;
	}

	public function addGSVHeadCode(): string
	{
		return $this->renderTemplate('head/gsv.php', [
			'verificationString' => (string) $this->get('google_site_verification'),
		]);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function renderTag(string $name, array $data, bool $noScript = false): string
	{
		return $this->renderTemplate("tags/{$name}.php", $data + [
			'cspNonce' => $this->cspNonce,
			'noScript' => $noScript,
		]);
	}

	public function addGATrackingCode(bool $noScript = false): string
	{
		return $this->renderTag('ga', ['propertyId' => (string) $this->get('ga_property_id')], $noScript);
	}

	/**
	 * Muss vor GTM/gtag stehen, damit alle Google-Tags den Consent-Zustand kennen.
	 */
	public function addConsentDefaultCode(): string
	{
		return $this->renderTag('consent', ['googleConsentStates' => $this->getGoogleConsentStates()]);
	}

	public function addGTMTrackingCode(bool $noScript = false): string
	{
		return $this->renderTag('gtm', ['gtmId' => (string) $this->get('gtm_id')], $noScript);
	}

	public function addLinkedInTrackingCode(bool $noScript = false): string
	{
		return $this->renderTag('linkedin', ['insightId' => (string) $this->get('linkedin_insight_id')], $noScript);
	}

	public function addMatomoTrackingCode(bool $noScript = false): string
	{
		$cookieless = (string) $this->get('matomo_cookieless');

		return $this->renderTag('matomo', [
			'matomoUrl' => (string) $this->get('matomo_url'),
			'documentTitle' => (string) $this->get('matomo_documenttitle'),
			'siteId' => (string) $this->get('matomo_siteid'),
			'shareTracking' => (bool) $this->get('matomo_sharetracking'),
			'useCookies' => $cookieless === '' || ($cookieless === 'fallback' && $this->isTagUnlocked('matomo')),
			'shareDomain' => (string) $this->get('matomo_sharedomain'),
		], $noScript);
	}

	public function addMetaTrackingCode(bool $noScript = false): string
	{
		return $this->renderTag('meta', [
			'pixel' => (string) $this->get('meta_pixel_id'),
			'event' => (string) $this->get('meta_pixel_event'),
		], $noScript);
	}

	/**
	 * @param Page $page Hauptseite des Requests, nicht eine intern gerenderte Seite
	 */
	private function sendSecurityHeaders(Page $page): void
	{
		// FormBuilder-Formulare werden per iFrame auch auf anderen Websites eingebettet
		$excluded = \in_array($page->template->name, self::EXCLUDED_TEMPLATES, true);

		$this->sendHSTSHeader();
		$this->sendCSPHeader(!$excluded);

		$headers = [
			'Referrer-Policy' => $this->getAllowedSetting('referrer_policy', self::REFERRER_POLICIES, 'strict-origin-when-cross-origin'),
			'X-Content-Type-Options' => $this->get('x_content_type_options_enabled') ? 'nosniff' : '',
			// Würden das Einbetten bzw. Pop-ups (FormBuilder) auf fremden Websites brechen
			'Cross-Origin-Opener-Policy' => $excluded ? '' : $this->getAllowedSetting('coop_policy', self::COOP_POLICIES, 'same-origin-allow-popups'),
			'Cross-Origin-Embedder-Policy' => $this->getAllowedSetting('coep_policy', self::COEP_POLICIES, ''),
			'Cross-Origin-Resource-Policy' => $excluded ? '' : $this->getAllowedSetting('corp_policy', self::CORP_POLICIES, 'same-origin'),
			'Permissions-Policy' => $this->get('permissions_policy_enabled') ? $this->toHeaderValue($this->getStringSetting('permissions_policy')) : '',
		];

		foreach ($headers as $name => $value) {
			if ($value !== '') {
				\header("{$name}: {$value}");
			}
		}

		if ($this->get('remove_powered_by')) {
			\header_remove('X-Powered-By');
		}
	}

	private function getStringSetting(string $name): string
	{
		$value = $this->get($name);

		return \is_string($value) ? $value : '';
	}

	/**
	 * @param string[] $allowed
	 */
	private function getAllowedSetting(string $name, array $allowed, string $default): string
	{
		$value = $this->get($name);

		return \is_string($value) && \in_array($value, $allowed, true) ? $value : $default;
	}

	private function sendEndpointHeaders(): void
	{
		\header('Cache-Control: no-store');
		\header('X-Robots-Tag: noindex, nofollow');
		\header('X-Content-Type-Options: nosniff');
	}

	private function sendCacheHeaders(): void
	{
		$vary = \array_keys(\array_filter([
			'Cookie' => true,
			'Sec-GPC' => $this->get('respect_gpc') ?? false,
			'DNT' => $this->get('respect_dnt') ?? false,
		]));

		\header('Vary: ' . \implode(', ', $vary), false);

		// Eine per 304 bestätigte Kopie hätte die Nonce der alten Antwort, der CSP-Header aber eine neue
		\header_remove('Last-Modified');
		\header_remove('ETag');

		// no-store (z. B. ProcessWire für eingeloggte Besucher) nie abschwächen
		foreach (\headers_list() as $header) {
			if (\stripos($header, 'Cache-Control:') === 0 && \stripos($header, 'no-store') !== false) {
				return;
			}
		}

		\header_remove('Expires');
		\header('Cache-Control: private, no-cache');
	}

	private function usesCspNonce(): bool
	{
		return (bool) $this->get('csp_enabled') && \str_contains((string) $this->get('csp_policy'), 'nonce-proxy');
	}

	private function sendHSTSHeader(): void
	{
		if (!(bool) $this->get('hsts_enabled') || !$this->config->https) {
			return;
		}

		// 0 ist erlaubt und nimmt HSTS im Browser zurück
		$maxAge = $this->get('hsts_max_age');
		$maxAge = $maxAge === null || $maxAge === '' ? 31536000 : \max(0, (int) $maxAge);
		$includeSubDomains = (bool) $this->get('hsts_include_subdomains');
		$parts = ["max-age={$maxAge}"];

		if ($includeSubDomains) {
			$parts[] = 'includeSubDomains';
		}

		// Voraussetzung der Preload-Liste (hstspreload.org)
		if ((bool) $this->get('hsts_preload') && $includeSubDomains && $maxAge >= 31536000) {
			$parts[] = 'preload';
		}

		\header('Strict-Transport-Security: ' . \implode('; ', $parts));
	}

	private function sendCSPHeader(bool $framing): void
	{
		$enabled = (bool) $this->get('csp_enabled');
		$reportOnly = (bool) $this->get('csp_report_only');
		$frameAncestors = $framing ? $this->getFrameAncestorsValue() : '';

		if ($enabled) {
			$policy = \str_replace('nonce-proxy', "nonce-{$this->cspNonce}", $this->getStringSetting('csp_policy'));
			// block-all-mixed-content ist veraltet und neben upgrade-insecure-requests wirkungslos; Report-Only ignoriert upgrade-insecure-requests
			$parts = \array_values(\array_filter(
				\array_map(\trim(...), \explode(';', $this->toHeaderValue($policy))),
				static function (string $directive) use ($reportOnly): bool {
					return $directive !== '' &&
						\strcasecmp($directive, 'block-all-mixed-content') !== 0 &&
						!($reportOnly && \strcasecmp($directive, 'upgrade-insecure-requests') === 0);
				}
			));

			// Eigene Meldeadressen ersetzen die der Richtlinie; Report-Only ignoriert frame-ancestors
			$parts = \array_values(\preg_grep($reportOnly ? '/^(report-uri|report-to|frame-ancestors)\b/i' : '/^(report-uri|report-to)\b/i', $parts, \PREG_GREP_INVERT));

			if (!$reportOnly && $frameAncestors !== '' && !\preg_grep('/^frame-ancestors\b/i', $parts)) {
				$parts[] = "frame-ancestors {$frameAncestors}";
			}

			$parts = \array_merge($parts, $this->getCspReportDirectives());

			if (!empty($parts)) {
				$headerName = $reportOnly ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
				\header($headerName . ': ' . \implode('; ', $parts));
			}
		}

		// Schutz vor dem Einbetten auch ohne durchgesetzte CSP
		if ((!$enabled || $reportOnly) && $frameAncestors !== '') {
			\header("Content-Security-Policy: frame-ancestors {$frameAncestors}");
		}
	}

	/**
	 * @return string[]
	 */
	private function getCspReportDirectives(): array
	{
		$custom = $this->getStringSetting('csp_report_to');
		$reportUri = $this->isValidReportUrl($custom) ? $custom : '';
		$endpoint = $reportUri;

		if ($reportUri === '') {
			$reportUri = \rtrim($this->config->urls->httpRoot, '/') . self::REPORT_PATH;
			$endpoint = $reportUri;
		}

		$directives = ["report-uri {$reportUri}"];

		// report-to verlangt einen absoluten HTTPS-Endpunkt, report-uri bleibt als Fallback
		if (\str_starts_with($endpoint, 'https://')) {
			\header("Reporting-Endpoints: csp-endpoint=\"{$endpoint}\"");
			$directives[] = 'report-to csp-endpoint';
		}

		return $directives;
	}

	private function isValidReportUrl(string $url): bool
	{
		return $url !== '' && \preg_match('#^(https://|/(?!/))[^\s;,"\'\\\\<>]+$#', $url) === 1;
	}

	private function getFrameAncestorsValue(): string
	{
		return \implode(' ', $this->getFrameSources());
	}

	/**
	 * Eigene Herkünfte werden geprüft; ohne gültigen Eintrag gilt 'none', nie "kein Schutz".
	 *
	 * @return string[]
	 */
	private function getFrameSources(): array
	{
		$policy = $this->getAllowedSetting('framing_policy', self::FRAMING_POLICIES, 'self');

		if ($policy !== 'custom') {
			return ["'{$policy}'"];
		}

		$sources = [];

		foreach (\preg_split('/\s+/', \trim($this->getStringSetting('framing_custom_origins')), -1, \PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
			if ($token === "'self'" || \preg_match(self::FRAME_SOURCE_PATTERN, $token) === 1) {
				$sources[$token] = $token;
			}
		}

		return empty($sources) ? ["'none'"] : \array_values($sources);
	}

	private function toHeaderValue(string $value): string
	{
		return \trim((string) \preg_replace('/\s+/', ' ', $value));
	}

	/**
	 * @param array<string, mixed> $category leer = alle aktivierten Kategorien
	 */
	public function renderCookieTable(array $category = []): string
	{
		return $this->renderCookieTemplate('table.php', $category);
	}

	/**
	 * @param array<string, mixed> $category leer = alle aktivierten Kategorien
	 * @param bool $interactive Checkboxen je Dienst; nur im Einstellungsdialog sinnvoll (Formular, eindeutige IDs)
	 */
	public function renderCookieList(array $category = [], bool $interactive = false): string
	{
		return $this->renderCookieTemplate('list.php', $category, $interactive);
	}

	/**
	 * @param array<string, mixed> $category leer = alle aktivierten Kategorien
	 */
	private function renderCookieTemplate(string $template, array $category = [], bool $interactive = false): string
	{
		$categories = !empty($category['cookies'])
			? [[
				'key' => (string) ($category['key'] ?? ''),
				'title' => (string) ($category['title'] ?? ''),
				'description' => (string) ($category['description'] ?? ''),
				'groups' => $category['groups'] ?? $this->finalizeGroups($this->parseCookieData((string) $category['cookies'], (string) ($category['key'] ?? ''))),
			]]
			: \array_filter($this->buildCategoriesData(), static function (array $data): bool {
				return $data['enabled'];
			});

		$cookies = [];

		foreach ($categories as $data) {
			if (!empty($data['groups'])) {
				$cookies[] = [
					'key' => $data['key'],
					'title' => $data['title'],
					'description' => $data['description'],
					'groups' => $data['groups'],
				];
			}
		}

		if (empty($cookies)) {
			return '';
		}

		return $this->renderTemplate($template, [
			'headers' => [
				'name' => $this->text('column_name'),
				'type' => $this->text('column_type'),
				'provider' => $this->text('column_provider'),
				'purpose' => $this->text('column_purpose'),
				'duration' => $this->text('column_duration'),
			],
			'cookies' => $cookies,
			'interactive' => $interactive,
		]);
	}

	public function categoryExists(string $categoryKey): bool
	{
		if ($categoryKey === '') {
			return false;
		}

		[$parentKey, $serviceId] = $this->splitServiceKey($categoryKey);

		if (!isset(self::CATEGORY_CONSENT[$parentKey])) {
			return false;
		}

		if ($serviceId === null) {
			return true;
		}

		return \in_array($serviceId, $this->getServiceIds($parentKey), true);
	}

	public function isCategoryActive(string $categoryKey): bool
	{
		foreach ($this->consentedCategories as $key) {
			if ($key === $categoryKey || \str_starts_with($key, "{$categoryKey}-")) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string|string[]|null $services
	 */
	public function maskContent(string $html, string|array|null $services = null): string
	{
		$any = $this->isMatchAny($services);
		$maskId = 'cmnstr-mask-' . ++$this->maskCount;

		if ($any && $this->isUnlocked($services, true)) {
			return $this->anchorUnlocked($maskId, $html);
		}

		// Entfernte Dienste degradieren auf Kategorie bzw. „alle“ statt dauerhaft zu sperren
		$locked = $this->getLockedServiceKeys($services, $services === null && \func_num_args() >= 2);
		$remaining = \array_values(\array_diff($locked, $this->getOnceKeys()));

		// Einmal freigegebene Dienste öffnen nur die Maske, nicht den Consent
		$locked = $any && \count($remaining) < \count($locked) ? [] : $remaining;

		if (empty($locked)) {
			return $this->anchorUnlocked($maskId, $html);
		}

		$titles = [];
		$notices = [];

		foreach ($locked as $key) {
			if ($key === self::ALL_SERVICES) {
				$titles[] = $this->sanitizer->entities($this->getServiceLabel($key));
				continue;
			}

			[$mainCategory, $serviceId] = $this->splitServiceKey($key);
			$title = $this->getCategoryTitle($mainCategory);

			if ($serviceId !== null) {
				$groups = $this->getCategoryGroups($mainCategory);
				$index = \array_search($serviceId, \array_column($groups, 'id'), true);
				$group = $index === false ? [] : $groups[$index];

				$notices[] = (string) ($group['notice'] ?? '');
				$title .= ' (' . (($group['title'] ?? '') ?: $serviceId) . ')';
			}

			$titles[] = $this->sanitizer->entities($title);
		}

		// Hinweis des Dienstes nur, wenn genau ein Eintrag fehlt; sonst der Info-Text
		$single = \count($locked) === 1;
		$serviceNames = \implode(', ', $titles);
		$lastTitle = \array_pop($titles);
		$categoryTitle = empty($titles)
			? "<strong>{$lastTitle}</strong>"
			: '<strong>' . \implode('</strong>, <strong>', $titles) . '</strong> ' . ($any ? $this->text('or') : $this->text('and')) . " <strong>{$lastTitle}</strong>";

		$prompt = ($single ? ($notices[0] ?? '') : '') ?: \str_replace(
			'{category}',
			$categoryTitle,
			$this->getText('mask_prompt')
		);

		/** @var Page|NullPage $privacyPage */
		$privacyPage = $this->pages->get((int) $this->get('privacy_page'));

		return $this->renderTemplate('mask.php', $this->getConsentFormData() + [
			'maskId' => $maskId,
			'serviceNames' => $serviceNames,
			'category' => \implode(' ', $locked),
			'matchAny' => $any && \count($locked) > 1,
			'buttonEdit' => $this->getText('buttontext_edit'),
			'buttonRequired' => $this->getText('mask_buttontext_required', $this->text('mask_allow')),
			'buttonAll' => $this->getText('mask_buttontext_all', $this->text('mask_all')),
			'buttonOnce' => $this->isOnceAllowed($locked) ? $this->getText('mask_buttontext_once', $this->text('mask_once')) : '',
			'privacyPage' => $privacyPage,
			'prompt' => $prompt,
			'info' => $this->getText('mask_info'),
		]);
	}

	private function anchorUnlocked(string $maskId, string $html): string
	{
		if ($this->session->getFor($this, 'saved') !== $maskId) {
			return $html;
		}

		return "<span id=\"{$maskId}\" tabindex=\"-1\"></span>{$html}";
	}

	private function getText(string $name, string $default = ''): string
	{
		$lang = $this->getLanguageSuffix();

		return (string) $this->get("{$name}{$lang}") ?: (string) $this->get($name) ?: $default;
	}

	public function text(string $key): string
	{
		$languageId = (int) ($this->user->language?->id ?? 0);

		if (!isset($this->texts[$languageId])) {
			$this->texts[$languageId] = $this->texts();
		}

		return $this->texts[$languageId][$key] ?? $key;
	}

	/**
	 * @return array<string, string>
	 */
	private function texts(): array
	{
		return [
			'active' => $this->_('Aktiv'),
			'pending' => $this->_('(wird deaktiviert)'),
			'scope_all' => $this->_('(alle Dienste)'),
			'dialog_label' => $this->_('Cookie-Auswahl'),
			'legal_nav' => $this->_('Rechtliche Hinweise'),
			'close' => $this->_('Schließen'),
			'saved' => $this->_('Ihre Cookie-Einstellungen wurden gespeichert.'),
			'saved_once' => $this->_('Der Inhalt wurde freigegeben. Die Freigabe gilt bis zum Ende der Sitzung.'),
			'gpc_notice' => $this->_('Ihr Browser sendet das Signal „Global Privacy Control“. {categories} bleiben daher aus, können aber einzeln aktiviert werden.'),  // {categories} nicht ändern
			'dnt_notice' => $this->_('Ihr Browser sendet „Do Not Track“. Nicht notwendige Dienste laden wir ohnehin nur mit Ihrer Einwilligung.'),
			'category_essential' => $this->_('Notwendige Cookies'),
			'category_functional' => $this->_('Funktionelle Cookies'),
			'category_statistics' => $this->_('Statistik-Cookies'),
			'category_marketing' => $this->_('Marketing-Cookies'),
			'category_external' => $this->_('Externe Inhalte'),
			'all_categories' => $this->_('alle Kategorien'),
			'mask_allow' => $this->_('Zulassen'),
			'mask_once' => $this->_('Einmal laden'),
			'mask_all' => $this->_('Alle akzeptieren'),
			'or' => $this->_('oder'),
			'and' => $this->_('und'),
			'list_show' => $this->_('Cookie-Informationen anzeigen'),
			'list_show_for' => $this->_('Cookie-Informationen anzeigen: %s'),  // %s nicht ändern
			'table_label' => $this->_('Cookie-Informationen'),
			'column_name' => $this->_('Name'),
			'column_type' => $this->_('Typ'),
			'column_provider' => $this->_('Anbieter'),
			'column_purpose' => $this->_('Zweck'),
			'column_duration' => $this->_('Dauer'),
			'duration_session' => $this->_('Sitzung'),
			'duration_undefined' => $this->_('Unbestimmt'),
			'consent_id' => $this->_('Ihre Consent-ID'),
		];
	}

	private function getLanguageSuffix(): string
	{
		if (!$this->languages) {
			return '';
		}

		/** @var Language $userLanguage */
		$userLanguage = $this->user->language;

		return $userLanguage->isDefault() ? '' : '__' . $userLanguage->id;
	}

	/**
	 * @return array{0: string, 1: ?string}
	 */
	private function splitServiceKey(string $key): array
	{
		$parts = \explode('-', $key, 2);

		return [$parts[0], $parts[1] ?? null];
	}

	private function normalizeServiceId(string $groupId, string $parentKey): string
	{
		$id = \trim($groupId);

		if ($parentKey !== '' && \str_starts_with($id, "{$parentKey}-")) {
			$id = \substr($id, \strlen($parentKey) + 1);
		}

		// Nur Zeichen, die PHP in POST-Feldnamen und HTML-IDs unverändert lässt
		return (string) \preg_replace('/[^A-Za-z0-9_-]/', '_', $id);
	}

	/**
	 * @param array<int, array<string, mixed>> $groups
	 * @return array<string, string>
	 */
	private function extractSubCategories(array $groups): array
	{
		$subCategories = [];

		foreach ($groups as $group) {
			if ($group['id'] !== '' && $group['title'] !== '') {
				$subCategories[$group['id']] = $group['title'];
			}
		}

		return $subCategories;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function renderTemplate(string $filename, array $data = []): string
	{
		$templatePath = $this->config->paths->{$this} . "templates/{$filename}";

		if (!$this->files->exists($templatePath)) {
			$this->error($this->_('Template nicht gefunden') . ": {$filename}");

			return '';
		}

		return $this->files->render($templatePath, $data);
	}

	/**
	 * @param int $since Unix-Zeitstempel, 0 = ganze Datei
	 * @return array{total: int, all: int, declined: int, custom: int, gpc: int, categories: array<string, array{granted: int, total: int}>}
	 */
	public static function collectConsentStats(string $file, int $since): array
	{
		$stats = ['total' => 0, 'all' => 0, 'declined' => 0, 'custom' => 0, 'gpc' => 0, 'categories' => []];
		$seen = [];
		$pattern = '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\t(?:.*?[\t ])?([0-9a-f]{32}) (accept-all|decline-all|custom|grant:\S*)( gpc=1)?(?: dnt=1)?(?: cfg=[0-9a-f]{12})? (\{.*\})\s*$/';

		foreach (self::readLinesReverse($file) as $line) {
			if (\preg_match($pattern, $line, $match) !== 1) {
				continue;
			}

			$time = \strtotime($match[1]);

			if ($time === false) {
				continue;
			}

			if ($since > 0 && $time < $since) {
				break;
			}

			if (isset($seen[$match[2]])) {
				continue;
			}

			$consent = \json_decode($match[5], true);

			if (!\is_array($consent)) {
				continue;
			}

			$seen[$match[2]] = true;
			$stats['total']++;

			$stats[match ($match[3]) {
				'accept-all' => 'all',
				'decline-all' => 'declined',
				default => 'custom',
			}]++;

			if ($match[4] !== '') {
				$stats['gpc']++;
			}

			foreach ($consent as $key => $services) {
				if ($key === 'essential' || \str_starts_with((string) $key, '_') || !\is_array($services) || $services === []) {
					continue;
				}

				$stats['categories'][$key] ??= ['granted' => 0, 'total' => 0];

				if (\count(\array_filter($services)) === \count($services)) {
					$stats['categories'][$key]['granted']++;
				}
			}
		}

		foreach ($stats['categories'] as &$category) {
			$category['total'] = $stats['total'];
		}

		unset($category);

		return $stats;
	}

	/**
	 * @return \Generator<int, string>
	 */
	private static function readLinesReverse(string $file): \Generator
	{
		$handle = @\fopen($file, 'rb');

		if ($handle === false) {
			return;
		}

		try {
			\fseek($handle, 0, \SEEK_END);
			$position = (int) \ftell($handle);
			$buffer = '';

			while ($position > 0) {
				$length = \min(self::STATS_CHUNK, $position);
				$position -= $length;
				\fseek($handle, $position);
				$lines = \explode("\n", (string) \fread($handle, $length) . $buffer);
				$buffer = (string) \array_shift($lines);

				for ($i = \count($lines) - 1; $i >= 0; $i--) {
					yield $lines[$i];
				}
			}

			yield $buffer;
		} finally {
			\fclose($handle);
		}
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function buildConfigSnapshot(array $data): array
	{
		$allowed = \array_flip(self::CONFIG_SNAPSHOT_KEYS);
		$snapshot = [];

		foreach ($data as $key => $value) {
			$key = (string) $key;

			if (!isset($allowed[(string) \preg_replace('/__\d+$/', '', $key)])) {
				continue;
			}

			$value = $this->normalizeSnapshotValue($value);

			if ($value !== null) {
				$snapshot[$key] = $value;
			}
		}

		\ksort($snapshot);

		return $snapshot;
	}

	/**
	 * @return string|array<mixed>|null null = leer
	 */
	private function normalizeSnapshotValue(mixed $value): string|array|null
	{
		if (\is_bool($value)) {
			return $value ? '1' : '0';
		}

		if (\is_int($value) || \is_float($value)) {
			return (string) $value;
		}

		if (\is_string($value)) {
			$value = \trim(\str_replace(["\r\n", "\r"], "\n", $value));

			return $value === '' ? null : $value;
		}

		if (!\is_array($value)) {
			return null;
		}

		$isList = \array_is_list($value);
		$items = [];

		foreach ($value as $key => $item) {
			$item = $this->normalizeSnapshotValue($item);

			if ($item !== null) {
				$items[$key] = $item;
			}
		}

		if ($items === []) {
			return null;
		}

		if ($isList) {
			$items = \array_values($items);
			\usort($items, static fn(mixed $a, mixed $b): int => \strcmp((string) \json_encode($a), (string) \json_encode($b)));
		} else {
			\ksort($items);
		}

		return $items;
	}

	/**
	 * @param array<string, mixed> $snapshot
	 */
	private function hashConfigSnapshot(array $snapshot): string
	{
		$json = \json_encode($snapshot, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

		return \substr(\hash('sha256', $json), 0, self::CONFIG_HASH_LENGTH);
	}

	/**
	 * @param array<string, mixed> $old
	 * @param array<string, mixed> $new
	 * @return array<string, array{old: mixed, new: mixed}>
	 */
	private function diffConfigSnapshots(array $old, array $new): array
	{
		$keys = \array_unique([...\array_keys($old), ...\array_keys($new)]);
		\sort($keys, \SORT_STRING);
		$diff = [];

		foreach ($keys as $key) {
			$before = $old[$key] ?? null;
			$after = $new[$key] ?? null;

			if ($before !== $after) {
				$diff[(string) $key] = ['old' => $this->truncateDiffValue($before), 'new' => $this->truncateDiffValue($after)];
			}
		}

		return $diff;
	}

	private function truncateDiffValue(mixed $value): mixed
	{
		if (\is_array($value)) {
			return \array_map($this->truncateDiffValue(...), $value);
		}

		if (\is_string($value) && \mb_strlen($value) > self::CONFIG_DIFF_VALUE_LENGTH) {
			return \mb_substr($value, 0, self::CONFIG_DIFF_VALUE_LENGTH) . ' …[+' . (\mb_strlen($value) - self::CONFIG_DIFF_VALUE_LENGTH) . ']';
		}

		return $value;
	}

	private function syncConfigHistory(): void
	{
		$data = $this->modules->getConfig($this);

		if ($this->getConsentLogDays((string) ($data['consent_log_retention'] ?? '')) <= 0) {
			if (\array_key_exists('config_hash', $data)) {
				unset($data['config_hash'], $data['config_snapshot'], $data['config_logged_at']);
				$this->storeConfigData($data);
			}

			return;
		}

		try {
			$snapshot = $this->buildConfigSnapshot($data);
			$hash = $this->hashConfigSnapshot($snapshot);
			$stored = $data['config_snapshot'] ?? null;

			if (($data['config_hash'] ?? '') === $hash && \is_string($stored)) {
				return;
			}

			$previous = \is_string($stored) ? \json_decode($stored, true) : null;
			$previous = \is_array($previous) ? $previous : null;

			$this->writeConfigLog($hash, $previous === null ? 'initial' : 'change', $snapshot, $previous === null ? [] : $this->diffConfigSnapshots($previous, $snapshot));

			$data['config_hash'] = $hash;
			$data['config_snapshot'] = \json_encode($snapshot, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
			$data['config_logged_at'] = \time();
		} catch (\JsonException $e) {
			$this->log->save(self::LOG, 'Config history not updated: ' . $e->getMessage());

			return;
		}

		$this->storeConfigData($data);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function storeConfigData(array $data): void
	{
		$this->syncingConfig = true;

		try {
			$this->modules->saveConfig($this, $data);
		} finally {
			$this->syncingConfig = false;
		}

		foreach (['config_hash', 'config_snapshot', 'config_logged_at'] as $key) {
			if (\array_key_exists($key, $data)) {
				$this->set($key, $data[$key]);
			} else {
				$this->remove($key);
			}
		}
	}

	/**
	 * @param array<string, mixed> $snapshot
	 * @param array<string, mixed> $diff
	 */
	private function writeConfigLog(string $hash, string $kind, array $snapshot, array $diff): void
	{
		$version = (string) $this->modules->getModuleInfoProperty($this, 'version');
		$payload = \json_encode(['snapshot' => $snapshot, 'diff' => $diff], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
		$text = "{$hash} {$kind} v{$version} {$payload}";

		$this->log->save(self::CONFIG_LOG, $text, ['showUser' => false, 'showURL' => false, 'maxLineLength' => \strlen($text) + 100]);
	}

	/**
	 * Legt den ersten Snapshot an und schreibt ihn nach {@see self::CONFIG_SNAPSHOT_REFRESH} erneut, damit er das Pruning überdauert.
	 */
	private function ensureConfigHistory(): string
	{
		if (!$this->isConsentLogEnabled()) {
			return '';
		}

		$hash = $this->get('config_hash');

		if (!$this->isValidConfigHash($hash) || !\is_string($this->get('config_snapshot'))) {
			$this->syncConfigHistory();
			$hash = $this->get('config_hash');
		} elseif ((int) $this->get('config_logged_at') < \time() - self::CONFIG_SNAPSHOT_REFRESH) {
			$snapshot = \json_decode((string) $this->get('config_snapshot'), true);

			if (\is_array($snapshot)) {
				try {
					$this->writeConfigLog((string) $hash, 'refresh', $snapshot, []);
					$data = $this->modules->getConfig($this);
					$data['config_logged_at'] = \time();
					$this->storeConfigData($data);
				} catch (\JsonException $e) {
					$this->log->save(self::LOG, 'Config history refresh failed: ' . $e->getMessage());

					return '';
				}
			}
		}

		return $this->isValidConfigHash($hash) ? (string) $hash : '';
	}

	/**
	 * @return array<int, array{time: string, hash: string, kind: string, version: string, count: int, diff: array<string, array{old: mixed, new: mixed}>}>
	 */
	public static function collectConfigHistory(string $file, int $limit): array
	{
		$entries = [];
		$pattern = '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\t(?:.*?[\t ])?([0-9a-f]{12}) (initial|change) v(\S+) (\{.*\})\s*$/';

		foreach (self::readLinesReverse($file) as $line) {
			if (\preg_match($pattern, $line, $match) !== 1) {
				continue;
			}

			$payload = \json_decode($match[5], true);

			if (!\is_array($payload) || !\is_array($payload['snapshot'] ?? null)) {
				continue;
			}

			$entries[] = [
				'time' => $match[1],
				'hash' => $match[2],
				'kind' => $match[3],
				'version' => $match[4],
				'count' => \count($payload['snapshot']),
				'diff' => \is_array($payload['diff'] ?? null) ? $payload['diff'] : [],
			];

			if (\count($entries) >= $limit) {
				break;
			}
		}

		return $entries;
	}

	private function formatHistoryValue(mixed $value): string
	{
		if ($value === null || $value === '' || $value === []) {
			return '<em>' . $this->sanitizer->entities($this->_('leer')) . '</em>';
		}

		$text = \is_array($value) ? \implode(', ', \array_map(static fn(mixed $item): string => \is_scalar($item) ? (string) $item : (string) \json_encode($item, \JSON_UNESCAPED_UNICODE), $value)) : (string) $value;

		return '<q>' . $this->sanitizer->entities(\str_replace("\n", ' ⏎ ', $text)) . '</q>';
	}

	public function renderConfigHistory(): string
	{
		if ($this->input->requestMethod('POST') || !$this->user->hasPermission('module-admin')) {
			return '';
		}

		$out = '<p>' . \str_replace('`cookiemonster-config`', '<code>cookiemonster-config</code>', $this->sanitizer->entities($this->_('Das Log `cookiemonster-config` enthält keine personenbezogenen Daten und wird nach derselben Frist gelöscht wie das Einwilligungsprotokoll. Vor der ersten Einwilligung nach der Einführung gibt es keinen Verlauf.'))) . '</p>';

		if (!$this->isConsentLogEnabled()) {
			$out .= '<p><strong>' . $this->sanitizer->entities($this->_('Kein Logging ist eingestellt: Änderungen werden nicht mehr protokolliert, Cookie und Consent-Log enthalten keinen Konfigurations-Hash.')) . '</strong></p>';
		}

		$entries = self::collectConfigHistory($this->config->paths->logs . self::CONFIG_LOG . '.txt', self::CONFIG_HISTORY_LIMIT);

		if ($entries === []) {
			return $out . '<p>' . $this->sanitizer->entities($this->_('Noch keine Einträge.')) . '</p>';
		}

		/** @var MarkupAdminDataTable $table */
		$table = $this->modules->get('MarkupAdminDataTable');
		$table->setSortable(false);
		$table->setEncodeEntities(false);
		$table->addClass('cmnstr-stats-table');
		$table->addClass('cmnstr-history-table');
		$table->headerRow([$this->_('Zeit'), $this->_('Hash'), $this->_('Änderungen')]);

		foreach ($entries as $entry) {
			if ($entry['kind'] === 'initial') {
				$changes = $this->sanitizer->entities(\sprintf($this->_('Ausgangsstand mit %d Einstellungen'), $entry['count']));
			} else {
				$items = '';

				foreach ($entry['diff'] as $key => $change) {
					$items .= \sprintf(
						'<li><code>%s</code>: %s &rarr; %s</li>',
						$this->sanitizer->entities((string) $key),
						$this->formatHistoryValue($change['old'] ?? null),
						$this->formatHistoryValue($change['new'] ?? null)
					);
				}

				$changes = "<ul class='cmnstr-history-changes'>{$items}</ul>";
			}

			$table->row([
				$this->sanitizer->entities($entry['time']),
				'<code>' . $this->sanitizer->entities($entry['hash']) . '</code>',
				$changes,
			]);
		}

		return $out . $table->render();
	}

	public function renderConsentStats(): string
	{
		if ($this->input->requestMethod('POST') || !$this->user->hasPermission('module-admin')) {
			return '';
		}

		$file = $this->config->paths->logs . self::CONSENT_LOG . '.txt';
		$out = '';

		if (!$this->isConsentLogEnabled()) {
			$out .= '<p><strong>' . $this->sanitizer->entities($this->_('Kein Logging ist eingestellt: Neue Entscheidungen werden nicht protokolliert, die Auswertung zeigt nur noch vorhandene Einträge, bis diese gelöscht werden.')) . '</strong></p>';
		}

		$stats = self::collectConsentStats($file, 0);
		$total = $stats['total'];

		if ($total === 0) {
			return $out . '<p>' . $this->_('Noch keine Entscheidungen.') . '</p>';
		}

		$order = \array_flip(\array_keys(self::CATEGORY_CONSENT));
		$categories = $stats['categories'];
		\uksort($categories, static function (string $a, string $b) use ($order, $categories): int {
			return $categories[$b]['granted'] <=> $categories[$a]['granted'] ?: ($order[$a] ?? 99) <=> ($order[$b] ?? 99) ?: \strcmp($a, $b);
		});

		$share = static function (int $count, int $of): string {
			return \number_format($count / $of * 100, 1, ',', '') . ' %';
		};

		$heading = fn(string $caption): string => '<h3>' . $this->sanitizer->entities($caption) . '</h3>';

		$table = function (string $first, string ...$others): MarkupAdminDataTable {
			/** @var MarkupAdminDataTable $table */
			$table = $this->modules->get('MarkupAdminDataTable');
			$table->setSortable(false);
			$table->addClass('cmnstr-stats-table');
			$table->headerRow([$first, ...\array_map(static fn(string $label): array => [$label, 'cmnstr-stats-num'], $others)]);

			return $table;
		};

		$entries = $table($this->_('Auswahl'), $this->_('Anzahl'), '%');

		foreach (
			[
				$this->_('Alle akzeptiert') => $stats['all'],
				$this->_('Individuell') => $stats['custom'],
				$this->_('Nur notwendige') => $stats['declined'],
			] as $label => $count
		) {
			$entries->row([$label, [(string) $count, 'cmnstr-stats-num'], [$share($count, $total), 'cmnstr-stats-num']]);
		}

		$entries->footerRow([$this->_('Gesamt'), (string) $total, '']);
		$out .= "<div class='cmnstr-stats'><div class='cmnstr-stats-block'>" . $heading($this->_('Entscheidungen')) . $entries->render() . '</div>';

		if ($categories !== []) {
			$consents = $table($this->_('Kategorie'), $this->_('Zugestimmt'), $this->_('Abgelehnt'), $this->_('Zustimmung') . ' %');

			foreach ($categories as $key => $data) {
				$declined = $data['total'] - $data['granted'];

				$consents->row([
					$this->getCategoryTitle((string) $key),
					[(string) $data['granted'], 'cmnstr-stats-num'],
					[(string) $declined, 'cmnstr-stats-num'],
					[$share($data['granted'], $data['total']), 'cmnstr-stats-num'],
				]);
			}

			$out .= "<div class='cmnstr-stats-block'>" . $heading($this->_('Zustimmung je Kategorie')) . $consents->render() . '</div>';
		}

		$out .= '</div>';

		return $out;
	}

	public function getModuleConfigInputfields(InputfieldWrapper $inputfields): InputfieldWrapper
	{
		$this->modules->get('JqueryWireTabs');
		$this->config->js('CookieMonster', ['tabs' => $this->config->get('LanguageTabs') ?: []]);
		$this->config->scripts->add($this->config->urls->{$this} . 'assets/CookieMonster.config.js');
		$this->config->styles->add($this->config->urls->{$this} . 'assets/CookieMonster.config.css');

		// Beim POST noch alter Stand – die Warnung folgt nach der Weiterleitung
		if (!$this->input->requestMethod('POST')) {
			$this->warnUnresolvedServiceReferences();
		}

		return $inputfields;
	}

	private function warnUnresolvedServiceReferences(): void
	{
		$options = $this->getServiceOptions();

		/** @var array<string, string[]> $references */
		$references = [];
		/** @var array<string, int[]> $pageIds */
		$pageIds = [];

		foreach (self::TAG_LABELS as $prefix => $label) {
			foreach ($this->normalizeServiceKeys((array) $this->get("{$prefix}_cookie_service")) as $key) {
				if (!isset($options[$key])) {
					$references[$key][] = $this->sanitizer->entities($label);
				}
			}
		}

		if ($this->fields->get(self::SERVICE_FIELD_NAME) instanceof Field) {
			$selector = self::SERVICE_FIELD_NAME . "!='', include=all, check_access=0";

			foreach ($this->pages->findRaw($selector, self::SERVICE_FIELD_NAME) as $id => $value) {
				$keys = $this->normalizeServiceKeys(\is_array($value) ? (string) ($value['data'] ?? '') : (string) $value);

				foreach ($keys as $key) {
					if (!isset($options[$key])) {
						$pageIds[$key][] = (int) $id;
					}
				}
			}
		}

		foreach ($pageIds as $key => $ids) {
			foreach ($this->pages->getById(\array_slice($ids, 0, 5)) as $page) {
				$editPage = $page instanceof RepeaterPage ? $page->getForPage() : $page;
				$references[$key][] = \sprintf(
					"<a href='%s'>%s</a>",
					$this->sanitizer->entities($editPage->editUrl()),
					$this->sanitizer->entities((string) $editPage->get('title|name'))
				);
			}

			if (\count($ids) > 5) {
				$references[$key][] = \sprintf($this->_('%d weitere Seiten'), \count($ids) - 5);
			}
		}

		foreach ($references as $key => $usages) {
			$this->warning(\sprintf(
				$this->_('Der Cookie-Dienst „%1$s“ ist nicht mehr konfiguriert, wird aber noch verwendet von: %2$s. Bis zur Korrektur gilt ersatzweise die Zustimmung zu „%3$s“.'),
				$this->sanitizer->entities($key),
				\implode(', ', \array_unique($usages)),
				$this->sanitizer->entities($this->getServiceLabel($this->resolveServiceKey($key)))
			), Notice::allowMarkup);
		}
	}
}
