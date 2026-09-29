<?php

use App\Enums\ContentStatus;
use App\Enums\LinkStatus;
use App\Models\ExternalResource;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;

it('checks stored resources and records each result', function () {
    Http::fake([
        'ok.example.test/*' => Http::response('', 200),
        'gone.example.test/*' => Http::response('', 404),
        'flaky.example.test/*' => Http::response('', 503),
    ]);

    $ok = ExternalResource::factory()->create(['url' => 'https://ok.example.test/a']);
    $gone = ExternalResource::factory()->create(['url' => 'https://gone.example.test/b']);
    $flaky = ExternalResource::factory()->create(['url' => 'https://flaky.example.test/c', 'link_status' => LinkStatus::Ok]);
    $archived = ExternalResource::factory()->create(['url' => 'https://gone.example.test/d', 'status' => ContentStatus::Archived]);

    // Archived resources are skipped; a broken link is reported, not a failed run.
    $this->artisan('content:verify-resources')
        ->expectsOutputToContain('3 recurso(s) verificados: 1 roto(s), 1 no concluyente(s).')
        ->assertSuccessful();

    expect($ok->refresh()->link_status)->toBe(LinkStatus::Ok)
        ->and($gone->refresh()->link_status)->toBe(LinkStatus::Broken)
        // Inconclusive (5xx): the previous result stays.
        ->and($flaky->refresh()->link_status)->toBe(LinkStatus::Ok)
        ->and($archived->refresh()->link_status)->toBe(LinkStatus::Unchecked);
});

it('runs every night', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains((string) $event->command, 'content:verify-resources'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('30 3 * * *');
});
