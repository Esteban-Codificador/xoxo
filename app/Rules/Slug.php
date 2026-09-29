<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Lowercase words separated by single hyphens, the same pattern the content
 * package validator uses (PackageValidator::SLUG).
 */
class Slug implements ValidationRule
{
    public const string PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail(__('cms.invalid_slug'));
        }
    }
}
