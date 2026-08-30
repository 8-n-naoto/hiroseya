<?php

namespace App\Services;

use App\Mail\ContactAutoReply;
use App\Mail\ContactReceived;
use App\Mail\ContactReplied;
use App\Models\Contact;
use App\Models\ContactReply;
use App\Models\StoreProfile;
use App\Support\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * お問い合わせの受付と返信。
 *
 * 方針は「先に保存、あとで送信」。
 * メールの送信は SMTP の設定ミス・サーバー側の一時的な障害で普通に失敗する。
 * 先に DB へ保存しておけば、送信が失敗してもお問い合わせ自体は失われず、
 * 管理画面から内容を確認して対応できる。
 *
 * キューには載せていない。共用サーバーでは cron を設定しないとキューが
 * 動かないため、「設定漏れでメールが永久に送られない」ほうが危ない。
 */
class ContactService
{
    public function __construct(private readonly MailSettings $mail) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function receive(array $data, Request $request): Contact
    {
        $contact = Contact::create([
            'name' => $data['name'],
            'name_kana' => $data['name_kana'] ?? null,
            'email' => $data['email'],
            'tel' => $data['tel'] ?? null,
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        $this->notifyStore($contact);
        $this->autoReply($contact);

        return $contact;
    }

    /** 店舗への通知。宛先が未設定なら送らない（設定漏れは管理画面で警告する）。 */
    private function notifyStore(Contact $contact): void
    {
        $recipients = $this->mail->notifyRecipients();

        if ($recipients === []) {
            Log::warning('お問い合わせの通知先が未設定のため、店舗への通知を送信しませんでした。', [
                'contact_id' => $contact->id,
            ]);

            return;
        }

        $this->send(fn () => Mail::to($recipients)->send(new ContactReceived($contact)), $contact->id, '店舗通知');
    }

    private function autoReply(Contact $contact): void
    {
        $this->send(
            fn () => Mail::to($contact->email)->send(new ContactAutoReply($contact, StoreProfile::current())),
            $contact->id,
            '自動返信',
        );
    }

    /** 管理画面からの返信。送信結果を contact_replies に必ず残す。 */
    public function reply(Contact $contact, string $body, ?int $userId): ContactReply
    {
        $reply = ContactReply::create([
            'contact_id' => $contact->id,
            'user_id' => $userId,
            'body' => $body,
            'to_email' => $contact->email,
        ]);

        $this->mail->apply();

        try {
            Mail::to($contact->email)->send(new ContactReplied($reply, StoreProfile::current()));
            $reply->update(['sent_at' => now(), 'error_message' => null]);
        } catch (\Throwable $e) {
            // 失敗した本文を消さない。書き直しを強いると、送れたかどうかも分からなくなる。
            $reply->update(['error_message' => $e->getMessage()]);
            Log::error('お問い合わせへの返信メールの送信に失敗しました。', [
                'contact_id' => $contact->id,
                'reply_id' => $reply->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $reply->refresh();
    }

    private function send(callable $callback, int $contactId, string $label): void
    {
        $this->mail->apply();

        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error("お問い合わせの{$label}メールの送信に失敗しました。", [
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
