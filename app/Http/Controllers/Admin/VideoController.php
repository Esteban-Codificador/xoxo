<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Videos\VideoDuration;
use App\Domain\Content\Videos\YouTubeOEmbed;
use App\Domain\Curriculum\Actions\SaveVideo;
use App\Enums\LinkStatus;
use App\Enums\VideoProvider;
use App\Http\Controllers\Admin\Concerns\ListsStatusActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LookupVideoRequest;
use App\Http\Requests\Admin\SaveVideoRequest;
use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The video catalog (§32, ADR-035): YouTube videos by ID, checked with
 * oEmbed when added or changed (VerifyVideoJob) and every night
 * (content:verify-videos).
 */
class VideoController extends Controller
{
    use ListsStatusActions;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Video::class);

        $search = trim((string) $request->query('q', ''));
        $link = LinkStatus::tryFrom((string) $request->query('link', ''));

        $videos = Video::query()
            ->withCount('lessons')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->whereLike('title', "%{$search}%", caseSensitive: false)
                ->orWhereLike('instructor', "%{$search}%", caseSensitive: false)
                ->orWhere('external_id', $search)))
            ->when($link !== null, fn (Builder $query) => $query->where('link_status', $link?->value))
            ->orderBy('title')
            ->get();

        return Inertia::render('admin/videos/index', [
            'videos' => $videos->map(fn (Video $video) => [
                'id' => $video->id,
                'title' => $video->title,
                'instructor' => $video->instructor,
                'external_id' => $video->external_id,
                'url' => $video->url(),
                'duration' => VideoDuration::format($video->duration_seconds),
                'thumbnail_url' => $video->thumbnail_url,
                'link_status' => $video->link_status->value,
                'last_checked_at' => $video->last_checked_at?->toIso8601String(),
                'status' => $video->status->value,
                'lessons_count' => $video->lessons_count,
                'can_edit' => $request->user()?->can('update', $video) ?? false,
            ])->values()->all(),
            'filters' => ['q' => $search, 'link' => $link?->value],
            'can' => ['create' => $request->user()?->can('create', Video::class) ?? false],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Video::class);

        return Inertia::render('admin/videos/form', [
            'video' => null,
            'lessons' => [],
            'status_actions' => [],
        ]);
    }

    public function store(SaveVideoRequest $request, SaveVideo $action): RedirectResponse
    {
        /** @var array{url: string, title: string, instructor?: string|null, description?: string|null, duration?: string|null, difficulty?: string|null, language: string} $data */
        $data = $request->validated();
        $video = $action->handle(null, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('videos.created')]);

        return to_route('admin.videos.edit', $video);
    }

    public function edit(Request $request, Video $video): Response
    {
        Gate::authorize('update', $video);

        return Inertia::render('admin/videos/form', [
            'video' => [
                'id' => $video->id,
                'external_id' => $video->external_id,
                'url' => $video->url(),
                'title' => $video->title,
                'instructor' => $video->instructor,
                'description' => $video->description,
                'duration' => VideoDuration::format($video->duration_seconds),
                'difficulty' => $video->difficulty?->value,
                'language' => $video->language,
                'thumbnail_url' => $video->thumbnail_url,
                'status' => $video->status->value,
                'link_status' => $video->link_status->value,
                'last_checked_at' => $video->last_checked_at?->toIso8601String(),
                'last_http_status' => $video->last_http_status,
            ],
            'lessons' => $video->lessons()->orderBy('title')->get(['lessons.id', 'lessons.slug', 'lessons.title'])
                ->map(fn (Lesson $lesson) => ['slug' => $lesson->slug, 'title' => $lesson->title])->values()->all(),
            'status_actions' => $this->statusActions($request, $video),
        ]);
    }

    public function update(SaveVideoRequest $request, Video $video, SaveVideo $action): RedirectResponse
    {
        /** @var array{url: string, title: string, instructor?: string|null, description?: string|null, duration?: string|null, difficulty?: string|null, language: string} $data */
        $data = $request->validated();
        $action->handle($video, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('videos.saved')]);

        return back();
    }

    /**
     * What YouTube says about a pasted link, before saving: title and
     * channel to fill the form, or why the video cannot be used.
     */
    public function lookup(LookupVideoRequest $request, YouTubeOEmbed $oembed): JsonResponse
    {
        $id = $request->videoId();
        $result = $oembed->lookup($id);
        $existing = Video::query()->where('provider', VideoProvider::Youtube->value)->where('external_id', $id)->first();

        return response()->json([
            'video_id' => $id,
            'url' => "https://www.youtube.com/watch?v={$id}",
            // true: exists and can be embedded; false: it cannot be used; null: YouTube did not answer.
            'available' => $result->status === null ? null : $result->isAvailable(),
            'reason' => $result->reason,
            'title' => $result->title,
            'instructor' => $result->author,
            'thumbnail_url' => $result->thumbnailUrl,
            'existing' => $existing === null ? null : [
                'id' => $existing->id,
                'title' => $existing->title,
                'href' => route('admin.videos.edit', $existing, false),
            ],
        ]);
    }
}
