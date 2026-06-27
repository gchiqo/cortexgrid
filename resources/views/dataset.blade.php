@extends('layout')
@section('title', $dataset->name)
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/dashboard" class="text-slate-400 hover:text-slate-700">←</a>
                <div class="font-bold text-lg">{{ $dataset->name }}</div>
                <span class="text-xs text-slate-400">{{ $docCount }} დოკ. · {{ $chunkCount }} ჩანკი</span>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <a href="/dashboard/console" class="text-slate-600 hover:text-indigo-600">ტესტ-კონსოლი</a>
                <a href="/dashboard/conversations" class="text-slate-600 hover:text-indigo-600">საუბრები</a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8 space-y-6">
        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if (session('new_api_key'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4">
                <div class="font-medium text-emerald-800">ახალი API გასაღები (ერთხელ ნაჩვენები):</div>
                <code class="block mt-2 bg-white border rounded px-3 py-2 text-sm break-all">{{ session('new_api_key') }}</code>
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Sources --}}
            <section class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-lg mb-4">წყაროები (ფაილები / იმპორტი)</h2>
                <form method="POST" action="/dashboard/upload" enctype="multipart/form-data"
                      class="border-2 border-dashed border-slate-200 rounded-lg p-4 mb-5 space-y-3">
                    @csrf
                    <input type="hidden" name="dataset_id" value="{{ $dataset->id }}">
                    <input type="text" name="source_name" placeholder="წყაროს სახელი (არასავალდებულო)"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="file" name="file" required accept=".pdf,.csv,.xlsx,.xls,.txt,.md"
                           class="w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-indigo-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">PDF, CSV, XLSX, TXT — ერთ დატასეტში მრავალი ფაილი</span>
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-medium">ატვირთვა</button>
                    </div>
                </form>
                @if ($sources->isEmpty())
                    <p class="text-slate-400 text-sm">ჯერ არაფერი ჩაგიტვირთავს. ატვირთე ფაილი, ან გამოიყენე
                        <code>POST /v1/ingest</code> <code>{"dataset": {{ $dataset->id }}}</code>-ით.</p>
                @else
                    <ul class="space-y-2 text-sm">
                        @foreach ($sources as $src)
                            <li class="flex items-center justify-between border-t py-2">
                                <span>{{ $src->name }} <span class="text-slate-400">({{ $src->type }})</span></span>
                                <span class="text-xs px-2 py-0.5 rounded {{ $src->status === 'ready' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $src->status }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Test chat --}}
            <section class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-lg mb-4">ტესტ-ჩატი (ამ დატასეტზე)</h2>
                @if ($configs->isEmpty())
                    <p class="text-slate-400 text-sm">ჯერ ჩატბოტი არ არის. შექმენი ქვემოთ.</p>
                @else
                    <select id="config" class="w-full rounded-lg border border-slate-300 px-3 py-2 mb-3">
                        @foreach ($configs as $cfg)
                            <option value="{{ $cfg->id }}">{{ $cfg->name }} ({{ $cfg->model_tier }})</option>
                        @endforeach
                    </select>
                    <textarea id="question" rows="3" placeholder="დასვი კითხვა ქართულად…"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 mb-3"></textarea>
                    <button id="ask" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 font-medium">კითხვა</button>
                    <div id="answer" class="mt-4 hidden">
                        <div class="rounded-lg bg-slate-50 border p-4 whitespace-pre-wrap text-sm" id="answerText"></div>
                        <div class="mt-2 text-xs text-slate-500" id="answerSources"></div>
                    </div>
                @endif
            </section>
        </div>

        {{-- Chatbots --}}
        <section class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-lg">ჩატბოტები</h2>
                <div class="flex items-center gap-2">
                    <form method="POST" action="/dashboard/configs/suggest">@csrf
                        <input type="hidden" name="dataset_id" value="{{ $dataset->id }}">
                        <button class="bg-indigo-50 text-indigo-700 rounded-lg px-3 py-1.5 text-sm font-medium hover:bg-indigo-100">✨ გენერაცია მონაცემებიდან</button>
                    </form>
                    <a href="/dashboard/configs/create?dataset={{ $dataset->id }}"
                       class="bg-slate-800 text-white rounded-lg px-3 py-1.5 text-sm font-medium">+ ახალი</a>
                </div>
            </div>
            @if ($configs->isEmpty())
                <p class="text-slate-400 text-sm">ჯერ ჩატბოტი არ არის. შექმენი ხელით ან დააგენერირე მონაცემებიდან.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($configs as $cfg)
                        <div class="border rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ $cfg->name }}</span>
                                <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded">{{ $cfg->model_tier }}</span>
                            </div>
                            <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit($cfg->system_prompt, 110) }}</p>
                            <div class="flex items-center gap-3 mt-2 text-sm">
                                <a href="/dashboard/configs/{{ $cfg->id }}/edit" class="text-indigo-600 hover:underline">რედაქტირება / ჩასმის კოდი</a>
                                <form method="POST" action="/dashboard/configs/{{ $cfg->id }}" onsubmit="return confirm('წავშალო?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:underline">წაშლა</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <form method="POST" action="/dashboard/datasets/{{ $dataset->id }}" onsubmit="return confirm('წავშალო ეს დატასეტი და მისი ყველა მონაცემი?')">
            @csrf @method('DELETE')
            <button class="text-red-400 hover:text-red-600 text-sm">დატასეტის წაშლა</button>
        </form>
    </main>
</div>

<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
const askBtn = document.getElementById('ask');
if (askBtn) {
    askBtn.addEventListener('click', async () => {
        const q = document.getElementById('question').value.trim();
        if (!q) return;
        askBtn.disabled = true; askBtn.textContent = 'მუშავდება…';
        try {
            const res = await fetch('/dashboard/ask', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                body: JSON.stringify({question: q, config_id: document.getElementById('config').value}),
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
}
</script>
@endsection
