<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a media file from the private disk. The signed URL is the
 * permission: only pages showing the content hand it out (MediaSources).
 */
class ShowMediaController extends Controller
{
    public function __invoke(MediaAsset $media): StreamedResponse
    {
        $disk = Storage::disk($media->disk);

        abort_unless($disk->exists($media->path), 404);

        return $disk->response($media->path, "imagen-{$media->id}.{$media->extension()}", [
            'Content-Type' => $media->mime_type,
            // The bytes behind an id never change.
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
