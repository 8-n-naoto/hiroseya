<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * トップページのセクション。型は config/hiroseya.php で固定し、中身だけ可変にする。
 */
#[Fillable([
    'key', 'type', 'title', 'subtitle', 'body',
    'media_id', 'media_sp_id', 'is_visible', 'sort_order', 'options',
])]
class HomeSection extends Model
{
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /** スマートフォン用の画像。PC用の横長を切り抜くと料理が切れるため別に持つ。 */
    public function imageSp(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_sp_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function option(string $key, mixed $fallback = null): mixed
    {
        return data_get($this->options, $key, $fallback);
    }

    /** config に書いた型定義。 */
    public function definition(): array
    {
        return config("hiroseya.home_sections.{$this->key}", []);
    }

    public function label(): string
    {
        return $this->definition()['label'] ?? $this->key;
    }

    /** hero のように、非表示にできないセクションか。 */
    public function isLocked(): bool
    {
        return (bool) ($this->definition()['lockable'] ?? false);
    }
}
