<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\StoreProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * お客様への自動返信。
 *
 * 「送れたのかどうか分からない」が問い合わせフォームで最も多い不安なので、
 * 受け付けた内容をそのまま控えとして返す。
 * 自動返信であること、営業時間によっては返信が翌日以降になることを明記する。
 */
class ContactAutoReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Contact $contact,
        public readonly StoreProfile $store,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【'.$this->store->name.'】お問い合わせを承りました',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.contact-auto-reply', with: [
            'contact' => $this->contact,
            'store' => $this->store,
        ]);
    }
}
