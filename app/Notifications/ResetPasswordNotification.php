<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * パスワード再設定メール。多言語対応は不要（管理画面は店舗スタッフのみが使う）なので、
 * Laravel標準の英語文面を上書きして日本語で送る。
 */
class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('【広瀬屋 管理画面】パスワード再設定のご案内')
            ->greeting('パスワード再設定のご案内')
            ->line('このメールアドレス宛にパスワード再設定のリクエストがありました。')
            ->action('パスワードを再設定する', $url)
            ->line('このリンクの有効期限は60分です。')
            ->line('心当たりが無い場合は、このメールを破棄してください。パスワードは変更されません。');
    }

    /** @param  mixed  $notifiable */
    protected function resetUrl($notifiable): string
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
