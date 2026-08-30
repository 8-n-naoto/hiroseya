<?php

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 料理の価格バリエーション。
 *
 * 「みそ煮込み / みそ煮込みセット」「みそかつ定食 / 単品」のように
 * 同じ料理に複数の価格があるため、価格は必ずこちらに持つ。
 */
#[Fillable([
    'dish_id', 'label', 'price', 'price_excluding_tax',
    'service_type', 'is_default', 'sort_order',
])]
class DishVariant extends Model
{
    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'price' => 'integer',
            'price_excluding_tax' => 'integer',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    public function scopeForService(Builder $query, ServiceType|string $type): Builder
    {
        return $query->where('service_type', $type instanceof ServiceType ? $type->value : $type);
    }

    /** "1,700円" の形。 */
    public function formattedPrice(): string
    {
        return number_format($this->price).'円';
    }

    /** 一覧での表示名。label が無ければ料理名だけ。 */
    public function displayLabel(): string
    {
        return $this->label ?: '';
    }
}
