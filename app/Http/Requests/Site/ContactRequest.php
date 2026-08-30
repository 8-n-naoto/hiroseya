<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * お問い合わせの入力チェック。
 *
 * 迷惑メール対策に外部サービス（reCAPTCHA 等）は使っていない。
 * 個人店のフォームに対する自動投稿は、次の 3 つでほぼ止まる:
 *
 *   1. 見えない入力欄（ハニーポット）— 自動投稿は必ず全欄を埋める
 *   2. 表示から送信までの時間 — 人間は 3 秒未満で書き終えない
 *   3. 日本語を 1 文字も含まないのに URL が入っている本文
 *
 * 加えてルート側で送信回数を制限している。
 * 外部サービスを入れないのは、店舗側にキー管理という運用が増え、
 * 失効した瞬間にフォームが動かなくなるため。
 */
class ContactRequest extends FormRequest
{
    /** 表示から送信までに最低限必要な秒数。 */
    private const MIN_ELAPSED_SECONDS = 3;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'name_kana' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:filter', 'max:191'],
            'email_confirmation' => ['nullable', 'string', 'same:email'],
            'tel' => ['nullable', 'string', 'max:20', 'regex:/\A[0-9０-９\-\+\(\)\s]+\z/u'],
            'subject' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:5', 'max:2000'],
            'agree' => ['accepted'],

            // ハニーポット。画面には出ないので、値が入っていれば自動投稿。
            'website' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'お名前',
            'name_kana' => 'ふりがな',
            'email' => 'メールアドレス',
            'email_confirmation' => 'メールアドレス（確認）',
            'tel' => '電話番号',
            'subject' => '件名',
            'body' => 'お問い合わせ内容',
            'agree' => 'プライバシーポリシーへの同意',
        ];
    }

    public function messages(): array
    {
        return [
            'tel.regex' => '電話番号は数字とハイフンでご入力ください。',
            'agree.accepted' => 'プライバシーポリシーへの同意が必要です。',
            'email_confirmation.same' => 'メールアドレスが一致しません。',
            'website.prohibited' => '送信できませんでした。お手数ですが、もう一度お試しください。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $openedAt = $this->session()->get('contact_form_opened_at');

            if ($openedAt && (time() - (int) $openedAt) < self::MIN_ELAPSED_SECONDS) {
                $validator->errors()->add('body', '送信が早すぎます。数秒おいてからもう一度お試しください。');
            }

            $body = (string) $this->input('body');
            $hasJapanese = (bool) preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $body);
            $hasUrl = (bool) preg_match('#https?://#i', $body);

            if (! $hasJapanese && $hasUrl) {
                $validator->errors()->add('body', 'お問い合わせ内容をご確認ください。');
            }
        });
    }
}
