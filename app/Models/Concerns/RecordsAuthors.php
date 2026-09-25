<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Fills created_by / updated_by with the authenticated user.
 *
 * @mixin Model
 */
trait RecordsAuthors
{
    public static function bootRecordsAuthors(): void
    {
        static::creating(function (Model $model): void {
            $model->setAttribute('created_by', $model->getAttribute('created_by') ?? Auth::id());
            $model->setAttribute('updated_by', $model->getAttribute('updated_by') ?? Auth::id());
        });

        static::updating(function (Model $model): void {
            if (Auth::id() !== null) {
                $model->setAttribute('updated_by', Auth::id());
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
