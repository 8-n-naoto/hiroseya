@php
    use App\Enums\ServiceType;
    use App\Support\Kansuji;

    $isTakeout = $service === ServiceType::Takeout;
@endphp

<x-layouts.site>
    <x-site.page-head
        :reading="$isTakeout ? 'Takeout' : 'Menu'"
        :title="$isTakeout ? 'お持ち帰り' : 'お品書き'"
        :lead="$isTakeout
            ? 'お弁当・丼もの・揚げ物を店頭とお電話で承っております。ご来店の前にお電話をいただけますと、お待たせせずにお渡しできます。'
            : '打ちたてのうどん・そばと、味噌煮込み、定食物をご用意しております。価格はすべて税込です。'" />

    <div class="section">
        <div class="wrap">
            {{-- 店内とお持ち帰りは別ページ。タブに見えるが実体はリンク。 --}}
            @if ($hasTakeout)
                <nav class="menu-tabs" aria-label="お品書きの種類">
                    <a href="{{ route('menu.index') }}" class="menu-tabs__tab"
                        aria-selected="{{ $isTakeout ? 'false' : 'true' }}"
                        @if (! $isTakeout) aria-current="page" @endif>店内でお召し上がり</a>
                    <a href="{{ route('menu.takeout') }}" class="menu-tabs__tab"
                        aria-selected="{{ $isTakeout ? 'true' : 'false' }}"
                        @if ($isTakeout) aria-current="page" @endif>お持ち帰り</a>
                </nav>
            @endif

            @if ($categories->isEmpty())
                <p class="empty-state">お品書きは準備中です。しばらくお待ちください。</p>
            @else
                {{-- 品数が多いので、先に目次を出して目的の分類まで飛べるようにする。 --}}
                <ul class="menu-index">
                    @foreach ($categories as $category)
                        <li>
                            <a href="#cat-{{ $category->slug }}" data-num="{{ Kansuji::of($loop->iteration) }}">
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                @foreach ($categories as $category)
                    <section class="menu-group" id="cat-{{ $category->slug }}">
                        <div class="menu-group__head">
                            <span class="menu-group__num" aria-hidden="true">{{ Kansuji::of($loop->iteration) }}</span>
                            <h2 class="menu-group__name">{{ $category->name }}</h2>
                            @if ($category->description)
                                <span class="menu-group__note">{{ $category->description }}</span>
                            @endif
                        </div>

                        <ul class="menu-list">
                            @foreach ($category->dishes as $dish)
                                <x-site.dish-row :dish="$dish" :service="$service" />
                            @endforeach
                        </ul>
                    </section>
                @endforeach

                <div class="menu-note">
                    <p>表示価格はすべて税込です。仕入れの都合により、内容・価格が変わる場合がございます。</p>
                    @if ($isTakeout)
                        <p style="margin-top:8px;">お持ち帰りは店頭またはお電話にて承ります。お受け取りのお時間をお伝えください。</p>
                    @else
                        <p style="margin-top:8px;">お持ち帰りできるお品もございます。<a href="{{ route('menu.takeout') }}" style="border-bottom:1px solid;">お持ち帰りメニュー</a>をご覧ください。</p>
                    @endif
                    @php $allergens = \App\Models\Allergen::visible()->get(); @endphp
                    @if ($allergens->isNotEmpty() && $categories->flatMap->dishes->contains(fn ($d) => $d->allergens->isNotEmpty()))
                        <p style="margin-top:8px;">アレルギー表示は特定原材料8品目についてのものです。同一の厨房で調理しているため、微量の混入の可能性がございます。詳しくは店頭でおたずねください。</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-layouts.site>
