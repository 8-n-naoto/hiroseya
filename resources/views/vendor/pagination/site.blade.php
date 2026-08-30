@if ($paginator->hasPages())
    <nav aria-label="ページ送り">
        <ul class="pager">
            @if ($paginator->onFirstPage())
                <li><span class="is-disabled" aria-hidden="true">前へ</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">前へ</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="is-disabled">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="is-current" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">次へ</a></li>
            @else
                <li><span class="is-disabled" aria-hidden="true">次へ</span></li>
            @endif
        </ul>
    </nav>
@endif
