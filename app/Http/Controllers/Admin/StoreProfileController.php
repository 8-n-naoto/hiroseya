<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\StoreProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 店舗情報。
 *
 * このページの値が、フッター・アクセス・構造化データ（Restaurant）の
 * すべての出どころになる。店名・住所・電話（NAP）が各所で食い違うと、
 * 検索エンジンは同じ店だと判断できず、地図検索での評価が上がらない。
 * 二重管理を作らないため、他の場所に住所や電話を直接書かせない。
 */
class StoreProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.store.edit', ['store' => StoreProfile::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'name_kana' => ['nullable', 'string', 'max:191'],
            'catch_copy' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'postal_code' => ['nullable', 'string', 'max:10', 'regex:/\A[0-9]{3}-?[0-9]{4}\z/'],
            'prefecture' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:60'],
            'address_line' => ['nullable', 'string', 'max:191'],
            'building' => ['nullable', 'string', 'max:191'],
            'tel' => ['nullable', 'string', 'max:20', 'regex:/\A[0-9\-\+\(\)]+\z/'],
            'fax' => ['nullable', 'string', 'max:20', 'regex:/\A[0-9\-\+\(\)]+\z/'],
            'email' => ['nullable', 'email:filter', 'max:191'],
            'seats' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'parking' => ['nullable', 'string', 'max:191'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['string', 'max:40'],
            'access_car' => ['nullable', 'string', 'max:1000'],
            'access_train' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'postal_code.regex' => '郵便番号は 501-0622 の形式でご入力ください。',
            'tel.regex' => '電話番号は数字とハイフンでご入力ください。',
            'fax.regex' => 'FAX番号は数字とハイフンでご入力ください。',
        ], [
            'name' => '店名',
            'name_kana' => '店名（ふりがな）',
            'catch_copy' => 'キャッチコピー',
            'description' => '店舗紹介文',
            'postal_code' => '郵便番号',
            'prefecture' => '都道府県',
            'city' => '市区町村',
            'address_line' => '番地',
            'building' => '建物名',
            'tel' => '電話番号',
            'fax' => 'FAX番号',
            'email' => 'メールアドレス',
            'seats' => '席数',
            'parking' => '駐車場',
            'access_car' => 'お車でのアクセス',
            'access_train' => '電車でのアクセス',
            'latitude' => '緯度',
            'longitude' => '経度',
        ]);

        $data['payment_methods'] = array_values(array_filter($data['payment_methods'] ?? []));

        $profile = StoreProfile::current();
        $profile->fill($data)->save();

        ActivityLog::record('update', $profile, '店舗情報を更新しました。');

        return back()->with('status', '店舗情報を更新しました。');
    }
}
