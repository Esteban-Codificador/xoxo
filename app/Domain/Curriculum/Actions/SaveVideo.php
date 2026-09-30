<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Content\Videos\VideoDuration;
use App\Domain\Content\Videos\YouTubeId;
use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\LinkStatus;
use App\Enums\VideoProvider;
use App\Jobs\VerifyVideoJob;
use App\Models\Video;

/**
 * Creates or updates a video of the catalog. A new or changed video is
 * unchecked, so learners do not see it, until oEmbed confirms it exists
 * and can be embedded (VerifyVideoJob, right after saving).
 */
final class SaveVideo
{
    /**
     * @param  array{url: string, title: string, instructor?: string|null, description?: string|null, duration?: string|null, difficulty?: string|null, language: string}  $data
     */
    public function handle(?Video $video, array $data): Video
    {
        $video ??= new Video(['provider' => VideoProvider::Youtube, 'status' => ContentStatus::Draft]);
        $id = (string) YouTubeId::parse($data['url']);
        $changed = $video->external_id !== $id;

        $video->forceFill([
            'external_id' => $id,
            'title' => trim($data['title']),
            'instructor' => $this->nullable($data['instructor'] ?? null),
            'description' => $this->nullable($data['description'] ?? null),
            'duration_seconds' => VideoDuration::parse($data['duration'] ?? null),
            'difficulty' => ($data['difficulty'] ?? null) === null ? null : Difficulty::from((string) $data['difficulty']),
            'language' => $data['language'],
        ]);

        if ($changed) {
            $video->forceFill(['link_status' => LinkStatus::Unchecked, 'last_http_status' => null, 'last_checked_at' => null, 'thumbnail_url' => null]);
        }

        $video->save();

        if ($changed) {
            VerifyVideoJob::dispatch($video)->afterCommit();
        }

        return $video;
    }

    private function nullable(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}
