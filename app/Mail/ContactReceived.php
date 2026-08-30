<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 店舗への「お問い合わせが届きました」通知。
 *
 * 本文はテキストのみ。装飾より確実に届くことを優先している
 * （HTMLメールは迷惑メール判定を受けやすく、店舗のメール環境も選ばない）。
 *
 * 返信先をお客様のアドレスにしてあるので、受信メールでそのまま
 * 「返信」を押せば本人に届く。管理画面を開かない日でも対応できる。
 */
class ContactReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Contact $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【お問い合わせ】'.($this->contact->subject ?: '件名なし').'（'.$this->contact->name.' 様）',
            replyTo: [$this->contact->email],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.contact-received', with: [
            'contact' => $this->contact,
            'adminUrl' => route('admin.contacts.show', $this->contact),
        ]);
    }
}
