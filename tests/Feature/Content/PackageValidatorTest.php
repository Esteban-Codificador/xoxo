<?php

use App\Domain\Content\Package\PackageReader;
use App\Domain\Content\Package\PackageValidator;
use Tests\Support\ContentPackageFixture;

function packageIssues(ContentPackageFixture $fixture): string
{
    $package = app(PackageReader::class)->read($fixture->path);

    return implode("\n", array_map(strval(...), app(PackageValidator::class)->validate($package)));
}

beforeEach(function () {
    $this->fixture = ContentPackageFixture::valid();
});

afterEach(function () {
    $this->fixture->cleanup();
});

it('accepts a valid package', function () {
    expect(packageIssues($this->fixture))->toBe('');
});

it('accepts the real initial package', function () {
    $package = app(PackageReader::class)->read(base_path('content/ai-engineer'));

    expect(app(PackageValidator::class)->validate($package))->toBe([]);
});

it('reports duplicated keys', function () {
    $this->fixture->lesson('03-copia', ['key' => 'base.primera', 'slug' => 'copia']);

    expect(packageIssues($this->fixture))->toContain('03-copia.md: clave duplicada "base.primera"');
});

it('reports references to entities that do not exist', function () {
    $this->fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'primera', 'skills' => [['key' => 'fantasma', 'weight' => 1]], 'resources' => ['nada']]);

    expect(packageIssues($this->fixture))
        ->toContain('referencia a skill inexistente "fantasma"')
        ->toContain('referencia a resource inexistente "nada"');
});

it('reports dependency cycles', function () {
    $this->fixture->lesson('01-primera', [
        'key' => 'base.primera', 'slug' => 'primera',
        'depends_on' => [['lesson' => 'base.segunda', 'kind' => 'RECOMMENDED']],
    ]);

    expect(packageIssues($this->fixture))->toContain('ciclo de dependencias: base.primera → base.segunda → base.primera');
});

it('reports invalid field values', function () {
    $this->fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'Primera Lección', 'type' => 'QUIZ', 'estimated_minutes' => 0]);

    expect(packageIssues($this->fixture))
        ->toContain('slug field format is invalid')
        ->toContain('type is invalid')
        ->toContain('estimated minutes field must be between 1 and 600');
});

it('only accepts https resources', function () {
    $this->fixture->yaml('resources/base.yaml', [[
        'key' => 'docs-base', 'title' => 'Docs', 'url' => 'http://docs.example.test/base', 'type' => 'DOCUMENTATION',
        'provider' => 'Example', 'is_official' => true, 'language' => 'en', 'description' => 'Documentación.',
    ]]);

    expect(packageIssues($this->fixture))->toContain('url field must be a valid URL');
});

it('rejects markdown outside the rich content schema', function () {
    $this->fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'primera'], ContentPackageFixture::lessonBody('<script>alert(1)</script>'));

    expect(packageIssues($this->fixture))->toContain('contenido: línea 5: el HTML crudo no está permitido');
});

it('applies the publishing contract to published lessons only', function () {
    $this->fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'primera', 'summary' => 'Corto'], "## Concepto\n\nPoco texto.");
    $this->fixture->lesson('03-borrador', ['key' => 'base.borrador', 'slug' => 'borrador', 'status' => 'DRAFT', 'summary' => 'Corto'], "## Concepto\n\nPoco texto.");

    $issues = packageIssues($this->fixture);

    expect($issues)
        ->toContain('01-primera.md: no cumple el contrato de publicación: El resumen debe tener al menos 80 caracteres')
        ->toContain('01-primera.md: no cumple el contrato de publicación: Falta la práctica')
        ->not->toContain('03-borrador.md');
});

it('requires published parents for published children', function () {
    $this->fixture->markdown('tracks/01-base/track.md', [
        'key' => 'base', 'slug' => 'base', 'title' => 'Base', 'difficulty' => 'BEGINNER', 'status' => 'DRAFT',
        'summary' => 'Track base.', 'why_it_matters' => 'Porque sí.',
    ]);

    expect(packageIssues($this->fixture))->toContain('module.md: un módulo publicado necesita el track publicado');
});

it('reports structural problems in the directory tree', function () {
    $this->fixture->write('tracks/sin-numero/track.md', "---\nkey: x\n---\n")
        ->write('tracks/01-base/01-intro/leccion-sin-numero.md', "---\nkey: y\n---\n")
        ->write('tracks/01-base/suelta.md', 'texto')
        ->write('skills/sin-front-matter.md', 'solo texto');

    expect(packageIssues($this->fixture))
        ->toContain('tracks/sin-numero: el directorio debe llamarse NN-slug')
        ->toContain('leccion-sin-numero.md: el nombre debe tener la forma NN-slug.md')
        ->toContain('tracks/01-base/suelta.md: archivo inesperado')
        ->toContain('skills/sin-front-matter.md: falta el front matter');
});
