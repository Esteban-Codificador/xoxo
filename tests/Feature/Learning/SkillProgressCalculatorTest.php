<?php

use App\Domain\Learning\State\RoadmapStateResolver;
use App\Domain\Learning\State\SkillProgressCalculator;
use App\Domain\Learning\State\SkillsState;
use App\Enums\ContentStatus;
use App\Enums\DependencyKind;
use App\Enums\NodeState;
use App\Enums\ProgressStatus;
use App\Enums\UnlockPolicy;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Skill;
use App\Models\Track;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
| Skill progress and state (architecture §6, D5):
|   git    ← a1 (weight 1), a2 (weight 3)
|   python ← b1 (weight 2); REQUIRES git at 80 %
|   docker ← no lessons; RECOMMENDS git
|   sql    ← a draft skill: never shown
*/

beforeEach(function () {
    $roadmap = Roadmap::factory()->published()->create(['unlock_policy' => UnlockPolicy::Advisory]);
    $this->roadmap = $roadmap;
    $moduleA = Module::factory()->published()->for(Track::factory()->published()->for($roadmap)->create(['position' => 0]))->create();
    $moduleB = Module::factory()->published()->for(Track::factory()->published()->for($roadmap)->create(['position' => 1]))->create();

    $this->a1 = publishedLesson(['slug' => 'a1', 'position' => 1], $moduleA);
    $this->a2 = publishedLesson(['slug' => 'a2', 'position' => 2], $moduleA);
    $this->b1 = publishedLesson(['slug' => 'b1', 'position' => 1], $moduleB);
    // publishedLesson() gives each lesson a skill of its own; these tests use their own.
    Skill::query()->delete();

    $this->git = Skill::factory()->create(['slug' => 'git', 'name' => 'Git']);
    $this->python = Skill::factory()->create(['slug' => 'python', 'name' => 'Python']);
    $this->docker = Skill::factory()->create(['slug' => 'docker', 'name' => 'Docker']);
    $this->sql = Skill::factory()->create(['slug' => 'sql', 'name' => 'SQL', 'status' => ContentStatus::Draft]);

    $this->a1->skills()->attach($this->git, ['weight' => 1]);
    $this->a2->skills()->attach($this->git, ['weight' => 3]);
    $this->b1->skills()->attach($this->python, ['weight' => 2]);
    $this->a1->skills()->attach($this->sql, ['weight' => 5]);
    $this->python->prerequisites()->attach($this->git, ['kind' => DependencyKind::Required->value, 'min_progress' => 80]);
    $this->docker->prerequisites()->attach($this->git, ['kind' => DependencyKind::Recommended->value, 'min_progress' => 100]);

    $this->user = User::factory()->create();
});

function skillsFor(User $user, Roadmap $roadmap): SkillsState
{
    return app(SkillProgressCalculator::class)->calculate(app(RoadmapStateResolver::class)->resolve($user, $roadmap));
}

function done(User $user, Lesson $lesson): void
{
    LessonProgress::create([
        'user_id' => $user->id, 'lesson_id' => $lesson->id, 'status' => ProgressStatus::Completed,
        'started_at' => now(), 'completed_at' => now(), 'last_viewed_at' => now(),
    ]);
}

it('lists published skills in the order the roadmap develops them', function () {
    $skills = skillsFor($this->user, $this->roadmap);

    expect(array_map(fn (int $id) => $skills->info($id)['slug'], $skills->ids()))->toBe(['git', 'python', 'docker'])
        ->and($skills->idOf('sql'))->toBeNull()
        ->and($skills->lessonIdsOf($this->git->id))->toBe([$this->a1->id, $this->a2->id])
        ->and($skills->count())->toBe(3)
        ->and($skills->completedCount())->toBe(0);
});

it('starts a new learner with the skills whose prerequisites are met', function () {
    $skills = skillsFor($this->user, $this->roadmap);

    expect($skills->state($this->git->id)->toArray())->toBe(['state' => 'AVAILABLE', 'progress' => 0, 'completed' => 0, 'total' => 2, 'blockers' => []])
        ->and($skills->state($this->python->id)->state)->toBe(NodeState::Locked)
        ->and($skills->state($this->python->id)->blockers[0]->toArray())->toBe(['type' => 'skill', 'slug' => 'git', 'title' => 'Git', 'progress' => 0, 'required' => 80])
        // A recommended prerequisite never locks; a skill without lessons can only be available.
        ->and($skills->state($this->docker->id)->toArray())->toMatchArray(['state' => 'AVAILABLE', 'total' => 0]);
});

it('weighs each lesson by how much it develops the skill', function () {
    done($this->user, $this->a1);
    $afterLight = skillsFor($this->user, $this->roadmap);

    expect($afterLight->state($this->git->id)->toArray())->toMatchArray(['state' => 'IN_PROGRESS', 'progress' => 25, 'completed' => 1, 'total' => 2])
        ->and($afterLight->state($this->python->id)->blockers[0]->progress)->toBe(25);

    LessonProgress::query()->delete();
    done($this->user, $this->a2);

    expect(skillsFor($this->user, $this->roadmap)->state($this->git->id)->progress)->toBe(75);
});

it('completes a skill with all its lessons and unlocks the skills that require it', function () {
    done($this->user, $this->a1);
    done($this->user, $this->a2);

    $skills = skillsFor($this->user, $this->roadmap);

    expect($skills->state($this->git->id)->toArray())->toMatchArray(['state' => 'COMPLETED', 'progress' => 100])
        ->and($skills->state($this->python->id)->state)->toBe(NodeState::Available)
        ->and($skills->completedCount())->toBe(1);
});

it('never reaches MASTERED without evidence', function () {
    foreach ([$this->a1, $this->a2] as $lesson) {
        LessonProgress::create([
            'user_id' => $this->user->id, 'lesson_id' => $lesson->id, 'status' => ProgressStatus::Mastered,
            'started_at' => now(), 'completed_at' => now(), 'mastered_at' => now(),
        ]);
    }

    expect(skillsFor($this->user, $this->roadmap)->state($this->git->id)->state)->toBe(NodeState::Completed);
});

it('only counts lessons learners can see', function () {
    $draft = publishableLesson(['slug' => 'borrador', 'position' => 3], $this->a1->module);
    $draft->skills()->sync([$this->git->id => ['weight' => 5]]);
    done($this->user, $this->a1);
    done($this->user, $this->a2);

    expect(skillsFor($this->user, $this->roadmap)->state($this->git->id)->toArray())->toMatchArray(['state' => 'COMPLETED', 'total' => 2]);
});

it('adds three queries to the roadmap state', function () {
    $state = app(RoadmapStateResolver::class)->resolve($this->user, $this->roadmap);
    DB::enableQueryLog();

    app(SkillProgressCalculator::class)->calculate($state);

    expect(DB::getQueryLog())->toHaveCount(3);
});
