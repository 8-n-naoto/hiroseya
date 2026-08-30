@php $settings = app(\App\Support\Settings::class); @endphp

<x-layouts.site>
    <x-site.page-head reading="Access" title="アクセス・営業時間"
        :lead="$store->fullAddress().'　'.($store->access_train ?: '')" />

    <div class="section">
        <div class="wrap">
            <div class="about">
                <div>
                    <x-site.heading reading="Hours" title="営業時間" level="h2" />
                    <x-site.hours-table :hours="$hours" />

                    @if ($specialDays->isNotEmpty())
                        <div class="menu-note" style="margin-top:28px;">
                            <strong style="font-family:var(--font-mincho);letter-spacing:.1em;">臨時休業・時間変更</strong>
                            <ul style="list-style:none;margin-top:10px;">
                                @foreach ($specialDays as $day)
                                    <li>
                                        {{ $day->date->format('n月j日') }}（{{ \App\Models\BusinessHour::DAY_LABELS[(int) $day->date->dayOfWeek] }}）
                                        …
                                        @if ($day->is_closed)
                                            休業
                                        @else
                                            {{ substr((string) $day->opens_at, 0, 5) }}〜{{ substr((string) $day->closes_at, 0, 5) }}
                                        @endif
                                        {{ $day->note ? '（'.$day->note.'）' : '' }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div>
                    <x-site.heading reading="Store" title="店舗情報" level="h2" />
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
                        @if ($store->fax)
                            <div class="deflist__row">
                                <dt class="deflist__key">FAX</dt>
                                <dd class="deflist__val">{{ $store->fax }}</dd>
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
                        @if (filled($store->payment_methods))
                            <div class="deflist__row">
                                <dt class="deflist__key">お支払い</dt>
                                <dd class="deflist__val">{{ implode('・', $store->payment_methods) }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if ($settings->mapEnabled() && $settings->string('access.map_embed') !== '')
        <div class="section section--tint" style="padding-top:0;">
            <div class="wrap">
                <div class="map-frame">{!! $settings->string('access.map_embed') !!}</div>
                @if ($settings->string('access.map_link') !== '')
                    <p style="margin-top:20px;text-align:center;">
                        <a href="{{ $settings->string('access.map_link') }}" class="textlink"
                            target="_blank" rel="noopener noreferrer">地図アプリで開く</a>
                    </p>
                @endif
            </div>
        </div>
    @endif

    @if ($store->access_car || $store->access_train)
        <div class="section">
            <div class="wrap wrap--narrow">
                <x-site.heading reading="Route" title="お越しの方法" level="h2" center />
                <dl class="deflist">
                    @if ($store->access_train)
                        <div class="deflist__row">
                            <dt class="deflist__key">電車で</dt>
                            <dd class="deflist__val">{!! nl2br(e($store->access_train)) !!}</dd>
                        </div>
                    @endif
                    @if ($store->access_car)
                        <div class="deflist__row">
                            <dt class="deflist__key">お車で</dt>
                            <dd class="deflist__val">{!! nl2br(e($store->access_car)) !!}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    @endif
</x-layouts.site>
