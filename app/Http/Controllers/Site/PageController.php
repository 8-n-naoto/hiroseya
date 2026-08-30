<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use App\Support\SiteContext;
use Illuminate\View\View;

class PageController extends Controller
{
    public function privacy(Seo $seo, SiteContext $site): View
    {
        $seo->page('privacy')
            ->title('プライバシーポリシー')
            ->breadcrumbs([['プライバシーポリシー', route('privacy')]]);

        return view('site.privacy', ['store' => $site->store()]);
    }
}
