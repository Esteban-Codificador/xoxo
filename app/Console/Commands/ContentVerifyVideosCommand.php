<?php

namespace App\Console\Commands;

use App\Domain\Content\Videos\VerifyVideo;
use App\Domain\Content\Videos\YouTubeOEmbed;
use App\Enums\ContentStatus;
use App\Models\Video;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:verify-videos
    {--timeout=10 : Segundos máximos por solicitud}')]
#[Description('Comprueba con oEmbed que los videos del catálogo siguen disponibles y registra el resultado')]
class ContentVerifyVideosCommand extends Command
{
    public function handle(): int
    {
        $verify = new VerifyVideo(new YouTubeOEmbed((int) $this->option('timeout')));
        $rows = [];
        $counts = ['broken' => 0, 'inconclusive' => 0];

        Video::query()
            ->where('status', '!=', ContentStatus::Archived->value)
            ->orderBy('id')
            ->each(function (Video $video) use ($verify, &$rows, &$counts): void {
                $result = $verify->handle($video);

                $counts['broken'] += $result->isBroken() ? 1 : 0;
                $counts['inconclusive'] += $result->status === null ? 1 : 0;
                $rows[] = [$video->id, $video->title, $video->external_id, $result->status->value ?? 'NO CONCLUYENTE', $result->reason ?? '-'];
            });

        $this->table(['ID', 'Video', 'YouTube', 'Estado', 'Motivo'], $rows);
        $this->info(sprintf(
            '%d video(s) comprobados: %d no disponible(s), %d no concluyente(s).',
            count($rows), $counts['broken'], $counts['inconclusive'],
        ));

        // An unavailable video is content work, not a failed run: the CMS shows it.
        return self::SUCCESS;
    }
}
