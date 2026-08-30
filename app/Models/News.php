<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title', 'slug', 'published_at', 'excerpt', 'body', 'main_media_id', 'status',
])]
class News extends Model
{
    use HasSeoMeta, Publishable, SoftDeletes;

    protected $table = 'news';

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function mainImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'main_media_id');
    }

    /** 公開中かつ公開日時を過ぎたものだけ（未来日時は予約公開）。 */
    public function scopeListable(Builder $query): Builder
    {
        return $query->published()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    public function defaultSeoTitle(): string
    {
        return $this->title;
    }
}
