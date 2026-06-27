@extends('layout')
@section('title', 'API დოკუმენტაცია')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg"><a href="/">GTUH <span class="text-indigo-600">AI</span></a> · API დოკუმენტაცია</div>
            <div class="flex items-center gap-2">
                @include('partials.theme-toggle')
                <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">← პანელი</a>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold">ინტეგრაცია & ავტომატიზაცია</h1>
        <p class="text-slate-500 mt-2 mb-6 max-w-3xl">დააკავშირე შენი საიტი ან სისტემა და მონაცემები შეინარჩუნე ახალი (fresh). აირჩიე ინტეგრაციის ტიპი:</p>

        {{-- Tabs --}}
        <div class="flex gap-2 flex-wrap mb-6" id="tabs">
            <button data-tab="start" class="doc-tab">დაწყება</button>
            <button data-tab="ingest" class="doc-tab">მონაცემების გაგზავნა</button>
            <button data-tab="sync" class="doc-tab">სინქრონიზაცია (fresh)</button>
            <button data-tab="query" class="doc-tab">კითხვა (Query)</button>
            <button data-tab="widget" class="doc-tab">ვიჯეტი</button>
        </div>

        {{-- Start --}}
        <div data-panel="start" class="doc-panel space-y-4">
            <h2 class="text-lg font-semibold">ავთენტიფიკაცია</h2>
            <p class="text-slate-600 text-sm">ყველა API მოთხოვნა ავთენტიფიცირდება <b>tenant API გასაღებით</b>. გასაღები აიღე პანელზე → „API გასაღებები“ (ნაჩვენებია ერთხელ).</p>
            <div class="bg-white rounded-xl shadow-sm p-4 text-sm space-y-1">
                <div><span class="text-slate-400">Base URL</span> &nbsp; <code>{{ $base }}</code></div>
                <div><span class="text-slate-400">Header</span> &nbsp; <code>Authorization: Bearer YOUR_API_KEY</code></div>
                <div><span class="text-slate-400">ალტ.</span> &nbsp; <code>X-Api-Key: YOUR_API_KEY</code></div>
            </div>
            <pre class="doc-code"><code>curl {{ $base }}/v1/query \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"question":"გამარჯობა"}'</code></pre>
        </div>

        {{-- Ingest --}}
        <div data-panel="ingest" class="doc-panel hidden space-y-4">
            <h2 class="text-lg font-semibold">მონაცემების გაგზავნა — <code>POST /v1/ingest</code></h2>
            <p class="text-slate-600 text-sm">გააგზავნე ტექსტი ან სტრუქტურირებული ჩანაწერები. <code>dataset</code> (id ან სახელი) მიუთითებს რომელ დატასეტში — სახელით find-or-create.</p>
            <p class="text-slate-500 text-xs">⚠️ ერთი მოთხოვნა შეზღუდულია body-ის ზომით — დიდი კატალოგი გააგზავნე <b>ულუფებად</b> (~100–500 ჩანაწერი) ან გამოიყენე სინქრონიზაცია.</p>
            <pre class="doc-code"><code>curl {{ $base }}/v1/ingest \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "dataset": "კომპიუტერული მაღაზია",
    "records": [
      { "external_id": "sku-101", "name": "RTX 4070", "price_gel": 2100, "url": "https://shop.ge/p/rtx-4070" },
      { "external_id": "sku-102", "name": "Ryzen 5 7600X", "price_gel": 720, "socket": "AM5" }
    ]
  }'
# → 201 { "documents": 2, "created": 2, "updated": 0, "chunks": 2, "status": "processing" }</code></pre>
            <p class="text-slate-600 text-sm">ან ერთი ტექსტი (მაგ. PDF-ის შიგთავსი):</p>
            <pre class="doc-code"><code>{ "dataset": 1, "title": "მიწოდების პირობები", "text": "ჩვენ ვაგზავნით..." }</code></pre>
        </div>

        {{-- Sync --}}
        <div data-panel="sync" class="doc-panel hidden space-y-4">
            <h2 class="text-lg font-semibold">სინქრონიზაცია — მონაცემები ყოველთვის fresh</h2>
            <p class="text-slate-600 text-sm">თითო ჩანაწერს მიეცი <code>external_id</code> (შენი სისტემის id). იმავე <code>external_id</code>-ით ხელახალი გაგზავნა <b>აახლებს</b> ჩანაწერს და ხელახლა ემბედავს — დუბლიკატის გარეშე.</p>
            <p class="text-slate-600 text-sm">გაუშვი cron, რომელიც ბოლო სინქრონის შემდეგ <b>შეცვლილ</b> ჩანაწერებს აგზავნის:</p>
            <pre class="doc-code"><code>// cron (PHP/Node-ის ფსევდოკოდი) — ყოველ 15 წუთში
const changed = await db.products.where('updated_at > ?', lastSync); // მხოლოდ ცვლილებები
for (const batch of chunk(changed, 200)) {
  await fetch("{{ $base }}/v1/ingest", {
    method: "POST",
    headers: { "Authorization": "Bearer YOUR_API_KEY", "Content-Type": "application/json" },
    body: JSON.stringify({
      dataset: "კომპიუტერული მაღაზია",
      records: batch.map(p => ({ external_id: p.id, name: p.name, price_gel: p.price, url: p.url }))
    })
  });
}
saveLastSync(now());</code></pre>
            <p class="text-slate-500 text-xs">ვებჰუკები (პროდუქტის შექმნა/განახლება/წაშლა real-time) — გზამკვლევში მალე.</p>
        </div>

        {{-- Query --}}
        <div data-panel="query" class="doc-panel hidden space-y-4">
            <h2 class="text-lg font-semibold">კითხვა — <code>POST /v1/query</code></h2>
            <p class="text-slate-600 text-sm">დასვი კითხვა პროგრამულად. <code>config_id</code> ირჩევs რომელ აგენტს (და მის დატასეტს) ეკითხო; თუ არ მიუთითებ — პირველი აგენტი.</p>
            <pre class="doc-code"><code>curl {{ $base }}/v1/query \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{ "question": "ძლიერი PC მინდა, რას მირჩევთ?", "config_id": 1 }'

# → {
#   "answer": "გირჩევთ: RTX 4070 (2100₾) ...",
#   "sources": [ { "ref": 1, "title": "RTX 4070" } ],
#   "usage": { "input_tokens": 750, "output_tokens": 192 }
# }</code></pre>
        </div>

        {{-- Widget --}}
        <div data-panel="widget" class="doc-panel hidden space-y-4">
            <h2 class="text-lg font-semibold">ჩასმადი ვიჯეტი</h2>
            <p class="text-slate-600 text-sm">ჩასვი ეს კოდი შენი საიტის HTML-ში. <code>public key</code> აიღე აგენტის რედაქტირების გვერდიდან (ბრაუზერისთვის უსაფრთხო).</p>
            <pre class="doc-code"><code>&lt;script src="{{ $base }}/embed.js?key=YOUR_PUBLIC_KEY" async&gt;&lt;/script&gt;</code></pre>
            <p class="text-slate-600 text-sm">საიტზე გამოჩნდება მცურავი ჩატის ღილაკი. დაშვებული დომენების შეზღუდვა (CORS) აგენტის პარამეტრებშია.</p>
            <p class="text-slate-600 text-sm">პირდაპირი public endpoint (ვიჯეტი ამას იძახებს):</p>
            <pre class="doc-code"><code>POST {{ $base }}/public/chat
{ "public_key": "pk_gtuh_…", "message": "გამარჯობა", "conversation_id": null }</code></pre>
        </div>
    </main>
</div>

<style>
.doc-tab{padding:8px 14px;border-radius:9px;font-size:14px;font-weight:500;background:#f1f5f9;color:#475569;cursor:pointer}
.doc-tab.active{background:#4f46e5;color:#fff}
html.dark .doc-tab{background:#1e293b;color:#94a3b8}
html.dark .doc-tab.active{background:#4f46e5;color:#fff}
.doc-code{background:#0f172a;color:#e2e8f0;border-radius:12px;padding:16px;overflow-x:auto;font-size:12.5px;line-height:1.6}
</style>
<script>
const tabs = document.querySelectorAll('.doc-tab');
const panels = document.querySelectorAll('.doc-panel');
function activate(name){
    tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === name));
    panels.forEach(p => p.classList.toggle('hidden', p.dataset.panel !== name));
}
tabs.forEach(t => t.addEventListener('click', () => activate(t.dataset.tab)));
activate('start');
</script>
@endsection
