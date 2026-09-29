<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditEntries;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * The whole audit log (§43), newest first, filtered by action, entity type,
 * who did it and dates. Each entry shows its field changes.
 */
class AuditLogController extends Controller
{
    private const int PER_PAGE = 25;

    public function __invoke(Request $request, AuditEntries $entries): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        $action = AuditAction::tryFrom((string) $request->query('action', ''));
        $entityTypes = AuditLog::query()->distinct()->orderBy('auditable_type')->pluck('auditable_type')->all();
        $entity = in_array($request->query('entity'), $entityTypes, true) ? (string) $request->query('entity') : null;
        $userId = $request->integer('user') ?: null;
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));

        $logs = $entries->withSubjects(AuditLog::query())
            ->when($action !== null, fn (Builder $query) => $query->where('action', $action?->value))
            ->when($entity !== null, fn (Builder $query) => $query->where('auditable_type', $entity))
            ->when($userId !== null, fn (Builder $query) => $query->where('user_id', $userId))
            ->when($from !== null, fn (Builder $query) => $query->where('created_at', '>=', $from?->startOfDay()))
            ->when($to !== null, fn (Builder $query) => $query->where('created_at', '<=', $to?->endOfDay()))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('admin/audit/index', [
            'entries' => collect($logs->items())
                ->map(fn (AuditLog $log) => $entries->entry($log, $request->user(), withChanges: true))
                ->values()->all(),
            'pagination' => [
                'page' => $logs->currentPage(),
                'pages' => $logs->lastPage(),
                'total' => $logs->total(),
                'previous' => $logs->previousPageUrl(),
                'next' => $logs->nextPageUrl(),
            ],
            'filters' => [
                'action' => $action?->value,
                'entity' => $entity,
                'user' => $userId,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'options' => [
                'actions' => AuditAction::values(),
                'entities' => $entityTypes,
                // People who appear in the log, plus the one filtered by (a
                // user page links here even before they have done anything).
                'users' => User::query()
                    ->where(fn (Builder $query) => $query
                        ->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id'))
                        ->when($userId !== null, fn (Builder $inner) => $inner->orWhere('id', $userId)))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                    ->values()->all(),
            ],
        ]);
    }

    /**
     * A Y-m-d date from the query, or null. Impossible dates (2026-02-31)
     * are rejected, not rolled over to the next month.
     */
    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }

        return $date !== null && $date->format('Y-m-d') === $value ? $date : null;
    }
}
