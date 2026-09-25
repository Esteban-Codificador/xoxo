<?php

namespace App\Models;

use App\Casts\AsRichContent;
use App\Domain\Content\RichContent\RichContent;
use App\Enums\ContentStatus;
use App\Enums\UnlockPolicy;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\RecordsAuthors;
use Carbon\CarbonImmutable;
use Database\Factories\RoadmapFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $summary
 * @property RichContent|null $description
 * @property string $locale
 * @property UnlockPolicy $unlock_policy
 * @property int $mastery_threshold
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 */
#[Fillable(['slug', 'title', 'summary', 'description', 'locale', 'unlock_policy', 'mastery_threshold', 'status', 'published_at'])]
class Roadmap extends Model
{
    /** @use HasFactory<RoadmapFactory> */
    use Auditable, HasContentStatus, HasFactory, RecordsAuthors;

    protected function casts(): array
    {
        return [
            'description' => AsRichContent::class,
            'unlock_policy' => UnlockPolicy::class,
            'mastery_threshold' => 'integer',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Track, $this>
     */
    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class)->orderBy('position');
    }
}
