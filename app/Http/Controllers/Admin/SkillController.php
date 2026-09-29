<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\SaveSkill;
use App\Enums\ContentStatus;
use App\Http\Controllers\Admin\Concerns\ListsStatusActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSkillRequest;
use App\Models\Lesson;
use App\Models\Pivots\SkillDependency;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** Skills (§38): what lessons develop, with their own prerequisite graph. */
class SkillController extends Controller
{
    use ListsStatusActions;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Skill::class);

        $skills = Skill::query()->withCount('lessons')->with('prerequisites:id,name')->orderBy('name')->get();

        return Inertia::render('admin/skills/index', [
            'skills' => $skills->map(fn (Skill $skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'slug' => $skill->slug,
                'difficulty' => $skill->difficulty->value,
                'status' => $skill->status->value,
                'lessons_count' => $skill->lessons_count,
                'prerequisites' => $skill->prerequisites->pluck('name')->values()->all(),
                'can_edit' => $request->user()?->can('update', $skill) ?? false,
            ])->values()->all(),
            'can' => ['create' => $request->user()?->can('create', Skill::class) ?? false],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Skill::class);

        return Inertia::render('admin/skills/form', [
            'skill' => null,
            'dependencies' => [],
            'dependency_options' => [],
            'lessons' => [],
            'status_actions' => [],
        ]);
    }

    public function store(SaveSkillRequest $request, SaveSkill $action): RedirectResponse
    {
        /** @var array{name: string, slug: string, description: string, difficulty: string} $data */
        $data = $request->validated();
        $skill = $action->handle(null, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.skill_created')]);

        return to_route('admin.skills.edit', $skill);
    }

    public function edit(Request $request, Skill $skill): Response
    {
        Gate::authorize('update', $skill);

        $skill->load('prerequisites');

        return Inertia::render('admin/skills/form', [
            'skill' => [
                'id' => $skill->id,
                'name' => $skill->name,
                'slug' => $skill->slug,
                'description' => $skill->description,
                'difficulty' => $skill->difficulty->value,
                'status' => $skill->status->value,
            ],
            'dependencies' => $skill->prerequisites->map(fn (Skill $prerequisite) => [
                'id' => $prerequisite->id,
                'kind' => SkillDependency::of($prerequisite)->kind->value,
                'min_progress' => SkillDependency::of($prerequisite)->min_progress,
            ])->values()->all(),
            'dependency_options' => Skill::query()->whereKeyNot($skill->id)->orderBy('name')->get()
                ->map(fn (Skill $option) => [
                    'id' => $option->id,
                    'title' => $option->name,
                    'published' => $option->status === ContentStatus::Published,
                ])->values()->all(),
            'lessons' => $skill->lessons()->orderBy('title')->get(['lessons.id', 'lessons.slug', 'lessons.title'])
                ->map(fn (Lesson $lesson) => ['slug' => $lesson->slug, 'title' => $lesson->title])->values()->all(),
            'status_actions' => $this->statusActions($request, $skill),
        ]);
    }

    public function update(SaveSkillRequest $request, Skill $skill, SaveSkill $action): RedirectResponse
    {
        /** @var array{name: string, slug: string, description: string, difficulty: string} $data */
        $data = $request->validated();
        $action->handle($skill, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.skill_saved')]);

        return back();
    }
}
