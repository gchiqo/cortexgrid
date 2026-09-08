@extends('layout')
@section('title', __('configs.suggestions_title'))
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="/" class="font-bold text-lg">CortexGrid <span class="text-indigo-600">AI</span></a>
            <div class="flex items-center gap-2">
                @include('partials.lang-toggle')
                @include('partials.theme-toggle')
                <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">{{ __('common.back_to_dashboard') }}</a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
        <div>
            <h1 class="text-xl font-bold">{{ __('configs.suggestions_heading') }}</h1>
            <p class="text-slate-500 text-sm mt-1">{{ __('configs.suggestions_subtitle') }}</p>
        </div>

        @if (!empty($business_summary))
            <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-4 text-sm text-indigo-900">
                <span class="font-medium">{{ __('configs.analysis') }}:</span> {{ $business_summary }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach ($configs as $cfg)
                <form method="POST" action="/dashboard/configs" class="bg-white rounded-xl shadow-sm p-5 space-y-3 flex flex-col">
                    @csrf
                    <input type="hidden" name="widget_enabled" value="1">
                    <input type="hidden" name="dataset_id" value="{{ $dataset->id }}">
                    <input name="name" value="{{ $cfg['name'] }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 font-medium">

                    <select name="model_tier" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach (['fast', 'standard', 'max'] as $val)
                            <option value="{{ $val }}" @selected($cfg['model_tier'] === $val)>{{ __('configs.tier.'.$val) }}</option>
                        @endforeach
                    </select>

                    <textarea name="system_prompt" rows="5" required
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm flex-1">{{ $cfg['system_prompt'] }}</textarea>

                    @if (!empty($cfg['rationale']))
                        <p class="text-xs text-slate-400">💡 {{ $cfg['rationale'] }}</p>
                    @endif

                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg py-2 font-medium">{{ __('configs.add') }}</button>
                </form>
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <form method="POST" action="/dashboard/configs/suggest">@csrf
                <input type="hidden" name="dataset_id" value="{{ $dataset->id }}">
                <button class="text-indigo-600 hover:underline text-sm">{{ __('configs.regenerate') }}</button>
            </form>
            <a href="/dashboard/datasets/{{ $dataset->id }}" class="text-slate-500 hover:text-slate-700 text-sm">{{ __('configs.finish', ['total' => count($configs)]) }}</a>
        </div>
    </main>
</div>

@php($__t = [
    'adding' => __('configs.adding'),
    'added' => __('configs.added'),
    'add' => __('configs.add'),
])
<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
const T = @json($__t);
let added = 0;
document.querySelectorAll('form[action="/dashboard/configs"]').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button[type=submit], button:not([type])');
        btn.disabled = true; btn.textContent = T.adding;
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: new FormData(form),
            });
            if (res.ok) {
                form.classList.add('opacity-60');
                form.querySelectorAll('input,textarea,select').forEach(el => el.disabled = true);
                btn.textContent = T.added;
                btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
                btn.classList.add('bg-emerald-600');
                added++;
            } else {
                btn.disabled = false; btn.textContent = T.add;
            }
        } catch (_) {
            btn.disabled = false; btn.textContent = T.add;
        }
    });
});
</script>
@endsection
