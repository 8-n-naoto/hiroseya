<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BusinessHourController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DishCategoryController;
use App\Http\Controllers\Admin\DishController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\SeoMetaController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SocialLinkController;
use App\Http\Controllers\Admin\StoreProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Site\AccessController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\EventController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\MenuController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 公開サイト
|--------------------------------------------------------------------------
| preparation ミドルウェアが、準備中モードON かつ 未ログインの間は
| 準備中ページに差し替える。ログイン中の管理者・編集者は通常どおり閲覧でき、
| 公開前の確認ができる。
|
| 店内とお持ち帰りは別 URL にしている。タブの中身は検索結果に出せないため、
| 「揖斐川町 うどん 持ち帰り」のような検索で拾われるようにするには
| ページを分ける必要がある。
*/
Route::middleware('preparation')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('takeout', [MenuController::class, 'takeout'])->name('menu.takeout');
    Route::get('menu/{slug}', [MenuController::class, 'show'])->name('menu.show');

    Route::get('news', [NewsController::class, 'index'])->name('news.index');
    Route::get('news/{slug}', [NewsController::class, 'show'])->name('news.show');

    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/{slug}', [EventController::class, 'show'])->name('events.show');

    Route::get('access', AccessController::class)->name('access');
    Route::get('privacy', [PageController::class, 'privacy'])->name('privacy');

    Route::get('contact', [ContactController::class, 'create'])->name('contact.create');
    Route::post('contact', [ContactController::class, 'store'])
        ->middleware('throttle:contact')
        ->name('contact.store');
    Route::get('contact/complete', [ContactController::class, 'complete'])->name('contact.complete');
});

/*
|--------------------------------------------------------------------------
| クローラー向け（準備中モードと連動）
|--------------------------------------------------------------------------
| public/robots.txt・public/sitemap.xml が残っていても、
| public/.htaccess でこのルートへ回している。
*/
Route::get('robots.txt', RobotsController::class)->name('robots');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

/*
|--------------------------------------------------------------------------
| 認証（管理画面へのログイン）
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| 管理画面
|--------------------------------------------------------------------------
| auth: 未ログインは login へ。 active: is_active=false は即ログアウト。
|
| 権限は 2 段階。コンテンツと問い合わせ対応は編集者も行える（manage-content）。
| 設定系（店舗情報・営業時間・SEO・メール・SNS・ユーザー）は管理者のみ
| （manage-settings）。ミドルウェアはルート側に置き、コントローラーからは
| 権限の判断を外している（Laravel 11 以降、コントローラーの
| $this->middleware() は使えないため）。
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    /*
    | コンテンツ（管理者・編集者）
    */
    Route::middleware('can:manage-content')->group(function () {
        // 画像ライブラリ
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        // 画像選択の重ね窓が読む一覧（JSON）。{media} より前に置かないと吸われる。
        Route::get('media/picker', [MediaController::class, 'picker'])->name('media.picker');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::patch('media/{media}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

        // 料理
        Route::post('dishes/sort', [DishController::class, 'sort'])->name('dishes.sort');
        Route::post('dishes/{dish}/duplicate', [DishController::class, 'duplicate'])->name('dishes.duplicate');
        Route::resource('dishes', DishController::class)->except('show');

        // 料理カテゴリ
        Route::post('dish-categories/sort', [DishCategoryController::class, 'sort'])->name('dish-categories.sort');
        Route::resource('dish-categories', DishCategoryController::class)->except('show');

        // お知らせ
        Route::resource('news', AdminNewsController::class)->except('show')->parameters(['news' => 'news']);

        // イベント
        Route::resource('events', AdminEventController::class)->except('show');

        // トップページ
        Route::get('home', [HomeSectionController::class, 'index'])->name('home-sections.index');
        Route::put('home/order', [HomeSectionController::class, 'reorder'])->name('home-sections.reorder');
        Route::put('home/recommended', [HomeSectionController::class, 'updateRecommended'])->name('home-sections.recommended');
        Route::get('home/{homeSection}', [HomeSectionController::class, 'edit'])->name('home-sections.edit');
        Route::put('home/{homeSection}', [HomeSectionController::class, 'update'])->name('home-sections.update');
    });

    /*
    | 問い合わせ対応（管理者・編集者）
    */
    Route::middleware('can:handle-inquiries')->group(function () {
        Route::get('contacts', [AdminContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/{contact}', [AdminContactController::class, 'show'])->name('contacts.show');
        Route::patch('contacts/{contact}', [AdminContactController::class, 'update'])->name('contacts.update');
        Route::delete('contacts/{contact}', [AdminContactController::class, 'destroy'])->name('contacts.destroy');
        Route::post('contacts/{contact}/replies', [AdminContactController::class, 'reply'])->name('contacts.reply');
    });

    /*
    | 設定（管理者のみ）
    */
    Route::middleware('can:manage-settings')->group(function () {
        Route::get('store', [StoreProfileController::class, 'edit'])->name('store.edit');
        Route::put('store', [StoreProfileController::class, 'update'])->name('store.update');

        Route::get('business-hours', [BusinessHourController::class, 'index'])->name('business-hours.index');
        Route::put('business-hours', [BusinessHourController::class, 'update'])->name('business-hours.update');
        Route::post('special-days', [BusinessHourController::class, 'storeSpecialDay'])->name('special-days.store');
        Route::delete('special-days/{specialDay}', [BusinessHourController::class, 'destroySpecialDay'])->name('special-days.destroy');

        Route::get('seo', [SeoMetaController::class, 'index'])->name('seo.index');
        Route::put('seo', [SeoMetaController::class, 'update'])->name('seo.update');

        Route::get('settings/{group?}', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings/{group}', [SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/mail/test', [SettingController::class, 'sendTestMail'])->name('settings.mail.test');

        Route::get('social-links', [SocialLinkController::class, 'index'])->name('social-links.index');
        Route::put('social-links', [SocialLinkController::class, 'update'])->name('social-links.update');

        Route::resource('users', UserController::class)->except('show');

        Route::get('activity', ActivityLogController::class)->name('activity.index');
    });
});
