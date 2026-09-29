<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditEntries;
use App\Enums\ContentStatus;
use App\Enums\LinkStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Skill;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Overview of the curriculum for staff: content by status, link health and
 * the latest audit entries (the whole log is in AuditLogController).
 *
 * @phpstan-import-type Entry from AuditEntries
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

    public function __construct(private readonly AuditEntries $entries) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/dashboard', [
            'content' => $this->contentByStatus(),
            'links' => $this->countBy(ExternalResource::class, 'link_status', LinkStatus::values()),
            'activity' => $this->recentActivity($request->user()),
            'can' => ['view_audit' => $request->user()?->can(Permission::AuditView->value) ?? false],
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
     * @return list<Entry>
     */
    private function recentActivity(?User $viewer): array
    {
        return array_values($this->entries->withSubjects(AuditLog::query())
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => $this->entries->entry($log, $viewer))
            ->all());
    }
}
