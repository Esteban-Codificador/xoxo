<?php

use App\Models\User;

/*
| users.last_active_at follows the user's requests, a write at most every
| few minutes (shown in the admin's account list).
*/

it('records when a user was last active, at most once per window', function () {
    // As a factory builds it: without the column loaded.
    $user = User::factory()->create();
    $this->travelTo('2026-09-29 10:00:00');

    $this->actingAs($user)->get('/dashboard')->assertOk();
    expect($user->fresh()->last_active_at->toDateTimeString())->toBe('2026-09-29 10:00:00');

    $this->travelTo('2026-09-29 10:04:00');
    $this->actingAs($user->fresh())->get('/dashboard');
    expect($user->fresh()->last_active_at->toDateTimeString())->toBe('2026-09-29 10:00:00');

    $this->travelTo('2026-09-29 10:06:00');
    $updatedAt = $user->fresh()->updated_at;
    $this->actingAs($user->fresh())->get('/dashboard');
    expect($user->fresh()->last_active_at->toDateTimeString())->toBe('2026-09-29 10:06:00')
        ->and($user->fresh()->updated_at->equalTo($updatedAt))->toBeTrue();
});

it('records nothing for guests', function () {
    $this->get('/login')->assertOk();

    expect(User::query()->whereNotNull('last_active_at')->exists())->toBeFalse();
});
