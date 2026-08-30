<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Enums\ServiceType;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[Fillable([
    'category_id', 'name', 'name_kana', 'slug', 'description', 'body', 'main_media_id',
    'is_recommended', 'is_new', 'is_limited', 'is_sold_out',
    'available_from', 'available_to', 'has_detail_page', 'status', 'sort_order',
])]
class Dish extends Model
{
    use HasSeoMeta, Publishable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'is_recommended' => 'boolean',
            'is_new' => 'boolean',
            'is_limited' => 'boolean',
            'is_sold_out' => 'boolean',
            'has_detail_page' => 'boolean',
            'available_from' => 'date',
            'available_to' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DishCategory::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(DishVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function mainImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'main_media_id');
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'dish_media', 'dish_id', 'media_id')
            ->withPivot('sort_order')
            ->orderBy('dish_media.sort_order');
    }

    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class)->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | スコープ
    |--------------------------------------------------------------------------
    */

    /**
     * 提供期間内のものだけ。
     *
     * 冬季限定・夏季おすすめ・9月限定が常態のため、この自動判定が無いと
     * 店舗が毎回手で表示を切り替えることになり、必ず出しっぱなしになる。
     */
    public function scopeAvailable(Builder $query, ?string $on = null): Builder
    {
        $date = $on ?: today()->toDateString();

        return $query
            ->where(fn (Builder $q) => $q->whereNull('available_from')->orWhereDate('available_from', '<=', $date))
            ->where(fn (Builder $q) => $q->whereNull('available_to')->orWhereDate('available_to', '>=', $date));
    }

    /** 公開サイトに出せる状態か（公開中かつ提供期間内）。 */
    public function scopeListable(Builder $query): Builder
    {
        return $query->published()->available();
    }

    public function scopeRecommended(Builder $query): Builder
    {
        return $query->where('is_recommended', true);
    }

    /** 指定の提供区分の価格を持つ料理だけ。 */
    public function scopeForService(Builder $query, ServiceType|string $type): Builder
    {
        $value = $type instanceof ServiceType ? $type->value : $type;

        return $query->whereHas('variants', fn (Builder $q) => $q->where('service_type', $value));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | 表示用
    |--------------------------------------------------------------------------
    */

    /** 提供区分ごとの価格一覧。 */
    public function variantsFor(ServiceType|string $type): Collection
    {
        $value = $type instanceof ServiceType ? $type->value : $type;

        return $this->variants
            ->filter(fn (DishVariant $variant) => $variant->service_type->value === $value)
            ->values();
    }

    /** 一覧に出す代表価格。is_default が無ければ最安を使う。 */
    public function defaultVariant(ServiceType|string $type = ServiceType::DineIn): ?DishVariant
    {
        $variants = $this->variantsFor($type);

        return $variants->firstWhere('is_default', true) ?? $variants->sortBy('price')->first();
    }

    public function isAvailableToday(): bool
    {
        $today = today();

        if ($this->available_from && $today->lt($this->available_from)) {
            return false;
        }

        if ($this->available_to && $today->gt($this->available_to)) {
            return false;
        }

        return true;
    }

    public function defaultSeoTitle(): string
    {
        return $this->name;
    }

    public function defaultSeoDescription(): string
    {
        if (filled($this->description)) {
            return mb_substr($this->description, 0, 120);
        }

        $store = config('hiroseya.settings.site.site_name.default', '広瀬屋');
        $category = $this->category?->name;

        return trim("{$this->name}｜{$category}　{$store}のお品書き");
    }
}
