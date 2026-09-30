<?php

namespace App\Domain\Content\Media;

/** Bytes written by GD: nothing of the uploaded file survives but its pixels. */
final readonly class SanitizedImage
{
    public function __construct(
        public string $bytes,
        public ImageInfo $info,
    ) {}
}
