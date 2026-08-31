<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Enums\ServiceType;
use App\Models\Dish;
use App\Models\DishCategory;
use App\Models\DishVariant;
use Illuminate\Database\Seeder;

/**
 * 料理の仮データ。
 *
 * 出典:
 *   店内飲食  … 料理写真/看板用写真/金額.xlsx
 *   お持ち帰り … 料理写真/メニュー原本2019(増税後）/お持ち帰りメニュー.xlsx
 *
 * ★価格は 2019〜2021 年頃のもので、現行価格ではない。
 *   準備中モードを解除する前に必ず現行価格へ更新すること。
 *
 * この Seeder は、価格を dishes ではなく dish_variants に持たせる設計が
 * なぜ必要かをそのまま示している:
 *   - かつ丼 / 天丼 … 同じ料理で店内と持ち帰りの価格が違う（1レコードで両方持つ）
 *   - みそヒレかつ丼 … 二枚 / 三枚 で価格が違う
 *   - ライス、ポテトフライ … 小 / 中 / 大
 *   - ヒレかつ … おろし / みそ
 */
class DishSeeder extends Seeder
{
    /**
     * 'variants' の各要素: [label, price, service, default]
     * service を省略すると店内飲食。
     */
    private const DISHES = [
        // ---------------- 温かい麺 ----------------
        ['hot-noodles', 'あんかけうどん', 'ankake-udon', [['', 880]]],
        ['hot-noodles', 'かつとじうどん', 'katsutoji-udon', [['', 920]]],
        ['hot-noodles', 'カレーうどん', 'curry-udon', [['', 880]], ['detail' => true]],
        ['hot-noodles', 'きつねうどん', 'kitsune-udon', [['', 720]]],
        ['hot-noodles', 'けんちんうどん', 'kenchin-udon', [['', 880]]],
        ['hot-noodles', '天とじうどん', 'tentoji-udon', [['', 980]]],
        ['hot-noodles', '天ぷらうどん', 'tempura-udon', [['', 920]]],
        ['hot-noodles', '麺セットうどん', 'men-set-udon', [['', 920]]],

        // ---------------- 冷たい麺 ----------------
        ['cold-noodles', 'ころうどん', 'koro-udon', [['', 850]], ['detail' => true]],
        ['cold-noodles', 'ざるそば', 'zaru-soba', [['', 690]], ['detail' => true]],
        ['cold-noodles', 'たぬきそば', 'tanuki-soba', [['', 660]]],
        ['cold-noodles', 'ヒレカツころうどん', 'hirekatsu-koro-udon', [['', 1170]]],
        ['cold-noodles', '天ざるそば', 'tenzaru-soba', [['', 1450]], ['detail' => true]],

        // ---------------- 煮込み ----------------
        ['nikomi', 'みそ煮込み', 'miso-nikomi', [['', 910]], ['recommended' => true, 'detail' => true]],
        ['nikomi', '天入りみそ煮込み', 'ten-miso-nikomi', [['', 1320]], ['detail' => true]],
        ['nikomi', 'すき焼き煮込み', 'sukiyaki-nikomi', [['', 1140]]],
        ['nikomi', 'カレー煮込み', 'curry-nikomi', [['', 970]]],

        // ---------------- 丼もの（持ち帰り価格を併せ持つものがある） ----------------
        ['donburi', 'カツ丼', 'katsu-don', [
            ['', 880, ServiceType::DineIn, true],
            ['お持ち帰り', 900, ServiceType::Takeout],
        ], ['detail' => true]],
        ['donburi', '天丼', 'ten-don', [
            ['', 970, ServiceType::DineIn, true],
            ['お持ち帰り', 980, ServiceType::Takeout],
        ], ['detail' => true]],
        ['donburi', 'エビ丼', 'ebi-don', [['', 1050]]],
        ['donburi', 'みそひれかつ丼', 'miso-hirekatsu-don', [['', 1130]], ['detail' => true]],
        ['donburi', '山かけ鮪丼', 'yamakake-maguro-don', [['', 1180]]],
        ['donburi', '鉄火丼', 'tekka-don', [['', 1180]]],

        // ---------------- 定食物 ----------------
        ['teishoku', '広瀬屋御膳', 'hiroseya-gozen', [['', 1700]], ['recommended' => true, 'detail' => true]],
        ['teishoku', 'エビフライ定食', 'ebi-fry-teishoku', [['', 1370]]],
        ['teishoku', 'ヒレカツ定食', 'hirekatsu-teishoku', [['', 1370]], ['detail' => true]],
        ['teishoku', 'かつなべ定食', 'katsunabe-teishoku', [['', 1220]]],
        ['teishoku', 'から揚げ定食', 'karaage-teishoku', [['', 1220]]],
        ['teishoku', 'みそかつ定食', 'misokatsu-teishoku', [['', 1220]]],
        ['teishoku', '牛なべ定食', 'gyunabe-teishoku', [['', 1220]]],
        ['teishoku', 'コロッケ定食', 'korokke-teishoku', [['', 820]]],

        // ---------------- 冬限定 ----------------
        ['winter', 'カキフライ定食', 'kaki-fry-teishoku', [['', 1380]], ['limited' => true]],
        ['winter', 'カキの玉子とじ定食', 'kaki-tamagotoji-teishoku', [['', 1380]], ['limited' => true]],
        ['winter', 'トマト煮込み', 'tomato-nikomi', [['', 970]], ['limited' => true]],
        ['winter', '鳥塩生姜煮込み', 'torishio-shoga-nikomi', [['', 970]], ['limited' => true]],
        ['winter', '豆乳煮込み', 'tonyu-nikomi', [['', 970]], ['limited' => true]],

        // ---------------- 麺セット（温） ----------------
        ['men-set-hot', 'ミニかつ丼とうどん', 'mini-katsudon-udon', [['', 1070]]],
        ['men-set-hot', 'ミニみそひれかつ丼とうどん', 'mini-misohirekatsudon-udon', [['', 1120]]],
        ['men-set-hot', 'ミニ山かけ鮪丼とうどん', 'mini-yamakakedon-udon', [['', 1140]]],
        ['men-set-hot', 'ミニ天丼とうどん', 'mini-tendon-udon', [['', 1140]]],

        // ---------------- 麺セット（冷） ----------------
        ['men-set-cold', 'ミニカツ丼とざるそば', 'mini-katsudon-zaru', [['', 1220]]],
        ['men-set-cold', 'ミニひれかつ丼とざるそば', 'mini-hirekatsudon-zaru', [['', 1270]]],
        ['men-set-cold', 'ミニ山かけ鮪丼とざるそば', 'mini-yamakakedon-zaru', [['', 1290]]],
        ['men-set-cold', 'ミニ天丼とざるそば', 'mini-tendon-zaru', [['', 1290]]],

        // ---------------- お持ち帰り：揚げ物・丼物 ----------------
        ['takeout-donburi', 'みそヒレかつ丼', 'miso-hirekatsu-don-takeout', [
            ['二枚', 950, ServiceType::Takeout, true],
            ['三枚', 1150, ServiceType::Takeout],
        ], ['detail' => true]],
        ['takeout-donburi', '上かつ丼', 'jo-katsu-don', [['', 1150, ServiceType::Takeout]]],
        ['takeout-donburi', '上天丼', 'jo-ten-don', [['', 1200, ServiceType::Takeout]]],
        ['takeout-donburi', '牛丼', 'gyu-don', [['', 830, ServiceType::Takeout]]],
        ['takeout-donburi', '親子丼', 'oyako-don', [['', 840, ServiceType::Takeout]]],
        ['takeout-donburi', '玉子丼', 'tamago-don', [['', 740, ServiceType::Takeout]]],
        ['takeout-donburi', 'からあげ', 'karaage-takeout', [['', 900, ServiceType::Takeout]]],
        ['takeout-donburi', 'エビフライ', 'ebi-fry-takeout', [['', 1090, ServiceType::Takeout]]],
        ['takeout-donburi', 'カキフライ', 'kaki-fry-takeout', [['', 1090, ServiceType::Takeout]], ['limited' => true]],
        ['takeout-donburi', 'ヒレかつ', 'hirekatsu-takeout', [
            ['おろし', 1090, ServiceType::Takeout, true],
            ['みそ', 1090, ServiceType::Takeout],
        ]],
        ['takeout-donburi', 'おろしかつ', 'oroshi-katsu', [['', 980, ServiceType::Takeout]]],
        ['takeout-donburi', 'みそかつ', 'misokatsu-takeout', [['', 980, ServiceType::Takeout]]],

        // ---------------- お持ち帰り：ライス ----------------
        ['takeout-rice', 'ライス', 'rice', [
            ['小', 170, ServiceType::Takeout],
            ['中', 220, ServiceType::Takeout, true],
            ['大', 270, ServiceType::Takeout],
        ]],

        // ---------------- お持ち帰り：おつまみ ----------------
        ['takeout-otsumami', 'いか焼き', 'ika-yaki', [['', 500, ServiceType::Takeout]]],
        ['takeout-otsumami', '枝豆', 'edamame', [['', 400, ServiceType::Takeout]]],
        ['takeout-otsumami', '手羽元から揚げ', 'tebamoto-karaage', [['', 570, ServiceType::Takeout]]],
        ['takeout-otsumami', 'ごぼうから揚げ', 'gobo-karaage', [['', 500, ServiceType::Takeout]]],
        ['takeout-otsumami', 'いかから揚げ', 'ika-karaage', [['', 570, ServiceType::Takeout]]],
        ['takeout-otsumami', 'みそ串カツ', 'miso-kushikatsu', [['二本', 270, ServiceType::Takeout]]],
        ['takeout-otsumami', 'ポテトフライ', 'potato-fry', [
            ['小', 290, ServiceType::Takeout, true],
            ['大', 490, ServiceType::Takeout],
        ]],
    ];

    /** 冬限定・季節商品の説明に添える注記。 */
    private const LIMITED_NOTE = '冬季限定のお品書きです。提供期間は管理画面から設定できます。';

    /*
     * 詳細ページを持つ品に入れておく仮の文。
     *
     * 詳細ページはあっても中身が空だと「作りかけ」に見え、店舗側も
     * どこを直せばよいか分からない。仮であることを明記した文を入れて、
     * 書き換える場所が一目で分かるようにしておく。
     */
    private const DETAIL_NOTE = '＊仮の紹介文です。管理画面の「お品書き」から、お店の言葉に書き換えてください。';

    private const DETAIL_BODY = <<<'TEXT'
        ＊ここは詳細ページの本文です。仮の文が入っています。

        管理画面の「お品書き」→ 該当の料理 →「詳細ページ」→「詳細本文」から書き換えられます。
        素材の産地、だしの取り方、おすすめの召し上がり方など、この料理でしか書けないことを入れてください。
        検索から来た方が最後まで読む理由になり、検索結果での評価にもつながります。
        TEXT;

    public function run(): void
    {
        $categories = DishCategory::pluck('id', 'slug');
        $order = 0;

        foreach (self::DISHES as $row) {
            [$categorySlug, $name, $slug, $variants] = $row;
            $flags = $row[4] ?? [];

            $dish = Dish::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $categories[$categorySlug] ?? null,
                    'name' => $name,
                    // 詳細ページを持つ品は、説明文と本文が空だと「作りかけ」に見える。
                    // 仮の文であることを明記したうえで入れておく。
                    'description' => match (true) {
                        (bool) ($flags['limited'] ?? false) => self::LIMITED_NOTE,
                        (bool) ($flags['detail'] ?? false) => self::DETAIL_NOTE,
                        default => null,
                    },
                    'body' => ($flags['detail'] ?? false) ? self::DETAIL_BODY : null,
                    'is_recommended' => $flags['recommended'] ?? false,
                    'is_limited' => $flags['limited'] ?? false,
                    'has_detail_page' => $flags['detail'] ?? false,
                    'status' => PublishStatus::Published,
                    'sort_order' => $order++,
                ],
            );

            $dish->variants()->delete();

            foreach ($variants as $index => $variant) {
                $label = $variant[0] ?? '';
                $price = $variant[1];
                $service = $variant[2] ?? ServiceType::DineIn;
                $isDefault = $variant[3] ?? (count($variants) === 1);

                DishVariant::create([
                    'dish_id' => $dish->id,
                    'label' => $label !== '' ? $label : null,
                    'price' => $price,
                    'service_type' => $service,
                    'is_default' => (bool) $isDefault,
                    'sort_order' => $index,
                ]);
            }
        }

        $this->command?->info(sprintf(
            '料理 %d 件 / 価格バリエーション %d 件を投入しました（価格は仮の値です）。',
            Dish::count(),
            DishVariant::count(),
        ));
    }
}
