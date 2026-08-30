<?php

namespace App\Support;

use App\Models\Media;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * 1ページ分の SEO 情報をまとめる。
 *
 * コントローラーが seo()->page('menu') のように宣言し、レイアウトが読み出す。
 * 値の優先順位は常に次の 3 段:
 *
 *   1. コントローラーで明示的に指定した値
 *   2. seo_metas に店舗が入力した値（固定ページは page_key、記事は多相リレーション）
 *   3. モデル / 設定から自動生成した値
 *
 * 入力必須にしないのは、運用で必ず空欄や使い回しが起き、かえって
 * 重複タイトル・重複ディスクリプションを量産するため。
 *
 * robots は準備中モードが最優先で noindex を強制する。
 * 「解除し忘れて仮情報がインデックスされる」事故を、設定 1 箇所で防ぐ。
 */
class Seo
{
    private ?string $title = null;

    private ?string $description = null;

    private ?string $keywords = null;

    private ?string $canonical = null;

    private ?string $robots = null;

    private ?string $ogTitle = null;

    private ?string $ogDescription = null;

    private ?Media $ogImage = null;

    private string $ogType = 'website';

    /** タイトルに接尾辞（｜広瀬屋）を付けるか。トップページだけ付けない。 */
    private bool $withSuffix = true;

    /** @var array<int, array{name: string, url: string|null}> */
    private array $breadcrumbs = [];

    /** @var array<int, array<string, mixed>> 追加の JSON-LD */
    private array $schemas = [];

    public function __construct(private readonly Settings $settings) {}

    /*
    |--------------------------------------------------------------------------
    | 宣言
    |--------------------------------------------------------------------------
    */

    /** 固定ページ（home / menu / access ...）のメタを seo_metas から読み込む。 */
    public function page(string $pageKey): static
    {
        return $this->applyMeta(SeoMeta::forPage($pageKey));
    }

    /**
     * 記事・料理などのモデルから読み込む。
     * seo_metas に行が無ければ、モデル側の自動生成にフォールバックする。
     */
    public function model(Model $model): static
    {
        if (method_exists($model, 'seoMeta')) {
            $this->applyMeta($model->seoMeta);
        }

        if (method_exists($model, 'defaultSeoTitle')) {
            $this->title ??= $model->defaultSeoTitle();
        }

        if (method_exists($model, 'defaultSeoDescription')) {
            $this->description ??= $model->defaultSeoDescription();
        }

        if ($this->ogImage === null && isset($model->mainImage) && $model->mainImage instanceof Media) {
            $this->ogImage = $model->mainImage;
        }

        return $this;
    }

    private function applyMeta(?SeoMeta $meta): static
    {
        if (! $meta) {
            return $this;
        }

        $this->title ??= $meta->title ?: null;
        $this->description ??= $meta->description ?: null;
        $this->keywords ??= $meta->keywords ?: null;
        $this->canonical ??= $meta->canonical ?: null;
        $this->robots ??= $meta->robots ?: null;
        $this->ogTitle ??= $meta->og_title ?: null;
        $this->ogDescription ??= $meta->og_description ?: null;
        $this->ogImage ??= $meta->ogImage;

        return $this;
    }

    /**
     * タイトルの既定値。
     *
     * すでに seo_metas に店舗が入力した値があれば、そちらを優先する。
     * 上書きにすると、管理画面で設定したタイトルが画面に出ず、
     * 「設定したのに変わらない」という状態になる。
     */
    public function title(?string $title): static
    {
        if (filled($title)) {
            $this->title ??= $title;
        }

        return $this;
    }

    /** コントローラー側の値を必ず優先する場合だけ使う。 */
    public function forceTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /** トップページ用。店名の接尾辞を付けない。 */
    public function noSuffix(): static
    {
        $this->withSuffix = false;

        return $this;
    }

    /** ディスクリプションの既定値。seo_metas の入力があればそちらを優先する。 */
    public function description(?string $description): static
    {
        if (filled($description)) {
            $this->description ??= $description;
        }

        return $this;
    }

    public function canonical(string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    public function noindex(): static
    {
        $this->robots = 'noindex,nofollow';

        return $this;
    }

    public function image(?Media $media): static
    {
        if ($media) {
            $this->ogImage = $media;
        }

        return $this;
    }

    public function type(string $type): static
    {
        $this->ogType = $type;

        return $this;
    }

    /**
     * パンくず。トップは自動で先頭に入るので、2階層目以降だけ渡す。
     *
     * @param  array<int, array{0: string, 1?: string|null}>  $items  [表示名, URL]
     */
    public function breadcrumbs(array $items): static
    {
        $this->breadcrumbs = collect($items)
            ->map(fn (array $item) => ['name' => $item[0], 'url' => $item[1] ?? null])
            ->all();

        return $this;
    }

    /** ページ固有の JSON-LD を足す（Menu / Article / Event など）。 */
    public function schema(?array $schema): static
    {
        if (filled($schema)) {
            $this->schemas[] = $schema;
        }

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 出力
    |--------------------------------------------------------------------------
    */

    public function metaTitle(): string
    {
        $base = $this->title ?: $this->settings->string('seo.default_title', '広瀬屋');

        if (! $this->withSuffix) {
            return $base;
        }

        $suffix = $this->settings->string('seo.title_suffix', '');

        // 既に店名が入っているタイトルに二重で付けない。
        if ($suffix === '' || str_contains($base, trim($suffix, '｜|- '))) {
            return $base;
        }

        return $base.$suffix;
    }

    public function metaDescription(): string
    {
        $text = $this->description ?: $this->settings->string('seo.default_description', '');

        return Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? ''), 140, '');
    }

    public function metaKeywords(): string
    {
        return $this->keywords ?: $this->settings->string('seo.default_keywords', '');
    }

    public function canonicalUrl(): string
    {
        return $this->canonical ?: url()->current();
    }

    /**
     * 準備中モードの間は、個別の指定にかかわらず必ず noindex。
     * 仮の営業時間・仮の価格が検索結果に載るのを防ぐ最後の砦。
     */
    public function metaRobots(): string
    {
        if ($this->settings->inPreparation()) {
            return 'noindex,nofollow';
        }

        return $this->robots ?: 'index,follow,max-image-preview:large';
    }

    public function ogTitle(): string
    {
        return $this->ogTitle ?: $this->metaTitle();
    }

    public function ogDescription(): string
    {
        return $this->ogDescription ?: $this->metaDescription();
    }

    public function ogType(): string
    {
        return $this->ogType;
    }

    public function ogImageUrl(): ?string
    {
        if ($this->ogImage) {
            return $this->ogImage->variantUrl('lg');
        }

        return null;
    }

    /** @return array<int, array{name: string, url: string|null}> */
    public function breadcrumbItems(): array
    {
        return $this->breadcrumbs;
    }

    public function hasBreadcrumbs(): bool
    {
        return $this->breadcrumbs !== [];
    }

    /** @return array<int, array<string, mixed>> */
    public function extraSchemas(): array
    {
        return $this->schemas;
    }
}
