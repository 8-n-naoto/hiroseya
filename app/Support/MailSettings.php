<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * メール送信設定。
 *
 * SMTP の設定は .env ではなく settings テーブルに持たせている。
 * 店舗側がサーバー移転やメールアドレス変更のたびに .env を触れるとは限らず、
 * その都度こちらに依頼が来る運用は続かないため。
 *
 * 設定が未入力のうちは .env の値（既定では log ドライバ）のまま動く。
 * つまり「設定していないのに送信したつもりになる」ことは起きるが、
 * 逆に「設定漏れでフォーム自体が 500 になる」ことは起きない。
 * 問い合わせは先に DB へ保存しているので、送信に失敗しても内容は失われない。
 */
class MailSettings
{
    private bool $applied = false;

    public function __construct(private readonly Settings $settings) {}

    /** SMTP が管理画面から設定されているか。 */
    public function configured(): bool
    {
        return $this->settings->string('mail.host') !== ''
            && $this->settings->string('mail.from_address') !== '';
    }

    /**
     * DB の設定を実行時の mail 設定へ流し込む。
     * 送信の直前に呼ぶ。1 リクエストで何度呼んでも 1 回しか適用しない。
     */
    public function apply(): void
    {
        if ($this->applied || ! $this->configured()) {
            $this->applied = true;

            return;
        }

        $encryption = $this->settings->string('mail.encryption', 'tls');

        Config::set('mail.mailers.smtp', array_merge(
            Config::get('mail.mailers.smtp', []),
            [
                'transport' => 'smtp',
                'host' => $this->settings->string('mail.host'),
                'port' => $this->settings->int('mail.port', 587),
                'encryption' => $encryption === 'none' ? null : $encryption,
                'username' => $this->settings->string('mail.username') ?: null,
                'password' => $this->settings->string('mail.password') ?: null,
                'timeout' => 20,
            ],
        ));

        Config::set('mail.default', 'smtp');
        Config::set('mail.from', [
            'address' => $this->settings->string('mail.from_address'),
            'name' => $this->settings->string('mail.from_name', '広瀬屋'),
        ]);

        // 設定を差し替えたので、生成済みのメーラーを捨てて作り直させる。
        Mail::purge('smtp');
        Mail::forgetMailers();

        $this->applied = true;
    }

    public function fromAddress(): string
    {
        return $this->settings->string('mail.from_address')
            ?: (string) config('mail.from.address', 'no-reply@example.com');
    }

    public function fromName(): string
    {
        return $this->settings->string('mail.from_name', '広瀬屋');
    }

    public function replyTo(): ?string
    {
        $value = $this->settings->string('mail.reply_to');

        return $value !== '' ? $value : null;
    }

    /**
     * 店舗側の通知先。カンマ・全角カンマ・改行のいずれで区切られていても拾う。
     *
     * @return array<int, string>
     */
    public function notifyRecipients(): array
    {
        $raw = $this->settings->string('mail.notify_to');

        if ($raw === '') {
            return [];
        }

        return collect(preg_split('/[,、\r\n\s]+/u', $raw) ?: [])
            ->map(fn (string $value) => trim($value))
            ->filter(fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }
}
