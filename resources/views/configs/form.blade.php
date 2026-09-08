@extends('layout')
@section('title', $config->exists ? __('configs.edit_title') : __('configs.new_title'))
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="/" class="font-bold text-lg">CortexGrid <span class="text-indigo-600">AI</span></a>
            <div class="flex items-center gap-2">
                @include('partials.lang-toggle')
                @include('partials.theme-toggle')
                <a href="/dashboard/datasets/{{ $dataset->id }}" class="text-sm text-slate-600 hover:text-slate-900">← {{ $dataset->name }}</a>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h1 class="text-xl font-bold mb-6">
                {{ $config->exists ? __('configs.edit_title') : __('configs.new_heading') }}
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
                    <label class="block text-sm font-medium mb-1">{{ __('configs.name') }}</label>
                    <input name="name" value="{{ old('name', $config->name) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('configs.model_tier') }}</label>
                    <select name="model_tier" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach (['fast', 'standard', 'max'] as $val)
                            <option value="{{ $val }}" @selected(old('model_tier', $config->model_tier) === $val)>{{ __('configs.tier_model.'.$val) }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($datasets->count() > 1)
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('configs.extra_datasets') }}</label>
                        @php $extra = $config->data_scope['dataset_ids'] ?? []; @endphp
                        <div class="space-y-1.5 border border-slate-200 rounded-lg p-3">
                            @foreach ($datasets as $ds)
                                @if ($ds->id !== $config->dataset_id)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="additional_datasets[]" value="{{ $ds->id }}" @checked(in_array($ds->id, $extra))>
                                        {{ $ds->name }}
                                    </label>
                                @endif
                            @endforeach
                        </div>
                        <p class="text-xs text-slate-400 mt-1">{!! __('configs.extra_datasets_hint', ['dataset' => e($dataset->name)]) !!}</p>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('configs.system_prompt') }}</label>
                    <textarea name="system_prompt" rows="6" required
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">{{ old('system_prompt', $config->system_prompt) }}</textarea>
                    <p class="text-xs text-slate-400 mt-1">{{ __('configs.system_prompt_hint') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('configs.tools') }}</label>
                    <input name="enabled_tools" value="{{ old('enabled_tools', implode(', ', $config->enabled_tools ?? [])) }}"
                           placeholder="add_item, update_item, find_items"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="text-xs text-slate-400 mt-1">{!! __('configs.tools_hint') !!}</p>
                </div>

                <div class="border-t pt-5">
                    @php $w = $config->widget(); @endphp
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="checkbox" name="rerank" value="1" @checked(old('rerank', $config->rerankEnabled()))>
                        🎯 {{ __('configs.rerank') }}
                    </label>
                </div>

                <div class="border-t pt-5 space-y-4">
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="checkbox" name="widget_enabled" value="1" @checked(old('widget_enabled', $config->widget_enabled ?? true))>
                        {{ __('configs.widget_enabled') }}
                    </label>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('configs.allowed_domains') }}</label>
                        <input name="allowed_domains" value="{{ old('allowed_domains', implode(', ', $config->allowed_domains ?? [])) }}"
                               placeholder="example.ge, shop.example.ge"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <p class="text-xs text-slate-400 mt-1">{{ __('configs.allowed_domains_hint') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('configs.widget_color') }}</label>
                            <input type="color" name="widget_color" value="{{ old('widget_color', $w['color']) }}" class="h-10 w-full rounded-lg border border-slate-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('configs.widget_position') }}</label>
                            <select name="widget_position" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="right" @selected($w['position']==='right')>{{ __('configs.position_right') }}</option>
                                <option value="left" @selected($w['position']==='left')>{{ __('configs.position_left') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('configs.widget_title') }}</label>
                            <input name="widget_title" value="{{ old('widget_title', $w['title']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('configs.widget_launcher') }}</label>
                            <input name="widget_launcher" value="{{ old('widget_launcher', $w['launcher']) }}" maxlength="4" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-1">{{ __('configs.widget_greeting') }}</label>
                            <input name="widget_greeting" value="{{ old('widget_greeting', $w['greeting']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2.5 font-medium">
                        {{ $config->exists ? __('common.save') : __('common.create') }}
                    </button>
                    <a href="/dashboard/datasets/{{ $dataset->id }}" class="text-slate-500 hover:text-slate-700">{{ __('common.cancel') }}</a>
                </div>
            </form>

            @if ($config->exists && $config->public_key)
                <div class="mt-6 pt-6 border-t">
                    <h3 class="font-medium mb-2">{{ __('configs.integration') }}</h3>
                    <p class="text-sm text-slate-500 mb-2">{{ __('configs.integration_hint') }}</p>
                    <div class="relative">
                        <pre id="snippet" class="bg-slate-900 text-slate-100 text-xs rounded-lg p-3 pr-20 overflow-x-auto"><code>&lt;script src="{{ url('/embed.js') }}?key={{ $config->public_key }}" async&gt;&lt;/script&gt;</code></pre>
                        <button type="button" onclick="copySnippet()" class="absolute top-2 right-2 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded px-2 py-1">{{ __('common.copy') }}</button>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">{{ __('configs.public_key') }}: <code>{{ $config->public_key }}</code></p>
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
