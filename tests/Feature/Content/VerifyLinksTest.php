<?php

use App\Domain\Content\Links\LinkChecker;
use App\Enums\LinkStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\ContentPackageFixture;

beforeEach(fn () => Http::preventStrayRequests());

it('marks reachable links as OK', function () {
    Http::fake(['docs.example.test/*' => Http::response('', 200)]);

    expect((new LinkChecker)->check('https://docs.example.test/base')->status)->toBe(LinkStatus::Ok);
});

it('marks 404 and 410 as broken', function (int $status) {
    Http::fake(['docs.example.test/*' => Http::response('', $status)]);

    expect((new LinkChecker)->check('https://docs.example.test/base')->isBroken())->toBeTrue();
})->with([404, 410]);

it('treats server errors and timeouts as inconclusive, not broken', function () {
    Http::fake(['docs.example.test/caido' => Http::response('', 503)]);
    Http::fake(['docs.example.test/lento' => fn () => throw new ConnectionException('timeout')]);

    $checker = new LinkChecker;

    expect($checker->check('https://docs.example.test/caido')->status)->toBeNull()
        ->and($checker->check('https://docs.example.test/lento')->status)->toBeNull()
        ->and($checker->check('https://docs.example.test/lento')->error)->toBe('timeout');
});

it('retries with GET when a server rejects HEAD', function () {
    Http::fake(fn (Request $request) => Http::response('', $request->method() === 'HEAD' ? 405 : 200));

    expect((new LinkChecker)->check('https://docs.example.test/base')->status)->toBe(LinkStatus::Ok);
    Http::assertSent(fn (Request $request) => $request->method() === 'GET');
});

it('passes the command when no link is broken', function () {
    $fixture = ContentPackageFixture::valid();
    Http::fake(['docs.example.test/*' => Http::response('', 200)]);

    $this->artisan('content:verify-links', ['path' => $fixture->path])
        ->expectsOutputToContain('1 enlace(s) verificados, 0 no concluyente(s), ninguno roto.')
        ->assertSuccessful();

    $fixture->cleanup();
});

it('fails the command when a link is broken', function () {
    $fixture = ContentPackageFixture::valid();
    Http::fake(['docs.example.test/*' => Http::response('', 404)]);

    $this->artisan('content:verify-links', ['path' => $fixture->path])
        ->expectsOutputToContain('1 enlace(s) roto(s)')
        ->assertFailed();

    $fixture->cleanup();
});

it('checks the videos of the package and those written in the text with oEmbed', function () {
    $fixture = ContentPackageFixture::valid();
    $fixture->yaml('videos/videos.yaml', [['key' => 'intuicion', 'url' => 'fake-abcdef', 'title' => 'La intuición', 'language' => 'en']]);
    $fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'primera'], ContentPackageFixture::lessonBody("```video\nprovider: youtube\nid: fake-privad\n```"));
    Http::fake([
        'docs.example.test/*' => Http::response('', 200),
        '*fake-abcdef*' => Http::response(['title' => 'La intuición']),
        '*fake-privad*' => Http::response('Unauthorized', 401),
    ]);

    $this->artisan('content:verify-links', ['path' => $fixture->path])
        ->expectsOutputToContain('YouTube fake-privad: el video es privado o no permite insertarlo')
        ->expectsOutputToContain('1 enlace(s) roto(s) (404/410) o video(s) que no se pueden ver.')
        ->assertFailed();

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'fake-abcdef'));
    $fixture->cleanup();
});

it('checks the videos written in the questions of a quiz', function () {
    $fixture = ContentPackageFixture::valid();
    $fixture->yaml('quizzes/base.primera.yaml', [
        'key' => 'base.primera', 'lesson' => 'base.primera', 'title' => 'Quiz',
        'questions' => [[
            'type' => 'TRUE_FALSE', 'answer' => true,
            'prompt' => "Mira el video:\n\n```video\nprovider: youtube\nid: fake-borrad\n```",
            'explanation' => 'Así es.',
        ]],
    ]);
    Http::fake([
        'docs.example.test/*' => Http::response('', 200),
        '*fake-borrad*' => Http::response('Not Found', 404),
    ]);

    $this->artisan('content:verify-links', ['path' => $fixture->path])
        ->expectsOutputToContain('texto de quizzes/base.primera.yaml')
        ->assertFailed();

    $fixture->cleanup();
});
