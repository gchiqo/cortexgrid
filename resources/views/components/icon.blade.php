@props(['name'])
@php
    $paths = [
        'grid'  => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'term'  => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M7 9l3 3-3 3M13 15h4"/>',
        'chat'  => '<path d="M21 12a8 8 0 0 1-8 8H7l-4 3 1.2-4.3A8 8 0 1 1 21 12Z"/>',
        'pulse' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'user'  => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
        'code'  => '<path d="M8.5 8.5 4 13l4.5 4.5M15.5 8.5 20 13l-4.5 4.5M13.5 5l-3 15"/>',
        'card'  => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19"/>',
        'cog'   => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v2.6M12 18.6v2.6M21.2 12h-2.6M5.4 12H2.8M18.5 5.5l-1.8 1.8M7.3 16.7l-1.8 1.8M18.5 18.5l-1.8-1.8M7.3 7.3 5.5 5.5"/>',
        'menu'  => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'search'=> '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>',
    ];
@endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
     stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => 'ico']) }}>
    {!! $paths[$name] ?? '' !!}
</svg>
