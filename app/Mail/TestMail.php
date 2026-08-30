<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * メール設定のテスト送信。
 *
 * 「設定を保存した」ことと「実際に届く」ことは別なので、
 * 管理画面から必ず 1 通送って確認できるようにしている。
 */
class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $siteName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '【'.$this->siteName.'】メール設定のテスト送信');
    }

    public function content(): Content
    {
        return new Content(text: 'emails.test', with: [
            'siteName' => $this->siteName,
            'sentAt' => now()->format('Y年n月j日 H:i'),
        ]);
    }
}
