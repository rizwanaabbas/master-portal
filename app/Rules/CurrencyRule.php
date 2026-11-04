<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CurrencyRule implements Rule
{
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
        if (is_array($value)) {
            foreach ($value as $currency) {
                if (!is_numeric($currency)) {
                    return false;
                }
            }
        } else {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Something went wrong, please try again later..';
    }
}
