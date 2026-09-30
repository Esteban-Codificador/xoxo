<?php

namespace App\Domain\Content\Package;

use App\Domain\Content\Media\ImageInfo;
use App\Domain\Content\Media\ImageProcessor;
use App\Domain\Content\Media\InvalidImage;
use App\Domain\Content\Media\MediaStore;
use Closure;

/**
 * Images of a content package (ADR-034): files in media/, referenced from
 * the Markdown as `![alt](media/<file>)`, a path relative to the package
 * root. Each method gives MarkdownToRichContent the resolver its caller
 * needs.
 */
final readonly class PackageMedia
{
    public const string PATH_PATTERN = '/^media\/[A-Za-z0-9][A-Za-z0-9._-]*\.(png|jpe?g|webp)$/';

    public function __construct(
        private ImageProcessor $images,
        private MediaStore $store,
    ) {}

    /**
     * Validation: every image must be a readable file of the package. Ids
     * are stand-ins; nothing is stored.
     *
     * @return Closure(string): (int|string)
     */
    public function checker(ContentPackage $package): Closure
    {
        $checked = [];

        return function (string $path) use ($package, &$checked): int|string {
            if (! isset($checked[$path])) {
                $file = $this->file($package, $path);
                $checked[$path] = is_string($file) ? $file : self::standIn($path);
            }

            return $checked[$path];
        };
    }

    /**
     * Import: each file becomes a media asset, as it is (its checksum names
     * it in the next export). A dry run stores no file.
     *
     * @return Closure(string): (int|string)
     */
    public function importer(ContentPackage $package, bool $dryRun): Closure
    {
        $ids = [];

        return function (string $path) use ($package, $dryRun, &$ids): int|string {
            if (isset($ids[$path])) {
                return $ids[$path];
            }

            $file = $this->file($package, $path);

            if (is_string($file)) {
                return $file;
            }

            [$bytes, $info] = $file;

            return $ids[$path] = $this->store->put($bytes, $info, basename($path), null, writeFile: ! $dryRun)->id;
        };
    }

    /**
     * Comparing two Markdown texts: the same path gives the same id in any
     * process, so equal documents hash equal. Never stored.
     *
     * @return Closure(string): (int|string)
     */
    public static function comparable(): Closure
    {
        return fn (string $path): int|string => preg_match(self::PATH_PATTERN, $path) === 1
            ? self::standIn($path)
            : 'la ruta debe ser media/<archivo>.png, .jpg o .webp, relativa a la raíz del paquete.';
    }

    private static function standIn(string $path): int
    {
        return (int) hexdec(substr(hash('sha256', $path), 0, 12)) + 1;
    }

    /**
     * @return array{0: string, 1: ImageInfo}|string The bytes and what they are, or why they cannot be used.
     */
    private function file(ContentPackage $package, string $path): array|string
    {
        if (preg_match(self::PATH_PATTERN, $path) !== 1) {
            return 'la ruta debe ser media/<archivo>.png, .jpg o .webp, relativa a la raíz del paquete.';
        }

        if (! $package->hasMedia($path)) {
            return 'el archivo no existe en el paquete.';
        }

        $bytes = (string) file_get_contents("{$package->path}/{$path}");

        try {
            return [$bytes, $this->images->verify($bytes)];
        } catch (InvalidImage $exception) {
            return $exception->getMessage();
        }
    }
}
