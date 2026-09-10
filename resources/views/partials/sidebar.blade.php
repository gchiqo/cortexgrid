@php
    $nav = [
        ['/dashboard',               'nav.overview',      'grid',   fn ($p) => $p === '/dashboard'],
        ['/dashboard/console',       'nav.console',       'term',   fn ($p) => str_starts_with($p, '/dashboard/console')],
        ['/dashboard/conversations', 'nav.conversations', 'chat',   fn ($p) => str_starts_with($p, '/dashboard/conversations')],
        ['/dashboard/insights',      'nav.insights',      'pulse',  fn ($p) => str_starts_with($p, '/dashboard/insights')],
        ['/dashboard/leads',         'nav.leads',         'user',   fn ($p) => str_starts_with($p, '/dashboard/leads')],
        ['/dashboard/docs',          'nav.api',           'code',   fn ($p) => str_starts_with($p, '/dashboard/docs')],
        ['/dashboard/billing',       'nav.billing',       'card',   fn ($p) => str_starts_with($p, '/dashboard/billing')],
    ];
    $path = '/'.ltrim(request()->path(), '/');
@endphp

<aside class="side" id="side">
    <a href="/" class="side-brand">
        <span class="side-mark">CG</span>
        <span class="side-word">Cortex<b>Grid</b></span>
    </a>

    <nav class="side-nav">
        @foreach ($nav as [$href, $key, $icon, $isActive])
            <a href="{{ $href }}" class="side-link {{ $isActive($path) ? 'is-active' : '' }}">
                <x-icon :name="$icon" />
                <span>{{ __($key) }}</span>
            </a>
        @endforeach

        @if (($user ?? auth()->user())?->isPlatformAdmin())
            <div class="side-sep"></div>
            <a href="/dashboard/settings" class="side-link {{ str_starts_with($path, '/dashboard/settings') ? 'is-active' : '' }}">
                <x-icon name="cog" />
                <span>{{ __('nav.settings') }}</span>
            </a>
        @endif
    </nav>

    <div class="side-foot">
        {{-- Which backend is answering right now, and on how much credit. --}}
        @php($llm = \App\Services\Llm\LlmConfig::active())
        <div class="side-stat">
            <span class="dot"></span>
            <div class="min-w-0">
                <div class="side-stat-k">{{ __('nav.engine') }}</div>
                <div class="side-stat-v" title="{{ $llm['model'] }}">{{ $llm['provider'] }}</div>
            </div>
        </div>
        @isset($tenant)
            <a href="/dashboard/billing" class="side-stat">
                <span class="dot dot-2"></span>
                <div class="min-w-0">
                    <div class="side-stat-k">{{ __('dashboard.credits') }}</div>
                    <div class="side-stat-v">{{ number_format($tenant->credits) }}</div>
                </div>
            </a>
        @endisset

        @auth
            <div class="side-sep"></div>
            <div class="side-user">
                <span class="side-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->email, 0, 1)) }}</span>
                <span class="side-email" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</span>
                <form method="POST" action="/logout">@csrf
                    <button class="side-out" title="{{ __('nav.logout') }}" aria-label="{{ __('nav.logout') }}">⏻</button>
                </form>
            </div>
        @endauth
    </div>
</aside>
