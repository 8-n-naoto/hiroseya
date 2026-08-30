<?php

namespace App\Support;

use App\Enums\ServiceType;
use App\Models\Dish;
use App\Models\DishCategory;
use App\Models\Event;
use App\Models\News;
use App\Models\SocialLink;
use App\Models\StoreProfile;
use App\Services\BusinessHourService;
use Illuminate\Support\Collection;

/**
 * 構造化データ（JSON-LD）。
 *
 * 実店舗の検索流入は「店名で調べた人」と「地域＋料理で調べた人」の 2 種類。
 * 前者にはナレッジパネル、後者にはローカル検索結果が効くため、
 * Restaurant（LocalBusiness のサブタイプ）を必ず出す。
 *
 * NAP（店名・住所・電話）はすべて store_profile から出す。
 * HTML の表示と JSON-LD で値がずれると、かえって信頼を落とすため、
 * 二重に書かず必ずこのクラスを通す。
 */
class StructuredData
{
    public function __construct(
        private readonly Settings $settings,
        private readonly BusinessHourService $hours,
    ) {}

    /** サイト全体で出す Restaurant。 */
    public function restaurant(StoreProfile $store): array
    {
        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            '@id' => url('/').'#restaurant',
            'name' => $store->name,
            'alternateName' => $this->settings->string('site.site_name_en') ?: null,
            'description' => $store->description ? $this->plain($store->description) : null,
            'url' => url('/'),
            'telephone' => $store->tel ?: null,
            'faxNumber' => $store->fax ?: null,
            'email' => $store->email ?: null,
            'servesCuisine' => ['うどん', 'そば', '和食'],
            'priceRange' => $this->priceRange(),
            'currenciesAccepted' => 'JPY',
            'paymentAccepted' => $store->payment_methods ? implode(', ', $store->payment_methods) : null,
            'address' => $this->address($store),
            'geo' => $this->geo($store),
            'hasMap' => $this->settings->string('access.map_link') ?: null,
            'openingHoursSpecification' => $this->hours->schemaOpeningHours() ?: null,
            'specialOpeningHoursSpecification' => $this->hours->schemaSpecialHours() ?: null,
            'sameAs' => $this->sameAs(),
            'hasMenu' => route('menu.index'),
            'image' => $this->siteImage(),
            'publicAccess' => true,
        ], fn ($value) => $value !== null && $value !== []);

        if ($store->seats) {
            $data['maximumAttendeeCapacity'] = $store->seats;
        }

        return $data;
    }

    /** トップページの WebSite。サイト名の表示を安定させる。 */
    public function website(StoreProfile $store): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'url' => url('/'),
            'name' => $this->settings->string('site.site_name', $store->name),
            'inLanguage' => 'ja',
            'publisher' => ['@id' => url('/').'#restaurant'],
        ];
    }

    /**
     * パンくず。
     *
     * @param  array<int, array{name: string, url: string|null}>  $items
     */
    public function breadcrumbs(array $items): ?array
    {
        if ($items === []) {
            return null;
        }

        $list = [[
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'ホーム',
            'item' => url('/'),
        ]];

        foreach ($items as $index => $item) {
            $entry = [
                '@type' => 'ListItem',
                'position' => $index + 2,
                'name' => $item['name'],
            ];

            if (filled($item['url'])) {
                $entry['item'] = $item['url'];
            }

            $list[] = $entry;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    /**
     * お品書き全体の Menu。
     *
     * @param  Collection<int, DishCategory>  $categories  dishes.variants を eager load 済みであること
     */
    public function menu(Collection $categories, ServiceType $service): array
    {
        $sections = $categories
            ->map(function (DishCategory $category) use ($service): ?array {
                $items = $category->dishes
                    ->map(fn (Dish $dish) => $this->menuItem($dish, $service))
                    ->filter()
                    ->values();

                if ($items->isEmpty()) {
                    return null;
                }

                return array_filter([
                    '@type' => 'MenuSection',
                    'name' => $category->name,
                    'description' => $category->description ?: null,
                    'hasMenuItem' => $items->all(),
                ], fn ($value) => $value !== null);
            })
            ->filter()
            ->values();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Menu',
            '@id' => route('menu.index').'#'.$service->value,
            'name' => $service === ServiceType::Takeout ? 'お持ち帰りメニュー' : 'お品書き',
            'inLanguage' => 'ja',
            'hasMenuSection' => $sections->all(),
        ];
    }

    private function menuItem(Dish $dish, ServiceType $service): ?array
    {
        $variants = $dish->variantsFor($service);

        if ($variants->isEmpty()) {
            return null;
        }

        $offers = $variants->map(fn ($variant) => array_filter([
            '@type' => 'Offer',
            'price' => (string) $variant->price,
            'priceCurrency' => 'JPY',
            'name' => $variant->label ?: null,
            'availability' => $dish->is_sold_out
                ? 'https://schema.org/SoldOut'
                : 'https://schema.org/InStock',
        ], fn ($value) => $value !== null))->values()->all();

        return array_filter([
            '@type' => 'MenuItem',
            'name' => $dish->name,
            'description' => $dish->description ?: null,
            'url' => $dish->has_detail_page ? route('menu.show', $dish) : null,
            'image' => $dish->mainImage?->variantUrl('md'),
            'offers' => $offers,
        ], fn ($value) => $value !== null);
    }

    /** 料理詳細ページの MenuItem 単体。 */
    public function dish(Dish $dish): array
    {
        $item = $this->menuItem($dish, ServiceType::DineIn)
            ?? $this->menuItem($dish, ServiceType::Takeout)
            ?? ['@type' => 'MenuItem', 'name' => $dish->name];

        return array_merge(['@context' => 'https://schema.org'], $item, [
            'menuAddOn' => null,
            'isPartOf' => ['@id' => url('/').'#restaurant'],
        ]);
    }

    /** お知らせの Article。 */
    public function news(News $news, StoreProfile $store): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $news->title,
            'description' => $news->excerpt ? $this->plain($news->excerpt) : null,
            'datePublished' => $news->published_at?->toIso8601String(),
            'dateModified' => $news->updated_at?->toIso8601String(),
            'image' => $news->mainImage?->variantUrl('lg'),
            'mainEntityOfPage' => route('news.show', $news->slug),
            'inLanguage' => 'ja',
            'author' => ['@type' => 'Organization', 'name' => $store->name, '@id' => url('/').'#restaurant'],
            'publisher' => ['@id' => url('/').'#restaurant'],
        ], fn ($value) => $value !== null);
    }

    /** イベントの Event。開催期間が入っているものだけ意味がある。 */
    public function event(Event $event, StoreProfile $store): ?array
    {
        if (! $event->starts_on) {
            return null;
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->title,
            'description' => $event->excerpt ? $this->plain($event->excerpt) : null,
            'startDate' => $event->starts_on->toDateString(),
            'endDate' => $event->ends_on?->toDateString(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'image' => $event->mainImage?->variantUrl('lg'),
            'url' => route('events.show', $event->slug),
            'location' => array_filter([
                '@type' => 'Restaurant',
                'name' => $store->name,
                'address' => $this->address($store),
            ], fn ($value) => $value !== null),
            'organizer' => ['@id' => url('/').'#restaurant'],
        ], fn ($value) => $value !== null);
    }

    /*
    |--------------------------------------------------------------------------
    | 部品
    |--------------------------------------------------------------------------
    */

    private function address(StoreProfile $store): ?array
    {
        if (blank($store->prefecture) && blank($store->address_line)) {
            return null;
        }

        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => trim($store->address_line.' '.$store->building) ?: null,
            'addressLocality' => $store->city ?: null,
            'addressRegion' => $store->prefecture ?: null,
            'postalCode' => $store->postal_code ?: null,
            'addressCountry' => 'JP',
        ], fn ($value) => $value !== null);
    }

    private function geo(StoreProfile $store): ?array
    {
        if (! $store->latitude || ! $store->longitude) {
            return null;
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $store->latitude,
            'longitude' => (float) $store->longitude,
        ];
    }

    /** @return array<int, string> */
    private function sameAs(): array
    {
        $urls = SocialLink::visible()->pluck('url')->filter()->values()->all();

        if ($this->settings->bool('seo.gbp_enabled') && $this->settings->string('seo.gbp_url') !== '') {
            $urls[] = $this->settings->string('seo.gbp_url');
        }

        return $urls;
    }

    /**
     * 価格帯。公開中の料理の実データから出す。
     * 手入力にすると価格改定のたびに古い値が残るため、自動で計算する。
     */
    private function priceRange(): ?string
    {
        $prices = Dish::listable()
            ->with('variants')
            ->get()
            ->flatMap(fn (Dish $dish) => $dish->variants->pluck('price'))
            ->filter();

        if ($prices->isEmpty()) {
            return null;
        }

        return '¥'.number_format($prices->min()).'〜¥'.number_format($prices->max());
    }

    /** トップのメインビジュアルをサイト代表画像として使う。 */
    private function siteImage(): ?string
    {
        $hero = \App\Models\HomeSection::query()->where('key', 'hero')->first();

        return $hero?->image?->variantUrl('lg');
    }

    private function plain(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
    }
}
