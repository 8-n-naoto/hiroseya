<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Event;
use App\Models\News;
use Illuminate\Database\Seeder;

/**
 * お知らせ・イベントのサンプル。
 *
 * 一覧・詳細・並び順・公開制御の動作確認用で、内容はすべて仮。
 * 公開前に削除するか、実際のお知らせに差し替えること。
 */
class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        if (News::query()->doesntExist()) {
            News::create([
                'title' => '【サンプル】冬季限定メニューをはじめました',
                'slug' => 'sample-winter-menu',
                'published_at' => now()->subDays(3),
                'excerpt' => 'カキフライ定食、カキの玉子とじ定食など、冬季限定のお品書きをご用意しました。',
                'body' => "これはサンプルのお知らせです。公開前に削除するか、実際の内容に差し替えてください。\n\n"
                    ."冬季限定のお品書きをご用意しました。\n"
                    .'※内容・価格は仮のものです。',
                'status' => PublishStatus::Published,
            ]);

            News::create([
                'title' => '【サンプル】お持ち帰りメニューのご案内',
                'slug' => 'sample-takeout',
                'published_at' => now()->subDays(10),
                'excerpt' => 'お弁当・丼もののお持ち帰りを承っております。',
                'body' => "これはサンプルのお知らせです。\n\nお持ち帰りは店頭・お電話にて承ります。",
                'status' => PublishStatus::Published,
            ]);

            News::create([
                'title' => '【サンプル】下書きのお知らせ',
                'slug' => 'sample-draft',
                'published_at' => null,
                'excerpt' => '下書き状態のお知らせは公開サイトに出ません。',
                'body' => '公開制御の確認用です。',
                'status' => PublishStatus::Draft,
            ]);
        }

        if (Event::query()->doesntExist()) {
            Event::create([
                'title' => '【サンプル】開催中のキャンペーン',
                'slug' => 'sample-ongoing-campaign',
                'starts_on' => today()->subDays(5),
                'ends_on' => today()->addDays(25),
                'excerpt' => '開催中として表示されるサンプルイベントです。',
                'body' => 'これはサンプルのイベントです。公開前に削除するか、実際の内容に差し替えてください。',
                'status' => PublishStatus::Published,
                'sort_order' => 0,
            ]);

            Event::create([
                'title' => '【サンプル】終了したイベント',
                'slug' => 'sample-finished-event',
                'starts_on' => today()->subDays(60),
                'ends_on' => today()->subDays(30),
                'excerpt' => '終了扱いで表示されるサンプルイベントです。',
                'body' => '開催期間による出し分けの確認用です。',
                'status' => PublishStatus::Published,
                'sort_order' => 1,
            ]);
        }
    }
}
