<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Media\ImageProcessor;
use App\Domain\Content\Media\InvalidImage;
use App\Domain\Content\Media\MediaStore;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final readonly class UploadImage
{
    public function __construct(
        private ImageProcessor $images,
        private MediaStore $store,
    ) {}

    /**
     * An image uploaded from the editor: checked and re-encoded before it
     * is stored, so only pixels drawn by GD ever reach the disk.
     *
     * @throws ValidationException
     */
    public function handle(UploadedFile $file, User $uploader): MediaAsset
    {
        try {
            $image = $this->images->sanitize((string) $file->get());
        } catch (InvalidImage $exception) {
            throw ValidationException::withMessages([
                'image' => __('media.invalid', ['reason' => $exception->getMessage()]),
            ]);
        }

        return $this->store->put($image->bytes, $image->info, $file->getClientOriginalName(), $uploader->id);
    }
}
