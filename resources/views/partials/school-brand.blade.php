@php
    $schoolName = $school->school_name ?: 'CBT Sekolah';
    $logoUrl = $school->logo_url;
    $asLink = $asLink ?? true;
    $brandHref = auth()->check() ? route('dashboard') : route('login');
    $tag = $asLink ? 'a' : 'div';
@endphp
<{{ $tag }}
    @if($asLink) href="{{ $brandHref }}" @endif
    class="school-brand {{ $brandClass ?? '' }}"
    title="{{ $schoolName }}"
>
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $schoolName }}" class="school-logo">
    @endif
    <span class="school-name">{{ $schoolName }}</span>
</{{ $tag }}>
