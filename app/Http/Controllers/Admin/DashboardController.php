<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Dish;
use App\Models\Media;
use App\Models\Reservation;
use App\Support\Settings;
use Illuminate\View\View;

/**
 * 管理画面トップ。
 *
 * 「今日やること」がひと目でわかることを目的にする。
 * 未確認の予約・未対応の問い合わせという「対応漏れ」と、
 * 公開前チェックリストという「準備の進み具合」の二本立て。
 */
class DashboardController extends Controller
{
    public function __invoke(Settings $settings): View
    {
        $reservationsEnabled = $settings->reservationEnabled();

        return view('admin.dashboard', [
            'pendingReservations' => $reservationsEnabled
                ? Reservation::pending()->upcoming()->limit(5)->get()
                : collect(),
            'pendingReservationCount' => $reservationsEnabled ? Reservation::pending()->count() : 0,
            'reservationsEnabled' => $reservationsEnabled,
            'openContacts' => Contact::open()->latest()->limit(5)->get(),
            'openContactCount' => Contact::open()->count(),
            'missingAltCount' => Media::query()->where(function ($q) {
                $q->whereNull('alt')->orWhere('alt', '');
            })->count(),
            'checklist' => $this->checklist($settings),
            'inPreparation' => $settings->inPreparation(),
        ]);
    }

    /**
     * 公開前チェックリスト。config/hiroseya.php の launch_checklist を基に、
     * わかる範囲でデータから自動判定する。判定できない項目は「要確認」のまま表示する。
     *
     * @return array<int, array{key: string, label: string, done: bool}>
     */
    private function checklist(Settings $settings): array
    {
        $items = config('hiroseya.launch_checklist', []);

        $autoChecks = [
            'hero_image' => fn () => \App\Models\HomeSection::query()
                ->where('key', 'hero')->whereNotNull('media_id')->exists(),
            'social_links' => fn () => \App\Models\SocialLink::query()->where('is_visible', true)->exists()
                || ! $settings->bool('social.api_enabled'),
            'mail_settings' => fn () => $settings->string('mail.from_address') !== '',
            'map_embed' => fn () => ! $settings->bool('access.map_enabled')
                || $settings->string('access.map_embed') !== '',
            'dish_images' => fn () => ! Dish::published()->whereNull('main_media_id')->exists(),
            'reservation_slots' => fn () => ! $settings->reservationEnabled()
                || \App\Models\ReservationTimeSlot::query()->exists(),
        ];

        $out = [];
        foreach ($items as $key => $label) {
            $out[] = [
                'key' => $key,
                'label' => $label,
                'done' => isset($autoChecks[$key]) ? (bool) $autoChecks[$key]() : null,
            ];
        }

        return $out;
    }
}
