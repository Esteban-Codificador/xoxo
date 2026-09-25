<?php

namespace App\Models\Concerns;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes an audit_logs row for every create, update and delete of the model.
 * Large fields listed in auditSummarized() are logged as a hash, not stored.
 *
 * @mixin Model
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditLogger::class)->recordModelChange(AuditAction::Created, $model, [], self::auditValues($model, $model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            $after = self::auditValues($model, $model->getChanges());

            if ($after === []) {
                return;
            }

            $before = self::auditValues($model, array_intersect_key($model->getRawOriginal(), $after));

            app(AuditLogger::class)->recordModelChange(AuditAction::Updated, $model, $before, $after);
        });

        static::deleted(function (Model $model): void {
            app(AuditLogger::class)->recordModelChange(AuditAction::Deleted, $model, self::auditValues($model, $model->getRawOriginal()), []);
        });
    }

    /**
     * Attributes stored as a short hash in the audit log instead of their value.
     *
     * @return list<string>
     */
    public function auditSummarized(): array
    {
        return ['body', 'description'];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function auditValues(Model $model, array $values): array
    {
        unset($values['created_at'], $values['updated_at']);

        $summarized = method_exists($model, 'auditSummarized') ? $model->auditSummarized() : [];

        foreach ($values as $key => $value) {
            if (in_array($key, $summarized, true) && $value !== null) {
                $values[$key] = ['sha256' => substr(hash('sha256', is_string($value) ? $value : (string) json_encode($value)), 0, 16)];
            }
        }

        return $values;
    }
}
