<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\LinkStatus;
use App\Enums\VideoProvider;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\RecordsAuthors;
use Carbon\CarbonImmutable;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * A video of the catalog (§32, ADR-035): a provider and its ID, never
 * embed HTML. Title, channel and thumbnail come from oEmbed; learners see
 * it only while the last check says it is still available.
 *
 * @property int $id
 * @property VideoProvider $provider
 * @property string $external_id
 * @property string $title
 * @property string|null $description
 * @property int|null $duration_seconds
 * @property string|null $thumbnail_url
 * @property string|null $instructor
 * @property Difficulty|null $difficulty
 * @property string $language
 * @property LinkStatus $link_status
 * @property CarbonImmutable|null $last_checked_at
 * @property int|null $last_http_status
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'provider', 'external_id', 'title', 'description', 'duration_seconds', 'thumbnail_url', 'instructor',
    'difficulty', 'language', 'link_status', 'last_checked_at', 'last_http_status', 'status', 'published_at',
])]
class Video extends Model
{
    /** @use HasFactory<VideoFactory> */
    use Auditable, HasContentStatus, HasFactory, RecordsAuthors;

    protected function casts(): array
    {
        return [
            'provider' => VideoProvider::class,
            'duration_seconds' => 'integer',
            'difficulty' => Difficulty::class,
            'link_status' => LinkStatus::class,
            'last_checked_at' => 'datetime',
            'last_http_status' => 'integer',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /** Where people watch it: derived from the ID, so it is never stored. */
    public function url(): string
    {
        return "https://www.youtube.com/watch?v={$this->external_id}";
    }

    public function isVisibleToLearners(): bool
    {
        return $this->status === ContentStatus::Published && $this->link_status === LinkStatus::Ok;
    }

    /**
     * Published and still available on its provider.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleToLearners(Builder $query): void
    {
        $query->where('videos.status', ContentStatus::Published->value)->where('videos.link_status', LinkStatus::Ok->value);
    }

    /**
     * @return MorphToMany<Lesson, $this>
     */
    public function lessons(): MorphToMany
    {
        return $this->morphedByMany(Lesson::class, 'linkable', 'video_links', 'video_id', 'linkable_id');
    }
}
