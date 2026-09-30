<?php

namespace App\Domain\Content\Package\Export;

use App\Domain\Content\Media\MediaNames;
use App\Domain\Content\Package\ContentPackage;
use App\Domain\Content\Package\EntityType;
use App\Domain\Content\RichContent\InvalidRichContent;
use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;
use App\Domain\Content\RichContent\Markdown\RichContentToMarkdown;
use App\Domain\Content\RichContent\RichContent;
use App\Enums\ContentStatus;
use App\Models\ContentImportRecord;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\MediaAsset;
use App\Models\Module;
use App\Models\Pivots\LessonDependency;
use App\Models\Pivots\SkillDependency;
use App\Models\Pivots\TrackDependency;
use App\Models\Roadmap;
use App\Models\Skill;
use App\Models\Track;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The database side of an export: every entity of a roadmap with the key,
 * file and fields the package format gives it (the inverse of each
 * importer).
 *
 * Keys come from the sync records, so an imported entity keeps its key;
 * one created in the CMS gets one from its slug. Files keep the names they
 * already have in the target package; only the NN prefix follows the
 * position in the database.
 *
 * A lesson is written as learners see it: its published version, with
 * status PUBLISHED while it is visible. Unpublished changes stay in the
 * database and are reported. The images of what is written go along, as
 * files of media/.
 */
final class DatabaseSnapshot
{
    public const string UNUSED_RESOURCES_FILE = 'resources/otros.yaml';

    /** @var array<string, array<int, string>> Keys by type and id. */
    private array $keys = [];

    /** @var array<string, array<string, true>> */
    private array $taken = [];

    /** @var array<string, array<string, string>> Existing file by type and key. */
    private array $files = [];

    /** @var array<string, int> Place of each existing resource inside its file. */
    private array $resourceOrder = [];

    /** @var list<string> */
    private array $warnings = [];

    /** @var list<string> */
    private array $unfaithful = [];

    private MediaNames $media;

    public function __construct(
        private readonly RichContentToMarkdown $markdown,
        private readonly MarkdownToRichContent $reader,
    ) {
        $this->media = new MediaNames;
    }

    /**
     * @return list<ExportedEntity>
     */
    public function take(Roadmap $roadmap, string $package, ?ContentPackage $existing): array
    {
        $this->keys = $this->taken = $this->files = $this->resourceOrder = [];
        $this->warnings = $this->unfaithful = [];
        $this->media = new MediaNames;
        $this->remember($package, $existing);

        $tracks = $roadmap->tracks()->with(['prerequisites', 'modules.lessons' => fn ($query) => $query->with([
            'publishedVersion', 'skills', 'prerequisites', 'resources',
        ])])->get();
        $lessons = $tracks->flatMap(fn (Track $track) => $track->modules->flatMap(fn (Module $module) => $module->lessons));
        $skills = Skill::query()->whereNotIn('id', $this->foreignIds('skill', $package))->with('prerequisites')->orderBy('slug')->get();
        $resources = ExternalResource::query()->whereNotIn('id', $this->foreignIds('resource', $package))->orderBy('id')->get();

        // Keys first: entities reference each other by key.
        $this->key(EntityType::Roadmap, $roadmap, $roadmap->slug);
        foreach ($tracks as $track) {
            $trackKey = $this->key(EntityType::Track, $track, $track->slug);

            foreach ($track->modules as $module) {
                $this->key(EntityType::Module, $module, "{$trackKey}.{$module->slug}");
            }
        }
        $lessons->each(fn (Lesson $lesson) => $this->key(EntityType::Lesson, $lesson, $lesson->slug));
        $skills->each(fn (Skill $skill) => $this->key(EntityType::Skill, $skill, $skill->slug));
        $resources->each(fn (ExternalResource $resource) => $this->key(EntityType::Resource, $resource, Str::limit(Str::slug($resource->title), 60, '') ?: 'recurso'));

        $entities = [$this->roadmap($roadmap)];

        foreach ($tracks as $track) {
            $trackDir = $this->directory('tracks', EntityType::Track, $track, 'track.md');
            $entities[] = $this->track($track, "{$trackDir}/track.md");

            foreach ($track->modules as $module) {
                $moduleDir = $this->directory($trackDir, EntityType::Module, $module, 'module.md');
                $entities[] = $this->module($module, "{$moduleDir}/module.md");

                foreach ($module->lessons as $lesson) {
                    $entities[] = $this->lesson($lesson, $moduleDir);
                }
            }
        }

        foreach ($skills as $skill) {
            $key = $this->keyOf(EntityType::Skill, $skill);
            $entities[] = $this->skill($skill, $this->files[EntityType::Skill->value][$key] ?? "skills/{$key}.md");
        }

        array_push($entities, ...$this->resources($resources, $tracks));

        return $entities;
    }

    /**
     * Lessons whose unpublished changes are not in the export.
     *
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * Images of the exported content, by their path in the package.
     *
     * @return array<string, MediaAsset>
     */
    public function media(): array
    {
        return $this->media->assets();
    }

    /**
     * Documents whose Markdown would not import back to the same content.
     *
     * @return list<string>
     */
    public function unfaithful(): array
    {
        return $this->unfaithful;
    }

    private function remember(string $package, ?ContentPackage $existing): void
    {
        foreach (ContentImportRecord::query()->where('package', $package)->get() as $record) {
            [$type, $key] = explode(':', $record->key, 2);
            $this->keys[$type][$record->importable_id] = $key;
            $this->taken[$type][$key] = true;
        }

        foreach (EntityType::cases() as $type) {
            foreach ($existing?->all($type) ?? [] as $entity) {
                $this->taken[$type->value][$entity->key] = true;
                $this->files[$type->value][$entity->key] = (string) preg_replace('/#\d+$/', '', $entity->file);

                if ($type === EntityType::Resource) {
                    $this->resourceOrder[$entity->key] = $entity->position;
                }
            }
        }
    }

    /**
     * Skills and resources are shared: those another package imported stay
     * with it.
     *
     * @return Builder<ContentImportRecord>
     */
    private function foreignIds(string $morph, string $package): Builder
    {
        return ContentImportRecord::query()->select('importable_id')->where('importable_type', $morph)->where('package', '!=', $package);
    }

    private function key(EntityType $type, Model $model, string $fallback): string
    {
        $id = (int) $model->getKey();

        if (isset($this->keys[$type->value][$id])) {
            return $this->keys[$type->value][$id];
        }

        $key = $fallback;

        for ($n = 2; isset($this->taken[$type->value][$key]); $n++) {
            $key = "{$fallback}-{$n}";
        }

        $this->taken[$type->value][$key] = true;

        return $this->keys[$type->value][$id] = $key;
    }

    private function keyOf(EntityType $type, Model $model): string
    {
        return $this->keys[$type->value][(int) $model->getKey()];
    }

    /**
     * "NN-name" under $parent: the name it already has, or its slug.
     */
    private function directory(string $parent, EntityType $type, Track|Module $model, string $file): string
    {
        $existing = $this->files[$type->value][$this->keyOf($type, $model)] ?? null;
        $name = $existing === null ? $model->slug : $this->withoutPosition(basename(dirname($existing)));

        return "{$parent}/{$this->position($model->position)}-{$name}";
    }

    private function position(int $position): string
    {
        return sprintf('%02d', $position);
    }

    private function withoutPosition(string $name): string
    {
        return (string) preg_replace('/^\d{2}-/', '', $name);
    }

    private function roadmap(Roadmap $roadmap): ExportedEntity
    {
        return new ExportedEntity(EntityType::Roadmap, $this->keyOf(EntityType::Roadmap, $roadmap), $roadmap, 'roadmap.yaml', [
            'key' => $this->keyOf(EntityType::Roadmap, $roadmap),
            'slug' => $roadmap->slug,
            'title' => $roadmap->title,
            'locale' => $roadmap->locale,
            'unlock_policy' => $roadmap->unlock_policy->value,
            'mastery_threshold' => $roadmap->mastery_threshold,
            'status' => $roadmap->status->value,
            'summary' => $roadmap->summary,
        ], $this->richText($roadmap->description, "el roadmap «{$roadmap->title}»"));
    }

    private function track(Track $track, string $file): ExportedEntity
    {
        return new ExportedEntity(EntityType::Track, $this->keyOf(EntityType::Track, $track), $track, $file, [
            'key' => $this->keyOf(EntityType::Track, $track),
            'slug' => $track->slug,
            'title' => $track->title,
            'icon' => $track->icon,
            'difficulty' => $track->difficulty->value,
            'estimated_hours' => $track->estimated_hours,
            'review_interval_months' => $track->review_interval_months,
            'status' => $track->status->value,
            'summary' => $track->summary,
            'why_it_matters' => $track->why_it_matters,
            'depends_on' => $this->sorted($track->prerequisites->map(fn (Track $prerequisite) => [
                'track' => $this->keyOf(EntityType::Track, $prerequisite),
                'kind' => $this->pivot($prerequisite, TrackDependency::class)->kind->value,
                'min_progress' => $this->pivot($prerequisite, TrackDependency::class)->min_progress,
            ])->all()),
        ], $this->richText($track->description, "el track «{$track->title}»") ?? '');
    }

    private function module(Module $module, string $file): ExportedEntity
    {
        return new ExportedEntity(EntityType::Module, $this->keyOf(EntityType::Module, $module), $module, $file, [
            'key' => $this->keyOf(EntityType::Module, $module),
            'slug' => $module->slug,
            'title' => $module->title,
            'status' => $module->status->value,
            'summary' => $module->summary,
        ], '');
    }

    private function lesson(Lesson $lesson, string $moduleDir): ExportedEntity
    {
        $key = $this->keyOf(EntityType::Lesson, $lesson);
        $existing = $this->files[EntityType::Lesson->value][$key] ?? null;
        $name = $existing === null ? $lesson->slug : $this->withoutPosition(basename($existing, '.md'));
        $version = $lesson->publishedVersion;
        $source = $version ?? $lesson;

        if ($version !== null && $lesson->hasUnpublishedChanges()) {
            $this->warnings[] = "«{$lesson->title}» tiene cambios sin publicar: se exporta la versión {$version->version}, la publicada. Los cambios siguen en la base de datos.";
        }

        return new ExportedEntity(EntityType::Lesson, $key, $lesson, "{$moduleDir}/{$this->position($lesson->position)}-{$name}.md", [
            'key' => $key,
            'slug' => $lesson->slug,
            'title' => $source->title,
            'type' => $source->content_type->value,
            'difficulty' => $source->difficulty->value,
            'estimated_minutes' => $source->estimated_minutes,
            // Visible means PUBLISHED in the package: importing publishes it again.
            'status' => match (true) {
                $lesson->status === ContentStatus::Archived => ContentStatus::Archived->value,
                $version !== null => ContentStatus::Published->value,
                default => $lesson->status->value,
            },
            'last_reviewed' => $lesson->last_reviewed_at?->format('Y-m-d'),
            'summary' => $source->summary,
            'why_it_matters' => $source->why_it_matters,
            'objectives' => $source->learning_objectives,
            'skills' => $this->sorted($lesson->skills->map(fn (Skill $skill) => [
                'key' => $this->keyOf(EntityType::Skill, $skill),
                'weight' => (int) $skill->getRelation('pivot')->getAttribute('weight'),
            ])->all()),
            'depends_on' => $this->sorted($lesson->prerequisites->map(fn (Lesson $prerequisite) => [
                'lesson' => $this->keyOf(EntityType::Lesson, $prerequisite),
                'kind' => $this->pivot($prerequisite, LessonDependency::class)->kind->value,
            ])->all()),
            'resources' => $lesson->resources
                ->map(fn (ExternalResource $resource) => $this->keys[EntityType::Resource->value][$resource->id] ?? null)
                ->filter()->values()->all(),
        ], $this->richText($source->body, "la lección «{$source->title}»") ?? '');
    }

    private function skill(Skill $skill, string $file): ExportedEntity
    {
        $key = $this->keyOf(EntityType::Skill, $skill);

        return new ExportedEntity(EntityType::Skill, $key, $skill, $file, [
            'key' => $key,
            'slug' => $skill->slug === $key ? null : $skill->slug,
            'name' => $skill->name,
            'icon' => $skill->icon,
            'difficulty' => $skill->difficulty->value,
            'status' => $skill->status->value,
            'depends_on' => $this->sorted($skill->prerequisites->map(fn (Skill $prerequisite) => [
                'skill' => $this->keyOf(EntityType::Skill, $prerequisite),
                'kind' => $this->pivot($prerequisite, SkillDependency::class)->kind->value,
                'min_progress' => $this->pivot($prerequisite, SkillDependency::class)->min_progress,
            ])->all()),
        ], $skill->description."\n");
    }

    /**
     * Resources share files. One already in the package stays in its file
     * and place; a new one joins the resources of the first lesson that
     * uses it (or a file named after its track), and an unused one goes to
     * UNUSED_RESOURCES_FILE.
     *
     * @param  Collection<int, ExternalResource>  $resources
     * @param  Collection<int, Track>  $tracks
     * @return list<ExportedEntity>
     */
    private function resources(Collection $resources, Collection $tracks): array
    {
        $files = $this->files[EntityType::Resource->value] ?? [];
        $inScope = $resources->keyBy('id');

        foreach ($tracks as $track) {
            $fallback = 'resources/'.$this->withoutPosition(basename($this->directory('tracks', EntityType::Track, $track, 'track.md'))).'.yaml';

            foreach ($track->modules as $module) {
                foreach ($module->lessons as $lesson) {
                    $keys = $lesson->resources->filter(fn (ExternalResource $resource) => $inScope->has($resource->id))
                        ->map(fn (ExternalResource $resource) => $this->keyOf(EntityType::Resource, $resource));
                    $shared = $keys->map(fn (string $key) => $files[$key] ?? null)->filter()->first();

                    foreach ($keys as $key) {
                        $files[$key] ??= $shared ?? $fallback;
                    }
                }
            }
        }

        $entities = [];
        $new = 0;

        foreach ($resources as $resource) {
            $key = $this->keyOf(EntityType::Resource, $resource);
            $entities[] = new ExportedEntity(EntityType::Resource, $key, $resource, $files[$key] ?? self::UNUSED_RESOURCES_FILE, [
                'key' => $key,
                'title' => $resource->title,
                'url' => $resource->url,
                'type' => $resource->type->value,
                'provider' => $resource->provider,
                'is_official' => $resource->is_official,
                'difficulty' => $resource->difficulty?->value,
                'language' => $resource->language,
                'status' => $resource->status === ContentStatus::Published ? null : $resource->status->value,
                'description' => $resource->description,
            ], order: $this->resourceOrder[$key] ?? 100_000 + $new++);
        }

        return $entities;
    }

    private function richText(?RichContent $content, string $owner): ?string
    {
        if ($content === null || $content->isEmpty()) {
            return null;
        }

        $markdown = $this->markdown->convert($content, $this->media->pathOf(...));

        foreach ($content->mediaIds() as $id) {
            if (is_string($this->media->idOf($this->media->pathOf($id)))) {
                $this->unfaithful[] = "El contenido de {$owner} usa una imagen que no está guardada (id {$id}): quítala o vuelve a subirla en el CMS.";
            }
        }

        try {
            $faithful = $this->comparable($this->reader->convert($markdown, $this->media->idOf(...))->doc) === $this->comparable($content->doc);
        } catch (InvalidRichContent) {
            $faithful = false;
        }

        if (! $faithful) {
            $this->unfaithful[] = "El contenido de {$owner} usa algo que el Markdown del paquete no representa (por ejemplo, celdas de tabla combinadas).";
        }

        return $markdown;
    }

    /**
     * A document without what the editor adds and Markdown cannot carry
     * but means nothing: null attributes, default cell spans, mark order.
     */
    private function comparable(mixed $node): mixed
    {
        if (! is_array($node)) {
            return $node;
        }

        if (in_array($node['type'] ?? null, ['tableHeader', 'tableCell'], true)
            && ($node['attrs']['colspan'] ?? 1) === 1 && ($node['attrs']['rowspan'] ?? 1) === 1) {
            unset($node['attrs']['colspan'], $node['attrs']['rowspan'], $node['attrs']['colwidth']);
        }

        if (isset($node['marks']) && is_array($node['marks'])) {
            usort($node['marks'], fn (mixed $a, mixed $b) => strcmp((string) json_encode($a), (string) json_encode($b)));
        }

        $node = array_map($this->comparable(...), $node);

        if (! array_is_list($node)) {
            ksort($node);
            $node = array_filter($node, fn (mixed $value) => $value !== null && $value !== []);
        }

        return $node;
    }

    /**
     * Dependencies and skills are sets: a stable order keeps diffs quiet.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function sorted(array $items): array
    {
        usort($items, fn (array $a, array $b) => strcmp((string) reset($a), (string) reset($b)));

        return $items;
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $class
     * @return T
     */
    private function pivot(Model $related, string $class): Model
    {
        // Dependency relations name their pivot "dependency".
        $pivot = $related->getRelation('dependency');
        assert($pivot instanceof $class);

        return $pivot;
    }
}
