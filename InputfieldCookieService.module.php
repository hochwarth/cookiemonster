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

        return InputfieldSelect::___render() . $this->renderMatch();
    }

    private function renderMatch(): string
    {
        if (!$this->get('showMatch')) {
            return '';
        }

        $name = $this->wire()->sanitizer->entities((string) $this->attr('name') . '__match');
        $choices = [
            CookieMonster::ALL_SERVICES => $this->_('UND: Allen muss zugestimmt sein'),
            CookieMonster::MATCH_ANY => $this->_('ODER: Mindestens einem muss zugestimmt sein'),
        ];
        $out = '';

        foreach ($choices as $value => $label) {
            $checked = ($value === CookieMonster::MATCH_ANY) === $this->matchAny ? ' checked' : '';
            $out .= "<br><label class='uk-margin-right'><input type='radio' class='uk-radio' name='{$name}' value='{$value}'{$checked}> "
                . $this->wire()->sanitizer->entities($label) . '</label>';
        }

        $legend = $this->wire()->sanitizer->entities($this->_('Bei mehreren Einträgen:'));

        return "<div class='InputfieldCookieServiceMatch uk-margin-small-top'><span class='uk-margin-small-right'>{$legend}</span>{$out}</div>";
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
            /** @var FilenameArray $files */
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
            $this->matchAny = $input->get((string) $this->attr('name') . '__match') === CookieMonster::MATCH_ANY;
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

            foreach ($group['options'] as $key => $label) {
                $this->addOption($key, "\u{00A0}\u{00A0}\u{00A0}$label");
            }
        }
    }
}
