<?php

namespace App\Http\Controllers\Learn;

use App\Http\Controllers\Controller;
use App\Models\Roadmap;
use Illuminate\Http\RedirectResponse;

/**
 * /roadmap opens the published roadmap (the one the dashboard shows), so
 * navigation does not need to know its slug.
 */
class CurrentRoadmapController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $roadmap = Roadmap::query()->published()->orderBy('id')->first();

        return $roadmap === null
            ? redirect()->route('dashboard')
            : redirect()->route('roadmaps.show', $roadmap);
    }
}
