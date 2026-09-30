<?php

namespace App\Domain\Content\Package\Export;

use App\Domain\Content\Package\ContentPackage;
use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\Importers\EntityImporter;
use App\Domain\Content\Package\Importers\LessonImporter;
use App\Domain\Content\Package\Importers\ModuleImporter;
use App\Domain\Content\Package\Importers\ResourceImporter;
use App\Domain\Content\Package\Importers\RoadmapImporter;
use App\Domain\Content\Package\Importers\SkillImporter;
use App\Domain\Content\Package\Importers\TrackImporter;
use App\Domain\Content\Package\PackageIssue;
use App\Domain\Content\Package\PackageReader;
use App\Domain\Content\Package\PackageValidator;
use App\Domain\Content\Package\SourceEntity;
use App\Domain\Content\Package\SyncRecords;
use App\Models\ContentImportRecord;
use App\Models\Roadmap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Database → content package (docs/content-architecture.md §7, ADR-031).
 *
 * plan() computes the whole export without touching the target: files are
 * rendered, read back and validated in a temporary directory, so what gets
 * written always imports. An entity that did not change keeps its file
 * byte for byte; only what changed in the CMS is rewritten.
 *
 * Unless it is a copy (--copy), an export is a sync point like an import:
 * the sync records take the new hashes, so importing right after changes
 * nothing. It refuses to overwrite a file edited by hand that was never
 * imported, the mirror of the importer skipping rows edited in the CMS.
 */
final readonly class PackageExporter
{
    public function __construct(
        private PackageReader $reader,
        private PackageValidator $validator,
        private DatabaseSnapshot $snapshot,
        private ExportFormat $format,
        private FileMerger $merger,
        private SyncRecords $records,
        private RoadmapImporter $roadmaps,
        private SkillImporter $skills,
        private ResourceImporter $resources,
        private TrackImporter $tracks,
        private ModuleImporter $modules,
        private LessonImporter $lessons,
    ) {}

    /**
     * @throws ExportRefused
     */
    public function plan(string $path, bool $copy = false, bool $force = false, ?string $roadmapSlug = null): ExportPlan
    {
        $path = rtrim($path, '/');
        $roadmap = $this->roadmap($roadmapSlug);
        $package = ContentImportRecord::query()
            ->where('importable_type', $roadmap->getMorphClass())
            ->where('importable_id', $roadmap->id)
            ->value('package') ?? basename($path);
        $existing = $this->existing($path);

        if (! $copy) {
            $this->guardSyncTarget($path, $package, $existing);
        }

        $entities = $this->snapshot->take($roadmap, $package, $existing);

        if ($this->snapshot->unfaithful() !== []) {
            throw new ExportRefused('El paquete no puede representar fielmente parte del contenido; ajústalo en el CMS y vuelve a exportar.', $this->snapshot->unfaithful());
        }

        [$files, $result] = $this->files($entities, $existing, $package);

        $byKey = [];
        foreach ($entities as $entity) {
            $byKey[$entity->type->value][$entity->key] = $entity;
        }

        return new ExportPlan(
            path: $path,
            package: $package,
            copy: $copy,
            changes: $this->changes($path, $files, $existing),
            result: $result,
            entities: $byKey,
            warnings: $this->snapshot->warnings(),
            conflicts: $copy || $force ? [] : $this->conflicts($package, $existing, $result),
            forgottenKeys: $copy ? [] : $this->forgottenKeys($existing, $result),
        );
    }

    public function apply(ExportPlan $plan): void
    {
        foreach ($plan->pending() as $change) {
            $target = "{$plan->path}/{$change->path}";

            if ($change->contents === null) {
                File::delete($target);

                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            File::put($target, $change->contents);
        }

        $this->removeEmptyDirectories("{$plan->path}/tracks");

        if (is_dir("{$plan->path}/media") && File::isEmptyDirectory("{$plan->path}/media")) {
            File::deleteDirectory("{$plan->path}/media");
        }

        if ($plan->copy) {
            return;
        }

        DB::transaction(function () use ($plan): void {
            foreach (EntityType::cases() as $type) {
                foreach ($plan->result->all($type) as $entity) {
                    $this->records->save($plan->package, $entity, $plan->entities[$type->value][$entity->key]->model, $this->importer($type));
                }
            }

            ContentImportRecord::query()->where('package', $plan->package)->whereIn('key', $plan->forgottenKeys)->delete();
        });
    }

    /**
     * The importer of each type knows the state its sync record hashes.
     */
    private function importer(EntityType $type): EntityImporter
    {
        return match ($type) {
            EntityType::Roadmap => $this->roadmaps,
            EntityType::Skill => $this->skills,
            EntityType::Resource => $this->resources,
            EntityType::Track => $this->tracks,
            EntityType::Module => $this->modules,
            EntityType::Lesson => $this->lessons,
        };
    }

    private function roadmap(?string $slug): Roadmap
    {
        if ($slug !== null) {
            return Roadmap::query()->where('slug', $slug)->first()
                ?? throw new ExportRefused("No existe el roadmap \"{$slug}\".");
        }

        $roadmaps = Roadmap::query()->limit(2)->get();

        return $roadmaps->count() === 1
            ? $roadmaps->first()
            : throw new ExportRefused($roadmaps->isEmpty() ? 'No hay ningún roadmap que exportar.' : 'Hay varios roadmaps: indica cuál con --roadmap.');
    }

    private function existing(string $path): ?ContentPackage
    {
        if (file_exists($path) && ! is_dir($path)) {
            throw new ExportRefused("{$path} no es un directorio.");
        }

        if (! is_file("{$path}/roadmap.yaml")) {
            if (is_dir($path) && (scandir($path) ?: []) !== ['.', '..']) {
                throw new ExportRefused("{$path} no está vacío y no es un paquete de contenido (falta roadmap.yaml).");
            }

            return null;
        }

        $existing = $this->reader->read($path);

        if ($existing->readIssues() !== []) {
            throw new ExportRefused('El paquete de destino tiene errores de estructura; corrígelos antes de exportar sobre él.', $this->messages($existing->readIssues()));
        }

        return $existing;
    }

    /**
     * The sync point is the package itself: its directory has the package
     * name, and once the database is in sync with a package, a fresh
     * directory does not silently become the new reference.
     */
    private function guardSyncTarget(string $path, string $package, ?ContentPackage $existing): void
    {
        if (basename($path) !== $package) {
            throw new ExportRefused("El roadmap pertenece al paquete \"{$package}\": para sincronizarlo, exporta a un directorio con ese nombre. Para una copia en otro lugar, usa --copy.");
        }

        if ($existing === null && ContentImportRecord::query()->where('package', $package)->exists()) {
            throw new ExportRefused("La base de datos ya está sincronizada con un paquete \"{$package}\": exporta sobre ese paquete o usa --copy para una copia.");
        }
    }

    /**
     * The files of the export and the package they form (validated). An
     * existing file keeps its text wherever its meaning did not change.
     *
     * @param  list<ExportedEntity>  $entities
     * @return array{0: array<string, string>, 1: ContentPackage}
     *
     * @throws ExportRefused
     */
    private function files(array $entities, ?ContentPackage $existing, string $package): array
    {
        $temp = sys_get_temp_dir().'/content-export-'.Str::random(12);
        $root = "{$temp}/{$package}";

        try {
            $canonicalFiles = [...$this->render($entities), ...$this->mediaFiles()];
            $canonical = $this->write($root, $canonicalFiles);
            $files = $canonicalFiles;
            $result = $canonical;

            if ($existing !== null) {
                $files = [...$files, ...$this->preserved($entities, $existing, $canonical)];
                $result = $this->write($root, $files);

                // A preserved file must mean exactly what its canonical form means.
                $differing = $this->differing($canonical, $result);

                if ($differing !== []) {
                    foreach ($differing as $file) {
                        $files[$file] = $canonicalFiles[$file];
                    }

                    $result = $this->write($root, $files);
                }
            }

            $issues = $this->validator->validate($result);

            if ($issues !== []) {
                throw new ExportRefused('El paquete no puede representar el estado actual de la base de datos; corrige esto en el CMS y vuelve a exportar.', $this->messages($issues));
            }

            ksort($files);

            return [$files, $result];
        } finally {
            File::deleteDirectory($temp);
        }
    }

    /**
     * @param  list<ExportedEntity>  $entities
     * @return array<string, string>
     */
    private function render(array $entities): array
    {
        $files = [];

        foreach ($entities as $entity) {
            if ($entity->type !== EntityType::Resource) {
                if (isset($files[$entity->file])) {
                    throw new ExportRefused("Dos entidades irían al mismo archivo ({$entity->file}): revisa sus posiciones en el CMS.");
                }

                $files[$entity->file] = $entity->type === EntityType::Roadmap
                    ? $this->format->roadmapFile($entity->data, $entity->body)
                    : $this->format->markdownFile($entity->data, (string) $entity->body);
            }
        }

        foreach ($this->resourceGroups($entities) as $file => $items) {
            $files[$file] = $this->format->resourcesFile(array_map(fn (ExportedEntity $item) => $item->data, $items));
        }

        return $files;
    }

    /**
     * The stored images the exported content shows, byte for byte: a file
     * named after its checksum only changes when the image does.
     *
     * @return array<string, string>
     */
    private function mediaFiles(): array
    {
        $files = [];

        foreach ($this->snapshot->media() as $path => $asset) {
            $bytes = Storage::disk($asset->disk)->get($asset->path);

            if ($bytes === null) {
                throw new ExportRefused("Falta el archivo de la imagen {$asset->id} ({$asset->disk}:{$asset->path}); vuelve a subirla en el CMS.");
            }

            $files[$path] = $bytes;
        }

        return $files;
    }

    /**
     * @param  list<ExportedEntity>  $entities
     * @return array<string, list<ExportedEntity>> In file order.
     */
    private function resourceGroups(array $entities): array
    {
        $groups = [];

        foreach ($entities as $entity) {
            if ($entity->type === EntityType::Resource) {
                $groups[$entity->file][] = $entity;
            }
        }

        foreach ($groups as &$items) {
            usort($items, fn (ExportedEntity $a, ExportedEntity $b) => $a->order <=> $b->order);
        }

        return $groups;
    }

    /**
     * @param  array<string, string>  $files
     */
    private function write(string $root, array $files): ContentPackage
    {
        File::deleteDirectory($root);

        foreach ($files as $relative => $contents) {
            File::ensureDirectoryExists(dirname("{$root}/{$relative}"));
            File::put("{$root}/{$relative}", $contents);
        }

        return $this->reader->read($root);
    }

    /**
     * Existing text for every file: whole when its entities did not change,
     * merged field by field and block by block when they did. A file moves
     * with its entity when the position changed.
     *
     * @param  list<ExportedEntity>  $entities
     * @return array<string, string>
     */
    private function preserved(array $entities, ContentPackage $existing, ContentPackage $canonical): array
    {
        $files = [];

        foreach ($entities as $entity) {
            if ($entity->type === EntityType::Resource) {
                continue;
            }

            $old = $existing->find($entity->type, $entity->key);
            $new = $canonical->find($entity->type, $entity->key);

            if ($old === null || $new === null) {
                continue;
            }

            $text = (string) file_get_contents("{$existing->path}/{$old->file}");
            $merged = match (true) {
                $this->same($old, $new) => $text,
                $entity->type === EntityType::Roadmap => $this->merger->roadmapFile($text, $entity->data, $entity->body),
                default => $this->merger->markdownFile($text, $entity->data, (string) $entity->body),
            };

            if ($merged !== null) {
                $files[$entity->file] = $merged;
            }
        }

        $old = $this->byFile($existing->all(EntityType::Resource));
        $new = $this->byFile($canonical->all(EntityType::Resource));

        foreach ($this->resourceGroups($entities) as $file => $items) {
            if (! isset($old[$file])) {
                continue;
            }

            $text = (string) file_get_contents("{$existing->path}/{$file}");
            $unchanged = array_keys($old[$file]) === array_keys($new[$file] ?? [])
                && array_filter($new[$file], fn (SourceEntity $item) => ! $this->same($old[$file][$item->key], $item)) === [];
            $files[$file] = $unchanged ? $text : $this->merger->resourcesFile($text, array_map(fn (ExportedEntity $item) => $item->data, $items));
        }

        return $files;
    }

    /**
     * Files of $result whose entities do not mean what the canonical ones do.
     *
     * @return list<string>
     */
    private function differing(ContentPackage $canonical, ContentPackage $result): array
    {
        $files = [];

        foreach (EntityType::cases() as $type) {
            foreach ($canonical->all($type) as $entity) {
                $other = $result->find($type, $entity->key);

                if ($other === null || $other->file !== $entity->file || ! $this->same($entity, $other)) {
                    $files[] = (string) preg_replace('/#\d+$/', '', $entity->file);
                }
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * @param  array<string, SourceEntity>  $resources
     * @return array<string, array<string, SourceEntity>> Resources by file, in file order.
     */
    private function byFile(array $resources): array
    {
        $files = [];

        foreach ($resources as $resource) {
            $files[(string) preg_replace('/#\d+$/', '', $resource->file)][$resource->key] = $resource;
        }

        foreach ($files as &$items) {
            uasort($items, fn (SourceEntity $a, SourceEntity $b) => $a->position <=> $b->position);
        }

        return $files;
    }

    private function same(SourceEntity $a, SourceEntity $b): bool
    {
        return $this->format->comparable($a) == $this->format->comparable($b);
    }

    /**
     * @param  array<string, string>  $files
     * @return list<FileChange>
     */
    private function changes(string $path, array $files, ?ContentPackage $existing): array
    {
        $changes = [];

        foreach ($files as $relative => $contents) {
            $current = is_file("{$path}/{$relative}") ? file_get_contents("{$path}/{$relative}") : null;
            $kind = match (true) {
                $current === null => FileChangeKind::Created,
                $current === $contents => FileChangeKind::Unchanged,
                default => FileChangeKind::Updated,
            };
            $changes[] = new FileChange($relative, $kind, $contents);
        }

        foreach ($this->packageFiles($existing) as $relative) {
            if (! isset($files[$relative])) {
                $changes[] = new FileChange($relative, FileChangeKind::Deleted, null);
            }
        }

        usort($changes, fn (FileChange $a, FileChange $b) => strcmp($a->path, $b->path));

        return $changes;
    }

    /**
     * @return list<string>
     */
    private function packageFiles(?ContentPackage $existing): array
    {
        $files = [];

        foreach (EntityType::cases() as $type) {
            foreach ($existing?->all($type) ?? [] as $entity) {
                $files[(string) preg_replace('/#\d+$/', '', $entity->file)] = true;
            }
        }

        // An image nothing shows any more leaves the package.
        foreach ($existing?->media() ?? [] as $path) {
            $files[$path] = true;
        }

        return array_keys($files);
    }

    /**
     * Entities of the target that the export would change although their
     * file changed since the last sync: overwriting would lose that edit.
     *
     * @return list<string>
     */
    private function conflicts(string $package, ?ContentPackage $existing, ContentPackage $result): array
    {
        $conflicts = [];

        foreach (EntityType::cases() as $type) {
            foreach ($existing?->all($type) ?? [] as $old) {
                $new = $result->find($type, $old->key);

                if ($new !== null && $this->same($old, $new)) {
                    continue;
                }

                $record = $this->records->find($package, $old);

                if ($record === null) {
                    $conflicts[] = "{$old->file}: nunca se importó y el export lo borraría. Impórtalo antes con content:import o usa --force para descartarlo.";

                    continue;
                }

                if ($old->hash() === $record->source_hash) {
                    continue;
                }

                $importer = $this->importer($type);
                $model = $importer->find($record->importable_id);

                $conflicts[] = $model !== null && $record->entity_hash === $this->records->stateHash($importer, $model)
                    ? "{$old->file}: cambió en el archivo y no se importó; el export descartaría ese cambio. Impórtalo antes con content:import o usa --force para descartarlo."
                    : "{$old->file}: cambió en el archivo y en la base de datos desde la última sincronización. Decide cuál vale: content:import --force se queda con el archivo; content:export --force, con la base de datos.";
            }
        }

        return $conflicts;
    }

    /**
     * @return list<string>
     */
    private function forgottenKeys(?ContentPackage $existing, ContentPackage $result): array
    {
        $keys = [];

        foreach (EntityType::cases() as $type) {
            foreach ($existing?->all($type) ?? [] as $old) {
                if ($result->find($type, $old->key) === null) {
                    $keys[] = $old->recordKey();
                }
            }
        }

        return $keys;
    }

    private function removeEmptyDirectories(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (File::directories($directory) as $child) {
            $this->removeEmptyDirectories($child);

            if (File::isEmptyDirectory($child)) {
                File::deleteDirectory($child);
            }
        }
    }

    /**
     * @param  list<PackageIssue>  $issues
     * @return list<string>
     */
    private function messages(array $issues): array
    {
        return array_map(fn (PackageIssue $issue) => (string) $issue, $issues);
    }
}
