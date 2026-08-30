<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessHour;
use App\Models\Contact;
use App\Models\Dish;
use App\Models\DishVariant;
use App\Models\HomeSection;
use App\Models\Media;
use App\Models\News;
use App\Models\ReservationTimeSlot;
use App\Models\SeoMeta;
use App\Models\SocialLink;
use App\Models\StoreProfile;
use App\Support\MailSettings;
use App\Support\Settings;
use Illuminate\View\View;

/**
 * 管理画面トップ。
 *
 * 「今日やること」と「公開までに残っていること」がひと目で分かることを
 * 目的にする。数字を並べるだけのダッシュボードにはしない。
 *
 * 公開前チェックリストは、判定できる項目を実データから自動で見る。
 * 手でチェックを付ける形にすると、確認しないまま全部チェックが付く。
 */
class DashboardController extends Controller
{
    public function __invoke(Settings $settings, MailSettings $mail): View
    {
        $checklist = $this->checklist($settings, $mail);

        return view('admin.dashboard', [
            'openContacts' => Contact::open()->latest()->limit(5)->get(),
            'pendingContactCount' => Contact::pending()->count(),
            'openContactCount' => Contact::open()->count(),
            'missingAltCount' => Media::query()
                ->where(fn ($q) => $q->whereNull('alt')->orWhere('alt', ''))->count(),
            'publishedDishCount' => Dish::published()->count(),
            'draftDishCount' => Dish::draft()->count(),
            'dishesWithoutImage' => Dish::published()->whereNull('main_media_id')->count(),
            'draftNewsCount' => News::draft()->count(),
            'scheduledNewsCount' => News::published()->where('published_at', '>', now())->count(),
            'checklist' => $checklist,
            'checklistRemaining' => collect($checklist)->where('done', false)->count(),
            'inPreparation' => $settings->inPreparation(),
            'mailConfigured' => $mail->configured(),
            'notifyConfigured' => $mail->notifyRecipients() !== [],
        ]);
    }

    /**
     * 公開前チェックリスト。
     *
     * done が null の項目は「機械では判定できない＝人が確認するもの」。
     *
     * @return array<int, array{key: string, label: string, done: bool|null, hint: string|null, route: string|null}>
     */
    private function checklist(Settings $settings, MailSettings $mail): array
    {
        $store = StoreProfile::current();

        $checks = [
            'business_hours' => [
                // 初期値のまま一度も保存していない場合を「未確認」とみなす。
                fn () => BusinessHour::query()->whereColumn('updated_at', '>', 'created_at')->exists(),
                '初期値は仮の営業時間（水曜定休・昼夜）です。実際の値で一度保存してください。',
                'admin.business-hours.index',
            ],
            'dish_prices' => [
                fn () => DishVariant::query()->whereColumn('updated_at', '>', 'created_at')->exists(),
                '初期データの価格は2019〜2021年頃のものです。現行価格に更新してください。',
                'admin.dishes.index',
            ],
            'store_description' => [
                fn () => filled($store->description)
                    && ! str_contains((string) $store->description, '仮の内容')
                    && filled($store->catch_copy),
                'キャッチコピーと店舗紹介文が仮のままです。',
                'admin.store.edit',
            ],
            'hero_image' => [
                fn () => HomeSection::query()->where('key', 'hero')->whereNotNull('media_id')->exists(),
                'トップページのメインビジュアルが未設定です。',
                'admin.home-sections.index',
            ],
            'dish_images' => [
                fn () => ! Dish::published()->whereNull('main_media_id')->exists(),
                '写真が未設定の公開中の料理があります。',
                'admin.dishes.index',
            ],
            'social_links' => [
                fn () => SocialLink::query()->where('is_visible', true)->exists()
                    || SocialLink::query()->whereNotNull('url')->exists(),
                'SNSのURLが未設定です。使わない場合はこの項目は無視して構いません。',
                'admin.social-links.index',
            ],
            'mail_settings' => [
                fn () => $mail->configured() && $mail->notifyRecipients() !== [],
                'SMTPと通知先を設定し、テスト送信で到達を確認してください。未設定だとお問い合わせの通知が届きません。',
                'admin.settings.edit',
            ],
            'seo_meta' => [
                fn () => SeoMeta::query()->where('page_key', 'home')->whereNotNull('title')->exists(),
                'トップページのタイトルとディスクリプションを設定してください。',
                'admin.seo.index',
            ],
            'map_embed' => [
                fn () => ! $settings->bool('access.map_enabled')
                    || $settings->string('access.map_embed') !== '',
                'アクセスページの地図が未設定です。',
                'admin.settings.edit',
            ],
            'reservation_slots' => [
                fn () => ! $settings->reservationEnabled() || ReservationTimeSlot::query()->exists(),
                '予約を使う場合は、予約枠を実際の営業時間に合わせてください。',
                null,
            ],
        ];

        $out = [];

        foreach (config('hiroseya.launch_checklist', []) as $key => $label) {
            [$check, $hint, $route] = $checks[$key] ?? [null, null, null];

            $out[] = [
                'key' => $key,
                'label' => $label,
                'done' => $check ? (bool) $check() : null,
                'hint' => $hint,
                'route' => $route && \Illuminate\Support\Facades\Route::has($route) ? $route : null,
            ];
        }

        return $out;
    }
}
