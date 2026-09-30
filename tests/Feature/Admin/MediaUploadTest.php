<?php

use App\Domain\Content\Media\MediaSources;
use App\Domain\Content\RichContent\RichContent;
use App\Domain\Curriculum\Actions\PublishLesson;
use App\Enums\Role;
use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Images of the content (ADR-034): uploaded from the editor, checked and
| re-encoded, stored once per content, served only through signed URLs.
*/

/** A JPEG whose EXIF says "turn me 90° clockwise" (orientation 6), like a phone photo. */
function sidewaysJpeg(int $width, int $height): string
{
    $jpeg = imageBytes($width, $height, 'jpeg');
    // Big-endian TIFF with one IFD0 entry: Orientation (0x0112), SHORT, value 6.
    $tiff = "MM\x00\x2A\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00\x00\x00\x00\x00";
    $exif = "Exif\x00\x00{$tiff}";

    return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
}

function upload(string $name, string $bytes): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $bytes);
}

it('stores an uploaded image and answers with what the editor inserts', function () {
    $response = $this->actingAs(staff(Role::Instructor))
        ->post('/admin/media', ['image' => upload('diagrama.png', imageBytes(300, 200))])
        ->assertCreated();

    $asset = MediaAsset::query()->sole();
    $stored = (string) Storage::disk('local')->get($asset->path);

    expect($response->json())->toMatchArray(['id' => $asset->id, 'width' => 300, 'height' => 200])
        ->and($response->json('url'))->toStartWith("/media/{$asset->id}?signature=")
        ->and($asset->path)->toBe("media/{$asset->checksum}.png")
        ->and($asset->checksum)->toBe(hash('sha256', $stored))
        ->and($asset->size_bytes)->toBe(strlen($stored))
        ->and($asset->original_name)->toBe('diagrama.png')
        ->and($asset->mime_type)->toBe('image/png');
});

it('keeps only the pixels: metadata and trailing payloads are gone', function () {
    $this->actingAs(staff(Role::Editor));

    $this->post('/admin/media', ['image' => upload('shell.png', imageBytes().'<?php system($_GET["c"]); ?>')])->assertCreated();
    $this->post('/admin/media', ['image' => upload('foto.jpg', sidewaysJpeg(80, 40))])->assertCreated();

    [$png, $jpeg] = MediaAsset::query()->orderBy('id')->get()->all();
    $jpegBytes = (string) Storage::disk('local')->get($jpeg->path);

    expect((string) Storage::disk('local')->get($png->path))->not->toContain('<?php')
        ->and($jpegBytes)->not->toContain('Exif')
        // Turned upright, since the EXIF that said how is gone.
        ->and([$jpeg->width, $jpeg->height])->toBe([40, 80])
        ->and(array_slice((array) getimagesizefromstring($jpegBytes), 0, 2))->toBe([40, 80]);
});

it('scales large images down to 2400 px on the longest side', function () {
    $this->actingAs(staff(Role::Editor))
        ->post('/admin/media', ['image' => upload('ancha.webp', imageBytes(3600, 1200, 'webp'))])
        ->assertCreated()
        ->assertJson(['width' => 2400, 'height' => 800]);

    expect(MediaAsset::query()->sole()->mime_type)->toBe('image/webp');
});

it('stores the same image once', function () {
    $this->actingAs(staff(Role::Editor));
    $first = $this->post('/admin/media', ['image' => upload('a.png', imageBytes())])->json('id');
    $second = $this->post('/admin/media', ['image' => upload('otra-vez.png', imageBytes())])->json('id');

    expect($second)->toBe($first)
        ->and(MediaAsset::count())->toBe(1)
        ->and(Storage::disk('local')->allFiles('media'))->toHaveCount(1);
});

it('refuses what is not an accepted image', function (string $name, Closure $bytes, string $message) {
    $this->actingAs(staff(Role::Editor))
        ->postJson('/admin/media', ['image' => upload($name, $bytes())])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image' => $message]);

    expect(MediaAsset::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    // The request rule or the bytes check, whichever sees it first.
    'gif' => ['animacion.gif', fn () => imageBytes(10, 10, 'gif'), 'se admiten imágenes PNG, JPEG o WebP.'],
    'svg named png' => ['logo.png', fn () => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'se admiten imágenes PNG, JPEG o WebP.'],
    'text named png' => ['notas.png', fn () => 'no soy una imagen', 'se admiten imágenes PNG, JPEG o WebP.'],
    'truncated png' => ['rota.png', fn () => substr(imageBytes(200, 200), 0, 120), 'no se pudo leer la imagen'],
    'too wide' => ['panorama.png', fn () => imageBytes(5000, 10), 'mide 5000×10 px; el máximo es 4096 px por lado.'],
]);

it('requires an image', function () {
    $this->actingAs(staff(Role::Editor))
        ->postJson('/admin/media', [])
        ->assertJsonValidationErrors(['image' => 'Elige una imagen.']);
});

it('lets only content authors upload', function () {
    $this->actingAs(staff(Role::Student))
        ->post('/admin/media', ['image' => upload('a.png', imageBytes())])
        ->assertForbidden();

    $this->post('/logout');
    $this->post('/admin/media', ['image' => upload('a.png', imageBytes())])->assertRedirect('/login');

    expect(MediaAsset::count())->toBe(0);
});

it('limits uploads per minute', function () {
    $this->actingAs(staff(Role::Editor));

    for ($i = 0; $i < 30; $i++) {
        $this->post('/admin/media', ['image' => upload('a.png', imageBytes(4, 4))])->assertCreated();
    }

    $this->post('/admin/media', ['image' => upload('a.png', imageBytes(4, 4))])->assertTooManyRequests();
});

it('serves an image only through its signed URL', function () {
    $asset = storedImage();
    $url = app(MediaSources::class)->source($asset)['url'];

    $response = $this->actingAs(staff(Role::Student))->get($url)->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Security-Policy', "default-src 'none'; sandbox");

    expect($response->streamedContent())->toBe(Storage::disk('local')->get($asset->path))
        ->and($response->headers->get('Cache-Control'))->toContain('max-age=31536000')->toContain('private');

    // Unsigned, or the signature of another image.
    $other = storedImage(rgb: [200, 30, 30]);
    $this->get("/media/{$asset->id}")->assertForbidden();
    $this->get(str_replace("/media/{$asset->id}?", "/media/{$other->id}?", $url))->assertForbidden();

    Storage::disk('local')->delete($asset->path);
    $this->get($url)->assertNotFound();
});

it('sends guests to log in instead of serving images', function () {
    $url = app(MediaSources::class)->source(storedImage())['url'];

    $this->get($url)->assertRedirect('/login');
});

it('saves content that shows stored images and refuses unknown ones', function () {
    $editor = staff(Role::Editor);
    $lesson = publishableLesson();
    $image = storedImage();
    $body = fn (int $id) => ['version' => 1, 'doc' => ['type' => 'doc', 'content' => [
        ...$lesson->body->doc['content'],
        ['type' => 'image', 'attrs' => ['mediaId' => $id, 'alt' => 'Esquema de ramas de Git']],
    ]]];

    $this->actingAs($editor)
        ->put("/admin/lessons/{$lesson->slug}", lessonForm($lesson, ['body' => $body(999_999)]))
        ->assertSessionHasErrors(['body' => 'La imagen 999999 no existe: vuelve a subirla.']);

    $this->put("/admin/lessons/{$lesson->slug}", lessonForm($lesson, ['body' => $body($image->id)]))
        ->assertSessionHasNoErrors();

    expect($lesson->fresh()->body->mediaIds())->toBe([$image->id]);

    // An image needs its alternative text.
    $withoutAlt = $body($image->id);
    $withoutAlt['doc']['content'][array_key_last($withoutAlt['doc']['content'])]['attrs']['alt'] = ' ';
    $this->put("/admin/lessons/{$lesson->slug}", lessonForm($lesson, ['body' => $withoutAlt]))
        ->assertSessionHasErrors('body');
});

it('sends every page that shows content the images it needs', function () {
    $image = storedImage(640, 480);
    $withImage = fn (RichContent $content) => RichContent::fromDocument(['type' => 'doc', 'content' => [
        ...$content->doc['content'],
        ['type' => 'image', 'attrs' => ['mediaId' => $image->id, 'alt' => 'Mapa del track']],
    ]]);
    $lesson = publishableLesson();
    $lesson->update(['body' => $withImage($lesson->body)]);
    app(PublishLesson::class)->handle($lesson->refresh());
    $track = $lesson->module->track;
    $track->update(['description' => $withImage(RichContent::empty())]);
    $track->roadmap->update(['description' => $withImage(RichContent::empty())]);

    $source = fn (Assert $page) => $page
        ->where("media.{$image->id}.width", 640)
        ->where("media.{$image->id}.height", 480)
        ->where("media.{$image->id}.url", fn (string $url) => str_starts_with($url, "/media/{$image->id}?signature="));

    $this->actingAs(staff(Role::Student));
    $this->get("/lessons/{$lesson->slug}")->assertInertia($source);
    $this->get("/roadmaps/{$track->roadmap->slug}/tracks/{$track->slug}")->assertInertia($source);
    $this->get("/roadmaps/{$track->roadmap->slug}")->assertInertia($source);

    $this->actingAs(staff(Role::Editor));
    $this->get("/admin/lessons/{$lesson->slug}/edit")->assertInertia($source);
    $this->get("/admin/lessons/{$lesson->slug}/versions/{$lesson->publishedVersion->version}")->assertInertia($source);
    $this->get("/admin/tracks/{$track->id}/edit")->assertInertia($source);
    $this->get("/admin/roadmaps/{$track->roadmap->id}/edit")->assertInertia($source);
});

it('sends an empty map when the content has no images', function () {
    $lesson = publishedLesson();

    $this->actingAs(staff(Role::Student))->get("/lessons/{$lesson->slug}")
        ->assertInertia(fn (Assert $page) => $page->where('media', []));
});
