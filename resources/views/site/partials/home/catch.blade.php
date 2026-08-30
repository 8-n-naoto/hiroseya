<section class="section">
    <div class="wrap wrap--narrow" style="text-align:center;" data-reveal>
        @if ($section->title)
            <p style="font-family:var(--font-mincho);font-size:clamp(20px,3.2vw,30px);line-height:2;letter-spacing:.18em;">
                {!! nl2br(e($section->title)) !!}
            </p>
        @endif

        @if ($section->body)
            <div class="prose" style="margin-top:28px;text-align:left;">{!! nl2br(e($section->body)) !!}</div>
        @endif
    </div>
</section>
