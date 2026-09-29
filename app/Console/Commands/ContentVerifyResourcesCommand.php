<?php

namespace App\Console\Commands;

use App\Domain\Content\Links\LinkChecker;
use App\Domain\Content\Links\VerifyResourceLink;
use App\Enums\ContentStatus;
use App\Models\ExternalResource;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:verify-resources
    {--timeout=15 : Segundos máximos por solicitud}')]
#[Description('Verifica las URLs de los recursos guardados (los del CMS incluidos) y registra su estado de enlace')]
class ContentVerifyResourcesCommand extends Command
{
    public function handle(): int
    {
        $verify = new VerifyResourceLink(new LinkChecker((int) $this->option('timeout')));
        $rows = [];
        $counts = ['broken' => 0, 'inconclusive' => 0];

        ExternalResource::query()
            ->where('status', '!=', ContentStatus::Archived->value)
            ->orderBy('id')
            ->each(function (ExternalResource $resource) use ($verify, &$rows, &$counts): void {
                $result = $verify->handle($resource);

                $counts['broken'] += $result->isBroken() ? 1 : 0;
                $counts['inconclusive'] += $result->status === null ? 1 : 0;
                $rows[] = [$resource->id, $resource->title, $result->status->value ?? 'NO CONCLUYENTE', $result->httpStatus ?? '-'];
            });

        $this->table(['ID', 'Recurso', 'Estado', 'HTTP'], $rows);
        $this->info(sprintf(
            '%d recurso(s) verificados: %d roto(s), %d no concluyente(s).',
            count($rows), $counts['broken'], $counts['inconclusive'],
        ));

        // A broken link is content work, not a failed run: the CMS shows it.
        return self::SUCCESS;
    }
}
