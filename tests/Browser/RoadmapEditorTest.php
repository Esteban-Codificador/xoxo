<?php

use App\Enums\Role;
use App\Enums\UnlockPolicy;
use App\Models\Roadmap;

/*
| Editing the roadmap in a real browser: an editor opens it from the track
| list, changes its summary and unlock policy, and a learner reads the new
| summary and its description above the map.
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
});

it('edits the roadmap from the track list and learners read it above the map', function () {
    $this->actingAs(staff(Role::Editor));

    visit('/admin/tracks')
        ->click('[aria-label="Editar el roadmap AI Engineer"]')
        ->assertPathIs('/admin/roadmaps/'.Roadmap::firstWhere('slug', 'ai-engineer')->id.'/edit')
        ->assertSee('Política de desbloqueo')
        ->fill('summary', 'Del primer script a sistemas de IA en producción.')
        ->click('input[name=unlock_policy][value=STRICT]')
        ->press('Guardar cambios')
        ->waitForText('Roadmap guardado')
        ->assertNoJavaScriptErrors();

    $roadmap = Roadmap::firstWhere('slug', 'ai-engineer');
    expect($roadmap->summary)->toBe('Del primer script a sistemas de IA en producción.')
        ->and($roadmap->unlock_policy)->toBe(UnlockPolicy::Strict);

    $this->actingAs(staff(Role::Student));

    visit('/roadmap')
        ->assertSee('Del primer script a sistemas de IA en producción.')
        ->click('summary:has-text("Sobre este roadmap")')
        ->assertSee('Este roadmap sigue una cadena de fundamentos a aplicaciones')
        ->assertNoJavaScriptErrors();
});
