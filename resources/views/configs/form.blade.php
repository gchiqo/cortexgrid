@extends('layout')
@section('title', $config->exists ? 'კონფიგურაციის რედაქტირება' : 'ახალი კონფიგურაცია')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span></div>
            <a href="/dashboard/datasets/{{ $dataset->id }}" class="text-sm text-slate-600 hover:text-slate-900">← {{ $dataset->name }}</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h1 class="text-xl font-bold mb-6">
                {{ $config->exists ? 'კონფიგურაციის რედაქტირება' : 'ახალი AI კონფიგურაცია' }}
            </h1>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-3 text-sm">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <form method="POST" action="{{ $config->exists ? "/dashboard/configs/{$config->id}" : '/dashboard/configs' }}" class="space-y-5">
                @csrf
                @if ($config->exists) @method('PUT') @endif
                <input type="hidden" name="dataset_id" value="{{ $config->dataset_id }}">

                <div>
                    <label class="block text-sm font-medium mb-1">სახელი</label>
                    <input name="name" value="{{ old('name', $config->name) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">მოდელის დონე</label>
                    <select name="model_tier" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach (['fast' => 'სწრაფი (Haiku)', 'standard' => 'სტანდარტი (Sonnet)', 'max' => 'მაქსიმუმი (Opus)'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('model_tier', $config->model_tier) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">სისტემური ინსტრუქცია (ქართულად)</label>
                    <textarea name="system_prompt" rows="6" required
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">{{ old('system_prompt', $config->system_prompt) }}</textarea>
                    <p class="text-xs text-slate-400 mt-1">აღწერე ასისტენტის როლი და ქცევა.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">ხელსაწყოები (მოქმედებები — admin ჩატბოტებისთვის)</label>
                    <input name="enabled_tools" value="{{ old('enabled_tools', implode(', ', $config->enabled_tools ?? [])) }}"
                           placeholder="add_item, update_item, find_items"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="text-xs text-slate-400 mt-1">ხელმისაწვდომი: <code>add_item</code>, <code>update_item</code>, <code>find_items</code> (მძიმით). ჩატბოტი მათით მონაცემებს ცვლის — ეშვება მხოლოდ პანელში/API-ში, არა საჯარო ვიჯეტში.</p>
                </div>

                <div class="border-t pt-5 space-y-4">
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="checkbox" name="widget_enabled" value="1" @checked(old('widget_enabled', $config->widget_enabled ?? true))>
                        ვებ-ვიჯეტი ჩართულია (საიტზე ჩასასმელად)
                    </label>
                    <div>
                        <label class="block text-sm font-medium mb-1">დაშვებული დომენები (არასავალდებულო)</label>
                        <input name="allowed_domains" value="{{ old('allowed_domains', implode(', ', $config->allowed_domains ?? [])) }}"
                               placeholder="example.ge, shop.example.ge"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <p class="text-xs text-slate-400 mt-1">ცარიელი = ნებისმიერი დომენი. შეავსე უსაფრთხოებისთვის.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2.5 font-medium">
                        {{ $config->exists ? 'შენახვა' : 'შექმნა' }}
                    </button>
                    <a href="/dashboard/datasets/{{ $dataset->id }}" class="text-slate-500 hover:text-slate-700">გაუქმება</a>
                </div>
            </form>

            @if ($config->exists && $config->public_key)
                <div class="mt-6 pt-6 border-t">
                    <h3 class="font-medium mb-2">ინტეგრაცია საიტზე</h3>
                    <p class="text-sm text-slate-500 mb-2">ჩასვი ეს კოდი შენი საიტის HTML-ში:</p>
                    <div class="relative">
                        <pre id="snippet" class="bg-slate-900 text-slate-100 text-xs rounded-lg p-3 pr-20 overflow-x-auto"><code>&lt;script src="{{ url('/embed.js') }}?key={{ $config->public_key }}" async&gt;&lt;/script&gt;</code></pre>
                        <button type="button" onclick="copySnippet()" class="absolute top-2 right-2 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded px-2 py-1">კოპირება</button>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">საჯარო გასაღები: <code>{{ $config->public_key }}</code></p>
                </div>
                <script>
                    function copySnippet() {
                        navigator.clipboard.writeText(document.getElementById('snippet').innerText.trim());
                    }
                </script>
            @endif
        </div>
    </main>
</div>
@endsection
