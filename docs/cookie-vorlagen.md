# Typische Cookie-Konfigurationen

Rolle: Betreiber und Redaktion. Diese Vorlagen ergänzen die [README](../README.md) von CookieMonster.

## Vorlage verwenden

1. Den passenden Dienst in der Liste unten aufklappen und die Zeilen kopieren.
2. Im Admin **Modules** → **Site** → **CookieMonster** öffnen, den Tab **Cookies** wählen und die passende Kategorie aufklappen.
3. Die Zeilen in das Feld **Dienste und Cookies** der Kategorie einfügen. Das Zeilenformat steht unter [Dienste und Cookies eintragen](../README.md#dienste-und-cookies-eintragen).
4. `beispiel.de` durch die eigene Domain ersetzen (Host der Website, ohne `https://`).
5. Nur Einträge behalten, die die Website tatsächlich einsetzt.
6. Speichern und die Website im Browser prüfen, einmal ohne und einmal mit Zustimmung (DevTools → Anwendung → Cookies/Speicher).

> [!NOTE]
> Die Vorlagen sind Beispiele und keine Rechtsberatung. Anbieter ändern Namen und Laufzeiten. Die Einträge mit der Datenschutzberatung abstimmen.

## Übersicht

- [Notwendige Cookies](#notwendige-cookies)
    - [ProcessWire](#vorlage-processwire)
    - [Friendly Captcha](#vorlage-friendly-captcha)
    - [Cloudflare Turnstile (mit Pre-Clearance)](#vorlage-cloudflare-turnstile-mit-pre-clearance)
    - [Eye-Able](#vorlage-eye-able)
- [Funktionelle Cookies](#funktionelle-cookies)
    - [Google reCAPTCHA](#vorlage-google-recaptcha)
- [Statistik-Cookies](#statistik-cookies)
    - [Matomo](#vorlage-matomo)
    - [Google Analytics 4](#vorlage-google-analytics-4)
    - [Google Tag Manager (Statistik)](#vorlage-google-tag-manager-statistik)
- [Marketing-Cookies](#marketing-cookies)
    - [Google Tag Manager (Marketing) / Google Ads](#vorlage-google-tag-manager-marketing-google-ads)
    - [Meta Pixel (Facebook/Instagram)](#vorlage-meta-pixel-facebook-instagram)
    - [LinkedIn Insight Tag](#vorlage-linkedin-insight-tag)
    - [TikTok Pixel](#vorlage-tiktok-pixel)
- [Externe Inhalte](#externe-inhalte)
    - [YouTube](#vorlage-youtube)
    - [Vimeo](#vorlage-vimeo)
    - [Google Maps](#vorlage-google-maps)
    - [Google Fonts](#vorlage-google-fonts)
- [Veraltete Einträge](#veraltete-einträge)
- [Quellen](#quellen)

_Stand: Oktober 2026. Die Einträge stammen aus den Cookie-Richtlinien der Anbieter (Quellen am Ende des Abschnitts), aus dem Code der Anbieter-Scripts und aus Stichproben im Browser; nicht offiziell dokumentierte Angaben sind in den Hinweisen gekennzeichnet. Anbieter ändern Namen und Laufzeiten regelmäßig: vor dem Livegang im Browser (DevTools → Anwendung → Cookies/Speicher) mit und ohne Zustimmung prüfen und nur übernehmen, was die Website tatsächlich einsetzt._

## Konventionen in den Beispielen

- **Anbieter-Spalte:** `beispiel.de` steht für First-Party-Cookies auf der eigenen Domain – nur diese kann CookieMonster ohne Zustimmung löschen (siehe [Löschen ohne Zustimmung](../README.md#löschen-ohne-zustimmung)). Cookies unter der Domain des Anbieters (z.B. `youtube.com`) sind Third-Party-Cookies und werden nur zur Information aufgeführt.
- **Dynamische Namen** stehen als Muster mit `*`, z.B. `_ga_*` für `_ga_ABC123XYZ` oder `_pk_id.*` für `_pk_id.1.a1b2`. Ohne `*` greift das Löschen nur bei exakt gleichem Namen.
- **Kein Cookie:** Einträge mit `localStorage`, `sessionStorage` oder `IndexedDB` als Dauer sind Browser-Speicher. Er ist ebenso einwilligungspflichtig (§ 25 TDDDG), kann serverseitig aber nicht gelöscht werden. Wie die Dauer gelesen wird, steht unter [Dauer und Speichertyp](../README.md#dauer-und-speichertyp).
- **Browser-Grenzen:** Chrome und Firefox kürzen jede Cookie-Laufzeit auf höchstens 400 Tage (getestet mit Chrome 154 und Firefox 156, gemäß [RFC 6265bis](https://httpwg.org/http-extensions/draft-ietf-httpbis-rfc6265bis.html)); WebKit unterstützt dieselbe Grenze, Safari kürzt per JavaScript gesetzte Cookies (ITP) zudem auf 7 Tage. Angaben über 400 Tage (z.B. „2 Jahre“ bei `_ga`) sind deshalb als 400 Tage eingetragen; der Hinweis unter dem Feld erinnert daran.
- **Jeder Name nur einmal je Kategorie:** In verschiedenen Kategorien darf derselbe Name stehen (z.B. `_ga` beim GTM unter Statistik und unter Marketing): Das Cookie bleibt erhalten, sobald einem der Dienste zugestimmt wurde. Wird GA4 über den Tag Manager geladen, nur die GTM-Gruppe eintragen, nicht zusätzlich die GA4-Gruppe.

## Notwendige Cookies

<details id="vorlage-processwire">
	<summary>ProcessWire</summary>

    wire|beispiel.de|Session-Cookie von ProcessWire (HTTP): Hält die Sitzung aufrecht, z.B. für Formulare, die Anmeldung und den Schutz vor Missbrauch (CSRF).|Sitzung
    wire_challenge|beispiel.de|Wird nur nach einer Anmeldung gesetzt und schützt die Sitzung vor Übernahme durch Dritte.|30 Tage
    wires|beispiel.de|Session-Cookie von ProcessWire (HTTPS): Hält die Sitzung aufrecht, z.B. für Formulare, die Anmeldung und den Schutz vor Missbrauch (CSRF).|Sitzung
    wires_challenge|beispiel.de|Wird nur nach einer Anmeldung gesetzt und schützt die Sitzung vor Übernahme durch Dritte.|30 Tage
    cmnstr|beispiel.de|Speichert Ihre Cookie-Einstellungen (Zustimmung bzw. Ablehnung je Dienst) samt Zeitpunkt, Consent-ID und Kürzel des damaligen Konfigurationsstands.|180 Tage

</details>

Der Name des Session-Cookies entspricht `$config->sessionName` (Standard `wire`) bzw. unter HTTPS `$config->sessionNameSecure` (Standard: Session-Name + `s`, also `wires`). Session- und Consent-Cookie werden nie gelöscht.

**Automatische Vorbefüllung:** Solange das Feld „Dienste und Cookies“ der notwendigen Cookies noch nicht gespeichert wurde, befüllt das Modul es mit dem Dienst „ProcessWire“ (`essential-processwire`): dem aktuell genutzten Session-Cookie, dessen `…_challenge`-Cookie und `cmnstr`. Vorbefüllt wird für HTTPS: mit `$config->sessionCookieSecure` der Name `$config->sessionNameSecure` (leer: `sessionName` + `s`), sonst `$config->sessionName`. Läuft die Site ausnahmsweise über HTTP, den Namen von Hand anpassen. `_challenge` nur mit `$config->sessionChallenge`, die Domain stammt aus `$config->httpHost`. Wird die Konfiguration einmal gespeichert, gilt der gespeicherte Wert; spätere Änderungen werden nicht übernommen und müssen von Hand nachgetragen werden.

<details id="vorlage-friendly-captcha">
	<summary>Friendly Captcha</summary>

Friendly Captcha setzt keine Cookies und legt nichts dauerhaft im Browser ab; v2 nutzt nur sessionStorage (`frc_sid`, `frc_sc` u. a.), das bis zum Schließen des Tabs erhalten bleibt. Ein Eintrag in den Cookie-Daten ist nicht nötig; in der Datenschutzerklärung sollte der Dienst trotzdem genannt werden.
</details>

<details id="vorlage-cloudflare-turnstile-mit-pre-clearance">
	<summary>Cloudflare Turnstile (mit Pre-Clearance)</summary>

    cf_clearance|beispiel.de|Cloudflare Turnstile: Speichert, dass die Bot-Prüfung bestanden wurde.|30 Minuten

</details>

Cloudflare Turnstile setzt `cf_clearance` nur mit „Pre-Clearance“ (standardmäßig aus); die Laufzeit entspricht der „Challenge Passage“ der Zone (Standard 30 Minuten). Ohne Pre-Clearance sind keine Turnstile-Cookies bekannt; Cloudflare dokumentiert das allerdings nicht ausdrücklich – im Browser prüfen. `__cf_bm` auf der eigenen Domain stammt nicht von Turnstile, sondern vom Bot Management, wenn die Website selbst über Cloudflare läuft.

<details id="vorlage-eye-able">
	<summary>Eye-Able</summary>

    ---eyeable|Eye-Able|Eye-Able Assist ist eine Software für Barrierefreiheit, mit der Sie die Darstellung dieser Website an Ihre Bedürfnisse anpassen können (z.B. Schriftgröße und Kontrast). Die Software wird von Servern der Web Inclusion GmbH in der EU geladen; Eye-Able setzt keine Cookies.|Wir verwenden Eye-Able, um unsere Website barrierefreier zu machen.
    eyeAbleVariables|beispiel.de|Speichert die von Ihnen in Eye-Able gewählten Einstellungen.|localStorage
    eyeAbleHideIcon|beispiel.de|Speichert, dass Sie das Eye-Able-Symbol ausgeblendet haben.|localStorage
    eyeAbleCrossStorage|beispiel.de|Speichert, dass Sie „Einstellungen teilen“ aktiviert haben.|localStorage
    eaIntro|beispiel.de|Speichert, dass der Einführungshinweis bereits angezeigt wurde.|sessionStorage

</details>

- Eye-Able setzt laut Anbieter keine Cookies, sondern nur localStorage/sessionStorage. Die Schlüsselnamen stammen aus `eyeAble.js` (v3.6.0), Eye-Able nennt sie nicht. Mit `eyeAble_blockLocalStorage = true` entfällt `eyeAbleVariables`.
- Gespeichert werden nur selbst gewählte Darstellungseinstellungen – unbedingt erforderlich für einen ausdrücklich gewünschten Dienst (§ 25 Abs. 2 Nr. 2 TDDDG); Eye-Able empfiehlt selbst die Einordnung als technisch notwendig. Das Widget sollte auch nicht hinter dem Banner warten, gerade die Menschen, die es brauchen, müssten sonst zuerst das Banner bedienen. Die Übertragung der IP-Adresse an das CDN gehört trotzdem in die Datenschutzerklärung (oder Eye-Able lokal hosten).
- „Einstellungen teilen“ (opt-in) speichert die Einstellungen zusätzlich in einem iFrame von `storage.eye-able-cdn.com`. Cookies unter `eye-able.com` im Browser stammen von einem Besuch der Eye-Able-Website, nicht vom Widget.

## Funktionelle Cookies

<details id="vorlage-google-recaptcha">
	<summary>Google reCAPTCHA</summary>

    ---recaptcha|Google reCAPTCHA|Google reCAPTCHA schützt unsere Formulare vor Spam und automatisierten Zugriffen. Dazu werden u. a. Ihre IP-Adresse sowie Browser- und Interaktionsdaten an Google übertragen, das sie in unserem Auftrag auswertet.|Das Formular ist mit Google reCAPTCHA vor Spam geschützt. Dies erfordert Ihre Zustimmung.
    _GRECAPTCHA|google.com|Speichert das Ergebnis der Risikoanalyse, um echte Besucher von Bots zu unterscheiden.|6 Monate
    rc::a|google.com|Unterscheidet Menschen von Bots.|localStorage
    rc::f|google.com|Unterscheidet Menschen von Bots.|localStorage
    rc::b|google.com|Unterscheidet Menschen von Bots.|sessionStorage
    rc::c|google.com|Unterscheidet Menschen von Bots.|sessionStorage

</details>

- `_GRECAPTCHA` (www.google.com, Pfad `/recaptcha`) entsteht erst bei der Prüfung (Klick bzw. `execute`), `rc::a`/`rc::c` schon beim Laden. Bei Einbindung über `www.recaptcha.net` liegen Cookie und Speicher unter `recaptcha.net`; dann diese Domain eintragen.
- Die Laufzeit von `_GRECAPTCHA` nennt Google nicht; 6 Monate (180 Tage) ist der beobachtete Wert. Die `rc::*`-Schlüssel sind nicht offiziell dokumentiert. `NID` setzt reCAPTCHA nicht selbst, ein vorhandenes wird höchstens mitgesendet.
- Seit 2. April 2026 ist Google für reCAPTCHA nur noch Auftragsverarbeiter, der Websitebetreiber alleiniger Verantwortlicher. Hinweise auf Googles Datenschutzerklärung und Nutzungsbedingungen entfallen; die eigene Datenschutzerklärung muss den Zweck (Sicherheit, Betrugs- und Missbrauchsabwehr) abdecken.

## Statistik-Cookies

<details id="vorlage-matomo">
	<summary>Matomo</summary>

    ---matomo|Matomo|Matomo erfasst das Nutzungsverhalten auf dieser Website pseudonymisiert, um uns Statistiken über Besucher und deren Verhalten bereitzustellen.|
    _pk_id.*|beispiel.de|Speichert eine zufällige Besucher-ID, um wiederkehrende Besucher zu erkennen.|6 Monate
    _pk_ses.*|beispiel.de|Fasst die Seitenaufrufe eines Besuchs zusammen.|30 Minuten
    _pk_ref.*|beispiel.de|Speichert, über welche Website oder Kampagne Sie zu uns gekommen sind.|6 Monate

</details>

- Die Namen haben die Form `_pk_<name>.<Site-ID>.<Hash>` (z.B. `_pk_id.1.a1b2`) – deshalb die Muster mit `*`.
- `_pk_id` gilt 6 Monate ab dem ersten Besuch, weil `templates/tags/matomo.php` `setVisitorCookieTimeout(15778800)` setzt (Matomo-Standard: 13 Monate). Die Laufzeit verlängert sich bei weiteren Besuchen nicht.
- Im cookielosen Modus (Modul-Option) setzt Matomo gar keine Cookies; dann genügt die Gruppe ohne Cookie-Zeilen.
- Im cookielosen Modus löscht `disableCookies` vorhandene Matomo-Cookies beim nächsten Seitenaufruf.
- Nur bei Bedarf ergänzen: `_pk_hsr.*` (Heatmap & Session Recording, 30 Minuten), `MatomoAbTesting` (A/B-Testing-Plugin, localStorage) und `mtm_consent`/`mtm_cookie_consent`/`mtm_consent_removed` (nur bei Nutzung der Matomo-eigenen Consent-API, laut Matomo 30 Jahre, in Chrome höchstens 400 Tage – mit CookieMonster nicht nötig).
- `_pk_uid` ist ein Third-Party-Cookie auf der Domain des Matomo-Servers (nur mit `use_third_party_id_cookie = 1` in der Matomo-Konfiguration, 13 Monate) und lässt sich von der Website nicht löschen.

<details id="vorlage-google-analytics-4">
	<summary>Google Analytics 4</summary>

    ---ga|Google Analytics|Google Analytics erfasst das Nutzungsverhalten auf dieser Website und überträgt die Daten an Google, um uns Statistiken über Besucher und deren Verhalten bereitzustellen.|
    _ga|beispiel.de|Speichert eine zufällige Client-ID, um Besucher voneinander zu unterscheiden.|400 Tage
    _ga_*|beispiel.de|Speichert den Sitzungsstatus (Sitzungs-ID, Anzahl der Sitzungen, Zeitstempel).|400 Tage

</details>

- GA4 setzt standardmäßig nur `_ga` und `_ga_<Mess-ID>`. Die Laufzeit ist in der GA4-Verwaltung einstellbar (sofort bis 25 Monate) und verlängert sich standardmäßig bei jedem Besuch.
- Ist die Property mit Google Ads verknüpft und `ad_storage` = `granted`, setzt das Google-Tag zusätzlich die `_gcl_*`-Cookies (siehe Google Ads) – mit CookieMonster also nur nach Zustimmung zu den Marketing-Cookies.
- `_gid` und `_gat` stammen aus dem eingestellten Universal Analytics und werden von GA4 nicht mehr gesetzt.
- Ohne Zustimmung (Consent Mode v2: `analytics_storage` = `denied`) liest und schreibt das Tag keine Cookies. Wird es trotzdem geladen (z.B. im GTM-Container wegen einer Marketing-Zustimmung), sendet es cookielose Pings an Google.
- Beim serverseitigen Tagging mit vom Server verwalteter Client-ID kommen `FPID` (2 Jahre, im Browser höchstens 400 Tage) und `FPLC` (20 Stunden) hinzu.

<details id="vorlage-google-tag-manager-statistik">
	<summary>Google Tag Manager (Statistik)</summary>

    ---gtm|Google Tag Manager|Über den Google Tag Manager werden – je nachdem, welchen Kategorien Sie zustimmen – Statistik- und/oder Marketing-Tags von Google geladen (u. a. Google Analytics sowie Google Ads, Conversion Linker). Der Tag Manager selbst setzt keine Cookies und überträgt nur die Daten, denen Sie tatsächlich zugestimmt haben.|Wir nutzen den Google Tag Manager u. a. zur statistischen Auswertung der Websitenutzung.
    _ga|beispiel.de|Speichert eine zufällige Client-ID, um Besucher voneinander zu unterscheiden.|400 Tage
    _ga_*|beispiel.de|Speichert den Sitzungsstatus (Sitzungs-ID, Anzahl der Sitzungen, Zeitstempel).|400 Tage

</details>

Die Cookie-Zeilen hängen von den Tags im Container ab – hier ein Container mit GA4. Dass gtm.js selbst keine Cookies setzt, ist beobachtet; Google sagt es nicht ausdrücklich. Weitere Tags (z.B. Hotjar, Microsoft Clarity) mit ihren Cookies ergänzen.

## Marketing-Cookies

<details id="vorlage-google-tag-manager-marketing-google-ads">
	<summary>Google Tag Manager (Marketing) / Google Ads</summary>

    ---gtm|Google Tag Manager|Über den Google Tag Manager werden – je nachdem, welchen Kategorien Sie zustimmen – Statistik- und/oder Marketing-Tags von Google geladen (u. a. Google Analytics sowie Google Ads, Conversion Linker). Der Tag Manager selbst setzt keine Cookies und überträgt nur die Daten, denen Sie tatsächlich zugestimmt haben.|Wir nutzen den Google Tag Manager zur Steuerung von Marketing- und Conversion-Tracking.
    _gcl_au|beispiel.de|Conversion Linker: Speichert eine pseudonyme Kennung, um Conversions Google-Ads-Kampagnen zuzuordnen.|90 Tage
    _gcl_aw|beispiel.de|Speichert die Google-Ads-Klick-ID (gclid) aus der Anzeigen-URL zur Conversion-Zuordnung.|90 Tage
    _gcl_gs|beispiel.de|Speichert Klickinformationen aus Google-Anzeigen für die Conversion-Messung.|90 Tage
    _gcl_gb|beispiel.de|Speichert die Klick-ID (gbraid/wbraid) für Kampagnen auf iOS-Geräten.|90 Tage
    _gcl_dc|beispiel.de|Speichert die Klick-ID (dclid) aus Campaign Manager 360, Display & Video 360 bzw. Search Ads 360.|90 Tage
    _gcl_ls|beispiel.de|Speichert Klickinformationen des Conversion Linkers.|localStorage
    IDE|doubleclick.net|Wird zur Auslieferung und Messung personalisierter Google-Werbung auf anderen Websites verwendet.|13 Monate
    test_cookie|doubleclick.net|Prüft, ob der Browser Cookies akzeptiert.|15 Minuten
    DSID|doubleclick.net|Nur bei angemeldeten Google-Nutzern: Erkennt den Nutzer auf anderen Websites, um seine Einstellungen zu personalisierter Werbung zu berücksichtigen.|2 Wochen

</details>

- Der Container lädt die Ads-Tags nur mit `ad_storage` = `granted`; die `_gcl_*`-Cookies entstehen erst nach Zustimmung (bzw. `_gcl_aw`/`_gcl_gb`/`_gcl_dc` nur nach einem Anzeigenklick). Statt einzelner Zeilen löscht auch `_gcl_*` alle Varianten (Google führt zusätzlich `_gcl_ag`, `_gcl_gf`, `_gcl_ha`, je 90 Tage).
- `IDE` gilt im EWR, in der Schweiz und im UK 13 Monate, sonst 24. Die doubleclick.net-Cookies sind Third-Party: Safari und Firefox blockieren sie standardmäßig, Chrome lässt sie weiter zu.
- `__gads`/`__gpi` setzen AdSense bzw. Google Ad Manager beim Ausspielen von Anzeigen, nicht das Conversion-Tracking.
- Beim serverseitigen Tagging (First-Party-Modus) kommen `FPID` (2 Jahre, im Browser höchstens 400 Tage), `FPLC` (20 Stunden) sowie `FPAU`/`FPGCLAW`/`FPGCLGB`/`FPGCLGS`/`FPGCLDC`/`FPGSID` (je 90 Tage) hinzu.

<details id="vorlage-meta-pixel-facebook-instagram">
	<summary>Meta Pixel (Facebook/Instagram)</summary>

    ---meta|Meta Pixel|Der Meta Pixel (ehemals Facebook Pixel) misst die Wirksamkeit von Werbeanzeigen anhand der Aktionen, die Besucher auf unserer Website ausführen. Bei Zustimmung findet ein Datenaustausch mit Meta (Facebook/Instagram) statt.|Wir nutzen den Meta Pixel zur Erfolgsmessung unserer Facebook- und Instagram-Werbeanzeigen.
    _fbp|beispiel.de|Identifiziert den Browser, um Werbeanzeigen auf Facebook und Instagram auszuliefern und deren Erfolg zu messen.|90 Tage
    _fbc|beispiel.de|Speichert die Facebook-Klick-ID (fbclid) aus der Anzeigen-URL, um einen Besuch der angeklickten Werbeanzeige zuzuordnen.|90 Tage
    fr|facebook.com|Wird von Meta verwendet, um Werbeanzeigen auszuliefern, zu messen und relevanter zu gestalten.|90 Tage
    datr|facebook.com|Erkennt den Browser wieder, um Meta-Konten vor Betrug und Missbrauch zu schützen.|400 Tage

</details>

`_fbc` entsteht nur, wenn die Seite mit `?fbclid=…` aufgerufen wurde. Weitere Meta-Cookies wie `c_user`/`xs` (365 Tage), `sb`/`dbln` und `oo` (je 400 Tage) gibt es nur bei angemeldeten Facebook-Nutzern bzw. nach Einstellungen auf facebook.com; Safari und Firefox blockieren die Third-Party-Cookies standardmäßig.

<details id="vorlage-linkedin-insight-tag">
	<summary>LinkedIn Insight Tag</summary>

    ---linkedin|LinkedIn Insight Tag|Das LinkedIn Insight Tag ermöglicht Kampagnen-Reporting sowie das Wiedererkennen von Websitebesuchern für Retargeting-Zwecke über die LinkedIn-Werbeplattform. Bei Zustimmung findet ein Datenaustausch mit LinkedIn statt.|Wir nutzen das LinkedIn Insight Tag zur Erfolgsmessung unserer LinkedIn-Werbeanzeigen.
    li_fat_id|beispiel.de|Speichert eine indirekte Mitglieds-Kennung aus dem Anzeigenklick für Conversion-Tracking und Retargeting.|30 Tage
    li_giant|beispiel.de|Speichert eine indirekte Kennung für Gruppen von LinkedIn-Mitgliedern zur Conversion-Messung.|7 Tage
    ln_or|beispiel.de|Prüft, ob auf dieser Website Analysen durch LinkedIn erfolgen dürfen.|1 Tag
    bcookie|linkedin.com|Browser-Kennung zur Missbrauchserkennung und Fehleranalyse.|1 Jahr
    lidc|linkedin.com|Wählt das Rechenzentrum für die Anfragen aus.|24 Stunden
    li_gc|linkedin.com|Speichert die Cookie-Einwilligung von Besuchern ohne LinkedIn-Konto.|6 Monate
    li_mc|linkedin.com|Speichert die Cookie-Einwilligung angemeldeter LinkedIn-Mitglieder zwischen.|6 Monate
    li_sugr|linkedin.com|Ordnet Besucher wahrscheinlichkeitsbasiert einer Person zu (geräteübergreifende Zuordnung).|90 Tage
    UserMatchHistory|linkedin.com|Synchronisiert Werbe-IDs für LinkedIn Ads (Retargeting).|30 Tage
    AnalyticsSyncHistory|linkedin.com|Speichert den Zeitpunkt der letzten Synchronisierung mit LinkedIn Analytics.|30 Tage
    lms_ads|linkedin.com|Erkennt LinkedIn-Mitglieder außerhalb von LinkedIn für Werbezwecke.|30 Tage
    lms_analytics|linkedin.com|Erkennt LinkedIn-Mitglieder außerhalb von LinkedIn für Analysezwecke.|30 Tage

</details>

`li_fat_id` wird nur nach einem Anzeigenklick und mit „Enhanced conversion tracking“ gesetzt (bei neuen Insight Tags Standard) und gilt 30 Tage ab dem letzten Klick. `bscookie` und `JSESSIONID` liegen auf `www.linkedin.com` und werden nicht an das Insight Tag gesendet. Die LinkedIn-Tabelle nennt zusätzlich die First-Party-Cookies `oribi_cookie_test` (Sitzung) und `oribili_user_guid` (1 Jahr); das aktuelle Tag legt außerdem `li_adsId` im localStorage an (inoffiziell) – vor Übernahme im Browser prüfen. LinkedIn schlüsselt die Cookies nicht je Produkt auf – die Auswahl folgt dem Abschnitt „Cookies LinkedIn drops on third party sites“ der LinkedIn-Cookie-Tabelle.

<details id="vorlage-tiktok-pixel">
	<summary>TikTok Pixel</summary>

    ---tiktok|TikTok Pixel|Der TikTok Pixel misst die Wirksamkeit von Werbeanzeigen auf TikTok anhand der Aktionen, die Besucher auf unserer Website ausführen, und ermöglicht Retargeting. Bei Zustimmung findet ein Datenaustausch mit TikTok statt.|Wir nutzen den TikTok Pixel zur Erfolgsmessung unserer TikTok-Werbeanzeigen.
    _ttp|beispiel.de|Identifiziert den Browser, um Aktionen auf dieser Website TikTok-Werbeanzeigen zuzuordnen.|13 Monate
    ttcsid|beispiel.de|Ordnet Ereignisse einer Sitzung zu, um Werbeanzeigen zu messen und zu personalisieren.|13 Monate
    ttcsid_*|beispiel.de|Ordnet Ereignisse je Pixel einer Sitzung zu, um Werbeanzeigen zu messen und zu personalisieren.|13 Monate
    ttclid|beispiel.de|Speichert die TikTok-Klick-ID aus der Anzeigen-URL, um einen Besuch der angeklickten Werbeanzeige zuzuordnen.|13 Monate
    _ttp|tiktok.com|Identifiziert den Browser, um Werbeanzeigen auf TikTok zu messen.|13 Monate
    _pangle|pangle-ads.com|Misst Werbeanzeigen im Pangle-Werbenetzwerk von TikTok.|13 Monate

</details>

Die Laufzeiten gelten laut TikTok jeweils 13 Monate ab letzter Nutzung. First-Party-Cookies lassen sich im TikTok Events Manager abschalten; schon gesetzte bleiben bis zum Ablauf erhalten.

## Externe Inhalte

<details id="vorlage-youtube">
	<summary>YouTube</summary>

    ---youtube|YouTube|Über die Video-Plattform „YouTube“ eingebettete Videos. Bei Zustimmung findet ein Datenaustausch mit YouTube bzw. Google statt.|Wir verwenden YouTube zur Darstellung von Videos auf unserer Seite.
    YSC|youtube.com|Stellt sicher, dass die Anfragen einer Sitzung vom selben Besucher stammen, und schützt vor Missbrauch.|Sitzung
    __Secure-YNID|youtube.com|Speichert Einstellungen, schätzt die verfügbare Bandbreite und dient der Fehleranalyse, Statistik und Werbung.|6 Monate
    VISITOR_INFO1_LIVE|youtube.com|Speichert eine Kennung für personalisierte Empfehlungen, Statistik und Werbung (wird schrittweise durch __Secure-YNID ersetzt).|6 Monate
    VISITOR_PRIVACY_METADATA|youtube.com|Speichert den Einwilligungs- und Datenschutzstatus bzw. die Region des Besuchers.|6 Monate
    __Secure-ROLLOUT_TOKEN|youtube.com|Wird für die Einführung neuer Funktionen und die Messung ihrer Wirkung verwendet.|6 Monate
    PREF|youtube.com|Speichert Wiedergabe-Einstellungen wie Autoplay, Lautstärke und Player-Größe.|8 Monate
    __Secure-YENID|youtube.com|Dient der Erkennung von Spam, Betrug und Missbrauch sowie der Fehleranalyse.|13 Monate
    ytidb::LAST_RESULT_ENTRY_KEY|youtube.com|Speichert die zuletzt abgerufenen Player-Daten.|localStorage
    yt-remote-*|youtube.com|Speichert Einstellungen und verbundene Geräte für die Übertragung auf andere Geräte (Cast).|localStorage
    yt-player-*|youtube.com|Speichert Player-Einstellungen wie Qualität, Lautstärke und Bandbreite.|localStorage
    YtIdbMeta|youtube.com|Technische Datenbank des Players.|IndexedDB

</details>

- Alle Cookies liegen unter `youtube.com` (Third-Party, partitioniert) und können von der Website nicht gelöscht werden.
- **youtube-nocookie.com** („Erweiterter Datenschutzmodus“) setzt beim Laden keine Cookies, legt aber trotzdem `ytidb::LAST_RESULT_ENTRY_KEY` (localStorage) und `YtIdbMeta` (IndexedDB) an und sendet Anfragen an Google – eine Zustimmung ist also weiterhin nötig. Bei Einbindung über youtube-nocookie.com genügen die Zeilen ohne Cookies; als Domain dann `youtube-nocookie.com` eintragen.
- `PREF` und `__Secure-YENID` entstehen erst bei Interaktion, `LOGIN_INFO` nur bei angemeldeten Nutzern. Die `yt-remote-*`/`yt-player-*`-Schlüssel sind nicht offiziell dokumentiert und entstehen erst bei der Wiedergabe.
- `VISITOR_PRIVACY_METADATA` steht nicht in der Google-Richtlinie; die Laufzeit ist beobachtet. Die vier 6-Monats-Cookies werden im Embed als partitionierte Cookies gesetzt.
- youtube.com sendet zusätzlich `__Secure-BUCKET` mit `SameSite=Lax` – im eingebetteten Player wird es nicht gespeichert und muss nicht eingetragen werden. Das youtube.com-Embed ruft außerdem `googleads.g.doubleclick.net` auf, youtube-nocookie.com nicht.

<details id="vorlage-vimeo">
	<summary>Vimeo</summary>

    ---vimeo|Vimeo|Über die Video-Plattform „Vimeo“ eingebettete Videos. Bei Zustimmung findet ein Datenaustausch mit Vimeo (Vimeo.com, Inc., USA) statt.|Wir verwenden Vimeo zur Darstellung von Videos auf unserer Seite.
    vuid|vimeo.com|Speichert eine Kennung, mit der Vimeo dem Video-Eigentümer Statistiken zur Wiedergabe bereitstellt.|400 Tage
    player|vimeo.com|Speichert Player-Einstellungen wie Lautstärke, Qualität und Untertitel.|1 Jahr
    flags|vimeo.com|Speichert vom Video-Eigentümer aktivierte Player-Funktionen.|1 Jahr
    player_clearance|vimeo.com|Schützt den Player vor Bots und automatisierten Zugriffen.|7 Tage
    cf_clearance|vimeo.com|Cloudflare: Speichert, dass die Bot-Prüfung bestanden wurde.|1 Jahr
    __cf_bm|vimeo.com|Cloudflare: Unterscheidet Menschen von Bots.|30 Minuten
    _cfuvid|vimeo.com|Cloudflare: Wird für die Begrenzung der Anfragerate verwendet.|Sitzung

</details>

Mit dem Parameter `?dnt=1` in der Player-URL setzt Vimeo nur noch die technisch notwendigen Cookies `player_clearance`, `cf_clearance`, `__cf_bm` und `_cfuvid`; `vuid`, `player` und `flags` entfallen. Bereits vorhandene vimeo.com-Cookies sendet der Browser trotzdem mit. Vimeo schreibt in seiner Liste `_cf_bm` – das Cookie heißt laut Cloudflare `__cf_bm`. Cookies für passwortgeschützte Videos, Webinare und Formulare (`[clip_id]_password`, `lc_[hash]` u. a.) nicht als `*`-Muster eintragen, sonst löscht CookieMonster gleichnamige eigene Cookies.

<details id="vorlage-google-maps">
	<summary>Google Maps</summary>

    ---gmaps|Google Maps|Google Maps stellt interaktive Karten bereit. Beim Laden der Karte werden u. a. Ihre IP-Adresse an Google übertragen und, je nach Nutzung, Cookies von Google gesetzt. Die Karte wird erst nach Ihrer Zustimmung geladen.|Die Anfahrt zu unserem Standort stellen wir auf einer Karte von Google Maps dar. Dies erfordert Ihre Zustimmung.
    NID|google.com|Speichert Einstellungen (z.B. Sprache) und Informationen für Werbung bei nicht angemeldeten Nutzern.|6 Monate
    __Secure-ENID|google.com|Speichert Einstellungen und schützt vor Missbrauch; wird abhängig von der Cookie-Entscheidung statt NID verwendet.|13 Monate
    AEC|google.com|Erkennt Spam, Betrug und Missbrauch.|6 Monate
    SOCS|google.com|Speichert die Cookie-Entscheidung auf Google-Seiten.|13 Monate
    SID|google.com|Nur bei angemeldeten Google-Nutzern: Enthält die verschlüsselte Konto-ID und den Zeitpunkt der letzten Anmeldung.|400 Tage
    HSID|google.com|Nur bei angemeldeten Google-Nutzern: Schützt zusammen mit SID das Google-Konto vor unberechtigtem Zugriff.|400 Tage
    SSID|google.com|Nur bei angemeldeten Google-Nutzern: Speichert Informationen zur Anmeldung und zu Einstellungen.|400 Tage
    APISID|google.com|Nur bei angemeldeten Google-Nutzern: Speichert Einstellungen für eingebettete Google-Dienste.|400 Tage
    SAPISID|google.com|Nur bei angemeldeten Google-Nutzern: Speichert Einstellungen für eingebettete Google-Dienste.|400 Tage
    __Secure-1PSID|google.com|Nur bei angemeldeten Google-Nutzern: Sichere Variante von SID für Google-eigene Seiten.|400 Tage
    __Secure-3PSID|google.com|Nur bei angemeldeten Google-Nutzern: Sichere Variante von SID für eingebettete Inhalte, u. a. für personalisierte Werbung.|400 Tage
    SIDCC|google.com|Nur bei angemeldeten Google-Nutzern: Sicherheits-Cookie zum Schutz der Nutzerdaten vor unberechtigtem Zugriff.|1 Jahr

</details>

- Das Karten-Embed (`google.com/maps/embed`) und die Maps JavaScript API setzen beim Laden keine Cookies und senden in einem Browser ohne Google-Cookies auch keine mit (beobachtet). Die IP-Adresse wird aber immer übertragen – die Zustimmung bleibt nötig.
- `AEC`, `__Secure-ENID` und `SOCS` setzt google.com mit `SameSite=Lax`/`Strict`. Im eingebetteten iFrame werden sie deshalb weder gesetzt noch gesendet, sondern nur auf google.com selbst (z.B. nach „Größere Karte ansehen“). Vorhandene `NID`- und Anmelde-Cookies können dagegen mitgesendet werden, solange der Browser Third-Party-Cookies zulässt.
- Google führt für das Embed keine eigene Liste. `NID`, `__Secure-ENID`, `AEC`, `SOCS`, `SID` und `HSID` stehen in der Google-Cookie-Richtlinie; die übrigen Anmelde-Cookies (`SSID`, `APISID`, …) nicht offiziell, ihre Laufzeiten sind beobachtete Werte.
- Die Anmelde-Cookies werden hier nur zur Information aufgeführt. Da die Namen auch als Löschmuster dienen, würde CookieMonster ohne Zustimmung gleichnamige Cookies der eigenen Domain löschen – bei Konflikten mit eigenen Cookies (z.B. einem eigenen `SID`) diese Zeilen weglassen.

<details id="vorlage-google-fonts">
	<summary>Google Fonts</summary>

Google Fonts setzt keine Cookies und legt nichts im Browser ab. Werden die Schriften aber von `fonts.googleapis.com`/`fonts.gstatic.com` geladen, erhält Google bei jedem Seitenaufruf IP-Adresse, User-Agent und Referrer. Das LG München I (Urteil vom 20.01.2022, Az. 3 O 17493/20) hat das ohne Einwilligung als DSGVO-Verstoß gewertet. Empfehlung: Schriften lokal hosten; dann ist kein Eintrag nötig.
</details>

## Veraltete Einträge

Diese Einträge finden sich häufig in älteren Konfigurationen, werden aber nicht mehr gesetzt und sollten entfernt werden:

| Eintrag                                                                                                                    | Dienst           | Grund                                                                                                                                        |
| -------------------------------------------------------------------------------------------------------------------------- | ---------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| `_gid`, `_gat`, `_gat_*`, `_gac_UA-*`, `_dc_gtm_*`, `AMP_TOKEN`, `__utm*`                                                  | Google Analytics | Stammen aus Universal Analytics/ga.js (eingestellt); GA4 setzt nur `_ga` und `_ga_*`. `_gac_gb_*` kann bei Ads-Verknüpfung weiter vorkommen. |
| `AID`, `TAID`                                                                                                              | Google Ads       | Laut Google nicht mehr gesetzt.                                                                                                              |
| `NID` bei reCAPTCHA                                                                                                        | reCAPTCHA        | Wird von reCAPTCHA nicht gesetzt, höchstens mitgesendet.                                                                                     |
| `1P_JAR`, `CONSENT`, `DV`, `CGIC`, `SNID`, `OGPC`, `OTZ`, `ANID`, `SEARCH_SAMESITE`                                        | Google Maps      | Nicht mehr in der Google-Cookie-Richtlinie bzw. betreffen die Google-Suche; `CONSENT` wurde durch `SOCS` ersetzt.                            |
| `__Secure-YEC`                                                                                                             | YouTube          | Durch `__Secure-YENID` ersetzt; YouTube löscht das alte Cookie aktiv.                                                                        |
| `GPS`                                                                                                                      | YouTube          | Weder in der Google-Richtlinie noch beim Laden beobachtet.                                                                                   |
| `yt.innertube::requests`, `yt.innertube::nextId`, `yt-remote-cast-*`, `yt-remote-session-*`, `yt-remote-fast-check-period` | YouTube          | Einzelschlüssel undokumentiert und wechselnd – als `yt-remote-*`/`yt-player-*` zusammengefasst.                                              |
| `_pk_cvar.*`, `_pk_testcookie.*`                                                                                           | Matomo           | Custom Variables sind seit Matomo 4 nur noch als Plugin verfügbar; der Test-Cookie wird nur im Internet Explorer gesetzt.                    |
| `_pk_id` ohne `*`                                                                                                          | Matomo           | Der echte Name enthält Site-ID und Hash (`_pk_id.1.a1b2`) – ohne `*` wird das Cookie nicht gelöscht.                                         |
| `bscookie`, `JSESSIONID`                                                                                                   | LinkedIn         | Liegen auf `www.linkedin.com` und werden nicht an das Insight Tag gesendet.                                                                  |
| `youtube_player`, `vimeo_player`                                                                                           | –                | Frühere Platzhalter in dieser README, keine echten Cookies.                                                                                  |

## Quellen

- Google: [Cookie-Richtlinie](https://policies.google.com/technologies/cookies), [Advertising and measurement cookie reference](https://business.safety.google/adscookies/), [GA4-Cookie-Nutzung](https://support.google.com/analytics/answer/11397207), [Consent Mode](https://developers.google.com/tag-platform/security/concepts/consent-mode), [Conversion Linker](https://support.google.com/tagmanager/answer/7549390), [Tag Manager und Datenschutz](https://support.google.com/tagmanager/answer/9323295), [YouTube: Erweiterter Datenschutzmodus](https://support.google.com/youtube/answer/171780), [reCAPTCHA FAQ](https://docs.cloud.google.com/recaptcha/docs/faq), [reCAPTCHA als Auftragsverarbeitung](https://security.googlecloudcommunity.com/community-blog-42/switching-google-s-role-with-recaptcha-from-data-controller-to-data-processor-6646), [Cookie-Einstellungen des Google-Tags](https://developers.google.com/tag-platform/security/guides/customize-cookies), [Maps Platform FAQ](https://developers.google.com/maps/faq), [Google Fonts und Datenschutz](https://developers.google.com/fonts/faq/privacy)
- Matomo: [Welche Cookies setzt Matomo?](https://matomo.org/faq/general/faq_146/), [JavaScript-Tracking-API](https://developer.matomo.org/api-reference/tracking-javascript), [global.ini.php](https://github.com/matomo-org/matomo/blob/5.x-dev/config/global.ini.php)
- Meta: [Cookie-Richtlinie](https://www.facebook.com/privacy/policies/cookies/?annotations[0]=explanation%2F1_common_cookies_and_uses), [fbp und fbc](https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc/)
- LinkedIn: [Cookie-Tabelle](https://www.linkedin.com/legal/l/cookie-table), [First-Party-Cookies im Insight Tag](https://learn.microsoft.com/en-us/linkedin/marketing/conversions/enabling-first-party-cookies)
- Vimeo: [Vimeo Player Cookies](https://help.vimeo.com/hc/en-us/articles/26080940921361-Vimeo-Player-Cookies), [Player-Parameter](https://help.vimeo.com/hc/en-us/articles/12426260232977-About-Player-Parameters)
- Cloudflare: [Cloudflare-Cookies](https://developers.cloudflare.com/fundamentals/reference/policies-compliances/cloudflare-cookies/), [Clearance](https://developers.cloudflare.com/cloudflare-challenges/concepts/clearance/), [Challenge Passage](https://developers.cloudflare.com/cloudflare-challenges/challenge-types/challenge-pages/challenge-passage/)
- Eye-Able: [Verwendet Eye-Able Assist Cookies?](https://help.eye-able.com/verwendet-eye-able-assist-cookies), [Wie Eye-Able Einstellungen speichert](https://eye-able.com/de/blog/wie-eye-able-deine-einstellungen-speichert)
- TikTok: [Cookies mit dem TikTok Pixel](https://ads.tiktok.com/help/article/using-cookies-with-tiktok-pixel?lang=en)
- Friendly Captcha: [Cookies](https://friendlycaptcha.com/legal/cookies-service-users/)
