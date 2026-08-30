<?php

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * 公開状態を持つモデル（料理・お知らせ・イベント）の共通スコープ。
 */
trait Publishable
{
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published->value);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Draft->value);
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published;
    }
}
