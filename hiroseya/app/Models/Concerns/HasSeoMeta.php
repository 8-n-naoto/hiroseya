<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * SEOメタを持てるモデル。
 *
 * seoMeta が未設定でも空にならないよう、モデル側が既定値を用意する。
 * 入力必須にすると運用で必ず空欄や使い回しが発生し、かえってSEOを損なうため、
 * 「入力があればそれを使い、無ければ自動生成」という形にしている。
 */
trait HasSeoMeta
{
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /** 各モデルで上書きして、自動生成のタイトルを返す。 */
    public function defaultSeoTitle(): string
    {
        return (string) ($this->title ?? $this->name ?? '');
    }

    /** 各モデルで上書きして、自動生成のディスクリプションを返す。 */
    public function defaultSeoDescription(): string
    {
        $source = $this->excerpt ?? $this->description ?? $this->body ?? '';

        return mb_substr(trim(strip_tags((string) $source)), 0, 120);
    }

    public function seoTitle(): string
    {
        return $this->seoMeta?->title ?: $this->defaultSeoTitle();
    }

    public function seoDescription(): string
    {
        return $this->seoMeta?->description ?: $this->defaultSeoDescription();
    }
}
