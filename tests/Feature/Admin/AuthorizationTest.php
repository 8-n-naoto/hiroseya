<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 権限の境目を確認する。
 * 編集者が設定を触れてしまうと、店舗情報やメール設定が意図せず変わりうる。
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public static function settingRoutes(): array
    {
        return [
            ['admin.store.edit'],
            ['admin.business-hours.index'],
            ['admin.seo.index'],
            ['admin.settings.edit'],
            ['admin.social-links.index'],
            ['admin.users.index'],
            ['admin.activity.index'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('settingRoutes')]
    public function test_編集者は設定画面に入れない(string $route): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor, 'is_active' => true]);

        $this->actingAs($editor)->get(route($route))->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('settingRoutes')]
    public function test_管理者は設定画面に入れる(string $route): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);

        $this->actingAs($owner)->get(route($route))->assertOk();
    }

    public function test_編集者もコンテンツと問い合わせは扱える(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor, 'is_active' => true]);

        foreach (['admin.dishes.index', 'admin.news.index', 'admin.events.index',
                  'admin.media.index', 'admin.contacts.index', 'admin.home-sections.index'] as $route) {
            $this->actingAs($editor)->get(route($route))->assertOk();
        }
    }

    public function test_利用停止のユーザーは即ログアウトされる(): void
    {
        $user = User::factory()->create(['role' => UserRole::Owner, 'is_active' => false]);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_未ログインは管理画面に入れない(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_最後の管理者の権限は落とせない(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);
        $other = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);

        // other を編集者に落とす（owner が残るので成功する）
        $this->actingAs($owner)->put(route('admin.users.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
            'role' => UserRole::Editor->value,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertSame(UserRole::Editor, $other->refresh()->role);

        // 自分自身の権限は変えられない（フォームからも hidden で固定している）
        $this->actingAs($owner)->put(route('admin.users.update', $owner), [
            'name' => $owner->name,
            'email' => $owner->email,
            'role' => UserRole::Editor->value,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertSame(UserRole::Owner, $owner->refresh()->role);
    }
}
