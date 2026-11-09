<?php

declare(strict_types=1);

namespace ProcessWire;

class FieldtypeCookieService extends Fieldtype
{
    private const MAX_KEY_LENGTH = 128;

    private const MAX_LENGTH = 512;

    private const SEPARATOR = '|';

    /**
     * @return array<string, mixed>
     */
    public static function getModuleInfo(): array
    {
        return [
            'title' => 'CookieMonster Service Reference',
            'version' => 100,
            'summary' => 'Field that stores references to one or more CookieMonster cookie categories or services.',
            'author' => 'Hochwarth IT',
            'icon' => 'shield',
            'requires' => [
                'CookieMonster',
                'PHP>=8.2',
                'ProcessWire>=3.0.229',
            ],
        ];
    }

    /**
     * Akzeptiert einen Schlüssel, eine Liste ('a|b', 'a b', 'a,b') oder ein Array.
     */
    public function sanitizeValue(Page $page, Field $field, mixed $value): string
    {
        if (!\is_array($value)) {
            $value = \is_scalar($value) ? (\preg_split('/[\s,|]+/', (string) $value) ?: []) : [];
        }

        $sanitizer = $this->wire()->sanitizer;
        $keys = [];

        foreach ($value as $key) {
            $key = \is_scalar($key) ? $sanitizer->nameFilter(\trim((string) $key), ['-', '_'], '_', false, self::MAX_KEY_LENGTH) : '';

            if ($key === '' || isset($keys[$key])) {
                continue;
            }

            if (\strlen(\implode(self::SEPARATOR, $keys + [$key => $key])) > self::MAX_LENGTH) {
                break;
            }

            $keys[$key] = $key;
        }

        return \implode(self::SEPARATOR, $keys);
    }

    public function getBlankValue(Page $page, Field $field): string
    {
        return '';
    }

    /**
     * `!=` übernimmt getMatchQuery(), PageFinder würde 'a|b' != 'a' als wahr werten.
     */
    public function isEmptyValue(Field $field, mixed $value): bool
    {
        if ($value instanceof Selector) {
            return $value->operator === '!=' && (string) $value->value !== '';
        }

        return parent::isEmptyValue($field, $value);
    }

    /**
     * @param PageFinderDatabaseQuerySelect|DatabaseQuerySelect $query
     * @param string $table
     * @param string $subfield
     * @param string $operator
     * @param mixed $value
     * @return PageFinderDatabaseQuerySelect|DatabaseQuerySelect
     */
    public function getMatchQuery($query, $table, $subfield, $operator, $value)
    {
        $value = (string) $value;

        if ($value === '' || !\in_array($operator, ['=', '!='], true) || \strpbrk($value, ' ,|') !== false) {
            return parent::getMatchQuery($query, $table, $subfield, $operator, $value);
        }

        $table = $this->wire()->database->escapeTable($table);
        $key = $query->bindValueGetKey($value);
        $not = $operator === '!=' ? 'NOT ' : '';

        $query->where("{$not}FIND_IN_SET({$key}, REPLACE({$table}.data, '" . self::SEPARATOR . "', ','))");

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDatabaseSchema(Field $field): array
    {
        $schema = parent::getDatabaseSchema($field);
        $schema['data'] = 'varchar(' . self::MAX_LENGTH . ") NOT NULL DEFAULT ''";

        return $schema;
    }

    public function getInputfield(Page $page, Field $field): Inputfield
    {
        /** @var InputfieldCookieService $inputfield */
        $inputfield = $this->wire()->modules->get('InputfieldCookieService');
        $inputfield->description = $this->_('Bei mehreren Einträgen erscheint der Inhalt erst, wenn allen zugestimmt wurde.');

        $cookieMonster = $this->wire()->modules->get('CookieMonster');

        if (!$cookieMonster instanceof CookieMonster) {
            return $inputfield;
        }

        $options = $cookieMonster->getServiceOptions();
        $notes = [];

        foreach ($cookieMonster->normalizeServiceKeys($page->getUnformatted($field->name)) as $value) {
            if (!isset($options[$value])) {
                $notes[] = \sprintf(
                    $this->_('Achtung: „%1$s“ ist nicht mehr konfiguriert. Bis zur Korrektur gilt dafür die Zustimmung zu „%2$s“.'),
                    $value,
                    $cookieMonster->getServiceLabel($cookieMonster->resolveServiceKey($value))
                );
            }
        }

        $inputfield->notes = \implode("\n", $notes);

        return $inputfield;
    }

    /**
     * @param string[] $keepKeys
     * @return array<string, string>
     */
    public function getSelectableServices(array $keepKeys = []): array
    {
        $cookieMonster = $this->wire()->modules->get('CookieMonster');

        return $cookieMonster instanceof CookieMonster ? $cookieMonster->getServiceOptions($keepKeys) : [];
    }

    public function ___getConfigInputfields(Field $field): InputfieldWrapper
    {
        $inputfields = parent::___getConfigInputfields($field);
        $sanitizer = $this->wire()->sanitizer;
        $items = '';

        foreach ($this->getSelectableServices() as $label) {
            $items .= '<li>' . $sanitizer->entities($label) . '</li>';
        }

        /** @var InputfieldMarkup $f */
        $f = $this->wire()->modules->get('InputfieldMarkup');
        $f->attr('name', '_cookie_services');
        $f->label = $this->_('Auswählbare Cookie-Dienste');
        $f->icon = 'shield';
        $f->description = $this->_('Die Liste entsteht bei jedem Aufruf aus der CookieMonster-Konfiguration und ist hier nicht bearbeitbar. Kategorien werden unter *Module > CookieMonster* aktiviert, Unterkategorien über Gruppen-Definitionen (`---id|Titel|…`) in den Cookie-Daten.');
        $f->value = $items === ''
            ? '<p>' . $this->_('Derzeit ist keine Cookie-Kategorie aktiv.') . '</p>'
            : "<ul>{$items}</ul>";

        $inputfields->add($f);

        return $inputfields;
    }
}
