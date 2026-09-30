<?php

use App\Enums\Role;
use App\Models\Lesson;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\ParseMultipartBody;

/*
| Images in lessons (phase 6, ADR-034), end to end: an editor uploads one
| from the editor with its alternative text, publishes, and a learner sees
| it, loaded through its signed URL.
*/

beforeEach(function () {
    ParseMultipartBody::register();
    $this->artisan('content:import')->assertSuccessful();
    $this->file = sys_get_temp_dir().'/'.Str::random(8).'-ramas.png';
    file_put_contents($this->file, imageBytes(640, 360, 'png', [40, 120, 80]));
});

afterEach(function () {
    @unlink($this->file);
});

/**
 * Waits until the image with this alternative text has loaded and returns
 * its natural width (0 if the browser could not load it).
 */
function loadedWidth(mixed $page, string $alt): int
{
    return (int) $page->script(<<<JS
        () => new Promise((resolve) => {
            const img = document.querySelector('img[alt="{$alt}"]');
            if (!img) return resolve(-1);
            img.scrollIntoView();
            const done = () => resolve(img.naturalWidth);
            if (img.complete && img.naturalWidth > 0) return done();
            img.addEventListener('load', done);
            img.addEventListener('error', () => resolve(0));
        })
        JS);
}

it('uploads an image in the editor, publishes it and a learner sees it', function () {
    $alt = 'Una rama que sale de main y vuelve con un merge';
    $this->actingAs(staff(Role::Editor));

    $page = visit('/admin/lessons/ramas-merge-y-rebase/edit')
        ->waitForText('Requisitos para publicar');

    // The caret after the first paragraph, through TipTap (see CreateAndPublishTest).
    $page->script(<<<'JS'
        () => {
            const editor = document.querySelector('.tiptap').editor;
            let end = null;
            editor.state.doc.forEach((node, offset) => {
                if (end === null && node.type.name === 'paragraph') {
                    end = offset + node.nodeSize - 1;
                }
            });
            editor.chain().focus().setTextSelection(end).run();
        }
        JS);

    $page->click('button[aria-label="Imagen"]')
        ->waitForText('Subir e insertar')
        ->attach('input[type="file"]', $this->file)
        ->fill('[role="dialog"] input:not([type="file"])', $alt)
        ->press('Subir e insertar')
        ->waitForText("Imagen · {$alt}");

    expect(loadedWidth($page, $alt))->toBe(640);
    // Inserting is an edit to save, not a save.
    $page->assertSee('Tienes cambios sin guardar.');

    $page->press('Guardar cambios')
        ->waitForText('Todo guardado.')
        ->fill('change_note', 'Añade un diagrama de ramas.')
        ->press('Publicar versión 2')
        ->waitForText('Versión 2 publicada')
        ->assertNoJavaScriptErrors();

    $asset = MediaAsset::query()->sole();
    expect(Lesson::firstWhere('slug', 'ramas-merge-y-rebase')->publishedVersion->body->mediaIds())->toBe([$asset->id]);

    // A learner reads it, with the image in place.
    $this->actingAs(User::factory()->create());
    $lessonPage = visit('/lessons/ramas-merge-y-rebase')
        ->waitForText('Ramas, merge y rebase');

    expect(loadedWidth($lessonPage, $alt))->toBe(640);
    $lessonPage->assertNoJavaScriptErrors();
});
