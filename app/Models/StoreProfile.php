<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * 店舗情報。常に 1 行だけを扱う。
 *
 * このモデルが店舗情報の唯一の正。フッター・アクセスページ・JSON-LD は
 * すべてここから出力し、二重管理を作らないこと。
 */
#[Fillable([
    'name', 'name_kana', 'catch_copy', 'description',
    'postal_code', 'prefecture', 'city', 'address_line', 'building',
    'tel', 'fax', 'email', 'seats', 'parking', 'payment_methods',
    'access_car', 'access_train', 'latitude', 'longitude',
])]
class StoreProfile extends Model
{
    protected $table = 'store_profile';

    protected function casts(): array
    {
        return [
            'payment_methods' => 'array',
            'seats' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** 常に同じ 1 行を返す。無ければ空のインスタンスを作る。 */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], ['name' => '広瀬屋']);
    }

    /** 郵便番号を除いた住所の 1 行表記。NAP統一のため必ずここを通す。 */
    public function fullAddress(): string
    {
        return trim(implode('', array_filter([
            $this->prefecture,
            $this->city,
            $this->address_line,
            $this->building,
        ])));
    }

    public function formattedPostalCode(): string
    {
        return $this->postal_code ? '〒'.$this->postal_code : '';
    }

    /** 電話リンク用（ハイフンを除いた番号）。 */
    public function telLink(): string
    {
        return 'tel:'.preg_replace('/[^0-9+]/', '', (string) $this->tel);
    }
}
