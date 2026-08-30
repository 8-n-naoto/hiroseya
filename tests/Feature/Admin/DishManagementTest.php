<?php

namespace Tests\Feature\Admin;

use App\Enums\PublishStatus;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Models\Dish;
use App\Models\DishCategory;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DishManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->editor = User::factory()->create(['role' => UserRole::Editor, 'is_active' => true]);
    }

    public function test_複数の価格を持つ料理を登録できる(): void
    {
        $category = DishCategory::create([
            'name' => '丼もの', 'slug' => 'donburi',
            'service_type' => ServiceType::DineIn, 'is_visible' => true,
        ]);

        $this->actingAs($this->editor)->post(route('admin.dishes.store'), [
            'name' => 'カツ丼',
            'name_kana' => 'かつどん',
            'category_id' => $category->id,
            'status' => PublishStatus::Published->value,
            'variants' => [
                ['label' => '', 'price' => 880, 'service_type' => 'dine_in', 'is_default' => 1],
                ['label' => 'お持ち帰り', 'price' => 900, 'service_type' => 'takeout', 'is_default' => 1],
            ],
        ])->assertRedirect();

        $dish = Dish::firstWhere('name', 'カツ丼');

        $this->assertNotNull($dish);
        $this->assertSame(2, $dish->variants()->count());
        $this->assertSame(880, $dish->defaultVariant(ServiceType::DineIn)->price);
        $this->assertSame(900, $dish->defaultVariant(ServiceType::Takeout)->price);
    }

    public function test_URLが未入力なら自動で作られる(): void
    {
        $this->actingAs($this->editor)->post(route('admin.dishes.store'), [
            'name' => 'みそ煮込み',
            'name_kana' => 'misonikomi',
            'status' => PublishStatus::Draft->value,
            'variants' => [['price' => 910, 'service_type' => 'dine_in', 'is_default' => 1]],
        ])->assertRedirect();

        $this->assertSame('misonikomi', Dish::firstWhere('name', 'みそ煮込み')->slug);
    }

    public function test_代表価格が未指定でも提供区分ごとに1つ決まる(): void
    {
        $this->actingAs($this->editor)->post(route('admin.dishes.store'), [
            'name' => 'ライス',
            'name_kana' => 'raisu',
            'status' => PublishStatus::Draft->value,
            'variants' => [
                ['label' => '小', 'price' => 150, 'service_type' => 'takeout', 'is_default' => 0],
                ['label' => '中', 'price' => 200, 'service_type' => 'takeout', 'is_default' => 0],
            ],
        ])->assertRedirect();

        $dish = Dish::firstWhere('name', 'ライス');

        $this->assertSame(1, $dish->variants()->where('is_default', true)->count());
        $this->assertSame('小', $dish->defaultVariant(ServiceType::Takeout)->label);
    }

    public function test_価格が無いと保存できない(): void
    {
        $this->actingAs($this->editor)
            ->post(route('admin.dishes.store'), [
                'name' => '価格なし',
                'status' => PublishStatus::Draft->value,
            ])
            ->assertSessionHasErrors('variants');
    }

    public function test_詳細ページを作るのに中身が無いと保存できない(): void
    {
        $this->actingAs($this->editor)
            ->post(route('admin.dishes.store'), [
                'name' => '中身なし',
                'name_kana' => 'nakaminashi',
                'status' => PublishStatus::Draft->value,
                'has_detail_page' => 1,
                'variants' => [['price' => 500, 'service_type' => 'dine_in', 'is_default' => 1]],
            ])
            ->assertSessionHasErrors('body');
    }

    public function test_複製は下書きとして作られる(): void
    {
        $dish = Dish::create([
            'name' => 'みそかつ定食', 'slug' => 'misokatsu-teishoku',
            'status' => PublishStatus::Published,
        ]);
        $dish->variants()->create(['price' => 1220, 'service_type' => ServiceType::DineIn, 'is_default' => true]);

        $this->actingAs($this->editor)
            ->post(route('admin.dishes.duplicate', $dish))
            ->assertRedirect();

        $copy = Dish::firstWhere('name', 'みそかつ定食（複製）');

        $this->assertNotNull($copy);
        $this->assertSame(PublishStatus::Draft, $copy->status);
        $this->assertNotSame($dish->slug, $copy->slug);
        $this->assertSame(1, $copy->variants()->count());
    }
}
