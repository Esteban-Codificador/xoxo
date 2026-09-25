<?php

namespace App\Domain\Content\Package;

use DateTimeInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads a content package directory (docs/content-architecture.md §6):
 *
 *   roadmap.yaml
 *   tracks/NN-track/track.md
 *   tracks/NN-track/NN-module/module.md
 *   tracks/NN-track/NN-module/NN-lesson.md
 *   skills/*.md
 *   resources/*.yaml
 *
 * Structural problems are collected as issues instead of aborting, so a
 * single run reports everything that is wrong.
 */
final class PackageReader
{
    private const string ORDERED_NAME = '/^(\d{2})-[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const string FRONT_MATTER = '/\A---\R(.*?)\R---[ \t]*(?:\R(.*))?\z/s';

    private ContentPackage $package;

    public function read(string $path): ContentPackage
    {
        $path = rtrim($path, '/');
        $this->package = new ContentPackage(basename($path), $path);

        if (! is_dir($path)) {
            $this->package->addIssue($path, 'el directorio del paquete no existe.');

            return $this->package;
        }

        $roadmapKey = $this->readRoadmap();
        $this->readTracks($roadmapKey);
        $this->readSkills();
        $this->readResources();

        return $this->package;
    }

    private function readRoadmap(): ?string
    {
        $data = $this->yamlFile('roadmap.yaml');

        if (! is_array($data)) {
            $this->package->addIssue('roadmap.yaml', 'falta o no es un objeto YAML.');

            return null;
        }

        $key = $this->key($data, 'roadmap.yaml');

        if ($key !== null) {
            $description = $data['description'] ?? null;
            unset($data['description']);
            $this->package->add(new SourceEntity(EntityType::Roadmap, $key, 'roadmap.yaml', $data, is_string($description) ? $description : null));
        }

        return $key;
    }

    private function readTracks(?string $roadmapKey): void
    {
        foreach ($this->orderedDirectories('tracks') as [$trackDir, $trackPosition]) {
            $track = $this->markdownEntity(EntityType::Track, "{$trackDir}/track.md", $trackPosition, $roadmapKey);

            foreach ($this->orderedDirectories($trackDir) as [$moduleDir, $modulePosition]) {
                $module = $this->markdownEntity(EntityType::Module, "{$moduleDir}/module.md", $modulePosition, $track?->key);

                foreach ($this->files($moduleDir, '*.md') as $file) {
                    if (basename($file) === 'module.md') {
                        continue;
                    }

                    if (preg_match(self::ORDERED_NAME, basename($file, '.md'), $match) !== 1) {
                        $this->package->addIssue($file, 'el nombre debe tener la forma NN-slug.md.');

                        continue;
                    }

                    $this->markdownEntity(EntityType::Lesson, $file, (int) $match[1], $module?->key);
                }
            }

            $this->rejectUnexpectedFiles($trackDir, ['track.md']);
        }
    }

    private function readSkills(): void
    {
        foreach ($this->files('skills', '*.md') as $file) {
            $this->markdownEntity(EntityType::Skill, $file, 0, null);
        }
    }

    private function readResources(): void
    {
        foreach ($this->files('resources', '*.yaml') as $file) {
            $items = $this->yamlFile($file);

            if (! is_array($items) || ! array_is_list($items)) {
                $this->package->addIssue($file, 'debe ser una lista YAML de recursos.');

                continue;
            }

            foreach ($items as $index => $item) {
                $location = "{$file}#".($index + 1);

                if (! is_array($item)) {
                    $this->package->addIssue($location, 'cada recurso debe ser un objeto.');

                    continue;
                }

                $key = $this->key($item, $location);

                if ($key !== null) {
                    $this->package->add(new SourceEntity(EntityType::Resource, $key, $location, $item, null, $index));
                }
            }
        }
    }

    private function markdownEntity(EntityType $type, string $file, int $position, ?string $parentKey): ?SourceEntity
    {
        $contents = @file_get_contents("{$this->package->path}/{$file}");

        if ($contents === false) {
            $this->package->addIssue($file, 'falta el archivo.');

            return null;
        }

        if (preg_match(self::FRONT_MATTER, $contents, $match) !== 1) {
            $this->package->addIssue($file, 'falta el front matter YAML delimitado por "---".');

            return null;
        }

        $data = $this->parseYaml($match[1], $file);

        if (! is_array($data)) {
            $this->package->addIssue($file, 'el front matter debe ser un objeto YAML.');

            return null;
        }

        $key = $this->key($data, $file);

        if ($key === null) {
            return null;
        }

        $entity = new SourceEntity($type, $key, $file, $data, trim($match[2] ?? ''), $position, $parentKey);
        $this->package->add($entity);

        return $entity;
    }

    /**
     * @param  array<mixed>  $data
     */
    private function key(array $data, string $file): ?string
    {
        $key = $data['key'] ?? null;

        if (! is_string($key) || $key === '') {
            $this->package->addIssue($file, 'falta "key".');

            return null;
        }

        return $key;
    }

    private function yamlFile(string $file): mixed
    {
        $contents = @file_get_contents("{$this->package->path}/{$file}");

        return $contents === false ? null : $this->parseYaml($contents, $file);
    }

    private function parseYaml(string $yaml, string $file): mixed
    {
        try {
            return $this->normalizeDates(Yaml::parse($yaml, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE | Yaml::PARSE_DATETIME));
        } catch (ParseException $exception) {
            $this->package->addIssue($file, 'YAML inválido: '.$exception->getMessage());

            return null;
        }
    }

    /**
     * Unquoted YAML dates (last_reviewed: 2026-09-25) become Y-m-d strings
     * instead of Unix timestamps.
     */
    private function normalizeDates(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return is_array($value) ? array_map($this->normalizeDates(...), $value) : $value;
    }

    /**
     * @return list<array{0: string, 1: int}> Relative directory and its NN position.
     */
    private function orderedDirectories(string $relative): array
    {
        $directories = [];

        foreach (glob("{$this->package->path}/{$relative}/*", GLOB_ONLYDIR) ?: [] as $directory) {
            $name = basename($directory);

            if (preg_match(self::ORDERED_NAME, $name, $match) !== 1) {
                $this->package->addIssue("{$relative}/{$name}", 'el directorio debe llamarse NN-slug.');

                continue;
            }

            $directories[] = ["{$relative}/{$name}", (int) $match[1]];
        }

        return $directories;
    }

    /**
     * @return list<string>
     */
    private function files(string $relative, string $pattern): array
    {
        $files = glob("{$this->package->path}/{$relative}/{$pattern}") ?: [];
        sort($files);

        return array_map(fn (string $file) => "{$relative}/".basename($file), array_values(array_filter($files, 'is_file')));
    }

    /**
     * @param  list<string>  $allowed
     */
    private function rejectUnexpectedFiles(string $relative, array $allowed): void
    {
        foreach (glob("{$this->package->path}/{$relative}/*") ?: [] as $path) {
            if (is_file($path) && ! in_array(basename($path), $allowed, true)) {
                $this->package->addIssue("{$relative}/".basename($path), 'archivo inesperado: las lecciones van dentro de un directorio de módulo.');
            }
        }
    }
}
