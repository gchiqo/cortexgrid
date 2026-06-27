@extends('layout')
@section('title', 'შემოთავაზებული კონფიგურაციები')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span></div>
            <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">← პანელი</a>
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
                <button class="text-indigo-600 hover:underline text-sm">↻ თავიდან გენერაცია</button>
            </form>
            <a href="/dashboard" class="text-slate-500 hover:text-slate-700 text-sm">დასრულება</a>
        </div>
    </main>
</div>
@endsection
