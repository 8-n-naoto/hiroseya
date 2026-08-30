<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SNS。リンク表示と API 連携は完全に分離する。
 * api_enabled が false でもリンクは表示され、サイトは API に一切依存しない。
 */
#[Fillable([
    'platform', 'display_name', 'url', 'icon_media_id',
    'is_visible', 'sort_order', 'api_enabled', 'api_credentials',
])]
#[Hidden(['api_credentials'])]
class SocialLink extends Model
{
    public const PLATFORMS = [
        'instagram' => 'Instagram',
        'x' => 'X',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'line' => 'LINE',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'api_enabled' => 'boolean',
            'sort_order' => 'integer',
            'api_credentials' => 'encrypted:array',
        ];
    }

    public function icon(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'icon_media_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)
            ->whereNotNull('url')
            ->orderBy('sort_order');
    }

    public function platformLabel(): string
    {
        return $this->display_name ?: (self::PLATFORMS[$this->platform] ?? $this->platform);
    }

    /**
     * 投稿の取得を試みてよいか。
     * ここが false でもリンクは出るため、サイトの表示は API に左右されない。
     */
    public function shouldFetchFeed(): bool
    {
        return $this->api_enabled && filled($this->api_credentials);
    }
}
