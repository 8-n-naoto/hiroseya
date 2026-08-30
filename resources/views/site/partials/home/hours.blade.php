@php
    $site = app(\App\Support\SiteContext::class);
    $store = $site->store();
    $hours = $site->hours();
    $specialDays = $hours->upcomingSpecialDays(3);
@endphp

<section class="section section--tint">
    <div class="wrap">
        <x-site.heading reading="Information" :title="$section->title ?: '営業時間・所在地'" center />

        <div style="display:grid;gap:clamp(28px,5vw,56px);" class="about">
            <div>
                <x-site.hours-table :hours="$hours" />

                @if ($specialDays->isNotEmpty())
                    <div class="menu-note" style="margin-top:24px;">
                        <strong style="font-family:var(--font-mincho);letter-spacing:.1em;">臨時のお知らせ</strong>
                        <ul style="list-style:none;margin-top:10px;">
                            @foreach ($specialDays as $day)
                                <li>
                                    {{ $day->date->format('n月j日(') }}{{ \App\Models\BusinessHour::DAY_LABELS[(int) $day->date->dayOfWeek] }})
                                    …
                                    @if ($day->is_closed)
                                        休業{{ $day->note ? '（'.$day->note.'）' : '' }}
                                    @else
                                        {{ substr((string) $day->opens_at, 0, 5) }}〜{{ substr((string) $day->closes_at, 0, 5) }}
                                        {{ $day->note ? '（'.$day->note.'）' : '' }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div>
                <dl class="deflist">
                    <div class="deflist__row">
                        <dt class="deflist__key">店名</dt>
                        <dd class="deflist__val">{{ $store->name }}{{ $store->name_kana ? '（'.$store->name_kana.'）' : '' }}</dd>
                    </div>
                    <div class="deflist__row">
                        <dt class="deflist__key">所在地</dt>
                        <dd class="deflist__val">{{ $store->formattedPostalCode() }}<br>{{ $store->fullAddress() }}</dd>
                    </div>
                    @if ($store->tel)
                        <div class="deflist__row">
                            <dt class="deflist__key">電話番号</dt>
                            <dd class="deflist__val"><a href="{{ $store->telLink() }}">{{ $store->tel }}</a></dd>
                        </div>
                    @endif
                    @if ($store->seats)
                        <div class="deflist__row">
                            <dt class="deflist__key">席数</dt>
                            <dd class="deflist__val">{{ $store->seats }}席</dd>
                        </div>
                    @endif
                    @if ($store->parking)
                        <div class="deflist__row">
                            <dt class="deflist__key">駐車場</dt>
                            <dd class="deflist__val">{{ $store->parking }}</dd>
                        </div>
                    @endif
                </dl>

                <p style="margin-top:32px;">
                    <a href="{{ route('access') }}" class="textlink">アクセス方法を見る</a>
                </p>
            </div>
        </div>
    </div>
</section>
