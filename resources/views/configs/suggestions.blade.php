@extends('layout')
@section('title', 'შემოთავაზებული კონფიგურაციები')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="/" class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span></a>
            <div class="flex items-center gap-2">
                @include('partials.theme-toggle')
                <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">← პანელი</a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
        <div>
            <h1 class="text-xl font-bold">შემოთავაზებული ჩატბოტები</h1>
            <p class="text-slate-500 text-sm mt-1">დაგენერირდა შენი მონაცემების მიხედვით. დაარედაქტირე საჭიროებისამებრ და დაამატე.</p>
        </div>

        @if (!empty($business_summary))
            <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-4 text-sm text-indigo-900">
                <span class="font-medium">ანალიზი:</span> {{ $business_summary }}
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
                        @foreach (['fast' => 'სწრაფი', 'standard' => 'სტანდარტი', 'max' => 'მაქსიმუმი'] as $val => $label)
                            <option value="{{ $val }}" @selected($cfg['model_tier'] === $val)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <textarea name="system_prompt" rows="5" required
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm flex-1">{{ $cfg['system_prompt'] }}</textarea>

                    @if (!empty($cfg['rationale']))
                        <p class="text-xs text-slate-400">💡 {{ $cfg['rationale'] }}</p>
                    @endif

                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg py-2 font-medium">დამატება</button>
                </form>
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <form method="POST" action="/dashboard/configs/suggest">@csrf
                <input type="hidden" name="dataset_id" value="{{ $dataset->id }}">
                <button class="text-indigo-600 hover:underline text-sm">↻ თავიდან გენერაცია</button>
            </form>
            <a href="/dashboard/datasets/{{ $dataset->id }}" class="text-slate-500 hover:text-slate-700 text-sm">დასრულება ({{ count($configs) }}-დან არჩეული)</a>
        </div>
    </main>
</div>

<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
let added = 0;
document.querySelectorAll('form[action="/dashboard/configs"]').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button[type=submit], button:not([type])');
        btn.disabled = true; btn.textContent = 'ემატება…';
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: new FormData(form),
            });
            if (res.ok) {
                form.classList.add('opacity-60');
                form.querySelectorAll('input,textarea,select').forEach(el => el.disabled = true);
                btn.textContent = '✓ დამატებულია';
                btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
                btn.classList.add('bg-emerald-600');
                added++;
            } else {
                btn.disabled = false; btn.textContent = 'დამატება';
            }
        } catch (_) {
            btn.disabled = false; btn.textContent = 'დამატება';
        }
    });
});
</script>
@endsection
