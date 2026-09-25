<?php

namespace App\Domain\Content\Package;

use App\Domain\Audit\AuditLogger;
use App\Domain\Content\Package\Importers\EntityImporter;
use App\Domain\Content\Package\Importers\LessonImporter;
use App\Domain\Content\Package\Importers\ModuleImporter;
use App\Domain\Content\Package\Importers\ResourceImporter;
use App\Domain\Content\Package\Importers\RoadmapImporter;
use App\Domain\Content\Package\Importers\SkillImporter;
use App\Domain\Content\Package\Importers\TrackImporter;
use App\Enums\AuditAction;
use App\Models\ContentImportRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Idempotent import of a content package (docs/content-architecture.md §7).
 *
 * The database is the source of truth: an entity edited in the CMS after
 * the last import is left untouched unless --force is used. A dry run
 * performs the whole import inside a transaction and rolls it back, so its
 * report is exact.
 */
final readonly class PackageImporter
{
    /** @var list<EntityImporter> */
    private array $importers;

    public function __construct(
        private PackageValidator $validator,
        private AuditLogger $audit,
        RoadmapImporter $roadmaps,
        SkillImporter $skills,
        ResourceImporter $resources,
        TrackImporter $tracks,
        ModuleImporter $modules,
        LessonImporter $lessons,
    ) {
        // Order matters: every importer references only entities imported before it.
        $this->importers = [$roadmaps, $skills, $resources, $tracks, $modules, $lessons];
    }

    /**
     * @throws InvalidContentPackage
     */
    public function import(ContentPackage $package, bool $force = false, bool $dryRun = false): ImportReport
    {
        $issues = $this->validator->validate($package);

        if ($issues !== []) {
            throw new InvalidContentPackage($issues);
        }

        $report = new ImportReport($dryRun);

        DB::beginTransaction();

        try {
            $this->audit->during(AuditAction::Imported, fn () => $this->run($package, new ImportContext($package, $report), $force));
            $dryRun ? DB::rollBack() : DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        return $report;
    }

    private function run(ContentPackage $package, ImportContext $context, bool $force): void
    {
        foreach ($this->importers as $importer) {
            /** @var list<array{0: SourceEntity, 1: Model}> $pending */
            $pending = [];

            foreach ($package->all($importer->type()) as $entity) {
                $record = ContentImportRecord::query()->where('package', $package->name)->where('key', $entity->recordKey())->first();
                $model = $record === null ? null : $importer->find($record->importable_id);

                if ($record !== null && $model === null && ! $force) {
                    $context->report->record($entity, ImportOutcome::SkippedDeleted);

                    continue;
                }

                if ($record !== null && $model !== null) {
                    $context->remember($entity, $model);

                    if (! $force && $record->source_hash === $entity->hash()) {
                        $context->report->record($entity, ImportOutcome::Unchanged);

                        continue;
                    }

                    if (! $force && $record->entity_hash !== $this->stateHash($importer, $model)) {
                        $context->report->record($entity, ImportOutcome::SkippedModified);

                        continue;
                    }
                }

                $outcome = $model === null ? ImportOutcome::Created : ImportOutcome::Updated;
                $model = $importer->fill($entity, $model, $context);
                $context->remember($entity, $model);
                $context->report->record($entity, $outcome);
                $pending[] = [$entity, $model];
            }

            foreach ($pending as [$entity, $model]) {
                $importer->syncRelations($entity, $model, $context);
            }

            foreach ($pending as [$entity, $model]) {
                $importer->finalize($entity, $model, $context);

                ContentImportRecord::query()->updateOrCreate(
                    ['package' => $package->name, 'key' => $entity->recordKey()],
                    [
                        'importable_type' => $model->getMorphClass(),
                        'importable_id' => $model->getKey(),
                        'source_hash' => $entity->hash(),
                        'entity_hash' => $this->stateHash($importer, $model->refresh()),
                        'imported_at' => now(),
                    ],
                );
            }
        }
    }

    private function stateHash(EntityImporter $importer, Model $model): string
    {
        return hash('sha256', (string) json_encode($importer->state($model), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
