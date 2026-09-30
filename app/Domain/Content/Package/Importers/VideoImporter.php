<?php

namespace App\Domain\Content\Package\Importers;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportContext;
use App\Domain\Content\Package\SourceEntity;
use App\Domain\Content\Videos\VideoDuration;
use App\Domain\Content\Videos\YouTubeId;
use App\Enums\Difficulty;
use App\Enums\LinkStatus;
use App\Enums\VideoProvider;
use App\Jobs\VerifyVideoJob;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;

/**
 * Videos of the package (videos/*.yaml). A new or changed video is checked
 * with oEmbed once the import commits: until then learners do not see it.
 */
final class VideoImporter implements EntityImporter
{
    use PublishesByStatus;

    public function type(): EntityType
    {
        return EntityType::Video;
    }

    public function find(int $id): ?Model
    {
        return Video::query()->find($id);
    }

    public function fill(SourceEntity $entity, ?Model $model, ImportContext $context): Model
    {
        $id = (string) YouTubeId::parse($entity->string('url'));
        // The same video added in the CMS before this package: adopted, not duplicated.
        $video = $model instanceof Video ? $model
            : (Video::query()->where('provider', VideoProvider::Youtube->value)->where('external_id', $id)->first() ?? new Video);

        $video->fill([
            'provider' => VideoProvider::Youtube,
            'external_id' => $id,
            'title' => $entity->string('title'),
            'instructor' => $entity->nullableString('instructor'),
            'description' => $entity->nullableString('description'),
            'duration_seconds' => VideoDuration::parse($entity->nullableString('duration')),
            'difficulty' => ($difficulty = $entity->nullableString('difficulty')) === null ? null : Difficulty::from($difficulty),
            'language' => $entity->string('language', 'en'),
            ...$this->statusAttributes($entity, $model),
        ]);

        $changed = $video->isDirty('external_id');

        if ($changed) {
            $video->fill(['link_status' => LinkStatus::Unchecked, 'last_checked_at' => null, 'last_http_status' => null, 'thumbnail_url' => null]);
        }

        $video->save();

        if ($changed && ! $context->report->dryRun) {
            VerifyVideoJob::dispatch($video)->afterCommit();
        }

        return $video;
    }

    public function syncRelations(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function finalize(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function state(Model $model): array
    {
        assert($model instanceof Video);

        return [
            $model->external_id, $model->title, $model->instructor, $model->description, $model->duration_seconds,
            $model->difficulty?->value, $model->language, $model->status->value,
        ];
    }
}
