<?php

/**
 * 広瀬屋サイト固有の設定。
 *
 * ここに書くのは「コードの都合で決まる定数」だけ。
 * 店舗が運用中に変える値（営業時間・SNSのURL・予約のON/OFFなど）は
 * settings テーブルに入れ、管理画面から変更できるようにしている。
 * この配列は settings の "初期値と型の定義" としても使う（SettingSeeder が読む）。
 */
return [

    /*
    |--------------------------------------------------------------------------
    | 設定値の定義
    |--------------------------------------------------------------------------
    | group => [ key => [type, default, label, help] ]
    |
    | type: string / text / bool / int / json / encrypted
    | encrypted は DB に暗号化して保存する（APP_KEY を失うと復号できない）。
    */
    'settings' => [

        'site' => [
            'site_name' => ['type' => 'string', 'default' => '広瀬屋', 'label' => 'サイト名'],
            'site_name_en' => ['type' => 'string', 'default' => 'Hiroseya', 'label' => 'サイト名（英字）'],
            'preparation_mode' => [
                'type' => 'bool',
                'default' => true,
                'label' => '準備中モード',
                'help' => 'ONの間は全ページを noindex にし、sitemap.xml を空、robots.txt を全拒否にします。'
                    .'未ログインの訪問者には準備中ページを表示します。公開の準備が整うまでONのままにしてください。',
            ],
            'preparation_message' => [
                'type' => 'text',
                'default' => 'ただいまホームページを準備しております。しばらくお待ちください。',
                'label' => '準備中ページの本文',
            ],
            'maintenance_mode' => ['type' => 'bool', 'default' => false, 'label' => 'メンテナンスモード'],
            'ga_measurement_id' => ['type' => 'string', 'default' => '', 'label' => 'Google Analytics 測定ID'],
            'gsc_verification' => ['type' => 'string', 'default' => '', 'label' => 'Search Console 確認用メタタグ'],
        ],

        'seo' => [
            'default_title' => ['type' => 'string', 'default' => '広瀬屋｜岐阜県揖斐川町のうどん・そば・味噌煮込み', 'label' => '既定のタイトル'],
            'title_suffix' => ['type' => 'string', 'default' => '｜広瀬屋', 'label' => 'タイトルの接尾辞'],
            'default_description' => [
                'type' => 'text',
                'default' => '岐阜県揖斐郡揖斐川町のうどん・そば処「広瀬屋」。味噌煮込みうどん、定食、丼もの、お持ち帰り弁当をご用意しています。',
                'label' => '既定のディスクリプション',
            ],
            'default_keywords' => ['type' => 'string', 'default' => '', 'label' => '既定のキーワード'],
            'gbp_enabled' => [
                'type' => 'bool',
                'default' => false,
                'label' => 'Googleビジネスプロフィールの導線を表示',
                'help' => 'オーナー確認が済んでURLが決まってからONにしてください。',
            ],
            'gbp_url' => ['type' => 'string', 'default' => '', 'label' => 'ビジネスプロフィールのURL'],
        ],

        'access' => [
            'map_enabled' => ['type' => 'bool', 'default' => true, 'label' => '地図を表示する'],
            'map_embed' => [
                'type' => 'text',
                'default' => '',
                'label' => 'Googleマップの埋め込みコード',
                'help' => 'Googleマップの「共有」→「地図を埋め込む」で得られる iframe をそのまま貼り付けます。APIキーは不要です。',
            ],
            'map_link' => ['type' => 'string', 'default' => '', 'label' => '地図アプリで開くリンク'],
        ],

        'reservation' => [
            'enabled' => [
                'type' => 'bool',
                'default' => false,
                'label' => '予約機能を使う',
                'help' => 'OFFの間は予約ページを非公開にし、メニューやボタンの予約導線も表示しません。',
            ],
            'accept_from_days' => ['type' => 'int', 'default' => 1, 'label' => '何日先から受け付けるか', 'help' => '0 なら当日も受け付けます。'],
            'accept_until_days' => ['type' => 'int', 'default' => 30, 'label' => '何日先まで受け付けるか'],
            'cutoff_hours' => ['type' => 'int', 'default' => 24, 'label' => '受付の締切（時間前）'],
            'max_party_size' => ['type' => 'int', 'default' => 10, 'label' => 'Webで受け付ける最大人数', 'help' => 'これを超える人数は電話へご案内します。'],
            'auto_reply_enabled' => ['type' => 'bool', 'default' => true, 'label' => '受付メールを自動送信する'],
            'note' => [
                'type' => 'text',
                'default' => 'ご予約は店舗が確認したうえで確定いたします。確定のご連絡までしばらくお待ちください。',
                'label' => '予約フォームの補足文',
            ],
        ],

        'mail' => [
            'host' => ['type' => 'string', 'default' => '', 'label' => 'SMTPホスト'],
            'port' => ['type' => 'int', 'default' => 587, 'label' => 'ポート'],
            'encryption' => ['type' => 'string', 'default' => 'tls', 'label' => '暗号化方式'],
            'username' => ['type' => 'string', 'default' => '', 'label' => 'ユーザー名'],
            'password' => ['type' => 'encrypted', 'default' => '', 'label' => 'パスワード'],
            'from_address' => [
                'type' => 'string',
                'default' => '',
                'label' => '送信元アドレス',
                'help' => '必ず自ドメインのアドレスにしてください。お客様のアドレスを送信元にすると、なりすまし判定で届かなくなります。',
            ],
            'from_name' => ['type' => 'string', 'default' => '広瀬屋', 'label' => '送信者名'],
            'reply_to' => ['type' => 'string', 'default' => '', 'label' => '返信先アドレス'],
            'notify_to' => ['type' => 'string', 'default' => '', 'label' => '通知の宛先', 'help' => 'カンマ区切りで複数指定できます。'],
        ],

        'social' => [
            'api_enabled' => [
                'type' => 'bool',
                'default' => false,
                'label' => 'SNSのAPI連携を使う',
                'help' => 'OFFでもSNSへのリンクは表示されます。API連携はトークンの有効期限管理が必要なため、初期は OFF を推奨します。',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | トップページのセクション
    |--------------------------------------------------------------------------
    | 「型」はここで固定し、中身（画像・見出し・本文・表示・順序）だけを
    | 管理画面から変えられるようにする。ページビルダーにはしない。
    */
    'home_sections' => [
        'hero' => ['label' => 'メインビジュアル', 'image' => true, 'image_sp' => true, 'body' => false, 'lockable' => true],
        'catch' => ['label' => 'キャッチコピー', 'image' => true, 'image_sp' => false, 'body' => true],
        'about' => ['label' => '店舗紹介', 'image' => true, 'image_sp' => false, 'body' => true],
        'recommend' => ['label' => 'おすすめ料理', 'image' => false, 'image_sp' => false, 'body' => true],
        'news' => ['label' => 'お知らせ', 'image' => false, 'image_sp' => false, 'body' => false],
        'events' => ['label' => 'イベント', 'image' => false, 'image_sp' => false, 'body' => false],
        'hours' => ['label' => '営業時間・所在地', 'image' => false, 'image_sp' => false, 'body' => false],
        'access' => ['label' => 'アクセス', 'image' => false, 'image_sp' => false, 'body' => true],
        'social' => ['label' => 'SNS', 'image' => false, 'image_sp' => false, 'body' => true],
        'cta' => ['label' => 'お問い合わせ・予約', 'image' => true, 'image_sp' => false, 'body' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | 画像
    |--------------------------------------------------------------------------
    | アップロード時に生成する派生サイズ（長辺）。WebP も併せて生成する。
    */
    'images' => [
        'sizes' => ['lg' => 1600, 'md' => 800, 'sm' => 400],
        'webp_quality' => 80,
        'jpeg_quality' => 82,
        'max_upload_kb' => 8192,
        'accepted' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | 公開前チェックリスト
    |--------------------------------------------------------------------------
    | 準備中モードを解除する前に差し替えるべき項目。
    | 管理画面のダッシュボードに表示し、全部済むまで解除を警告し続ける。
    */
    'launch_checklist' => [
        'business_hours' => '営業時間・定休日を実際の値に更新する',
        'dish_prices' => '料理の価格を現行価格に更新する',
        'store_description' => '店舗紹介文とキャッチコピーを書く',
        'hero_image' => 'メインビジュアルを設定する',
        'dish_images' => '公開する料理すべてに写真と alt を設定する',
        'social_links' => 'SNSのURLを設定する（使わない場合は非表示に）',
        'mail_settings' => 'メール設定を行い、テスト送信で到達を確認する',
        'seo_meta' => '各ページのタイトルとディスクリプションを設定する',
        'map_embed' => 'アクセスページの地図を設定する',
        'reservation_slots' => '予約を使う場合、予約枠を実際の営業時間に合わせる',
    ],
];
