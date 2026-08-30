<?php

namespace App\Enums;

/**
 * 管理画面の権限。要件どおり 2 段階のみ。
 *
 * OWNER  : すべて操作できる。設定系（SEO / SNS / 予約 / メール / サイト基本 / ユーザー）はこちらのみ。
 * EDITOR : コンテンツ（料理・お知らせ・イベント・トップページ・画像）と、
 *          問い合わせ・予約の対応のみ行える。
 */
enum UserRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Owner => '管理者',
            self::Editor => '編集者',
        };
    }

    /** 設定系の画面に入れるか。 */
    public function canManageSettings(): bool
    {
        return $this === self::Owner;
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
