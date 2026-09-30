<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Actions\UploadImage;
use App\Domain\Content\Media\MediaSources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadImageRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/** Upload from the editor's image dialog: answers with what the editor inserts. */
class MediaController extends Controller
{
    public function store(UploadImageRequest $request, UploadImage $upload, MediaSources $sources): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $asset = $upload->handle($request->file('image'), $user);

        return response()->json(['id' => $asset->id, ...$sources->source($asset)], 201);
    }
}
