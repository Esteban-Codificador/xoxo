<?php

namespace App\Domain\Audit;

use App\Enums\Permission;
use App\Models\AuditLog;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\Module;
use App\Models\Skill;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit log rows as the admin shows them: who, what, on which entity (its
 * name and, when the viewer can open it, a link) and, on request, the
 * field changes as before → after.
 *
 * @phpstan-type Change array{field: string, before: string|null, after: string|null, hashed: bool}
 * @phpstan-type Entry array{id: int, action: string, entity: string, label: string|null, href: string|null, user: string|null, created_at: string, changes?: list<Change>}
 */
final class AuditEntries
{
    /** Longer values are cut: the log is for finding changes, not reading them. */
    private const int MAX_VALUE = 280;

    /**
     * Eager loads what labels and links need.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function withSubjects(Builder $query): Builder
    {
        return $query->with(['user:id,name', 'auditable']);
    }

    /**
     * @return Entry
     */
    public function entry(AuditLog $log, ?User $viewer, bool $withChanges = false): array
    {
        $entry = [
            'id' => $log->id,
            'action' => $log->action->value,
            'entity' => $log->auditable_type,
            'label' => $this->label($log->auditable),
            'href' => $viewer === null ? null : $this->href($log->auditable, $viewer),
            'user' => $log->user?->name,
            'created_at' => $log->created_at->toIso8601String(),
        ];

        if ($withChanges) {
            $entry['changes'] = $this->changes($log->changes);
        }

        return $entry;
    }

    public function label(?Model $subject): ?string
    {
        foreach (['title', 'name', 'url'] as $attribute) {
            // Skills have a name, not a title: strict models throw on missing attributes.
            $value = $subject?->getAttributes()[$attribute] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Where the entity is edited in the CMS, if it still exists and the
     * viewer may open it.
     */
    public function href(?Model $subject, User $viewer): ?string
    {
        return match (true) {
            $subject instanceof Lesson => route('admin.lessons.edit', ['lesson' => $subject->slug], false),
            $subject instanceof LessonVersion => $this->versionHref($subject),
            $subject instanceof Track => route('admin.tracks.edit', $subject, false),
            $subject instanceof Module => route('admin.tracks.edit', ['track' => $subject->track_id], false),
            $subject instanceof Skill => route('admin.skills.edit', $subject, false),
            $subject instanceof ExternalResource => route('admin.resources.edit', $subject, false),
            $subject instanceof User && $viewer->can(Permission::UsersView->value) => route('admin.users.edit', $subject, false),
            default => null,
        };
    }

    private function versionHref(LessonVersion $version): ?string
    {
        $lesson = $version->loadMissing('lesson:id,slug')->lesson;

        return $lesson === null ? null : route('admin.lessons.versions.show', ['lesson' => $lesson->slug, 'version' => $version->version], false);
    }

    /**
     * @param  array{before?: array<string, mixed>, after?: array<string, mixed>}  $changes
     * @return list<Change>
     */
    public function changes(array $changes): array
    {
        $before = $changes['before'] ?? [];
        $after = $changes['after'] ?? [];
        $rows = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $field) {
            $hashed = $this->isHash($before[$field] ?? null) || $this->isHash($after[$field] ?? null);
            $rows[] = [
                'field' => (string) $field,
                'before' => $this->value($before[$field] ?? null),
                'after' => $this->value($after[$field] ?? null),
                // Bodies and descriptions are logged as a hash: the log says
                // they changed, the version history says how.
                'hashed' => $hashed,
            ];
        }

        return $rows;
    }

    /**
     * @phpstan-assert-if-true array{sha256: string} $value
     */
    private function isHash(mixed $value): bool
    {
        return is_array($value) && array_keys($value) === ['sha256'] && is_string($value['sha256']);
    }

    /**
     * A hashed field shows its short hash; the rest as text.
     */
    private function value(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($this->isHash($value)) {
            return $value['sha256'];
        }

        $text = match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };

        return mb_strlen($text) > self::MAX_VALUE ? mb_substr($text, 0, self::MAX_VALUE).'…' : $text;
    }
}
