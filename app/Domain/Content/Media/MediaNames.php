<?php

namespace App\Domain\Content\Media;

use App\Models\MediaAsset;

/**
 * Package paths of stored images and back (ADR-034): what the Markdown of
 * an export, or of a version diff, writes for each media id. Only paths it
 * handed out resolve back, which is what an export checks.
 */
final class MediaNames
{
    /** @var array<int, string> */
    private array $paths = [];

    /** @var array<string, int> */
    private array $ids = [];

    /** @var array<int, MediaAsset> */
    private array $assets = [];

    public function pathOf(int $mediaId): string
    {
        if (! isset($this->paths[$mediaId])) {
            $asset = MediaAsset::query()->find($mediaId);
            $path = $asset?->packagePath() ?? "media/falta-{$mediaId}";

            if ($asset !== null) {
                $this->assets[$mediaId] = $asset;
                $this->ids[$path] = $mediaId;
            }

            $this->paths[$mediaId] = $path;
        }

        return $this->paths[$mediaId];
    }

    public function idOf(string $path): int|string
    {
        return $this->ids[$path] ?? 'no corresponde a ninguna imagen guardada.';
    }

    /**
     * Stored images whose path was handed out, by package path.
     *
     * @return array<string, MediaAsset>
     */
    public function assets(): array
    {
        $assets = [];

        foreach ($this->assets as $id => $asset) {
            $assets[$this->paths[$id]] = $asset;
        }

        return $assets;
    }
}
