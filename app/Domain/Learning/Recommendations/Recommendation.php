<?php

namespace App\Domain\Learning\Recommendations;

use App\Domain\Learning\State\RoadmapState;
use App\Enums\RecommendationReason;

/**
 * One thing to study next: a lesson or a track, why (a reason key plus
 * its parameters, translated by the client) and its place in the list.
 */
final readonly class Recommendation
{
    /**
     * @param  'lesson'|'track'  $type
     * @param  array<string, string|int>  $params
     */
    private function __construct(
        public RecommendationReason $reason,
        public string $type,
        public string $slug,
        public string $title,
        public ?string $track,
        public array $params,
        public int $priority = 0,
    ) {}

    /**
     * @param  array<string, string|int>  $params
     */
    public static function lesson(RecommendationReason $reason, RoadmapState $state, int $lesson, array $params = []): self
    {
        $info = $state->lessonInfo($lesson);

        return new self($reason, 'lesson', $info['slug'], $info['title'], $state->trackInfo($info['track_id'])['title'], $params);
    }

    /**
     * @param  array<string, string|int>  $params
     */
    public static function track(RecommendationReason $reason, string $slug, string $title, array $params = []): self
    {
        return new self($reason, 'track', $slug, $title, null, $params);
    }

    public function withPriority(int $priority): self
    {
        return new self($this->reason, $this->type, $this->slug, $this->title, $this->track, $this->params, $priority);
    }

    public function key(): string
    {
        return "{$this->type}:{$this->slug}";
    }

    /**
     * @return array{reason: string, subject: array{type: string, slug: string, title: string, track: string|null}, params: array<string, string|int>, priority: int}
     */
    public function toArray(): array
    {
        return [
            'reason' => $this->reason->value,
            'subject' => ['type' => $this->type, 'slug' => $this->slug, 'title' => $this->title, 'track' => $this->track],
            'params' => $this->params,
            'priority' => $this->priority,
        ];
    }
}
