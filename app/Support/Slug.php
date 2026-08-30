<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * URL に使う slug の生成。
 *
 * 日本語の料理名から Str::slug() を通すと空文字になるため、
 * 「入力されていなければ自動で決める」だけでは URL が作れない。
 * ふりがな（name_kana）→ 名前 →連番 の順に試し、必ず一意な値を返す。
 *
 * 既に公開済みのページの slug は変えない運用にしている。
 * URL が変わると検索エンジンの評価が積み上がらず、外部のリンクも切れるため。
 */
class Slug
{
    public static function make(string $desired, string $fallbackSource, string $table, ?int $ignoreId = null): string
    {
        $base = Str::slug($desired) ?: Str::slug($fallbackSource);

        if ($base === '') {
            $base = 'item';
        }

        $base = Str::limit($base, 90, '');
        $slug = $base;
        $suffix = 2;

        while (self::exists($table, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public static function forModel(Model $model, string $desired, string $fallbackSource): string
    {
        return self::make($desired, $fallbackSource, $model->getTable(), $model->exists ? $model->getKey() : null);
    }

    private static function exists(string $table, string $slug, ?int $ignoreId): bool
    {
        return \Illuminate\Support\Facades\DB::table($table)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
