@props(['dish', 'service'])
{{--
    お品書きの 1 行。

    品名と価格を点線でつなぐのは、紙のお品書きの体裁をそのまま持ち込むため。
    価格が複数ある品（セット/単品、二枚/三枚、小/中/大）は、代表価格を上に、
    残りをその下にぶら下げる。実データに必ず複数価格の品があるので、
    「1 品 1 価格」を前提にした表示にはできない。
--}}
@php
    $variants = $dish->variantsFor($service);
    $main = $variants->firstWhere('is_default', true) ?? $variants->first();
    $rest = $variants->reject(fn ($v) => $main && $v->is($main))->values();
    $link = $dish->has_detail_page ? route('menu.show', $dish->slug) : null;
@endphp

<li class="menu-row">
    @if ($dish->mainImage)
        <x-site.picture :media="$dish->mainImage" :alt="$dish->mainImage->alt ?: $dish->name"
            class="menu-row__thumb" sizes="104px" :placeholder="false" />
    @endif

    <div class="menu-row__body">
        <div class="menu-row__line">
            <span class="menu-row__name">
                {{-- 詳細ページを持つ品だけがリンクになる。全体で a { color: inherit;
                     text-decoration: none } を効かせているため、罫と「詳しく」の
                     二つで印を付けないと、リンクだと気づかれないまま素通りされる。 --}}
                @if ($link)
                    <a href="{{ $link }}" class="menu-row__link">{{ $dish->name }}</a>
                @else
                    {{ $dish->name }}
                @endif
                @if ($main && $main->label)
                    <span style="font-size:13px;color:var(--ink-faint);">（{{ $main->label }}）</span>
                @endif
                @if ($link)
                    {{-- 品名と同じ行き先。読み上げとキーボードでは重複するだけなので
                         そちらからは外し、目と指のための印としてだけ置く。 --}}
                    <a href="{{ $link }}" class="menu-row__more" aria-hidden="true" tabindex="-1">詳しく</a>
                @endif
            </span>
            <span class="menu-row__leader" aria-hidden="true"></span>
            @if ($main)
                <span class="menu-row__price">{{ $main->formattedPrice() }}</span>
            @endif
        </div>

        @if ($rest->isNotEmpty())
            <ul class="menu-row__variants">
                @foreach ($rest as $variant)
                    <li>
                        <span>{{ $variant->label ?: 'その他' }}</span>
                        <span class="menu-row__leader" aria-hidden="true"></span>
                        <span class="menu-row__price">{{ $variant->formattedPrice() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($dish->description)
            <p class="menu-row__desc">{{ $dish->description }}</p>
        @endif

        @php
            $tags = [];
            if ($dish->is_new) { $tags[] = ['新', true]; }
            if ($dish->is_limited) { $tags[] = ['期間限定', true]; }
            if ($dish->allergens->isNotEmpty()) {
                $tags[] = [$dish->allergens->pluck('name')->implode('・'), false];
            }
        @endphp

        @if ($tags !== [] || $dish->is_sold_out)
            <ul class="tags">
                @if ($dish->is_sold_out)
                    <li class="tag tag--soldout">品切れ</li>
                @endif
                @foreach ($tags as [$label, $accent])
                    <li @class(['tag', 'tag--accent' => $accent])>{{ $label }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</li>
