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
    <section class="max-w-4xl mx-auto px-4 text-center pt-16 pb-10">
        <div class="inline-block text-xs font-medium bg-indigo-100 text-indigo-700 rounded-full px-3 py-1 mb-5">
            {{ __('landing.badge') }}
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight">
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
                <div class="text-xs font-semibold text-indigo-600 mb-1">{{ __('landing.step', ['n' => 1]) }}</div>
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
                <div class="text-xs font-semibold text-indigo-600 mb-1">{{ __('landing.step', ['n' => 2]) }}</div>
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
                <div class="text-xs font-semibold text-indigo-600 mb-1">{{ __('landing.step', ['n' => 3]) }}</div>
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
.flow{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:nowrap;overflow-x:auto;padding:8px 4px}
.flow-node{display:flex;flex-direction:column;align-items:center;gap:8px;flex:0 0 auto;width:78px}
.flow-node span{font-size:11px;color:#64748b;text-align:center}
.flow-dot{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;
    background:#fff;border:2px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,.04);animation:nodePulse 2.6s ease-in-out infinite;animation-delay:var(--d)}
@keyframes nodePulse{0%,72%,100%{border-color:#e2e8f0;transform:scale(1)}82%{border-color:#6366f1;transform:scale(1.12);box-shadow:0 0 0 8px rgba(99,102,241,.12)}}
.flow-link{flex:1;height:2px;background:#e2e8f0;margin-top:25px;position:relative;min-width:16px;border-radius:2px}
.flow-packet{position:absolute;top:-2px;left:0;width:6px;height:6px;border-radius:50%;background:#6366f1;
    box-shadow:0 0 8px #6366f1;animation:packet 2.6s ease-in-out infinite;animation-delay:var(--d)}
@keyframes packet{0%,72%{left:0;opacity:0}74%{opacity:1}100%{left:100%;opacity:0}}
.reveal{opacity:0;transform:translateY(22px);transition:opacity .6s ease,transform .6s ease}
.reveal.show{opacity:1;transform:none}
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
