<?php

namespace App\Providers;

use App\Models\User;
use App\Services\BusinessHourService;
use App\Services\MenuService;
use App\Support\MailSettings;
use App\Support\Seo;
use App\Support\Settings;
use App\Support\SiteContext;
use App\Support\StructuredData;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         * どれも 1 リクエスト内で何度も参照されるうえ、内部で結果を覚えている。
         * 都度 new すると同じクエリを何度も投げることになるため singleton にする。
         */
        $this->app->singleton(Settings::class);
        $this->app->singleton(BusinessHourService::class);
        $this->app->singleton(SiteContext::class);
        $this->app->singleton(StructuredData::class);
        $this->app->singleton(MailSettings::class);
        $this->app->singleton(MenuService::class);

        // Seo は「今表示しているページの状態」そのものなので、必ず 1 個に固定する。
        $this->app->singleton(Seo::class);
    }

    public function boot(): void
    {
        $this->forceHttpsWhenConfigured();
        $this->registerGates();
        $this->registerRateLimiters();
    }

    /**
     * 本番の URL が https のときはリンクとフォームの action を https で生成する。
     *
     * 共用サーバーはリバースプロキシの内側で動くことがあり、
     * Laravel 側からは http に見えてしまう。canonical・sitemap・OG の URL が
     * http で出ると、正規化が二重になって検索エンジンの評価が割れる。
     */
    private function forceHttpsWhenConfigured(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * 権限は owner / editor の 2 段階。
     *
     * 設定系（SEO / SNS / 予約設定 / メール / サイト基本 / ユーザー管理）は owner のみ。
     * コンテンツと問い合わせ・予約の対応は両方が行える。
     */
    private function registerGates(): void
    {
        Gate::define('manage-settings', fn (User $user) => $user->canManageSettings());
        Gate::define('manage-users', fn (User $user) => $user->isOwner());
        Gate::define('manage-content', fn (User $user) => $user->is_active);
        Gate::define('handle-inquiries', fn (User $user) => $user->is_active);
    }

    /**
     * お問い合わせの送信回数制限。
     *
     * 外部の bot 対策サービスを使わない代わりに、同一IPからの連投を止める。
     * 家族で同じ回線から送るような正常な使い方を邪魔しない程度に緩くしてある。
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);
    }
}
