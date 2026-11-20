<?php

declare(strict_types=1);

namespace ProcessWire;

/**
 * @property bool $showMatch
 */
class InputfieldCookieService extends InputfieldAsmSelect
{
    /**
     * Kollidiert nie mit Dienst-Schlüsseln, die immer mit einer Kategorie beginnen
     */
    private const GROUP_PREFIX = '_group-';

    private bool $processing = false;

    private bool $matchAny = false;

    /**
     * @return array<string, mixed>
     */
    public static function getModuleInfo(): array
    {
        return [
            'title' => 'CookieMonster Service Select',
            'version' => 100,
            'summary' => 'Select one or more CookieMonster cookie categories or services.',
            'author' => 'Hochwarth IT',
            'icon' => 'shield',
            'requires' => [
                'CookieMonster',
                'PHP>=8.2',
                'ProcessWire>=3.0.229',
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->set('showMatch', false);

        $this->setAsmSelectOption('sortable', false);
        $this->addServiceOptionGroups();
    }

    /**
     * Wie InputfieldAsmSelect::___render(), aber ohne gewählte Optionen ans Ende zu schieben.
     */
    public function ___render(): string
    {
        $settings = [];

        foreach ($this->asmOptions as $key => $value) {
            if (!isset($this->asmDefaults[$key]) || $this->asmDefaults[$key] != $value) {
                $settings[$key] = $value;
            }
        }

        $this->attr('data-asmopt', \json_encode($settings));
        // AdminThemeUikit gibt Selects außerhalb von Spalten sonst nur `width: auto`; asmSelect übernimmt die Klasse
        $this->addClass('InputfieldMaxWidth');

        return InputfieldSelect::___render() . $this->renderMatch();
    }

    private function renderMatch(): string
    {
        if (!$this->get('showMatch')) {
            return '';
        }

        $sanitizer = $this->wire()->sanitizer;
        $name = $sanitizer->entities($this->name . '__match');
        $choices = [
            CookieMonster::ALL_SERVICES => $this->_('allen (UND)'),
            CookieMonster::MATCH_ANY => $this->_('mindestens einem (ODER)'),
        ];
        $out = '';

        foreach ($choices as $value => $label) {
            $checked = ($value === CookieMonster::MATCH_ANY) === $this->matchAny ? ' checked' : '';
            $out .= "<label class='uk-margin-small-right'><input type='radio' class='uk-radio' name='{$name}' value='{$value}'{$checked}> "
                . $sanitizer->entities($label) . '</label>';
        }

        $legend = $sanitizer->entities($this->_('Zustimmung nötig zu:'));

        return "<div class='InputfieldCookieServiceMatch uk-flex uk-flex-wrap uk-flex-middle uk-margin-small-top' role='radiogroup' aria-labelledby='{$name}_legend'>"
            . "<span id='{$name}_legend' class='uk-margin-small-right'>{$legend}</span>{$out}</div>";
    }

    /**
     * @param string $key
     */
    public function getAttribute($key): mixed
    {
        $value = parent::getAttribute($key);

        if ($key === 'value' && $this->matchAny && \is_array($value)) {
            \array_unshift($value, CookieMonster::MATCH_ANY);
        }

        return $value;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setAttribute($key, mixed $value): self
    {
        // Wert aus FieldtypeCookieService: '|'-getrennter String
        if ($key === 'value' && \is_string($value)) {
            $value = $value === '' ? [] : \explode('|', $value);
        }

        if ($key === 'value' && \is_array($value)) {
            $this->matchAny = \in_array(CookieMonster::MATCH_ANY, $value, true);
            $value = \array_values(\array_diff($value, [CookieMonster::MATCH_ANY]));
        }

        parent::setAttribute($key, $value);

        if ($key === 'value' && !$this->processing) {
            $stored = \array_map(\strval(...), (array) parent::getAttribute('value'));
            $missing = \array_diff($stored, \array_map(\strval(...), \array_keys($this->getOptions())));

            if (!empty($missing)) {
                $this->addServiceOptionGroups($missing, '_stale');
            }
        }

        return $this;
    }

    /**
     * @param bool $renderValueMode
     */
    public function renderReady(?Inputfield $parent = null, $renderValueMode = false): bool
    {
        $result = (bool) parent::renderReady($parent, $renderValueMode);

        $config = $this->wire()->config;
        $ownUrl = $config->urls($this->className());
        $asmUrl = $config->urls('InputfieldAsmSelect');

        foreach ([$config->scripts, $config->styles] as $files) {
            foreach (\iterator_to_array($files) as $file) {
                $file = (string) $file;

                if (\str_starts_with($file, "{$ownUrl}asmselect/")) {
                    $files->remove($file);
                    $files->add($asmUrl . \substr($file, \strlen($ownUrl)));
                } elseif (\str_starts_with($file, "{$ownUrl}{$this->className()}.css")) {
                    $files->remove($file);
                }
            }
        }

        // InputfieldAsmSelect.js initialisiert alle `.InputfieldAsmSelect select[multiple]`
        $this->wire()->modules->loadModuleFileAssets('InputfieldAsmSelect');
        $this->addClass('InputfieldAsmSelect', 'wrapClass');

        return $result;
    }

    public function ___processInput(WireInputData $input): self
    {
        $this->processing = true;

        try {
            parent::___processInput($input);
        } finally {
            $this->processing = false;
        }

        // Zwischenüberschriften könnten manipuliert mitgeschickt werden
        $value = \array_values(\array_filter((array) parent::getAttribute('value'), static function ($key): bool {
            return !\str_starts_with((string) $key, self::GROUP_PREFIX) && $key !== CookieMonster::MATCH_ANY;
        }));
        parent::setAttribute('value', $value);

        if ($this->get('showMatch')) {
            $this->matchAny = $input->get($this->name . '__match') === CookieMonster::MATCH_ANY;
        }

        return $this;
    }

    /**
     * @param string[] $keepKeys
     */
    private function addServiceOptionGroups(array $keepKeys = [], ?string $onlyGroup = null): void
    {
        $cookieMonster = $this->wire()->modules->get('CookieMonster');

        if (!$cookieMonster instanceof CookieMonster) {
            return;
        }

        foreach ($cookieMonster->getServiceOptionGroups($keepKeys) as $groupId => $group) {
            if ($onlyGroup !== null && $groupId !== $onlyGroup) {
                continue;
            }

            $parent = self::GROUP_PREFIX . \ltrim($groupId, '_');
            $this->addOption($parent, $group['label'], ['disabled' => 'disabled']);

            $categoryLabel = null;

            foreach ($group['options'] as $key => $label) {
                if ($key === $groupId) {
                    $categoryLabel = \sprintf($this->_('∗ Gesamte Kategorie (%s)'), $key);

                    continue;
                }

                if (\str_starts_with($label, "{$group['label']}: ")) {
                    $label = \substr($label, \strlen("{$group['label']}: "));
                }

                $this->addOption($key, "\u{00A0}\u{00A0}\u{00A0}$label");
            }

            if ($categoryLabel !== null) {
                $this->addOption($groupId, "\u{00A0}\u{00A0}\u{00A0}$categoryLabel");
            }
        }
    }
}
