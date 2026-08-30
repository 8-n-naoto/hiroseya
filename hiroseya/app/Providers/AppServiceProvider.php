<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 設定値は全ページが参照するため、リクエスト内で 1 インスタンスに固定する。
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        $this->registerGates();
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
}
