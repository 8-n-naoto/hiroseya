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
    'title', 'slug', 'starts_on', 'ends_on', 'excerpt', 'body',
    'main_media_id', 'status', 'sort_order',
])]
class Event extends Model
{
    use HasSeoMeta, Publishable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function mainImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'main_media_id');
    }

    public function scopeListable(Builder $query): Builder
    {
        return $query->published();
    }

    /** 開催中（開始日を過ぎ、終了日を過ぎていない）。日付未設定は常に開催中扱い。 */
    public function scopeOngoing(Builder $query): Builder
    {
        $today = today()->toDateString();

        return $query
            ->where(fn (Builder $q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today))
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today));
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereNotNull('ends_on')->whereDate('ends_on', '<', today());
    }

    public function isOngoing(): bool
    {
        $today = today();

        if ($this->starts_on && $today->lt($this->starts_on)) {
            return false;
        }

        if ($this->ends_on && $today->gt($this->ends_on)) {
            return false;
        }

        return true;
    }

    /** "2026年9月1日〜9月30日" の形。 */
    public function periodLabel(): string
    {
        if (! $this->starts_on && ! $this->ends_on) {
            return '';
        }

        $from = $this->starts_on?->format('Y年n月j日') ?? '';
        $to = $this->ends_on?->format('Y年n月j日') ?? '';

        return $from && $to ? "{$from}〜{$to}" : ($from ?: $to);
    }

    public function defaultSeoTitle(): string
    {
        return $this->title;
    }
}
