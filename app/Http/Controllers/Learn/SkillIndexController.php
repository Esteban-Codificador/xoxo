<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Learning\State\RoadmapStateResolver;
use App\Domain\Learning\State\SkillProgressCalculator;
use App\Http\Controllers\Controller;
use App\Models\Roadmap;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every published skill with the learner's progress, in the order the
 * roadmap develops them (master spec §38, "ver skills dominadas").
 */
class SkillIndexController extends Controller
{
    public function __invoke(Request $request, RoadmapStateResolver $states, SkillProgressCalculator $calculator): Response
    {
        $roadmap = Roadmap::query()->published()->orderBy('id')->first();

        if ($roadmap === null) {
            return Inertia::render('skills/index', ['skills' => [], 'summary' => ['completed' => 0, 'total' => 0]]);
        }

        $skills = $calculator->calculate($states->resolve($request->user(), $roadmap));

        return Inertia::render('skills/index', [
            'skills' => array_map(fn (int $id) => [
                'slug' => $skills->info($id)['slug'],
                'name' => $skills->info($id)['name'],
                'description' => $skills->info($id)['description'],
                'difficulty' => $skills->info($id)['difficulty']->value,
                'progress' => $skills->state($id)->toArray(),
            ], $skills->ids()),
            'summary' => ['completed' => $skills->completedCount(), 'total' => $skills->count()],
        ]);
    }
}
