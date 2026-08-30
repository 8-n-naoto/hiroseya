<?php

namespace App\Support;

/**
 * 章番号に使う漢数字。
 *
 * お品書きの見出しに「一、二、三…」を添えると、紙のお品書きの読み心地に近づく。
 * 20 を超えるカテゴリは実運用ではまず出ないので、その場合は算用数字に落とす。
 */
class Kansuji
{
    private const DIGITS = ['', '一', '二', '三', '四', '五', '六', '七', '八', '九'];

    public static function of(int $number): string
    {
        if ($number < 1 || $number > 99) {
            return (string) $number;
        }

        if ($number < 10) {
            return self::DIGITS[$number];
        }

        $tens = intdiv($number, 10);
        $ones = $number % 10;

        return ($tens > 1 ? self::DIGITS[$tens] : '').'十'.self::DIGITS[$ones];
    }
}
