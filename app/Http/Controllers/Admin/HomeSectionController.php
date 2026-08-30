<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Dish;
use App\Models\HomeRecommendedDish;
use App\Models\HomeSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * トップページ。
 *
 * 節（セクション）の「型」は config/hiroseya.php で固定し、
 * 中身（画像・見出し・本文・表示/非表示・順序）だけを変えられるようにしている。
 * 自由に組めるページビルダーにはしない。自由度を上げるほど、店舗側が
 * レイアウトを壊せてしまい、結局こちらへ修正依頼が来る。
 */
class HomeSectionController extends Controller
{
    public function index(): View
    {
        return view('admin.home-sections.index', [
            'sections' => HomeSection::ordered()->with(['image', 'imageSp'])->get(),
            'recommended' => HomeRecommendedDish::query()
                ->with('dish')
                ->orderBy('sort_order')
                ->get()
                ->filter(fn (HomeRecommendedDish $row) => $row->dish !== null),
            'dishes' => Dish::published()->ordered()->get(['id', 'name']),
        ]);
    }

    public function edit(HomeSection $homeSection): View
    {
        return view('admin.home-sections.edit', [
            'section' => $homeSection->load(['image', 'imageSp']),
            'definition' => $homeSection->definition(),
        ]);
    }

    public function update(Request $request, HomeSection $homeSection): RedirectResponse
    {
        $definition = $homeSection->definition();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:191'],
            'body' => ['nullable', 'string', 'max:5000'],
            'media_id' => ['nullable', 'integer', 'exists:media,id'],
            'media_sp_id' => ['nullable', 'integer', 'exists:media,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ], [], [
            'title' => '見出し',
            'subtitle' => '小見出し',
            'body' => '本文',
            'media_id' => '画像',
            'media_sp_id' => 'スマートフォン用の画像',
            'limit' => '表示件数',
        ]);

        $options = $homeSection->options ?? [];
        if ($request->filled('limit')) {
            $options['limit'] = (int) $data['limit'];
        }

        $homeSection->fill([
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'body' => ($definition['body'] ?? false) ? ($data['body'] ?? null) : $homeSection->body,
            'media_id' => ($definition['image'] ?? false) ? ($data['media_id'] ?? null) : $homeSection->media_id,
            'media_sp_id' => ($definition['image_sp'] ?? false) ? ($data['media_sp_id'] ?? null) : $homeSection->media_sp_id,
            'options' => $options,
            // メインビジュアルなど、非表示にできない節は必ず表示のままにする。
            'is_visible' => $homeSection->isLocked() ? true : $request->boolean('is_visible'),
        ])->save();

        ActivityLog::record('update', $homeSection, "トップページの「{$homeSection->label()}」を更新しました。");

        return redirect()->route('admin.home-sections.index')
            ->with('status', "「{$homeSection->label()}」を更新しました。");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:home_sections,id'],
        ]);

        foreach ($request->input('order', []) as $position => $id) {
            HomeSection::whereKey($id)->update(['sort_order' => $position]);
        }

        return back()->with('status', 'トップページの並び順を保存しました。');
    }

    /** おすすめ料理の差し替え。選ばれた順にそのまま並ぶ。 */
    public function updateRecommended(Request $request): RedirectResponse
    {
        $request->validate([
            'dishes' => ['nullable', 'array', 'max:12'],
            'dishes.*' => ['nullable', 'integer', 'exists:dishes,id'],
        ], [], ['dishes' => 'おすすめ料理']);

        $ids = collect($request->input('dishes', []))->filter()->unique()->values();

        HomeRecommendedDish::query()->delete();

        foreach ($ids as $order => $id) {
            HomeRecommendedDish::create(['dish_id' => $id, 'sort_order' => $order]);
        }

        return back()->with('status', 'おすすめ料理を保存しました。');
    }
}
