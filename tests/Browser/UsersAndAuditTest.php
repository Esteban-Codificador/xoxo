<?php

use App\Enums\Role;
use App\Models\User;

/*
| Accounts and audit in a real browser (step 5): an admin finds an account,
| gives it a role and finds that change, before → after, in the audit log.
*/

it('gives a role and shows the change in the audit log', function () {
    $this->actingAs(staff(Role::Admin));
    $learner = User::factory()->create(['name' => 'Lucía Aprendiz'])->assignRole(Role::Student->value);

    $page = visit('/admin')
        ->click('a:has-text("Usuarios")')
        ->assertPathIs('/admin/users')
        ->fill('q', 'aprendiz')
        ->press('Filtrar')
        ->assertSee('Cuentas: 1')
        ->click('[aria-label="Ver la cuenta de Lucía Aprendiz"]')
        ->assertPathIs("/admin/users/{$learner->id}/edit")
        ->click('input[name=role][value=EDITOR]')
        ->press('Guardar rol')
        ->waitForText('Rol de Lucía Aprendiz actualizado: Editor.');

    expect($learner->fresh()->getRoleNames()->all())->toBe(['EDITOR']);

    $page->click('[data-sidebar=menu-button][href="/admin/audit"]')
        ->assertPathIs('/admin/audit')
        ->assertSee('asignó un rol a Lucía Aprendiz')
        ->click('summary:has-text("Cambios (1)")')
        ->assertSee('STUDENT')
        ->assertSee('EDITOR')
        ->assertNoJavaScriptErrors();
});

it('locks the role of the account in use', function () {
    $admin = staff(Role::Admin);
    $this->actingAs($admin);

    visit("/admin/users/{$admin->id}/edit")
        ->assertSee('No puedes cambiar tu propio rol')
        ->assertDontSee('Guardar rol')
        ->assertNoJavaScriptErrors();
});
