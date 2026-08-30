<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use App\Support\SiteContext;
use Illuminate\View\View;

/**
 * アクセス・営業時間。
 *
 * 実店舗のサイトで最も見られるページ。「今日やっているか」「どう行くか」
 * の 2 点に答えることだけを目的にしている。
 */
class AccessController extends Controller
{
    public function __invoke(Seo $seo, SiteContext $site): View
    {
        $seo->page('access')
            ->title('アクセス・営業時間')
            ->breadcrumbs([['アクセス・営業時間', route('access')]]);

        return view('site.access', [
            'store' => $site->store(),
            'hours' => $site->hours(),
            'specialDays' => $site->hours()->upcomingSpecialDays(),
        ]);
    }
}
