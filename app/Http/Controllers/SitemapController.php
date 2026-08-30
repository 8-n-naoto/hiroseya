<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Event;
use App\Models\News;
use App\Support\Settings;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * sitemap.xml を動的に出し分ける。
 *
 * 準備中モードがONの間は空のサイトマップを返し、まだ完成していないページが
 * 検索エンジンに登録されるのを防ぐ。
 *
 * 静的ファイルとして書き出さないのは、書き出しを忘れると内容が古いまま
 * 残り続けるため。お知らせを 1 本足すたびに誰かが操作する運用は続かない。
 */
class SitemapController extends Controller
{
    public function __invoke(Settings $settings): Response
    {
        $xml = view('sitemap', ['urls' => $settings->inPreparation() ? [] : $this->urls($settings)])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex');
    }

    /** @return array<int, array<string, string>> */
    private function urls(Settings $settings): array
    {
        $urls = [
            $this->url(route('home'), 'daily', '1.0'),
            $this->url(route('menu.index'), 'weekly', '0.9'),
            $this->url(route('menu.takeout'), 'weekly', '0.8'),
            $this->url(route('access'), 'monthly', '0.8'),
            $this->url(route('news.index'), 'weekly', '0.6'),
            $this->url(route('events.index'), 'weekly', '0.6'),
            $this->url(route('contact.create'), 'yearly', '0.5'),
            $this->url(route('privacy'), 'yearly', '0.2'),
        ];

        // 詳細ページを持つ料理だけ。品数が多いため、全料理は載せない。
        foreach (Dish::listable()->where('has_detail_page', true)->get() as $dish) {
            $urls[] = $this->url(route('menu.show', $dish->slug), 'monthly', '0.7', $dish->updated_at);
        }

        foreach (News::listable()->latestFirst()->get() as $news) {
            $urls[] = $this->url(route('news.show', $news->slug), 'yearly', '0.5', $news->updated_at);
        }

        foreach (Event::listable()->get() as $event) {
            $urls[] = $this->url(route('events.show', $event->slug), 'monthly', '0.5', $event->updated_at);
        }

        return $urls;
    }

    /** @return array<string, string> */
    private function url(string $loc, string $changefreq, string $priority, ?Carbon $lastmod = null): array
    {
        return array_filter([
            'loc' => $loc,
            'lastmod' => $lastmod?->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ]);
    }
}
