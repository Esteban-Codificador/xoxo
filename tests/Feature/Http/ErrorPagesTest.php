<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

it('renders unknown pages with the Inertia error page', function () {
    $this->get('/no-existe')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/error')
            ->where('status', 404));
});

it('keeps JSON errors for JSON requests', function () {
    $this->getJson('/no-existe')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('renders server errors and maintenance with the error page outside debug', function (int $status) {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/_test/failure', fn () => abort($status));

    $this->get('/_test/failure')
        ->assertStatus($status)
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/error')
            ->where('status', $status));
})->with([500, 503]);

it('leaves server errors to the debug page in debug mode', function () {
    config(['app.debug' => true]);
    Route::middleware('web')->get('/_test/failure', fn () => throw new RuntimeException('boom'));

    $this->get('/_test/failure')
        ->assertStatus(500)
        ->assertSee('boom')
        ->assertDontSee('data-page="app"', false);
});

it('uses the language of the user', function () {
    $this->actingAs(User::factory()->create(['locale' => 'en']))
        ->get(route('dashboard'))
        ->assertSee('<html lang="en"', false)
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

it('defaults to Spanish, including validation messages', function () {
    $this->get(route('login'))
        ->assertSee('<html lang="es"', false)
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'es'));

    $this->post(route('login.store'), ['email' => '', 'password' => ''])
        ->assertSessionHasErrors(['email' => 'El campo correo electrónico es obligatorio.']);
});
