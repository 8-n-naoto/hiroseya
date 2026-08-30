<?php

namespace Database\Seeders;

use App\Models\StoreProfile;
use Illuminate\Database\Seeder;

/**
 * 店舗情報。
 *
 * 住所・電話番号・席数・アクセスはポータル掲載値を採用しているが、
 * 屋号と住所は各ポータルで表記が割れている（広瀬屋/廣瀬屋、脛永/和田）。
 * このテーブルが公式サイトの一次情報源になるため、
 * 公開前に必ず店舗へ確認して確定させること。
 *
 * キャッチコピー・紹介文は仮の文章。公開前に差し替える。
 */
class StoreProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profile = StoreProfile::firstOrNew(['id' => 1]);

        $profile->fill([
            'name' => '広瀬屋',
            'name_kana' => 'ひろせや',

            // TODO: 公開前に差し替える（仮のキャッチコピー・紹介文）
            'catch_copy' => '揖斐の地で、うどんとそばを。',
            'description' => "岐阜県揖斐郡揖斐川町のうどん・そば処です。\n"
                ."味噌煮込みうどん、季節の定食、丼もの、お持ち帰り弁当をご用意しております。\n"
                .'※この紹介文は仮の内容です。公開前に管理画面から差し替えてください。',

            // 以下はポータル掲載値。公開前に照合すること。
            'postal_code' => '501-0622',
            'prefecture' => '岐阜県',
            'city' => '揖斐郡揖斐川町',
            'address_line' => '脛永1784-13',
            'tel' => '0585-22-1437',
            'seats' => 48,
            'access_train' => '養老鉄道 揖斐駅から徒歩8分',
            'access_car' => '駐車場あり（台数は要確認）',
            'payment_methods' => ['現金'],
        ])->save();
    }
}
