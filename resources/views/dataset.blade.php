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
                <a href="/dashboard/datasets/{{ $dataset->id }}/explorer" class="text-slate-600 hover:text-indigo-600">📊 ცოდნის მკვლევარი</a>
                <a href="/dashboard/console" class="text-slate-600 hover:text-indigo-600">ტესტ-კონსოლი</a>
                <a href="/dashboard/conversations" class="text-slate-600 hover:text-indigo-600">საუბრები</a>
                @include('partials.theme-toggle')
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
                <form method="POST" action="/dashboard/upload" enctype="multipart/form-data" id="uploadForm"
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

{{-- Upload pipeline animation overlay --}}
<div id="upOverlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-xl mx-4">
        <h3 class="font-bold text-lg text-center mb-1">მონაცემები მუშავდება</h3>
        <p class="text-center text-slate-400 text-sm mb-7" id="upFile"></p>
        <div class="up-pipe">
            <div class="up-stage" data-s="0"><div class="up-dot">📄</div><span>ამოღება</span></div>
            <div class="up-line" data-l="0"></div>
            <div class="up-stage" data-s="1"><div class="up-dot">✂️</div><span>დაყოფა</span></div>
            <div class="up-line" data-l="1"></div>
            <div class="up-stage" data-s="2"><div class="up-dot">💾</div><span>შენახვა</span></div>
            <div class="up-line" data-l="2"></div>
            <div class="up-stage" data-s="3"><div class="up-dot">🧠</div><span>ემბედინგი</span></div>
            <div class="up-line" data-l="3"></div>
            <div class="up-stage" data-s="4"><div class="up-dot">✅</div><span>მზადაა</span></div>
        </div>
        <p class="text-center text-sm text-slate-500 mt-7" id="upStatus">იწყება…</p>
        <div class="text-center mt-4 hidden" id="upClose">
            <button onclick="location.reload()" class="bg-indigo-600 text-white rounded-lg px-5 py-2 text-sm font-medium">გვერდის განახლება</button>
        </div>
    </div>
</div>
<style>
.up-pipe{display:flex;align-items:flex-start;justify-content:space-between}
.up-stage{display:flex;flex-direction:column;align-items:center;gap:8px;width:64px;flex:0 0 auto}
.up-stage span{font-size:12px;color:#94a3b8;text-align:center}
.up-dot{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;
        background:#f1f5f9;border:2px solid #e2e8f0;transition:all .35s ease;filter:grayscale(1);opacity:.55}
.up-stage.active .up-dot{border-color:#6366f1;background:#eef2ff;filter:none;opacity:1;transform:scale(1.12);animation:upPulse 1s ease-in-out infinite}
.up-stage.done .up-dot{border-color:#10b981;background:#ecfdf5;filter:none;opacity:1}
.up-stage.active span,.up-stage.done span{color:#334155;font-weight:600}
.up-line{flex:1;height:3px;background:#e2e8f0;margin-top:23px;border-radius:2px;position:relative;overflow:hidden}
.up-line.fill::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,#6366f1,#10b981);animation:upFlow .5s ease forwards}
@keyframes upPulse{0%,100%{box-shadow:0 0 0 6px rgba(99,102,241,.15)}50%{box-shadow:0 0 0 11px rgba(99,102,241,.04)}}
@keyframes upFlow{from{transform:translateX(-100%)}to{transform:translateX(0)}}
</style>

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

// --- Upload pipeline animation ---
const uploadForm = document.getElementById('uploadForm');
if (uploadForm) {
    const overlay = document.getElementById('upOverlay');
    const stages = [...document.querySelectorAll('.up-stage')];
    const lines = [...document.querySelectorAll('.up-line')];
    const upStatus = document.getElementById('upStatus');
    const upFile = document.getElementById('upFile');
    const upClose = document.getElementById('upClose');
    const sleep = ms => new Promise(r => setTimeout(r, ms));
    const active = i => stages.forEach((s, idx) => s.classList.toggle('active', idx === i));
    const done = i => { stages[i].classList.remove('active'); stages[i].classList.add('done'); if (lines[i]) lines[i].classList.add('fill'); };

    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const file = uploadForm.querySelector('input[type=file]').files[0];
        if (!file) return;
        stages.forEach(s => s.classList.remove('active', 'done'));
        lines.forEach(l => l.classList.remove('fill'));
        upClose.classList.add('hidden');
        overlay.classList.remove('hidden'); overlay.classList.add('flex');
        upFile.textContent = file.name;
        upStatus.textContent = 'ფაილის წაკითხვა…';
        active(0); await sleep(550);

        try {
            const res = await fetch(uploadForm.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: new FormData(uploadForm),
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({ error: 'ატვირთვა ვერ მოხერხდა' }));
                upStatus.textContent = '❌ ' + (err.error || 'შეცდომა');
                upClose.classList.remove('hidden');
                return;
            }
            const data = await res.json();
            done(0); active(1); upStatus.textContent = 'ტექსტის დაყოფა ჩანკებად…'; await sleep(500);
            done(1); active(2); upStatus.textContent = `${data.documents} დოკუმენტი · ${data.chunks} ჩანკი შენახულია`; await sleep(500);
            done(2); active(3); upStatus.textContent = 'ვექტორული ემბედინგების შექმნა (Gemini)…';

            let tries = 0;
            while (tries++ < 24) {
                await sleep(1500);
                try {
                    const s = await (await fetch(`/dashboard/sources/${data.source_id}/status`, { headers: { 'Accept': 'application/json' } })).json();
                    if (s.status === 'ready') {
                        done(3); active(4); done(4);
                        upStatus.textContent = '✅ მზადაა — მონაცემები ძებნადია!';
                        await sleep(950); location.reload(); return;
                    }
                    if (s.status === 'failed') {
                        upStatus.textContent = '❌ ემბედინგი ჩაიშალა — შეამოწმე Gemini გასაღები.';
                        upClose.classList.remove('hidden'); return;
                    }
                } catch (_) { /* keep polling */ }
            }
            upStatus.textContent = 'შენახულია ✓ — ემბედინგი ფონურად დასრულდება (გაუშვი queue:work).';
            upClose.classList.remove('hidden');
        } catch (err) {
            upStatus.textContent = '❌ ' + err;
            upClose.classList.remove('hidden');
        }
    });
}
</script>
@endsection
