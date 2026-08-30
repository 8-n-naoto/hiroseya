<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Dish;
use App\Models\Event;
use App\Models\HomeSection;
use App\Models\Media;
use App\Models\News;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 画像ライブラリ。
 *
 * 料理・お知らせ・イベント・トップページのすべてが media を参照するため、
 * 「どこで使われているか」を出さないと、削除してよい画像かどうか判断できない。
 *
 * 権限（manage-content）はルート側で掛けている。
 * Laravel 11 以降、コントローラーの $this->middleware() は使えない。
 */
class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Media::query()->latest('id');

        if ($request->boolean('missing_alt')) {
            $query->where(fn ($q) => $q->whereNull('alt')->orWhere('alt', ''));
        }

        $directory = $request->string('directory')->toString();
        if ($directory !== '') {
            $query->where('path', 'like', $directory.'/%');
        }

        $keyword = $request->string('q')->toString();
        if ($keyword !== '') {
            $query->where(fn ($q) => $q
                ->where('original_name', 'like', '%'.$keyword.'%')
                ->orWhere('alt', 'like', '%'.$keyword.'%')
                ->orWhere('path', 'like', '%'.$keyword.'%'));
        }

        /** @var LengthAwarePaginator $media */
        $media = $query->paginate(30)->withQueryString();

        return view('admin.media.index', [
            'media' => $media,
            'usages' => $this->usageMap(collect($media->items())->pluck('id')),
            'directories' => $this->directories(),
            'missingAlt' => $request->boolean('missing_alt'),
            'directory' => $directory,
            'keyword' => $keyword,
            'missingAltCount' => Media::query()
                ->where(fn ($q) => $q->whereNull('alt')->orWhere('alt', ''))->count(),
        ]);
    }

    /** 画像選択の重ね窓が読む一覧。 */
    public function picker(Request $request): JsonResponse
    {
        $keyword = $request->string('q')->toString();

        $media = Media::query()
            ->when($keyword !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('original_name', 'like', '%'.$keyword.'%')
                ->orWhere('alt', 'like', '%'.$keyword.'%')
                ->orWhere('path', 'like', '%'.$keyword.'%')))
            ->latest('id')
            ->limit(120)
            ->get();

        return response()->json($media->map(fn (Media $item) => [
            'id' => $item->id,
            'url' => $item->variantUrl('sm'),
            'name' => $item->original_name ?: $item->path,
            'alt' => $item->alt,
        ]));
    }

    /**
     * アップロード。まとめて選べるようにしてある。
     * 1 枚ずつしか上げられないと、料理写真の登録が現実的な手間で終わらない。
     */
    public function store(Request $request, ImageService $images): RedirectResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['image', 'mimes:'.implode(',', config('hiroseya.images.accepted')), 'max:'.config('hiroseya.images.max_upload_kb', 8192)],
            'directory' => ['nullable', 'string', 'alpha_dash', 'max:40'],
            'alt' => ['nullable', 'string', 'max:191'],
        ], [], [
            'files' => '画像ファイル',
            'directory' => '保存先',
            'alt' => '代替テキスト',
        ]);

        $directory = $request->string('directory')->toString() ?: 'uploads';
        $alt = $request->string('alt')->toString() ?: null;
        $saved = 0;
        $failed = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $images->store($file, $directory, $alt);
                $saved++;
            } catch (\Throwable $e) {
                // 1 枚失敗しても残りは保存する。何が落ちたかは画面に出す。
                $failed[] = $file->getClientOriginalName();
                report($e);
            }
        }

        ActivityLog::record('create', null, "画像を{$saved}件アップロードしました。");

        if ($failed !== []) {
            return back()
                ->with('status', "{$saved}件をアップロードしました。")
                ->with('warning', '次の画像は保存できませんでした：'.implode('、', $failed));
        }

        return back()->with('status', "{$saved}件の画像をアップロードしました。");
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $validated = $request->validate([
            'alt' => ['nullable', 'string', 'max:191'],
            'caption' => ['nullable', 'string', 'max:191'],
        ], [], ['alt' => '代替テキスト', 'caption' => 'キャプション']);

        $media->update($validated);

        return back()->with('status', '画像情報を更新しました。');
    }

    public function destroy(Media $media, ImageService $images): RedirectResponse
    {
        if ($this->usageMap(collect([$media->id])) !== []) {
            return back()->withErrors([
                'media' => 'この画像は他の場所で使われているため削除できません。先に差し替えてください。',
            ]);
        }

        $name = $media->original_name ?: $media->path;
        $images->delete($media);

        ActivityLog::record('delete', null, "画像「{$name}」を削除しました。");

        return back()->with('status', '画像を削除しました。');
    }

    /**
     * 保存先ディレクトリ（dishes / news / home / uploads ...）の一覧。
     *
     * @return array<int, string>
     */
    private function directories(): array
    {
        return Media::query()
            ->pluck('path')
            ->map(fn (string $path) => Str::before($path, '/'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * 指定した media_id ごとに「どこで使われているか」のラベル一覧を作る。
     * 一覧ページ（最大30件）を N+1 にしないよう、テーブルごとにまとめて取得する。
     *
     * @param  Collection<int, int>  $mediaIds
     * @return array<int, array<int, string>>
     */
    private function usageMap(Collection $mediaIds): array
    {
        $ids = $mediaIds->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $out = [];
        $add = function (int $mediaId, string $label) use (&$out): void {
            $out[$mediaId][] = $label;
        };

        Dish::withTrashed()->whereIn('main_media_id', $ids)->get(['id', 'name', 'main_media_id'])
            ->each(fn (Dish $dish) => $add($dish->main_media_id, "料理（メイン）: {$dish->name}"));

        DB::table('dish_media')
            ->join('dishes', 'dishes.id', '=', 'dish_media.dish_id')
            ->whereIn('dish_media.media_id', $ids)
            ->get(['dish_media.media_id', 'dishes.name'])
            ->each(fn ($row) => $add($row->media_id, "料理（追加画像）: {$row->name}"));

        News::withTrashed()->whereIn('main_media_id', $ids)->get(['id', 'title', 'main_media_id'])
            ->each(fn (News $news) => $add($news->main_media_id, "お知らせ: {$news->title}"));

        Event::withTrashed()->whereIn('main_media_id', $ids)->get(['id', 'title', 'main_media_id'])
            ->each(fn (Event $event) => $add($event->main_media_id, "イベント: {$event->title}"));

        DB::table('dish_categories')->whereIn('image_media_id', $ids)->get(['image_media_id', 'name'])
            ->each(fn ($row) => $add($row->image_media_id, "料理の分類: {$row->name}"));

        DB::table('seo_metas')->whereIn('og_image_media_id', $ids)->get(['og_image_media_id', 'page_key'])
            ->each(fn ($row) => $add($row->og_image_media_id, 'SEO（OG画像）: '.($row->page_key ?: 'ページ')));

        HomeSection::query()
            ->where(fn ($q) => $q->whereIn('media_id', $ids)->orWhereIn('media_sp_id', $ids))
            ->get(['id', 'key', 'media_id', 'media_sp_id'])
            ->each(function (HomeSection $section) use ($ids, $add): void {
                $label = $section->label();
                if ($section->media_id && $ids->contains($section->media_id)) {
                    $add($section->media_id, "トップページ: {$label}");
                }
                if ($section->media_sp_id && $ids->contains($section->media_sp_id)) {
                    $add($section->media_sp_id, "トップページ（SP）: {$label}");
                }
            });

        return $out;
    }
}
