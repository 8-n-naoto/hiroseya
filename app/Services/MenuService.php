<?php

namespace App\Services;

use App\Enums\ServiceType;
use App\Models\Dish;
use App\Models\DishCategory;
use Illuminate\Support\Collection;

/**
 * お品書きの組み立て。
 *
 * 重要なのは「カテゴリは並べ方のヒントであって、絞り込みの正ではない」こと。
 * カツ丼・天丼は『丼もの』（店内）カテゴリに属したまま持ち帰り価格を持つ。
 * したがって、お持ち帰りのページは
 *   「takeout カテゴリの料理」ではなく「takeout の価格を持つ料理」
 * で集める。ここを間違えると、実際に持ち帰れる品がページから消える。
 */
class MenuService
{
    /**
     * 指定の提供区分で出せる料理を、カテゴリごとにまとめて返す。
     *
     * 返すのは DishCategory のコレクションで、各カテゴリの dishes リレーションに
     * 該当する料理だけを差し込んである（views と JSON-LD が同じ形を使えるようにする）。
     *
     * @return Collection<int, DishCategory>
     */
    public function grouped(ServiceType $service): Collection
    {
        $dishes = Dish::listable()
            ->forService($service)
            ->with(['variants', 'mainImage', 'allergens'])
            ->ordered()
            ->get();

        if ($dishes->isEmpty()) {
            return collect();
        }

        $byCategory = $dishes->groupBy('category_id');

        $categories = DishCategory::query()
            ->visible()
            ->whereIn('id', $byCategory->keys()->filter())
            ->get()
            // その区分のカテゴリを先に、残りを後に。並びは sort_order。
            ->sortBy(fn (DishCategory $category) => [
                $category->service_type === $service ? 0 : 1,
                $category->sort_order,
                $category->id,
            ])
            ->values();

        foreach ($categories as $category) {
            $category->setRelation('dishes', $byCategory->get($category->id, collect()));
        }

        // カテゴリ未設定の料理も落とさない。管理画面での設定漏れが
        // 「サイトから消える」形で現れると、店舗側は原因に気づけない。
        $uncategorized = $byCategory->get(null);

        if ($uncategorized && $uncategorized->isNotEmpty()) {
            $other = new DishCategory(['name' => 'その他', 'slug' => 'other', 'sort_order' => 999]);
            $other->setRelation('dishes', $uncategorized);
            $categories->push($other);
        }

        return $categories;
    }

    /** その区分に出せる料理が 1 品でもあるか。タブの出し分けに使う。 */
    public function hasAny(ServiceType $service): bool
    {
        return Dish::listable()->forService($service)->exists();
    }

    /**
     * トップページのおすすめ。
     *
     * 手で選んだ home_recommended_dishes を優先し、
     * 足りない分を is_recommended から補う。公開直後に
     * 「おすすめが空」という状態を作らないための保険。
     *
     * @return Collection<int, Dish>
     */
    public function recommended(int $limit = 6): Collection
    {
        $picked = Dish::query()
            ->listable()
            ->whereIn('id', fn ($query) => $query->select('dish_id')->from('home_recommended_dishes'))
            ->with(['variants', 'mainImage'])
            ->get()
            ->sortBy(fn (Dish $dish) => \App\Models\HomeRecommendedDish::query()
                ->where('dish_id', $dish->id)->value('sort_order') ?? 999)
            ->values();

        if ($picked->count() >= $limit) {
            return $picked->take($limit);
        }

        $fallback = Dish::listable()
            ->recommended()
            ->whereNotIn('id', $picked->pluck('id'))
            ->with(['variants', 'mainImage'])
            ->ordered()
            ->limit($limit - $picked->count())
            ->get();

        return $picked->concat($fallback)->take($limit);
    }
}
