<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BusinessHour;
use App\Models\SpecialDay;
use App\Services\BusinessHourService;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 営業時間と臨時休業。
 *
 * 曜日ごとに「昼の部」「夜の部」の 2 行を持てる形にしている。
 * 通し営業の店なら 1 行だけ使えばよい。
 *
 * 臨時休業はここで入れると、公開サイトの表示だけでなく
 * 構造化データ（specialOpeningHoursSpecification）にも反映され、
 * 検索結果の営業時間表示が実態とずれない。
 */
class BusinessHourController extends Controller
{
    public function index(BusinessHourService $hours, Settings $settings): View
    {
        return view('admin.business-hours.index', [
            'rows' => BusinessHour::query()
                ->orderBy('day_of_week')->orderBy('sort_order')->get()
                ->groupBy('day_of_week'),
            'specialDays' => SpecialDay::query()->orderByDesc('date')->limit(40)->get(),
            'week' => $hours->week(),
            'inPreparation' => $settings->inPreparation(),
        ]);
    }

    /**
     * 曜日ごとの一括更新。
     *
     * 行を消して入れ直すのではなく、曜日ごとに作り直している。
     * 入力が空の枠は保存しないので、「夜の部だけ消す」も自然にできる。
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'days' => ['required', 'array'],
            'days.*.closed' => ['nullable', 'boolean'],
            'days.*.slots' => ['nullable', 'array', 'max:4'],
            'days.*.slots.*.label' => ['nullable', 'string', 'max:50'],
            'days.*.slots.*.opens_at' => ['nullable', 'date_format:H:i'],
            'days.*.slots.*.closes_at' => ['nullable', 'date_format:H:i'],
        ], [
            'days.*.slots.*.opens_at.date_format' => '開店時刻は 11:00 の形式でご入力ください。',
            'days.*.slots.*.closes_at.date_format' => '閉店時刻は 11:00 の形式でご入力ください。',
        ]);

        DB::transaction(function () use ($request): void {
            foreach ($request->input('days', []) as $day => $config) {
                $day = (int) $day;

                if ($day < 0 || $day > 6) {
                    continue;
                }

                BusinessHour::where('day_of_week', $day)->delete();

                if (! empty($config['closed'])) {
                    BusinessHour::create(['day_of_week' => $day, 'is_closed' => true, 'sort_order' => 0]);

                    continue;
                }

                $order = 0;

                foreach ($config['slots'] ?? [] as $slot) {
                    if (blank($slot['opens_at'] ?? null) || blank($slot['closes_at'] ?? null)) {
                        continue;
                    }

                    BusinessHour::create([
                        'day_of_week' => $day,
                        'opens_at' => $slot['opens_at'].':00',
                        'closes_at' => $slot['closes_at'].':00',
                        'label' => $slot['label'] ?? null,
                        'is_closed' => false,
                        'sort_order' => $order++,
                    ]);
                }

                // 1 枠も入力されなかった曜日は定休日として扱う。
                if ($order === 0) {
                    BusinessHour::create(['day_of_week' => $day, 'is_closed' => true, 'sort_order' => 0]);
                }
            }
        });

        ActivityLog::record('update', null, '営業時間を更新しました。');

        return back()->with('status', '営業時間を更新しました。');
    }

    public function storeSpecialDay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'is_closed' => ['boolean'],
            'opens_at' => ['nullable', 'required_if:is_closed,0', 'date_format:H:i'],
            'closes_at' => ['nullable', 'required_if:is_closed,0', 'date_format:H:i', 'after:opens_at'],
            'note' => ['nullable', 'string', 'max:191'],
        ], [
            'opens_at.required_if' => '時間変更の場合は開店時刻をご入力ください。',
            'closes_at.required_if' => '時間変更の場合は閉店時刻をご入力ください。',
            'closes_at.after' => '閉店時刻は開店時刻より後にしてください。',
        ], [
            'date' => '日付', 'note' => '備考', 'opens_at' => '開店時刻', 'closes_at' => '閉店時刻',
        ]);

        $isClosed = $request->boolean('is_closed');

        SpecialDay::updateOrCreate(['date' => $data['date']], [
            'is_closed' => $isClosed,
            'opens_at' => $isClosed ? null : $data['opens_at'].':00',
            'closes_at' => $isClosed ? null : $data['closes_at'].':00',
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('status', '臨時のお知らせを登録しました。');
    }

    public function destroySpecialDay(SpecialDay $specialDay): RedirectResponse
    {
        $specialDay->delete();

        return back()->with('status', '臨時のお知らせを削除しました。');
    }
}
