<?php

namespace Nickwest\EloquentForms;

use Illuminate\Support\Collection;
use Nickwest\EloquentForms\Exceptions\InvalidFieldException;
use Nickwest\EloquentForms\Exceptions\InvalidRouteException;
use Nickwest\EloquentForms\Exports\TableExporter;
use Nickwest\EloquentForms\Traits\Themeable;
use Illuminate\Support\Facades\Route;

class Table
{
    use Themeable;

    /**
     * Submit Button name (used for first submit button only).
     *
     * @var Nickwest\EloquentForms\Attributes
     */
    public $attributes = null;

    /**
     * Collection that the table will display.
     *
     * @var Illuminate\Support\Collection
     */
    public $Collection = null;

    /**
     * Exporter used to generate Excel file.
     *
     * @var Maatwebsite\Excel\Concerns\Exportable
     */
    public $Exporter = null;

    /**
     * Fields that should not have htmlspecialchars applied.
     *
     * @var array
     */
    public $raw_fields = [];

    /**
     * Array of field names.
     *
     * @var array
     */
    protected $display_fields = [];

    /**
     * Array of field names.
     *
     * @var array
     */
    protected $field_replacements = [];

    /**
     * Theme to use.
     *
     * @var string
     */
    protected $Theme = null;

    /**
     * Array of labels, keyed by field_name.
     *
     * @var array
     */
    protected $labels = [];

    /**
     * Array of css classes.
     *
     * @var array
     */
    protected $classes = [];

    /**
     * Array of column linking patterns keyed by field_name.
     *
     * @var array
     */
    protected $linking_patterns = [];

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->Theme = new DefaultTheme();
        $this->attributes = new Attributes();
        $this->Exporter = new TableExporter($this);
    }

    /**
     * Set the Collection data to the Table Object.
     *
     * @param  Illuminate\Support\Collection  $Collection
     * @return void
     */
    public function setData(Collection $Collection): void
    {
        $this->Collection = $Collection;
    }

    /**
     * member mutator.
     *
     * @param  array  $field_names
     * @return void
     */
    public function setDisplayFields(array $field_names): void
    {
        foreach ($field_names as $field_name) {
            $this->display_fields[$field_name] = $field_name;
        }
    }

    /**
     * Display Fields Accessor.
     *
     * @return array
     */
    public function getDisplayFields(): array
    {
        return $this->display_fields;
    }

    /**
     * Add field labels to the existing labels.
     *
     * @param  array  $labels
     * @return void
     *
     * @throws Nickwest\EloquentForms\Exceptions\InvalidFieldException
     */
    public function setLabels(array $labels): void
    {
        foreach ($labels as $field_name => $label) {
            if (isset($this->display_fields[$field_name])) {
                $this->labels[$field_name] = $label;
            } else {
                throw new InvalidFieldException('"' . $field_name . '" not set as a display field');
            }
        }
    }

    /**
     * Get a Label for a specific field.
     *
     * @param  string  $field_name
     * @return string
     */
    public function getLabel(string $field_name): string
    {
        if (isset($this->labels[$field_name])) {
            return $this->labels[$field_name];
        }

        // This should always be done the same was as Field::makeLabel()
        return ucfirst(str_replace('_', ' ', $field_name));
    }

    /**
     * Get array of Labels.
     *
     * @return array
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Set a replacement string for a given field's output. Use {field_name} to inject values
     * field_name supports any field set in the given object/array that exists within the Collection.
     *
     * @param  string  $field  field name
     * @param  string  $html  non-escaped text to replace field value in output
     * @return void
     */
    public function addFieldReplacement(string $field, string $html): void
    {
        $this->field_replacements[$field] = $html;
    }

    /**
     * Check if a field has a replacement pattern.
     *
     * @param  string  $field  field name
     * @return bool
     */
    public function hasFieldReplacement(string $field): bool
    {
        return isset($this->field_replacements[$field]);
    }

    /**
     * Get a field's replacement value.
     *
     * @param  string  $field  field name
     * @param  string  $Object  Object or array
     * @return string
     */
    public function getFieldReplacement(string $field, &$Object): string
    {
        $pattern = '/\{([a-zA-Z0-9_\?]+)\}/';
        $results = [];
        preg_match_all($pattern, $this->field_replacements[$field], $results, PREG_PATTERN_ORDER);

        $replaced = $this->field_replacements[$field];

        if (!is_array($results[0]) || !is_array($results[1])) {
            return $replaced;
        }

        foreach ($results[0] as $key => $match) {
            // If it's an option parameter, drop the question market
            $optional = false;
            if (strpos($results[1][$key], '?') !== false) {
                $optional = true;
                $results[1][$key] = str_replace('?', '', $results[1][$key]);
            }

            if (is_object($Object) && isset($Object->{$results[1][$key]})) {
                $replaced = $this->fieldReplaceString($results[0][$key], $Object->{$results[1][$key]}, $replaced, $field);
            } elseif (is_array($Object) && isset($Object[$results[1][$key]])) {
                $replaced = $this->fieldReplaceString($results[0][$key], $Object[$results[1][$key]], $replaced, $field);
            } elseif ($optional) {
                $replaced = str_replace($results[0][$key], '', $replaced);
            }
        }

        return $replaced;
    }

    /**
     * Get array of Field Replacements.
     *
     * @return array
     */
    public function getFieldReplacements(): array
    {
        return $this->field_replacements;
    }

    /**
     * String replace for specific field, conditionally uses htmlspecialchars.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $subject
     * @param  string  $field
     * @return string
     */
    protected function fieldReplaceString($search, $replace, $subject, $field)
    {
        if (in_array($field, $this->raw_fields)) {
            $replaced = str_replace($search, $replace, $subject);
        } else {
            $replaced = str_replace($search, e($replace), $subject);
        }

        return $replaced;
    }

    /**
     *  Convenience method for setting a linking pattern on a field.
     *
     * @param  string  $field_name
     * @param  string  $href
     */
    public function addLinkingPattern(string $field_name, string $href): void
    {
        // Make and set the linking pattern
        $this->field_replacements[$field_name] = '<a href="' . $href . '">{' . $field_name . '}</a>';
    }

    /**
     * Convenience method for creating a link replacement pattern by route name.
     *
     * @param  string  $field_name
     * @param  string  $route_name
     * @param  array  $replacement_map
     * @param  mixed  $query_string
     * @return void
     *
     * @throws Nickwest\EloquentForms\InvalidRouteException
     */
    public function addLinkingPatternByRoute(string $field_name, string $route_name, array $replacement_map = [], $query_string = []): void
    {
        $Route = Route::getRoutes()->getByName($route_name);
        if ($Route == null) {
            throw new InvalidRouteException('Invalid route name ' . $route_name);
        }

        $uri = $Route->uri;
        foreach ($replacement_map as $key => $new) {
            $uri = str_replace($key, $new, $uri);
        }

        if (is_array($query_string) && count($query_string) > 0) {
            $uri .= '?' . implode('&', array_map(function ($key, $val) {
                return $key . '=' . $val;
            }, array_keys($query_string), $query_string));
        } elseif (is_string($query_string)) {
            $uri .= '?' . $query_string;
        }

        // If it already has a linking pattern, replace $field_name with that linking pattern
        $link_text = (isset($this->field_replacements[$field_name]) ? $this->field_replacements[$field_name] : '{' . $field_name . '}');

        // Make and set the linking pattern
        $this->field_replacements[$field_name] = '<a href="/' . $uri . '">' . $link_text . '</a>';
    }

    /**
     * Make a view and extend $extends in $section, $blade_data is the data array to pass to View::make().
     *
     * @param  array  $blade_data
     * @param  string  $extends
     * @param  string  $section
     * @return \Illuminate\View\View
     */
    public function makeView(array $blade_data = [], string $extends = '', string $section = ''): \Illuminate\View\View
    {
        $blade_data['Table'] = $this;
        $blade_data['extends'] = $extends;
        $blade_data['section'] = $section;

        $this->Theme->prepareTableView($this);

        $template = ($extends != '' ? 'table-extend' : 'table');

        return $this->getThemeView($template, $blade_data);
    }
}
