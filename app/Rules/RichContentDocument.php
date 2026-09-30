<?php

namespace App\Rules;

use App\Domain\Content\RichContent\RichContent;
use App\Domain\Content\RichContent\RichContentValidator;
use App\Enums\MediaKind;
use App\Models\MediaAsset;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value must be a RichContent envelope ({version, doc}) that passes the
 * server allowlist (ADR-023), and every image must be a stored one. The
 * editor is configured with the same allowlist, so this only fails for
 * tampered or outdated clients.
 */
class RichContentDocument implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $errors = (new RichContentValidator)->errors($value);

        if ($errors !== []) {
            $fail(__('cms.invalid_body', ['errors' => implode('; ', array_slice($errors, 0, 3))]));

            return;
        }

        $ids = RichContent::mediaIdsOf(is_array($value) ? ($value['doc'] ?? null) : null);

        if ($ids === []) {
            return;
        }

        $found = MediaAsset::query()->whereIn('id', $ids)->where('kind', MediaKind::Image)->pluck('id')->all();

        foreach (array_diff($ids, $found) as $missing) {
            $fail(__('media.missing', ['id' => $missing]));
        }
    }
}
