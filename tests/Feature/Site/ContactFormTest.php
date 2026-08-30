<?php

namespace Tests\Feature\Site;

use App\Mail\ContactAutoReply;
use App\Mail\ContactReceived;
use App\Models\Contact;
use App\Models\StoreProfile;
use App\Support\Settings;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        StoreProfile::current()->update(['name' => '広瀬屋']);

        $settings = app(Settings::class);
        $settings->set('site', 'preparation_mode', false);
        $settings->set('mail', 'notify_to', 'store@example.com');
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => '山田太郎',
            'name_kana' => 'やまだたろう',
            'email' => 'taro@example.com',
            'tel' => '090-1234-5678',
            'subject' => '仕出しについて',
            'body' => '10名分の仕出しをお願いしたいのですが、可能でしょうか。',
            'agree' => '1',
            'website' => '',
        ], $overrides);
    }

    public function test_送信するとお問い合わせが保存されメールが送られる(): void
    {
        Mail::fake();

        $this->get('/contact')->assertOk();

        // 「送信が早すぎる」判定に引っかからないよう、表示時刻を戻す。
        $this->session(['contact_form_opened_at' => time() - 30]);

        $this->post('/contact', $this->payload())
            ->assertRedirect(route('contact.complete'));

        $this->assertDatabaseHas('contacts', [
            'email' => 'taro@example.com',
            'status' => 'pending',
        ]);

        Mail::assertSent(ContactReceived::class);
        Mail::assertSent(ContactAutoReply::class);
    }

    public function test_ハニーポットに入力があると受け付けない(): void
    {
        Mail::fake();
        $this->session(['contact_form_opened_at' => time() - 30]);

        $this->post('/contact', $this->payload(['website' => 'https://spam.example.com']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, Contact::count());
    }

    public function test_表示直後の送信は受け付けない(): void
    {
        $this->session(['contact_form_opened_at' => time()]);

        $this->post('/contact', $this->payload())->assertSessionHasErrors('body');

        $this->assertSame(0, Contact::count());
    }

    public function test_同意が無いと受け付けない(): void
    {
        $this->session(['contact_form_opened_at' => time() - 30]);

        $this->post('/contact', $this->payload(['agree' => null]))
            ->assertSessionHasErrors('agree');
    }

    public function test_日本語を含まないURLだけの本文は受け付けない(): void
    {
        $this->session(['contact_form_opened_at' => time() - 30]);

        $this->post('/contact', $this->payload([
            'body' => 'Check this out https://spam.example.com for cheap deals',
        ]))->assertSessionHasErrors('body');
    }

    public function test_メール送信に失敗してもお問い合わせは保存される(): void
    {
        // SMTP 設定ミスなどで送信が落ちても、内容が失われないことを確かめる。
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp error'));

        $this->session(['contact_form_opened_at' => time() - 30]);

        $this->post('/contact', $this->payload())->assertRedirect(route('contact.complete'));

        $this->assertDatabaseHas('contacts', ['email' => 'taro@example.com']);
    }

    public function test_完了ページは直接開けない(): void
    {
        $this->get('/contact/complete')->assertRedirect(route('contact.create'));
    }
}
