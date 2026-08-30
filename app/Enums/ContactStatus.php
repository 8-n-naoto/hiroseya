<?php

namespace App\Enums;

enum ContactStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '未対応',
            self::InProgress => '対応中',
            self::Done => '対応済み',
        };
    }

    /** 一覧で色分けするためのキー。 */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'alert',
            self::InProgress => 'notice',
            self::Done => 'muted',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
