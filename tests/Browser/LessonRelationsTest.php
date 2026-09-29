<?php

use App\Enums\Role;
use App\Models\Lesson;
use App\Models\Skill;
use App\Models\User;

/*
| Lesson relations in the CMS on the real sample curriculum. They are not
| versioned (TD-6): a saved skill shows on the lesson page right away.
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
});

it('adds a skill to a lesson and the learner sees it without publishing', function () {
    $this->actingAs(staff(Role::Editor));
    $lesson = Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging');
    $skill = Skill::query()->whereNotIn('id', $lesson->skills()->pluck('skills.id'))->orderBy('name')->firstOrFail();

    visit("/admin/lessons/{$lesson->slug}/edit")
        ->click('nav a:has-text("Relaciones")')
        ->assertPathIs("/admin/lessons/{$lesson->slug}/relations")
        ->assertSee('Las relaciones no tienen versiones')
        ->select('section:has-text("Skills que desarrolla") select[id$="-add"]', $skill->name)
        ->click('button:has-text("Añadir skill")')
        ->assertSee('Tienes cambios sin guardar.')
        ->press('Guardar cambios')
        ->waitForText('Relaciones guardadas')
        ->assertNoJavaScriptErrors();

    expect($lesson->skills()->whereKey($skill->id)->first()?->pivot->weight)->toBe(3);

    $this->actingAs(User::factory()->create());

    visit("/lessons/{$lesson->slug}")
        ->assertSee('Skills que desarrolla')
        ->assertSee($skill->name)
        ->assertNoJavaScriptErrors();
});
