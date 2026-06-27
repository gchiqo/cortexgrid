@extends('layout')
@section('title', 'GTUH AI — ცოდნის პლატფორმა')
@section('body')
<div class="min-h-screen bg-gradient-to-b from-slate-50 to-indigo-50/40">
    <header class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
        <a href="/" class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span></a>
        <div class="flex items-center gap-3 text-sm">
            @include('partials.theme-toggle')
            <a href="#how" class="text-slate-600 hover:text-slate-900 px-3 py-2 hidden sm:inline">როგორ მუშაობს</a>
            @auth
                <a href="/dashboard" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 font-medium">პანელი</a>
            @else
                <a href="/login" class="text-slate-600 hover:text-slate-900 px-3 py-2">შესვლა</a>
                <a href="/register" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 font-medium">დაწყება</a>
            @endauth
        </div>
    </header>

    {{-- Hero --}}
    <section class="max-w-4xl mx-auto px-4 text-center pt-16 pb-10">
        <div class="inline-block text-xs font-medium bg-indigo-100 text-indigo-700 rounded-full px-3 py-1 mb-5">
            ცოდნის AI პლატფორმა — არა უბრალოდ ჩატბოტი
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight">
            შენი მონაცემები →<br><span class="text-indigo-600">ინტელექტუალური AI აგენტები</span>
        </h1>
        <p class="text-lg text-slate-500 mt-5 max-w-2xl mx-auto">
            დააკავშირე ფაილები და მონაცემები, პლატფორმა გაიგებს მათ, და მიიღე ქართულენოვანი AI აგენტები,
            რომლებსაც შენს საიტზე ერთი კოდით ჩასვამ.
        </p>
        <div class="flex items-center justify-center gap-3 mt-8">
            @auth
                <a href="/dashboard" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-6 py-3 font-medium">პანელზე გადასვლა</a>
            @else
                <a href="/register" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-6 py-3 font-medium">უფასოდ დაწყება</a>
            @endauth
            <a href="#how" class="border border-slate-300 hover:bg-white rounded-lg px-6 py-3 font-medium">როგორ მუშაობს</a>
        </div>
    </section>

    {{-- Hero data flow --}}
    <section class="max-w-5xl mx-auto px-4 py-12">
        <p class="text-center text-sm text-slate-400 mb-8">როგორ მუშავდება მონაცემები</p>
        <div class="flow">
            @foreach ([['🔌','დაკავშირება'],['📥','იმპორტი'],['✂️','დაყოფა'],['🧠','ემბედინგი'],['🔍','ჰიბრიდული ძებნა'],['🤖','AI აგენტი'],['💬','ვიჯეტი']] as $i => $s)
                <div class="flow-node" style="--d: {{ $i * 0.35 }}s"><div class="flow-dot">{{ $s[0] }}</div><span>{{ $s[1] }}</span></div>
                @if (!$loop->last)<div class="flow-link"><span class="flow-packet" style="--d: {{ $i * 0.35 }}s"></span></div>@endif
            @endforeach
        </div>
    </section>

    {{-- Features --}}
    <section class="max-w-6xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach ([
                ['📂','დატასეტები','ერთი ბიზნესი = ერთი დატასეტი, შევსებული მრავალი ფაილით (PDF, CSV, XLSX) ან API-ით.'],
                ['🤖','მრავალი AI აგენტი','თითო დატასეტზე რამდენიმე აგენტი — მომხმარებლისთვის და ადმინისთვის, განსხვავებული ქცევით.'],
                ['🔎','ჰიბრიდული ძებნა','ვექტორული (pgvector) + ლექსიკური (BM25) ძებნა, შერწყმული RRF-ით — ზუსტი, ციტირებული პასუხები.'],
                ['🪟','Glass-box კონსოლი','რეალურ დროში ნახე როგორ ფიქრობს სისტემა — embedding, ძებნა, შერწყმა, Claude.'],
                ['📊','ცოდნის მკვლევარი','პლატფორმა აანალიზებს მონაცემებს — ბრენდები, კატეგორიები, კავშირები, ნაკლული ინფო.'],
                ['🛠️','მოქმედი აგენტები','ადმინ-აგენტი ჩატით ამატებს და აახლებს მონაცემებს — მაშინვე ძებნადს.'],
            ] as $f)
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="text-3xl mb-3">{{ $f[0] }}</div>
                    <div class="font-semibold mb-1">{{ $f[1] }}</div>
                    <p class="text-sm text-slate-500">{{ $f[2] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===== Deep dive: how it works ===== --}}
    <section id="how" class="bg-white border-t">
        <div class="max-w-5xl mx-auto px-4 py-16">
            <h2 class="text-2xl md:text-3xl font-bold text-center">როგორ მუშაობს — ღრმად</h2>
            <p class="text-center text-slate-500 mt-2 mb-14">ნაბიჯ-ნაბიჯ: ფაილიდან პასუხამდე — პროგრამულად.</p>

            {{-- 1. Upload --}}
            <div class="reveal mb-20">
                <div class="text-xs font-semibold text-indigo-600 mb-1">ნაბიჯი 1</div>
                <h3 class="text-xl font-bold mb-3">ფაილის ატვირთვა → მონაცემები (payload)</h3>
                <p class="text-slate-600 mb-6 max-w-3xl">
                    ფაილი იგზავნება <code>POST /dashboard/upload</code>-ით (ან API-ით <code>POST /v1/ingest</code>). სერვერი ცნობს ტიპს და <b>აპარსავს</b>:
                    PDF → ტექსტი Gemini-ის მულტიმოდალით; CSV/XLSX → თითო სტრიქონი ცალკე ჩანაწერად; TXT → ერთი ჩანაწერი.
                    შემდეგ <b>IngestService</b> ქმნის <b>Source → Document</b>-ებს (ველები ინახება <code>structured</code> jsonb-ში) და <b>Chunker</b> ანაწევრებს ტექსტს ~1200-სიმბოლოიან <b>ჩანკებად</b>.
                    Postgres ავტომატურად აშენებს <code>tsvector</code>-ს (BM25-ისთვის), ხოლო ფონური <b>EmbedChunks</b> job აგზავნის ჩანკებს <b>Gemini</b>-ში (768-განზომილებიანი ვექტორი) და ინახავს <b>pgvector</b>-ში.
                </p>
                <div class="flow mb-6">
                    @foreach ([['📤','payload'],['🧩','პარსინგი'],['📄','Document'],['✂️','ჩანკი'],['🧠','Gemini'],['🗄️','pgvector']] as $i => $s)
                        <div class="flow-node" style="--d: {{ $i * 0.3 }}s"><div class="flow-dot">{{ $s[0] }}</div><span>{{ $s[1] }}</span></div>
                        @if (!$loop->last)<div class="flow-link"><span class="flow-packet" style="--d: {{ $i * 0.3 }}s"></span></div>@endif
                    @endforeach
                </div>
                <pre class="bg-slate-900 text-slate-100 text-xs rounded-xl p-4 overflow-x-auto"><code>POST /v1/ingest
{
  "dataset": "კომპიუტერული მაღაზია",
  "records": [
    { "name": "RTX 4070", "category": "GPU", "price_gel": 2100, "url": "https://pcstore.ge/p/rtx-4070" }
  ]
}
→ 201  { "documents": 1, "chunks": 1, "status": "processing" }</code></pre>
            </div>

            {{-- 2. Agent creation --}}
            <div class="reveal mb-20">
                <div class="text-xs font-semibold text-indigo-600 mb-1">ნაბიჯი 2</div>
                <h3 class="text-xl font-bold mb-3">AI აგენტის შექმნა</h3>
                <p class="text-slate-600 mb-6 max-w-3xl">
                    აგენტი არის <b>კონფიგურაცია, მიბმული დატასეტზე</b>. შეგიძლია ხელით შექმნა, ან დააჭირო <b>„✨ გენერაცია მონაცემებიდან“</b> — მაშინ
                    სისტემა იღებს მონაცემთა ნიმუშს, აგზავნის <b>Claude</b>-თან და იღებს მზა აგენტებს (სახელი + ქართული system prompt + მოდელის დონე).
                    ყოველი აგენტი იღებს <b>public key</b>-ს, რომლითაც მისი ვიჯეტი საიტზე ჩაისმება. აგენტი ეძებს <b>მხოლოდ თავის დატასეტში</b>.
                </p>
                <div class="flow mb-6">
                    @foreach ([['🗂️','მონაცემთა ნიმუში'],['🤖','Claude'],['⚙️','კონფიგი'],['🔑','public key'],['💬','ვიჯეტი']] as $i => $s)
                        <div class="flow-node" style="--d: {{ $i * 0.3 }}s"><div class="flow-dot">{{ $s[0] }}</div><span>{{ $s[1] }}</span></div>
                        @if (!$loop->last)<div class="flow-link"><span class="flow-packet" style="--d: {{ $i * 0.3 }}s"></span></div>@endif
                    @endforeach
                </div>
                <pre class="bg-slate-900 text-slate-100 text-xs rounded-xl p-4 overflow-x-auto"><code>Agent = {
  dataset_id, name,
  system_prompt,            // ქცევა (ქართულად)
  model_tier,               // fast → Haiku · standard → Sonnet · max → Opus
  enabled_tools,            // [add_item, update_item, find_items] — admin-ისთვის
  public_key, allowed_domains
}</code></pre>
            </div>

            {{-- 3. Chat --}}
            <div class="reveal">
                <div class="text-xs font-semibold text-indigo-600 mb-1">ნაბიჯი 3</div>
                <h3 class="text-xl font-bold mb-3">რა ხდება, როცა ჩატში წერენ</h3>
                <p class="text-slate-600 mb-6 max-w-3xl">
                    კითხვა მიდის <code>POST /public/chat</code>-ზე. თუ საუბარს აქვს ისტორია, <b>Groq</b> ჯერ გადააკეთებs კითხვას დამოუკიდებელ საძიებო ფრაზად.
                    <b>Gemini</b> აქცევs ვექტორად, <b>Retriever</b> აკეთებs <b>ჰიბრიდულ ძებნას</b> (pgvector + BM25) აგენტის დატასეტში და აერთებს <b>RRF</b>-ით.
                    მოძიებული ჩანკები (ციტატებით) მიდის <b>Claude</b>-თან, რომელიც აბრუნებs ქართულ პასუხს. თუ აგენტს აქვს <b>ხელსაწყოები</b> (admin), Claude-ს შეუძლია მათი გამოძახება და მონაცემების შეცვლა.
                </p>
                <div class="flow mb-6">
                    @foreach ([['💬','კითხვა'],['✍️','Groq rewrite'],['🧠','embed'],['🔍','vector + BM25'],['⚖️','RRF'],['🤖','Claude'],['✅','პასუხი']] as $i => $s)
                        <div class="flow-node" style="--d: {{ $i * 0.28 }}s"><div class="flow-dot">{{ $s[0] }}</div><span>{{ $s[1] }}</span></div>
                        @if (!$loop->last)<div class="flow-link"><span class="flow-packet" style="--d: {{ $i * 0.28 }}s"></span></div>@endif
                    @endforeach
                </div>
                <pre class="bg-slate-900 text-slate-100 text-xs rounded-xl p-4 overflow-x-auto"><code>POST /public/chat
{ "public_key": "pk_gtuh_…", "message": "ძლიერი PC მინდა, რას მირჩევთ?" }
→ { "answer": "გირჩევთ: RTX 4070 (2100₾) …", "sources": [{ "ref": 1, "title": "RTX 4070" }], "message_id": 42 }</code></pre>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="max-w-3xl mx-auto px-4 py-16 text-center">
        <h2 class="text-2xl font-bold">მზად ხარ?</h2>
        <p class="text-slate-500 mt-2">შექმენი ანგარიში და რამდენიმე წუთში გექნება მუშა AI აგენტი შენს მონაცემებზე.</p>
        @auth
            <a href="/dashboard" class="inline-block mt-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-8 py-3 font-medium">პანელზე გადასვლა</a>
        @else
            <a href="/register" class="inline-block mt-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-8 py-3 font-medium">დაწყება</a>
        @endauth
    </section>

    <footer class="text-center text-slate-400 text-xs py-8">GTUH AI · Technological Hackathon 2026</footer>
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
