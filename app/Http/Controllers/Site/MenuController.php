<?php

namespace App\Http\Controllers\Site;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Services\MenuService;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * お品書き。
 *
 * 店内とお持ち帰りを 1 ページのタブにせず、別の URL に分けている。
 * 「揖斐川町 うどん 持ち帰り」で探す人に対して、タブの中身は検索結果に
 * 出せないため。ページを分ければ、それぞれのタイトルと説明文で拾える。
 */
class MenuController extends Controller
{
    public function __construct(private readonly MenuService $menu) {}

    public function index(Seo $seo, StructuredData $schema): View
    {
        $categories = $this->menu->grouped(ServiceType::DineIn);

        $seo->page('menu')
            ->title('お品書き')
            ->breadcrumbs([['お品書き', route('menu.index')]])
            ->schema($categories->isNotEmpty() ? $schema->menu($categories, ServiceType::DineIn) : null);

        return view('site.menu.index', [
            'service' => ServiceType::DineIn,
            'categories' => $categories,
            'hasTakeout' => $this->menu->hasAny(ServiceType::Takeout),
        ]);
    }

    public function takeout(Seo $seo, StructuredData $schema): View
    {
        $categories = $this->menu->grouped(ServiceType::Takeout);

        $seo->page('takeout')
            ->title('お持ち帰りメニュー')
            ->description('広瀬屋のお持ち帰りメニュー。お弁当・丼もの・揚げ物を店頭とお電話で承っております。')
            ->breadcrumbs([
                ['お品書き', route('menu.index')],
                ['お持ち帰り', route('menu.takeout')],
            ])
            ->schema($categories->isNotEmpty() ? $schema->menu($categories, ServiceType::Takeout) : null);

        return view('site.menu.index', [
            'service' => ServiceType::Takeout,
            'categories' => $categories,
            'hasTakeout' => true,
        ]);
    }

    /**
     * 料理の詳細ページ。
     *
     * 全料理に詳細ページは作らない（has_detail_page）。品数が多く、
     * 中身の薄いページを大量に作ると、サイト全体の評価をむしろ下げるため。
     */
    public function show(Request $request, string $slug, Seo $seo, StructuredData $schema): View
    {
        $dish = Dish::query()
            ->where('slug', $slug)
            ->where('has_detail_page', true)
            ->with(['category', 'variants', 'mainImage', 'images', 'allergens', 'seoMeta'])
            ->firstOrFail();

        // 下書き・提供期間外は、ログイン中の管理者だけが確認できる（公開前のプレビュー）。
        $visible = $dish->isPublished() && $dish->isAvailableToday();

        abort_unless($visible || $request->user(), 404);

        $seo->model($dish)
            ->breadcrumbs([
                ['お品書き', route('menu.index')],
                [$dish->name, route('menu.show', $dish)],
            ])
            ->type('article')
            ->schema($schema->dish($dish));

        if (! $visible) {
            $seo->noindex();
        }

        return view('site.menu.show', [
            'dish' => $dish,
            'isPreview' => ! $visible,
            'related' => Dish::listable()
                ->where('category_id', $dish->category_id)
                ->whereKeyNot($dish->id)
                ->with(['variants', 'mainImage'])
                ->ordered()
                ->limit(3)
                ->get(),
        ]);
    }
}
