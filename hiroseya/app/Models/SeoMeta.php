<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'seoable_type', 'seoable_id', 'page_key', 'title', 'description', 'keywords',
    'canonical', 'robots', 'og_title', 'og_description', 'og_image_media_id',
])]
class SeoMeta extends Model
{
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_media_id');
    }

    /** 固定ページ（home / menu / access ...）のメタを取り出す。 */
    public static function forPage(string $pageKey): ?self
    {
        return static::where('page_key', $pageKey)->first();
    }
}
