<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ログイン済みでも is_active が false になったユーザーは即座に締め出す。
 *
 * 退職したアルバイトのアカウントを無効化しただけで終わらせず、
 * すでにログイン中のセッションも次のリクエストで強制ログアウトさせる。
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'このアカウントは現在無効になっています。管理者にお問い合わせください。',
            ]);
        }

        return $next($request);
    }
}
