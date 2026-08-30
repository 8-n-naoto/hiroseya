<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\Event;
use App\Models\HomeSection;
use App\Models\Media;
use App\Models\News;
use App\Services\ImageService;
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
 */
class MediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-content');
    }

    public function index(Request $request): View
    {
        $query = Media::query()->latest('id');

        if ($request->boolean('missing_alt')) {
            $query->where(function ($q) {
                $q->whereNull('alt')->orWhere('alt', '');
            });
        }

        $directory = $request->string('directory')->toString();
        if ($directory !== '') {
            $query->where('path', 'like', $directory.'/%');
        }

        /** @var LengthAwarePaginator $media */
        $media = $query->paginate(30)->withQueryString();

        return view('admin.media.index', [
            'media' => $media,
            'usages' => $this->usageMap(collect($media->items())->pluck('id')),
            'directories' => $this->directories(),
            'missingAlt' => $request->boolean('missing_alt'),
            'directory' => $directory,
        ]);
    }

    public function store(Request $request, ImageService $images): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:'.config('hiroseya.images.max_upload_kb', 8192)],
            'directory' => ['nullable', 'string', 'alpha_dash', 'max:40'],
            'alt' => ['nullable', 'string', 'max:191'],
        ]);

        $images->store(
            $request->file('file'),
            $request->string('directory')->toString() ?: 'uploads',
            $request->string('alt')->toString() ?: null,
        );

        return back()->with('status', '画像をアップロードしました。');
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $request->validate([
            'alt' => ['nullable', 'string', 'max:191'],
            'caption' => ['nullable', 'string', 'max:191'],
        ]);

        $media->update($request->only('alt', 'caption'));

        return back()->with('status', '画像情報を更新しました。');
    }

    public function destroy(Media $media, ImageService $images): RedirectResponse
    {
        if ($this->usageMap(collect([$media->id]))->isNotEmpty()) {
            return back()->withErrors([
                'media' => 'この画像は他の場所で使われているため削除できません。先に差し替えてください。',
            ]);
        }

        $images->delete($media);

        return back()->with('status', '画像を削除しました。');
    }

    /**
     * 保存先ディレクトリ（dishes / news / home / uploads ...）の一覧。
     * アップロード時に directory を自由入力させているため、DB の実データから拾う。
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

        HomeSection::query()
            ->where(fn ($q) => $q->whereIn('media_id', $ids)->orWhereIn('media_sp_id', $ids))
            ->get(['key', 'media_id', 'media_sp_id'])
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
