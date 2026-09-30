<?php

namespace App\Domain\Learning\State;

/**
 * A REQUIRED dependency the learner has not met yet. Under ADVISORY it is
 * a warning; under STRICT it blocks progress actions (ADR-009).
 */
final readonly class Blocker
{
    private function __construct(
        public string $type,
        public string $slug,
        public string $title,
        public ?int $progress = null,
        public ?int $required = null,
    ) {}

    public static function lesson(string $slug, string $title): self
    {
        return new self('lesson', $slug, $title);
    }

    public static function track(string $slug, string $title, int $progress, int $required): self
    {
        return new self('track', $slug, $title, $progress, $required);
    }

    /** A skill never blocks lessons: it only says what to develop first. */
    public static function skill(string $slug, string $title, int $progress, int $required): self
    {
        return new self('skill', $slug, $title, $progress, $required);
    }

    /**
     * @return array{type: string, slug: string, title: string, progress: int|null, required: int|null}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'slug' => $this->slug,
            'title' => $this->title,
            'progress' => $this->progress,
            'required' => $this->required,
        ];
    }
}
