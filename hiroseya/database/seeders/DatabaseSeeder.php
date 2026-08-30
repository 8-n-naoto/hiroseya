<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 初期データ。
 *
 * 店舗情報の一部（住所・電話・席数）以外は「仮置き」であり、
 * 公開前に管理画面から実際の値へ差し替える前提。
 * 仮の情報が検索エンジンに登録されるのを防ぐため、
 * site.preparation_mode（準備中モード）は既定で ON にしてある。
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            UserSeeder::class,
            StoreProfileSeeder::class,
            BusinessHourSeeder::class,
            AllergenSeeder::class,
            DishCategorySeeder::class,
            DishSeeder::class,
            HomeSectionSeeder::class,
            SocialLinkSeeder::class,
            ReservationTimeSlotSeeder::class,
            SeoMetaSeeder::class,
            SampleContentSeeder::class,
        ]);
    }
}
