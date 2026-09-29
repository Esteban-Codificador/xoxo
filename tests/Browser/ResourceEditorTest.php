<?php

use App\Enums\LinkStatus;
use App\Enums\Role;
use App\Models\ExternalResource;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/*
| Resources in the CMS on the real sample curriculum: create one (its link
| is checked right away; faked here, the test never reaches the network),
| publish it, attach it to a lesson and the learner sees it.
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
    Http::fake(['docs.example.test/*' => Http::response('', 200)]);
});

it('creates a resource, publishes it, attaches it to a lesson and the learner sees it', function () {
    $this->actingAs(staff(Role::Editor));
    $slug = 'git-commits-arbol-de-trabajo-y-staging';

    $page = visit('/admin/resources')
        ->click('a:has-text("Nuevo recurso")')
        ->assertPathIs('/admin/resources/create')
        ->fill('title', 'Guía de staging')
        ->fill('url', 'https://docs.example.test/staging')
        ->fill('provider', 'Docs de ejemplo')
        ->fill('description', 'Cómo preparar cambios parciales antes de un commit.')
        ->press('Guardar cambios')
        ->waitForText('Recurso creado como borrador')
        ->assertSee('Correcto');

    $resource = ExternalResource::firstWhere('url', 'https://docs.example.test/staging');
    expect($resource->link_status)->toBe(LinkStatus::Ok);

    $page->click('aside button:has-text("Publicar")')
        ->click('[role=dialog] button:has-text("Publicar")')
        ->waitForText('Recurso publicado');

    $page->navigate("/admin/lessons/{$slug}/relations")
        ->select('section:has-text("Recursos para profundizar") select[id$="-add"]', 'Guía de staging (Docs de ejemplo)')
        ->click('button:has-text("Añadir recurso")')
        ->press('Guardar cambios')
        ->waitForText('Relaciones guardadas')
        ->assertNoJavaScriptErrors();

    $this->actingAs(User::factory()->create());

    visit("/lessons/{$slug}")
        ->assertSee('Para profundizar')
        ->assertSee('Guía de staging')
        ->assertNoJavaScriptErrors();
});
