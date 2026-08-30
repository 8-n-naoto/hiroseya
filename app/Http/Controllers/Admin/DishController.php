<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DishRequest;
use App\Models\ActivityLog;
use App\Models\Allergen;
use App\Models\Dish;
use App\Models\DishCategory;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 料理の管理。
 *
 * 価格は必ず dish_variants 側に持つ。実際のお品書きに
 * 「セット/単品」「二枚/三枚」「小/中/大」「店内/持ち帰り」の価格差があり、
 * 1 料理 1 価格では入力できないため。
 */
class DishController extends Controller
{
    public function index(Request $request): View
    {
        $dishes = Dish::query()
            ->with(['category', 'variants', 'mainImage'])
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('name_kana', 'like', '%'.$request->string('q').'%')))
            ->when($request->boolean('no_image'), fn ($q) => $q->whereNull('main_media_id'))
            ->ordered()
            ->paginate(30)
            ->withQueryString();

        return view('admin.dishes.index', [
            'dishes' => $dishes,
            'categories' => DishCategory::ordered()->get(),
            'filters' => $request->only(['category', 'status', 'q', 'no_image']),
            'noImageCount' => Dish::published()->whereNull('main_media_id')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.dishes.edit', [
            'dish' => new Dish(['status' => PublishStatus::Draft, 'sort_order' => 0]),
            'categories' => DishCategory::ordered()->get(),
            'allergens' => Allergen::orderBy('sort_order')->get(),
            'selectedAllergens' => [],
            'variants' => [['label' => '', 'price' => '', 'service_type' => ServiceType::DineIn->value, 'is_default' => true]],
            'extraImages' => [],
        ]);
    }

    public function store(DishRequest $request): RedirectResponse
    {
        $dish = DB::transaction(function () use ($request): Dish {
            $dish = new Dish;
            $this->fill($dish, $request);

            return $dish;
        });

        ActivityLog::record('create', $dish, "料理「{$dish->name}」を追加しました。");

        return redirect()->route('admin.dishes.edit', $dish)->with('status', '料理を追加しました。');
    }

    public function edit(Dish $dish): View
    {
        $dish->load(['variants', 'allergens', 'mainImage', 'images']);

        return view('admin.dishes.edit', [
            'dish' => $dish,
            'categories' => DishCategory::ordered()->get(),
            'allergens' => Allergen::orderBy('sort_order')->get(),
            'selectedAllergens' => $dish->allergens->pluck('id')->all(),
            'variants' => $dish->variants->map(fn ($v) => [
                'label' => $v->label,
                'price' => $v->price,
                'service_type' => $v->service_type->value,
                'is_default' => (bool) $v->is_default,
            ])->values()->all(),
            'extraImages' => $dish->images->all(),
        ]);
    }

    public function update(DishRequest $request, Dish $dish): RedirectResponse
    {
        DB::transaction(fn () => $this->fill($dish, $request));

        ActivityLog::record('update', $dish, "料理「{$dish->name}」を更新しました。");

        return redirect()->route('admin.dishes.edit', $dish)->with('status', '料理を更新しました。');
    }

    public function destroy(Dish $dish): RedirectResponse
    {
        $name = $dish->name;
        $dish->delete();

        ActivityLog::record('delete', $dish, "料理「{$name}」を削除しました。");

        return redirect()->route('admin.dishes.index')->with('status', "料理「{$name}」を削除しました。");
    }

    /**
     * 複製。
     * 「みそかつ定食」と「みそかつ定食（冬）」のように、
     * 1 か所だけ違う品を作る場面が多いため。複製は必ず下書きで作る。
     */
    public function duplicate(Dish $dish): RedirectResponse
    {
        $copy = DB::transaction(function () use ($dish): Dish {
            $copy = $dish->replicate(['slug', 'created_at', 'updated_at', 'deleted_at']);
            $copy->name = $dish->name.'（複製）';
            $copy->status = PublishStatus::Draft;
            $copy->slug = Slug::make('', $dish->slug.'-copy', 'dishes');
            $copy->save();

            foreach ($dish->variants as $variant) {
                $copy->variants()->create($variant->only(['label', 'price', 'price_excluding_tax', 'service_type', 'is_default', 'sort_order']));
            }

            $copy->allergens()->sync($dish->allergens->pluck('id'));

            return $copy;
        });

        return redirect()->route('admin.dishes.edit', $copy)
            ->with('status', '料理を複製しました。下書きとして保存されています。');
    }

    /** 並び替え。上下ボタンで並べ替えた結果をまとめて保存する。 */
    public function sort(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:dishes,id'],
        ]);

        foreach ($request->input('order', []) as $position => $id) {
            Dish::whereKey($id)->update(['sort_order' => $position]);
        }

        return back()->with('status', '並び順を保存しました。');
    }

    /**
     * 入力値をモデルへ反映する。作成と更新で処理を分けない
     * （分けると片方だけ直して食い違うため）。
     */
    private function fill(Dish $dish, DishRequest $request): void
    {
        $data = $request->validated();

        $dish->fill([
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'name_kana' => $data['name_kana'] ?? null,
            'description' => $data['description'] ?? null,
            'body' => $data['body'] ?? null,
            'main_media_id' => $data['main_media_id'] ?? null,
            'is_recommended' => $request->boolean('is_recommended'),
            'is_new' => $request->boolean('is_new'),
            'is_limited' => $request->boolean('is_limited'),
            'is_sold_out' => $request->boolean('is_sold_out'),
            'has_detail_page' => $request->boolean('has_detail_page'),
            'available_from' => $data['available_from'] ?? null,
            'available_to' => $data['available_to'] ?? null,
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        // 公開済みの URL は変えない。変えると外部リンクが切れ、評価も積み直しになる。
        if (blank($dish->slug) || filled($data['slug'] ?? null)) {
            $dish->slug = Slug::forModel($dish, (string) ($data['slug'] ?? ''), $data['name_kana'] ?? $data['name']);
        }

        $dish->save();

        $this->syncVariants($dish, $data['variants']);

        $dish->allergens()->sync($data['allergens'] ?? []);

        // 追加画像。空欄を詰めて、選ばれた順に並べる。
        $images = collect($data['images'] ?? [])->filter()->unique()->values();
        $dish->images()->sync($images->mapWithKeys(
            fn ($id, $index) => [$id => ['sort_order' => $index]]
        )->all());
    }

    /**
     * 価格の保存。
     *
     * 行の増減があるので、いったん消して入れ直す。
     * 提供区分ごとに既定価格が 1 つだけになるよう、ここで必ず整える
     * （既定が無い／複数あると、一覧の代表価格が決まらない）。
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncVariants(Dish $dish, array $rows): void
    {
        $dish->variants()->delete();

        $defaults = [];

        foreach (array_values($rows) as $index => $row) {
            $service = $row['service_type'];
            $isDefault = (bool) ($row['is_default'] ?? false);

            if ($isDefault && isset($defaults[$service])) {
                $isDefault = false;
            }

            if ($isDefault) {
                $defaults[$service] = true;
            }

            $dish->variants()->create([
                'label' => $row['label'] ?? null,
                'price' => (int) $row['price'],
                'service_type' => $service,
                'is_default' => $isDefault,
                'sort_order' => $index,
            ]);
        }

        // どの区分にも既定が無い場合は、その区分の最初の行を既定にする。
        foreach ($dish->variants()->get()->groupBy('service_type') as $service => $variants) {
            if ($variants->where('is_default', true)->isEmpty()) {
                $variants->first()->update(['is_default' => true]);
            }
        }
    }
}
