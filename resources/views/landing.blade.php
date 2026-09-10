@extends('layout')
@section('title', __('landing.title'))
@section('body')
<div class="min-h-screen bg-gradient-to-b from-slate-50 to-indigo-50/40">
    <header class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
        <a href="/" class="font-bold text-lg">CortexGrid <span class="text-indigo-600">AI</span></a>
        <div class="flex items-center gap-3 text-sm">
            @include('partials.lang-toggle')
            @include('partials.theme-toggle')
            <a href="#how" class="text-slate-600 hover:text-slate-900 px-3 py-2 hidden sm:inline">{{ __('landing.how_it_works') }}</a>
            @auth
                <a href="/dashboard" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 font-medium">{{ __('dashboard.title') }}</a>
            @else
                <a href="/login" class="text-slate-600 hover:text-slate-900 px-3 py-2">{{ __('auth.login') }}</a>
                <a href="/register" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 font-medium">{{ __('landing.get_started') }}</a>
            @endauth
        </div>
    </header>

    {{-- Hero --}}
    <section class="max-w-4xl mx-auto px-4 text-center pt-14 pb-8">
        <div class="hero-badge inline-block font-medium rounded-full px-4 py-1.5 mb-6">
            {{ __('landing.badge') }}
        </div>
        <h1 class="hero-title text-4xl md:text-5xl font-extrabold leading-[1.1] max-w-3xl mx-auto">
            {!! __('landing.hero') !!}
        </h1>
        <p class="text-lg text-slate-500 mt-5 max-w-2xl mx-auto">
            {{ __('landing.hero_sub') }}
        </p>
        <div class="flex items-center justify-center gap-3 mt-8">
            @auth
                <a href="/dashboard" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-6 py-3 font-medium">{{ __('landing.go_to_dashboard') }}</a>
            @else
                <a href="/register" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-6 py-3 font-medium">{{ __('landing.start_free') }}</a>
            @endauth
            <a href="#how" class="border border-slate-300 hover:bg-white rounded-lg px-6 py-3 font-medium">{{ __('landing.how_it_works') }}</a>
        </div>
    </section>

    {{-- A console mock: what the platform actually does, at a glance --}}
    <section class="max-w-4xl mx-auto px-4 -mt-2 mb-4">
        <div class="mock">
            <div class="mock-bar">
                <span class="mock-dot"></span><span class="mock-dot"></span><span class="mock-dot"></span>
                <span class="mock-title">{{ __('console.title') }}</span>
            </div>
            <div class="mock-body">
                <div class="mock-q">{{ __('docs.example_question') }}</div>
                @foreach ([['console.rewrite','groq'],['console.embedding','gemini'],['console.semantic','—'],['console.lexical','—'],['console.fusion','RRF'],['console.generate','claude']] as $i => [$k, $tag])
                    <div class="mock-step" style="--i:{{ $i }}">
                        <span class="mock-tick"></span>
                        <span class="mock-label">{{ __($k) }}</span>
                        <span class="mock-tag">{{ $tag }}</span>
                    </div>
                @endforeach
                <div class="mock-a">{{ __('docs.example_answer') }} <span class="mock-cite">[#1]</span></div>
            </div>
        </div>
    </section>

    {{-- Engines --}}
    <section class="max-w-5xl mx-auto px-4 py-10">
        <p class="text-center text-xs text-slate-400 mb-5" style="letter-spacing:.16em;text-transform:uppercase">
            {{ __('landing.engines') }}
        </p>
        <div class="engines">
            @foreach (['Groq','Gemini','Cerebras','OpenRouter','NVIDIA','Claude'] as $e)
                <span class="engine">{{ $e }}</span>
            @endforeach
        </div>
    </section>

    {{-- Hero data flow --}}
    <section class="max-w-5xl mx-auto px-4 py-12">
        <p class="text-center text-sm text-slate-400 mb-8">{{ __('landing.flow_caption') }}</p>
        <div class="flow">
            @foreach ([['🔌','connect'],['📥','import'],['✂️','split'],['🧠','embed'],['🔍','search'],['🤖','agent'],['💬','widget']] as $i => $s)
                <div class="flow-node" style="--d: {{ $i * 0.35 }}s"><div class="flow-dot">{{ $s[0] }}</div><span>{{ __('landing.flow.'.$s[1]) }}</span></div>
                @if (!$loop->last)<div class="flow-link"><span class="flow-packet" style="--d: {{ $i * 0.35 }}s"></span></div>@endif
            @endforeach
        </div>
    </section>

    {{-- Features --}}
    <section class="max-w-6xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach (['datasets' => '📂', 'agents' => '🤖', 'hybrid' => '🔎', 'glassbox' => '🪟', 'explorer' => '📊', 'acting' => '🛠️'] as $key => $icon)
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="text-3xl mb-3">{{ $icon }}</div>
                    <div class="font-semibold mb-1">{{ __('landing.features.'.$key.'.title') }}</div>
                    <p class="text-sm text-slate-500">{{ __('landing.features.'.$key.'.body') }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===== Deep dive: how it works ===== --}}
    <section id="how" class="bg-white border-t">
        <div class="max-w-5xl mx-auto px-4 py-16">
            <h2 class="text-2xl md:text-3xl font-bold text-center">{{ __('landing.deep_title') }}</h2>
            <p class="text-center text-slate-500 mt-2 mb-14">{{ __('landing.deep_sub') }}</p>

            {{-- 1. Upload --}}
            <div class="reveal mb-20">
                <div class="step-tag mb-2">{{ __('landing.step', ['n' => 1]) }}</div>
                <h3 class="text-xl font-bold mb-3">{{ __('landing.step1_title') }}</h3>
                <p class="text-slate-600 mb-6 max-w-3xl">
                    {!! __('landing.step1_body') !!}
                </p>
                <div class="flow mb-6">
                    @foreach ([['📤','payload'],['🧩','parse'],['📄','document'],['✂️','chunk'],['🧠','gemini'],['🗄️','pgvector']] as $i => $s)
                        <div class="flow-node" style="--d: {{ $i * 0.3 }}s"><div class="flow-dot">{{ $s[0] }}</div><span>{{ __('landing.flow.'.$s[1]) }}</span></div>
                        @if (!$loop->last)<div class="flow-link"><span class="flow-packet" style="--d: {{ $i * 0.3 }}s"></span></div>@endif
                    @endforeach
                </div>
                <pre class="bg-slate-900 text-slate-100 text-xs rounded-xl p-4 overflow-x-auto"><code>POST /v1/ingest
{
  "dataset": "{{ __('docs.example_dataset') }}",
  "records": [
    { "name": "RTX 4070", "category": "GPU", "price_gel": 2100, "url": "https://pcstore.ge/p/rtx-4070" }
  ]
}
→ 201  { "documents": 1, "chunks": 1, "status": "processing" }</code></pre>
            </div>

            {{-- 2. Agent creation --}}
            <div class="reveal mb-20">
                <div class="step-tag mb-2">{{ __('landing.step', ['n' => 2]) }}</div>
                <h3 class="text-xl font-bold mb-3">{{ __('landing.step2_title') }}</h3>
                <p class="text-slate-600 mb-6 max-w-3xl">
                    {!! __('landing.step2_body') !!}
                </p>
                <div class="flow mb-6">
                    @foreach ([['🗂️','sample'],['🤖','claude'],['⚙️','config'],['🔑','public_key'],['💬','widget']] as $i => $s)
                        <div class="flow-node" style="--d: {{ $i * 0.3 }}s"><div class="flow-dot">{{ $s[0] }}</div><span>{{ __('landing.flow.'.$s[1]) }}</span></div>
                        @if (!$loop->last)<div class="flow-link"><span class="flow-packet" style="--d: {{ $i * 0.3 }}s"></span></div>@endif
                    @endforeach
                </div>
                <pre class="bg-slate-900 text-slate-100 text-xs rounded-xl p-4 overflow-x-auto"><code>Agent = {
  dataset_id, name,
  system_prompt,            // {{ __('landing.code_behaviour') }}
  model_tier,               // fast → Haiku · standard → Sonnet · max → Opus
  enabled_tools,            // [add_item, update_item, find_items] — {{ __('landing.code_for_admin') }}
  public_key, allowed_domains
}</code></pre>
            </div>

            {{-- 3. Chat --}}
            <div class="reveal">
                <div class="step-tag mb-2">{{ __('landing.step', ['n' => 3]) }}</div>
                <h3 class="text-xl font-bold mb-3">{{ __('landing.step3_title') }}</h3>
                <p class="text-slate-600 mb-6 max-w-3xl">
                    {!! __('landing.step3_body') !!}
                </p>
                <div class="flow mb-6">
                    @foreach ([['💬','question'],['✍️','rewrite'],['🧠','embed'],['🔍','search'],['⚖️','rrf'],['🤖','claude'],['✅','answer']] as $i => $s)
                        <div class="flow-node" style="--d: {{ $i * 0.28 }}s"><div class="flow-dot">{{ $s[0] }}</div><span>{{ __('landing.flow.'.$s[1]) }}</span></div>
                        @if (!$loop->last)<div class="flow-link"><span class="flow-packet" style="--d: {{ $i * 0.28 }}s"></span></div>@endif
                    @endforeach
                </div>
                <pre class="bg-slate-900 text-slate-100 text-xs rounded-xl p-4 overflow-x-auto"><code>POST /public/chat
{ "public_key": "pk_cortexgrid_…", "message": "{{ __('docs.example_question') }}" }
→ { "answer": "{{ __('docs.example_answer') }}", "sources": [{ "ref": 1, "title": "RTX 4070" }], "message_id": 42 }</code></pre>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="max-w-3xl mx-auto px-4 py-16 text-center">
        <h2 class="text-2xl font-bold">{{ __('landing.cta_title') }}</h2>
        <p class="text-slate-500 mt-2">{{ __('landing.cta_body') }}</p>
        @auth
            <a href="/dashboard" class="inline-block mt-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-8 py-3 font-medium">{{ __('landing.go_to_dashboard') }}</a>
        @else
            <a href="/register" class="inline-block mt-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-8 py-3 font-medium">{{ __('landing.get_started') }}</a>
        @endauth
    </section>

    <footer class="text-center text-slate-400 text-xs py-8">CortexGrid AI · Technological Hackathon 2026</footer>
</div>

<style>
.flow{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:nowrap;overflow-x:auto;padding:10px 4px}
.flow-node{display:flex;flex-direction:column;align-items:center;gap:9px;flex:0 0 auto;width:80px}
.flow-node span{font-size:11px;color:var(--dim);text-align:center;letter-spacing:.02em}
.flow-dot{width:54px;height:54px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:22px;
    background:var(--panel);border:1px solid var(--line);backdrop-filter:blur(10px);
    animation:nodePulse 2.6s ease-in-out infinite;animation-delay:var(--d)}
@keyframes nodePulse{
    0%,72%,100%{border-color:var(--line);transform:scale(1);box-shadow:none}
    82%{border-color:rgba(34,211,238,.75);transform:scale(1.1);box-shadow:0 0 0 6px rgba(34,211,238,.09),0 0 26px -4px rgba(34,211,238,.6)}}
.flow-link{flex:1;height:2px;background:var(--line);margin-top:26px;position:relative;min-width:16px;border-radius:2px}
.flow-packet{position:absolute;top:-2px;left:0;width:6px;height:6px;border-radius:50%;background:var(--accent);
    box-shadow:0 0 12px var(--accent);animation:packet 2.6s ease-in-out infinite;animation-delay:var(--d)}
@keyframes packet{0%,72%{left:0;opacity:0}74%{opacity:1}100%{left:100%;opacity:0}}
.reveal{opacity:0;transform:translateY(24px);transition:opacity .7s ease,transform .7s ease}
@media (prefers-reduced-motion:reduce){.reveal{opacity:1;transform:none}}
.reveal.show{opacity:1;transform:none}

/* Hero: gradient wordline + a scanning sweep across the headline */
.hero-title{background:linear-gradient(96deg,var(--text) 18%,var(--accent) 52%,var(--accent-2) 88%);
    -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.hero-badge{border:1px solid rgba(34,211,238,.35);background:rgba(34,211,238,.08);color:var(--accent);
    backdrop-filter:blur(10px);text-transform:uppercase;letter-spacing:.16em;font-size:10.5px}
.hero-badge::before{content:'';display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--accent);
    margin-right:8px;vertical-align:middle;box-shadow:0 0 10px var(--accent);animation:blip 1.8s ease-in-out infinite}
@keyframes blip{0%,100%{opacity:1}50%{opacity:.25}}
.step-tag{font-family:'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.18em;font-size:10px;
    color:var(--accent)}
.step-tag::before{content:'// '}

/* console mock */
.mock{border:1px solid var(--line);border-radius:16px;overflow:hidden;background:var(--surface);
    box-shadow:0 40px 90px -40px rgba(34,211,238,.35),0 0 0 1px rgba(34,211,238,.08)}
.mock-bar{display:flex;align-items:center;gap:7px;padding:11px 14px;border-bottom:1px solid var(--line);
    background:rgba(255,255,255,.02)}
.mock-dot{width:9px;height:9px;border-radius:50%;background:var(--line)}
.mock-dot:first-child{background:rgba(251,113,133,.6)}
.mock-dot:nth-child(2){background:rgba(251,191,36,.6)}
.mock-dot:nth-child(3){background:rgba(52,211,153,.6)}
.mock-title{margin-inline-start:8px;font-size:11.5px;color:var(--dim);font-family:'JetBrains Mono',monospace}
.mock-body{padding:16px 18px;display:flex;flex-direction:column;gap:7px}
.mock-q{font-size:13.5px;color:var(--text);padding-bottom:6px}
.mock-q::before{content:'> ';color:var(--accent);font-family:'JetBrains Mono',monospace}
.mock-step{display:flex;align-items:center;gap:10px;font-size:12px;color:var(--muted);
    opacity:0;animation:mockIn .5s ease forwards;animation-delay:calc(var(--i) * .16s + .2s)}
.mock-tick{width:6px;height:6px;border-radius:50%;background:var(--accent);box-shadow:0 0 8px var(--accent);flex:0 0 auto}
.mock-label{flex:1}
.mock-tag{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--dim);
    border:1px solid var(--line);border-radius:5px;padding:1px 6px}
.mock-a{margin-top:8px;padding-top:12px;border-top:1px solid var(--line);font-size:13.5px;color:var(--text);
    opacity:0;animation:mockIn .5s ease forwards;animation-delay:1.3s}
.mock-cite{color:var(--accent);font-family:'JetBrains Mono',monospace;font-size:11px}
@keyframes mockIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}

/* engines */
.engines{display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
.engine{font-family:'JetBrains Mono',monospace;font-size:12.5px;color:var(--muted);
    border:1px solid var(--line);border-radius:999px;padding:7px 16px;background:rgba(255,255,255,.02);
    transition:all .18s ease}
.engine:hover{color:var(--accent);border-color:rgba(34,211,238,.45);box-shadow:0 0 20px -6px rgba(34,211,238,.6)}
</style>
<script>
(function () {
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('show'); io.unobserve(e.target); } });
    }, { threshold: 0.15 });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
})();
</script>
@endsection
