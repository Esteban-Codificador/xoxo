<?php

namespace App\Console\Commands;

use App\Domain\Content\Package\Export\ExportRefused;
use App\Domain\Content\Package\Export\FileChangeKind;
use App\Domain\Content\Package\Export\PackageExporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:export
    {path=content/ai-engineer : Directorio del paquete de contenido}
    {--roadmap= : Slug del roadmap (si hay más de uno)}
    {--dry-run : Muestra qué archivos cambiarían sin escribir nada}
    {--force : Sobrescribe también archivos editados a mano que no se importaron}
    {--copy : Escribe una copia (backup o revisión) sin marcar la base de datos como sincronizada con ella}')]
#[Description('Exporta el contenido de la base de datos al paquete de contenido, con el mismo formato que lee content:import')]
class ContentExportCommand extends Command
{
    public function handle(PackageExporter $exporter): int
    {
        try {
            $plan = $exporter->plan(
                $this->packagePath(),
                copy: (bool) $this->option('copy'),
                force: (bool) $this->option('force'),
                roadmapSlug: $this->option('roadmap') === null ? null : (string) $this->option('roadmap'),
            );
        } catch (ExportRefused $exception) {
            foreach ($exception->reasons as $reason) {
                $this->error($reason);
            }

            $this->error($exception->getMessage().' No se exportó nada.');

            return self::FAILURE;
        }

        if ($plan->conflicts !== []) {
            foreach ($plan->conflicts as $conflict) {
                $this->error($conflict);
            }

            $this->error('Hay archivos que el export sobrescribiría con cambios que no están en la base de datos. No se exportó nada.');

            return self::FAILURE;
        }

        foreach ($plan->pending() as $change) {
            $this->line(sprintf('  %-11s %s', $change->kind->label(), $change->path));
        }

        $this->line(sprintf(
            'Archivos: %d nuevos, %d modificados, %d eliminados, %d sin cambios.',
            $plan->count(FileChangeKind::Created),
            $plan->count(FileChangeKind::Updated),
            $plan->count(FileChangeKind::Deleted),
            $plan->count(FileChangeKind::Unchanged),
        ));

        foreach ($plan->warnings as $warning) {
            $this->warn($warning);
        }

        if ($this->option('dry-run')) {
            $this->info('Simulación: no se escribió nada.');

            return self::SUCCESS;
        }

        $exporter->apply($plan);

        $this->info($plan->copy
            ? "Copia escrita en {$plan->path}."
            : "Exportado a {$plan->path}. La base de datos y el paquete quedan sincronizados: revisa el diff y haz commit.");

        return self::SUCCESS;
    }

    private function packagePath(): string
    {
        $path = (string) $this->argument('path');

        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}
