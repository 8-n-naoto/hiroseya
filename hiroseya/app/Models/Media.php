<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'disk', 'path', 'original_name', 'mime', 'size',
    'width', 'height', 'alt', 'caption', 'uploaded_by',
])]
class Media extends Model
{
    protected $table = 'media';

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * 派生サイズのURL。アップロード時に生成した lg / md / sm を参照する。
     * 例: dishes/misonikomi-01.jpg -> dishes/misonikomi-01_md.webp
     */
    public function variantUrl(string $size = 'md', string $ext = 'webp'): string
    {
        $base = preg_replace('/\.[^.]+$/', '', $this->path);

        return Storage::disk($this->disk)->url("{$base}_{$size}.{$ext}");
    }

    /** alt が未入力の画像は運用の抜けなので、管理画面で目立たせる。 */
    public function isMissingAlt(): bool
    {
        return blank($this->alt);
    }
}
