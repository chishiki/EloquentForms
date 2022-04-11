<?php

namespace Nickwest\EloquentForms\CustomFields\daysofweek;

use Illuminate\Support\Facades\View;

use Nickwest\EloquentForms\CustomField as BaseCustomField;
use Nickwest\EloquentForms\DefaultTheme;
use Nickwest\EloquentForms\Field;

class CustomField extends BaseCustomField
{
    /**
     * The Days of the week that we use for storing daysofweek fields.
     *
     * @var array
     */
    public $daysofweek = ['M' => 'Mon', 'T' => 'Tue', 'W' => 'Wed', 'R' => 'Thu', 'F' => 'Fri', 'S' => 'Sat', 'U' => 'Sun'];

    public function makeView(Field $Field, bool $prev_inline = false, bool $view_only = false)
    {
        // TODO: make is so themes can override custom fields too.
        return View::make(DefaultTheme::getDefaultNamespace() . '::customfields.daysofweek', ['Field' => $Field, 'daysofweek' => $this->daysofweek, 'view_only' => $view_only]);
    }

    public function hook_setAllFormValues(Field $Field, $value)
    {
        if (is_object($value) || is_array($value)) {
            throw new \Exception('$value cannot be an array or Object');
        }

        $value = explode('|', $value);

        return $value;
    }
}
