<?php

namespace Database\Seeders;

use App\Models\SocialLink;
use Illuminate\Database\Seeder;

/**
 * SNS（仮）。
 *
 * 実在するアカウントが未確認のため、すべて非表示・URL空で登録しておく。
 * URLを入れて「表示する」にすればリンクが出る。API連携は初期リリースでは使わない
 * （トークンの有効期限管理が必要で、失効するとフィードが止まるため）。
 */
class SocialLinkSeeder extends Seeder
{
    private const PLATFORMS = ['instagram', 'x', 'facebook', 'line'];

    public function run(): void
    {
        foreach (self::PLATFORMS as $index => $platform) {
            SocialLink::firstOrCreate(
                ['platform' => $platform],
                [
                    'display_name' => SocialLink::PLATFORMS[$platform] ?? $platform,
                    'url' => null,
                    'is_visible' => false,
                    'api_enabled' => false,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
