<?php

namespace App\Rules;

use App\Domain\Content\RichContent\RichContentValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value must be a RichContent envelope ({version, doc}) that passes the
 * server allowlist (ADR-023). The editor is configured with the same
 * allowlist, so this only fails for tampered or outdated clients.
 */
class RichContentDocument implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $errors = (new RichContentValidator)->errors($value);

        if ($errors !== []) {
            $fail(__('cms.invalid_body', ['errors' => implode('; ', array_slice($errors, 0, 3))]));
        }
    }
}
