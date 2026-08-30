<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\HomeRecommendedDish;
use App\Models\HomeSection;
use Illuminate\Database\Seeder;

/**
 * トップページのセクション。
 *
 * 並びは要件どおり。型は config/hiroseya.php で固定し、
 * 中身（画像・見出し・本文・表示/非表示・順序）だけ管理画面から変えられる。
 * 見出しは仮の文言なので公開前に差し替える。
 */
class HomeSectionSeeder extends Seeder
{
    private const SECTIONS = [
        ['hero', 'メインビジュアル', null, null],
        ['catch', 'キャッチコピー', '揖斐の地で、うどんとそばを。', null],
        ['about', '店舗紹介', 'お店について', null],
        ['recommend', 'おすすめ料理', 'おすすめのお品書き', ['limit' => 6]],
        ['news', 'お知らせ', 'お知らせ', ['limit' => 3]],
        ['events', 'イベント', 'イベント', ['limit' => 2, 'ongoing_only' => true]],
        ['hours', '営業時間・所在地', '営業時間・所在地', null],
        ['access', 'アクセス', 'アクセス', null],
        ['social', 'SNS', '広瀬屋の最新情報', null],
        ['cta', 'お問い合わせ・予約', 'お問い合わせ', null],
    ];

    public function run(): void
    {
        foreach (self::SECTIONS as $index => [$key, $type, $title, $options]) {
            HomeSection::updateOrCreate(
                ['key' => $key],
                [
                    'type' => $key,
                    'title' => $title,
                    'is_visible' => true,
                    'sort_order' => $index,
                    'options' => $options,
                ],
            );
        }

        // おすすめ料理の初期選択（管理画面から差し替えられる）。
        if (HomeRecommendedDish::query()->doesntExist()) {
            $slugs = ['hiroseya-gozen', 'miso-nikomi', 'tenzaru-soba', 'katsu-don', 'ten-miso-nikomi', 'kaki-fry-teishoku'];

            foreach ($slugs as $order => $slug) {
                $dish = Dish::where('slug', $slug)->first();

                if ($dish) {
                    HomeRecommendedDish::create(['dish_id' => $dish->id, 'sort_order' => $order]);
                }
            }
        }
    }
}
