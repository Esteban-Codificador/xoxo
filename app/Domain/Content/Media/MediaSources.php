<?php

namespace App\Domain\Content\Media;

use App\Domain\Content\RichContent\RichContent;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\URL;

/**
 * What the reader and the editor need to show the images of some content:
 * a signed URL and the size of each, by media id (ADR-034). Pages send it
 * next to the RichContent, which only stores ids.
 */
final class MediaSources
{
    /**
     * @return array<int, array{url: string, width: int, height: int}>
     */
    public function for(?RichContent ...$documents): array
    {
        $ids = [];

        foreach ($documents as $document) {
            array_push($ids, ...$document?->mediaIds() ?? []);
        }

        if ($ids === []) {
            return [];
        }

        return MediaAsset::query()->whereIn('id', array_unique($ids))->get()
            ->mapWithKeys(fn (MediaAsset $asset) => [$asset->id => $this->source($asset)])
            ->all();
    }

    /**
     * The URL never expires: it only works signed, behind the login, and a
     * stable URL lets the browser cache an image that never changes.
     *
     * @return array{url: string, width: int, height: int}
     */
    public function source(MediaAsset $asset): array
    {
        return [
            'url' => URL::signedRoute('media.show', ['media' => $asset->id], absolute: false),
            'width' => (int) $asset->width,
            'height' => (int) $asset->height,
        ];
    }
}
