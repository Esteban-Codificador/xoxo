<?php

namespace App\Domain\Content\Package;

use App\Domain\Content\RichContent\InvalidRichContent;
use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;
use App\Domain\Content\RichContent\RichContent;
use App\Domain\Curriculum\Graph\DependencyGraph;
use App\Domain\Curriculum\Publishing\LessonDraft;
use App\Domain\Curriculum\Publishing\LessonReadiness;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\DependencyKind;
use App\Enums\Difficulty;
use App\Enums\ResourceType;
use App\Enums\UnlockPolicy;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Checks a content package before import: field rules per entity, unique
 * slugs, resolvable references, acyclic dependency graphs, convertible
 * Markdown and the publishing contract of everything marked PUBLISHED.
 */
final class PackageValidator
{
    private const string KEY = 'regex:/^[a-z0-9]+(?:[.-][a-z0-9]+)*$/';

    private const string SLUG = 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** @var list<PackageIssue> */
    private array $issues = [];

    public function __construct(
        private readonly MarkdownToRichContent $markdown,
        private readonly LessonReadiness $readiness,
    ) {}

    /**
     * @return list<PackageIssue>
     */
    public function validate(ContentPackage $package): array
    {
        $this->issues = $package->readIssues();

        if ($package->roadmap() === null) {
            $this->issue('roadmap.yaml', 'el paquete necesita un roadmap.');
        }

        foreach (EntityType::cases() as $type) {
            foreach ($package->all($type) as $entity) {
                $this->validateFields($entity);
            }
        }

        $this->validateUniqueSlugs($package);
        $this->validateReferences($package);
        $this->validateGraphs($package);
        $this->validateRichContent($package);
        $this->validatePublishing($package);

        return $this->issues;
    }

    private function validateFields(SourceEntity $entity): void
    {
        $validator = Validator::make($entity->data, $this->rules($entity->type));

        foreach ($validator->errors()->all() as $message) {
            $this->issue($entity->file, $message);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(EntityType $type): array
    {
        $status = ['sometimes', Rule::enum(ContentStatus::class)];
        $difficulty = ['required', Rule::enum(Difficulty::class)];
        $dependencies = fn (string $target, bool $withThreshold) => array_filter([
            'depends_on' => ['sometimes', 'array'],
            "depends_on.*.{$target}" => ['required', 'string', self::KEY],
            'depends_on.*.kind' => ['required', Rule::enum(DependencyKind::class)],
            'depends_on.*.min_progress' => $withThreshold ? ['required', 'integer', 'between:1,100'] : null,
        ]);

        return match ($type) {
            EntityType::Roadmap => [
                'key' => ['required', self::KEY],
                'slug' => ['required', self::SLUG, 'max:120'],
                'title' => ['required', 'string', 'max:200'],
                'summary' => ['required', 'string'],
                'locale' => ['required', 'in:es,en'],
                'unlock_policy' => ['required', Rule::enum(UnlockPolicy::class)],
                'mastery_threshold' => ['required', 'integer', 'between:50,100'],
                'status' => $status,
            ],
            EntityType::Track => [
                'key' => ['required', self::KEY],
                'slug' => ['required', self::SLUG, 'max:120'],
                'title' => ['required', 'string', 'max:200'],
                'summary' => ['required', 'string'],
                'why_it_matters' => ['required', 'string'],
                'icon' => ['nullable', self::SLUG],
                'difficulty' => $difficulty,
                'estimated_hours' => ['nullable', 'integer', 'between:1,1000'],
                'review_interval_months' => ['nullable', 'integer', 'between:1,60'],
                'status' => $status,
                ...$dependencies('track', true),
            ],
            EntityType::Module => [
                'key' => ['required', self::KEY],
                'slug' => ['required', self::SLUG, 'max:120'],
                'title' => ['required', 'string', 'max:200'],
                'summary' => ['required', 'string'],
                'status' => $status,
            ],
            EntityType::Lesson => [
                'key' => ['required', self::KEY],
                'slug' => ['required', self::SLUG, 'max:160'],
                'title' => ['required', 'string', 'max:200'],
                'type' => ['required', Rule::in(ContentType::lessonValues())],
                'difficulty' => $difficulty,
                'estimated_minutes' => ['required', 'integer', 'between:1,600'],
                'status' => $status,
                'last_reviewed' => ['nullable', 'date_format:Y-m-d'],
                'summary' => ['required', 'string'],
                'why_it_matters' => ['required', 'string'],
                'objectives' => ['required', 'array'],
                'objectives.*' => ['required', 'string'],
                'skills' => ['sometimes', 'array'],
                'skills.*.key' => ['required', 'string', self::KEY],
                'skills.*.weight' => ['required', 'integer', 'between:1,5'],
                'resources' => ['sometimes', 'array'],
                'resources.*' => ['required', 'string', self::KEY],
                ...$dependencies('lesson', false),
            ],
            EntityType::Skill => [
                'key' => ['required', self::SLUG, 'max:120'],
                'name' => ['required', 'string', 'max:160'],
                'difficulty' => $difficulty,
                'icon' => ['nullable', self::SLUG],
                'status' => $status,
                ...$dependencies('skill', true),
            ],
            EntityType::Resource => [
                'key' => ['required', self::KEY],
                'title' => ['required', 'string', 'max:200'],
                'url' => ['required', 'url:https', 'max:2048'],
                'type' => ['required', Rule::enum(ResourceType::class)],
                'provider' => ['required', 'string', 'max:120'],
                'description' => ['required', 'string'],
                'is_official' => ['required', 'boolean'],
                'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
                'language' => ['required', 'in:es,en'],
            ],
        };
    }

    private function validateUniqueSlugs(ContentPackage $package): void
    {
        $this->uniqueWithin($package->all(EntityType::Track), fn (SourceEntity $e) => $e->string('slug'), 'slug');
        $this->uniqueWithin($package->all(EntityType::Lesson), fn (SourceEntity $e) => $e->string('slug'), 'slug');
        $this->uniqueWithin($package->all(EntityType::Module), fn (SourceEntity $e) => $e->parentKey.'/'.$e->string('slug'), 'slug dentro del track');
        $this->uniqueWithin($package->all(EntityType::Resource), fn (SourceEntity $e) => rtrim($e->string('url'), '/'), 'url');
    }

    /**
     * @param  array<string, SourceEntity>  $entities
     * @param  callable(SourceEntity): string  $value
     */
    private function uniqueWithin(array $entities, callable $value, string $label): void
    {
        $seen = [];

        foreach ($entities as $entity) {
            $current = $value($entity);

            if (isset($seen[$current])) {
                $this->issue($entity->file, "{$label} repetido \"{$current}\" (también en {$seen[$current]}).");

                continue;
            }

            $seen[$current] = $entity->file;
        }
    }

    private function validateReferences(ContentPackage $package): void
    {
        foreach ($package->all(EntityType::Track) as $track) {
            $this->references($package, $track, EntityType::Track, $this->dependencyKeys($track, 'track'));
        }

        foreach ($package->all(EntityType::Skill) as $skill) {
            $this->references($package, $skill, EntityType::Skill, $this->dependencyKeys($skill, 'skill'));
        }

        foreach ($package->all(EntityType::Lesson) as $lesson) {
            $this->references($package, $lesson, EntityType::Lesson, $this->dependencyKeys($lesson, 'lesson'));
            $this->references($package, $lesson, EntityType::Skill, array_map(fn ($skill) => is_array($skill) ? (string) ($skill['key'] ?? '') : '', $lesson->list('skills')));
            $this->references($package, $lesson, EntityType::Resource, array_values(array_filter($lesson->list('resources'), 'is_string')));
        }
    }

    /**
     * @param  list<string>  $keys
     */
    private function references(ContentPackage $package, SourceEntity $from, EntityType $type, array $keys): void
    {
        foreach ($keys as $key) {
            if ($key !== '' && $package->find($type, $key) === null) {
                $this->issue($from->file, "referencia a {$type->value} inexistente \"{$key}\".");
            }
        }
    }

    private function validateGraphs(ContentPackage $package): void
    {
        foreach ([[EntityType::Track, 'track'], [EntityType::Skill, 'skill'], [EntityType::Lesson, 'lesson']] as [$type, $field]) {
            $entities = $package->all($type);
            $edges = [];

            foreach ($entities as $entity) {
                foreach ($this->dependencyKeys($entity, $field) as $prerequisite) {
                    $edges[] = [$entity->key, $prerequisite];
                }
            }

            $cycle = DependencyGraph::fromEdges($edges, array_keys($entities))->findCycle();

            if ($cycle !== null) {
                $first = $entities[$cycle[0]] ?? null;
                $this->issue($first->file ?? $type->value, 'ciclo de dependencias: '.implode(' → ', $cycle).'.');
            }
        }
    }

    private function validateRichContent(ContentPackage $package): void
    {
        foreach ([EntityType::Roadmap, EntityType::Track, EntityType::Lesson] as $type) {
            foreach ($package->all($type) as $entity) {
                if ($type === EntityType::Lesson || ($entity->body ?? '') !== '') {
                    $this->richContent($entity);
                }
            }
        }

        foreach ($package->all(EntityType::Skill) as $skill) {
            if (trim((string) $skill->body) === '') {
                $this->issue($skill->file, 'la skill necesita una descripción en el cuerpo del archivo.');
            }
        }
    }

    private function validatePublishing(ContentPackage $package): void
    {
        $roadmap = $package->roadmap();

        foreach ($package->all(EntityType::Track) as $track) {
            if ($track->isPublished() && $roadmap !== null && ! $roadmap->isPublished()) {
                $this->issue($track->file, 'un track publicado necesita el roadmap publicado.');
            }
        }

        foreach ($package->all(EntityType::Module) as $module) {
            $track = $package->find(EntityType::Track, (string) $module->parentKey);

            if ($module->isPublished() && $track !== null && ! $track->isPublished()) {
                $this->issue($module->file, 'un módulo publicado necesita el track publicado.');
            }
        }

        foreach ($package->all(EntityType::Lesson) as $lesson) {
            if (! $lesson->isPublished()) {
                continue;
            }

            $body = $this->richContent($lesson, report: false);

            if ($body === null) {
                continue;
            }

            $module = $package->find(EntityType::Module, (string) $lesson->parentKey);
            $track = $module === null ? null : $package->find(EntityType::Track, (string) $module->parentKey);

            $draft = new LessonDraft(
                summary: $lesson->string('summary'),
                whyItMatters: $lesson->string('why_it_matters'),
                objectives: array_values(array_filter($lesson->list('objectives'), 'is_string')),
                body: $body,
                skillCount: count($lesson->list('skills')),
                practiceActivityCount: 0,
                parentsPublished: ($module?->isPublished() ?? false) && ($track?->isPublished() ?? false),
            );

            foreach ($this->readiness->check($draft) as $issue) {
                $this->issue($lesson->file, 'no cumple el contrato de publicación: '.$issue->message());
            }
        }
    }

    private function richContent(SourceEntity $entity, bool $report = true): ?RichContent
    {
        try {
            return $this->markdown->convert((string) $entity->body);
        } catch (InvalidRichContent $exception) {
            if ($report) {
                foreach ($exception->errors as $error) {
                    $this->issue($entity->file, "contenido: {$error}");
                }
            }

            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function dependencyKeys(SourceEntity $entity, string $field): array
    {
        $keys = [];

        foreach ($entity->list('depends_on') as $dependency) {
            if (is_array($dependency) && is_string($dependency[$field] ?? null)) {
                $keys[] = $dependency[$field];
            }
        }

        return $keys;
    }

    private function issue(string $file, string $message): void
    {
        $this->issues[] = new PackageIssue($file, $message);
    }
}
