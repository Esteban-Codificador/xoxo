<?php

namespace App\Console\Commands;

use App\Domain\Content\Links\LinkChecker;
use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\PackageReader;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:verify-links
    {path=content/ai-engineer : Directorio del paquete de contenido}
    {--timeout=15 : Segundos máximos por solicitud}')]
#[Description('Verifica que las URLs de los recursos del paquete existan (falla ante 404/410)')]
class ContentVerifyLinksCommand extends Command
{
    public function handle(PackageReader $reader): int
    {
        $path = (string) $this->argument('path');
        $package = $reader->read(str_starts_with($path, '/') ? $path : base_path($path));
        $checker = new LinkChecker((int) $this->option('timeout'));

        $rows = [];
        $broken = 0;
        $inconclusive = 0;

        foreach ($package->all(EntityType::Resource) as $resource) {
            $result = $checker->check($resource->string('url'));

            $broken += $result->isBroken() ? 1 : 0;
            $inconclusive += $result->status === null ? 1 : 0;

            $rows[] = [
                $resource->key,
                $result->status->value ?? 'NO CONCLUYENTE',
                $result->httpStatus ?? '-',
                $result->error ?? ($result->finalUrl !== null && rtrim($result->finalUrl, '/') !== rtrim($result->url, '/') ? "→ {$result->finalUrl}" : $result->url),
            ];
        }

        $this->table(['Recurso', 'Estado', 'HTTP', 'Detalle'], $rows);

        if ($inconclusive > 0) {
            $this->warn("{$inconclusive} verificación(es) no concluyente(s): revisar manualmente si persisten.");
        }

        if ($broken > 0) {
            $this->error("{$broken} enlace(s) roto(s) (404/410).");

            return self::FAILURE;
        }

        $verified = count($rows) - $inconclusive;
        $this->info("{$verified} enlace(s) verificados, {$inconclusive} no concluyente(s), ninguno roto.");

        return self::SUCCESS;
    }
}
