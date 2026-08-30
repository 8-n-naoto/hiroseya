<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublishStatus;
use App\Enums\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dishId = $this->route('dish')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'name_kana' => ['nullable', 'string', 'max:150'],
            'slug' => [
                'nullable', 'string', 'max:150', 'alpha_dash',
                Rule::unique('dishes', 'slug')->ignore($dishId)->whereNull('deleted_at'),
            ],
            'category_id' => ['nullable', 'integer', 'exists:dish_categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:20000'],
            'main_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['nullable', 'integer', 'exists:media,id'],
            'allergens' => ['nullable', 'array'],
            'allergens.*' => ['integer', 'exists:allergens,id'],
            'is_recommended' => ['boolean'],
            'is_new' => ['boolean'],
            'is_limited' => ['boolean'],
            'is_sold_out' => ['boolean'],
            'has_detail_page' => ['boolean'],
            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],
            'status' => ['required', Rule::in(array_column(PublishStatus::cases(), 'value'))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],

            'variants' => ['required', 'array', 'min:1', 'max:12'],
            'variants.*.label' => ['nullable', 'string', 'max:60'],
            'variants.*.price' => ['required', 'integer', 'min:0', 'max:999999'],
            'variants.*.service_type' => ['required', Rule::in(array_column(ServiceType::cases(), 'value'))],
            'variants.*.is_default' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '料理名',
            'name_kana' => 'ふりがな',
            'slug' => 'URL（英数字）',
            'category_id' => '分類',
            'description' => '説明',
            'body' => '詳細本文',
            'main_media_id' => 'メイン画像',
            'available_from' => '提供開始日',
            'available_to' => '提供終了日',
            'status' => '公開状態',
            'variants' => '価格',
        ];
    }

    public function messages(): array
    {
        return [
            'variants.required' => '価格を 1 つ以上ご入力ください。',
            'variants.*.price.required' => '価格をご入力ください。',
            'slug.alpha_dash' => 'URL は半角英数字とハイフンでご入力ください。',
            'available_to.after_or_equal' => '提供終了日は提供開始日以降の日付にしてください。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // 詳細ページを作るなら、中身が空のページを公開させない。
            if ($this->boolean('has_detail_page')
                && blank($this->input('description'))
                && blank($this->input('body'))) {
                $validator->errors()->add(
                    'body',
                    '詳細ページを作る場合は、説明または詳細本文をご入力ください。中身の無いページは検索エンジンの評価を下げます。',
                );
            }
        });
    }
}
