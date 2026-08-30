<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublishStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * お知らせとイベントは、公開状態・本文・画像・SEO の扱いが同じなので
 * 入力チェックを共通にしている。日付の意味だけが違う。
 */
class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isEvent = $this->routeIs('admin.events.*');
        $model = $this->route('event') ?? $this->route('news');

        $rules = [
            'title' => ['required', 'string', 'max:191'],
            'slug' => [
                'nullable', 'string', 'max:191', 'alpha_dash',
                Rule::unique($isEvent ? 'events' : 'news', 'slug')
                    ->ignore($model?->id)
                    ->whereNull('deleted_at'),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:40000'],
            'main_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'status' => ['required', Rule::in(array_column(PublishStatus::cases(), 'value'))],
            'seo_title' => ['nullable', 'string', 'max:191'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];

        if ($isEvent) {
            $rules['starts_on'] = ['nullable', 'date'];
            $rules['ends_on'] = ['nullable', 'date', 'after_or_equal:starts_on'];
            $rules['sort_order'] = ['nullable', 'integer', 'min:0', 'max:9999'];
        } else {
            // 公開にするなら公開日時は必須。未設定だと一覧に出てこず、
            // 「公開したのに載らない」という問い合わせになる。
            $rules['published_at'] = [
                Rule::requiredIf(fn () => $this->input('status') === PublishStatus::Published->value),
                'nullable', 'date',
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'slug' => 'URL（英数字）',
            'excerpt' => '概要',
            'body' => '本文',
            'main_media_id' => '画像',
            'status' => '公開状態',
            'published_at' => '公開日時',
            'starts_on' => '開催開始日',
            'ends_on' => '開催終了日',
            'seo_title' => 'SEOタイトル',
            'seo_description' => 'SEOディスクリプション',
        ];
    }

    public function messages(): array
    {
        return [
            'published_at.required' => '公開する場合は、公開日時をご入力ください。',
            'ends_on.after_or_equal' => '開催終了日は開始日以降の日付にしてください。',
            'slug.alpha_dash' => 'URL は半角英数字とハイフンでご入力ください。',
        ];
    }
}
