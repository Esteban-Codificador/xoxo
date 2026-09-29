<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\CreateTrack;
use App\Domain\Curriculum\Actions\UpdateTrack;
use App\Enums\ContentStatus;
use App\Http\Controllers\Admin\Concerns\ListsStatusActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTrackRequest;
use App\Http\Requests\Admin\UpdateTrackRequest;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Pivots\TrackDependency;
use App\Models\Roadmap;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CMS for tracks and their modules. Tracks and modules are not versioned:
 * a saved change is what learners see (lessons are, ADR-006).
 */
class TrackController extends Controller
{
    use ListsStatusActions;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Track::class);

        $roadmaps = Roadmap::query()
            ->orderBy('id')
            ->with(['tracks' => fn ($query) => $query->orderBy('position')->orderBy('id')
                ->withCount(['modules', 'lessons'])
                ->with('prerequisites:id,title')])
            ->get();

        return Inertia::render('admin/tracks/index', [
            'roadmaps' => $roadmaps->map(fn (Roadmap $roadmap) => [
                'slug' => $roadmap->slug,
                'title' => $roadmap->title,
                'tracks' => $roadmap->tracks->map(fn (Track $track) => [
                    'id' => $track->id,
                    'title' => $track->title,
                    'status' => $track->status->value,
                    'modules_count' => $track->modules_count,
                    'lessons_count' => $track->lessons_count,
                    'prerequisites' => $track->prerequisites->pluck('title')->values()->all(),
                    'updated_at' => $track->updated_at?->toIso8601String(),
                    'can_edit' => $request->user()?->can('update', $track) ?? false,
                ])->values()->all(),
            ])->values()->all(),
            'can' => ['create' => $request->user()?->can('create', Track::class) ?? false],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Track::class);

        $roadmap = $request->filled('roadmap')
            ? Roadmap::query()->where('slug', $request->string('roadmap'))->firstOrFail()
            : Roadmap::query()->orderBy('id')->firstOrFail();

        return Inertia::render('admin/tracks/create', [
            'roadmap' => ['id' => $roadmap->id, 'slug' => $roadmap->slug, 'title' => $roadmap->title],
        ]);
    }

    public function store(StoreTrackRequest $request, CreateTrack $action): RedirectResponse
    {
        /** @var array{title: string, slug: string, summary: string, why_it_matters: string, difficulty: string, estimated_hours: int|string|null} $data */
        $data = $request->safe()->except('roadmap_id');
        $track = $action->handle($request->roadmap(), $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.track_created')]);

        return to_route('admin.tracks.edit', $track);
    }

    public function edit(Request $request, Track $track): Response
    {
        Gate::authorize('update', $track);

        $track->load(['roadmap', 'prerequisites']);
        $modules = $track->modules()->withCount('lessons')->get();

        return Inertia::render('admin/tracks/edit', [
            'track' => [
                'id' => $track->id,
                'slug' => $track->slug,
                'title' => $track->title,
                'summary' => $track->summary,
                'why_it_matters' => $track->why_it_matters,
                'description' => $track->description,
                'difficulty' => $track->difficulty->value,
                'estimated_hours' => $track->estimated_hours,
                'status' => $track->status->value,
                'published_at' => $track->published_at?->toIso8601String(),
                'roadmap' => ['slug' => $track->roadmap->slug, 'title' => $track->roadmap->title],
            ],
            'visible_to_learners' => $track->isPublished() && $track->roadmap->isPublished(),
            'status_actions' => $this->statusActions($request, $track),
            'dependencies' => $track->prerequisites->map(fn (Track $prerequisite) => [
                'id' => $prerequisite->id,
                'kind' => TrackDependency::of($prerequisite)->kind->value,
                'min_progress' => TrackDependency::of($prerequisite)->min_progress,
            ])->values()->all(),
            'dependency_options' => Track::query()
                ->where('roadmap_id', $track->roadmap_id)
                ->whereKeyNot($track->id)
                ->orderBy('position')
                ->get(['id', 'title', 'status'])
                ->map(fn (Track $option) => [
                    'id' => $option->id,
                    'title' => $option->title,
                    'published' => $option->status === ContentStatus::Published,
                ])->values()->all(),
            'can' => [
                'create_module' => $request->user()?->can('create', [Module::class, $track]) ?? false,
                'create_lesson' => $request->user()?->can('create', Lesson::class) ?? false,
            ],
            'modules' => $modules->map(fn (Module $module) => [
                'id' => $module->id,
                'slug' => $module->slug,
                'title' => $module->title,
                'summary' => $module->summary,
                'status' => $module->status->value,
                'lessons_count' => $module->lessons_count,
                'status_actions' => $this->statusActions($request, $module),
            ])->values()->all(),
        ]);
    }

    public function update(UpdateTrackRequest $request, Track $track, UpdateTrack $action): RedirectResponse
    {
        /** @var array{title: string, slug: string, summary: string, why_it_matters: string, description: array<string, mixed>|null, difficulty: string, estimated_hours: int|string|null} $data */
        $data = $request->validated();
        $action->handle($track, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.track_saved')]);

        return back();
    }
}
