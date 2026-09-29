<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Models\Module;
use App\Models\Track;
use Illuminate\Support\Facades\DB;

/**
 * Sets the study order of a track's modules. The order is logged once on
 * the track instead of one UPDATED row per module.
 */
final readonly class ReorderModules
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  list<int>  $moduleIds  Every module of the track, in the new order.
     */
    public function handle(Track $track, array $moduleIds): void
    {
        $before = $track->modules()->pluck('id')->all();

        if ($before === $moduleIds) {
            return;
        }

        DB::transaction(function () use ($track, $moduleIds): void {
            Module::withoutEvents(function () use ($track, $moduleIds): void {
                foreach ($moduleIds as $index => $id) {
                    Module::query()->whereKey($id)->where('track_id', $track->id)->update(['position' => $index + 1]);
                }
            });
        });

        $this->audit->record(AuditAction::Updated, $track, ['module_order' => $before], ['module_order' => $moduleIds]);
    }
}
