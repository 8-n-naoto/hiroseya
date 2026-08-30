<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use Illuminate\Database\Seeder;

/**
 * 営業時間（仮）。
 *
 * 正確な営業時間と定休日はどのポータルにも掲載が無く未確認のため、
 * うどん店の一般的な形で仮置きしている。
 * この値のまま公開すると誤情報を自ら検索エンジンに登録させることになるので、
 * 準備中モードを解除する前に必ず実際の値へ更新すること。
 */
class BusinessHourSeeder extends Seeder
{
    /** 仮の定休日：水曜。 */
    private const CLOSED_DAY = 3;

    private const LUNCH = ['11:00:00', '14:00:00'];

    private const DINNER = ['17:00:00', '20:00:00'];

    public function run(): void
    {
        if (BusinessHour::query()->exists()) {
            return;
        }

        for ($day = 0; $day <= 6; $day++) {
            if ($day === self::CLOSED_DAY) {
                BusinessHour::create([
                    'day_of_week' => $day,
                    'is_closed' => true,
                    'sort_order' => 0,
                ]);

                continue;
            }

            BusinessHour::create([
                'day_of_week' => $day,
                'opens_at' => self::LUNCH[0],
                'closes_at' => self::LUNCH[1],
                'label' => '昼の部',
                'sort_order' => 0,
            ]);

            BusinessHour::create([
                'day_of_week' => $day,
                'opens_at' => self::DINNER[0],
                'closes_at' => self::DINNER[1],
                'label' => '夜の部',
                'sort_order' => 1,
            ]);
        }
    }
}
