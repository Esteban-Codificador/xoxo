<?php

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Skill;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->python = Skill::factory()->create(['name' => 'Python', 'slug' => 'python']);
    $this->numpy = Skill::factory()->create(['name' => 'NumPy', 'slug' => 'numpy']);
    $this->pandas = Skill::factory()->create(['name' => 'Pandas', 'slug' => 'pandas']);
    $this->numpy->prerequisites()->attach($this->python, ['kind' => 'REQUIRED', 'min_progress' => 70]);
    $this->pandas->prerequisites()->attach($this->numpy, ['kind' => 'REQUIRED', 'min_progress' => 70]);
});

it('creates a skill as a draft with a unique slug', function () {
    $editor = staff(Role::Editor);

    $this->actingAs($editor)
        ->post('/admin/skills', ['name' => 'Git', 'slug' => 'git', 'description' => 'Control de versiones.', 'difficulty' => 'BEGINNER'])
        ->assertSessionHasNoErrors();

    $git = Skill::firstWhere('slug', 'git');
    expect($git->status)->toBe(ContentStatus::Draft)->and($git->created_by)->toBe($editor->id);

    $this->actingAs($editor)
        ->post('/admin/skills', ['name' => 'Git otra vez', 'slug' => 'git', 'description' => 'x', 'difficulty' => 'BEGINNER'])
        ->assertSessionHasErrors('slug');
});

it('edits a skill and publishes it', function () {
    $this->actingAs(staff(Role::Editor))
        ->put("/admin/skills/{$this->python->id}", ['name' => 'Python moderno', 'slug' => 'python', 'description' => 'Tipos, módulos y entornos.', 'difficulty' => 'INTERMEDIATE'])
        ->assertSessionHasNoErrors();
    expect($this->python->refresh()->name)->toBe('Python moderno');

    $draft = Skill::factory()->create(['status' => ContentStatus::Draft]);
    $this->actingAs(staff(Role::Editor))->put("/admin/skills/{$draft->id}/status", ['status' => 'PUBLISHED'])->assertSessionHasNoErrors();
    expect($draft->refresh()->status)->toBe(ContentStatus::Published);
});

it('replaces skill prerequisites and rejects cycles', function () {
    $editor = staff(Role::Editor);

    $this->actingAs($editor)
        ->put("/admin/skills/{$this->python->id}/dependencies", ['dependencies' => [
            ['skill_id' => $this->pandas->id, 'kind' => 'RECOMMENDED', 'min_progress' => 50],
        ]])
        ->assertSessionHasErrors(['dependencies' => 'Ese cambio crearía un ciclo de prerrequisitos: Python → Pandas → NumPy → Python.']);

    $this->actingAs($editor)
        ->put("/admin/skills/{$this->pandas->id}/dependencies", ['dependencies' => [
            ['skill_id' => $this->numpy->id, 'kind' => 'REQUIRED', 'min_progress' => 80],
            ['skill_id' => $this->python->id, 'kind' => 'RECOMMENDED', 'min_progress' => 50],
        ]])
        ->assertSessionHasNoErrors();

    expect($this->pandas->prerequisites()->count())->toBe(2)
        ->and(AuditLog::where('auditable_id', $this->pandas->id)->where('auditable_type', 'skill')->where('user_id', $editor->id)->sole()->action)
        ->toBe(AuditAction::Updated);
});

it('lists skills and opens the editor with prerequisites and options', function () {
    $this->actingAs(staff(Role::Editor))
        ->get('/admin/skills')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/skills/index')
            ->has('skills', 3)
            ->where('skills.0.name', 'NumPy')
            ->where('skills.0.prerequisites', ['Python']));

    $this->actingAs(staff(Role::Editor))
        ->get("/admin/skills/{$this->numpy->id}/edit")
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/skills/form')
            ->where('dependencies', [['id' => $this->python->id, 'kind' => 'REQUIRED', 'min_progress' => 70]])
            ->has('dependency_options', 2));
});

it('lets instructors create skills but not edit imported ones', function () {
    $instructor = staff(Role::Instructor);

    $this->actingAs($instructor)->get('/admin/skills/create')->assertOk();
    $this->actingAs($instructor)
        ->put("/admin/skills/{$this->python->id}", ['name' => 'X', 'slug' => 'python', 'description' => 'x', 'difficulty' => 'BEGINNER'])
        ->assertForbidden();
});
