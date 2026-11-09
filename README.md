# CookieMonster

CookieMonster ist ein ProcessWire-Modul für Cookie-Einwilligung, Tracking und HTTP-Sicherheits-Header. Es zeigt einen Cookie-Banner, speichert die Entscheidung serverseitig und lädt Tracking-Codes und externe Inhalte erst nach Zustimmung.

- Cookie-Banner mit Kategorien und einzelnen Diensten, serverseitig gespeicherter Entscheidung (HttpOnly-Cookie, Einwilligungsprotokoll), Global-Privacy-Control-Unterstützung und Formularen, die auch ohne JavaScript funktionieren.
- Inhaltsmasken für externe Inhalte (Videos, Karten, Widgets), die erst nach Zustimmung laden.
- Tracking-Codes (Google Analytics, Google Tag Manager, Matomo, Meta Pixel, LinkedIn Insight Tag) inkl. Google Consent Mode v2, nur mit passender Zustimmung.
- Cookies abgelehnter Dienste löscht der Server.
- Security-Header: HSTS, optionale Content Security Policy (mit Nonce-Platzhalter; der Standard erlaubt externe `https:`-Quellen und schützt vor allem vor eingeschleustem Script), Framing- und Cross-Origin-Richtlinien.

Banner, Assets und Tracking-Codes landen automatisch in jeder vollständig gerenderten Seite. Template-Änderungen sind nicht nötig. Ausnahmen stehen unter [Bekannte Grenzen](#bekannte-grenzen).

## Status und Voraussetzungen

| Angabe      | Wert               |
| ----------- | ------------------ |
| Status      | Version 4.6.0      |
| PHP         | 8.2 oder neuer     |
| ProcessWire | 3.0.229 oder neuer |

Alternativenvergleich, Aufwandsschätzung, Support- und Release-Zusagen sind nicht Teil dieser Dokumentation.

Das Modul registriert die API-Variable `$cmnstr` (in Templates direkt verfügbar) und installiert vier Module:

| Modul                      | Aufgabe                                             |
| -------------------------- | --------------------------------------------------- |
| `CookieMonster`            | Hauptmodul (autoload)                               |
| `FieldtypeCookieService`   | Feld `cmnstr_service` (Zuordnung von Inhalten)      |
| `InputfieldCookieService`  | Mehrfachauswahl der Dienste                         |
| `TextformatterCookieTable` | Shortcodes `[[cookie-table]]` und `[[cookie-list]]` |

Einstieg: [Schnellstart](#schnellstart). Betrieb und Redaktion: [Aufgaben](#aufgaben), Zuständigkeiten: [Rollen und Kompatibilität](#rollen-und-kompatibilität). Entwicklung: [Entwickler](#entwickler). Probleme: [Fehlersuche](#fehlersuche).

## Inhaltsverzeichnis

- [Schnellstart](#schnellstart)
- [Rollen und Kompatibilität](#rollen-und-kompatibilität)
- [Konzepte und Glossar](#konzepte-und-glossar)
- [Aufgaben](#aufgaben)
- [Rechtliche Grenzen und Verantwortung](#rechtliche-grenzen-und-verantwortung)
- [Barrierefreiheit](#barrierefreiheit)
- [Bekannte Grenzen](#bekannte-grenzen)
- [Entwickler](#entwickler)
- [Fehlersuche](#fehlersuche)
- [Upgrade auf 4.6.0](#upgrade-auf-460)

## Schnellstart

Ziel: Der Banner erscheint und bietet einen Dienst zur Auswahl an. Die Schritte nutzen YouTube; ohne YouTube bis „Dienste eintragen“ folgen und dann zu [Google Analytics anbinden](#google-analytics-anbinden) wechseln. Restliche Punkte vor dem Livegang: [Checkliste](#checkliste-vor-dem-livegang).

### Vorher

Backup von Dateien und Datenbank anlegen. Die Konfiguration braucht das Recht `module-admin`.

> [!WARNING]
> Das Deinstallieren löscht das Feld `cmnstr_service` samt allen Zuordnungen an Seiten (siehe [Abschalten und deinstallieren](#abschalten-und-deinstallieren)). Zum bloßen Ausschalten reicht es, den Banner zu deaktivieren.

### Installieren

Ordner `CookieMonster` nach `site/modules/` kopieren, im Admin **Modules** → **Refresh**, dann im Tab **Site** bei **CookieMonster** **Install** wählen.

Erwartet: Der Admin meldet „Feld „cmnstr_service“ angelegt.“, LazyCron und die drei Zusatzmodule sind installiert.

### Banner prüfen und ersten Dienst eintragen

1. **Modules** → **Site** → **CookieMonster** öffnen.
2. Im Tab **Cookie-Banner** im Bereich **Anzeige** prüfen, dass **Cookie-Banner** („Hinweis und Einstellungsdialog auf der Website anzeigen“) an ist.
3. Im Bereich **Rechtliche Seiten** die Seiten bei **Impressum** und **Datenschutzerklärung** auswählen.
4. Zum Tab **Cookies** wechseln und die Kategorie **Externe Inhalte** aufklappen.
5. **Kategorie im Cookie-Banner anbieten** anhaken.
6. Die [Vorlage für YouTube](docs/cookie-vorlagen.md#vorlage-youtube) in das Feld **Dienste und Cookies** einfügen. Die Datei liegt unter `site/modules/CookieMonster/docs/cookie-vorlagen.md`. `beispiel.de` ersetzen (siehe unten).
7. Speichern.

Aufbau der Zeilen im Feld **Dienste und Cookies**, Spalten durch `|` getrennt:

```text
---youtube|YouTube|Eingebettete Videos|Hinweis in der Maske
YSC|youtube.com|Zweck des Cookies|Sitzung
```

- Zeile mit `---`: ein Dienst. Spalten: ID, Titel, Beschreibung, Hinweis für die Maske.
- Zeile ohne `---`: ein Cookie des Dienstes darüber. Spalten: Name, Anbieter, Zweck, Dauer.
- Das Zeichen `|` trennt die Spalten und lässt sich nicht maskieren. In Texten ersetzen (z. B. durch einen Schrägstrich), sonst verschieben sich die Spalten.
- Zu wenige Spalten bleiben leer (ohne Fehlermeldung), alles ab der fünften Spalte wird ignoriert.
- Leere ID (`---|Titel`): Die ID entsteht aus dem Titel, ohne Titel gilt `default`. Alle Regeln: [Dienste und Cookies eintragen](#dienste-und-cookies-eintragen).
- `beispiel.de` in der Spalte „Anbieter“ steht für die eigene Domain. Den Host der Website eintragen, ohne `https://`, z. B. `firma.de`. Die Spalte ist nur eine Anzeige, das Löschen ohne Zustimmung nutzt nur den Cookie-Namen.

### Im Privatmodus prüfen

1. Die Website in einem privaten Browserfenster öffnen.
2. Erwartet: Der Hinweis mit den Buttons „Cookie-Einstellungen“, „Alle ablehnen“ und „Alle akzeptieren“ erscheint.
3. „Cookie-Einstellungen“ wählen. Erwartet: Der Dialog zeigt **Notwendige Cookies** und **Externe Inhalte** mit dem Dienst YouTube.
4. „Auswahl speichern“ wählen. Erwartet: Die Seite lädt neu, der Hinweis bleibt aus, das Cookie `cmnstr` ist gesetzt ([Welche Cookies setzt meine Seite?](#welche-cookies-setzt-meine-seite)).

Bei Problemen: [Fehlersuche](#fehlersuche). Weiter: [Aufgaben](#aufgaben).

### Footer-Link setzen

Pflichtschritt, damit Besucher ihre Entscheidung ändern und widerrufen können: Link in die Template-Datei setzen, die den Footer ausgibt (z. B. `site/templates/_main.php`; Fundstelle: `rg -l "</footer>" site/templates`):

```html
<a href="?cmnstr-settings" class="cmnstr-button-edit" rel="nofollow">Cookie-Einstellungen</a>
```

Jedes Element mit der Klasse `cmnstr-button-edit` öffnet den Einstellungsdialog.

### Abschalten und deinstallieren

- Abschalten: Im Tab **Cookie-Banner** die Option **Cookie-Banner** ausschalten. Dann gilt:
    - Weder Hinweis noch Einstellungsdialog erscheinen, CSS und JavaScript des Moduls laden nicht mehr.
    - Tracking-Codes und maskierte Inhalte bleiben ohne Entscheidung gesperrt.
    - Eine Maske erscheint weiter, ohne Modul-CSS. Ihr „Zulassen“ sendet ein normales Formular.
    - Läuft trotzdem weiter: alles, was keine Einwilligung braucht (Matomo im Modus „Immer cookielos“, Search Console).
- Deinstallieren: **Modules** → **Site** → **CookieMonster** → **Uninstall**. Das Feld `cmnstr_service` verschwindet aus allen Fieldgroups und wird samt Inhalten gelöscht, danach der Fieldtype `FieldtypeCookieService`. Die Logdateien in `site/assets/logs` bleiben.

## Rollen und Kompatibilität

| Rolle                    | Einmalig                                                                                          | Laufend                                                                        | Bei Updates                                                                                         |
| ------------------------ | ------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------- |
| Betrieb / Projektleitung | Banner, Texte, Dienste, Tracking-Codes einrichten, Aufbewahrung festlegen, Standardwerte prüfen   | Neue Dienste eintragen, Cookie-Listen aktuell halten, Banner-Texte archivieren | Upgrade-Hinweise lesen, Tracking-Zuordnung prüfen (der Banner fragt nach jedem Versionswechsel neu) |
| Redaktion                | Cookie-Übersicht und Textbaustein in die Datenschutzerklärung einfügen                            | Einbettungen einem Dienst zuordnen                                             | –                                                                                                   |
| Entwicklung              | Modul installieren, Footer-Link, Masken und eigene Tracking-Codes einbauen, Header und CSP prüfen | Log `cookiemonster` beobachten                                                 | Templates und Scripts anpassen (`cmnstr_service`, Nonce)                                            |
| Datenschutz              | Texte, Einstufung der Dienste, Aufbewahrung, Rechtsgrundlage prüfen                               | Änderungen an Diensten und Texten prüfen                                       | Neue Einstellungen und Textbausteine prüfen                                                         |

### Was das Modul übernimmt und was der Betreiber tun muss

| Umgebung                       | Das Modul                                                                               | Der Betreiber                                                                                 |
| ------------------------------ | --------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| Template-Cache von ProcessWire | umgeht ihn (keine Masken, Nonces, Consent-Daten für andere Besucher)                    | lässt den Standard, siehe [Bekannte Grenzen](#bekannte-grenzen)                               |
| ProCache, Full-Page-Cache      | kann bei Seiten ohne PHP nichts tun                                                     | schließt Seiten mit Banner und Masken aus (Einordnung des Autors, nicht getestet)             |
| Skip-Link der Website          | setzt den Banner direkt nach `<body>`, vor den Skip-Link                                | setzt den Skip-Link bei Bedarf davor, siehe [Barrierefreiheit](#barrierefreiheit)             |
| Teil-Renderings, AJAX          | liefert weder Banner noch Tracking-Codes noch Header                                    | gibt die Ausgabe nur in vollständigen Seiten aus, siehe [Bekannte Grenzen](#bekannte-grenzen) |
| CDN, Reverse-Proxy             | sendet `Cache-Control: private, no-cache` und `Vary: Cookie`, siehe [Caching](#caching) | prüft, ob der Cache die Header beachtet (einzelne Dienste nicht geprüft)                      |

## Konzepte und Glossar

### Kategorie, Dienst und Cookie

Drei Ebenen:

```text
Statistik-Cookies        Kategorie   statistics
└── Google Analytics     Dienst      statistics-ga
    └── _ga              Cookie      _ga
```

Es gibt genau fünf feste Kategorien.

| ------------ | -------------------- | -------------------------------------------------------------------------- |
| `essential` | Notwendige Cookies | Immer aktiv: Session-Cookie, Consent-Cookie `cmnstr` |
| `functional` | Funktionelle Cookies | Nutzerpräferenzen, Sprache, Konfiguratoren, Spam-Schutz (Google reCAPTCHA) |
| `statistics` | Statistik-Cookies | Matomo, Google Analytics 4, Google Tag Manager |
| `marketing` | Marketing-Cookies | Google Ads (Conversion Linker), Meta Pixel, LinkedIn Insight Tag |
| `external` | Externe Inhalte | YouTube, Vimeo, Google Maps, iFrames, externe Widgets |

Der Schlüssel eines Dienstes besteht aus Kategorie und Dienst-ID, z. B. `external-youtube`. Mit diesem Schlüssel arbeiten das Feld `cmnstr_service`, die Tracking-Konfiguration und die Methoden `isUnlocked()` und `maskContent()`.

### Glossar

| Begriff                | Bedeutung                                                                                                                                                                                      |
| ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Kategorie              | Eine der fünf festen Gruppen (Tabelle oben), in der Oberfläche ein Abschnitt im Tab **Cookies**.                                                                                               |
| Dienst                 | Ein Eintrag wie `statistics-ga`, in der Oberfläche eine Zeile `---ga\|…` im Feld **Dienste und Cookies**. Ältere Texte sagen „Gruppe“ oder „Subkategorie“, gemeint ist derselbe Dienst.        |
| Cookie                 | Eine Zeile `Name\|Anbieter\|Zweck\|Dauer` unter einem Dienst. Auch Browser-Speicher wie `localStorage` steht als Zeile.                                                                        |
| Schlüssel              | Kennung für Kategorie oder Dienst, z. B. `statistics` oder `statistics-ga`.                                                                                                                    |
| Zustimmung             | Entscheidung eines Besuchers für einen Dienst. Eine Kategorie gilt erst als zugestimmt, wenn allen ihren Diensten zugestimmt wurde.                                                            |
| Hinweis                | Der Banner mit dem kurzen Text („Hinweistext“) und den Buttons.                                                                                                                                |
| Inhaltsmaske           | Platzhalter an der Stelle eines blockierten Inhalts (Texte im Bereich **Inhaltsmaske für blockierte Inhalte**).                                                                                |
| Consent-Cookie         | HttpOnly-Cookie `cmnstr` mit der Entscheidung.                                                                                                                                                 |
| Epoche                 | Zeitpunkt, vor dem gespeicherte Entscheidungen nicht mehr gelten. Jede Versionsänderung setzt ihn neu (der Banner fragt alle erneut), Änderungen an Einstellungen, Texten oder Diensten nicht. |
| Consent-ID             | Zufällige Kennung im Consent-Cookie bei aktivem Einwilligungsprotokoll.                                                                                                                        |
| Einwilligungsprotokoll | Log `cookiemonster-consent` mit Consent-ID, Zeitpunkt und Auswahl.                                                                                                                             |
| GPC, DNT               | Browser-Signale „Global Privacy Control“ und „Do Not Track“, standardmäßig aus.                                                                                                                |
| UND, ODER              | Bei mehreren Diensten: Allen (UND) oder mindestens einem (ODER) muss zugestimmt sein.                                                                                                          |
| LazyCron               | Zeitgesteuerte ProcessWire-Aufgaben, laufen nur bei Seitenaufrufen.                                                                                                                            |

### Dienste und Cookies eintragen

Eingabeort: Tab **Cookies** → Kategorie aufklappen → Feld **Dienste und Cookies**. Das Zeilenformat (`---ID|…` für Dienste, `Name|…` für Cookies) steht im [Schnellstart](#banner-prüfen-und-ersten-dienst-eintragen), die Dauer erklärt [Dauer und Speichertyp](#dauer-und-speichertyp). Das Eingabefeld färbt die Spalten beim Tippen ein ([Eingabefeld für Dienste und Cookies](#eingabefeld-für-dienste-und-cookies)).

Beispiel: Die Zeilen der YouTube-Vorlage ergeben den Dienst `external-youtube`. Danach funktionieren `isUnlocked('external-youtube')` und `maskContent($html, 'external-youtube')`. Fertige Zeilen für gängige Dienste stehen in den [Cookie-Vorlagen](docs/cookie-vorlagen.md) (`site/modules/CookieMonster/docs/cookie-vorlagen.md`).

Regeln für Dienst-IDs:

- Die ID wird auf `A-Z`, `a-z`, `0-9`, `_` und `-` reduziert. Andere Zeichen werden zu `_`. Beispiel: `google.maps` wird zu `external-google_maps`.
- Notwendige Dienste (`essential-…`) sind immer freigeschaltet.
- Ein Kategorie-Präfix wird nur am Anfang entfernt.
- Eine Zeile ohne ID (`---|Titel`) erhält die ID aus dem Titel (`Google Maps` wird zu `google-maps`). Ohne Titel gehört sie zu `default`. Ändert sich der Titel, ändert sich die ID.
- Mehrfach vergebene IDs werden zu einem Dienst zusammengeführt.

Mehrsprachigkeit: Dienst-IDs und Cookie-Namen (damit Zustimmung, Maskierung, Aufräumen) stammen immer aus dem Text der Standardsprache. Die Texte weiterer Sprachen (Titel, Beschreibung, Hinweis, Anbieter, Zweck, Dauer, Banner, Buttons) ersetzen nur die Anzeige, leere fallen auf die Standardsprache zurück. Zugeordnet wird über ID bzw. Name, bei gleicher Anzahl auch über die Position.

### Dauer und Speichertyp

Banner, `[[cookie-table]]` und `[[cookie-list]]` zeigen Dauer und Typ aus der letzten Spalte einer Cookie-Zeile:

| Eingetragene Dauer               | Angezeigte Dauer | Typ            |
| -------------------------------- | ---------------- | -------------- |
| _(leer)_                         | Sitzung          | Cookie         |
| `sessionStorage`                 | Sitzung          | sessionStorage |
| `localStorage`                   | Unbestimmt       | localStorage   |
| `IndexedDB`                      | Unbestimmt       | IndexedDB      |
| alles andere, z. B. `30 Minuten` | unverändert      | Cookie         |

- Die Groß-/Kleinschreibung der drei Speicherarten ist egal, alle anderen Angaben und der Zweck werden 1:1 übernommen.
- „Unbestimmt“: bleibt bis zum manuellen Löschen. sessionStorage endet mit dem Schließen des Tabs.
- Einträge mit Browser-Speicher dienen nicht als Muster für das [Löschen ohne Zustimmung](#löschen-ohne-zustimmung).

### Mehrere Dienste: UND und ODER

|          | Inhalte (`cmnstr_service`, `maskContent()`) | Tracking-Codes            |
| -------- | ------------------------------------------- | ------------------------- |
| Standard | UND: allen muss zugestimmt sein             | UND                       |
| ODER     | per Pseudo-Schlüssel `any` im Wert          | wählbar unter der Auswahl |

- UND: Die Maske nennt nur die fehlenden Einträge, „Zulassen“ stimmt allen zu. Eine gewählte Kategorie deckt ihre Dienste ab (`external` + `external-youtube` = `external`).
- ODER: Der Pseudo-Schlüssel `any` (`CookieMonster::MATCH_ANY`) steht im Wert, z. B. `['any', 'statistics-gtm', 'marketing-gtm']`. `isUnlocked()` und `maskContent()` erkennen ihn, der Wert kann direkt übergeben werden. Die Maske nennt die Einträge mit „oder“ und verschwindet, sobald einem zugestimmt wurde.
- Auswahl in der Oberfläche: Unter der Mehrfachauswahl steht **Bei mehreren Einträgen:** mit „UND: Allen muss zugestimmt sein“ und „ODER: Mindestens einem muss zugestimmt sein“. In eigenen Formularen erscheint sie mit `'showMatch' => true`.

## Aufgaben

Reihenfolge für eine neue Website: [Banner einrichten](#banner-einrichten) → [Dienste und Cookies eintragen](#dienste-und-cookies-eintragen) → [Tracking-Codes zuordnen](#tracking-codes-zuordnen) → [Video maskieren](#video-maskieren) → [Datenschutzerklärung ergänzen](#datenschutzerklärung-ergänzen) → [Checkliste](#checkliste-vor-dem-livegang).

Rechtliche Entscheidungen (Protokollierung, Aufbewahrung, Matomo-Modus, Rechtsgrundlage) trifft der Betreiber, nicht das Modul. Bei Unsicherheit den Standard übernehmen und die Datenschutzbeauftragten fragen. Wer welchen Schritt erledigt: [Rollen und Kompatibilität](#rollen-und-kompatibilität).

### Konfigurationsoberfläche

Die Konfiguration liegt unter **Modules** → **Site** → **CookieMonster** (Texte mehrsprachig, Bereiche mit aktiver Einstellung aufgeklappt).

| Tab                    | Bereiche                                                                                                                                                                                                                                                                                                                                                                                                                   |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Cookie-Banner**      | **Anzeige** (Banner an/aus, Standard-Stylesheet, unter **Erweitert** Template-Cache beibehalten und Fehlendes Dienst-Feld), **Texte** (Hinweistext, Überschriften, Erklärung im ersten Abschnitt, vier Buttons), **Rechtliche Seiten**, **Inhaltsmaske für blockierte Inhalte** (Hinweistext mit `{category}`, Buttons „Zulassen“, „Einmal laden“, „Alle akzeptieren (Maske)“, Text vor dem Link zur Datenschutzerklärung) |
| **Cookies**            | **Kategorien** (fünf Abschnitte mit Titel, Beschreibung, **Dienste und Cookies**; ausgeschaltete abgeblendet), **Datenschutz-Signale des Browsers (GPC / DNT)**, **Consent-Cookie** (**Consent über Subdomains teilen**)                                                                                                                                                                                                   |
| **Tracking**           | **Google Analytics**, **Google Tag Manager**, **Matomo**, **Meta Pixel**, **LinkedIn Insight Tag** (je mit **Erforderliche Zustimmung**), **Google Search Console** (Meta-Tag `google-site-verification`, unabhängig von der Zustimmung)                                                                                                                                                                                   |
| **Sicherheits-Header** | **HSTS**, **Einbetten in Frames**, **Content Security Policy (CSP)**, **Permissions-Policy**, **Weitere Sicherheits-Header** ([Security-Header](#security-header))                                                                                                                                                                                                                                                         |
| **Statistik**          | **Aufbewahrung des Einwilligungsprotokolls**, **Consent-Statistik**, **Verlauf der Konfiguration** ([Rechtliche Grenzen und Verantwortung](#rechtliche-grenzen-und-verantwortung))                                                                                                                                                                                                                                         |

### Welche Cookies setzt meine Seite?

Rolle: Betrieb. Das Ergebnis gehört in das Feld **Dienste und Cookies** und in die Checkliste.

1. Die Website in einem privaten Fenster und die Entwicklerwerkzeuge öffnen (F12, Mac: Cmd+Alt+I). Safari zeigt sie erst nach **Safari** → **Einstellungen** → **Erweitert** → „Funktionen für Webentwickler anzeigen“.
2. Den Tab für gespeicherte Daten öffnen: in Chrome **Anwendung** → **Speicher** → **Cookies**, in Firefox und Safari **Speicher** → **Cookies**.
3. Die Cookies notieren. Erwartet vor einer Entscheidung: das Session-Cookie von ProcessWire (`wire` oder `wires`). Dann „Alle akzeptieren“ wählen, neu laden und die Liste erneut ansehen. Jedes Cookie, das nicht im Feld **Dienste und Cookies** steht, eintragen bzw. dem Dienst zuordnen, der es setzt. Browser-Speicher (Local Storage, Session Storage, IndexedDB) steht im selben Tab unter eigenen Einträgen.

Zuordnung: Den Cookie-Namen in den [Cookie-Vorlagen](docs/cookie-vorlagen.md) nachschlagen, dort steht jedes Cookie bei seinem Dienst (z. B. `_ga` bei Google Analytics 4). Cookies von Drittanbietern erscheinen unter der Domain des Anbieters und lassen sich serverseitig nicht löschen ([Löschen ohne Zustimmung](#löschen-ohne-zustimmung)).

### Banner einrichten

Rolle: Betrieb.

1. Im Tab **Cookie-Banner** → **Texte** die Texte prüfen und anpassen: **Hinweistext**, **Überschrift des Einstellungsdialogs**, **Überschrift des ersten Abschnitts**, **Erklärung im ersten Abschnitt** (leer: Hinweistext) und die vier Buttons. Weitere Sprachen über die Sprach-Tabs der Felder pflegen.
2. Im Bereich **Rechtliche Seiten** Impressum und Datenschutzerklärung wählen. Der Hinweis erscheint dort immer, damit Besucher sie ohne Hürde lesen können.
3. Im Tab **Cookies** die Kategorien einrichten: **Kategorie im Cookie-Banner anbieten** anhaken, **Titel** und **Beschreibung** prüfen, Dienste und Cookies eintragen ([Zeilenformat](#dienste-und-cookies-eintragen), [Vorlagen](docs/cookie-vorlagen.md)).
4. Einen Link „Cookie-Einstellungen“ in den Footer setzen ([Footer-Link setzen](#footer-link-setzen)).
5. Im Bereich **Anzeige** entscheiden, ob das mitgelieferte CSS gilt (**Standard-Stylesheet**). Anpassung: [Gestaltung und JavaScript](#gestaltung-und-javascript).
6. Im Browser prüfen (ohne und mit Zustimmung).

**Dienste und Cookies** bestimmt, was der Banner abfragt und welche Cookies der Server ohne Zustimmung löscht: nur tatsächlich eingesetzte Einträge übernehmen.

### Banner und Einstellungsdialog

- Hinweis: Er erscheint, solange keine Entscheidung vorliegt oder ein angebotener Dienst im Consent fehlt (z. B. neu konfiguriert; bestehende Entscheidungen bleiben vorausgewählt), auf Impressum und Datenschutzseite immer.
- Einstellungsdialog: Aktivierte Kategorien mit ihren Diensten, je Dienst eine Checkbox, die Checkbox der Kategorie schaltet alle Dienste um.
- Buttons: „Alle akzeptieren“, „Alle ablehnen“ und „Auswahl speichern“ senden an die [Consent-Schnittstelle](#consent-schnittstelle). Danach lädt die Seite neu, Tracking-Codes greifen sofort. Inhaltsmasken laden die Seite ebenfalls neu.
- Assets: `CookieMonster.js` (ES-Modul) und, wenn aktiviert, `CookieMonster.css` mit Änderungszeit als Cache-Buster und CSP-Nonce.

Einstellungen öffnen:

- Jedes Element mit der Klasse `cmnstr-button-edit` öffnet den Dialog (Beispiel: [Footer-Link setzen](#footer-link-setzen)).
- Eigene Skripte rufen `window.showCookieBanner()` auf.
- Ohne JavaScript führt der Link auf die aktuelle Seite mit `?cmnstr-settings`, der Server rendert den Dialog geöffnet. Mit JavaScript öffnen dieselben Links den Dialog modal ohne Seitenwechsel und das Skript entfernt `?cmnstr-settings` per `history.replaceState()`, damit Neuladen oder Teilen den Dialog nicht erneut öffnet.
- Ohne JavaScript funktionieren die Formulare per normalem `POST` mit Weiterleitung.

### Löschen ohne Zustimmung

Die Namen in den Cookie-Zeilen sind zugleich Muster zum Aufräumen. Ohne Zustimmung zum Dienst (bzw. nach Widerruf) löscht der Server passende Cookies bei jeder Consent-Entscheidung und jedem Seitenaufruf, als Host-only-Cookie und auf allen übergeordneten Domains. `*` steht für beliebige Zeichen.

```text
---ga|Google Analytics|Statistiken zur Nutzung der Website
_ga|beispiel.de|Speichert eine zufällige Client-ID, um Besucher zu unterscheiden.|400 Tage
_ga_*|beispiel.de|Speichert den Sitzungsstatus.|400 Tage
```

- Notwendige Cookies, das Consent-Cookie `cmnstr` und das Session-Cookie (inkl. `…_challenge`) werden nie gelöscht. Dasselbe gilt für Namen, die zusätzlich bei einem Dienst mit Zustimmung stehen.
- Deaktivierte Kategorien bleiben unberücksichtigt. Namen mit Leerzeichen, `;`, `=` oder `,` sowie Namen nur aus `*` gelten nicht als Muster.
- Dynamische Namen brauchen ein Muster: Matomo setzt z. B. `_pk_id.1.a1b2`. Das erfasst nur `_pk_id.*`, nicht `_pk_id`.
- Gelöschte Cookies vermerkt das Einwilligungsprotokoll bei einer Entscheidung (`<id> purge _ga,_ga_ABC123`), sofern es aktiv ist.
- Nicht möglich: Cookies unter der Domain des Anbieters (z. B. `IDE` auf doubleclick.net, `YSC` auf youtube.com, kein Zugriff der Website; auf die Opt-out-Seiten der Anbieter verweisen), Cookies mit einem anderen `path` als `/` sowie localStorage, sessionStorage und IndexedDB.
- Full-Page-Cache: [Bekannte Grenzen](#bekannte-grenzen).

### Tracking-Codes zuordnen

Rolle: Betrieb.

Im Tab **Tracking** weist jeder Tracking-Code unter **Erforderliche Zustimmung** einen oder mehrere Dienste zu (gleiche Auswahl wie im Feld `cmnstr_service`), z. B. `statistics-ga` oder eine ganze Kategorie wie `statistics`.

| Auswahl                | Verhalten                                                                                                                  |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Ein Dienst             | Der Code erscheint nach Zustimmung zu diesem Dienst (Kategorie: alle Dienste, [Glossar](#glossar)).                        |
| Mehrere Dienste        | Standard UND, unter der Auswahl auf ODER umstellbar ([UND und ODER](#mehrere-dienste-und-und-oder)).                       |
| Keine Auswahl, Analyse | Google Analytics, Google Tag Manager, Matomo: nach Zustimmung zu allen Diensten der Statistik-Cookies (`allowTracking()`). |
| Keine Auswahl, Werbung | Meta Pixel, LinkedIn Insight Tag: nach Zustimmung zu allen Diensten der Marketing-Cookies (nicht Statistik).               |

Ist die Kategorie deaktiviert oder ohne Dienst, gibt das Modul den Code nie aus (ohne Dienst ist keine Zustimmung möglich).

> [!NOTE]
> Steht im Template bereits ein eigener Code von Google Analytics, Tag Manager, Matomo oder Meta Pixel, diesen entfernen, bevor der Dienst über das Modul läuft. Ein Code im Template lädt unabhängig vom Modul, also vor der Zustimmung. Fundstellen in den Template-Dateien finden z. B. mit `rg -n "gtag|googletagmanager|matomo|fbq" site/templates`.

Der Tracking-Code liegt im `<head>`, der `<noscript>`-Fallback (Google Tag Manager, Matomo, Meta Pixel, LinkedIn) vor `</body>`. Beides erscheint nur mit Zustimmung.

### Google Analytics anbinden

Rolle: Betrieb.

1. Im Tab **Cookies** die Kategorie **Statistik-Cookies** aufklappen und **Kategorie im Cookie-Banner anbieten** anhaken.
2. Die [Vorlage für Google Analytics 4](docs/cookie-vorlagen.md#vorlage-google-analytics-4) in **Dienste und Cookies** einfügen und `beispiel.de` ersetzen. Die Zeile `---ga|Google Analytics|…` muss dabei sein, sonst gibt es nichts zum Zustimmen und der Tag lädt nie.
3. Im Tab **Tracking** im Bereich **Google Analytics** die **Mess-ID** eintragen (`G-XXXXXXXXXX`). Andere gtag.js-IDs wie `AW-…` (Google Ads) und `DC-…` (Floodlight) funktionieren ebenfalls.
4. Bei **Erforderliche Zustimmung** den Dienst `statistics-ga` wählen. Bleibt die Auswahl leer, wartet der Code auf alle Dienste der Statistik-Cookies.
5. Speichern und im Browser prüfen: Ohne Zustimmung lädt kein Script von Google und es gibt kein Cookie `_ga`. Nach „Alle akzeptieren“ lädt der Tag.

Wird GA4 über den Tag Manager geladen, nur die GTM-Vorlage eintragen, nicht zusätzlich die GA4-Vorlage.

Probleme: [Tracking-Code lädt nicht](#tracking-code-lädt-nicht).

### Google Tag Manager anbinden

Rolle: Betrieb.

Der Tag Manager darf in Statistik und Marketing auftreten. Mit ODER lädt der Container, sobald einer der beiden zugestimmt wurde. Bei Unsicherheit stattdessen [Google Analytics anbinden](#google-analytics-anbinden).

> [!WARNING]
> Ein Consent-Typ wird erst `granted`, wenn allen Diensten der Kategorie zugestimmt wurde. Bietet eine Kategorie außer dem Tag Manager weitere Dienste an (z. B. `meta` in „Marketing“), bleibt `ad_storage` bei Zustimmung nur zum Tag Manager auf `denied`.

1. In **Statistik-Cookies** die [Vorlage Google Tag Manager (Statistik)](docs/cookie-vorlagen.md#vorlage-google-tag-manager-statistik) und in **Marketing-Cookies** die [Vorlage Google Tag Manager (Marketing)](docs/cookie-vorlagen.md#vorlage-google-tag-manager-marketing-google-ads) einfügen. Jede enthält eine Zeile `---gtm|Google Tag Manager|…`.
2. Im Tab **Tracking** im Bereich **Google Tag Manager** die **Container-ID** (`GTM-…`) eintragen.
3. Bei **Erforderliche Zustimmung** `statistics-gtm` und `marketing-gtm` auswählen und unter der Auswahl **ODER** wählen. Mit UND lädt der Container erst bei beiden Zustimmungen.
4. Welche Tags im Container feuern, regelt der Google Consent Mode v2 (siehe unten).

Google Consent Mode: Auf jeder Seite mit Google Analytics oder Tag Manager gibt das Modul vor dem Tracking-Code `gtag('consent', 'default', …)` mit dem Zustand des Besuchers aus (`templates/tags/consent.php`).

| Kategorie (vollständig zugestimmt) | Consent-Typ `granted`                              |
| ---------------------------------- | -------------------------------------------------- |
| Notwendige Cookies (immer)         | `security_storage`                                 |
| Funktionelle Cookies               | `functionality_storage`, `personalization_storage` |
| Statistik-Cookies                  | `analytics_storage`                                |
| Marketing-Cookies                  | `ad_storage`, `ad_user_data`, `ad_personalization` |
| Externe Inhalte                    | `personalization_storage`                          |

Alles andere steht auf `denied`. Lädt der Container nur wegen der Marketing-Zustimmung, bleiben GA4-Tags ohne Cookies (`analytics_storage` = `denied`), Google-Ads-Tags laufen.

- Nicht-Google-Tags (z. B. Hotjar, Microsoft Clarity, Meta Pixel über GTM) werten den Consent Mode nicht aus. In GTM unter _Erweiterte Einstellungen → Einwilligungseinstellungen → Zusätzliche Einwilligung erforderlich_ den passenden Typ (z. B. `analytics_storage`) eintragen, sonst feuern sie, sobald der Container lädt. Google-Tags (GA4, Google Ads, Conversion Linker) prüfen selbst.
- Nur `default`, kein `update`: Der Zustand wird bei jedem Seitenaufruf serverseitig gesetzt, Entscheidungen laden die Seite neu.
- Cookies im Container: Die Cookie-Zeilen hängen von den Tags im Container ab. Nur eintragen, was gesetzt wird, damit Banner und Löschen ohne Zustimmung stimmen.

Probleme: [Tracking-Code lädt nicht](#tracking-code-lädt-nicht).

### Matomo, Meta Pixel, LinkedIn und Search Console

Rolle: Betrieb.

1. Im Tab **Cookies** die Kategorie **Statistik-Cookies** aufklappen, **Kategorie im Cookie-Banner anbieten** anhaken und die [Vorlage für Matomo](docs/cookie-vorlagen.md#vorlage-matomo) einfügen. `beispiel.de` ersetzen, die Zeile `---matomo|Matomo|…` muss dabei sein.
2. Im Tab **Tracking** im Bereich **Matomo** **Matomo-Tracking aktivieren**, **Matomo-URL** und **Website-ID** eintragen (Beispiele unter der Tabelle).
3. **Cookieloses Tracking** wählen. Der Standard „Aus“ lädt Matomo nur mit Zustimmung. „Fallback“ und „Immer cookielos“ laden auch ohne Zustimmung, ohne Cookies. Ob das zulässig ist, ist eine Rechtsfrage für die Datenschutzbeauftragten.
4. Bei **Erforderliche Zustimmung** den Dienst `statistics-matomo` wählen.
5. Speichern und im Browser prüfen wie bei [Google Analytics anbinden](#google-analytics-anbinden).

Meta Pixel, LinkedIn Insight Tag und Search Console analog: Dienst mit [Vorlage](docs/cookie-vorlagen.md) eintragen, im Tab **Tracking** ID eintragen, **Erforderliche Zustimmung** wählen.

| Code                  | Felder im Tab **Tracking**                                                                                           | Besonderheit                                                                                                                                                                                                                                           |
| --------------------- | -------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Matomo                | **Matomo-URL**, **Website-ID**, **Cookieloses Tracking**, **Subdomains gemeinsam tracken**, **Fester Dokumenttitel** | „Aus“: nur mit Zustimmung. „Fallback“: ohne Zustimmung cookielos, mit Zustimmung mit Cookies. „Immer cookielos“: immer (`disableCookies`). Besucher-ID 6 Monate (`setVisitorCookieTimeout`), **Subdomains gemeinsam tracken** setzt `setCookieDomain`. |
| Meta Pixel            | **Pixel-ID**, **Event beim Seitenaufruf**                                                                            | Standard-Event `PageView` beim Laden.                                                                                                                                                                                                                  |
| LinkedIn Insight Tag  | **Partner-ID**                                                                                                       | Ohne Auswahl gilt die Marketing-Zustimmung.                                                                                                                                                                                                            |
| Google Search Console | **Bestätigungscode**                                                                                                 | Gibt `<meta name="google-site-verification">` aus, zustimmungsunabhängig.                                                                                                                                                                              |

Beispielwerte für Matomo: **Matomo-URL** `https://matomo.beispiel.de` (Adresse der Installation, mit oder ohne Schrägstrich am Ende), **Website-ID** `1` (steht in der Website-Verwaltung von Matomo).

Eigene Codes: [Inhalte laden und maskieren](#inhalte-laden-und-maskieren). Probleme: [Tracking-Code lädt nicht](#tracking-code-lädt-nicht).

### Video maskieren

Rolle: Redaktion und Entwicklung.

Videos im Editor-Fließtext (WYSIWYG-Feld wie `body`) maskiert das Modul nicht. Lösung: ein eigenes Feld für den Embed-Code (die Schritte unten, braucht eine Template-Änderung) oder das ganze Textfeld maskieren (am Ende).

Ziel: Ein eingebettetes YouTube-Video erscheint erst nach Zustimmung. Das Beispiel nutzt `site/templates/basic-page.php`. Bei anderen Templates ist die Stelle gleich, an der `$page->body` ausgegeben wird.

1. Im Tab **Cookies** bei **Externe Inhalte** die [Vorlage für YouTube](docs/cookie-vorlagen.md#vorlage-youtube) eintragen (Dienst `---youtube`).
2. Ein Feld für den Embed-Code anlegen: **Setup** → **Fields** → **Add New**, Typ **Textarea**, Name `embed_code`, speichern. Dem Feld keinen HTML maskierenden Textformatter zuweisen (z. B. „HTML Entity Encoder“).
3. Beide Felder dem Template hinzufügen: **Setup** → **Templates** → `basic-page` → Tab **Fields** → `embed_code` und `cmnstr_service` hinzufügen.
4. Die Seite bearbeiten. Im Feld **cmnstr_service** den Dienst YouTube wählen und im Feld `embed_code` den Embed-Code ablegen. Jeder `<iframe>` braucht ein `title`:

    ```html
    <iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" title="Beispielvideo"></iframe>
    ```

5. Das Template umstellen. Vorher:

    ```php
    <div id="content">
    	<?= $page->body ?>
    	<?= $page->embed_code ?>
    </div>
    ```

    Nachher (`$cmnstr` steht in Templates direkt zur Verfügung):

    ```php
    <div id="content">
    	<?= $page->body ?>
    	<?= $cmnstr->maskContent($page->embed_code, $page->cmnstr_service) ?>
    </div>
    ```

Erwartet: Ohne Zustimmung erscheint statt des Videos eine Maske mit „Zulassen“ und „Einmal laden“, mit Zustimmung das Video. Masken nur in Templates ohne Template-Cache einsetzen ([Bekannte Grenzen](#bekannte-grenzen)).

Vimeo analog: [Vorlage für Vimeo](docs/cookie-vorlagen.md#vorlage-vimeo) eintragen, bei **cmnstr_service** Vimeo wählen, Embed-Code mit `title` in `embed_code` ablegen (z. B. `<iframe src="https://player.vimeo.com/video/76979871" title="Beispielvideo" allowfullscreen></iframe>`).

Alternative: das ganze Textfeld mit `maskContent($page->body, $page->cmnstr_service)` maskieren, dann bleibt der gesamte Text bis zur Zustimmung verborgen. Eine Prüfung auf Einbettungen im Text gibt es nicht (`checkForExternalContent` entfiel, siehe [Änderungen gegenüber 4.5](#änderungen-gegenüber-45)).

Probleme: [Maske bleibt trotz Zustimmung](#maske-bleibt-trotz-zustimmung).

### Einmal laden

Inhaltsmasken für einen einzelnen Dienst (Schlüssel mit Dienst-ID, z. B. `external-youtube`) bieten zusätzlich „Einmal laden“ (Text: **Button: Einmal laden**, mehrsprachig). Der Button sendet `cmnstr-once` an `/_cmnstr/consent` (auch ohne JavaScript).

- Befristet: Die Freigabe gilt für diesen Dienst bis Sitzungsende (ProcessWire-Session-Cookie, spätestens beim Schließen des Browsers) und für alle Masken desselben Dienstes.
- Wirkung: Der Button gibt alle in der Maske genannten Dienste frei, höchstens 10 je Absenden ([Einmal laden im Code](#einmal-laden-im-code)).
- Nicht angeboten: bei Kategorie-Masken (`external`), `all`, notwendigen Diensten und, bei aktivem GPC, bei Diensten einer GPC-Kategorie. Der Server prüft das beim Absenden erneut.
- Zurückgesetzt: Jede gespeicherte Entscheidung (auch „Alle ablehnen“ und Erneuern) leert die Liste.
- Datenschutzerklärung: Besucher separat informieren ([Datenschutzerklärung ergänzen](#datenschutzerklärung-ergänzen)).

### Dialog ans Corporate Design anpassen

Rolle: Betrieb und Entwicklung (CSS). Variablen: [Gestaltung und JavaScript](#gestaltung-und-javascript).

1. Im Tab **Cookie-Banner** → **Anzeige** prüfen, dass **Standard-Stylesheet** eingeschaltet ist.
2. Die Custom Properties in der Haupt-CSS-Datei der Website überschreiben (nicht im Modulordner), ohne `@layer`. Solche Regeln gewinnen gegen das Modul-CSS in `@layer cmnstr`, ein eigener Layer verliert in der Regel.
3. Die vier wichtigsten Werte setzen (HEX-Werte funktionieren direkt):

    ```css
    :root {
    	--cmnstr-primary-color: #0b5fa5;
    	--cmnstr-bg: #ffffff;
    	--cmnstr-text: #1a1a1a;
    	--cmnstr-font-family: Georgia, serif;
    }
    ```

4. Kontrast prüfen: `--cmnstr-text` auf `--cmnstr-bg` und der Button-Text (`--cmnstr-bg`) auf `--cmnstr-primary-color` brauchen mindestens 4,5:1 (WCAG 1.4.3).
5. Banner, Dialog, Maske und Fokusring im Browser ansehen.

### Datenschutzerklärung ergänzen

Rolle: Redaktion. Texte vor der Veröffentlichung mit den Datenschutzbeauftragten oder dem Anwalt abstimmen ([Rechtliche Grenzen und Verantwortung](#rechtliche-grenzen-und-verantwortung)). Die Rechtsgrundlage (in eckigen Klammern im Textbaustein) legt der Betreiber fest.

1. Den Textformatter **CookieMonster Textformatter** dem Textfeld der Datenschutzseite zuweisen: **Setup** → **Fields** → Textfeld → Tab **Details** → **Textformatters**.
2. Im Text der Seite eine der beiden Marken einfügen:

    | Marke              | Ausgabe                                           |
    | ------------------ | ------------------------------------------------- |
    | `[[cookie-table]]` | Tabelle aller konfigurierten Cookies              |
    | `[[cookie-list]]`  | Liste der Kategorien und Dienste, ohne Checkboxen |

3. Unter der Übersicht zeigen beide „Ihre Consent-ID: …“, sobald der Besucher entschieden hat und das Einwilligungsprotokoll aktiv ist.
4. Einen der beiden Textbausteine in die Datenschutzerklärung übernehmen. Maßgeblich ist **Statistik** → **Aufbewahrung des Einwilligungsprotokolls** ([Einwilligungsprotokoll](#einwilligungsprotokoll)): mit Frist der erste, bei „Kein Logging“ der zweite Baustein. Eckige Klammern und Dauer an die Einstellungen anpassen.

Erster Textbaustein, bei aktivem Einwilligungsprotokoll:

> **Einwilligungsverwaltung (Cookie-Banner).**
>
> - Wir speichern Ihre Cookie-Auswahl in einem Cookie (`cmnstr`, 180 Tage), um sie bei weiteren Besuchen zu berücksichtigen, und protokollieren sie auf unserem Server, um sie nachweisen zu können ([Rechtsgrundlage vom Betreiber zu prüfen: Art. 6 Abs. 1 lit. c i. V. m. Art. 7 Abs. 1 DSGVO oder lit. f]).
> - Gespeichert werden eine zufällige Consent-ID, ein Kürzel (Hash) des damals gültigen Konfigurationsstands, der Zeitpunkt, Ihre Auswahl je Kategorie und Dienst, die Art der Entscheidung (z. B. „alle akzeptiert“), ein aktives Browser-Signal (GPC/DNT, falls berücksichtigt) und gelöschte Cookies.
> - Wir speichern keine IP-Adresse, keinen User-Agent und keine besuchte Seite.
> - Die Einträge löschen wir [3 Jahre] nach Ablauf Ihrer Einwilligung.
> - Ohne Ihre Auswahl speichern wir keine Cookie-Auswahl.
> - Wenn Sie in einem blockierten Inhalt „Einmal laden“ wählen, willigen Sie für diesen einen Dienst bis zum Ende der Sitzung ein.
> - Die Freigabe halten wir in einem Sitzungs-Cookie von ProcessWire (ohne feste Laufzeit, es endet spätestens mit dem Schließen des Browsers); ein weiterer Cookie `cmnstr` entsteht dadurch nicht.
> - Ihre Consent-ID finden Sie in dieser Datenschutzerklärung unter der Cookie-Übersicht.
> - Sie können uns Ihre Consent-ID mitteilen, damit wir Ihre Einträge zuordnen können (Art. 11 Abs. 2 DSGVO); Ihre Rechte nach Art. 15 bis 21 DSGVO bleiben unberührt.

Zweiter Textbaustein, bei „Kein Logging“ (statt des obigen; die Beschreibung des Cookies `cmnstr` in den notwendigen Cookies entsprechend anpassen, sie ändert sich beim Umschalten nicht automatisch):

> **Einwilligungsverwaltung (Cookie-Banner).**
>
> - Wir speichern Ihre Cookie-Auswahl in einem Cookie (`cmnstr`, 180 Tage), um sie bei weiteren Besuchen zu berücksichtigen.
> - Das Cookie enthält Ihre Auswahl je Kategorie und Dienst, den Zeitpunkt der Entscheidung und ein aktives Browser-Signal (GPC/DNT, falls berücksichtigt), aber keine Kennung.
> - Auf unserem Server protokollieren wir Ihre Auswahl nicht.
> - Wenn Sie in einem blockierten Inhalt „Einmal laden“ wählen, willigen Sie für diesen einen Dienst bis zum Ende der Sitzung ein.
> - Die Freigabe halten wir in einem Sitzungs-Cookie von ProcessWire (ohne feste Laufzeit, es endet spätestens mit dem Schließen des Browsers); ein weiterer Cookie `cmnstr` entsteht dadurch nicht.

Die Consent-ID erscheint nur in den Cookie-Übersichten ([Consent-ID und Nachweis](#consent-id-und-nachweis)).

### Consent über Subdomains teilen

Rolle: Betrieb.

Im Tab **Cookies** → **Consent-Cookie** bei **Consent über Subdomains teilen** die Hauptdomain eintragen, z. B. `beispiel.de` für `shop.beispiel.de`. Leer (Standard) gilt der Consent nur für den aktuellen Host. Der Wert muss der aufgerufene Host oder eine übergeordnete Domain sein, sonst wird er ignoriert.

- Jede Subdomain soll CookieMonster selbst ausliefern (Cookie vom selben Server), sonst begrenzt Safari die Laufzeit auf 7 Tage.
- Jede Subdomain fragt nur nach ihren eigenen Diensten. Was nur eine andere anbietet, bleibt im Cookie erhalten. Bietet eine Subdomain einen zusätzlichen Dienst an, öffnet der Banner dort erneut (bisherige Entscheidungen vorausgewählt). „Alle akzeptieren“ bzw. „Alle ablehnen“ gilt nur für die Dienste der jeweiligen Subdomain.
- Gemeinsame Dienste brauchen überall dieselbe ID (z. B. `statistics-ga`) und Beschreibung. Sonst gilt eine Zustimmung für etwas, das so nicht angezeigt wurde, oder eine Subdomain löscht (auch auf übergeordneten Domains) Cookies, denen auf einer anderen zugestimmt wurde.
- Alle Subdomains sollen dieselbe Modulversion nutzen: Ein Cookie einer neueren Version liest das Modul unverändert (keine Rückmigration), eine ältere Version migriert es bei jedem Aufruf neu.
- Das Einwilligungsprotokoll führt die Installation, auf der die Entscheidung fiel. Bei getrennten Installationen lassen sich Einträge über die Consent-ID zusammenführen.

### Übersetzungen

Rolle: Betrieb.

Die Quellsprache der Modultexte ist Deutsch (`__()`, `_n()`, `_x()`). Das Modul liefert keine Übersetzungsdateien mit. Voraussetzung für weitere Sprachen ist das Modul Language Support.

Es gibt zwei Orte, beide in der gewohnten ProcessWire-Oberfläche:

| Was                                                                                                                                          | Wo                                                                                                                                                                                                                                                                                                       |
| -------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Feste Frontend-Texte**: „Aktiv“, „Schließen“, Spaltenköpfe der Cookie-Tabelle, Masken-Buttons, Statusmeldungen, vorbelegte Cookie-Einträge | **Setup** → **Languages** → Sprache → **Site Translation Files** → `CookieMonster.module.php` (wie bei jedem Modul). Die festen Texte stehen dort gesammelt als Methode `texts()`, bei jedem steht ein Hinweis, wo er erscheint; Texte der Konfigurationsoberfläche stehen in `CookieMonster.config.php` |
| **Redaktionelle Texte**: Hinweis, Buttons, Kategorien, Dienste, Masken-Text                                                                  | Modul-Konfiguration, je Sprache im Sprach-Tab des Feldes. Sie haben Vorrang vor den festen Texten                                                                                                                                                                                                        |

- Jeder feste Text steht nur einmal in der Datei, auch wenn er an mehreren Stellen erscheint („Aktiv“ im Hinweis, im Dialog und in der Liste). Eine Übersetzung gilt überall.
- Platzhalter wie `%s` und `{categories}` unverändert lassen.
- Nicht übersetzte Texte erscheinen auf Deutsch.
- Die Übersetzungsdatei für die Standardsprache lässt sich ebenfalls bearbeiten, wenn der deutsche Wortlaut angepasst werden soll.
- Die Admin-Beschriftungen und Meldungen des Moduls (`CookieMonster.config.php`, `CookieMonster.module.php`, `FieldtypeCookieService.module.php`, `InputfieldCookieService.module.php`) stehen in eigenen Dateien. Sie sind für Besucher nicht sichtbar.

Zur rechtlichen Prüfung siehe [Rechtliche Grenzen und Verantwortung](#rechtliche-grenzen-und-verantwortung).

### Standardwerte prüfen

Rolle: Betrieb. Die Tabelle nennt je Einstellung Standard, Empfehlung und Handlungsbedarf. Die rechtliche Einordnung bleibt Sache des Betreibers.

| Einstellung                                  | Standard                               | Übernehmen?                                                                                                                                                         | Aufgabe                                                |
| -------------------------------------------- | -------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------ |
| **Cookieloses Tracking** (Matomo)            | Aus, Matomo lädt nur mit Zustimmung    | Ja, solange die Datenschutzberatung den cookielosen Betrieb nicht freigegeben hat                                                                                   | Nur bei Matomo: Modus festlegen                        |
| **Bei mehreren Einträgen:**                  | UND                                    | Ja. ODER nur bei einem Dienst in mehreren Kategorien, z. B. Tag Manager                                                                                             | Nur beim Tag Manager in zwei Kategorien                |
| **Aufbewahrung des Einwilligungsprotokolls** | 3 Jahre nach Ablauf der Einwilligung   | Betreiber entscheidet, das Gesetz nennt keinen Wert. Begründen und im Verzeichnis der Verarbeitungstätigkeiten festhalten                                           | Ja: Frist festlegen und dokumentieren                  |
| **Global Privacy Control (GPC)**             | Aus                                    | Freiwillig. Eingeschaltet gilt Marketing als abgelehnt ([Browser-Signale](#browser-signale-gpc-und-dnt))                                                            | Nein                                                   |
| **Do Not Track (DNT)**                       | Aus                                    | Ja, DNT ist veraltet und überstimmt nichts                                                                                                                          | Nein                                                   |
| **CSP-Header senden**                        | Aus (danach Report-Only)               | Erst im Report-Only-Modus prüfen, dann durchsetzen ([Security-Header](#security-header))                                                                            | Nur mit Entwicklung einschalten                        |
| **HSTS-Header senden**                       | Aus                                    | Erst, wenn Website und Subdomains dauerhaft per HTTPS laufen                                                                                                        | Nur mit Entwicklung einschalten                        |
| **Einbetten erlauben**                       | Nur auf dieser Website                 | Ja, außer andere Websites sollen die Seiten einbetten                                                                                                               | Nein                                                   |
| **Template-Cache beibehalten**               | Aus                                    | Ja ([Bekannte Grenzen](#bekannte-grenzen))                                                                                                                          | Nein                                                   |
| **Fehlendes Dienst-Feld**                    | Inhalt anzeigen                        | Maskieren ist sicherer, sobald alle Templates `cmnstr_service` nutzen ([Entfernte Dienste und fehlendes Dienst-Feld](#entfernte-dienste-und-fehlendes-dienst-feld)) | Prüfen, ob alle Templates das Feld nutzen              |
| Sessions für Erstbesucher                    | ProcessWire startet immer eine Session | Ja, solange kein Bedarf besteht ([Sessions ohne Cookie für Erstbesucher](#sessions-ohne-cookie-für-erstbesucher))                                                   | Nein, das Session-Cookie steht in der Cookie-Übersicht |

### Checkliste vor dem Livegang

Nach Rollen verteilt. Die technischen Prüfungen stehen im [Abnahmeprotokoll](#abnahmeprotokoll).

#### Betrieb und Redaktion

- [ ] Impressum und Datenschutzerklärung sind im Bereich **Rechtliche Seiten** gewählt.
- [ ] Jede angebotene Kategorie hat Dienste mit eigener Zeile `---ID|…`. Jeder Tracking-Code hat Mess-ID und **Erforderliche Zustimmung**.
- [ ] `beispiel.de` ist in allen Zeilen ersetzt, es stehen nur Cookies drin, die die Website setzt ([Welche Cookies setzt meine Seite?](#welche-cookies-setzt-meine-seite)).
- [ ] Die Datenschutzerklärung enthält Cookie-Übersicht und Textbaustein zur Einwilligungsverwaltung.

#### Entwicklung

- [ ] Link „Cookie-Einstellungen“ im Footer ([Footer-Link setzen](#footer-link-setzen), Pflicht).
- [ ] Bestehender Tracking-Code im Template entfernt ([Tracking-Codes zuordnen](#tracking-codes-zuordnen)).
- [ ] Template-Cache für Templates mit Masken aus (Standard).
- [ ] CSP zuerst im Report-Only-Modus (meldet nur, blockiert nichts) geprüft, Meldungen im Log `cookiemonster-report` ausgewertet.
- [ ] Abnahmeprotokoll durchlaufen (ohne Zustimmung, Netzwerk, ohne JavaScript, Tastatur, 200 % Zoom).

#### Datenschutz

- [ ] Aufbewahrung des Einwilligungsprotokolls festgelegt, geprüft und im Verzeichnis der Verarbeitungstätigkeiten dokumentiert.
- [ ] Banner-Texte mit Datum archiviert, wenn der Nachweis darauf beruht.
- [ ] Übersetzungen und Texte rechtlich geprüft.
- [ ] Einstufung der Dienste (Kategorie) und Rechtsgrundlage abgestimmt.

### Abnahmeprotokoll

Rolle: Entwicklung.

| Prüfung                     | Vorgehen                                                         | Erwartung                                                                             |
| --------------------------- | ---------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| Ohne Zustimmung             | Website im privaten Fenster öffnen, nichts anklicken             | Hinweis erscheint, nur Session-Cookie (`wire` oder `wires`), kein `cmnstr`            |
| Netzwerk vor der Zustimmung | Entwicklerwerkzeuge, Tab „Netzwerk“, neu laden                   | Keine Requests an Drittanbieter (Google, Matomo, YouTube usw.)                        |
| Mit Zustimmung              | „Alle akzeptieren“                                               | Seite lädt neu, Hinweis weg, `cmnstr` gesetzt, Tracking-Codes und Masken greifen      |
| Ablehnen und Ändern         | „Alle ablehnen“, dann Dialog über den Footer-Link öffnen         | Auswahl änderbar, löschbare Cookies abgelehnter Dienste fehlen                        |
| Ohne JavaScript             | JavaScript ausschalten, neu laden                                | Hinweis erscheint, Buttons senden, Footer-Link öffnet den Dialog (`?cmnstr-settings`) |
| Tastatur                    | Nur Tab, Umschalt+Tab, Enter, Leertaste                          | Fokus immer sichtbar, alle Buttons und Checkboxen erreichbar                          |
| 200 % Zoom                  | Browser-Zoom auf 200 %                                           | Hinweis und Dialog bedienbar, nichts abgeschnitten                                    |
| Dialog schließen            | Dialog über den Footer-Link öffnen, mit Schließen-Button beenden | Fokus kehrt zum öffnenden Element zurück                                              |

### Betrieb

Rolle: Betrieb und Entwicklung.

- Logs: `cookiemonster` (Warnungen, z. B. `null`-Werte, nicht gesendete Header) und `cookiemonster-report` (CSP-Verstöße) in `site/assets/logs` regelmäßig ansehen.
- Backup: Das Einwilligungsprotokoll `cookiemonster-consent` in `site/assets/logs` gehört ins Backup. Die Frist legt der Betreiber fest ([Einwilligungsprotokoll](#einwilligungsprotokoll)), LazyCron löscht täglich, aber nur bei Seitenaufrufen.
- Auskunft: Zeilen zur genannten Consent-ID im Protokoll heraussuchen (Format: [Consent-Statistik](#consent-statistik)).
- Nach Updates: Der Banner fragt alle Besucher neu, Tracking-Zuordnung prüfen ([Upgrade auf 4.6.0](#upgrade-auf-460)).

## Rechtliche Grenzen und Verantwortung

Rolle: Betrieb. Dieser Abschnitt dient der Orientierung und ist keine Rechtsberatung. Texte und Einstufungen mit der Datenschutzberatung abstimmen, die Verantwortung für Banner-Texte, Einstufung der Dienste, Aufbewahrung und Datenschutzerklärung liegt beim Betreiber.

### Nicht geprüft

- Behördenzuständigkeit nach § 28 TDDDG (welche Behörde zuständig ist, klärt der Anwalt)
- die Einwilligungsverwaltungsverordnung (ob und wie sie für die Website gilt, klärt der Anwalt)
- Art. 6 Abs. 1 lit. c gegenüber lit. f DSGVO (welche Rechtsgrundlage zum Protokoll passt, klären Datenschutzbeauftragte oder Anwalt)
- die Frist „3 Jahre“ für das Einwilligungsprotokoll (ob sie angemessen ist, klären Datenschutzbeauftragte oder Anwalt)
- die Texte in der Modul-Konfiguration und alle Übersetzungen: Für Banner-, Dialog- und Maskentexte (Einwilligung, Widerruf, Drittländer, Datenschutz) ist je Zielsprache eine rechtliche Prüfung empfohlen.
- die [Cookie-Vorlagen](docs/cookie-vorlagen.md): Beispiele ohne Gewähr, Anbieter ändern Namen und Laufzeiten (Abgleich mit den Anbieterangaben: Entwicklung).

Datenschutzbeauftragte oder Anwalt sollen diese Punkte vor dem Livegang prüfen.

### Einwilligungsprotokoll

Das Log `cookiemonster-consent` ist eine Verarbeitung personenbezogener Daten und gehört ggf. in das Verzeichnis der Verarbeitungstätigkeiten nach Art. 30 DSGVO. Ob und wie lange protokolliert wird, entscheidet der Betreiber.

- **Einstellung:** Tab **Statistik** → **Aufbewahrung des Einwilligungsprotokolls**: Kein Logging, 180 Tage oder 1 bis 5 Jahre (Standard 3 Jahre), jeweils nach Ablauf der Einwilligung.
- **Frist:** Entscheidung des Betreibers, das Gesetz nennt keinen Wert, „3 Jahre“ ist nicht belegt. Nach EDPB-Leitlinien 05/2020 (Rn. 107) nur so lange speichern, wie unbedingt erforderlich: orientiert am Zweck des Nachweises und an Verjährungsfristen, dokumentiert im Verzeichnis der Verarbeitungstätigkeiten. Ein „Nicht löschen“ gibt es nicht.
- **Inhalt je Zeile:** Zeitstempel, Consent-ID, Art der Entscheidung (`accept-all`, `decline-all`, `custom`, `grant:…`, bei mehreren Diensten z. B. `grant:statistics-ga,statistics-gtm`, `renew`, `once`, `purge`), aktive Browser-Signale (`gpc=1`, `dnt=1`), Konfigurations-Hash (`cfg=…`) und Consent-Stand. Ein erneutes Absenden ohne Änderung steht als `renew` (setzt den Ablauf neu).
- **Nicht gespeichert:** Benutzername, URL, IP-Adresse und User-Agent.
- **Löschen:** Täglich per LazyCron (nur bei Seitenaufrufen) entfernt das Modul Zeilen älter als Aufbewahrung plus 180 Tage (Cookie-Laufzeit), z. B. bei 3 Jahren nach 3 × 365 + 180 Tagen. Das gilt auch bei „Kein Logging“ (mit 3 Jahren), damit Altbestand ausläuft (frühere Versionen speicherten Benutzername und URL).
- **Ablageort:** `site/assets/logs/cookiemonster-consent.txt` im Webroot. ProcessWire sperrt den Ordner über die mitgelieferte `.htaccess` (HTTP 403), bei anderen Webservern als Apache die Sperre selbst sicherstellen.
- **„Kein Logging“:** Keine neuen Zeilen mehr (auch kein `purge`, `renew`), kein Verlauf der Konfiguration. Bestehende Cookies verlieren ihre `_id` beim nächsten Schreiben, vorhandene Zeilen bleiben bis zum Löschen. Wieder eingeschaltet, erhält jede neue Entscheidung eine neue ID.
- **Cookie-Beschreibung:** Die Beschreibung von `cmnstr` in den notwendigen Cookies ist nur vorbefüllt und nennt die Kennung für den Fall eines aktiven Einwilligungsprotokolls. Bei ausgeschaltetem Protokoll den Satz selbst kürzen.
- **Banner-Texte:** Welchen Text ein Besucher sah, speichert das Modul nicht. Wer den Nachweis darauf stützt, archiviert die Texte (Hinweis, Erklärung, Dienste und Cookies) selbst mit Datum, z. B. bei jeder Änderung.

### Consent-ID und Nachweis

Die Consent-ID erscheint als „Ihre Consent-ID: …“ unter der Übersicht von `[[cookie-table]]` und `[[cookie-list]]` (bzw. `renderCookieTable()`, `renderCookieList()` ohne Checkboxen) und nur, wenn der Besucher entschieden hat und das Logging aktiv ist. Das Modul gibt im Template einen Platzhalter aus, den es je Request ersetzt oder entfernt, Seiten im Template-Cache enthalten deshalb nie eine ID. Außerhalb eines Seiten-Renderings (z. B. in E-Mails) bleibt der Platzhalter ein HTML-Kommentar.

Zu Art. 11 und 13 DSGVO (Einordnung des Autors, nicht rechtlich geprüft):

- Art. 11 Abs. 2 DSGVO (Betroffenenrechte nur mit zusätzlich bereitgestellter Information) gilt nur, wenn der Betreiber nachweist, die Person nicht identifizieren zu können.
- Ohne Consent-ID lassen sich Einträge in der Regel keiner Person zuordnen, nicht in jedem Fall aber, insbesondere wenn Zugriffsprotokolle eine zusätzliche Identifizierung erlauben.
- Rückfragen zur Identität sind nach der Orientierungshilfe Digitale Dienste der DSK (Rn. 141) nur bei konkreten, begründeten Zweifeln zulässig. Ein ID-Cookie braucht nach Rn. 143 eine Laufzeit.
- Die Informationen nach Art. 13 (Zweck, Rechtsgrundlage, Dauer, Rechte) gehören in die Datenschutzerklärung.
- Das Cookie ist HttpOnly, die ID nur über die Anzeige oder die Entwicklerwerkzeuge einsehbar. Wo die ID im HTML einer Rechtsseite steht, können dort geladene Skripte sie lesen.

Zur Consent-ID als Nachweis (Einordnung des Autors, nicht rechtlich geprüft; Quellen: Orientierungshilfe Digitale Dienste der DSK, Version 1.2, November 2024; EDPB-Leitlinien 05/2020):

- Nach DSK Rn. 81 und 84 sind langlebige Cookies mit eindeutiger Kennung für den Einwilligungsnachweis nicht notwendig, im Regelfall genügt der Prozessnachweis mit einem Cookie ohne Kennung.
- Der EDPB (Rn. 106 bis 108) erlaubt die Speicherung der Einwilligung zum Nachweis, verlangt aber Datenminimierung. Ein bloßer Verweis auf die korrekte Konfiguration genügt nicht.
- Die Aufsichtsbehörden gewichten das unterschiedlich. Der Betreiber entscheidet und muss das begründen können.

### Verlauf der Konfiguration

Zweck: Prozess- und Konfigurationsnachweis, welche Texte (auch frühere), Kategorien und Dienste wann galten. Er erleichtert den Nachweis einzelner Einwilligungen, ersetzt ihn aber nicht in jedem Fall. Orientierung (Einordnung des Autors, keine Rechtsberatung, nicht geprüft): DSK-Orientierungshilfe Digitale Dienste 1.2 (Rn. 84, 85), EDPB-Leitlinien 05/2020 (Rn. 106 bis 108).

- **Hash:** 12 Hex-Zeichen (SHA-256) über einen normalisierten Snapshot (Schlüssel sortiert, Zeilenumbrüche vereinheitlicht, leere Werte entfallen, Listen sortiert). Enthalten: Kategorien (Titel, Beschreibung, Aktivierung, Cookie-Listen mit Diensten), Banner-, Dialog-, Button- und Masken-Texte in allen Sprachen (`name__<Sprach-ID>`), GPC/DNT-Einstellungen und Hinweise, Impressum- und Datenschutzseite, Tracking-Einstellungen mit Tracking-IDs (öffentliche Kennungen) und Dienst-Zuordnung.
- **Nicht im Snapshot:** Security-Header, Aufbewahrung, Cookie-Domain, Zeitstempel, Epoche, Nonces, interne Marker und die Zuordnung über das Feld `cmnstr_service` (Seiteninhalt).
- **Cache:** Hash, Snapshot und Zeitpunkt der letzten Protokollierung liegen in der Modul-Konfiguration (`config_hash`, `config_snapshot`, `config_logged_at`). Neu berechnet werden sie beim Speichern der Konfiguration (Hook `Modules::saveConfig`, im Admin wie per `saveConfig()`) und beim Upgrade, nicht bei jedem Request und nicht bei Änderungen direkt in der Datenbank.
- **Log `cookiemonster-config`:** Ändert sich der Hash, schreibt das Modul eine Zeile mit Zeit, Hash, Art (`initial`, `change`), Modulversion, vollständigem Snapshot (JSON, einzeilig) und Diff zum vorigen Stand (geänderte Schlüssel mit `old`/`new`, Texte auf 500 Zeichen gekürzt). Speichern ohne relevante Änderung (z. B. nur Security-Header) schreibt nichts. Der erste Snapshot entsteht beim ersten Einwilligungs-Log nach der Einführung oder beim nächsten Speichern.
- **Pruning-Lücke:** Das Log wird mit derselben Frist gelöscht wie das Einwilligungsprotokoll (Aufbewahrung plus 180 Tage). Damit ein Hash in alten Einwilligungen auflösbar bleibt, schreibt das Modul beim Protokollieren den aktuellen Snapshot erneut (`refresh`), wenn der letzte Eintrag älter als 30 Tage ist.
- **Cookie und Consent-Log:** Der Hash steht als `_c` im Cookie und als `cfg=…` in der Logzeile. Ein geänderter Hash macht Einwilligungen nicht ungültig. Nach jedem Versionswechsel (neue Epoche) gibt es eine neue Consent-ID, Statistiken können bis zum Ablauf der alten Logzeilen Besucher doppelt zählen.
- **Anzeige:** Tab **Statistik**, **Verlauf der Konfiguration**: die letzten 50 Änderungen mit Zeit, Hash und geänderten Feldern (alt → neu), escaped, ohne Snapshot-Erneuerungen. Das Log enthält Konfigurationstexte, keine personenbezogenen Daten (kein Benutzer, keine URL, keine IP). Texte sollen keine personenbezogenen Angaben enthalten.
- **Grenzen:** Kein Verlauf vor der Einführung. Nach dem Löschen (Frist) ist der Stand nur noch über den Hash belegt. Nie gespeicherte Standardwerte stehen nicht im Snapshot und erscheinen als Änderung, sobald sie gespeichert werden. Der Verlauf belegt den Konfigurationsstand, nicht die ausgelieferte Seite (Templates, CSS, Übersetzungen außerhalb der Modul-Konfiguration).

### Consent-Statistik

Tab **Statistik** → **Consent-Statistik** wertet das Einwilligungsprotokoll auf Abruf aus und zeigt nur Summen (keine IPs, Benutzer oder URLs).

- Zeitraum: immer der gesamte (letzte Entscheidung je Consent-ID). Gerechnet wird nur beim Anzeigen der Konfigurationsseite (nicht beim Speichern) und nur mit der Berechtigung `module-admin`.
- Gezählt wird je Consent-ID die letzte Entscheidung (`accept-all`, `decline-all`, `custom`, `grant:…`; `purge` zählt nicht): Anzahl, Anteil „Alle akzeptiert“, „Nur notwendige“ und „Individuell“ (`custom`, `grant:…`). Je Kategorie (ohne „Notwendige“) die Zustimmungsquote: alle Dienste `true`, bezogen auf die Entscheidungen, in denen die Kategorie angeboten wurde.
- Wer nie interagiert hat, steht nicht im Protokoll und ist nicht enthalten. `renew`-Zeilen (unveränderte erneute Entscheidung) zählen nicht, es gilt die letzte Entscheidung davor.
- `cookiemonster-consent.txt` wird ohne die Log-API von hinten gelesen. Der Speicherbedarf wächst nur mit der Zahl der Consent-IDs, bei sehr großen Logs öffnet die Konfigurationsseite langsamer. Erwartetes Zeilenformat: `Zeitstempel<Tab>…<Tab>{id} {aktion}[ gpc=1][ dnt=1] {json}`, nicht lesbare Zeilen werden übersprungen.
- Je kürzer die Aufbewahrung, desto kleiner der auswertbare Zeitraum (bei „Kein Logging“: [Einwilligungsprotokoll](#einwilligungsprotokoll)).

### Browser-Signale GPC und DNT

Beide Signale sind standardmäßig aus (Tab **Cookies** → **Datenschutz-Signale des Browsers (GPC / DNT)**). Ob GPC berücksichtigt wird, entscheidet der Betreiber.

- Global Privacy Control (GPC): § 26 TDDDG nennt GPC nicht. § 26 Abs. 2 Nr. 3 ist nur eine Verordnungsermächtigung für Browsereinstellungen (eine darauf beruhende Einwilligungsverwaltungsverordnung wurde nicht geprüft). Die Option **Global Privacy Control (GPC)** ist freiwillig.
- Wirkung bei aktivem GPC: Sendet der Browser `Sec-GPC: 1`, gelten die gewählten Kategorien (**Betroffene Kategorien**, Standard: Marketing) als abgelehnt. Pauschale Zustimmungen („Alle akzeptieren“ oder vor dem Signal erteilt) werden überstimmt, einzeln bei aktivem Signal erteilte bleiben gültig (`_gpc` im Cookie). Der Banner zeigt einen Hinweis (**Hinweis bei aktivem GPC-Signal**).
- Do Not Track (DNT): Wird, wenn aktiviert, nur als Hinweis angezeigt und im Protokoll vermerkt. DNT überstimmt nichts, denn es ist veraltet und war teils voreingestellt.

## Barrierefreiheit

Ziel ist WCAG 2.2 Level AA. Ein Konformitätsnachweis ist nicht Teil dieser Dokumentation, belegbar ist, was in Code und CSS des Moduls steht:

- Der Hinweis `#cmnstr-alert` ist ein nicht-modales `<dialog open>` mit `aria-label` (Titel) und `aria-describedby="cmnstr-alert-content"`, geöffnet ohne `showModal()`.
- Der Einstellungsdialog `#cmnstr-dialog` ist ein modales `<dialog>` (`showModal()`) mit `aria-labelledby="cmnstr-title"`. Der Schließen-Button hat ein `aria-label`.
- Nach dem Schließen geht der Fokus zurück an das öffnende Element, nach dem Speichern aus einer Maske an die Maske.
- Statusmeldungen laufen über einen unsichtbaren Bereich mit `role="status"`. Die Fußzeile des Banners ist eine `<nav>` mit `aria-label`.
- Fokusringe sind per `:focus-visible` immer sichtbar, mit hellem Saum für beliebige Untergründe.
- Das CSS berücksichtigt `prefers-reduced-motion`, `prefers-contrast: more` (Rahmen mit knapp mindestens 3:1 Kontrast) und `forced-colors`.
- Ohne JavaScript senden Hinweis, Dialog und Masken über normale Formulare mit Weiterleitung, den Dialog öffnet der Link `?cmnstr-settings`. Modal und ohne Seitenwechsel arbeitet er nur mit JavaScript.
- Der Banner steht direkt nach `<body>`, vor den Skip-Links der Site (WCAG 2.4.1). Die Site setzt ihren Skip-Link bei Bedarf vor den Banner (Template) oder belässt es bewusst so.

Nicht abgedeckt: Kontrast eigener Farben und Schriften (Custom Properties), Banner-Texte, Übersetzungen und die Bedienung der eigenen Website mit Screenreadern liegen beim Betreiber.

## Bekannte Grenzen

- Template-Cache: Das Modul umgeht den Template-Cache von ProcessWire für Templates mit aktivem Cache, sonst würden Masken, Nonces und Consent-Daten eines Besuchers allen weiteren gezeigt (Ausnahmen: `admin`, `form-builder`). **Template-Cache beibehalten** hebt das auf, sicher nur ohne Masken, Nonces und zustimmungsabhängige Inhalte.
- Full-Page-Cache: Caches, die ohne PHP antworten (z. B. ProCache), berücksichtigen Consent-Signale erst bei dynamisch erzeugten Seiten. Das Aufräumen von Cookies läuft dort nur bei einer Consent-Entscheidung.
- Teil-Renderings: Banner, Assets und Tracking-Codes fehlen in Teil-Renderings wie `$page->render('feld')` und auf den Templates `admin` und `form-builder`.
- Security-Header: Nicht bei AJAX (`X-Requested-With`), an Endpunkt-Antworten und an direkt vom Webserver ausgelieferten Dateien. Hat die Ausgabe schon begonnen, entfallen sie (Zeile im Log `cookiemonster`).
- Sessions: ProcessWire startet die Session vor jedem Modul, Besucher erhalten deshalb ein Session-Cookie ([Sessions ohne Cookie für Erstbesucher](#sessions-ohne-cookie-für-erstbesucher)).
- Cookies von Anbietern, mit anderem `path` als `/` und Browser-Speicher kann der Server nicht löschen ([Löschen ohne Zustimmung](#löschen-ohne-zustimmung)).
- Neue Abfrage: Nach jedem Versionswechsel fragt der Banner alle Besucher erneut (neue Epoche, [Glossar](#glossar)), Statistiken zählen bis zum Ablauf der alten Logzeilen doppelt.
- Banner aus: Das Verhalten ohne Banner ([Abschalten und deinstallieren](#abschalten-und-deinstallieren)) ist aus dem Code abgeleitet, nicht im Browser getestet.
- `Permissions-Policy`: Laut [MDN-Kompatibilitätsdaten](https://developer.mozilla.org/docs/Web/HTTP/Reference/Headers/Permissions-Policy) wertet nur Chromium den Header aus.

## Entwickler

Rolle: Entwicklung (Anleitungen für Betreiber und Redaktion: [Aufgaben](#aufgaben)). Das Modul registriert die API-Variable `$cmnstr` (Klasse `CookieMonster`). In Templates ist sie direkt verfügbar.

### Methoden

| Frage                                              | Methode                    | „Einmal laden“ berücksichtigt? | GPC berücksichtigt? |
| -------------------------------------------------- | -------------------------- | ------------------------------ | ------------------- |
| Allen Diensten der Statistik-Cookies zugestimmt?   | `allowTracking()`          | Nein                           | Ja                  |
| Einer Kategorie oder einem Dienst zugestimmt?      | `isUnlocked()`             | Nein                           | Ja                  |
| Mindestens ein Dienst einer Kategorie freigegeben? | `isCategoryActive()`       | Nein                           | Ja                  |
| Welche Kategorien und Dienste sind freigegeben?    | `getUnlockedCategories()`  | Nein                           | Ja                  |
| Welche Werte hat der Google Consent Mode?          | `getGoogleConsentStates()` | Nein                           | Ja                  |
| Inhalt zeigen oder maskieren                       | `maskContent()`            | Ja, öffnet nur die Maske       | Ja                  |

GPC wirkt über den Consent, nur bei `respect_gpc` (**Global Privacy Control (GPC)**) und aktivem Signal: Die Kategorien aus `gpc_categories` (Standard Marketing) gelten als abgelehnt. Eine bei aktivem Signal erteilte Zustimmung (`_gpc`) bleibt unverändert ([Browser-Signale GPC und DNT](#browser-signale-gpc-und-dnt)). „Einmal laden“: [Einmal laden im Code](#einmal-laden-im-code).

`cmnstr` ist HttpOnly, der Server entscheidet beim Rendern.

#### allowTracking

```php
allowTracking(): bool
```

Prüft, ob allen Diensten der Statistik-Cookies zugestimmt wurde (Standard für Tracking-Codes ohne eigene Zuordnung).

#### isUnlocked

```php
isUnlocked(string|string[]|null $keys, bool $any = false): bool
```

Prüft eine oder mehrere Kategorien bzw. Dienste. Mehrere Schlüssel gelten als UND, mit `$any = true` oder dem Pseudo-Schlüssel `any` als ODER ([UND und ODER](#mehrere-dienste-und-und-oder)). Ein leerer Schlüssel (`''`, `[]`) bedeutet keine Einschränkung (`true`), ein ausdrückliches `null` richtet sich nach [Entfernte Dienste und fehlendes Dienst-Feld](#entfernte-dienste-und-fehlendes-dienst-feld). Unbekannte Schlüssel werden aufgelöst.

```php
if ($cmnstr->isUnlocked('marketing')) {
	// Load marketing scripts
}

if ($cmnstr->isUnlocked('external-youtube')) {
	// Load YouTube videos
}

if ($cmnstr->isUnlocked(['external-maps', 'functional-fonts'])) {
	// Both services are allowed
}
```

#### getUnlockedCategories

```php
getUnlockedCategories(): string[]
```

Liefert alle freigeschalteten Kategorien und Dienste. Bei Zustimmung zu allem zusätzlich `all`.

```php
$keys = $cmnstr->getUnlockedCategories();
// ['essential', 'statistics', 'statistics-ga', 'external-youtube', ...]
```

#### getGoogleConsentStates

```php
getGoogleConsentStates(): array<string, string>
```

Liefert die sieben Consent-Mode-v2-Typen mit `granted` oder `denied` (Zuordnung zu Kategorien: [Google Tag Manager anbinden](#google-tag-manager-anbinden)). Beispiel bei Zustimmung nur zu den Statistik-Cookies: `analytics_storage` und `security_storage` sind `granted`, die fünf übrigen `denied`.

#### maskContent

```php
maskContent(string $html, string|string[]|null $services = null): string
```

Maskiert HTML, bis der Kategorie bzw. dem Dienst zugestimmt wurde (mehrere Schlüssel: [UND und ODER](#mehrere-dienste-und-und-oder)). Ohne Zustimmung liefert die Methode eine Maske mit Hinweis, mit Zustimmung das HTML unverändert. Eine CSP-Nonce fügt sie dem HTML nicht hinzu ([Inhalte laden und maskieren](#inhalte-laden-und-maskieren)).

```php
$iframe = '<iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" title="Example video"></iframe>';

echo $cmnstr->maskContent($iframe, 'external');
echo $cmnstr->maskContent($iframe, 'external-youtube');
echo $cmnstr->maskContent($iframe, ['external-maps', 'functional-fonts']);
```

#### renderCookieTable

```php
renderCookieTable(array $category = []): string
renderCookieList(array $category = [], bool $interactive = false): string
```

Rendert die konfigurierten Cookies als HTML-Tabelle bzw. -Liste, z. B. für die Datenschutzerklärung (Shortcodes im Textformatter: `[[cookie-table]]`, `[[cookie-list]]`). Beide hängen einen Platzhalter für die Consent-ID an (nur nicht-interaktive Ausgabe, [Consent-ID und Nachweis](#consent-id-und-nachweis)).

Parameter `$category`:

| Schlüssel     | Typ    | Bedeutung                                                                                                                             |
| ------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------- |
| _(leer)_      | –      | Leeres Array: alle aktivierten Kategorien mit Diensten.                                                                               |
| `cookies`     | string | Dienste und Cookies im [Zeilenformat](#dienste-und-cookies-eintragen). Nur damit rendert die Methode eine einzelne, eigene Kategorie. |
| `key`         | string | Schlüssel der Kategorie, z. B. `statistics`. Er dient als Präfix der Dienst-IDs.                                                      |
| `title`       | string | Titel der Kategorie.                                                                                                                  |
| `description` | string | Beschreibung der Kategorie.                                                                                                           |

`renderCookieList()` hat zusätzlich `$interactive`. Checkboxen mit eindeutigen IDs gibt nur der Einstellungsdialog aus, sonst entstünden wirkungslose Bedienelemente und doppelte IDs.

```php
// All enabled categories
echo $cmnstr->renderCookieTable();

// One custom category
echo $cmnstr->renderCookieTable([
	'key' => 'statistics',
	'title' => 'Statistics',
	'description' => 'Helps us improve the website.',
	'cookies' => "---ga|Google Analytics|Usage statistics\n_ga|example.com|Stores a random client ID.|400 Tage",
]);
```

#### getCspNonce

```php
getCspNonce(): string
```

Liefert die Nonce der aktuellen Anfrage (immer String). Im CSP-Header steht sie nur bei eingeschalteter CSP mit der Richtlinie `'nonce-proxy'`. Ohne CSP ist das Attribut `nonce` harmlos. Eigene `<script>`- und `<style>`-Tags brauchen sie unter einer durchgesetzten CSP ([CSP-Rezept mit Nonce](#csp-rezept-mit-nonce)).

#### Weitere Methoden

| Methode                                               | Beschreibung                                                                                                                                                                |
| ----------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `resolveServiceKey(string $key): string`              | Ersatz-Zustimmung für einen (ggf. entfernten) Schlüssel ([Entfernte Dienste und fehlendes Dienst-Feld](#entfernte-dienste-und-fehlendes-dienst-feld))                       |
| `getServiceLabel(string $key): string`                | Anzeigename (Dienst, Kategorie, „alle Kategorien“)                                                                                                                          |
| `getServiceOptions(array $keepKeys = []): array`      | Auswählbare Kategorien und Dienste als `[Schlüssel => Beschriftung]`                                                                                                        |
| `getServiceOptionGroups(array $keepKeys = []): array` | Dasselbe, nach Kategorien gruppiert                                                                                                                                         |
| `normalizeServiceKeys($keys): array`                  | String (Trenner: Leerzeichen, Komma, `\|`) oder Array in eine Schlüsselliste. Der Pseudo-Schlüssel `any` entfällt, `isUnlocked()` und `maskContent()` werten ihn vorher aus |
| `isCategoryActive(string $categoryKey): bool`         | Kategorie oder mindestens einer ihrer Dienste aktiv, z. B. `isCategoryActive('external')`                                                                                   |
| `getConsentId(): ?string`                             | Consent-ID, `null` ohne Entscheidung oder bei „Kein Logging“                                                                                                                |
| `getConsentUrl(): string`                             | URL der [Consent-Schnittstelle](#consent-schnittstelle), z. B. `/_cmnstr/consent`                                                                                           |
| `categoryExists(string $key): bool`                   | Kategorie bzw. Dienst konfiguriert                                                                                                                                          |
| `hasUndecidedServices(): bool`                        | Fehlt im Consent ein angebotener Dienst (Banner öffnet erneut)                                                                                                              |

### Konfigurationsschlüssel

Alle Schlüssel der Modul-Konfiguration aus `CookieMonster.config.php` (Betreiber orientieren sich an den Bezeichnungen der Oberfläche). Mehrsprachige Texte speichert ProcessWire je Sprache zusätzlich als `schlüssel__<Sprach-ID>`.

Lesen: `$modules->getConfig('CookieMonster', 'consent_log_retention')`. Einen Schlüssel setzen, andere bleiben: `$modules->saveConfig('CookieMonster', 'missing_service_mode', 'closed')`.

`getConfig()` liefert nur gespeicherte Werte, ein nie gespeicherter Standard fehlt (`null`). Mehrere Schlüssel: [Konfiguration per Code deployen](#konfiguration-per-code-deployen).

Cookie-Banner:

| Schlüssel                                                                          | Oberfläche                                                                                       | Typ, Werte         | Standard                                                         |
| ---------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ | ------------------ | ---------------------------------------------------------------- |
| `is_active`                                                                        | **Cookie-Banner**                                                                                | bool               | `true`                                                           |
| `use_stylesheet`                                                                   | **Standard-Stylesheet**                                                                          | bool               | `true`                                                           |
| `keep_template_cache`                                                              | **Template-Cache beibehalten**                                                                   | bool               | `false`                                                          |
| `missing_service_mode`                                                             | **Fehlendes Dienst-Feld**                                                                        | `open`, `closed`   | `open`                                                           |
| `infotext`                                                                         | **Hinweistext**                                                                                  | Text, mehrsprachig | Text im Modul                                                    |
| `titletext`                                                                        | **Überschrift des Einstellungsdialogs**                                                          | Text, mehrsprachig | „Cookie-Einstellungen“                                           |
| `infolabel`                                                                        | **Überschrift des ersten Abschnitts**                                                            | Text, mehrsprachig | „Ihre Privatsphäre“                                              |
| `infodetails`                                                                      | **Erklärung im ersten Abschnitt**                                                                | Text, mehrsprachig | Text im Modul                                                    |
| `buttontext_edit`, `buttontext_confirm`, `buttontext_decline`, `buttontext_accept` | **Button: Einstellungen öffnen**, **Auswahl speichern**, **Alle ablehnen**, **Alle akzeptieren** | Text, mehrsprachig | wie Bezeichnung („Einstellungen öffnen“: „Cookie-Einstellungen“) |
| `imprint_page`, `privacy_page`                                                     | **Impressum**, **Datenschutzerklärung**                                                          | Seiten-ID          | leer                                                             |
| `mask_prompt`                                                                      | **Hinweistext** (Inhaltsmaske, Platzhalter `{category}`)                                         | Text, mehrsprachig | Text im Modul                                                    |
| `mask_buttontext_required`, `mask_buttontext_once`, `mask_buttontext_all`          | **Button: Zulassen**, **Einmal laden**, **Alle akzeptieren (Maske)**                             | Text, mehrsprachig | „Zulassen“, „Einmal laden“, „Alle akzeptieren“                   |
| `mask_info`                                                                        | **Text vor dem Link zur Datenschutzerklärung**                                                   | Text, mehrsprachig | Text im Modul                                                    |

Cookies:

| Schlüssel                                                                                                                    | Oberfläche                              | Typ, Werte                                                    | Standard                            |
| ---------------------------------------------------------------------------------------------------------------------------- | --------------------------------------- | ------------------------------------------------------------- | ----------------------------------- |
| `functional_enabled`, `statistics_enabled`, `marketing_enabled`, `external_enabled`                                          | **Kategorie im Cookie-Banner anbieten** | bool (Notwendige Cookies sind immer an)                       | `false`                             |
| `essential_title`, `functional_title`, `statistics_title`, `marketing_title`, `external_title`                               | **Titel**                               | Text, mehrsprachig                                            | Name der Kategorie                  |
| `essential_description`, `functional_description`, `statistics_description`, `marketing_description`, `external_description` | **Beschreibung**                        | Text, mehrsprachig                                            | Text im Modul                       |
| `essential_cookies`, `functional_cookies`, `statistics_cookies`, `marketing_cookies`, `external_cookies`                     | **Dienste und Cookies**                 | Zeilenformat, mehrsprachig                                    | Notwendige: ProcessWire; sonst leer |
| `respect_gpc`                                                                                                                | **Global Privacy Control (GPC)**        | bool                                                          | `false`                             |
| `gpc_categories`                                                                                                             | **Betroffene Kategorien**               | Liste aus `functional`, `statistics`, `marketing`, `external` | `['marketing']`                     |
| `gpc_notice`                                                                                                                 | **Hinweis bei aktivem GPC-Signal**      | Text, mehrsprachig                                            | Text im Modul                       |
| `respect_dnt`                                                                                                                | **Do Not Track (DNT)**                  | bool                                                          | `false`                             |
| `dnt_notice`                                                                                                                 | **Hinweis bei aktivem DNT-Signal**      | Text, mehrsprachig                                            | Text im Modul                       |
| `cookie_domain`                                                                                                              | **Consent über Subdomains teilen**      | Domain                                                        | leer                                |

Tracking:

| Schlüssel                                                                                                                          | Oberfläche                                      | Typ, Werte                               | Standard   |
| ---------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------- | ---------------------------------------- | ---------- |
| `ga_property_id`                                                                                                                   | Google Analytics: **Mess-ID**                   | Text                                     | leer       |
| `gtm_id`                                                                                                                           | Google Tag Manager: **Container-ID**            | Text                                     | leer       |
| `matomo_enabled`                                                                                                                   | **Matomo-Tracking aktivieren**                  | bool                                     | `false`    |
| `matomo_url`                                                                                                                       | **Matomo-URL**                                  | URL                                      | leer       |
| `matomo_siteid`                                                                                                                    | **Website-ID**                                  | Ziffern                                  | `1`        |
| `matomo_cookieless`                                                                                                                | **Cookieloses Tracking**                        | `''` (aus), `fallback`, `cookieless`     | `''`       |
| `matomo_sharetracking`                                                                                                             | **Subdomains gemeinsam tracken**                | bool                                     | `false`    |
| `matomo_sharedomain`                                                                                                               | **Gemeinsame Domain**                           | Domain                                   | leer       |
| `matomo_documenttitle`                                                                                                             | **Fester Dokumenttitel**                        | Text                                     | leer       |
| `meta_pixel_id`                                                                                                                    | Meta Pixel: **Pixel-ID**                        | Text                                     | leer       |
| `meta_pixel_event`                                                                                                                 | **Event beim Seitenaufruf**                     | Text                                     | `PageView` |
| `linkedin_insight_id`                                                                                                              | LinkedIn Insight Tag: **Partner-ID**            | Text                                     | leer       |
| `ga_cookie_service`, `gtm_cookie_service`, `matomo_cookie_service`, `meta_pixel_cookie_service`, `linkedin_insight_cookie_service` | **Erforderliche Zustimmung** je Code            | Liste von Schlüsseln, optional mit `any` | leer       |
| `google_site_verification`                                                                                                         | **Google Search Console**: **Bestätigungscode** | Text                                     | leer       |

Sicherheits-Header und Protokoll:

| Schlüssel                        | Oberfläche                                   | Typ, Werte                                                                                               | Standard                                           |
| -------------------------------- | -------------------------------------------- | -------------------------------------------------------------------------------------------------------- | -------------------------------------------------- |
| `hsts_enabled`                   | **HSTS-Header senden**                       | bool                                                                                                     | `false`                                            |
| `hsts_max_age`                   | **Gültigkeitsdauer (max-age)**               | Sekunden: `0`, 1 bis 6 Monate (`2592000` bis `15552000`), 12 Monate (`31536000`), 24 Monate (`63072000`) | `31536000`                                         |
| `hsts_include_subdomains`        | **Subdomains (includeSubDomains)**           | bool                                                                                                     | `false`                                            |
| `hsts_preload`                   | **Preload**                                  | bool                                                                                                     | `false`                                            |
| `framing_policy`                 | **Einbetten erlauben**                       | `none`, `self`, `custom`                                                                                 | `self`                                             |
| `framing_custom_origins`         | **Erlaubte Websites**                        | `'self'` und `http(s)://host`, leerzeichengetrennt, siehe [Einbetten in Frames](#einbetten-in-frames)    | leer                                               |
| `csp_enabled`                    | **CSP-Header senden**                        | bool                                                                                                     | `false`                                            |
| `csp_policy`                     | **Richtlinie**                               | Text                                                                                                     | `CookieMonster::DEFAULT_CSP_POLICY`                |
| `csp_report_only`                | **Report-Only-Modus**                        | bool                                                                                                     | `true`                                             |
| `csp_report_to`                  | **Meldeadresse für Verstöße**                | URL oder Pfad                                                                                            | leer                                               |
| `permissions_policy_enabled`     | **Permissions-Policy-Header senden**         | bool                                                                                                     | `false`                                            |
| `permissions_policy`             | **Richtlinie**                               | Text                                                                                                     | `geolocation=(), camera=(), microphone=(), usb=()` |
| `x_content_type_options_enabled` | **X-Content-Type-Options**                   | bool                                                                                                     | `true`                                             |
| `remove_powered_by`              | **X-Powered-By**                             | bool                                                                                                     | `false`                                            |
| `referrer_policy`                | **Referrer-Policy**                          | Liste gültiger Werte                                                                                     | `strict-origin-when-cross-origin`                  |
| `coop_policy`                    | **Cross-Origin-Opener-Policy (COOP)**        | `''`, `unsafe-none`, `same-origin-allow-popups`, `same-origin`                                           | `same-origin-allow-popups`                         |
| `coep_policy`                    | **Cross-Origin-Embedder-Policy (COEP)**      | `''`, `unsafe-none`, `require-corp`, `credentialless`                                                    | `''`                                               |
| `corp_policy`                    | **Cross-Origin-Resource-Policy (CORP)**      | `''`, `same-site`, `same-origin`, `cross-origin`                                                         | `same-origin`                                      |
| `consent_log_retention`          | **Aufbewahrung des Einwilligungsprotokolls** | `none`, `180`, `365`, `730`, `1095`, `1460`, `1825` (Tage)                                               | `1095`                                             |

Interne Marker (`config_hash`, `config_snapshot`, `config_logged_at`, `consent_epoch`, `migration_pending`) sind keine Einstellungen und gehören nicht in eigenen Code.

Vorbefüllung der notwendigen Cookies (Tab **Cookies**):

Im Feld **Dienste und Cookies** der notwendigen Cookies steht vorbefüllt der Dienst „ProcessWire“ mit dem Standard-Session-Cookie `wire` und `cmnstr`. Gilt, solange das Feld nie gespeichert wurde. Weicht `$config->sessionName` ab (bei HTTPS meist `wires`) oder ist `$config->sessionChallenge` aktiv, den Namen von Hand anpassen bzw. `…_challenge` ergänzen.

### Konfiguration per Code deployen

Für Deployments und Skripte mit mehreren Schlüsseln: lesen, ändern, komplett zurückschreiben.

```php
<?php

namespace ProcessWire;

/** @var Modules $modules */
$data = $modules->getConfig('CookieMonster');

$data['statistics_enabled'] = true;
$data['statistics_cookies'] = "---ga|Google Analytics|Statistiken zur Nutzung der Website\n_ga|example.com|Stores a random client ID.|400 Tage";
$data['ga_property_id'] = 'G-XXXXXXXXXX';
$data['ga_cookie_service'] = ['statistics-ga'];

$modules->saveConfig('CookieMonster', $data);
```

- Format: `*_cookies` ist ein Text im Zeilenformat aus dem [Schnellstart](#banner-prüfen-und-ersten-dienst-eintragen), Zeilen durch `\n` getrennt. `*_cookie_service` ist eine Liste von Schlüsseln, bei mehreren steht `any` als erster Eintrag für ODER, sonst gilt UND ([UND und ODER](#mehrere-dienste-und-und-oder)).
- Keine Validierung: `saveConfig()` prüft weder Auswahlwerte (z. B. `hsts_max_age`) noch Muster der Formularfelder (z. B. Mess-ID `G-…`, `AW-…`, `DC-…`, `UA-…`). Ungültige Werte fallen erst im Betrieb auf.
- Ganzes Array: Es ersetzt die gesamte Konfiguration, nicht genannte Schlüssel gehen verloren, wenn es ohne `getConfig()` entsteht. `saveConfig('CookieMonster', 'schlüssel', $wert)` ändert nur diesen Schlüssel, `null` entfernt ihn.
- Interne Marker: `consent_epoch` nicht ändern. Fehlt oder sinkt der Wert, gelten ältere Entscheidungen wieder, eine neue Epoche fragt alle Besucher neu ([Glossar](#glossar)). `config_hash` und `config_snapshot` ebenfalls nicht ändern.
- Verlauf: `saveConfig()` löst den Hook `Modules::saveConfig` aus. Das Modul berechnet den Hash neu und schreibt bei Änderungen eine Zeile in `cookiemonster-config` (nicht bei „Kein Logging“), siehe [Verlauf der Konfiguration](#verlauf-der-konfiguration).

### Inhalte laden und maskieren

#### Eigene Tracking-Codes

Die mitgelieferten Tracking-Codes binden sich über die [Konfiguration](#konfigurationsoberfläche) ein. Für eigene Snippets (z. B. Matomo Tag Manager) prüft `allowTracking()` die Zustimmung zu allen Statistik-Diensten:

```php
<?php
/** @var CookieMonster $cmnstr */

if ($cmnstr->allowTracking()): ?>
	<script nonce="<?= $cmnstr->getCspNonce() ?>">
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

#### Bestimmte Dienste prüfen

`marketing-partner` steht für einen selbst eingetragenen Dienst `---partner` in den Marketing-Cookies. Das Script braucht die Nonce, sobald eine CSP durchgesetzt wird:

```php
<?php
/** @var CookieMonster $cmnstr */

if ($cmnstr->isUnlocked('marketing-partner')): ?>
	<script nonce="<?= $cmnstr->getCspNonce() ?>" src="https://tracker.example.com/tag.js" async></script>
<?php endif; ?>
```

#### Inhalte maskieren

Beispiel für Iframes: [maskContent](#maskcontent). Widgets ohne Script: das HTML als String, z. B. `$cmnstr->maskContent('<div>…</div>', 'external')`. Bei einem Script im maskierten HTML trägt der Aufrufer die Nonce selbst ein, `maskContent()` ergänzt keine. Ohne Nonce blockiert eine durchgesetzte CSP das Script. Die Nonce gilt nur für diese Anfrage, die Ausgabe darf nicht im Template-Cache liegen.

```php
<?php
/** @var CookieMonster $cmnstr */

$tag = '<script nonce="' . $cmnstr->getCspNonce() . '" src="https://widget.example.com/loader.js" async></script>';

echo $cmnstr->maskContent($tag, 'marketing-partner');
```

#### Inhalte einem Dienst zuordnen

Bei der Installation legt das Modul über `FieldtypeCookieService` das Feld `cmnstr_service` an. Es lässt sich in beliebige Templates (auch Repeater) einfügen und bietet alle aktiven Kategorien und Dienste zur Auswahl, z. B. `external` oder `external-youtube`. Die Auswahl entsteht bei jedem Aufruf aus der Modul-Konfiguration (in der Sprache des Benutzers), neue Dienste stehen sofort bereit. Ein nicht mehr konfigurierter Wert bleibt erhalten und erscheint als „nicht mehr konfiguriert“.

Mehrere Einträge sind wählbar (z. B. Karte mit Google Maps und Google Fonts, [UND und ODER](#mehrere-dienste-und-und-oder)). Der Wert ist ein `|`-getrennter String (z. B. `external-maps|functional-fonts`), beim Lesen trennen auch Leerzeichen und Komma (`[\s,|]+`). `any` als eigener Eintrag macht aus UND ein ODER (`any|statistics-gtm|marketing-gtm`). Der Wert lässt sich direkt übergeben:

```php
<?php
/** @var CookieMonster $cmnstr */

echo $cmnstr->maskContent($page->embed_code, $page->cmnstr_service);
```

- Selektoren: `cmnstr_service=external-youtube` trifft jede Seite, die diesen Schlüssel (auch neben anderen) enthält.
- Eigene Formulare: Das Inputfield `InputfieldCookieService` (Mehrfachauswahl, nach Kategorien gruppiert) funktioniert auch in eigenen Formularen und Modul-Konfigurationen, z. B. mit `['type' => 'CookieService', 'name' => '…']`.

### Einmal laden im Code

Zur Freigabe über „Einmal laden“ ([Einmal laden](#einmal-laden)):

- Wirkung: `cmnstr` bleibt unberührt. `isUnlocked()`, Tracking-Codes, Google Consent Mode, `allowTracking()`, die Checkboxen in `[[cookie-list]]` und der Einstellungsdialog kennen die Freigabe nicht. Sie wirkt nur in `maskContent()` und verhindert das Löschen von Cookies dieses Dienstes. Ein nicht maskierter Tag (z. B. Google Analytics zum selben Dienst) bleibt gesperrt.
- Protokoll: Bei aktivem Einwilligungsprotokoll schreibt das Modul eine Zeile `<Consent-ID> once <Dienst>`, einmal je Dienst und Session. Ohne Entscheidung des Besuchers gibt es keine ID und keine Zeile, die Freigabe funktioniert trotzdem. Die Zeile belegt die befristete Entscheidung, ersetzt aber keinen Nachweis der dauerhaften Einwilligung. Die Statistik zählt `once` nicht.
- Grenze: `cmnstr-once` verarbeitet höchstens 10 Dienste je Absenden (`array_slice(…, 0, 10)`), jeder Schlüssel einzeln geprüft.
- Template-Cache: Die Freigabe ist sessionbezogen ([Bekannte Grenzen](#bekannte-grenzen)).

### Entfernte Dienste und fehlendes Dienst-Feld

#### Entfernte Dienste

Entfernt jemand einen Dienst oder deaktiviert eine Kategorie, auf die Inhalte (`cmnstr_service`) oder Tracking-Codes noch verweisen, bleibt nichts dauerhaft gesperrt: Statt des entfernten Dienstes verlangt das Modul die Zustimmung zur Kategorie bzw. zu allen Diensten, nicht weniger. Ein leerer Wert (`''`, `[]`) und standardmäßig auch ein ausdrückliches `null` bedeuten keine Einschränkung ([Fehlendes Dienst-Feld](#fehlendes-dienst-feld)). Der Ersatz erfolgt schrittweise (`resolveServiceKey()`), auch für jeden Eintrag einer Mehrfachauswahl:

1. Dienst wird angeboten: unverändert, z. B. `statistics-matomo`.
2. Dienst fehlt, Kategorie wird angeboten: die Kategorie, z. B. `statistics` (Zustimmung zu allen ihren Diensten).
3. Kategorie deaktiviert oder unbekannt: `all` (Zustimmung zu allen angebotenen Diensten).

Die Inhaltsmaske bietet „Zulassen“ für die Ersatz-Kategorie an, bei Stufe 3 entfällt der Button (dort genügt „Alle akzeptieren“).

Beim Speichern (`cmnstr-grant`) ersetzt das Modul nichts, Masken senden bereits die ersetzten Schlüssel. Ein Schlüssel, der sich erst jetzt anders auflöst (z. B. aus einer zwischengespeicherten Seite), wird ignoriert statt ausgeweitet: Eine Zustimmung gilt nur für das Angezeigte. Bleibt kein gültiger Eintrag, ändert sich der Consent nicht und die Seite zeigt die aktuelle Maske. Bei der ersten Entscheidung (noch kein Cookie) gilt alles außer den freigegebenen Einträgen als abgelehnt, der Hinweis schließt sich wie nach „Auswahl speichern“.

Die Modul-Konfiguration warnt mit Links zu den betroffenen Seiten und nennt die Ersatz-Zustimmung, im Seiteneditor weist das Feld selbst darauf hin.

#### Fehlendes Dienst-Feld

Liefert ein Template `null` (z. B. `$page->cmnstr_service` ohne existierendes oder zugeordnetes Feld) und geht das ausdrücklich an `isUnlocked()` oder `maskContent()`, entscheidet **Fehlendes Dienst-Feld** (Tab **Cookie-Banner** → **Anzeige** → **Erweitert**):

| Option                                                | Verhalten                                                                                                                                       |
| ----------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| Inhalt anzeigen (Standard)                            | Keine Einschränkung, der Inhalt bleibt unmaskiert (fail-open).                                                                                  |
| Inhalt maskieren, bis allen Diensten zugestimmt wurde | Wie bei einem entfernten Dienst ersetzt durch `all`, der Inhalt bleibt bis zur Zustimmung zu allen angebotenen Diensten maskiert (fail-closed). |

Unbeschränkt bleiben in beiden Modi `''`, `[]` und ein weggelassenes Argument (`maskContent($html)`), Tracking-Tags sind nicht betroffen. Bei `null` warnt das Modul je Seite und Aufrufstelle einmal pro Request im Log `cookiemonster` (Seiten-ID, Datei:Zeile).

### Eingabefeld für Dienste und Cookies

Das Eingabefeld im Admin färbt die Spalten beim Tippen ein (Dienst-ID, Cookie-Name, Titel oder Anbieter, `*`-Muster, Dauer oder Hinweis, Trennzeichen), Autokorrektur und Rechtschreibprüfung sind aus. Ein `contenteditable`-Element (`role="textbox"`) ersetzt die Textarea, die weiterhin gespeichert wird, und übernimmt Aussehen und Fokus vom Admin-Theme. Ohne [CSS Custom Highlight API](https://developer.mozilla.org/docs/Web/API/CSS_Custom_Highlight_API) oder JavaScript bleibt die normale Textarea.

### Consent-Cookie

Der Server speichert die Entscheidung im Cookie `cmnstr` (HttpOnly, SameSite=Lax, 180 Tage) und leitet zurück. Es enthält jeden angebotenen Dienst einzeln, Schema-Version, Consent-ID und Konfigurations-Hash:

```json
{
	"essential": true,
	"statistics": { "default": true, "ga": false },
	"_version": "4.6.0",
	"_id": "…32 Hex-Zeichen…",
	"_t": 1790000000,
	"_r": 1790000000,
	"_gpc": true,
	"_c": "…12 Hex-Zeichen…"
}
```

| Schlüssel  | Bedeutung                                                                                                                                                                                                                                                                                                                                                                                    |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `_version` | Schema-Version. Sie entspricht der Modulversion als Text (`4.6.0`) und wird mit `version_compare()` verglichen, jeder Teil darf beliebig groß sein. Frühere Builds speicherten die Version ohne Punkte als Ganzzahl; sie wird wie bei ProcessWire gelesen (`Modules::formatVersion()`): erste Ziffer Major, zweite Minor, der Rest Patch (`460` bedeutet `4.6.0`, `1234` bedeutet `1.2.34`). |
| `_id`      | Consent-ID. Bei „Kein Logging“ fehlt sie.                                                                                                                                                                                                                                                                                                                                                    |
| `_t`       | Zeitpunkt der Entscheidung.                                                                                                                                                                                                                                                                                                                                                                  |
| `_r`       | Zeitpunkt der letzten Erneuerung des Ablaufs.                                                                                                                                                                                                                                                                                                                                                |
| `_gpc`     | Das Signal war bei der Entscheidung aktiv.                                                                                                                                                                                                                                                                                                                                                   |
| `_c`       | Konfigurations-Hash bei der Entscheidung ([Verlauf der Konfiguration](#verlauf-der-konfiguration)), nur Beleg: Ein abweichender oder fehlender Hash macht das Cookie nicht ungültig. Bei „Kein Logging“ fehlt `_c` wie `_id`.                                                                                                                                                                |

- Laufzeit: 180 Tage ab der Entscheidung (`_t`), serverseitig geprüft. Ein gültiges Cookie setzt das Modul höchstens einmal täglich mit unverändertem Ablaufdatum neu (`_r`), damit Browser mit gekürzter Laufzeit (Safari ITP) die volle Restlaufzeit erhalten.
- Versionierung: Cookies mit anderer Version oder ohne `_t` (so 4.5) gelten als nicht vorhanden ([Upgrade auf 4.6.0](#upgrade-auf-460)), die nächste Entscheidung überschreibt sie.
- Subdomains: siehe [Consent über Subdomains teilen](#consent-über-subdomains-teilen).
- Caching: Die Ausgabe hängt von Consent-Cookie und Signalen ab, die dafür gesendeten Header stehen unter [Security-Header](#security-header).

### Consent-Schnittstelle

Die Banner- und Masken-Formulare senden per `POST` an `/_cmnstr/consent` (`CookieMonster::CONSENT_PATH`, der Unterstrich verhindert Überschneidungen mit Seitennamen), auch ohne JavaScript.

| Feld                          | Bedeutung                                                                                                                                                                                                                                          |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `cmnstr-all`                  | `1` = alle akzeptieren, `0` = alle ablehnen                                                                                                                                                                                                        |
| `cmnstr-grant`                | Kategorien bzw. Dienste zusätzlich erlauben, mehrere durch Leerzeichen getrennt, `all` = alle. Nicht mehr angebotene Einträge werden ignoriert, siehe [Entfernte Dienste und fehlendes Dienst-Feld](#entfernte-dienste-und-fehlendes-dienst-feld). |
| `cmnstr-{kategorie}-{dienst}` | Auswahl im Dialog                                                                                                                                                                                                                                  |
| `cmnstr-once`                 | Nur Masken, siehe [Einmal laden](#einmal-laden)                                                                                                                                                                                                    |
| `cmnstr-redirect`             | Adresse für die Weiterleitung                                                                                                                                                                                                                      |
| `cmnstr-lang`                 | Sprach-ID für die Statusmeldungen und Texte                                                                                                                                                                                                        |

Der Endpunkt `/_cmnstr/report` (`CookieMonster::REPORT_PATH`) nimmt CSP-Berichte entgegen, siehe [Security-Header](#security-header).

### CSP-Rezept mit Nonce

Das Modul ersetzt im Seiten-Output keine Nonce, damit eingeschleuster Text keine gültige erhält. Seine eigenen Scripts tragen sie automatisch, alles andere braucht `$cmnstr->getCspNonce()` (Wirkung: [getCspNonce](#getcspnonce)).

Fall 1: Inline-Script im Template.

```php
<script nonce="<?= $cmnstr->getCspNonce() ?>">
	window.dataLayer = window.dataLayer || [];
</script>
```

Fall 2: Externes Script oder `<style>`-Element. Die Nonce gehört auch an Scripts mit `src` und Inline-`<style>`-Elemente, Style-Attribute sind ohne Nonce erlaubt.

```php
<script nonce="<?= $cmnstr->getCspNonce() ?>" src="https://tracker.example.com/tag.js" async></script>

<style nonce="<?= $cmnstr->getCspNonce() ?>">
	.example { color: inherit; }
</style>
```

Fall 3: HTML aus Pflegefeldern oder `maskContent()`. Das Modul ergänzt dort keine Nonce, Redakteure kennen sie nicht. Zwei Wege:

- Das Script im Template zusammensetzen und die Nonce eintragen (Beispiel unter [Inhalte maskieren](#inhalte-maskieren)).
- Das fremde Script über eine eigene `script-src-elem`-Direktive erlauben, siehe [Fremdes Script ohne Nonce erlauben](#fremdes-script-ohne-nonce-erlauben). Die `https:`-Quellen im Standard helfen Scripts nicht, weil `'strict-dynamic'` Host-Angaben für Scripts ausschaltet.

### Security-Header

Alle Einstellungen stehen im Tab **Sicherheits-Header**. Das Modul sendet die Header bei jeder von ihm gerenderten Frontend-Seite, nicht im Admin, bei AJAX-Anfragen (`X-Requested-With`), an den Endpunkten `/_cmnstr/consent` und `/_cmnstr/report` (eigene Header inkl. `nosniff` und `no-store`) und an Dateien, die der Webserver direkt ausliefert (Bilder, CSS, JS, Uploads; dafür die Webserver-Konfiguration nutzen, z. B. die vorbereitete `nosniff`-Zeile in der `.htaccess`). Die Werte orientieren sich am [OWASP Secure Headers Project](https://owasp.org/www-project-secure-headers/) und am [OWASP HTTP Headers Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/HTTP_Headers_Cheat_Sheet.html).

| Header                                      | Einstellung                                                                                             | Standard                                                            | Hinweis                                                                                                                                                                                                                                                                      |
| ------------------------------------------- | ------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Strict-Transport-Security`                 | **HSTS-Header senden**, **Gültigkeitsdauer (max-age)**, **Subdomains (includeSubDomains)**, **Preload** | Aus. `max-age` 12 Monate, Subdomains und Preload aus                | Nur unter HTTPS. `max-age` wählbar: 0, 1 bis 6 Monate, 12 Monate, 2 Jahre (OWASP empfiehlt 2 Jahre). `preload` nur bei `max-age` ab 1 Jahr und aktivem `includeSubDomains`. `max-age=0` nimmt HSTS in den Browsern zurück, die Checkbox abzuschalten beendet nur das Senden. |
| `frame-ancestors`                           | **Einbetten erlauben**, **Erlaubte Websites**                                                           | Nur auf dieser Website                                              | [Einbetten in Frames](#einbetten-in-frames)                                                                                                                                                                                                                                  |
| `Content-Security-Policy`                   | **CSP-Header senden**, **Richtlinie**, **Report-Only-Modus**                                            | Aus. Richtlinie `CookieMonster::DEFAULT_CSP_POLICY`, Report-Only an | [Content Security Policy](#content-security-policy)                                                                                                                                                                                                                          |
| `report-uri`, `report-to`                   | **Meldeadresse für Verstöße**                                                                           | Leer: Endpunkt des Moduls                                           | [Meldungen](#meldungen)                                                                                                                                                                                                                                                      |
| `Permissions-Policy`                        | **Permissions-Policy-Header senden**, **Richtlinie**                                                    | Aus. Richtlinie `geolocation=(), camera=(), microphone=(), usb=()`  | Laut [MDN-Kompatibilitätsdaten](https://developer.mozilla.org/docs/Web/HTTP/Reference/Headers/Permissions-Policy) wertet nur Chromium den Header aus. `fullscreen=()` sperrt auch den Vollbildmodus eingebetteter Videos.                                                    |
| `X-Content-Type-Options`, `Referrer-Policy` | **X-Content-Type-Options**, **Referrer-Policy**                                                         | `nosniff` an, `strict-origin-when-cross-origin`                     |                                                                                                                                                                                                                                                                              |
| `Cross-Origin-Opener-Policy`                | **Cross-Origin-Opener-Policy (COOP)**                                                                   | `same-origin-allow-popups`                                          | Kompatibel mit Login- und Zahlungs-Pop-ups. OWASP empfiehlt `same-origin`.                                                                                                                                                                                                   |
| `Cross-Origin-Embedder-Policy`              | **Cross-Origin-Embedder-Policy (COEP)**                                                                 | Nicht gesetzt                                                       | `require-corp` blockiert Embeds wie YouTube.                                                                                                                                                                                                                                 |
| `Cross-Origin-Resource-Policy`              | **Cross-Origin-Resource-Policy (CORP)**                                                                 | `same-origin`                                                       |                                                                                                                                                                                                                                                                              |
| `X-Powered-By`                              | **X-Powered-By**                                                                                        | Aus (Header unberührt)                                              | Optional entfernt das Modul den Header (ProcessWire und PHP). Den `Server`-Header setzt der Webserver.                                                                                                                                                                       |
| `Vary`                                      | immer                                                                                                   | `Cookie`                                                            | Mit GPC zusätzlich `Sec-GPC`, mit DNT `DNT`.                                                                                                                                                                                                                                 |
| `Cache-Control`                             | immer                                                                                                   | `private, no-cache`                                                 | Ein vorhandenes `no-store` (z. B. von der ProcessWire-Session für eingeloggte Besucher) bleibt. Siehe [Caching](#caching).                                                                                                                                                   |

Auswahlwerte (Referrer-Policy, COOP, COEP, CORP, Einbetten) prüft das Modul gegen eine Liste gültiger Werte, sonst gilt der Standard. Zeilenumbrüche in Herkünften und Permissions-Policy werden beim Senden zu Leerzeichen.

#### Einbetten in Frames

Gilt immer, unabhängig von der CSP. Gesendet wird `frame-ancestors`, nicht `X-Frame-Options` (`ALLOW-FROM` ist obsolet).

| Situation                                           | Wo `frame-ancestors` steht                                                                                     |
| --------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| CSP durchgesetzt, Richtlinie ohne `frame-ancestors` | In der CSP                                                                                                     |
| CSP durchgesetzt, Richtlinie mit `frame-ancestors`  | Der Wert der Richtlinie gilt                                                                                   |
| CSP aus oder Report-Only                            | Eigener, durchgesetzter `Content-Security-Policy`-Header (Browser ignorieren `frame-ancestors` in Report-Only) |

- Eigene Herkünfte: Leerzeichen-getrennt, `'self'` oder `http://` bzw. `https://` mit Host. Vor dem Host darf `*.` stehen (alle Subdomains), danach `:port` oder `:*`, kein Pfad, z. B. `https://partner.de` oder `https://*.partner.de:8443`. Ein Host ohne Schema (`*.partner.de`) ist ungültig. Das Eingabefeld lässt jeden Wert mit `http(s)://` zu, der Server verwirft Einträge, die nicht zum Muster passen. Ohne gültigen Eintrag gilt „Nirgends“ (`frame-ancestors 'none'`).
- Ausnahme `form-builder`: Seiten mit diesem Template erhalten weder Frame-Schutz noch COOP oder CORP, damit Formulare auf anderen Websites einbettbar sind (maßgeblich ist die Hauptseite des Requests).
- `.htaccess`: Ein von der ProcessWire-`.htaccess` angehängtes `X-Frame-Options: SAMEORIGIN` (`Header always append X-Frame-Options SAMEORIGIN`) ist unschädlich, Browser mit `frame-ancestors`-Unterstützung ignorieren es, sobald die CSP `frame-ancestors` enthält. Bei `form-builder`-Seiten fehlt `frame-ancestors`, dort greift die Apache-Zeile und sperrt die Einbettung auf anderen Websites.

#### Content Security Policy

Der Header ist standardmäßig aus. Nach dem Einschalten sendet das Modul die Richtlinie (ein Text) standardmäßig als `Content-Security-Policy-Report-Only` (nur melden, nichts blockieren).

- Nonce: Der Platzhalter `'nonce-proxy'` in der Richtlinie ersetzt das Modul je Anfrage durch eine zufällige Nonce. In Templates: `$cmnstr->getCspNonce()` ([CSP-Rezept mit Nonce](#csp-rezept-mit-nonce)).
- Standard (`CookieMonster::DEFAULT_CSP_POLICY`): Er erlaubt externe Embeds und Tracker bewusst („besser als kaputt“): Scripts nur mit Nonce (`strict-dynamic`), Styles, Bilder, Schriften, Medien, Frames und Verbindungen auch von beliebigen `https:`-Quellen. Außerdem `object-src 'none'`, `base-uri 'self'`, `form-action 'self'` und `upgrade-insecure-requests`. Im Report-Only-Modus entfällt `upgrade-insecure-requests`, weil Browser es dort ignorieren.
- `'strict-dynamic'`: Der Standard hat keine eigene `script-src`, für Scripts gilt `default-src`. Mit `'strict-dynamic'` ignoriert der Browser Host-Angaben (`'self'`, Domains, `https:`) für Scripts: Erlaubt sind nur Scripts mit gültiger Nonce und von ihnen nachgeladene. Ein fremdes Script im HTML ohne Nonce blockiert er auch bei erlaubtem Host (Quelle: [MDN, `script-src`](https://developer.mozilla.org/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/script-src)).
- Wirkung: Der Standard schützt vor eingeschleustem Script, begrenzt aber nicht, wohin Daten über Bilder, Verbindungen oder Frames gesendet werden. Für strengere Vorgaben bei Bildern, Frames, Verbindungen, Schriften und Medien die `https:`-Quellen durch Hosts ersetzen (nicht bei Scripts, siehe oben).
- Styles: Inline-`<style>`-Elemente brauchen die Nonce, Style-Attribute sind erlaubt. Snippets aus Pflegefeldern erhalten sie nicht automatisch.
- Entfernt: Das veraltete `block-all-mixed-content` entfernt das Modul beim Senden, auch aus gespeicherten Richtlinien.

#### Fremdes Script ohne Nonce erlauben

Bevorzugt die Nonce eintragen ([CSP-Rezept mit Nonce](#csp-rezept-mit-nonce)). Geht das nicht (z. B. Script im Redaktionsfeld), eine eigene Direktive für Script-Elemente ergänzen. Sie ersetzt den Fallback auf `default-src` und enthält kein `'strict-dynamic'`:

```text
script-src-elem 'self' 'nonce-proxy' https://tracker.example.com
```

Die Zeile gehört in die Richtlinie im Tab **Sicherheits-Header**, getrennt durch `;` von den anderen Direktiven. Folgen (aus der Spezifikation abgeleitet, nicht in allen Browsern getestet):

- Scripts, die ein Script mit Nonce nachlädt (z. B. Tags im Tag Manager), erlaubt der Browser nicht mehr automatisch. Jeder Host braucht dann einen eigenen Eintrag, sonst blockiert er das nachgeladene Script.
- Ein Host-Eintrag erlaubt alle Scripts dieses Hosts, nicht nur das eine. Der Schutz vor eingeschleustem Script sinkt entsprechend.
- Inline-Event-Handler wie `onclick` bleiben blockiert, denn dafür gilt weiter `default-src`.
- Erst im Report-Only-Modus testen.

#### CSP-Fehler finden

Die Konsole der Entwicklerwerkzeuge nennt Verstöße samt Direktive. Im Report-Only-Modus beginnt die Meldung mit „[Report Only]“ und nichts wird blockiert. Der Wortlaut hängt von Browser und Version ab.

| Meldung (sinngemäß)                                                                                | Direktive                              | Maßnahme                                                                                                                                                              |
| -------------------------------------------------------------------------------------------------- | -------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| „Refused to load the script … directive: `default-src` … `script-src-elem` was not explicitly set“ | `default-src` als Fallback für Scripts | Nonce eintragen oder [Fremdes Script ohne Nonce erlauben](#fremdes-script-ohne-nonce-erlauben). Ein Host in der Richtlinie hilft hier nicht, wegen `'strict-dynamic'` |
| „Refused to execute inline script …“                                                               | `default-src` (Scripts)                | `nonce` am Script setzen                                                                                                                                              |
| „Refused to apply inline style …“ bei `<style>`-Element                                            | `style-src`                            | `nonce` am `<style>`-Element setzen. Style-Attribute sind erlaubt.                                                                                                    |
| „Refused to load the image/font/media …“                                                           | `img-src`, `font-src`, `media-src`     | Host in der jeweiligen Direktive ergänzen (der Standard erlaubt `https:`)                                                                                             |
| „Refused to frame …“ (Seite bindet Frame ein)                                                      | `frame-src`                            | Host ergänzen                                                                                                                                                         |
| „Refused to connect to …“                                                                          | `connect-src`                          | Host ergänzen                                                                                                                                                         |
| „Refused to frame … an ancestor violates …“ (Seite wird eingebettet)                               | `frame-ancestors`                      | **Einbetten erlauben** ändern, siehe [Einbetten in Frames](#einbetten-in-frames)                                                                                      |
| „Refused to send form data …“                                                                      | `form-action`                          | Formular an eine fremde Adresse nur mit Eintrag in der Richtlinie                                                                                                     |

#### Meldungen

`csp_report_to` (Feld **Meldeadresse für Verstöße**) ist standardmäßig leer. Dann gilt die absolute URL des Modul-Endpunkts auf dem aktuellen Host (als Platzhalter im Feld): `report-uri`, unter HTTPS zusätzlich `report-to csp-endpoint` mit `Reporting-Endpoints`.

- Eigene Adresse: `https://…` oder ein Pfad, ohne Leerraum und ohne `;` `,` `"`. Sie ersetzt den Endpunkt, ungültige Werte (auch `http://`) werden ignoriert. `report-uri` und `report-to` aus der gespeicherten Richtlinie ersetzt diese Angabe.
- Datenschutz: Berichte enthalten die URLs der Seiten und der blockierten Quellen. Eine fremde Adresse macht den Empfänger zum Empfänger dieser Daten.

Endpunkt `/_cmnstr/report` (`CookieMonster::REPORT_PATH`):

| Eigenschaft          | Verhalten                                                                                                                                                                                                                                     |
| -------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Anfrage              | Nur `POST` (sonst 405 mit `Allow`), `Content-Type` `application/csp-report` oder `application/reports+json` (sonst 415), höchstens 16 KB (413) und 10 Berichte je Anfrage. Antwort: 204.                                                      |
| Begrenzung           | Gleiche Berichte (Seite, Richtlinie, blockierte Quelle) loggt das Modul je Stunde einmal, höchstens 200 verschiedene je Stunde und Installation, nicht je Besucher (danach 429, Fensterstart mit dem ersten Bericht).                         |
| Zähler               | `site/assets/logs/cookiemonster-report-limit.json` (Sperre per `flock`, keine IP, nur Prüfsummen). Ist die Datei nicht schreibbar, antwortet der Endpunkt mit 503 und protokolliert nichts.                                                   |
| Cookies              | Kein eigenes Cookie, keine Same-Origin-Prüfung (Browser senden Berichte teils ohne `Sec-Fetch-Site`). Das Session-Cookie von ProcessWire entsteht trotzdem ([Sessions ohne Cookie für Erstbesucher](#sessions-ohne-cookie-für-erstbesucher)). |
| Log                  | Je Bericht eine Zeile in `cookiemonster-report`, nur mit `blocked`, `directive`, `document`, `disposition`, `source`, `line`, `status`. URLs ohne Query und Fragment, jedes Feld auf 200 Zeichen gekürzt, ohne IP, User-Agent und Referrer.   |
| Aufbewahrung (DSGVO) | Auch gekürzte Berichte enthalten Seiten- und Quell-URLs. Das Modul löscht Einträge nach 30 Tagen, täglich per LazyCron. Das Einwilligungsprotokoll hat eine eigene Aufbewahrung.                                                              |

#### Caching

Da die Ausgabe von Consent-Cookie und Signalen abhängt, sendet das Modul `Vary: Cookie` und, solange die Signale gelten, `Sec-GPC` bzw. `DNT`. Dazu kommt `Cache-Control: private, no-cache`. Eine strengere Regel wie `no-store` bleibt unverändert.

- Entfernt: `Last-Modified` und `ETag` immer, `Expires` solange kein `no-store` gilt. So verwenden Browser nie eine per 304 bestätigte Kopie mit veralteter Nonce oder Maske.
- Session-Cache-Limiter: Das Modul erreicht ihn nicht rechtzeitig (die Session startet vor `init()`). PHP-seitige Header wie `Pragma` oder ein abweichendes `Expires` vermeidet `$config->sessionCacheLimiter` in `site/config.php`, z. B. `['guest' => 'nocache', 'loggedin' => 'nocache', 'admin' => 'nocache']`, allerdings ohne gesicherten Browser-Cache für Zurück/Vor (bfcache).
- Full-Page-Caches wie ProCache berücksichtigen die Signale erst bei dynamisch erzeugten Seiten, siehe [Bekannte Grenzen](#bekannte-grenzen).

### Sessions ohne Cookie für Erstbesucher

ProcessWire startet die Session im Konstruktor von `Session`, also vor jedem Modul, und das Modul kann das nicht verhindern. Ein Besucher ohne Session-Cookie erhält deshalb bei jeder Anfrage, auch an `/_cmnstr/report`, eines. Einschränken lässt es sich in `site/config.php` über `$config->sessionAllow` ein (siehe `wire/config.php`):

```php
<?php

namespace ProcessWire;

// site/config.php
$config->sessionAllow = function (Session $session) use ($config): bool {
    // Running sessions and logged-in users
    if ($session->hasCookie()) {
        return true;
    }

    $path = $config->requestPath();

    // Adjust the admin URL; the consent endpoint needs the session
    return str_starts_with($path, '/processwire/') || $path === '/_cmnstr/consent';
};
```

- Der Callback läuft, bevor `$page`, `$user` und `$input` bereitstehen, und muss `bool` liefern (sonst `WireException`). Bei `false` startet keine Session: `$session->set()` speichert nur für die laufende Anfrage, `$session->CSRF()` startet keine.
- `/_cmnstr/consent` muss zugelassen sein: Bestätigung nach dem Speichern, Fokus-Anker an der Maske und [Einmal laden](#einmal-laden) liegen in der Session (danach greift `hasCookie()`). Pfade ggf. anpassen (Admin-URL, Unterverzeichnis).
- Auch Frontend-Logins, Formulare mit CSRF-Token (z. B. FormBuilder) und Warenkörbe brauchen ihre Pfade in der Freigabe, sonst funktionieren sie für Erstbesucher nicht.
- Das Cookie `cmnstr` hängt nicht an der Session.

### Gestaltung und JavaScript

Alle Regeln des Standard-Stylesheets liegen in `@layer cmnstr`. Styles der Website ohne Ebene haben immer Vorrang, Anpassungen brauchen weder `!important` noch längere Selektoren. Das gilt auch für globale Element-Styles der Website (z. B. `a`, `h2`). Wo diese im Banner nicht greifen sollen, setzt `revert-layer` auf die Werte des Moduls zurück:

```css
.cmnstr .cmnstr-title {
	margin: revert-layer;
}
```

Das CSS berücksichtigt `prefers-reduced-motion`, `prefers-contrast` und `forced-colors` und nutzt Container Queries (Container `cmnstr`, `cmnstrmask`, `cmnstrcookies`). Alternativ lässt sich das Standard-Stylesheet abschalten.

Anpassen über Custom Properties unter `:root` in `assets/CookieMonster.css` (Ausnahme: `--cmnstr-pad-block` und `--cmnstr-pad-inline` setzt `.cmnstr-categories`, nur dort überschreibbar):

```css
:root {
	--cmnstr-primary-color: oklch(45% 0.15 150);
	--cmnstr-radius-base: 0.25rem;
}
```

| Custom Property                                                  | Standard                                                                        | Wirkung                                       |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------------- | --------------------------------------------- |
| `--cmnstr-font-base`                                             | `1rem`                                                                          | Basis Schrift                                 |
| `--cmnstr-font-family`                                           | `system-ui, sans-serif`                                                         | Schrift                                       |
| `--cmnstr-font-size-xs`, `-sm`, `-base`, `-md`, `-lg`, `-xl`     | Basis × 0,825 / 1 / 1 / 1,125 / 1,2 / 1,5                                       | Schriftgrößen xs–xl                           |
| `--cmnstr-space-base`                                            | `0.875rem`                                                                      | Basis Abstände                                |
| `--cmnstr-space-xs`, `-sm`, `-md`, `-lg`, `-xl`                  | Basis × 0,75 / 1 / 1,25 / 1,5 / 2                                               | Abstände xs–xl                                |
| `--cmnstr-radius-base`                                           | `0.425rem`                                                                      | Basis Rundung                                 |
| `--cmnstr-border-radius`                                         | `var(--cmnstr-radius-base)`                                                     | Rundung Flächen, Buttons                      |
| `--cmnstr-border-radius-sm`                                      | Basis × 0,75                                                                    | kleine Rundung                                |
| `--cmnstr-bg`                                                    | `oklch(100% 0 0)`                                                               | Hintergrund                                   |
| `--cmnstr-text`                                                  | `oklch(0% 0 0)`                                                                 | Textfarbe                                     |
| `--cmnstr-primary-color`                                         | `oklch(50% 0.1 250)`                                                            | Akzentfarbe                                   |
| `--cmnstr-muted`                                                 | Mischung aus Text und Hintergrund (25 %)                                        | gedämpfter Text                               |
| `--cmnstr-faded`                                                 | Mischung aus Hintergrund und Text (10 %)                                        | abgesetzte Flächen                            |
| `--cmnstr-border-strong`                                         | Mischung aus Text (36 %) und Hintergrund                                        | Rahmen bei `prefers-contrast: more` (3:1)     |
| `--cmnstr-checkbox-background`, `--cmnstr-checkbox-border`       | `var(--cmnstr-bg)`; Mischung aus Text (36 %) und Hintergrund                    | Checkbox: Hintergrund, Rahmen (3:1)           |
| `--cmnstr-checkbox-checked-bg`, `--cmnstr-checkbox-checked-fg`   | `var(--cmnstr-primary-color)`; `var(--cmnstr-bg)`                               | angehakt: Hintergrund, Haken                  |
| `--cmnstr-checkbox-disabled-bg`, `--cmnstr-checkbox-disabled-fg` | `var(--cmnstr-faded)`; `var(--cmnstr-muted)`                                    | deaktiviert: Hintergrund, Haken               |
| `--cmnstr-focus-color`                                           | `var(--cmnstr-primary-color)`                                                   | Farbe des Fokusrings                          |
| `--cmnstr-focus-halo`                                            | `var(--cmnstr-bg)`                                                              | Saum um den Fokusring                         |
| `--cmnstr-focus-ring`                                            | `0 0 0 6px var(--cmnstr-focus-halo)`                                            | Schatten des Fokusrings                       |
| `--cmnstr-hover-bg`                                              | Mischung aus Hintergrund und Akzent (8 %)                                       | Hintergrund bei Hover                         |
| `--cmnstr-hover-border`                                          | Mischung aus Rahmen und Akzent (30 %)                                           | Rahmen bei Hover                              |
| `--cmnstr-transition`                                            | `250ms cubic-bezier(0.4, 0.2, 0.2, 1)`                                          | Übergänge                                     |
| `--cmnstr-pad-block`                                             | `var(--cmnstr-space-md)`                                                        | Innenabstand oben/unten im Kategorien-Bereich |
| `--cmnstr-pad-inline`                                            | `var(--cmnstr-space-lg)` (unter 32 em Containerbreite `var(--cmnstr-space-sm)`) | Innenabstand links/rechts dort                |

Die Ableitungen („Mischung“) nutzen `color-mix()` in `oklch` bzw. `oklab` (Werte am Anfang der Ebene in `assets/CookieMonster.css`).

Klassen und IDs tragen das Präfix `cmnstr`. Nur Anker ohne CSS-Regel im Standard-Stylesheet (für Skripte und `aria-*`): `#cmnstr`, `#cmnstr-status`, `#cmnstr-alert-content`, `#cmnstr-form` und `#cmnstr-title` (die Überschrift gestaltet `.cmnstr-title`).

| Selektor                                              | Element                                                                                            |
| ----------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| `.cmnstr`                                             | Klasse von Hinweis und Einstellungsdialog                                                          |
| `#cmnstr-alert`                                       | Hinweis                                                                                            |
| `#cmnstr-dialog`                                      | Einstellungsdialog (`<dialog>`)                                                                    |
| `#cmnstr-status`                                      | unsichtbarer Statusbereich für Screenreader                                                        |
| `.cmnstr-button`                                      | Buttons, je Aktion mit `-accept`, `-decline`, `-edit`, `-save`                                     |
| `.cmnstr-button-edit`                                 | öffnet den Einstellungsdialog (auch an eigenen Elementen)                                          |
| `.cmnstr-category`, `.cmnstr-group`, `.cmnstr-cookie` | Kategorie, Dienst und Cookie im Dialog (mit `-title`, `-description`, `-details`, `-row`, `-cell`) |
| `.cmnstr-content-mask`                                | Inhaltsmaske (mit `.cmnstr-mask-body`, `-text`, `-actions`, `-footer`, `-settings`)                |
| `.cmnstr-table`, `.cmnstr-list`                       | Cookie-Übersichten aus `[[cookie-table]]` und `[[cookie-list]]`                                    |
| `.cmnstr-notice`, `.cmnstr-signal`                    | Hinweise im Dialog, z. B. zu GPC                                                                   |
| `.cmnstr-sr-only`                                     | nur für Screenreader sichtbar                                                                      |

Markup in `templates/`: `banner.php` (Hinweis und Dialog), `mask.php` (Inhaltsmaske), `table.php` und `list.php` (Cookie-Übersichten), `tags/*.php` (Tracking-Codes, Consent-Mode-Default), `head/gsv.php` (Search-Console-Meta-Tag).

## Fehlersuche

### Tracking-Code lädt nicht

Der Code erscheint nur in vollständig gerenderten Seiten (`<head>`, `</body>`) und mit passender Zustimmung. Prüfen:

1. Hat die Kategorie (z. B. **Statistik-Cookies**) mindestens einen Dienst mit Zeile `---ID|…` und ist **Kategorie im Cookie-Banner anbieten** an? Ohne Dienst lädt der Code nie.
2. Ist die ID gültig? Die Mess-ID von Google Analytics muss zum Muster `G-…`, `AW-…`, `DC-…` oder `UA-…` passen.
3. Passt **Erforderliche Zustimmung**? Leer gilt Statistik (Google Analytics, Google Tag Manager, Matomo) bzw. Marketing (Meta Pixel, LinkedIn Insight Tag). Bei mehreren Diensten entscheidet [UND oder ODER](#mehrere-dienste-und-und-oder).
4. Hat der Besucher allen Diensten der Kategorie zugestimmt? Das Cookie `cmnstr` zeigt es (DevTools → Anwendung → Cookies). Bei aktivem GPC sind die betroffenen Kategorien abgelehnt.
5. Läuft die Seite durch PHP? Seiten aus einem Full-Page-Cache und Teil-Renderings erhalten keine Codes, siehe [Bekannte Grenzen](#bekannte-grenzen).
6. Blockiert die CSP das Script? In der Browser-Konsole nach Verstößen suchen, im Report-Only-Modus auch im Log `cookiemonster-report`.

### Banner erscheint nicht

1. Ist **Cookie-Banner** im Tab **Cookie-Banner** → **Anzeige** eingeschaltet?
2. Liegt schon eine gültige Entscheidung vor (Laufzeit: [Consent-Cookie](#consent-cookie))? Zum Testen das Cookie löschen oder den Dialog über `?cmnstr-settings` öffnen.
3. Ist die Seite eine vollständige HTML-Seite mit `<head>` oder `<body>` und läuft sie durch PHP? Auf den Templates `admin` und `form-builder`, in Teil-Renderings und in Full-Page-Caches fehlt der Banner.
4. Blockiert die CSP Script oder Stylesheet? Die Konsole nennt Verstöße (Assets tragen die Nonce automatisch).
5. Steht im Log `cookiemonster` die Zeile „Security headers not sent, output has already started“? Dann begann die Ausgabe vor dem Modul, die Header fehlen.

### Maske bleibt trotz Zustimmung

1. Passt der Schlüssel? Eine Zustimmung zu `external-youtube` öffnet keine Maske für `external-vimeo`. Die Maske nennt, was fehlt.
2. Gilt UND? Bei einer Kategorie oder mehreren Schlüsseln muss allen zugestimmt sein, siehe [UND und ODER](#mehrere-dienste-und-und-oder).
3. Existiert der Dienst noch? Fehlt er, fällt die Maske auf die Kategorie bzw. `all` zurück, siehe [Entfernte Dienste und fehlendes Dienst-Feld](#entfernte-dienste-und-fehlendes-dienst-feld).
4. Liefert `$page->cmnstr_service` den Wert `null`? Dann steuert **Fehlendes Dienst-Feld** das Verhalten, und das Log `cookiemonster` enthält eine Warnung.
5. Liegt die Seite im Template-Cache? Dort bleibt eine alte Maske stehen. Die Option **Template-Cache beibehalten** ausschalten.
6. Unterdrückt GPC die Zustimmung? Bei aktivem Signal gelten betroffene Kategorien nach pauschaler Zustimmung als abgelehnt.
7. Stammt das Cookie aus einer älteren Version? Cookies mit anderer Schema-Version gelten als nicht vorhanden, der Banner fragt neu.

## Upgrade auf 4.6.0

Rolle: Betrieb und Entwicklung.

Kurzfassung: Backup anlegen, Modulordner ersetzen, **Modules** → **Refresh**, Log `cookiemonster-upgrade` und Admin-Meldung prüfen. Danach fragt der Banner alle Besucher neu (jede Versionsänderung setzt die Consent-Epoche, [Was automatisch passiert](#was-automatisch-passiert)). Dann die Pflichtschritte unten abarbeiten.

### Was zu tun ist

1. Backup (Pflicht): Datenbank, `site/modules/CookieMonster/` und `site/assets/logs/` sichern.
2. Modulverzeichnis ersetzen (Pflicht): Altes Verzeichnis löschen und das neue einspielen, nicht überkopieren (bei 1.x bis 3.x zwingend).
3. Refresh (Pflicht): **Modules** → **Refresh**. Das Upgrade läuft beim nächsten Seitenaufruf, danach erscheint der Banner allen Besuchern erneut (neue Consent-Epoche).
4. Admin-Meldung lesen (Pflicht): Wurden Einstellungen übertragen, zeigt der Admin dem Superuser einmalig eine Warnung (auch im Log `cookiemonster-upgrade` als `Upgrade notices`).
5. Dienste anlegen (Pflicht, wenn die Warnung es nennt): Übernommene Cookie-Listen haben noch keine Dienste (`---…`), Masken und Tracking-Codes brauchen sie. Tracking-Codes (Google Analytics, Google Tag Manager, Meta Pixel, LinkedIn Insight Tag) mit ID, deren Kategorie keine Dienste anbietet, laden nie ([Dienste und Cookies eintragen](#dienste-und-cookies-eintragen)).
6. Zuordnung der Tracking-Codes prüfen (Pflicht): Tab **Tracking**, **Erforderliche Zustimmung**. 4.6 ist an zwei Stellen strenger als 4.5 ([Automatische Umstellungen](#automatische-umstellungen)).
7. Eigene Templates umstellen (Pflicht für Entwickler): `$page->cookiemonster_category` durch `$page->cmnstr_service` ersetzen. Das entfernte Feld liefert `null`, ein ausdrücklich übergebenes `null` lässt den Inhalt standardmäßig unmaskiert. Fundstellen: `rg cookiemonster_category site/`, danach im Log `cookiemonster` auf `null`-Warnungen prüfen.
8. Eigene `<script>`- und `<style>`-Tags umstellen (Pflicht für Entwickler): `nonce="proxy"` im Seiten-Output ersetzt das Modul nicht mehr (eingeschleuster Text erhielte sonst eine gültige Nonce). Stattdessen `<?= $cmnstr->getCspNonce() ?>`. Der Platzhalter `'nonce-proxy'` in der Richtlinie bleibt.
9. Datenschutzerklärung ergänzen (Pflicht, wenn Masken Einzeldienste anbieten): „Einmal laden“ ist zurück und nutzt ein Sitzungs-Cookie. Textbaustein und Änderungen am Einwilligungsprotokoll: [Datenschutzerklärung ergänzen](#datenschutzerklärung-ergänzen).
10. Aufbewahrung festlegen (optional): Tab **Statistik** → **Aufbewahrung des Einwilligungsprotokolls** (Standard 3 Jahre nach Ablauf der Einwilligung).
11. Texte und Übersetzungen prüfen (optional): Texte der Vorversion wurden unverändert übernommen.
12. Aufräumen (optional): `cookiemonster-upgrade.txt` und `cookiemonster-upgrade.lock` in `site/assets/logs` bleiben liegen.

Rollback: Modulverzeichnis der alten Version zurückspielen und Datenbank aus dem Backup wiederherstellen (das Upgrade löscht das alte Feld `cookiemonster_category` nach erfolgreicher Übertragung und setzt die Consent-Epoche).

### Was automatisch passiert

Alle Schritte sind idempotent, das Upgrade wirft nie eine Ausnahme.

- Neue Module: `FieldtypeCookieService`, `InputfieldCookieService`, `TextformatterCookieTable` und das Feld `cmnstr_service`.
- Feldübertragung: Das Options-Feld `cookiemonster_category` geht nach `cmnstr_service` über (gleiche Fieldgroups, auch versteckte, unveröffentlichte und gelöschte Seiten) und wird danach gelöscht.
    - Mehrere gewählte Werte gelten als ODER (`any|statistics|marketing`).
    - Schlüssel mit Leerzeichen oder Punkten werden wie Dienst-IDs normalisiert (`foo bar.1` wird `foo_bar_1`).
    - Ein bereits befülltes `cmnstr_service` bleibt unverändert.
    - In Repeater-Matrix-Feldern trägt das Upgrade `cmnstr_service` in alle Typen ein, die das alte Feld hatten, sonst liefern die Items das Feld nicht.
    - Jeder Wert wird nach dem Speichern aus der Datenbank geprüft. Das alte Feld wird nur gelöscht, wenn keine Seite ohne übertragenen Wert bleibt.
    - Feld-Kontext: Je Template gehen Label, Beschreibung, Hinweis, Einklappen, Spaltenbreite und Sichtbarkeitsbedingung über (falls für `cmnstr_service` noch kein Kontext besteht). Weitere Kontexteinstellungen gelten nicht.
- Berechtigung: Die nie geprüfte Berechtigung `manage-cookie-monster` wird entfernt.
- Neue Abfrage: Jede Versionsänderung setzt die Consent-Epoche (`consent_epoch`). Frühere Entscheidungen und 4.5-Cookies (ohne `_t`, andere Schema-Version) gelten als nicht vorhanden, die nächste Entscheidung überschreibt das Cookie (neue Consent-ID). Eine Wiederholung bei `migration_pending` lässt die Epoche unberührt. Änderungen an der Dienste-Liste machen keine Entscheidung ungültig: Ein neuer Dienst bleibt bis zur Entscheidung gesperrt (Banner öffnet erneut), entfernte Dienste werden ignoriert.
- CSP: Eine gespeicherte Richtlinie bleibt unverändert, außer sie entspricht (bis auf Leerraum) dem 4.5-Standard (identisch mit 4.2.3) oder dem alten 4.6.0-Dev-Standard: Dann ersetzt das Upgrade sie durch den neuen Standard. Ohne eigene Meldeadresse meldet die CSP jetzt an `/_cmnstr/report` (Log `cookiemonster-report`).
- Mehrsprachigkeit: Dienst-IDs und Cookie-Namen stammen aus dem Standardsprachen-Text ([Dienste und Cookies eintragen](#dienste-und-cookies-eintragen)).
- Fehler: Schlägt ein Schritt fehl (z. B. das Speichern einer Seite), steht er im Log `cookiemonster-upgrade`, die Konfiguration erhält den Marker `migration_pending` und `cookiemonster_category` bleibt erhalten. Als Superuser wiederholt das Modul die Migration höchstens einmal pro Minute und warnt im Admin, bis sie gelingt. Der Marker wird vor den Schritten gesetzt: Bricht der Lauf hart ab (Zeitlimit, Speicher), bleibt er stehen und die Migration wird wiederholt. Besucher prüfen nur den Marker. Parallele Aufrufe sperren sich über `site/assets/logs/cookiemonster-upgrade.lock`.

### Automatische Umstellungen

Tracking-Codes:

- Die Kombination aus Kategorien und Cookie-ID wird zu Dienst-Schlüsseln (`statistics` + `gtm` wird zu `statistics-gtm`). Waren mehrere Kategorien gewählt, setzt das Upgrade ODER. Das entspricht dem bisherigen Verhalten.
- Strenger als 4.5: Der Google Tag Manager mit Kategorie `marketing` und ohne Cookie-ID wartete in 4.5 auf die Statistik-Zustimmung. Jetzt gilt der Schlüssel `marketing`. Prüfen, ob die Zuordnung passt.
- Eine in 4.5 gewählte Kategorie bleibt auch ohne Cookie-ID erhalten und wird als Schlüssel übernommen (z. B. `marketing`). Ohne gewählte Kategorie gilt bei Meta Pixel und LinkedIn Insight Tag jetzt die Marketing-Zustimmung (4.5: Statistik).
- Die Einstellung **Fehlendes Dienst-Feld** gilt nicht für Tracking-Codes.

### Upgrade von 1.x bis 3.x

Zuerst das alte Modulverzeichnis ersetzen (nicht überkopieren), dann **Modules** → **Refresh**.

| Alte Einstellung                                               | Neue Einstellung                                                                       |
| -------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| `bodytext`                                                     | `infotext`                                                                             |
| `buttontext` (1.x)                                             | `buttontext_accept`                                                                    |
| `introtext_necessary`, `introtext_statistics`, `introtext_ads` | `essential_description`, `statistics_description`, `marketing_description`             |
| `cookies_necessary`, `cookies_statistics`, `cookies_ads`       | `essential_cookies`, `statistics_cookies`, `marketing_cookies` (gleiches Zeilenformat) |
| `target_page`                                                  | `privacy_page`                                                                         |
| `referrer_policy_header`                                       | `referrer_policy`                                                                      |
| `strict_transport_security_header`                             | `hsts_enabled` und `hsts_max_age` (auf die nächstkleinere Stufe abgerundet)            |
| `x_content_type_options_header`                                | `x_content_type_options_enabled`                                                       |

- Sprachspezifische Werte (`…__<ID>`) gehen mit über.
- `statistics_enabled` bzw. `marketing_enabled` werden gesetzt, wenn Cookies oder eine GA-Mess-ID vorhanden sind.
- Bereits gesetzte 4.6-Werte bleiben unberührt.
- Ohne Entsprechung (Log `cookiemonster-upgrade`): `custom_css`, `google_ads_conversions`, `conversion_count`. Ebenfalls entfallen: `autolink`, `target_string`, `table_placeholder`, `multilanguage`.
- Die Cookie-Listen haben noch keine Dienste (`---…`). Masken und Tracking-Codes benötigen sie.

### Änderungen gegenüber 4.5

Übersetzungen: Die festen Frontend-Texte stehen jetzt gesammelt in der Methode `texts()` der `CookieMonster.module.php` statt verteilt auf Modul und Templates. Die Sprachdateien `languages/*.csv` entfallen. Übersetzungen aus 4.5 gelten weiter, soweit der Text unverändert ist; Texte aus den Templates sind neu einzutragen ([Übersetzungen](#übersetzungen)).

Für Code, der die Modul-API nutzt:

- `isUnlocked()` liefert immer `bool` (früher `null`, `true` oder `false`). Ein leerer Schlüssel (`''`, `[]`) ist `true`, ein ausdrückliches `null` gilt je nach **Fehlendes Dienst-Feld**.
- Entfallen sind die Methoden `addCookieBanner`, `addHeadCodes`, `addTrackingCodes`, `sendSecurityHeaders` und `onConfigSave`. Banner, Head- und Tracking-Codes und Header kommen aus einem `Page::render`-Hook des Moduls.
- Entfallen ist `checkForExternalContent` (Warnung bei externem Inhalt). Das Modul führt sie bewusst nicht wieder ein.
- `window.CMNSTR_COOKIE_VERSION` gibt es nicht mehr.
- Das Cookie `cmnstr` ist HttpOnly. `document.cookie` liest es nicht.
- Einwilligungsprotokoll: neue Einstellung **Aufbewahrung des Einwilligungsprotokolls** (Standard 3 Jahre nach Ablauf der Einwilligung, Löschen täglich), neue Zeilen ohne Benutzername und URL, unveränderte Entscheidungen als `renew`, ältere Zeilen laufen aus ([Einwilligungsprotokoll](#einwilligungsprotokoll)).
- Neu sind `Vary: Cookie` (ggf. `Sec-GPC`, `DNT`) und `Cache-Control: private, no-cache` an Seiten, siehe [Caching](#caching).
