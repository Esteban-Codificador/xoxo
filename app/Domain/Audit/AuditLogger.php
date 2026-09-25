<?php

namespace App\Domain\Audit;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Single entry point to audit_logs (§43). Model observers call
 * recordModelChange(); workflow actions (publish, archive, import) wrap their
 * writes in during() so the resulting changes are logged under that action
 * instead of a generic UPDATED.
 */
final class AuditLogger
{
    private ?AuditAction $context = null;

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function during(AuditAction $action, Closure $callback): mixed
    {
        $previous = $this->context;
        $this->context = $action;

        try {
            return $callback();
        } finally {
            $this->context = $previous;
        }
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function recordModelChange(AuditAction $action, Model $subject, array $before, array $after): AuditLog
    {
        return $this->record($this->context ?? $action, $subject, $before, $after);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function record(AuditAction $action, Model $subject, array $before = [], array $after = []): AuditLog
    {
        $request = request();
        $fromHttp = $request->route() !== null;

        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'changes' => array_filter(['before' => $before, 'after' => $after], fn (array $values) => $values !== []),
            'ip_address' => $fromHttp ? $request->ip() : null,
            'user_agent' => $fromHttp ? mb_substr((string) $request->userAgent(), 0, 512) : null,
        ]);
    }
}
