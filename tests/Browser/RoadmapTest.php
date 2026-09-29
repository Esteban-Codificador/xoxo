<?php

use App\Models\User;

/*
| The visual roadmap in a real browser: React Flow draws the real sample
| curriculum, a node opens its panel, and the list view is the fallback.
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
    $this->actingAs(User::factory()->create());
});

it('draws the tracks and opens a track panel from its node', function () {
    visit('/roadmap')
        ->assertPathIs('/roadmaps/ai-engineer')
        ->assertSee('Necesario')
        ->assertPresent('.react-flow__node-track')
        ->assertCount('.react-flow__node-track', 2)
        ->assertPresent('.react-flow__edge')
        // A real click on the node (React Flow once swallowed it).
        ->click('[aria-label="Ver detalles de Fundamentos de computación"]')
        ->assertSee('Necesita')
        ->assertSee('Orientación: el rol de AI Engineer al 100 %')
        ->assertSee('Git y colaboración')
        ->assertSee('Ver track')
        ->assertNoJavaScriptErrors();
});

it('offers the same roadmap as a list', function () {
    visit('/roadmaps/ai-engineer?view=list')
        ->assertSee('Nivel 1')
        ->assertSee('Nivel 2')
        ->assertNotPresent('.react-flow__node-track')
        ->assertNoJavaScriptErrors();
});
