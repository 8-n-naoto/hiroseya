@props(['reading' => null, 'title', 'lead' => null])
<div class="page-head">
    <div class="wrap">
        @if ($reading)
            <span class="page-head__reading" aria-hidden="true">{{ $reading }}</span>
        @endif
        <h1 class="page-head__title">{{ $title }}</h1>
        @if ($lead)
            <p class="page-head__lead">{{ $lead }}</p>
        @endif
    </div>
</div>
