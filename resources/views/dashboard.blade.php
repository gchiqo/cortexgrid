@extends('layout')
@section('title', 'პანელი')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span></div>
            <div class="flex items-center gap-4 text-sm">
                <a href="/dashboard/console" class="text-slate-600 hover:text-indigo-600">ტესტ-კონსოლი</a>
                <a href="/dashboard/conversations" class="text-slate-600 hover:text-indigo-600">საუბრები</a>
                <span class="text-slate-500">{{ $user->email }}</span>
                <form method="POST" action="/logout">@csrf
                    <button class="text-slate-600 hover:text-red-600">გასვლა</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8 space-y-8">

        @if (session('new_api_key'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4">
                <div class="font-medium text-emerald-800">ახალი API გასაღები (ნაჩვენებია მხოლოდ ერთხელ):</div>
                <code class="block mt-2 bg-white border rounded px-3 py-2 text-sm break-all">{{ session('new_api_key') }}</code>
            </div>
        @endif

        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        {{-- Usage --}}
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-slate-500 text-sm">ჩატვირთული დოკუმენტები</div>
                <div class="text-3xl font-bold mt-1">{{ $usage['ingest'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-slate-500 text-sm">კითხვები</div>
                <div class="text-3xl font-bold mt-1">{{ $usage['query'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-slate-500 text-sm">გამოყენებული ტოკენები</div>
                <div class="text-3xl font-bold mt-1">{{ number_format($usage['tokens']) }}</div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Test chat --}}
            <section class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-lg mb-4">ტესტ-ჩატი (შენს მონაცემებზე)</h2>
                <select id="config" class="w-full rounded-lg border border-slate-300 px-3 py-2 mb-3">
                    @foreach ($configs as $cfg)
                        <option value="{{ $cfg->id }}">{{ $cfg->name }} ({{ $cfg->model_tier }})</option>
                    @endforeach
                </select>
                <textarea id="question" rows="3" placeholder="დასვი კითხვა ქართულად…"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 mb-3"></textarea>
                <button id="ask" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 font-medium">
                    კითხვა
                </button>
                <div id="answer" class="mt-4 hidden">
                    <div class="rounded-lg bg-slate-50 border p-4 whitespace-pre-wrap text-sm" id="answerText"></div>
                    <div class="mt-2 text-xs text-slate-500" id="answerSources"></div>
                </div>
            </section>

            {{-- API keys --}}
            <section class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-lg mb-4">API გასაღებები</h2>
                <form method="POST" action="/dashboard/keys" class="flex gap-2 mb-4">@csrf
                    <input name="label" placeholder="სახელი (არასავალდებულო)"
                           class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <button class="bg-slate-800 text-white rounded-lg px-4 py-2 text-sm font-medium">ახალი</button>
                </form>
                <table class="w-full text-sm">
                    <thead class="text-slate-400 text-left">
                        <tr><th class="py-1">პრეფიქსი</th><th>სტატუსი</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($apiKeys as $key)
                            <tr class="border-t">
                                <td class="py-2"><code>{{ $key->prefix }}…</code> <span class="text-slate-400">{{ $key->label }}</span></td>
                                <td>@if ($key->revoked_at)<span class="text-red-500">გაუქმებული</span>@else<span class="text-emerald-600">აქტიური</span>@endif</td>
                                <td class="text-right">
                                    @unless ($key->revoked_at)
                                        <form method="POST" action="/dashboard/keys/{{ $key->id }}/revoke">@csrf
                                            <button class="text-red-500 hover:underline">გაუქმება</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            {{-- Configs --}}
            <section class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-lg">AI კონფიგურაციები</h2>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="/dashboard/configs/suggest">@csrf
                            <button class="bg-indigo-50 text-indigo-700 rounded-lg px-3 py-1.5 text-sm font-medium hover:bg-indigo-100">✨ გენერაცია მონაცემებიდან</button>
                        </form>
                        <a href="/dashboard/configs/create"
                           class="bg-slate-800 text-white rounded-lg px-3 py-1.5 text-sm font-medium">+ ახალი</a>
                    </div>
                </div>
                <ul class="space-y-3">
                    @foreach ($configs as $cfg)
                        <li class="border rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ $cfg->name }}</span>
                                <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded">{{ $cfg->model_tier }}</span>
                            </div>
                            <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit($cfg->system_prompt, 120) }}</p>
                            <div class="flex items-center gap-3 mt-2 text-sm">
                                <a href="/dashboard/configs/{{ $cfg->id }}/edit" class="text-indigo-600 hover:underline">რედაქტირება</a>
                                <form method="POST" action="/dashboard/configs/{{ $cfg->id }}"
                                      onsubmit="return confirm('წავშალო ეს კონფიგურაცია?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:underline">წაშლა</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Sources --}}
            <section class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-lg mb-4">წყაროები</h2>

                <form method="POST" action="/dashboard/upload" enctype="multipart/form-data"
                      class="border-2 border-dashed border-slate-200 rounded-lg p-4 mb-5 space-y-3">
                    @csrf
                    <input type="text" name="source_name" placeholder="წყაროს სახელი (არასავალდებულო)"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="file" name="file" required
                           accept=".pdf,.csv,.xlsx,.xls,.txt,.md"
                           class="w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-indigo-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">PDF, CSV, XLSX, TXT — მაქს. 20MB</span>
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-medium">ატვირთვა</button>
                    </div>
                </form>

                @if ($sources->isEmpty())
                    <p class="text-slate-400 text-sm">ჯერ არაფერი ჩაგიტვირთავს. გამოიყენე <code>POST /v1/ingest</code>.</p>
                @else
                    <ul class="space-y-2 text-sm">
                        @foreach ($sources as $src)
                            <li class="flex items-center justify-between border-t py-2">
                                <span>{{ $src->name }} <span class="text-slate-400">({{ $src->type }})</span></span>
                                <span class="text-xs px-2 py-0.5 rounded
                                    {{ $src->status === 'ready' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $src->status }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </main>
</div>

<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
const askBtn = document.getElementById('ask');
askBtn.addEventListener('click', async () => {
    const question = document.getElementById('question').value.trim();
    if (!question) return;
    askBtn.disabled = true; askBtn.textContent = 'მუშავდება…';
    try {
        const res = await fetch('/dashboard/ask', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
            body: JSON.stringify({question, config_id: document.getElementById('config').value}),
        });
        const data = await res.json();
        document.getElementById('answer').classList.remove('hidden');
        document.getElementById('answerText').textContent = data.answer || JSON.stringify(data);
        const srcs = (data.sources || []).map(s => `[#${s.ref}] ${s.title ?? ''}`).join('  ');
        document.getElementById('answerSources').textContent = srcs ? ('წყაროები: ' + srcs) : '';
    } catch (e) {
        document.getElementById('answer').classList.remove('hidden');
        document.getElementById('answerText').textContent = 'შეცდომა: ' + e;
    } finally {
        askBtn.disabled = false; askBtn.textContent = 'კითხვა';
    }
});
</script>
@endsection
