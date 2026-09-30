<?php

use App\Enums\LinkStatus;
use App\Enums\Role;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Http;

/*
| Videos in the CMS on the real sample curriculum (ADR-035): look a link up
| on YouTube (oEmbed, faked here: the test never reaches the network), add
| it to the catalog, publish it, attach it to a lesson and the learner
| plays it from the lesson.
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
    // No thumbnail: the browser would try to fetch it from YouTube.
    Http::fake(['www.youtube.com/oembed*' => Http::response(['title' => 'Git branching explained', 'author_name' => 'Canal de ejemplo'])]);
});

it('looks a video up, adds it, attaches it to a lesson and the learner plays it', function () {
    $this->actingAs(staff(Role::Editor));
    $slug = 'ramas-merge-y-rebase';

    $page = visit('/admin/videos')
        ->click('a:has-text("Nuevo video")')
        ->assertPathIs('/admin/videos/create')
        ->fill('url', 'https://youtu.be/fake-abcdef')
        ->click('button:has-text("Buscar en YouTube")')
        ->waitForText('Disponible en YouTube')
        ->assertValue('title', 'Git branching explained')
        ->assertValue('instructor', 'Canal de ejemplo')
        ->fill('duration', '12:34')
        ->press('Guardar cambios')
        ->waitForText('Video añadido como borrador')
        ->assertSee('Disponible');

    expect(Video::query()->sole())
        ->external_id->toBe('fake-abcdef')
        ->duration_seconds->toBe(754)
        ->link_status->toBe(LinkStatus::Ok);

    $page->click('aside button:has-text("Publicar")')
        ->click('[role=dialog] button:has-text("Publicar")')
        ->waitForText('Video publicado');

    $page->navigate("/admin/lessons/{$slug}/relations")
        ->select('section:has-text("Videos") select[id$="-add"]', 'Git branching explained (Canal de ejemplo)')
        ->click('button:has-text("Añadir video")')
        ->press('Guardar cambios')
        ->waitForText('Relaciones guardadas')
        ->assertNoJavaScriptErrors();

    $this->actingAs(User::factory()->create());

    $lesson = visit("/lessons/{$slug}")
        ->assertSee('Git branching explained')
        ->assertSee('12:34')
        ->assertNoJavaScriptErrors()
        ->click('button[aria-label="Reproducir: Git branching explained"]');

    $lesson->assertAttribute('iframe[title="Git branching explained"]', 'src', 'https://www.youtube-nocookie.com/embed/fake-abcdef?autoplay=1');
});
