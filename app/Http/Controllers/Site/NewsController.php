<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Support\Seo;
use App\Support\SiteContext;
use App\Support\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Seo $seo): View
    {
        $seo->page('news')
            ->title('お知らせ')
            ->breadcrumbs([['お知らせ', route('news.index')]]);

        return view('site.news.index', [
            'items' => News::listable()->latestFirst()->with('mainImage')->paginate(12),
        ]);
    }

    public function show(
        Request $request,
        string $slug,
        Seo $seo,
        SiteContext $site,
        StructuredData $schema,
    ): View {
        $news = News::query()->where('slug', $slug)->with(['mainImage', 'seoMeta'])->firstOrFail();

        $visible = $news->isPublished()
            && $news->published_at !== null
            && ! $news->published_at->isFuture();

        // 公開予約中・下書きは、ログイン中の管理者だけが確認できる。
        abort_unless($visible || $request->user(), 404);

        $seo->model($news)
            ->breadcrumbs([
                ['お知らせ', route('news.index')],
                [$news->title, route('news.show', $news->slug)],
            ])
            ->type('article')
            ->schema($schema->news($news, $site->store()));

        if (! $visible) {
            $seo->noindex();
        }

        return view('site.news.show', [
            'news' => $news,
            'isPreview' => ! $visible,
            'prev' => News::listable()->where('published_at', '<', $news->published_at)
                ->orderByDesc('published_at')->first(),
            'next' => News::listable()->where('published_at', '>', $news->published_at)
                ->orderBy('published_at')->first(),
        ]);
    }
}
