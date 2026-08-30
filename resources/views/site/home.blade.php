<x-layouts.site>
    @foreach ($sections as $section)
        @php $partial = 'site.partials.home.'.$section->key; @endphp

        @if (view()->exists($partial))
            @include($partial, ['section' => $section])
        @endif

        {{-- メインビジュアルの直後に営業案内を出す。来店前に一番見られる情報。 --}}
        @if ($section->key === 'hero')
            @include('site.partials.home.infobar')
        @endif
    @endforeach
</x-layouts.site>
