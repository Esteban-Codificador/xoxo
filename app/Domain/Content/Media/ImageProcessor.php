<?php

namespace App\Domain\Content\Media;

use App\Models\MediaAsset;
use finfo;
use GdImage;

/**
 * Checks and re-encodes images (architecture §9, ADR-034). The type comes
 * from the bytes (finfo), never from the name or the declared MIME, and
 * the size is read from the header before anything is decoded.
 */
final class ImageProcessor
{
    public const int MAX_BYTES = 5 * 1024 * 1024;

    /** Largest side accepted: the decoded image must fit in memory. */
    public const int MAX_SIDE = 4096;

    /** Largest side stored: the reading column is ~700 px, so this covers 3× screens. */
    public const int STORED_SIDE = 2400;

    private const int QUALITY = 85;

    /**
     * @throws InvalidImage
     */
    public function inspect(string $bytes): ImageInfo
    {
        if ($bytes === '') {
            throw new InvalidImage('el archivo está vacío.');
        }

        if (strlen($bytes) > self::MAX_BYTES) {
            throw new InvalidImage('pesa más de '.(self::MAX_BYTES / 1024 / 1024).' MB.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);

        if (! is_string($mime) || ! isset(MediaAsset::IMAGE_EXTENSIONS[$mime])) {
            throw new InvalidImage('solo se admiten imágenes PNG, JPEG o WebP.');
        }

        $size = @getimagesizefromstring($bytes);

        if ($size === false || $size['mime'] !== $mime) {
            throw new InvalidImage('el archivo no es una imagen válida.');
        }

        [$width, $height] = $size;

        if ($width < 1 || $height < 1 || $width > self::MAX_SIDE || $height > self::MAX_SIDE) {
            throw new InvalidImage("mide {$width}×{$height} px; el máximo es ".self::MAX_SIDE.' px por lado.');
        }

        return new ImageInfo($mime, $width, $height);
    }

    /**
     * Inspects the image and decodes it once, which catches truncated or
     * animated files that only fail when read. The bytes are kept as they
     * are (imports: the checksum names the file in the package).
     *
     * @throws InvalidImage
     */
    public function verify(string $bytes): ImageInfo
    {
        $info = $this->inspect($bytes);
        imagedestroy($this->decode($bytes));

        return $info;
    }

    /**
     * New bytes drawn by GD from the decoded pixels: metadata (EXIF, GPS)
     * and anything hidden in the file are gone. Photos are turned upright
     * and large images scaled down; the format stays the same.
     *
     * @throws InvalidImage
     */
    public function sanitize(string $bytes): SanitizedImage
    {
        $info = $this->inspect($bytes);
        $this->ensureMemory();

        $image = $this->decode($bytes);

        if ($info->mime === 'image/jpeg') {
            $image = $this->upright($image, $bytes);
        }

        $image = $this->scaledDown($image);
        $encoded = $this->encode($image, $info->mime);
        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        return new SanitizedImage($encoded, new ImageInfo($info->mime, $width, $height));
    }

    private function decode(string $bytes): GdImage
    {
        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            throw new InvalidImage('no se pudo leer la imagen (¿está dañada o es animada?).');
        }

        return $image;
    }

    /**
     * A 4096 × 4096 image takes 64 MB once decoded, plus its scaled copy.
     */
    private function ensureMemory(): void
    {
        $limit = (string) ini_get('memory_limit');

        if ($limit !== '-1' && $this->bytesOf($limit) < 256 * 1024 * 1024) {
            ini_set('memory_limit', '256M');
        }
    }

    private function bytesOf(string $value): int
    {
        $number = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * Cameras store photos sideways and say how to turn them in EXIF, which
     * the re-encoding drops: the rotation is applied to the pixels instead.
     */
    private function upright(GdImage $image, string $bytes): GdImage
    {
        $stream = fopen('php://memory', 'r+b');

        if ($stream === false) {
            return $image;
        }

        fwrite($stream, $bytes);
        rewind($stream);
        $exif = @exif_read_data($stream);
        fclose($stream);

        // [counter-clockwise degrees, then mirror] per EXIF orientation.
        [$angle, $mirror] = match (is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1) {
            2 => [0, true],
            3 => [180, false],
            4 => [180, true],
            5 => [270, true],
            6 => [270, false],
            7 => [90, true],
            8 => [90, false],
            default => [0, false],
        };

        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);

            if ($rotated !== false) {
                imagedestroy($image);
                $image = $rotated;
            }
        }

        if ($mirror) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }

    private function scaledDown(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = self::STORED_SIDE / max($width, $height);

        if ($scale >= 1) {
            return $image;
        }

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $scaled = imagecreatetruecolor($newWidth, $newHeight);

        if ($scaled === false) {
            return $image;
        }

        // Keep transparency: copy the alpha channel instead of blending it.
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, (int) imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $scaled;
    }

    private function encode(GdImage $image, string $mime): string
    {
        $stream = fopen('php://memory', 'w+b');

        if ($stream === false) {
            throw new InvalidImage('no se pudo procesar la imagen.');
        }

        imagesavealpha($image, true);

        $written = match ($mime) {
            'image/png' => imagepng($image, $stream, 6),
            'image/webp' => imagewebp($image, $stream, self::QUALITY),
            default => imageinterlace($image, true) !== false && imagejpeg($image, $stream, self::QUALITY),
        };

        rewind($stream);
        $bytes = (string) stream_get_contents($stream);
        fclose($stream);

        if (! $written || $bytes === '') {
            throw new InvalidImage('no se pudo procesar la imagen.');
        }

        return $bytes;
    }
}
