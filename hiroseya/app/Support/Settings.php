<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * 設定値の読み書き。
 *
 * 全ページが参照するため、必ずキャッシュを通す。
 * 書き込み時にキャッシュを捨てるので、管理画面の保存は即座に反映される。
 *
 * 定義（型と初期値）は config/hiroseya.php の settings 配列が正。
 * DB に行が無いキーは、そこに書かれた既定値を返す。
 */
class Settings
{
    private const CACHE_KEY = 'hiroseya.settings';

    /** @var array<string, mixed>|null */
    private ?array $loaded = null;

    /**
     * @return array<string, mixed> "group.key" => value
     */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        return $this->loaded = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $values = [];

            // まず config の既定値で埋める。
            foreach (config('hiroseya.settings', []) as $group => $keys) {
                foreach ($keys as $key => $definition) {
                    $values["{$group}.{$key}"] = $definition['default'] ?? null;
                }
            }

            // DB にある値で上書きする。
            foreach (Setting::all() as $setting) {
                $values["{$setting->group}.{$setting->key}"] = $setting->typedValue();
            }

            return $values;
        });
    }

    public function get(string $path, mixed $fallback = null): mixed
    {
        return $this->all()[$path] ?? $fallback;
    }

    public function bool(string $path, bool $fallback = false): bool
    {
        return (bool) $this->get($path, $fallback);
    }

    public function int(string $path, int $fallback = 0): int
    {
        $value = $this->get($path, $fallback);

        return $value === null || $value === '' ? $fallback : (int) $value;
    }

    public function string(string $path, string $fallback = ''): string
    {
        $value = $this->get($path, $fallback);

        return $value === null ? $fallback : (string) $value;
    }

    /**
     * グループ単位でまとめて取り出す。
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $prefix = $group.'.';
        $out = [];

        foreach ($this->all() as $path => $value) {
            if (str_starts_with($path, $prefix)) {
                $out[substr($path, strlen($prefix))] = $value;
            }
        }

        return $out;
    }

    public function set(string $group, string $key, mixed $value): void
    {
        $type = $this->typeOf($group, $key);

        Setting::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => Setting::serialize($value, $type), 'type' => $type],
        );

        $this->flush();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            $type = $this->typeOf($group, $key);

            Setting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => Setting::serialize($value, $type), 'type' => $type],
            );
        }

        $this->flush();
    }

    public function typeOf(string $group, string $key): string
    {
        return config("hiroseya.settings.{$group}.{$key}.type", 'string');
    }

    /** @return array<string, mixed>|null */
    public function definition(string $group, string $key): ?array
    {
        return config("hiroseya.settings.{$group}.{$key}");
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }

    /*
    |--------------------------------------------------------------------------
    | よく参照する判定のショートカット
    |--------------------------------------------------------------------------
    */

    /** 準備中モード。ONの間は noindex にし、未ログインには準備中ページを出す。 */
    public function inPreparation(): bool
    {
        return $this->bool('site.preparation_mode', true);
    }

    /** 予約機能が有効か。OFFならページも導線も出さない。 */
    public function reservationEnabled(): bool
    {
        return $this->bool('reservation.enabled', false);
    }

    public function mapEnabled(): bool
    {
        return $this->bool('access.map_enabled', true);
    }
}
