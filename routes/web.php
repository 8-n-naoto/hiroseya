<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 公開サイト
|--------------------------------------------------------------------------
| preparation ミドルウェアが、準備中モードON かつ 未ログインの間は
| 準備中ページに差し替える。公開サイト本体は Phase 4 で実装する。
*/
Route::middleware('preparation')->group(function () {
    Route::get('/', fn () => view('welcome'))->name('home');
});

/*
|--------------------------------------------------------------------------
| クローラー向け（準備中モードと連動）
|--------------------------------------------------------------------------
| public/robots.txt を残したままだと Apache が Laravel より先に静的ファイルを
| 返してしまい、このルートに一切届かない。public/robots.txt は削除済み。
| もし別途 public/sitemap.xml が置かれていたら、それも削除すること。
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
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
    Route::patch('media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
});
