<?php

namespace App\Services;

use App\Models\BusinessHour;
use App\Models\SpecialDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 営業時間の組み立て。
 *
 * business_hours は「曜日 × 時間帯」の行の集まりでしかない。
 * 画面に出す形（曜日ごとの1行表記・本日の営業・定休日のまとめ）と
 * JSON-LD の openingHoursSpecification は、どちらもここで作る。
 *
 * 二重管理を作らないため、フッター・アクセスページ・構造化データは
 * すべてこのサービスを通す。
 */
class BusinessHourService
{
    /** @var Collection<int, BusinessHour>|null */
    private ?Collection $hours = null;

    /** @var Collection<int, SpecialDay>|null */
    private ?Collection $specialDays = null;

    /** @return Collection<int, BusinessHour> */
    public function all(): Collection
    {
        return $this->hours ??= BusinessHour::query()
            ->orderBy('day_of_week')
            ->orderBy('sort_order')
            ->get();
    }

    /** @return Collection<int, SpecialDay> 今日以降の臨時休業・時間変更 */
    public function upcomingSpecialDays(int $limit = 10): Collection
    {
        return $this->specialDays ??= SpecialDay::upcoming()->limit($limit)->get();
    }

    /**
     * 曜日ごとにまとめた表示用の行。
     *
     * @return array<int, array{day: int, label: string, closed: bool, ranges: array<int, array{label: string|null, range: string}>}>
     */
    public function week(): array
    {
        $grouped = $this->all()->groupBy('day_of_week');
        $rows = [];

        for ($day = 0; $day <= 6; $day++) {
            /** @var Collection<int, BusinessHour> $items */
            $items = $grouped->get($day, collect());
            $closed = $items->isEmpty() || $items->every(fn (BusinessHour $h) => $h->is_closed);

            $rows[] = [
                'day' => $day,
                'label' => BusinessHour::DAY_LABELS[$day],
                'closed' => $closed,
                'ranges' => $closed ? [] : $items
                    ->reject(fn (BusinessHour $h) => $h->is_closed)
                    ->map(fn (BusinessHour $h) => ['label' => $h->label, 'range' => $h->rangeLabel()])
                    ->values()
                    ->all(),
            ];
        }

        return $rows;
    }

    /**
     * 同じ営業時間が続く曜日をまとめた表記。
     * 「月〜火・木〜土 11:00〜14:00 / 17:00〜20:00」のように出す。
     *
     * @return array<int, array{days: string, ranges: array<int, array{label: string|null, range: string}>}>
     */
    public function summarizedWeek(): array
    {
        $rows = collect($this->week())->reject(fn (array $row) => $row['closed'])->values();
        $groups = [];

        foreach ($rows as $row) {
            $signature = json_encode($row['ranges'], JSON_UNESCAPED_UNICODE);

            if ($groups !== [] && end($groups)['signature'] === $signature
                && end($groups)['last'] === $row['day'] - 1) {
                $groups[array_key_last($groups)]['days'][] = $row['day'];
                $groups[array_key_last($groups)]['last'] = $row['day'];

                continue;
            }

            $groups[] = [
                'signature' => $signature,
                'days' => [$row['day']],
                'last' => $row['day'],
                'ranges' => $row['ranges'],
            ];
        }

        return collect($groups)->map(function (array $group): array {
            $labels = array_map(fn (int $d) => BusinessHour::DAY_LABELS[$d], $group['days']);

            return [
                'days' => count($labels) >= 3
                    ? $labels[0].'〜'.$labels[count($labels) - 1]
                    : implode('・', $labels),
                'ranges' => $group['ranges'],
            ];
        })->all();
    }

    /** 「定休日：水曜日」の形。定休日が無ければ「年中無休」。 */
    public function closedDaysLabel(): string
    {
        $closed = collect($this->week())
            ->filter(fn (array $row) => $row['closed'])
            ->pluck('label')
            ->all();

        return $closed === [] ? '年中無休' : implode('・', $closed).'曜日';
    }

    /** 指定日の営業時間。臨時休業・時間変更があればそちらを優先する。 */
    public function forDate(Carbon $date): array
    {
        $special = SpecialDay::query()->whereDate('date', $date->toDateString())->first();

        if ($special) {
            return [
                'closed' => $special->is_closed,
                'note' => $special->note,
                'ranges' => $special->is_closed || ! $special->opens_at ? [] : [[
                    'label' => null,
                    'range' => substr((string) $special->opens_at, 0, 5).'〜'.substr((string) $special->closes_at, 0, 5),
                ]],
                'special' => true,
            ];
        }

        $row = collect($this->week())->firstWhere('day', (int) $date->dayOfWeek);

        return [
            'closed' => (bool) ($row['closed'] ?? true),
            'note' => null,
            'ranges' => $row['ranges'] ?? [],
            'special' => false,
        ];
    }

    /** 本日の営業案内。ヘッダーとフッターに出す。 */
    public function today(): array
    {
        return $this->forDate(Carbon::today());
    }

    /**
     * JSON-LD の openingHoursSpecification。
     *
     * @return array<int, array<string, mixed>>
     */
    public function schemaOpeningHours(): array
    {
        return $this->all()
            ->reject(fn (BusinessHour $h) => $h->is_closed || ! $h->opens_at || ! $h->closes_at)
            ->map(fn (BusinessHour $h) => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => 'https://schema.org/'.$h->schemaDay(),
                'opens' => substr((string) $h->opens_at, 0, 5),
                'closes' => substr((string) $h->closes_at, 0, 5),
            ])
            ->values()
            ->all();
    }

    /**
     * JSON-LD の specialOpeningHoursSpecification（臨時休業・時間変更）。
     * 検索結果の営業時間表示が実態とずれるのを防ぐ。
     *
     * @return array<int, array<string, mixed>>
     */
    public function schemaSpecialHours(): array
    {
        return $this->upcomingSpecialDays(30)
            ->map(function (SpecialDay $day): array {
                $spec = [
                    '@type' => 'OpeningHoursSpecification',
                    'validFrom' => $day->date->toDateString(),
                    'validThrough' => $day->date->toDateString(),
                ];

                if ($day->is_closed) {
                    $spec['opens'] = '00:00';
                    $spec['closes'] = '00:00';
                } else {
                    $spec['opens'] = substr((string) $day->opens_at, 0, 5);
                    $spec['closes'] = substr((string) $day->closes_at, 0, 5);
                }

                return $spec;
            })
            ->values()
            ->all();
    }
}
