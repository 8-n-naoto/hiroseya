<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

#[Fillable(['group', 'key', 'value', 'type'])]
class Setting extends Model
{
    /** 保存されている生の値を、型に従って PHP の値に変換する。 */
    public function typedValue(): mixed
    {
        return match ($this->type) {
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'int' => $this->value === null || $this->value === '' ? null : (int) $this->value,
            'json' => json_decode((string) $this->value, true) ?? [],
            'encrypted' => blank($this->value) ? '' : rescue(fn () => Crypt::decryptString($this->value), '', false),
            default => (string) ($this->value ?? ''),
        };
    }

    /** 型に従って保存用の文字列に変換する。 */
    public static function serialize(mixed $value, string $type): ?string
    {
        return match ($type) {
            'bool' => $value ? '1' : '0',
            'int' => $value === null || $value === '' ? null : (string) (int) $value,
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE),
            'encrypted' => blank($value) ? '' : Crypt::encryptString((string) $value),
            default => $value === null ? null : (string) $value,
        };
    }
}
