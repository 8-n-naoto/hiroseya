{{--
    パンくず。構造化データ（BreadcrumbList）は Seo に登録された同じ配列から
    レイアウト側で出しているので、ここでは表示だけを担当する。
--}}
@php $items = app(\App\Support\Seo::class)->breadcrumbItems(); @endphp

@if ($items !== [])
    <nav class="crumbs" aria-label="パンくずリスト">
        <div class="wrap">
            <ol>
                <li><a href="{{ route('home') }}">ホーム</a></li>
                @foreach ($items as $item)
                    <li>
                        @if ($item['url'] && ! $loop->last)
                            <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                        @else
                            <span aria-current="page">{{ $item['name'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </nav>
@endif
