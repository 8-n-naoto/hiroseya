<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\HomeSection;
use App\Models\News;
use App\Services\MenuService;
use App\Support\Seo;
use App\Support\SiteContext;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * トップページ。
 *
 * 表示する節と順序は home_sections が持ち、型は config/hiroseya.php で固定している。
 * ページビルダーにはしていない（自由に組めるようにすると、店舗側が
 * レイアウトを壊せてしまい、結局こちらに修正依頼が来るため）。
 */
class HomeController extends Controller
{
    public function __invoke(Seo $seo, SiteContext $site, MenuService $menu): View
    {
        $sections = HomeSection::visible()->ordered()->with(['image', 'imageSp'])->get();

        // トップページのタイトルは店名の接尾辞を付けない（既定タイトルに店名が入っているため）。
        $seo->page('home')
            ->title($site->store()->catch_copy ?: null)
            ->description($site->store()->description)
            ->image($sections->firstWhere('key', 'hero')?->image)
            ->type('website')
            ->noSuffix();

        return view('site.home', [
            'sections' => $sections,
            'recommended' => $this->recommendedFor($sections, $menu),
            'news' => $this->newsFor($sections),
            'events' => $this->eventsFor($sections),
        ]);
    }

    private function recommendedFor(Collection $sections, MenuService $menu): Collection
    {
        $section = $sections->firstWhere('key', 'recommend');

        return $section ? $menu->recommended((int) $section->option('limit', 6)) : collect();
    }

    private function newsFor(Collection $sections): Collection
    {
        $section = $sections->firstWhere('key', 'news');

        if (! $section) {
            return collect();
        }

        return News::listable()->latestFirst()->limit((int) $section->option('limit', 3))->get();
    }

    private function eventsFor(Collection $sections): Collection
    {
        $section = $sections->firstWhere('key', 'events');

        if (! $section) {
            return collect();
        }

        $query = Event::listable();

        if ($section->option('ongoing_only', true)) {
            $query->ongoing();
        }

        return $query->orderBy('sort_order')->orderByDesc('starts_on')
            ->with('mainImage')
            ->limit((int) $section->option('limit', 2))
            ->get();
    }
}
