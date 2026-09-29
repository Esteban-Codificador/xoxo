<?php

namespace App\Http\Requests\Admin;

use App\Enums\DependencyKind;
use App\Models\Track;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The full list of a track's prerequisites. Cycles are checked by
 * SyncTrackDependencies, which knows the whole graph.
 */
class SyncTrackDependenciesRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->track());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $track = $this->track();

        return [
            'dependencies' => ['present', 'list', 'max:20'],
            'dependencies.*.track_id' => [
                'required', 'integer', 'distinct',
                // Prerequisites live in the same roadmap and are not the track itself.
                Rule::exists('tracks', 'id')->where('roadmap_id', $track->roadmap_id),
                Rule::notIn([$track->id]),
            ],
            'dependencies.*.kind' => ['required', Rule::enum(DependencyKind::class)],
            'dependencies.*.min_progress' => ['required', 'integer', 'between:1,100'],
        ];
    }

    /**
     * @return list<array{track_id: int, kind: string, min_progress: int}>
     */
    public function dependencies(): array
    {
        /** @var list<array{track_id: int|string, kind: string, min_progress: int|string}> $rows */
        $rows = $this->validated('dependencies');

        return array_map(fn (array $row) => [
            'track_id' => (int) $row['track_id'],
            'kind' => $row['kind'],
            'min_progress' => (int) $row['min_progress'],
        ], $rows);
    }

    public function track(): Track
    {
        $track = $this->route('track');

        return $track instanceof Track ? $track : abort(404);
    }
}
