# CookieMonster 4.2.3

## Konfiguration

Admin → Modules → CookieMonster

1. Banner aktivieren
2. Cookie-Kategorien definieren
3. Security Headers nach Bedarf
4. Optional Google Analytics Property-ID

## Inhalte laden

### Für Tracking (Matomo, GA, etc.)

Wenn abgefragt werden soll, ob Tracking erlaubt ist, nutze `allowTracking()`:

```php
<?php
/** @var CookieMonster $cmnstr */

if ($cmnstr->allowTracking()): ?>
	<script>
		var _mtm = window._mtm = window._mtm || [];
		_mtm.push({'mtm.startTime': (new Date().getTime())});
		(function() {
			var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
			g.src='https://analytics.example.com/matomo.js';
			s.parentNode.insertBefore(g,s);
		})();
	</script>
<?php endif; ?>
```

### Für spezifische Kategorien

Wenn eine direkte Kategorie geprüft werden soll:

```php
<?php
/** @var CookieMonster $cmnstr */

if ($cmnstr->isUnlocked('external')): ?>
	<iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"></iframe>
<?php else: ?>
	<p>Video benötigt externe Inhalte.</p>
<?php endif; ?>
```

Oder für Marketing:

```php
<?php
if ($cmnstr->isUnlocked('marketing')): ?>
	<!-- Facebook Pixel -->
	<script>
		!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};
		// ...
	</script>
<?php endif; ?>
```

### Inhalte maskieren

Mit `maskContent()` kannst du externe Inhalte maskieren, bis der Nutzer der Kategorie zustimmt:

```php
<?php
/** @var CookieMonster $cmnstr */

$videoIframe = '<iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"></iframe>';

echo $cmnstr->maskContent($videoIframe, 'external');
?>
```

Ohne Zustimmung wird eine Maske mit Hinweis angezeigt. Mit Zustimmung wird der Inhalt direkt geladen.

Funktioniert für alle Kategorien:

```php
<?php
$html = '<script>/* Facebook Pixel */</script>';
echo $cmnstr->maskContent($html, 'marketing');

$widget = '<div><!-- Third-party Widget --></div>';
echo $cmnstr->maskContent($widget, 'external');
?>
```

## Methoden

### allowTracking(): bool

Prüft ob Nutzer Statistik-Tracking erlaubt hat.

```php
if ($cmnstr->allowTracking()) {
	// Tracking laden
}
```

### isUnlocked(string|SelectableOptionArray $category): bool

Prüft eine spezifische Kategorie bzw. Subkategorie.

```php
if ($cmnstr->isUnlocked('marketing')) {
	// Marketing-Scripts laden
}

if ($cmnstr->isUnlocked('external-youtube')) {
	// YouTube-Videos laden
}
```

### isCategoryActive(string $categoryKey): bool

Prüft ob eine Kategorie oder mindestens eine ihrer Subkategorien aktiv ist.

```php
if ($cmnstr->isCategoryActive('external')) {
	// Externe Inhalte sind (teilweise) freigeschaltet
}
```

### getUnlockedCategories(): array

Alle freigeschalteten Kategorien.

```php
$categories = $cmnstr->getUnlockedCategories();
// ['essential', 'statistics', 'external-youtube', ...]
```

### getGoogleConsentStates(): array

Google Consent Mode v2 States.

```php
$states = $cmnstr->getGoogleConsentStates();
// ['ad_storage' => 'granted', 'analytics_storage' => 'denied', ...]
```

### maskContent(string $html, string|SelectableOptionArray $category): string

Maskiert HTML-Inhalte bis zur Zustimmung der Kategorie bzw. Subkategorie.

```php
$iframe = '<iframe src="..."></iframe>';
echo $cmnstr->maskContent($iframe, 'external');
echo $cmnstr->maskContent($iframe, 'external-youtube');
```

### renderCookieTable(array $category = []): string

Rendert die konfigurierten Cookies als HTML-Tabelle.

```php
// Alle Cookies
echo $cmnstr->renderCookieTable();
```

## Cookie-Kategorien

* **Essential** — Immer aktiv.
* **Functional** — Nutzerpräferenzen, Sprache, Konfiguratoren, ...
* **Statistics** — z.B. Matomo, Google Analytics
* **Marketing** — Facebook Pixel, Google Ads
* **External** — YouTube, iFrames, externe Widgets

### Subkategorien

Kategorien können Subkategorien haben (z.B. `external-youtube`, `marketing-facebook`). Diese werden in der Admin-Konfiguration bei den Cookie-Daten definiert:

```
Name | Provider | Purpose | Duration
---youtube|YouTube Videos|YouTube-Player für Embedded Videos
youtube_player | Google LLC | Video playback | Session

Name | Provider | Purpose | Duration
---vimeo|Vimeo Videos|Vimeo-Player für Embedded Videos
vimeo_player | Vimeo Inc | Video playback | Session
```

## Anforderungen

- PHP 7.4+
- ProcessWire 3+

## Repository

https://github.com/hochwarth/cookiemonster
