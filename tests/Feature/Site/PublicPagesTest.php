<?php

namespace Tests\Feature\Site;

use App\Enums\PublishStatus;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Models\Dish;
use App\Models\DishCategory;
use App\Models\News;
use App\Models\StoreProfile;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 公開サイトの各ページが表示できることと、
 * 準備中モードが「未ログインにだけ効く」ことを確認する。
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        StoreProfile::current()->update([
            'name' => '広瀬屋',
            'prefecture' => '岐阜県',
            'city' => '揖斐郡揖斐川町',
            'address_line' => '脛永1784-13',
            'tel' => '0585-22-1437',
        ]);
    }

    private function openSite(): void
    {
        app(Settings::class)->set('site', 'preparation_mode', false);
    }

    public function test_準備中モードでは未ログインに準備中ページを出す(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('ただいまホームページを準備しております')
            ->assertSee('noindex', false);
    }

    public function test_準備中でもログイン中は通常のページが見える(): void
    {
        $user = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertDontSee('ただいまホームページを準備しております');
    }

    public function test_公開後は主要ページが表示できる(): void
    {
        $this->openSite();

        foreach (['/', '/menu', '/takeout', '/news', '/events', '/access', '/contact', '/privacy'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_お品書きに公開中の料理と価格が出る(): void
    {
        $this->openSite();

        $category = DishCategory::create([
            'name' => '煮込み', 'slug' => 'nikomi',
            'service_type' => ServiceType::DineIn, 'is_visible' => true,
        ]);

        $dish = Dish::create([
            'category_id' => $category->id,
            'name' => 'みそ煮込み',
            'slug' => 'miso-nikomi',
            'status' => PublishStatus::Published,
        ]);
        $dish->variants()->create([
            'price' => 910, 'service_type' => ServiceType::DineIn, 'is_default' => true,
        ]);

        $this->get('/menu')
            ->assertOk()
            ->assertSee('みそ煮込み')
            ->assertSee('910円');
    }

    public function test_持ち帰り価格を持つ料理は店内カテゴリでもお持ち帰りページに出る(): void
    {
        $this->openSite();

        $category = DishCategory::create([
            'name' => '丼もの', 'slug' => 'donburi',
            'service_type' => ServiceType::DineIn, 'is_visible' => true,
        ]);

        $dish = Dish::create([
            'category_id' => $category->id,
            'name' => 'カツ丼', 'slug' => 'katsu-don',
            'status' => PublishStatus::Published,
        ]);
        $dish->variants()->create(['price' => 880, 'service_type' => ServiceType::DineIn, 'is_default' => true]);
        $dish->variants()->create(['price' => 900, 'service_type' => ServiceType::Takeout, 'is_default' => true]);

        $this->get('/takeout')->assertOk()->assertSee('カツ丼')->assertSee('900円');
    }

    public function test_提供期間外の料理はお品書きに出ない(): void
    {
        $this->openSite();

        $dish = Dish::create([
            'name' => 'カキフライ定食', 'slug' => 'kaki-fry',
            'status' => PublishStatus::Published,
            'available_from' => now()->addMonth()->toDateString(),
        ]);
        $dish->variants()->create(['price' => 1380, 'service_type' => ServiceType::DineIn, 'is_default' => true]);

        $this->get('/menu')->assertOk()->assertDontSee('カキフライ定食');
    }

    public function test_下書きのお知らせは未ログインには404(): void
    {
        $this->openSite();

        News::create([
            'title' => '下書き', 'slug' => 'draft-news',
            'body' => '本文', 'status' => PublishStatus::Draft,
        ]);

        $this->get('/news/draft-news')->assertNotFound();
    }

    public function test_公開予約中のお知らせはログイン中なら確認できる(): void
    {
        $this->openSite();

        News::create([
            'title' => '予約公開', 'slug' => 'scheduled-news',
            'body' => '本文', 'status' => PublishStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $this->get('/news/scheduled-news')->assertNotFound();

        $user = User::factory()->create(['role' => UserRole::Editor, 'is_active' => true]);
        $this->actingAs($user)->get('/news/scheduled-news')->assertOk()->assertSee('予約公開');
    }
}
