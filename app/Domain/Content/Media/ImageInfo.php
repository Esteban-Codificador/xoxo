<?php

namespace App\Domain\Content\Media;

use App\Models\MediaAsset;

final readonly class ImageInfo
{
    public function __construct(
        public string $mime,
        public int $width,
        public int $height,
    ) {}

    public function extension(): string
    {
        return MediaAsset::IMAGE_EXTENSIONS[$this->mime];
    }
}
