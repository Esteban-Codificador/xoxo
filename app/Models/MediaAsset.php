<?php

namespace App\Models;

use App\Enums\MediaKind;
use Carbon\CarbonImmutable;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A stored file used by the content (ADR-034). Rows are content-addressed
 * and never change: the same bytes are always the same asset.
 *
 * @property int $id
 * @property MediaKind $kind
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property int|null $width
 * @property int|null $height
 * @property string $checksum
 * @property int|null $uploaded_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['kind', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'width', 'height', 'checksum', 'uploaded_by'])]
class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    /** The image types accepted anywhere, with the extension each is stored with. */
    public const array IMAGE_EXTENSIONS = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];

    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function extension(): string
    {
        return self::IMAGE_EXTENSIONS[$this->mime_type] ?? 'bin';
    }

    /**
     * Its file inside a content package: named after the checksum, so the
     * name only changes when the bytes do.
     */
    public function packagePath(): string
    {
        return 'media/'.substr($this->checksum, 0, 16).'.'.$this->extension();
    }
}
