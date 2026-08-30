<?php

namespace App\Enums;

/**
 * 提供区分。
 *
 * 同じ料理でも店内と持ち帰りで価格が違う（持ち帰りはパック代込）ため、
 * この区分は料理ではなく「価格バリエーション」側に持たせている。
 * 実際のメニュー表でも かつ丼 が店内 880 円 / 持ち帰り 900 円 と別価格になっている。
 */
enum ServiceType: string
{
    case DineIn = 'dine_in';
    case Takeout = 'takeout';

    public function label(): string
    {
        return match ($this) {
            self::DineIn => '店内飲食',
            self::Takeout => 'お持ち帰り',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $t) => [$t->value => $t->label()])
            ->all();
    }
}
