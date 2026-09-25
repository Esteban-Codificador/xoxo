<?php

namespace App\Console\Commands;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportOutcome;
use App\Domain\Content\Package\InvalidContentPackage;
use App\Domain\Content\Package\PackageImporter;
use App\Domain\Content\Package\PackageReader;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:import
    {path=content/ai-engineer : Directorio del paquete de contenido}
    {--dry-run : Muestra qué cambiaría sin guardar nada}
    {--force : Sobrescribe también lo editado o eliminado en el CMS}')]
#[Description('Importa un paquete de contenido a la base de datos de forma idempotente')]
class ContentImportCommand extends Command
{
    public function handle(PackageReader $reader, PackageImporter $importer): int
    {
        $package = $reader->read($this->packagePath());

        try {
            $report = $importer->import($package, (bool) $this->option('force'), (bool) $this->option('dry-run'));
        } catch (InvalidContentPackage $exception) {
            foreach ($exception->issues as $issue) {
                $this->error((string) $issue);
            }

            $this->error($exception->getMessage().' No se importó nada.');

            return self::FAILURE;
        }

        $this->table(
            ['Tipo', 'Creados', 'Actualizados', 'Sin cambios', 'Editados en CMS', 'Eliminados en CMS'],
            array_map(fn (EntityType $type) => [
                $type->value,
                $report->count($type, ImportOutcome::Created),
                $report->count($type, ImportOutcome::Updated),
                $report->count($type, ImportOutcome::Unchanged),
                $report->count($type, ImportOutcome::SkippedModified),
                $report->count($type, ImportOutcome::SkippedDeleted),
            ], EntityType::cases()),
        );

        foreach ($report->warnings() as $warning) {
            $this->warn($warning);
        }

        $this->info($report->dryRun ? 'Simulación: no se guardó ningún cambio.' : 'Importación completada.');

        return self::SUCCESS;
    }

    private function packagePath(): string
    {
        $path = (string) $this->argument('path');

        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}
