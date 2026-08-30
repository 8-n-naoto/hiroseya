@php $payments = old('payment_methods', $store->payment_methods ?? []); @endphp

<x-layouts.admin title="店舗情報">
    <x-admin.page-header title="店舗情報"
        description="ここで入力した内容が、フッター・アクセスページ・検索エンジンに渡す店舗情報のすべての出どころになります。店名・住所・電話番号は、食べログやGoogleマップなど他の掲載先と必ず同じ表記に揃えてください。表記が割れていると、検索エンジンが同じ店だと判断できず、地図検索での評価が上がりません。" />

    <form method="POST" action="{{ route('admin.store.update') }}" class="max-w-3xl space-y-6">
        @csrf @method('PUT')

        <x-admin.card title="店名">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.field label="店名" name="name" for="name" required>
                    <x-admin.input id="name" name="name" value="{{ old('name', $store->name) }}" required />
                </x-admin.field>
                <x-admin.field label="店名（ふりがな）" name="name_kana" for="name_kana">
                    <x-admin.input id="name_kana" name="name_kana" value="{{ old('name_kana', $store->name_kana) }}" />
                </x-admin.field>
            </div>
        </x-admin.card>

        <x-admin.card title="紹介文">
            <div class="space-y-5">
                <x-admin.field label="キャッチコピー" name="catch_copy" for="catch_copy"
                    help="トップページのメインビジュアルに重ねて出ます。20文字前後が読みやすい長さです。">
                    <x-admin.input id="catch_copy" name="catch_copy" value="{{ old('catch_copy', $store->catch_copy) }}" />
                </x-admin.field>

                <x-admin.field label="店舗紹介文" name="description" for="description"
                    help="トップページの「お店について」と、検索結果の説明文に使われます。">
                    <x-admin.textarea id="description" name="description" rows="6">{{ old('description', $store->description) }}</x-admin.textarea>
                </x-admin.field>
            </div>
        </x-admin.card>

        <x-admin.card title="所在地・連絡先">
            <div class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-admin.field label="郵便番号" name="postal_code" for="postal_code" help="例：501-0622">
                        <x-admin.input id="postal_code" name="postal_code" value="{{ old('postal_code', $store->postal_code) }}" />
                    </x-admin.field>
                    <x-admin.field label="都道府県" name="prefecture" for="prefecture">
                        <x-admin.input id="prefecture" name="prefecture" value="{{ old('prefecture', $store->prefecture) }}" />
                    </x-admin.field>
                    <x-admin.field label="市区町村" name="city" for="city">
                        <x-admin.input id="city" name="city" value="{{ old('city', $store->city) }}" />
                    </x-admin.field>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.field label="番地" name="address_line" for="address_line">
                        <x-admin.input id="address_line" name="address_line" value="{{ old('address_line', $store->address_line) }}" />
                    </x-admin.field>
                    <x-admin.field label="建物名など" name="building" for="building">
                        <x-admin.input id="building" name="building" value="{{ old('building', $store->building) }}" />
                    </x-admin.field>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <x-admin.field label="電話番号" name="tel" for="tel" help="例：0585-22-1437">
                        <x-admin.input id="tel" name="tel" value="{{ old('tel', $store->tel) }}" />
                    </x-admin.field>
                    <x-admin.field label="FAX番号" name="fax" for="fax">
                        <x-admin.input id="fax" name="fax" value="{{ old('fax', $store->fax) }}" />
                    </x-admin.field>
                    <x-admin.field label="メールアドレス" name="email" for="email"
                        help="サイトには表示されません。">
                        <x-admin.input type="email" id="email" name="email" value="{{ old('email', $store->email) }}" />
                    </x-admin.field>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="ご案内">
            <div class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.field label="席数" name="seats" for="seats">
                        <x-admin.input type="number" id="seats" name="seats" min="0"
                            value="{{ old('seats', $store->seats) }}" />
                    </x-admin.field>
                    <x-admin.field label="駐車場" name="parking" for="parking" help="例：あり（10台）">
                        <x-admin.input id="parking" name="parking" value="{{ old('parking', $store->parking) }}" />
                    </x-admin.field>
                </div>

                <x-admin.field label="お支払い方法" name="payment_methods">
                    <div class="flex flex-wrap gap-4">
                        @foreach (['現金', 'クレジットカード', '電子マネー', 'QRコード決済', 'PayPay'] as $method)
                            <label class="flex items-center gap-2 text-sm text-stone-700">
                                <input type="checkbox" name="payment_methods[]" value="{{ $method }}"
                                    @checked(in_array($method, (array) $payments, true))
                                    class="h-4 w-4 rounded border-stone-300">
                                {{ $method }}
                            </label>
                        @endforeach
                    </div>
                </x-admin.field>

                <x-admin.field label="電車でのアクセス" name="access_train" for="access_train"
                    help="例：養老鉄道 揖斐駅から徒歩8分">
                    <x-admin.textarea id="access_train" name="access_train" rows="2">{{ old('access_train', $store->access_train) }}</x-admin.textarea>
                </x-admin.field>

                <x-admin.field label="お車でのアクセス" name="access_car" for="access_car">
                    <x-admin.textarea id="access_car" name="access_car" rows="2">{{ old('access_car', $store->access_car) }}</x-admin.textarea>
                </x-admin.field>
            </div>
        </x-admin.card>

        <x-admin.card title="地図の座標"
            description="Googleマップで店舗を長押しすると表示される数字です。入力しておくと、検索エンジンに正確な位置を伝えられます（未入力でも地図は表示されます）。">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.field label="緯度" name="latitude" for="latitude" help="例：35.4885000">
                    <x-admin.input id="latitude" name="latitude" value="{{ old('latitude', $store->latitude) }}" />
                </x-admin.field>
                <x-admin.field label="経度" name="longitude" for="longitude" help="例：136.5670000">
                    <x-admin.input id="longitude" name="longitude" value="{{ old('longitude', $store->longitude) }}" />
                </x-admin.field>
            </div>
        </x-admin.card>

        <x-admin.button>店舗情報を保存する</x-admin.button>
    </form>
</x-layouts.admin>
