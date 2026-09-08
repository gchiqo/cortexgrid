@extends('layout')
@section('title', $dataset->name.' — '.__('explorer.title'))
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/dashboard/datasets/{{ $dataset->id }}" class="text-slate-400 hover:text-slate-700">←</a>
                <div class="font-bold text-lg">📊 {{ __('explorer.title') }}</div>
                <span class="text-slate-400 text-sm">{{ $dataset->name }}</span>
            </div>
            <div class="flex items-center gap-2">
                @include('partials.lang-toggle')
                @include('partials.theme-toggle')
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8 space-y-8">
        <p class="text-slate-500 text-sm">{!! __('explorer.intro') !!}</p>

        {{-- Overview --}}
        <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach (['documents', 'chunks', 'embedded', 'sources'] as $k)
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="text-slate-500 text-sm">{{ __('explorer.overview.'.$k) }}</div>
                    <div class="text-3xl font-bold mt-1">{{ number_format($overview[$k]) }}</div>
                </div>
            @endforeach
        </section>

        {{-- Facets --}}
        <section>
            <h2 class="font-semibold text-lg mb-4">{{ __('explorer.entities') }}</h2>
            @if (empty($facets))
                <p class="text-slate-400 text-sm">{{ __('explorer.no_structured') }}</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($facets as $f)
                        <div class="bg-white rounded-xl shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="font-medium">{{ $f['key'] }}</span>
                                @if ($f['type'] === 'facet')
                                    <span class="text-xs text-slate-400">{{ trans_choice('explorer.value_count', $f['distinct'], ['count' => $f['distinct']]) }}</span>
                                @elseif ($f['type'] === 'numeric')
                                    <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded">{{ __('explorer.numeric') }}</span>
                                @endif
                            </div>

                            @if ($f['type'] === 'facet')
                                @php $max = max(array_map(fn ($v) => $v['count'], $f['values'])); @endphp
                                <div class="space-y-1.5">
                                    @foreach ($f['values'] as $v)
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="w-28 shrink-0 truncate text-slate-600">{{ $v['value'] }}</span>
                                            <div class="flex-1 bg-slate-100 rounded h-4 overflow-hidden">
                                                <div class="bg-indigo-400 h-4" style="width: {{ max(6, round($v['count'] / $max * 100)) }}%"></div>
                                            </div>
                                            <span class="text-slate-400 text-xs w-6 text-right">{{ $v['count'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($f['type'] === 'numeric')
                                <div class="flex justify-between text-sm">
                                    <div><div class="text-slate-400 text-xs">{{ __('explorer.min') }}</div><div class="font-semibold">{{ $f['min'] }}</div></div>
                                    <div><div class="text-slate-400 text-xs">{{ __('explorer.avg') }}</div><div class="font-semibold">{{ $f['avg'] }}</div></div>
                                    <div><div class="text-slate-400 text-xs">{{ __('explorer.max') }}</div><div class="font-semibold">{{ $f['max'] }}</div></div>
                                </div>
                            @else
                                <p class="text-sm text-slate-400">{{ __('explorer.free_text', ['count' => $f['distinct']]) }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- AI analysis --}}
        <section class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h2 class="font-semibold text-lg">🧠 {{ __('explorer.ai_analysis') }}</h2>
                <button id="analyzeBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-medium">{{ __('explorer.analyze') }}</button>
            </div>
            <p class="text-slate-500 text-sm mb-4">{{ __('explorer.ai_analysis_hint') }}</p>
            <div id="analysis" class="hidden space-y-4"></div>
        </section>
    </main>
</div>

@php($__t = [
    'running' => __('explorer.analyzing'),
    'again' => __('explorer.again'),
    'relationships' => __('explorer.relationships'),
    'missing_info' => __('explorer.missing_info'),
    'failed' => __('explorer.analysis_failed'),
    'error' => __('common.error'),
])
<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
const T = @json($__t);
const btn = document.getElementById('analyzeBtn');
btn.addEventListener('click', async () => {
    btn.disabled = true; btn.textContent = T.running;
    try {
        const res = await fetch('/dashboard/datasets/{{ $dataset->id }}/analyze', {
            method: 'POST', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}
        });
        const d = await res.json();
        const box = document.getElementById('analysis');
        box.classList.remove('hidden');
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
        let html = '';
        if (d.business_summary) html += '<div class="rounded-lg bg-indigo-50 border border-indigo-200 p-3 text-sm text-indigo-900">'+esc(d.business_summary)+'</div>';
        if ((d.relationships||[]).length) {
            html += '<div><div class="font-medium text-sm mb-2">'+esc(T.relationships)+'</div><div class="flex flex-wrap gap-2">' +
                d.relationships.map(r => '<span class="text-xs bg-slate-100 rounded px-2 py-1">'+esc(r.from)+' <span class="text-indigo-500">→</span> '+esc(r.to)+' <span class="text-slate-400">('+esc(r.type)+')</span></span>').join('') + '</div></div>';
        }
        if ((d.missing_info||[]).length) {
            html += '<div><div class="font-medium text-sm mb-2">'+esc(T.missing_info)+'</div><ul class="list-disc pl-5 text-sm text-slate-600 space-y-1">' +
                d.missing_info.map(m => '<li>'+esc(m)+'</li>').join('') + '</ul></div>';
        }
        box.innerHTML = html || '<p class="text-slate-400 text-sm">'+esc(T.failed)+'</p>';
    } catch (e) {
        document.getElementById('analysis').classList.remove('hidden');
        document.getElementById('analysis').textContent = T.error + ': ' + e;
    } finally {
        btn.disabled = false; btn.textContent = T.again;
    }
});
</script>
@endsection
