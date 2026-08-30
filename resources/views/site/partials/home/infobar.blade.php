@php
    $site = app(\App\Support\SiteContext::class);
    $store = $site->store();
    $today = $site->hours()->today();
@endphp

<div class="infobar">
    <div class="wrap">
        <div class="infobar__grid">
            <div class="infobar__cell">
                <span class="infobar__label">本日の営業</span>
                <p class="infobar__value">
                    @if ($today['closed'])
                        <span class="badge-closed">本日休み</span>{{ $today['note'] ?: '定休日' }}
                    @else
                        <span class="badge-open">営業日</span>
                        @foreach ($today['ranges'] as $range)
                            {{ $range['range'] }}@if (! $loop->last)　/　@endif
                        @endforeach
                    @endif
                </p>
            </div>

            <div class="infobar__cell">
                <span class="infobar__label">ご予約・お問い合わせ</span>
                <p class="infobar__value infobar__value--tel">
                    @if ($store->tel)
                        <a href="{{ $store->telLink() }}">{{ $store->tel }}</a>
                    @else
                        <a href="{{ route('contact.create') }}">お問い合わせフォーム</a>
                    @endif
                </p>
            </div>

            <div class="infobar__cell">
                <span class="infobar__label">所在地</span>
                <p class="infobar__value" style="font-size:14px;">
                    <a href="{{ route('access') }}">{{ $store->fullAddress() }}</a>
                </p>
            </div>
        </div>
    </div>
</div>
