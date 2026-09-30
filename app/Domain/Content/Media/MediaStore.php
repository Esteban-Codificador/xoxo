<?php

namespace App\Domain\Content\Media;

use App\Enums\MediaKind;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;

/**
 * Stores checked image bytes as a media asset (ADR-034). Content-addressed:
 * the file is named after its sha256, so the same bytes, uploaded twice or
 * imported after an export, are the same asset and the same file.
 */
final class MediaStore
{
    /**
     * @param  bool  $writeFile  False in a dry-run import: the row is rolled back and no file may stay behind.
     */
    public function put(string $bytes, ImageInfo $info, string $originalName, ?int $uploadedBy, bool $writeFile = true): MediaAsset
    {
        $checksum = hash('sha256', $bytes);
        $disk = (string) config('filesystems.media_disk', 'local');
        $path = "media/{$checksum}.{$info->extension()}";

        $existing = MediaAsset::query()->where('checksum', $checksum)->first();

        if ($existing !== null) {
            // A database restored without its files heals on the next upload or import.
            if ($writeFile && ! Storage::disk($existing->disk)->exists($existing->path)) {
                Storage::disk($existing->disk)->put($existing->path, $bytes);
            }

            return $existing;
        }

        if ($writeFile) {
            Storage::disk($disk)->put($path, $bytes);
        }

        // Two uploads of the same image at once: the second one gets the first row.
        return MediaAsset::query()->createOrFirst(['checksum' => $checksum], [
            'kind' => MediaKind::Image,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $this->safeName($originalName),
            'mime_type' => $info->mime,
            'size_bytes' => strlen($bytes),
            'width' => $info->width,
            'height' => $info->height,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    /** Only shown in the CMS, but never trusted: no path, no control characters. */
    private function safeName(string $name): string
    {
        $name = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $name)));

        return mb_substr($name === '' ? 'imagen' : $name, 0, 255);
    }
}
