@extends('layout')
@section('title', $config->exists ? 'კონფიგურაციის რედაქტირება' : 'ახალი კონფიგურაცია')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span></div>
            <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">← პანელი</a>
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
                    <label class="block text-sm font-medium mb-1">ხელსაწყოები (არასავალდებულო)</label>
                    <input name="enabled_tools" value="{{ old('enabled_tools', implode(', ', $config->enabled_tools ?? [])) }}"
                           placeholder="add_product, update_product"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="text-xs text-slate-400 mt-1">მძიმით გამოყოფილი სია (function calling — მომავალში).</p>
                </div>

                <div class="flex items-center gap-3">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2.5 font-medium">
                        {{ $config->exists ? 'შენახვა' : 'შექმნა' }}
                    </button>
                    <a href="/dashboard" class="text-slate-500 hover:text-slate-700">გაუქმება</a>
                </div>
            </form>
        </div>
    </main>
</div>
@endsection
