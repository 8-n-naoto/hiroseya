<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 準備中モード（settings.site.preparation_mode）。
 *
 * ONの間、未ログインの訪問者には準備中ページだけを見せる。
 * ログイン中の管理者・編集者には通常どおり表示し、公開前の中身を確認できるようにする。
 *
 * noindex は個々のページの <meta> でも制御するが（Phase 2 で実装）、
 * 準備中ページ自体にも念のため noindex を付けている。
 */
class CheckPreparationMode
{
    public function __construct(private readonly Settings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->settings->inPreparation() && auth()->guest()) {
            return response()->view('preparing', [
                'message' => $this->settings->string(
                    'site.preparation_message',
                    'ただいまホームページを準備しております。しばらくお待ちください。',
                ),
                'siteName' => $this->settings->string('site.site_name', '広瀬屋'),
            ], 200);
        }

        return $next($request);
    }
}
