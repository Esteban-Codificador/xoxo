<?php

use App\Domain\Content\RichContent\RichContent;
use App\Domain\Curriculum\Actions\PublishLesson;
use App\Enums\Role;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Skill;
use App\Models\Track;
use App\Models\User;
use Database\Factories\LessonFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Feature tests boot the application and run against the PostgreSQL test
| database (phpunit.xml). Unit tests stay framework-free.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/**
 * A lesson that satisfies the publishing contract. Without a module it gets
 * its own published module, track and roadmap.
 */
function publishableLesson(array $attributes = [], ?Module $module = null): Lesson
{
    $module ??= Module::factory()->published()
        ->for(Track::factory()->published()->for(Roadmap::factory()->published()))
        ->create();
    $paragraph = str_repeat('Git guarda la historia como instantáneas encadenadas por hash. ', 30);

    $lesson = Lesson::factory()->for($module)->create([
        'summary' => str_repeat('Resumen claro de la lección. ', 4),
        'why_it_matters' => str_repeat('Importa porque se usa a diario. ', 4),
        'body' => LessonFactory::body([$paragraph]),
        ...$attributes,
    ]);

    $lesson->body = RichContent::fromDocument([
        'type' => 'doc',
        'content' => [
            ...$lesson->body->doc['content'],
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Práctica']]],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Crea un repositorio y haz tres commits.']]],
        ],
    ]);
    $lesson->save();
    $lesson->skills()->attach(Skill::factory()->create(), ['weight' => 2]);

    return $lesson->refresh();
}

/**
 * A lesson published through PublishLesson, visible to learners.
 */
function publishedLesson(array $attributes = [], ?Module $module = null): Lesson
{
    $lesson = publishableLesson($attributes, $module);
    app(PublishLesson::class)->handle($lesson);

    return $lesson->refresh();
}

/** A user with a CMS role (or a learner, with Role::Student). */
function staff(Role $role): User
{
    return User::factory()->create()->assignRole($role->value);
}

/**
 * The form payload for a lesson, as the editor page sends it.
 *
 * @return array<string, mixed>
 */
function lessonForm(Lesson $lesson, array $overrides = []): array
{
    return [
        'title' => $lesson->title,
        'slug' => $lesson->slug,
        'summary' => $lesson->summary,
        'why_it_matters' => $lesson->why_it_matters,
        'learning_objectives' => $lesson->learning_objectives,
        'content_type' => $lesson->content_type->value,
        'difficulty' => $lesson->difficulty->value,
        'estimated_minutes' => $lesson->estimated_minutes,
        'body' => $lesson->body->toArray(),
        ...$overrides,
    ];
}
