<?php

namespace Database\Seeders;

use App\Models\SeoMeta;
use Illuminate\Database\Seeder;

/**
 * 固定ページのSEOメタ（仮）。
 *
 * 各ページのタイトルとディスクリプションは公開前に見直す。
 * 未設定でも自動生成にフォールバックするため、空のままでもページは壊れない。
 *
 * 送信完了ページなど、検索結果に出すべきでないページは robots に noindex を入れておく。
 */
class SeoMetaSeeder extends Seeder
{
    private const PAGES = [
        'home' => [
            '広瀬屋｜岐阜県揖斐川町のうどん・そば・味噌煮込み',
            '岐阜県揖斐郡揖斐川町のうどん・そば処「広瀬屋」。味噌煮込みうどん、季節の定食、丼もの、お持ち帰りをご用意しています。',
        ],
        'menu' => [
            'お品書き',
            '広瀬屋のお品書き。温かい麺・冷たい麺・煮込み・丼もの・定食物と、お持ち帰りメニューをご紹介します。',
        ],
        'news' => ['お知らせ', '広瀬屋からの新商品・期間限定商品などのお知らせです。'],
        'events' => ['イベント', '広瀬屋で開催するキャンペーンやイベントのご案内です。'],
        'access' => [
            'アクセス・営業時間',
            '広瀬屋への行き方、営業時間、定休日、駐車場のご案内です。養老鉄道 揖斐駅から徒歩8分。',
        ],
        'contact' => ['お問い合わせ', '広瀬屋へのお問い合わせはこちらのフォームからお願いいたします。'],
        'reservation' => ['ご予約', '広瀬屋のご予約フォームです。店舗が確認したうえで確定のご連絡をいたします。'],
        'privacy' => ['プライバシーポリシー', '広瀬屋の個人情報の取り扱いについてご説明します。'],
    ];

    /** 検索結果に出さないページ。 */
    private const NOINDEX_PAGES = ['contact-complete', 'reservation-complete'];

    public function run(): void
    {
        foreach (self::PAGES as $key => [$title, $description]) {
            SeoMeta::firstOrCreate(
                ['page_key' => $key],
                ['title' => $title, 'description' => $description],
            );
        }

        foreach (self::NOINDEX_PAGES as $key) {
            SeoMeta::firstOrCreate(
                ['page_key' => $key],
                ['robots' => 'noindex,nofollow'],
            );
        }
    }
}
