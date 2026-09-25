<?php

namespace App\Models;

use App\Casts\AsRichContent;
use App\Domain\Content\RichContent\RichContent;
use App\Enums\ContentType;
use App\Enums\Difficulty;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Immutable snapshot of a lesson taken when it is published.
 *
 * @property int $id
 * @property int $lesson_id
 * @property int $version
 * @property string $title
 * @property string $summary
 * @property string $why_it_matters
 * @property list<string> $learning_objectives
 * @property RichContent $body
 * @property ContentType $content_type
 * @property Difficulty $difficulty
 * @property int $estimated_minutes
 * @property string $content_hash
 * @property string|null $change_note
 * @property int|null $published_by
 * @property CarbonImmutable $published_at
 * @property CarbonImmutable $created_at
 */
#[Fillable([
    'lesson_id', 'version', 'title', 'summary', 'why_it_matters', 'learning_objectives', 'body', 'content_type',
    'difficulty', 'estimated_minutes', 'content_hash', 'change_note', 'published_by', 'published_at',
])]
class LessonVersion extends Model
{
    public const null UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Lesson versions are immutable.'));
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'learning_objectives' => 'array',
            'body' => AsRichContent::class,
            'content_type' => ContentType::class,
            'difficulty' => Difficulty::class,
            'estimated_minutes' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function hashFields(array $fields): string
    {
        $body = $fields['body'] ?? null;

        if (is_array($body)) {
            $fields['body'] = RichContent::fromArray($body)->hash();
        }

        ksort($fields);

        return hash('sha256', (string) json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
