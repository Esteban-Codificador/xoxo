<?php

namespace App\Casts;

use App\Domain\Content\RichContent\RichContent;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores RichContent as jsonb. Setting an invalid document throws, so no
 * invalid rich content can reach the database through Eloquent.
 *
 * @implements CastsAttributes<RichContent, RichContent|array<string, mixed>|null>
 */
final class AsRichContent implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?RichContent
    {
        if ($value === null) {
            return null;
        }

        return RichContent::fromArray(is_string($value) ? json_decode($value, true, flags: JSON_THROW_ON_ERROR) : $value);
    }

    /**
     * @return array<string, string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        // fromArray() validates the envelope and throws InvalidRichContent otherwise.
        $content = $value instanceof RichContent ? $value : RichContent::fromArray($value);

        return [$key => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
    }
}
