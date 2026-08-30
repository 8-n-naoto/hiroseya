<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * config/hiroseya.php の定義をそのまま settings テーブルへ流し込む。
 *
 * 既に値が入っているキーは上書きしない（運用中に再実行しても設定が消えない）。
 * 新しい設定キーを config に足したあとで再実行すれば、その分だけ追加される。
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('hiroseya.settings', []) as $group => $keys) {
            foreach ($keys as $key => $definition) {
                $type = $definition['type'] ?? 'string';

                Setting::firstOrCreate(
                    ['group' => $group, 'key' => $key],
                    [
                        'type' => $type,
                        'value' => Setting::serialize($definition['default'] ?? null, $type),
                    ],
                );
            }
        }

        app(\App\Support\Settings::class)->flush();
    }
}
