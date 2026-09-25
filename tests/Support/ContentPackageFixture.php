<?php

namespace Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

/**
 * Writes a small, valid content package to a temporary directory so tests
 * can break exactly one thing at a time.
 */
final class ContentPackageFixture
{
    public readonly string $path;

    private function __construct()
    {
        $this->path = sys_get_temp_dir().'/content-fixture-'.Str::random(10).'/demo';
        (new Filesystem)->ensureDirectoryExists($this->path);
    }

    public static function valid(): self
    {
        $fixture = new self;

        $fixture->yaml('roadmap.yaml', [
            'key' => 'demo', 'slug' => 'demo', 'title' => 'Demo', 'locale' => 'es', 'unlock_policy' => 'ADVISORY',
            'mastery_threshold' => 90, 'status' => 'PUBLISHED', 'summary' => 'Roadmap de prueba.',
        ]);
        $fixture->markdown('tracks/01-base/track.md', [
            'key' => 'base', 'slug' => 'base', 'title' => 'Base', 'difficulty' => 'BEGINNER', 'status' => 'PUBLISHED',
            'summary' => 'Track base.', 'why_it_matters' => 'Porque sí.', 'depends_on' => [],
        ], 'Descripción del track.');
        $fixture->markdown('tracks/02-avanzado/track.md', [
            'key' => 'avanzado', 'slug' => 'avanzado', 'title' => 'Avanzado', 'difficulty' => 'ADVANCED', 'status' => 'PUBLISHED',
            'summary' => 'Track avanzado.', 'why_it_matters' => 'Porque sí.',
            'depends_on' => [['track' => 'base', 'kind' => 'REQUIRED', 'min_progress' => 80]],
        ]);
        $fixture->markdown('tracks/01-base/01-intro/module.md', [
            'key' => 'base.intro', 'slug' => 'intro', 'title' => 'Intro', 'status' => 'PUBLISHED', 'summary' => 'Módulo intro.',
        ]);
        $fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'primera']);
        $fixture->lesson('02-segunda', [
            'key' => 'base.segunda', 'slug' => 'segunda',
            'depends_on' => [['lesson' => 'base.primera', 'kind' => 'REQUIRED']],
        ]);
        $fixture->markdown('skills/fundamentos.md', [
            'key' => 'fundamentos', 'name' => 'Fundamentos', 'difficulty' => 'BEGINNER', 'status' => 'PUBLISHED', 'depends_on' => [],
        ], 'Capacidad de trabajar con los fundamentos.');
        $fixture->markdown('skills/avanzada.md', [
            'key' => 'avanzada', 'name' => 'Avanzada', 'difficulty' => 'ADVANCED', 'status' => 'PUBLISHED',
            'depends_on' => [['skill' => 'fundamentos', 'kind' => 'REQUIRED', 'min_progress' => 70]],
        ], 'Capacidad avanzada.');
        $fixture->yaml('resources/base.yaml', [[
            'key' => 'docs-base', 'title' => 'Docs', 'url' => 'https://docs.example.test/base', 'type' => 'DOCUMENTATION',
            'provider' => 'Example', 'is_official' => true, 'language' => 'en', 'description' => 'Documentación.',
        ]]);

        return $fixture;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function lesson(string $file, array $overrides = [], ?string $body = null): self
    {
        return $this->markdown("tracks/01-base/01-intro/{$file}.md", [
            'key' => 'base.'.$file, 'slug' => $file, 'title' => 'Lección '.$file, 'type' => 'CONCEPT',
            'difficulty' => 'BEGINNER', 'estimated_minutes' => 20, 'status' => 'PUBLISHED', 'last_reviewed' => '2026-09-25',
            'summary' => str_repeat('Resumen de la lección con detalle suficiente. ', 3),
            'why_it_matters' => str_repeat('Importa porque se usa en proyectos reales. ', 3),
            'objectives' => ['Explicar el concepto.', 'Aplicarlo en un caso concreto.'],
            'skills' => [['key' => 'fundamentos', 'weight' => 2]],
            'resources' => ['docs-base'],
            'depends_on' => [],
            ...$overrides,
        ], $body ?? self::lessonBody());
    }

    public static function lessonBody(string $extra = ''): string
    {
        $paragraph = str_repeat('El concepto se explica con precisión y un ejemplo mínimo. ', 30);

        return "## Concepto\n\n{$paragraph}\n\n{$extra}\n\n## Práctica\n\nResuelve el ejercicio propuesto.";
    }

    /**
     * @param  array<string, mixed>  $frontMatter
     */
    public function markdown(string $relative, array $frontMatter, string $body = ''): self
    {
        return $this->write($relative, "---\n".Yaml::dump($frontMatter, 4, 2)."---\n{$body}\n");
    }

    /**
     * @param  array<mixed>  $data
     */
    public function yaml(string $relative, array $data): self
    {
        return $this->write($relative, Yaml::dump($data, 4, 2));
    }

    public function write(string $relative, string $contents): self
    {
        $file = "{$this->path}/{$relative}";
        (new Filesystem)->ensureDirectoryExists(dirname($file));
        file_put_contents($file, $contents);

        return $this;
    }

    public function delete(string $relative): self
    {
        unlink("{$this->path}/{$relative}");

        return $this;
    }

    public function cleanup(): void
    {
        (new Filesystem)->deleteDirectory(dirname($this->path));
    }
}
