<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\SaveResource;
use App\Enums\LinkStatus;
use App\Http\Controllers\Admin\Concerns\ListsStatusActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveResourceRequest;
use App\Models\ExternalResource;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * External resources (§33): official documentation first, never invented
 * URLs. Every new or changed URL is verified (VerifyResourceLinkJob) and
 * checked again every night (content:verify-resources).
 */
class ResourceController extends Controller
{
    use ListsStatusActions;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ExternalResource::class);

        $search = trim((string) $request->query('q', ''));
        $link = LinkStatus::tryFrom((string) $request->query('link', ''));

        $resources = ExternalResource::query()
            ->withCount('lessons')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->whereLike('title', "%{$search}%", caseSensitive: false)
                ->orWhereLike('provider', "%{$search}%", caseSensitive: false)
                ->orWhereLike('url', "%{$search}%", caseSensitive: false)))
            ->when($link !== null, fn (Builder $query) => $query->where('link_status', $link?->value))
            ->orderBy('title')
            ->get();

        return Inertia::render('admin/resources/index', [
            'resources' => $resources->map(fn (ExternalResource $resource) => [
                'id' => $resource->id,
                'title' => $resource->title,
                'provider' => $resource->provider,
                'type' => $resource->type->value,
                'url' => $resource->url,
                'is_official' => $resource->is_official,
                'link_status' => $resource->link_status->value,
                'last_checked_at' => $resource->last_checked_at?->toIso8601String(),
                'status' => $resource->status->value,
                'lessons_count' => $resource->lessons_count,
                'can_edit' => $request->user()?->can('update', $resource) ?? false,
            ])->values()->all(),
            'filters' => ['q' => $search, 'link' => $link?->value],
            'can' => ['create' => $request->user()?->can('create', ExternalResource::class) ?? false],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ExternalResource::class);

        return Inertia::render('admin/resources/form', [
            'resource' => null,
            'lessons' => [],
            'status_actions' => [],
        ]);
    }

    public function store(SaveResourceRequest $request, SaveResource $action): RedirectResponse
    {
        /** @var array{title: string, url: string, type: string, provider: string, description: string, difficulty: string|null, language: string, is_official: bool|int|string} $data */
        $data = $request->validated();
        $resource = $action->handle(null, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.resource_created')]);

        return to_route('admin.resources.edit', $resource);
    }

    public function edit(Request $request, ExternalResource $resource): Response
    {
        Gate::authorize('update', $resource);

        return Inertia::render('admin/resources/form', [
            'resource' => [
                'id' => $resource->id,
                'title' => $resource->title,
                'url' => $resource->url,
                'type' => $resource->type->value,
                'provider' => $resource->provider,
                'description' => $resource->description,
                'difficulty' => $resource->difficulty?->value,
                'language' => $resource->language,
                'is_official' => $resource->is_official,
                'status' => $resource->status->value,
                'link_status' => $resource->link_status->value,
                'last_checked_at' => $resource->last_checked_at?->toIso8601String(),
                'last_http_status' => $resource->last_http_status,
            ],
            'lessons' => $resource->lessons()->orderBy('title')->get(['lessons.id', 'lessons.slug', 'lessons.title'])
                ->map(fn (Lesson $lesson) => ['slug' => $lesson->slug, 'title' => $lesson->title])->values()->all(),
            'status_actions' => $this->statusActions($request, $resource),
        ]);
    }

    public function update(SaveResourceRequest $request, ExternalResource $resource, SaveResource $action): RedirectResponse
    {
        /** @var array{title: string, url: string, type: string, provider: string, description: string, difficulty: string|null, language: string, is_official: bool|int|string} $data */
        $data = $request->validated();
        $action->handle($resource, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.resource_saved')]);

        return back();
    }
}
