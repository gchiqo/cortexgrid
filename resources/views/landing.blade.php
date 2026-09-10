@extends('layout')
@section('title', __('landing.title'))
@section('body')
<div class="scroll-rail"><i id="scrollBar"></i></div>
<canvas id="net" class="net-canvas" aria-hidden="true"></canvas>
<div class="min-h-screen">
    <header class="nav" id="nav">
        <div class="nav-in">
            <a href="/" class="brand">
                <span class="brand-mark"><i></i><i></i><i></i></span>
                <span class="brand-word">Cortex<b>Grid</b></span>
            </a>

            <nav class="links">
                <a href="#how">{{ __('landing.how_it_works') }}</a>
                <a href="#engines">{{ __('landing.engines') }}</a>
            </nav>

            <div class="acts">
                @include('partials.lang-toggle')
                @include('partials.theme-toggle')
                @auth
                    <a href="/dashboard" class="cta">{{ __('dashboard.title') }}<span class="cta-arrow">→</span></a>
                @else
                    <a href="/login" class="ghost">{{ __('auth.login') }}</a>
                    <a href="/register" class="cta">{{ __('landing.get_started') }}<span class="cta-arrow">→</span></a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="max-w-4xl mx-auto px-4 text-center pt-32 pb-8">
        <div class="hero-badge inline-block font-medium rounded-full px-4 py-1.5 mb-6">
            {{ __('landing.badge') }}
        </div>
        <h1 class="hero-title text-4xl md:text-5xl font-extrabold leading-[1.1] max-w-3xl mx-auto">
            {!! __('landing.hero') !!}
        </h1>
        <p class="text-lg text-slate-500 mt-5 max-w-2xl mx-auto par" data-par="0.06">
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
                <div class="mock-q"><span id="mockQ"></span><i class="caret"></i></div>
                <div id="mockSteps">
                @foreach ([['console.rewrite','groq'],['console.embedding','gemini'],['console.semantic','pgvector'],['console.lexical','BM25'],['console.fusion','RRF'],['console.generate','claude']] as $i => [$k, $tag])
                    <div class="mock-step" data-s="{{ $i }}">
                        <span class="mock-tick"></span>
                        <span class="mock-label">{{ __($k) }}</span>
                        <span class="mock-ms"></span>
                        <span class="mock-tag">{{ $tag }}</span>
                    </div>
                @endforeach
                </div>
                <div class="mock-a"><span id="mockA"></span></div>
            </div>
        </div>
    </section>

    {{-- Engines --}}
    <section id="engines" class="max-w-5xl mx-auto px-4 py-10 scroll-mt-24">
        <p class="text-center text-xs text-slate-400 mb-5" style="letter-spacing:.16em;text-transform:uppercase">
            {{ __('landing.engines') }}
        </p>
        <div class="marquee">
            <div class="marquee-run">
                @for ($pass = 0; $pass < 2; $pass++)
                    @foreach (['Groq','Gemini','Cerebras','OpenRouter','NVIDIA','Claude','pgvector','BM25','RRF'] as $e)
                        <span class="engine">{{ $e }}</span>
                    @endforeach
                @endfor
            </div>
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 stagger reveal">
            @foreach (['datasets' => '📂', 'agents' => '🤖', 'hybrid' => '🔎', 'glassbox' => '🪟', 'explorer' => '📊', 'acting' => '🛠️'] as $key => $icon)
                <div class="bg-white rounded-2xl shadow-sm p-6 tilt">
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
html.js .reveal.show{opacity:1;transform:none}

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

/* ---- ambient neural canvas ---- */
.net-canvas{position:fixed;inset:0;width:100%;height:100%;z-index:0;pointer-events:none;opacity:.9}
/* No square mesh on the marketing page — the nerve field is the texture. */
body::before{display:none}
/* ---- scroll progress ---- */
.scroll-rail{position:fixed;top:0;left:0;right:0;height:2px;z-index:70;background:transparent}
.scroll-rail i{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--accent),var(--accent-2));
    box-shadow:0 0 12px var(--accent)}
/* ---- staggered, directional reveals ---- */
html.js .reveal{opacity:0;transform:translateY(26px);transition:opacity .75s cubic-bezier(.2,.7,.2,1),transform .75s cubic-bezier(.2,.7,.2,1)}
html.js .reveal.show{opacity:1;transform:none}
html.js .stagger > *{opacity:0;transform:translateY(20px);
    transition:opacity .6s cubic-bezier(.2,.7,.2,1),transform .6s cubic-bezier(.2,.7,.2,1)}
html.js .stagger.show > *{opacity:1;transform:none}
.stagger.show > *:nth-child(1){transition-delay:.05s}
.stagger.show > *:nth-child(2){transition-delay:.14s}
.stagger.show > *:nth-child(3){transition-delay:.23s}
.stagger.show > *:nth-child(4){transition-delay:.32s}
.stagger.show > *:nth-child(5){transition-delay:.41s}
.stagger.show > *:nth-child(6){transition-delay:.5s}
/* ---- card tilt + cursor spotlight ---- */
.tilt{position:relative;transform-style:preserve-3d;transition:transform .25s ease,box-shadow .25s ease}
.tilt::after{content:'';position:absolute;inset:0;border-radius:inherit;pointer-events:none;opacity:0;
    transition:opacity .25s ease;
    background:radial-gradient(18rem 18rem at var(--mx,50%) var(--my,50%),rgba(34,211,238,.16),transparent 60%)}
.tilt:hover::after{opacity:1}
/* ---- typing caret ---- */
.caret{display:inline-block;width:7px;height:15px;background:var(--accent);margin-inline-start:3px;
    vertical-align:-2px;animation:caret 1s steps(2) infinite;box-shadow:0 0 9px var(--accent)}
@keyframes caret{0%,100%{opacity:1}50%{opacity:0}}
/* ---- pipeline states ---- */
.mock-step{display:flex;align-items:center;gap:10px;font-size:12px;color:var(--dim);
    opacity:.3;transition:opacity .3s ease,color .3s ease;animation:none}
.mock-step.run{opacity:1;color:var(--text)}
.mock-step.done{opacity:.85;color:var(--muted)}
.mock-step .mock-tick{background:var(--line);box-shadow:none;transition:all .3s ease}
.mock-step.run .mock-tick{background:var(--accent);box-shadow:0 0 10px var(--accent);
    animation:tickPulse .9s ease-in-out infinite}
.mock-step.done .mock-tick{background:var(--accent-3);box-shadow:0 0 8px var(--accent-3)}
@keyframes tickPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.5)}}
.mock-ms{margin-inline-start:auto;font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--dim);opacity:0;
    transition:opacity .3s ease}
.mock-step.done .mock-ms{opacity:1}
.mock-a{min-height:22px;opacity:1;animation:none}
/* ---- marquee ---- */
.marquee{overflow:hidden;mask-image:linear-gradient(90deg,transparent,#000 12%,#000 88%,transparent);
    -webkit-mask-image:linear-gradient(90deg,transparent,#000 12%,#000 88%,transparent)}
.marquee-run{display:flex;gap:10px;width:max-content;animation:slide 34s linear infinite}
.marquee:hover .marquee-run{animation-play-state:paused}
@keyframes slide{from{transform:translateX(0)}to{transform:translateX(-50%)}}
/* ---- hero parallax ---- */
.par{will-change:transform}
@media (prefers-reduced-motion:reduce){
    .net-canvas{display:none}
    .marquee-run{animation:none}
    .reveal,.stagger > *{opacity:1!important;transform:none!important}
}

/* The landing page runs a deeper ground than the app panel: more contrast
   behind the canvas and the hero. Dark only — light has its own identity. */
html:not(.light){
    --bg:            #080d16;
    --bg-2:          #0d1420;
    --panel:         rgba(24, 34, 52, .72);
    --line:          rgba(150, 196, 245, .15);
    --line-soft:     rgba(150, 196, 245, .08);
    --bar:           rgba(13, 20, 33, .72);
    --surface:       rgba(23, 33, 51, .68);
    --surface-solid: #16202f;
    --code-bg:       rgba(6, 11, 20, .92);
    --input-bg:      rgba(13, 20, 33, .8);
}


/* ---- header: nothing at rest, a thin bar once you move ---- */
.nav{position:fixed;top:0;left:0;right:0;z-index:60;transition:transform .34s cubic-bezier(.2,.7,.2,1)}
.nav::after{content:'';position:absolute;inset:0;z-index:-1;opacity:0;
    background:var(--nav-bg);backdrop-filter:blur(16px) saturate(1.35);-webkit-backdrop-filter:blur(16px) saturate(1.35);
    border-bottom:1px solid var(--line);transition:opacity .3s ease}
.nav.stuck::after{opacity:1}
.nav.up{transform:translateY(-100%)}
.nav-in{max-width:1180px;margin:0 auto;padding:20px 26px;display:flex;align-items:center;gap:26px;
    transition:padding .3s ease}
.nav.stuck .nav-in{padding:12px 26px}

.brand{display:flex;align-items:center;gap:10px;text-decoration:none;font-size:15px;font-weight:600;
    color:var(--text);letter-spacing:-.025em;margin-inline-end:auto}
.brand b{font-weight:600;color:var(--accent)}
/* three stacked bars that lean like a signal rising */
.brand-mark{display:flex;align-items:flex-end;gap:2.5px;height:19px}
.brand-mark i{width:3.5px;border-radius:2px;background:linear-gradient(180deg,var(--accent),var(--accent-2));
    box-shadow:0 0 9px rgba(34,211,238,.5)}
.brand-mark i:nth-child(1){height:8px;animation:bar 2.4s ease-in-out infinite}
.brand-mark i:nth-child(2){height:14px;animation:bar 2.4s ease-in-out .28s infinite}
.brand-mark i:nth-child(3){height:19px;animation:bar 2.4s ease-in-out .56s infinite}
@keyframes bar{0%,100%{transform:scaleY(1)}50%{transform:scaleY(.55)}}

.links{display:flex;align-items:center;gap:4px}
.links a{position:relative;font-size:13.5px;color:var(--muted);text-decoration:none;padding:8px 12px;
    transition:color .16s ease}
.links a::after{content:'';position:absolute;left:12px;right:12px;bottom:3px;height:1px;
    background:linear-gradient(90deg,var(--accent),var(--accent-2));transform:scaleX(0);transform-origin:left;
    transition:transform .24s cubic-bezier(.2,.7,.2,1)}
.links a:hover{color:var(--text)}
.links a:hover::after{transform:scaleX(1)}

.acts{display:flex;align-items:center;gap:8px}
.ghost{font-size:13.5px;color:var(--muted);text-decoration:none;padding:8px 12px;border-radius:9px;
    transition:all .16s ease}
.ghost:hover{color:var(--text);background:var(--hover)}
.cta{display:inline-flex;align-items:center;gap:7px;font-size:13.5px;font-weight:600;text-decoration:none;
    padding:9px 17px;border-radius:10px;color:var(--on-accent);
    background:linear-gradient(135deg,var(--accent),var(--accent-2));
    box-shadow:var(--cta-shadow);transition:box-shadow .2s ease,transform .2s ease}
.cta:hover{transform:translateY(-1px);box-shadow:var(--cta-shadow-hover)}
.cta-arrow{transition:transform .2s ease}
.cta:hover .cta-arrow{transform:translateX(3px)}
@media (max-width:760px){
    .links{display:none}
    .brand-word{display:none}
    .nav-in{padding:14px 18px;gap:12px}
}
}
</style>
@php($__demoQ = (array) __('landing.demo_questions'))
@php($__demoA = __('docs.example_answer'))
<script>
(function () {
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- reveal on scroll (with stagger groups) ---------- */
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('show'); io.unobserve(e.target); }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    document.querySelectorAll('.reveal, .stagger').forEach(function (el) { io.observe(el); });
    // Anchor links land mid-page; show whatever is already in view immediately.
    function showVisible() {
        document.querySelectorAll('.reveal:not(.show), .stagger:not(.show)').forEach(function (el) {
            var r = el.getBoundingClientRect();
            if (r.top < window.innerHeight && r.bottom > 0) el.classList.add('show');
        });
    }
    showVisible();
    window.addEventListener('load', showVisible);
    // Last-resort safety net: never leave content permanently invisible.
    setTimeout(function () {
        document.querySelectorAll('.reveal, .stagger').forEach(function (el) { el.classList.add('show'); });
    }, 4000);

    /* ---------- scroll progress + hero parallax ---------- */
    var bar = document.getElementById('scrollBar');
    var pars = [].slice.call(document.querySelectorAll('.par'));
    var ticking = false;
    function onScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(function () {
            var y = window.scrollY || 0;
            var max = (document.documentElement.scrollHeight - window.innerHeight) || 1;
            if (bar) bar.style.width = Math.min(100, (y / max) * 100) + '%';
            if (!reduced) {
                pars.forEach(function (el) {
                    var k = parseFloat(el.dataset.par || '0.05');
                    el.style.transform = 'translate3d(0,' + (y * k) + 'px,0)';
                });
            }
            ticking = false;
        });
    }
    // The bar gets out of the way going down, and comes back on the way up.
    var navEl = document.getElementById('nav'), lastY = 0;
    function navScroll() {
        var y = window.scrollY || 0;
        navEl.classList.toggle('small', y > 20);
        navEl.classList.toggle('up', y > 220 && y > lastY);
        lastY = y;
    }
    window.addEventListener('scroll', navScroll, { passive: true });
    navScroll();

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* ---------- cursor spotlight + tilt on cards ---------- */
    if (!reduced) {
        document.querySelectorAll('.tilt').forEach(function (card) {
            card.addEventListener('mousemove', function (e) {
                var r = card.getBoundingClientRect();
                var px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
                card.style.setProperty('--mx', (px * 100) + '%');
                card.style.setProperty('--my', (py * 100) + '%');
                card.style.transform = 'perspective(760px) rotateX(' + ((0.5 - py) * 5).toFixed(2) +
                                       'deg) rotateY(' + ((px - 0.5) * 6).toFixed(2) + 'deg) translateY(-2px)';
            });
            card.addEventListener('mouseleave', function () { card.style.transform = ''; });
        });
    }

    /* ---------- ambient nerve field ----------
       Neurons with branching dendrites, wired by axons that carry signals.
       The dendrite geometry is generated once and cached to an offscreen
       canvas; only the signals and the firing glow are redrawn per frame. */
    var cv = document.getElementById('net');
    if (cv && !reduced) {
        var ctx = cv.getContext('2d'),
            dpr = Math.min(devicePixelRatio || 1, 2),
            w = 0, h = 0,
            neurons = [], axons = [], signals = [],
            still = document.createElement('canvas'), sctx = still.getContext('2d'),
            mouse = { x: -9999, y: -9999 };

        function rnd(a, b) { return a + Math.random() * (b - a); }
        function light() { return document.documentElement.classList.contains('light'); }
        // Ink on paper in light, bioluminescence on dark.
        function ink() {
            return light()
                ? { axon: 'rgba(60,96,140,.20)', dend: '60,96,140', dendA: .10, dendB: .13,
                    soma: 'rgba(52,88,132,.42)', hot: 'rgba(11,139,168,.95)', pulse: '11,139,168' }
                : { axon: 'rgba(134,186,238,.17)', dend: '152,198,244', dendA: .075, dendB: .10,
                    soma: 'rgba(150,196,240,.5)', hot: 'rgba(120,235,250,.95)', pulse: '34,211,238' };
        }

        // One dendrite: a tapering branch that forks as it goes.
        function grow(x, y, angle, len, width, depth, out) {
            var sway = rnd(-0.42, 0.42);
            var cx = x + Math.cos(angle + sway * 0.5) * len * 0.55,
                cy = y + Math.sin(angle + sway * 0.5) * len * 0.55,
                ex = x + Math.cos(angle + sway) * len,
                ey = y + Math.sin(angle + sway) * len;
            out.push({ x: x, y: y, cx: cx, cy: cy, ex: ex, ey: ey, w: width });
            if (depth <= 0) return;
            var forks = depth > 1 ? 2 : (Math.random() < 0.6 ? 2 : 1);
            for (var i = 0; i < forks; i++) {
                grow(ex, ey, angle + sway + rnd(-0.72, 0.72), len * rnd(0.55, 0.74),
                     Math.max(0.35, width * 0.62), depth - 1, out);
            }
        }

        function build() {
            neurons = []; axons = []; signals = [];
            var count = Math.max(9, Math.round(Math.min(22, (w * h) / 88000)));
            for (var i = 0; i < count; i++) {
                var n = { x: rnd(w * 0.04, w * 0.96), y: rnd(h * 0.05, h * 0.95),
                          r: rnd(2.4, 4.2), fire: 0, seg: [],
                          drift: rnd(0, 6.28), speed: rnd(0.0016, 0.0042) };
                var arms = Math.round(rnd(4, 7));
                for (var a = 0; a < arms; a++) {
                    grow(n.x, n.y, (a / arms) * 6.283 + rnd(-0.35, 0.35),
                         rnd(52, 118), rnd(1.2, 1.9), 2, n.seg);
                }
                neurons.push(n);
            }
            // Wire close pairs together with a bowed axon.
            for (var i = 0; i < neurons.length; i++) {
                for (var j = i + 1; j < neurons.length; j++) {
                    var A = neurons[i], B = neurons[j],
                        d = Math.hypot(A.x - B.x, A.y - B.y);
                    if (d < Math.min(w, h) * 0.42 && Math.random() < 0.55) {
                        var mx = (A.x + B.x) / 2, my = (A.y + B.y) / 2,
                            nx = -(B.y - A.y) / d, ny = (B.x - A.x) / d,
                            bow = rnd(-0.16, 0.16) * d;
                        axons.push({ a: i, b: j, cx: mx + nx * bow, cy: my + ny * bow });
                    }
                }
            }
        }

        function paintStill() {
            still.width = w * dpr; still.height = h * dpr;
            sctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            sctx.clearRect(0, 0, w, h);
            sctx.lineCap = 'round';

            var C = ink();
            axons.forEach(function (ax) {
                var A = neurons[ax.a], B = neurons[ax.b];
                sctx.strokeStyle = C.axon;
                sctx.lineWidth = 0.9;
                sctx.beginPath();
                sctx.moveTo(A.x, A.y);
                sctx.quadraticCurveTo(ax.cx, ax.cy, B.x, B.y);
                sctx.stroke();
            });

            neurons.forEach(function (n) {
                n.seg.forEach(function (g) {
                    sctx.strokeStyle = 'rgba(' + C.dend + ',' + (C.dendA + g.w * C.dendB) + ')';
                    sctx.lineWidth = g.w;
                    sctx.beginPath();
                    sctx.moveTo(g.x, g.y);
                    sctx.quadraticCurveTo(g.cx, g.cy, g.ex, g.ey);
                    sctx.stroke();
                });
            });
        }

        function resize() {
            w = cv.clientWidth; h = cv.clientHeight;
            cv.width = w * dpr; cv.height = h * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            build(); paintStill();
        }
        var rt; window.addEventListener('resize', function () {
            clearTimeout(rt); rt = setTimeout(resize, 220);
        });
        window.addEventListener('mousemove', function (e) { mouse.x = e.clientX; mouse.y = e.clientY; });
        window.addEventListener('mouseleave', function () { mouse.x = mouse.y = -9999; });
        resize();

        function pointOn(ax, t) {
            var A = neurons[ax.a], B = neurons[ax.b], u = 1 - t;
            return { x: u * u * A.x + 2 * u * t * ax.cx + t * t * B.x,
                     y: u * u * A.y + 2 * u * t * ax.cy + t * t * B.y };
        }

        function emit(from) {
            var options = [];
            axons.forEach(function (ax, i) {
                if (ax.a === from) options.push({ i: i, dir: 1 });
                if (ax.b === from) options.push({ i: i, dir: -1 });
            });
            if (!options.length) return;
            var pick = options[(Math.random() * options.length) | 0];
            signals.push({ ax: pick.i, dir: pick.dir, t: pick.dir === 1 ? 0 : 1,
                           speed: rnd(0.005, 0.011) });
        }

        var tick = 0, C2 = ink();
        // Repaint the cached structure when the theme flips.
        new MutationObserver(function () { C2 = ink(); paintStill(); })
            .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        (function frame() {
            ctx.clearRect(0, 0, w, h);
            ctx.drawImage(still, 0, 0, w, h);
            tick++;

            // Idle firing, plus a burst wherever the pointer is.
            if (tick % 26 === 0 && neurons.length) emit((Math.random() * neurons.length) | 0);

            neurons.forEach(function (n, i) {
                var md = Math.hypot(mouse.x - n.x, mouse.y - n.y), near = md < 190;
                if (near && Math.random() < 0.045) { n.fire = 1; emit(i); }
                if (n.fire > 0) n.fire = Math.max(0, n.fire - 0.018);

                // soma, with a halo while it fires or the pointer is close
                var glow = Math.max(n.fire, near ? (1 - md / 190) * 0.75 : 0);
                if (glow > 0.01) {
                    var g = ctx.createRadialGradient(n.x, n.y, 0, n.x, n.y, 34 + glow * 30);
                    g.addColorStop(0, 'rgba(' + C2.pulse + ',' + (0.26 * glow) + ')');
                    g.addColorStop(1, 'rgba(' + C2.pulse + ',0)');
                    ctx.fillStyle = g;
                    ctx.beginPath(); ctx.arc(n.x, n.y, 34 + glow * 30, 0, 6.283); ctx.fill();
                }
                ctx.fillStyle = glow > 0.05 ? C2.hot : C2.soma;
                ctx.beginPath(); ctx.arc(n.x, n.y, n.r + glow * 1.6, 0, 6.283); ctx.fill();
            });

            // signals travelling the axons
            for (var i = signals.length - 1; i >= 0; i--) {
                var sg = signals[i], ax = axons[sg.ax];
                sg.t += sg.speed * sg.dir;
                if (sg.t <= 0 || sg.t >= 1) {
                    neurons[sg.dir === 1 ? ax.b : ax.a].fire = 1;
                    signals.splice(i, 1);
                    continue;
                }
                var pt = pointOn(ax, sg.t),
                    tail = pointOn(ax, Math.max(0, Math.min(1, sg.t - 0.055 * sg.dir)));
                var lg = ctx.createLinearGradient(tail.x, tail.y, pt.x, pt.y);
                lg.addColorStop(0, 'rgba(' + C2.pulse + ',0)');
                lg.addColorStop(1, C2.hot);
                ctx.strokeStyle = lg; ctx.lineWidth = 1.7; ctx.lineCap = 'round';
                ctx.beginPath(); ctx.moveTo(tail.x, tail.y); ctx.lineTo(pt.x, pt.y); ctx.stroke();

                ctx.fillStyle = C2.hot;
                ctx.beginPath(); ctx.arc(pt.x, pt.y, 1.9, 0, 6.283); ctx.fill();
            }

            requestAnimationFrame(frame);
        })();
    }

    /* ---------- the console mock runs the real pipeline, on a loop ---------- */
    var qEl = document.getElementById('mockQ'), aEl = document.getElementById('mockA'),
        steps = [].slice.call(document.querySelectorAll('#mockSteps .mock-step'));
    if (qEl && aEl && steps.length) {
        var questions = @json($__demoQ), answer = @json($__demoA), qi = 0;
        var sleep = function (ms) { return new Promise(function (r) { setTimeout(r, ms); }); };

        function type(el, text, speed) {
            return new Promise(function (done) {
                el.textContent = ''; var i = 0;
                (function tick() {
                    if (i >= text.length) return done();
                    el.textContent += text.charAt(i++);
                    setTimeout(tick, speed);
                })();
            });
        }

        async function run() {
            while (true) {
                steps.forEach(function (s) { s.className = 'mock-step'; s.querySelector('.mock-ms').textContent = ''; });
                aEl.textContent = '';
                await type(qEl, questions[qi % questions.length], 42);
                await sleep(320);
                for (var i = 0; i < steps.length; i++) {
                    steps[i].classList.add('run');
                    var ms = 120 + Math.round(Math.random() * 380);
                    await sleep(reduced ? 60 : ms);
                    steps[i].classList.remove('run');
                    steps[i].classList.add('done');
                    steps[i].querySelector('.mock-ms').textContent = ms + 'ms';
                }
                await sleep(200);
                await type(aEl, answer + '  [#1]', 20);
                qi++;
                await sleep(2600);
            }
        }
        run();
    }
})();
</script>
@endsection
