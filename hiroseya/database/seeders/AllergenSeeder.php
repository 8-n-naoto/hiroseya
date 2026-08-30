<?php

namespace Database\Seeders;

use App\Models\Allergen;
use Illuminate\Database\Seeder;

/**
 * 特定原材料8品目。
 *
 * 料理への紐付けは任意で、未入力の料理では表示自体を出さない。
 * 表示する場合は「同一調理場のため微量混入の可能性があります」の注記を必ず添えること。
 */
class AllergenSeeder extends Seeder
{
    private const ITEMS = [
        'ebi' => 'えび',
        'kani' => 'かに',
        'kurumi' => 'くるみ',
        'komugi' => '小麦',
        'soba' => 'そば',
        'tamago' => '卵',
        'nyu' => '乳',
        'rakkasei' => '落花生',
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::ITEMS as $slug => $name) {
            Allergen::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'sort_order' => $order++, 'is_visible' => true],
            );
        }
    }
}
