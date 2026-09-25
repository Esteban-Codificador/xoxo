<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Curriculum\Publishing\LessonDraft;
use App\Domain\Curriculum\Publishing\LessonNotReadyToPublish;
use App\Domain\Curriculum\Publishing\LessonReadiness;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Models\Lesson;
use App\Models\LessonVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Snapshots the working copy into an immutable lesson_versions row and makes
 * it the version learners read (ADR-006). Publishing unchanged content is a
 * no-op that returns the current version.
 */
final readonly class PublishLesson
{
    public function __construct(
        private LessonReadiness $readiness,
        private AuditLogger $audit,
    ) {}

    /**
     * @throws LessonNotReadyToPublish
     */
    public function handle(Lesson $lesson, ?string $changeNote = null): LessonVersion
    {
        $issues = $this->readiness->check(LessonDraft::fromLesson($lesson));

        if ($issues !== []) {
            throw new LessonNotReadyToPublish($issues);
        }

        return DB::transaction(function () use ($lesson, $changeNote): LessonVersion {
            $lesson->loadMissing('publishedVersion');
            $current = $lesson->publishedVersion;
            $hash = $lesson->workingCopyHash();

            if ($current !== null && $current->content_hash === $hash && $lesson->status === ContentStatus::Published) {
                return $current;
            }

            $publishedAt = now();

            $version = LessonVersion::query()->create([
                ...$lesson->versionedFields(),
                'lesson_id' => $lesson->id,
                'version' => (int) $lesson->versions()->max('version') + 1,
                'content_hash' => $hash,
                'change_note' => $changeNote,
                'published_by' => Auth::id(),
                'published_at' => $publishedAt,
            ]);

            $this->audit->during(AuditAction::Published, fn () => $lesson->forceFill([
                'status' => ContentStatus::Published,
                'published_version_id' => $version->id,
                'published_at' => $publishedAt,
            ])->save());

            return $lesson->setRelation('publishedVersion', $version)->publishedVersion;
        });
    }
}
