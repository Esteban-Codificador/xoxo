<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\LessonReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => App::getLocale(),
            'auth' => [
                'user' => $request->user(),
            ],
            'can' => [
                'accessAdmin' => (bool) $request->user()?->can(Permission::AdminAccess->value),
            ],
            // Lessons waiting for review, for whoever reviews them (admin menu).
            'pendingReviews' => fn () => $request->user()?->can(Permission::ContentPublish->value)
                ? LessonReview::query()->open()->count()
                : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
