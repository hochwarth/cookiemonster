# CookieMonster 4.0.0

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
$cmnstr = $modules->get('CookieMonster');

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
$cmnstr = $modules->get('CookieMonster');

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
$cmnstr = $modules->get('CookieMonster');

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

### isUnlocked(string $category): bool

Prüft eine spezifische Kategorie.

```php
if ($cmnstr->isUnlocked('marketing')) {
	// Marketing-Scripts laden
}
```

### getUnlockedCategories(): array

Alle freigeschalteten Kategorien.

```php
$categories = $cmnstr->getUnlockedCategories();
// ['essential', 'statistics', ...]
```

### getGoogleConsentStates(): array

Google Consent Mode v2 States.

```php
$states = $cmnstr->getGoogleConsentStates();
// ['ad_storage' => 'granted', 'analytics_storage' => 'denied', ...]
```

### maskContent(string $html, string $category): string

Maskiert HTML-Inhalte bis zur Zustimmung der Kategorie.

```php
$iframe = '<iframe src="..."></iframe>';
echo $cmnstr->maskContent($iframe, 'external');
```

## Cookie-Kategorien

* **Essential** — Immer aktiv.
* **Functional** — Nutzerpräferenzen, Sprache, Konfiguratoren, ...
* **Statistics** — z.B. Matomo, Google Analytics
* **Marketing** — Facebook Pixel, Google Ads
* **External** — YouTube, iFrames, externe Widgets

## Anforderungen

- PHP 8.1+
- ProcessWire 3.0.246+

## Repository

https://github.com/hochwarth/cookiemonster
