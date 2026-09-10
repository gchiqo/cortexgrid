@extends('layout-app')
@section('title', __('settings.title'))
@push('head')
<style>
.js-test{border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--muted);
    transition:all .16s ease;cursor:pointer;font-weight:500}
.js-test:hover{border-color:rgba(34,211,238,.5);color:var(--accent);background:rgba(34,211,238,.08);
    box-shadow:0 0 18px -6px rgba(34,211,238,.7)}
.js-test:disabled{opacity:.5;cursor:default}
/* the selected provider card gets a live accent edge */
section.ring-2{position:relative}
section.ring-2::before{content:'';position:absolute;left:0;top:14px;bottom:14px;width:2px;border-radius:2px;
    background:linear-gradient(180deg,var(--accent),var(--accent-2));box-shadow:0 0 14px rgba(34,211,238,.7)}
</style>
@endpush
@section('heading'){{ __('settings.title') }}@endsection
@section('crumb'){{ __('common.app_title') }}@endsection

@section('content')
@if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        @if ($fallingBack)
            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                {{ __('settings.falling_back', [
                    'selected' => __('settings.provider.'.$active),
                    'used' => __('settings.provider.'.$resolved),
                ]) }}
            </div>
        @endif

        <div>
            <h1 class="text-xl font-bold">{{ __('settings.heading') }}</h1>
            <p class="text-slate-500 text-sm mt-1">{{ __('settings.subtitle') }}</p>
        </div>

        <form method="POST" action="/dashboard/settings" class="space-y-5">
            @csrf

            @foreach ($providers as $name => $p)
                <section class="bg-white rounded-xl shadow-sm p-5 space-y-4 {{ $active === $name ? 'ring-2 ring-indigo-500' : '' }}">
                    <div class="flex items-start justify-between gap-4">
                        <label class="flex items-center gap-2 font-semibold cursor-pointer">
                            <input type="radio" name="provider" value="{{ $name }}" @checked($active === $name)>
                            {{ __('settings.provider.'.$name) }}
                            @if ($resolved === $name && $fallingBack)
                                <span class="text-xs bg-amber-50 text-amber-700 px-2 py-0.5 rounded font-normal">{{ __('settings.in_use') }}</span>
                            @endif
                            @if ($p['has_key'])
                                <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded font-normal">{{ __('settings.key_set') }}</span>
                            @else
                                <span class="text-xs bg-amber-50 text-amber-700 px-2 py-0.5 rounded font-normal">{{ __('settings.no_key_badge') }}</span>
                            @endif
                        </label>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="js-test-result text-xs text-slate-400" data-for="{{ $name }}"></span>
                            <button type="button" class="js-test text-xs rounded-lg px-3 py-1.5"
                                    data-provider="{{ $name }}">{{ __('settings.test') }}</button>
                        </div>
                    </div>

                    <p class="text-xs text-slate-400">
                        {{ __('settings.hint.'.$name) }}
                        @if ($p['console_url'])
                            <a href="{{ $p['console_url'] }}" target="_blank" rel="noopener noreferrer"
                               class="text-indigo-600 hover:underline whitespace-nowrap">{{ __('settings.get_key') }} ↗</a>
                        @endif
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium mb-1">{{ __('settings.api_key') }}</label>
                            <div class="flex gap-2">
                                <input type="password" name="keys[{{ $name }}]" autocomplete="new-password"
                                       placeholder="{{ $p['has_key'] ? $p['key_hint'].'  —  '.__('settings.leave_blank') : __('settings.paste_key') }}"
                                       class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @if ($p['has_key'] && ! $p['from_env'])
                                    {{-- lives in its own form below; the form= attribute keeps it out of this one --}}
                                    <button type="submit" form="clear-{{ $name }}"
                                            class="text-xs text-red-500 hover:underline px-2 shrink-0">{{ __('settings.clear_key') }}</button>
                                @endif
                            </div>
                            @if ($p['from_env'])
                                <p class="text-xs text-slate-400 mt-1">{{ __('settings.from_env') }}</p>
                            @endif
                        </div>

                        @if ($name !== 'anthropic')
                            <div>
                                <label class="block text-xs font-medium mb-1">{{ __('settings.base_url') }}</label>
                                <input name="base_urls[{{ $name }}]" value="{{ $p['base_url'] }}"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-medium mb-1">{{ __('settings.default_model') }}</label>
                            <input name="models[{{ $name }}]" value="{{ $p['model'] }}" list="models-{{ $name }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                            <datalist id="models-{{ $name }}"></datalist>
                        </div>

                        @foreach ($tiers as $tier)
                            <div>
                                <label class="block text-xs font-medium mb-1">{{ __('settings.tier') }} · {{ __('configs.tier.'.$tier) }}</label>
                                <input name="tiers[{{ $name }}][{{ $tier }}]" value="{{ $p['tiers'][$tier] ?? '' }}" list="models-{{ $name }}"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="flex items-center gap-3">
                <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2.5 font-medium">{{ __('common.save') }}</button>
                <a href="/dashboard" class="text-slate-500 hover:text-slate-700">{{ __('common.cancel') }}</a>
            </div>
        </form>

        {{-- Separate forms so "clear key" never submits the settings form. --}}
        @foreach ($providers as $name => $p)
            @if ($p['has_key'] && ! $p['from_env'])
                <form id="clear-{{ $name }}" method="POST" action="/dashboard/settings/{{ $name }}/key" class="hidden">
                    @csrf @method('DELETE')
                </form>
            @endif
        @endforeach
    </main>
</div>

@php($__t = [
    'testing' => __('settings.testing'),
    'ok' => __('settings.test_ok'),
    'fail' => __('settings.test_fail'),
    'models_found' => __('settings.models_found'),
])
<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
const T = @json($__t);

document.querySelectorAll('.js-test').forEach(btn => {
    btn.addEventListener('click', async () => {
        const provider = btn.dataset.provider;
        const out = document.querySelector(`.js-test-result[data-for="${provider}"]`);
        btn.disabled = true;
        out.textContent = T.testing;
        out.className = 'js-test-result text-xs text-slate-400';
        try {
            const res = await fetch(`/dashboard/settings/${provider}/test`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const d = await res.json();
            if (d.ok) {
                const list = document.getElementById('models-' + provider);
                if (list && (d.models || []).length) {
                    list.innerHTML = d.models.map(m => `<option value="${m}"></option>`).join('');
                }
                const extra = (d.models || []).length
                    ? ` · ${T.models_found.replace(':count', d.models.length)}`
                    : '';
                out.textContent = `${T.ok} (${d.ms}ms)${extra}`;
                out.className = 'js-test-result text-xs text-emerald-600';
            } else {
                out.textContent = `${T.fail}: ${d.error || ''}`;
                out.className = 'js-test-result text-xs text-red-500';
            }
        } catch (e) {
            out.textContent = `${T.fail}: ${e}`;
            out.className = 'js-test-result text-xs text-red-500';
        } finally {
            btn.disabled = false;
        }
    });
});
</script>
@endsection
