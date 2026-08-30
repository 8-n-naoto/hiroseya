<?php

namespace Tests\Feature\Site;

use App\Models\StoreProfile;
use App\Support\Settings;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 準備中モードと SEO 出力の連動を確認する。
 *
 * ここが壊れると「仮の価格と仮の営業時間が検索結果に載る」という、
 * 後から取り返しのつかない事故になる。
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        StoreProfile::current()->update(['name' => '広瀬屋', 'tel' => '0585-22-1437']);
    }

    public function test_準備中はrobotsが全拒否になる(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /')
            ->assertDontSee('Sitemap:');
    }

    public function test_公開後のrobotsは管理画面だけを除外しサイトマップを示す(): void
    {
        app(Settings::class)->set('site', 'preparation_mode', false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin/')
            ->assertSee('Sitemap:');
    }

    public function test_準備中のサイトマップは空(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringNotContainsString('<loc>', $response->getContent());
    }

    public function test_公開後のサイトマップに主要ページが載る(): void
    {
        app(Settings::class)->set('site', 'preparation_mode', false);

        $content = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString(route('menu.index'), $content);
        $this->assertStringContainsString(route('menu.takeout'), $content);
        $this->assertStringContainsString(route('access'), $content);
    }

    public function test_公開後のトップに構造化データと正規URLが出る(): void
    {
        app(Settings::class)->set('site', 'preparation_mode', false);

        $this->get('/')
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Restaurant"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('index,follow', false);
    }

    public function test_準備中は個別ページもnoindexになる(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::Owner, 'is_active' => true,
        ]);

        $this->actingAs($user)->get('/menu')
            ->assertOk()
            ->assertSee('content="noindex,nofollow"', false);
    }
}
