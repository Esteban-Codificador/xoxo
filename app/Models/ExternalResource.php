<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\LinkStatus;
use App\Enums\ResourceType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ExternalResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * An external learning resource (official documentation, paper, book...).
 * Not named "Resource" to avoid clashing with PHP's resource type and
 * Laravel's API resources.
 *
 * @property int $id
 * @property string $title
 * @property string $url
 * @property ResourceType $type
 * @property string $provider
 * @property string $description
 * @property Difficulty|null $difficulty
 * @property string $language
 * @property bool $is_official
 * @property LinkStatus $link_status
 * @property CarbonImmutable|null $last_checked_at
 * @property int|null $last_http_status
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 */
#[Fillable([
    'title', 'url', 'type', 'provider', 'description', 'difficulty', 'language', 'is_official',
    'link_status', 'last_checked_at', 'last_http_status', 'status', 'published_at',
])]
class ExternalResource extends Model
{
    protected $table = 'resources';

    /** @use HasFactory<ExternalResourceFactory> */
    use Auditable, HasContentStatus, HasFactory;

    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'difficulty' => Difficulty::class,
            'is_official' => 'boolean',
            'link_status' => LinkStatus::class,
            'last_checked_at' => 'datetime',
            'last_http_status' => 'integer',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return MorphToMany<Lesson, $this>
     */
    public function lessons(): MorphToMany
    {
        return $this->morphedByMany(Lesson::class, 'linkable', 'resource_links', 'resource_id', 'linkable_id');
    }

    /**
     * @return MorphToMany<Skill, $this>
     */
    public function skills(): MorphToMany
    {
        return $this->morphedByMany(Skill::class, 'linkable', 'resource_links', 'resource_id', 'linkable_id');
    }

    /**
     * @return MorphToMany<Track, $this>
     */
    public function tracks(): MorphToMany
    {
        return $this->morphedByMany(Track::class, 'linkable', 'resource_links', 'resource_id', 'linkable_id');
    }
}
