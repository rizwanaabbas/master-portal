<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class NotNullAndNotEmptyRule   implements Rule
{
    private $attribute;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->attribute = str_replace('_', ' ', $attribute);
        return isset($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The '.$this->attribute.' field is required.';
    }
}
