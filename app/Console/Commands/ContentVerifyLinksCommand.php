<?php

namespace App\Console\Commands;

use App\Domain\Content\Links\LinkChecker;
use App\Domain\Content\Package\ContentPackage;
use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\PackageMedia;
use App\Domain\Content\Package\PackageReader;
use App\Domain\Content\RichContent\InvalidRichContent;
use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;
use App\Domain\Content\Videos\YouTubeId;
use App\Domain\Content\Videos\YouTubeOEmbed;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:verify-links
    {path=content/ai-engineer : Directorio del paquete de contenido}
    {--timeout=15 : Segundos máximos por solicitud}')]
#[Description('Verifica que las URLs de los recursos y los videos del paquete existan (falla ante 404/410 o un video que no se puede ver)')]
class ContentVerifyLinksCommand extends Command
{
    public function handle(PackageReader $reader, MarkdownToRichContent $markdown): int
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

        // Videos by oEmbed: those of videos/*.yaml and those written in the text.
        $oembed = new YouTubeOEmbed((int) $this->option('timeout'));

        foreach ($this->videoIds($package, $markdown) as $id => $where) {
            $result = $oembed->lookup($id);

            $broken += $result->isBroken() ? 1 : 0;
            $inconclusive += $result->status === null ? 1 : 0;

            $rows[] = [
                $where,
                $result->status->value ?? 'NO CONCLUYENTE',
                $result->httpStatus ?? '-',
                "YouTube {$id}".($result->reason === null ? '' : ": {$result->reason}"),
            ];
        }

        $this->table(['Recurso o video', 'Estado', 'HTTP', 'Detalle'], $rows);

        if ($inconclusive > 0) {
            $this->warn("{$inconclusive} verificación(es) no concluyente(s): revisar manualmente si persisten.");
        }

        if ($broken > 0) {
            $this->error("{$broken} enlace(s) roto(s) (404/410) o video(s) que no se pueden ver.");

            return self::FAILURE;
        }

        $verified = count($rows) - $inconclusive;
        $this->info("{$verified} enlace(s) verificados, {$inconclusive} no concluyente(s), ninguno roto.");

        return self::SUCCESS;
    }

    /**
     * Every YouTube ID the package uses, once, with where it was found.
     *
     * @return array<string, string>
     */
    private function videoIds(ContentPackage $package, MarkdownToRichContent $markdown): array
    {
        $ids = [];

        foreach ($package->all(EntityType::Video) as $video) {
            $id = YouTubeId::parse($video->string('url'));

            if ($id !== null) {
                $ids[$id] ??= "video {$video->key}";
            }
        }

        foreach ([EntityType::Roadmap, EntityType::Track, EntityType::Lesson] as $type) {
            foreach ($package->all($type) as $entity) {
                try {
                    $doc = $markdown->convert((string) $entity->body, PackageMedia::comparable())->doc;
                } catch (InvalidRichContent) {
                    continue; // content:validate reports it.
                }

                foreach ($this->inlineVideos($doc) as $id) {
                    $ids[$id] ??= "texto de {$entity->file}";
                }
            }
        }

        return $ids;
    }

    /**
     * @param  array<mixed>  $node
     * @return list<string>
     */
    private function inlineVideos(array $node): array
    {
        $ids = ($node['type'] ?? null) === 'video' && is_string($node['attrs']['videoId'] ?? null) ? [$node['attrs']['videoId']] : [];

        foreach (is_array($node['content'] ?? null) ? $node['content'] : [] as $child) {
            if (is_array($child)) {
                array_push($ids, ...$this->inlineVideos($child));
            }
        }

        return $ids;
    }
}
