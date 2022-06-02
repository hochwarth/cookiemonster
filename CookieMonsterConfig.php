<?php

namespace ProcessWire;

class CookieMonsterConfig extends Wire
{
    protected $data;

    protected $defaults = array(
        'titletext' => 'Diese Website verwendet Cookies',
        'bodytext' => "Wir verwenden Cookies, um Ihnen ein optimales Webseiten-Erlebnis zu bieten. Dazu zählen Cookies, die für den Betrieb der Seite und für die Steuerung unserer kommerziellen Unternehmensziele notwendig sind, sowie solche, die lediglich zu anonymen Statistikzwecken genutzt werden. Sie können selbst entscheiden, welche Kategorien Sie zulassen möchten. Bitte beachten Sie, dass auf Basis Ihrer Einstellungen womöglich nicht mehr alle Funktionalitäten der Seite zur Verfügung stehen. Weitere Informationen finden Sie in unseren Datenschutzhinweisen.",
        'buttontext_confirm' => "Auswahl bestätigen",
        'buttontext_accept' => "Alle auswählen",
        'use_stylesheet' => 1,
        'is_active' => 0,
        'multilanguage' => 0,
        'autolink' => 0,
        'target_string' => '',
        'target_page' => null,
        'imprint_page' => null,
        'cookies_necessary' => "wire|my-domain.de|Der Cookie ist für die sichere Anmeldung und die Erkennung von Spam oder Missbrauch der Webseite erforderlich.|Session\ncmnstr|my-domain.de|Speichert den Zustimmungsstatus des Benutzers für Cookies.|1 Jahr",
        'cookies_statistics' => "_ga|Google|Registriert eine eindeutige ID, die verwendet wird, um statistische Daten dazu, wie der Besucher die Website nutzt, zu generieren.|2 Jahre\n_gat|Google|Wird von Google Analytics verwendet, um die Anforderungsrate einzuschränken.|1 Tag\n_gid|Google|Registriert eine eindeutige ID, die verwendet wird, um statistische Daten dazu, wie der Besucher die Website nutzt, zu generieren|1 Tag",
        'introtext_necessary' => 'Notwendige Cookies helfen dabei, eine Webseite nutzbar zu machen, indem sie Grundfunktionen wie Seitennavigation und Zugriff auf sichere Bereiche der Webseite ermöglichen. Die Webseite kann ohne diese Cookies nicht richtig funktionieren.',
        'introtext_statistics' => 'Statistik-Cookies helfen Webseiten-Besitzern zu verstehen, wie Besucher mit Webseiten interagieren, indem Informationen anonym gesammelt und gemeldet werden.',
        'ga_property_id' => '',
        'table_placeholder' => '[[cookie-table]]'
    );

    public function __construct(array $data)
    {
        foreach ($this->defaults as $key => $value) {
            if (!isset($data[$key]) || $data[$key] == '') $data[$key] = $value;
        }

        $this->data = $data;
    }

    public function getConfig()
    {
        $fields = new InputfieldWrapper();
        $modules = wire('modules');

        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = __('Einstellungen');
        $fieldset->icon = 'cogs';
        $fields->add($fieldset);

        $field = $modules->get('InputfieldCheckbox');
        $field->label = __('Cookie-Banner aktiviert');
        $field->notes = __('Aktiviert die Anzeige des Cookie-Banners auf allen Seiten.');
        $field->name = 'is_active';
        $field->attr('value', $this->data['is_active']);
        if($this->data['is_active'] == 0) $field->attr('checked', '');
        else $field->attr('checked', 1);
        $field->columnWidth = '34';
        $fieldset->append($field);

        $field = $modules->get('InputfieldCheckbox');
        $field->label = __('CookieMonster-Stylesheet verwenden');
        $field->notes = __('Stellt eine einfache Basis-Formatierung zur Verfügung.');
        $field->name = 'use_stylesheet';
        $field->attr('value', $this->data['use_stylesheet']);
        if($this->data['use_stylesheet'] == 0) $field->attr('checked', '');
        else $field->attr('checked', 1);
        $field->columnWidth = '33';
        $fieldset->append($field);

        $field = $modules->get('InputfieldCheckbox');
        $field->label = __('Mehrsprachigkeit verwenden');
        $field->notes = __('Aktiviert mehrsprachig pflegbare Texte.');
        $field->name = 'multilanguage';
        $field->attr('value', $this->data['multilanguage']);
        if($this->data['multilanguage'] == 0) $field->attr('checked', '');
        else $field->attr('checked', 1);
        $field->columnWidth = '33';
        $fieldset->append($field);

        $field = $modules->get('InputfieldText');
        $field->label = __('Platzhalter für Cookie-Übersicht');
        $field->description = __('Dieser Platzhalter wird beim Seitenaufruf durch die Cookie-Übersicht ersetzt. So kann die die Cookie-Übersicht z.B. in der Datenschtuzerklärung ausgegeben werden.');
        $field->notes = __('Voraussetzung: der TextformatterCookieTable muss installiert und in der entsprechenden Feldkonfiguration ausgewählt sein.');
        $field->attr('name', 'table_placeholder');
        $field->attr('value', $this->data['table_placeholder']);
        $field->columnWidth = '100';
        $fieldset->append($field);

        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = __('Banner-Text');
        $fieldset->icon = 'align-left';
        $fieldset->collapsed = Inputfield::collapsedYes;
        $fields->add($fieldset);

        $field = $modules->get('InputfieldText');
        $field->label = __('Banner-Überschrift');
        $field->attr('name', 'titletext');
        $field->attr('value', $this->data['titletext']);
        $field->columnWidth = '100';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $fieldset->append($field);

        $field = $modules->get('InputfieldTextarea');
        $field->label = __('Banner-Text');
        $field->description = __('Der Text des Cookie-Banners');
        $field->notes = __('HTML ist erlaubt, Zeilenumbrüche werden automatisch in <br>-Elemente umgewandelt.');
        $field->attr('name', 'bodytext');
        $field->attr('value', $this->data['bodytext']);
        $field->columnWidth = '70';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $fieldset->append($field);

        $field = $modules->get('InputfieldCheckbox');
        $field->label = __('Begriff automatisch verlinken');
        $field->notes = __('Aktiviert die automatische Verlinkung eines Begriffs (z.B. „Datenschutz“) mit einer frei wählbaren Seite.');
        $field->name = 'autolink';
        $field->attr('value', $this->data['autolink']);
        if($this->data['autolink'] == 0) $field->attr('checked', '');
        else $field->attr('checked', 1);
        $field->columnWidth = '30';
        $fieldset->append($field);

        $field = $modules->get('InputfieldText');
        $field->label = __('Begriff');
        $field->description = __('Begriff, der automatisch verlinkt werden soll');
        $field->attr('name', 'target_string');
        $field->attr('value', $this->data['target_string']);
        $field->columnWidth = '50';
        $field->showIf = 'autolink=1';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $fieldset->append($field);

        $field = $modules->get('InputfieldPageListSelect');
        $field->label = __('Zielseite');
        $field->description = __('Seite, die automatisch mit dem Begriff verlinkt werden soll');
        $field->attr('name', 'target_page');
        $field->attr('value', $this->data['target_page']);
        $field->columnWidth = '50';
        $field->showIf = 'autolink=1';
        $fieldset->append($field);

        $field = $modules->get('InputfieldPageListSelect');
        $field->label = __('Impressum-Seite');
        $field->attr('name', 'imprint_page');
        $field->attr('value', $this->data['imprint_page']);
        $field->columnWidth = '100';
        $fieldset->append($field);

        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = __('Buttons');
        $fieldset->icon = 'map-signs';
        $fieldset->collapsed = Inputfield::collapsedYes;
        $fields->add($fieldset);

        $field = $modules->get('InputfieldText');
        $field->label = __('Beschriftung Auswahl-Button');
        $field->description = __('Beschriftung des Buttons, der mit gewählter Einstellung fortfährt');
        $field->attr('name', 'buttontext_confirm');
        $field->attr('value', $this->data['buttontext_confirm']);
        $field->columnWidth = '50';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $fieldset->append($field);

        $field = $modules->get('InputfieldText');
        $field->label = __('Beschriftung Auswahl-Button');
        $field->description = __('Beschriftung des Buttons, der alle Cookies akzeptiert');
        $field->attr('name', 'buttontext_accept');
        $field->attr('value', $this->data['buttontext_accept']);
        $field->columnWidth = '50';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $fieldset->append($field);

        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = __('Cookies');
        $fieldset->icon = 'certificate';
        $fields->add($fieldset);

        $necessary = $modules->get('InputfieldFieldset');
        $necessary->label = __('Notwendig');
        $necessary->icon = 'fire';
        $fieldset->add($necessary);

        $field = $modules->get('InputfieldTextarea');
        $field->label = __('Kurzbeschreibung für notwendige Cookies');
        $field->attr('name', 'introtext_necessary');
        $field->attr('value', $this->data['introtext_necessary']);
        $field->columnWidth = '100';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $necessary->append($field);

        $field = $modules->get('InputfieldTextarea');
        $field->label = __('Notwendige Cookies');
        $field->description = __('Geben Sie hier Informationen zu den notwendigen Cookies in folgendem Format ein');
        $field->notes = __('Folgendes Format verwenden: Name|Anbieter|Zweck|Ablauf');
        $field->attr('name', 'cookies_necessary');
        $field->attr('value', $this->data['cookies_necessary']);
        $field->columnWidth = '100';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $necessary->append($field);

        $statistics = $modules->get('InputfieldFieldset');
        $statistics->label = __('Statistik');
        $statistics->icon = 'signal';
        $fieldset->add($statistics);

        $field = $modules->get('InputfieldTextarea');
        $field->label = __('Kurzbeschreibung für Statistik Cookies');
        $field->attr('name', 'introtext_statistics');
        $field->attr('value', $this->data['introtext_statistics']);
        $field->columnWidth = '100';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $statistics->append($field);

        $field = $modules->get('InputfieldTextarea');
        $field->label = __('Statistik Cookies');
        $field->description = __('Geben Sie hier Informationen zu den notwendigen Cookies in folgendem Format ein');
        $field->notes = __('Folgendes Format verwenden: Name|Anbieter|Zweck|Ablauf');
        $field->attr('name', 'cookies_statistics');
        $field->attr('value', $this->data['cookies_statistics']);
        $field->columnWidth = '100';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $statistics->append($field);

        $external = $modules->get('InputfieldFieldset');
        $external->label = __('Externe Dienste');
        $external->icon = 'sitemap';
        $fieldset->add($external);

        $field = $modules->get('InputfieldTextarea');
        $field->label = __('Kurzbeschreibung für externe Dienste');
        $field->attr('name', 'introtext_external');
        $field->attr('value', $this->data['introtext_external']);
        $field->columnWidth = '100';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $external->append($field);

        $field = $modules->get('InputfieldTextarea');
        $field->label = __('Externe Dienste');
        $field->description = __('Geben Sie hier Informationen zu den externen Diensten in folgendem Format ein');
        $field->notes = __('Folgendes Format verwenden: Name|Anbieter|Zweck|Ablauf|Link|Typ(Script/Stylesheet)');
        $field->attr('name', 'cookies_external');
        $field->attr('value', $this->data['cookies_external']);
        $field->columnWidth = '100';
        if($this->data['multilanguage'] == 1) $field->useLanguages = true;
        $external->append($field);

        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = __('Google Analytics');
        $fieldset->icon = 'google';
        $fields->add($fieldset);

        $field = $modules->get('InputfieldText');
        $field->label = __('Property ID');
        $field->description = __('Tragen Sie hier eine Property-ID ein, um das Tracking zu aktivieren.');
        $field->notes = __('Erlaubte Formate:  
         Universal Analytics (UA-XXXXXXXX)  
         Google Analytics 4 (G-XXXXXXXX)  
         Google Ads (AW-XXXXXXXX)  
         Floodlight (DC-XXXXXXXX)');
        $field->attr('name', 'ga_property_id');
        $field->attr('value', $this->data['ga_property_id']);
        $field->columnWidth = '100';
        $fieldset->append($field);

        return $fields;
    }
}