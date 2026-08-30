<?php

namespace App\Mail;

use App\Models\ContactReply;
use App\Models\StoreProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 管理画面から送るお客様への返信。
 *
 * 本文は店舗の方が書いたものをそのまま送る。定型の挨拶を勝手に足さないのは、
 * 「送ったはずの文面と実際に届いた文面が違う」状態を作らないため。
 */
class ContactReplied extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ContactReply $reply,
        public readonly StoreProfile $store,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->reply->contact->subject;

        return new Envelope(
            subject: 'Re: '.($subject ?: 'お問い合わせの件').'（'.$this->store->name.'）',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.contact-replied', with: [
            'reply' => $this->reply,
            'contact' => $this->reply->contact,
            'store' => $this->store,
        ]);
    }
}
