<?php

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Skill;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('rejects a track that depends on itself', function () {
    $track = Track::factory()->create();

    DB::table('track_dependencies')->insert(['track_id' => $track->id, 'prerequisite_track_id' => $track->id]);
})->throws(QueryException::class, 'track_dependencies_not_self');

it('rejects dependency thresholds outside 1-100', function () {
    [$a, $b] = Skill::factory()->count(2)->create();

    DB::table('skill_dependencies')->insert(['skill_id' => $a->id, 'prerequisite_skill_id' => $b->id, 'min_progress' => 0]);
})->throws(QueryException::class, 'skill_dependencies_min_progress_range');

it('rejects unknown enum values at the database level', function () {
    Track::factory()->create();

    DB::table('tracks')->update(['status' => 'PUBLISHD']);
})->throws(QueryException::class);

it('requires a completion date for completed progress', function () {
    LessonProgress::query()->create([
        'user_id' => User::factory()->create()->id,
        'lesson_id' => Lesson::factory()->create()->id,
        'status' => 'COMPLETED',
        'started_at' => now(),
    ]);
})->throws(QueryException::class, 'lesson_progress_completion_date');

it('refuses to delete a lesson that has learner progress', function () {
    $lesson = Lesson::factory()->create();

    LessonProgress::query()->create([
        'user_id' => User::factory()->create()->id,
        'lesson_id' => $lesson->id,
        'status' => 'IN_PROGRESS',
        'started_at' => now(),
    ]);

    $lesson->delete();
})->throws(QueryException::class, 'foreign key');

it('grants xp only once per user, activity and subject', function () {
    $user = User::factory()->create();
    $activity = ['user_id' => $user->id, 'type' => 'LESSON_COMPLETED', 'subject_type' => 'lesson', 'subject_id' => 1, 'occurred_at' => now(), 'occurred_on' => today()];

    DB::table('learning_activities')->insert([...$activity, 'xp' => 10]);
    // Repeating the activity without XP is fine: it still appears in the feed.
    DB::table('learning_activities')->insert([...$activity, 'xp' => 0]);

    expect(fn () => DB::table('learning_activities')->insert([...$activity, 'xp' => 10]))
        ->toThrow(QueryException::class, 'learning_activities_xp_once');
});

it('enforces the public username format', function (string $username) {
    User::factory()->create(['username' => $username]);
})->with(['Mayusculas', '-guion', 'ab', 'con espacio'])->throws(QueryException::class, 'users_username_format');
