<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\LinkStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Skill;
use App\Models\Track;
use Illuminate\Database\Eloquent\Model;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only overview of the curriculum for staff. Editing arrives with the
 * CMS (phase 5b); nothing on this page pretends to be actionable yet.
 */
class DashboardController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const array CONTENT = [
        'roadmap' => Roadmap::class,
        'track' => Track::class,
        'module' => Module::class,
        'lesson' => Lesson::class,
        'skill' => Skill::class,
        'resource' => ExternalResource::class,
    ];

    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard', [
            'content' => $this->contentByStatus(),
            'links' => $this->countBy(ExternalResource::class, 'link_status', LinkStatus::values()),
            'activity' => $this->recentActivity(),
        ]);
    }

    /**
     * @return list<array{entity: string, counts: array<string, int>, total: int}>
     */
    private function contentByStatus(): array
    {
        $rows = [];

        foreach (self::CONTENT as $entity => $model) {
            $counts = $this->countBy($model, 'status', ContentStatus::values());
            $rows[] = ['entity' => $entity, 'counts' => $counts, 'total' => array_sum($counts)];
        }

        return $rows;
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<string>  $values
     * @return array<string, int>
     */
    private function countBy(string $model, string $column, array $values): array
    {
        $found = $model::query()->toBase()
            ->select("{$column} as value")
            ->selectRaw('count(*) as total')
            ->groupBy($column)
            ->pluck('total', 'value');

        return array_combine($values, array_map(fn (string $value) => (int) ($found[$value] ?? 0), $values));
    }

    /**
     * @return list<array{id: int, action: string, entity: string, label: string|null, user: string|null, created_at: string}>
     */
    private function recentActivity(): array
    {
        return array_values(AuditLog::query()
            ->with(['user:id,name', 'auditable'])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action->value,
                'entity' => $log->auditable_type,
                'label' => $this->label($log->auditable),
                'user' => $log->user?->name,
                'created_at' => $log->created_at->toIso8601String(),
            ])
            ->all());
    }

    private function label(?Model $subject): ?string
    {
        if ($subject === null) {
            return null;
        }

        foreach (['title', 'name', 'url'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
