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
