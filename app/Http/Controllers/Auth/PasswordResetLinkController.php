<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'パスワード再設定用のメールを送信しました。メールをご確認ください。');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $this->message($status)]);
    }

    private function message(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'そのメールアドレスは登録されていません。',
            Password::RESET_THROTTLED => 'しばらく時間をおいてから再度お試しください。',
            default => 'メールを送信できませんでした。時間をおいて再度お試しください。',
        };
    }
}
