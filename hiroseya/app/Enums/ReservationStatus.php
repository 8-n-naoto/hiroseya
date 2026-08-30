<?php

namespace App\Enums;

/**
 * 予約のステータス。方式は「リクエスト承認制」。
 *
 * お客様の送信は仮予約（Pending）であり、店舗が承認して初めて Confirmed になる。
 * システムは席の空きを正確に知っている必要がなく、電話・店頭予約との
 * 二重管理が起きてもダブルブッキングが構造的に発生しない。
 */
enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '未確認',
            self::Confirmed => '確定',
            self::Rejected => 'お断り',
            self::Cancelled => 'キャンセル',
            self::Completed => '来店済み',
            self::NoShow => '無断キャンセル',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'alert',
            self::Confirmed => 'ok',
            self::Rejected, self::Cancelled, self::NoShow => 'muted',
            self::Completed => 'notice',
        };
    }

    /** 枠の受付人数を消費するステータスか（未確認と確定のみ数える）。 */
    public function occupiesCapacity(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    /** 枠の消費対象となるステータスの値一覧。 */
    public static function occupyingValues(): array
    {
        return [self::Pending->value, self::Confirmed->value];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
