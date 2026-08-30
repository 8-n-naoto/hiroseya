<?php

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Models\DishCategory;
use Illuminate\Database\Seeder;

/**
 * 料理カテゴリ。
 *
 * 店舗が実際に看板で使っている分類をそのまま採用している
 * （料理写真/看板用写真/金額.xlsx より）。新しい分類は管理画面から追加できる。
 *
 * service_type は一覧の並べ方のヒントであって、絞り込みの正ではない。
 * お持ち帰りタブは「持ち帰り価格を持つ料理」を dish_variants 側で絞り込むため、
 * 店内カテゴリの料理（かつ丼・天丼など）も持ち帰り価格があれば表示される。
 */
class DishCategorySeeder extends Seeder
{
    private const CATEGORIES = [
        ['slug' => 'hot-noodles', 'name' => '温かい麺', 'service' => ServiceType::DineIn],
        ['slug' => 'cold-noodles', 'name' => '冷たい麺', 'service' => ServiceType::DineIn],
        ['slug' => 'nikomi', 'name' => '煮込み', 'service' => ServiceType::DineIn],
        ['slug' => 'donburi', 'name' => '丼もの', 'service' => ServiceType::DineIn],
        ['slug' => 'teishoku', 'name' => '定食物', 'service' => ServiceType::DineIn],
        ['slug' => 'men-set-hot', 'name' => '麺セット（温）', 'service' => ServiceType::DineIn],
        ['slug' => 'men-set-cold', 'name' => '麺セット（冷）', 'service' => ServiceType::DineIn],
        ['slug' => 'winter', 'name' => '冬限定', 'service' => ServiceType::DineIn],
        ['slug' => 'takeout-donburi', 'name' => '揚げ物・丼物', 'service' => ServiceType::Takeout],
        ['slug' => 'takeout-rice', 'name' => 'ライス', 'service' => ServiceType::Takeout],
        ['slug' => 'takeout-otsumami', 'name' => 'おつまみ', 'service' => ServiceType::Takeout],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $index => $category) {
            DishCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'service_type' => $category['service'],
                    'sort_order' => $index,
                    'is_visible' => true,
                ],
            );
        }
    }
}
