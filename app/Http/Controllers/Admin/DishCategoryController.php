<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DishCategory;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 料理の分類。
 *
 * 分類は「お品書きの並べ方」を決めるもので、絞り込みの正ではない。
 * 例えば『丼もの』（店内）の料理でも、持ち帰り価格を持っていれば
 * お持ち帰りのページに出る。ここを取り違えると、実際に持ち帰れる品が
 * サイトから消える。
 */
class DishCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.dish-categories.index', [
            'categories' => DishCategory::query()
                ->withCount('dishes')
                ->ordered()
                ->get()
                ->groupBy(fn (DishCategory $c) => $c->service_type->value),
            'serviceTypes' => ServiceType::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.dish-categories.edit', [
            'category' => new DishCategory(['service_type' => ServiceType::DineIn, 'is_visible' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = new DishCategory;
        $this->fill($category, $request);

        ActivityLog::record('create', $category, "料理の分類「{$category->name}」を追加しました。");

        return redirect()->route('admin.dish-categories.index')->with('status', '分類を追加しました。');
    }

    public function edit(DishCategory $dishCategory): View
    {
        return view('admin.dish-categories.edit', ['category' => $dishCategory]);
    }

    public function update(Request $request, DishCategory $dishCategory): RedirectResponse
    {
        $this->fill($dishCategory, $request);

        ActivityLog::record('update', $dishCategory, "料理の分類「{$dishCategory->name}」を更新しました。");

        return redirect()->route('admin.dish-categories.index')->with('status', '分類を更新しました。');
    }

    /**
     * 削除。
     * 料理が残っている分類は消させない。消すと料理が分類なしになり、
     * お品書きの並びが崩れたことに店舗側が気づけない。
     */
    public function destroy(DishCategory $dishCategory): RedirectResponse
    {
        if ($dishCategory->dishes()->exists()) {
            return back()->withErrors([
                'category' => 'この分類には料理が登録されているため削除できません。先に料理の分類を変更してください。',
            ]);
        }

        $name = $dishCategory->name;
        $dishCategory->delete();

        ActivityLog::record('delete', null, "料理の分類「{$name}」を削除しました。");

        return back()->with('status', '分類を削除しました。');
    }

    public function sort(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:dish_categories,id'],
        ]);

        foreach ($request->input('order', []) as $position => $id) {
            DishCategory::whereKey($id)->update(['sort_order' => $position]);
        }

        return back()->with('status', '並び順を保存しました。');
    }

    private function fill(DishCategory $category, Request $request): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable', 'string', 'max:100', 'alpha_dash',
                Rule::unique('dish_categories', 'slug')->ignore($category->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'image_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'service_type' => ['required', Rule::in(array_column(ServiceType::cases(), 'value'))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [], [
            'name' => '分類名',
            'slug' => 'URL（英数字）',
            'service_type' => '提供区分',
        ]);

        $category->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image_media_id' => $data['image_media_id'] ?? null,
            'service_type' => $data['service_type'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_visible' => $request->boolean('is_visible'),
        ]);

        if (blank($category->slug) || filled($data['slug'] ?? null)) {
            $category->slug = Slug::forModel($category, (string) ($data['slug'] ?? ''), $data['name']);
        }

        $category->save();
    }
}
