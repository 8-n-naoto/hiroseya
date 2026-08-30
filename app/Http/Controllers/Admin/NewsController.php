<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleRequest;
use App\Models\ActivityLog;
use App\Models\News;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * お知らせ。
 *
 * 公開日時を未来にすれば予約公開になる（サイトには時刻を過ぎてから出る）。
 * 下書きと公開予約は、ログイン中なら公開サイト側でもそのまま確認できる。
 */
class NewsController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.news.index', [
            'items' => News::query()
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only(['status', 'q']),
        ]);
    }

    public function create(): View
    {
        return view('admin.news.edit', [
            'news' => new News(['status' => PublishStatus::Draft, 'published_at' => now()]),
        ]);
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        $news = new News;
        $this->fill($news, $request);

        ActivityLog::record('create', $news, "お知らせ「{$news->title}」を追加しました。");

        return redirect()->route('admin.news.edit', $news)->with('status', 'お知らせを保存しました。');
    }

    public function edit(News $news): View
    {
        return view('admin.news.edit', ['news' => $news->load(['mainImage', 'seoMeta'])]);
    }

    public function update(ArticleRequest $request, News $news): RedirectResponse
    {
        $this->fill($news, $request);

        ActivityLog::record('update', $news, "お知らせ「{$news->title}」を更新しました。");

        return redirect()->route('admin.news.edit', $news)->with('status', 'お知らせを更新しました。');
    }

    public function destroy(News $news): RedirectResponse
    {
        $title = $news->title;
        $news->delete();

        ActivityLog::record('delete', $news, "お知らせ「{$title}」を削除しました。");

        return redirect()->route('admin.news.index')->with('status', "お知らせ「{$title}」を削除しました。");
    }

    private function fill(News $news, ArticleRequest $request): void
    {
        $data = $request->validated();

        $news->fill([
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'],
            'main_media_id' => $data['main_media_id'] ?? null,
            'published_at' => $data['published_at'] ?? null,
            'status' => $data['status'],
        ]);

        if (blank($news->slug) || filled($data['slug'] ?? null)) {
            $news->slug = Slug::forModel($news, (string) ($data['slug'] ?? ''), 'news-'.now()->format('YmdHis'));
        }

        $news->save();

        $this->saveSeo($news, $data);
    }

    /** SEOメタ。未入力ならレコードごと消し、モデル側の自動生成に戻す。 */
    private function saveSeo(News $news, array $data): void
    {
        $title = $data['seo_title'] ?? null;
        $description = $data['seo_description'] ?? null;

        if (blank($title) && blank($description)) {
            $news->seoMeta()->delete();

            return;
        }

        $news->seoMeta()->updateOrCreate([], [
            'title' => $title,
            'description' => $description,
        ]);
    }
}
