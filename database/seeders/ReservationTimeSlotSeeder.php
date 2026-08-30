<?php

namespace Database\Seeders;

use App\Models\ReservationTimeSlot;
use Illuminate\Database\Seeder;

/**
 * 予約枠（仮）。
 *
 * 昼 11:00〜13:30、夜 17:00〜19:30 を30分刻み、各枠10名で仮置きする。
 * 仮の定休日（水曜）には枠を作らない。
 *
 * capacity は在庫ではなく「Webで受け付ける上限」。
 * 未確認 + 確定 の合計がここに達した枠はフォームで選べなくなり、
 * 「満席のためお電話ください」と案内される。
 */
class ReservationTimeSlotSeeder extends Seeder
{
    private const CLOSED_DAY = 3;

    private const TIMES = [
        '11:00:00', '11:30:00', '12:00:00', '12:30:00', '13:00:00', '13:30:00',
        '17:00:00', '17:30:00', '18:00:00', '18:30:00', '19:00:00', '19:30:00',
    ];

    private const CAPACITY = 10;

    public function run(): void
    {
        for ($day = 0; $day <= 6; $day++) {
            if ($day === self::CLOSED_DAY) {
                continue;
            }

            foreach (self::TIMES as $index => $time) {
                ReservationTimeSlot::firstOrCreate(
                    ['day_of_week' => $day, 'starts_at' => $time],
                    ['capacity' => self::CAPACITY, 'is_active' => true, 'sort_order' => $index],
                );
            }
        }
    }
}
