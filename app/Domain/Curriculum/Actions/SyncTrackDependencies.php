<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Curriculum\Graph\CycleDetected;
use App\Domain\Curriculum\Graph\DependencyGraph;
use App\Enums\AuditAction;
use App\Models\Pivots\TrackDependency;
use App\Models\Track;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the prerequisites of a track. The track graph must stay acyclic
 * (§25): a set that would close a cycle is rejected as a whole.
 */
final readonly class SyncTrackDependencies
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  list<array{track_id: int, kind: string, min_progress: int}>  $dependencies
     *
     * @throws CycleDetected
     */
    public function handle(Track $track, array $dependencies): void
    {
        $graph = DependencyGraph::fromEdges(
            TrackDependency::query()->where('track_id', '!=', $track->id)->get()
                ->map(fn (TrackDependency $edge) => [$edge->track_id, $edge->prerequisite_track_id]),
        );

        foreach ($dependencies as $dependency) {
            $cycle = $graph->wouldCreateCycle($track->id, $dependency['track_id']);

            if ($cycle !== null) {
                throw new CycleDetected($cycle);
            }

            $graph->addEdge($track->id, $dependency['track_id']);
        }

        $before = $this->snapshot($track);

        DB::transaction(fn () => $track->prerequisites()->sync(
            collect($dependencies)->mapWithKeys(fn (array $dependency) => [
                $dependency['track_id'] => ['kind' => $dependency['kind'], 'min_progress' => $dependency['min_progress']],
            ])->all(),
        ));

        $after = $this->snapshot($track);

        // Pivot rows have no model events: the change is logged on the track.
        if ($before !== $after) {
            $this->audit->record(AuditAction::Updated, $track, ['prerequisites' => $before], ['prerequisites' => $after]);
        }
    }

    /**
     * @return list<array{track_id: int, kind: string, min_progress: int}>
     */
    private function snapshot(Track $track): array
    {
        return array_values(TrackDependency::query()->where('track_id', $track->id)->orderBy('prerequisite_track_id')->get()
            ->map(fn (TrackDependency $edge) => [
                'track_id' => $edge->prerequisite_track_id,
                'kind' => $edge->kind->value,
                'min_progress' => $edge->min_progress,
            ])->all());
    }
}
