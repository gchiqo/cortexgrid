@extends('layout-app')
@section('title', __('docs.title'))
@section('heading', __('docs.title'))

@section('content')
<h1 class="text-2xl font-bold">{{ __('docs.heading') }}</h1>
        <p class="text-slate-500 mt-2 mb-6 max-w-3xl">{{ __('docs.intro') }}</p>

        {{-- Tabs --}}
        <div class="flex gap-2 flex-wrap mb-6" id="tabs">
            <button data-tab="start" class="doc-tab">{{ __('docs.tab_start') }}</button>
            <button data-tab="ingest" class="doc-tab">{{ __('docs.tab_ingest') }}</button>
            <button data-tab="sync" class="doc-tab">{{ __('docs.tab_sync') }}</button>
            <button data-tab="query" class="doc-tab">{{ __('docs.tab_query') }}</button>
            <button data-tab="widget" class="doc-tab">{{ __('docs.tab_widget') }}</button>
        </div>

        {{-- Start --}}
        <div data-panel="start" class="doc-panel space-y-4">
            <h2 class="text-lg font-semibold">{{ __('docs.auth') }}</h2>
            <p class="text-slate-600 text-sm">{!! __('docs.auth_body') !!}</p>
            <div class="bg-white rounded-xl shadow-sm p-4 text-sm space-y-1">
                <div><span class="text-slate-400">Base URL</span> &nbsp; <code>{{ $base }}</code></div>
                <div><span class="text-slate-400">Header</span> &nbsp; <code>Authorization: Bearer YOUR_API_KEY</code></div>
                <div><span class="text-slate-400">{{ __('docs.alt') }}</span> &nbsp; <code>X-Api-Key: YOUR_API_KEY</code></div>
            </div>
            <pre class="doc-code"><code>curl {{ $base }}/v1/query \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"question":"{{ __('docs.example_greeting') }}"}'</code></pre>
        </div>

        {{-- Ingest --}}
        <div data-panel="ingest" class="doc-panel hidden space-y-4">
            <h2 class="text-lg font-semibold">{{ __('docs.ingest_heading') }} — <code>POST /v1/ingest</code></h2>
            <p class="text-slate-600 text-sm">{!! __('docs.ingest_body') !!}</p>
            <p class="text-slate-500 text-xs">{!! __('docs.ingest_warning') !!}</p>
            <pre class="doc-code"><code>curl {{ $base }}/v1/ingest \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "dataset": "{{ __('docs.example_dataset') }}",
    "records": [
      { "external_id": "sku-101", "name": "RTX 4070", "price_gel": 2100, "url": "https://shop.ge/p/rtx-4070" },
      { "external_id": "sku-102", "name": "Ryzen 5 7600X", "price_gel": 720, "socket": "AM5" }
    ]
  }'
# → 201 { "documents": 2, "created": 2, "updated": 0, "chunks": 2, "status": "processing" }</code></pre>
            <p class="text-slate-600 text-sm">{{ __('docs.ingest_single_text') }}</p>
            <pre class="doc-code"><code>{ "dataset": 1, "title": "{{ __('docs.example_title') }}", "text": "{{ __('docs.example_text') }}" }</code></pre>
        </div>

        {{-- Sync --}}
        <div data-panel="sync" class="doc-panel hidden space-y-4">
            <h2 class="text-lg font-semibold">{{ __('docs.sync_heading') }}</h2>
            <p class="text-slate-600 text-sm">{!! __('docs.sync_body') !!}</p>
            <p class="text-slate-600 text-sm">{!! __('docs.sync_cron') !!}</p>
            <pre class="doc-code"><code>// {{ __('docs.sync_code_comment') }}
const changed = await db.products.where('updated_at > ?', lastSync); // {{ __('docs.sync_code_only_changed') }}
for (const batch of chunk(changed, 200)) {
  await fetch("{{ $base }}/v1/ingest", {
    method: "POST",
    headers: { "Authorization": "Bearer YOUR_API_KEY", "Content-Type": "application/json" },
    body: JSON.stringify({
      dataset: "{{ __('docs.example_dataset') }}",
      records: batch.map(p => ({ external_id: p.id, name: p.name, price_gel: p.price, url: p.url }))
    })
  });
}
saveLastSync(now());</code></pre>
            <p class="text-slate-500 text-xs">{{ __('docs.sync_webhooks') }}</p>
        </div>

        {{-- Query --}}
        <div data-panel="query" class="doc-panel hidden space-y-4">
            <h2 class="text-lg font-semibold">{{ __('docs.query_heading') }} — <code>POST /v1/query</code></h2>
            <p class="text-slate-600 text-sm">{!! __('docs.query_body') !!}</p>
            <pre class="doc-code"><code>curl {{ $base }}/v1/query \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{ "question": "{{ __('docs.example_question') }}", "config_id": 1 }'

# → {
#   "answer": "{{ __('docs.example_answer') }}",
#   "sources": [ { "ref": 1, "title": "RTX 4070" } ],
#   "usage": { "input_tokens": 750, "output_tokens": 192 }
# }</code></pre>
        </div>

        {{-- Widget --}}
        <div data-panel="widget" class="doc-panel hidden space-y-4">
            <h2 class="text-lg font-semibold">{{ __('docs.widget_heading') }}</h2>
            <p class="text-slate-600 text-sm">{!! __('docs.widget_body') !!}</p>
            <pre class="doc-code"><code>&lt;script src="{{ $base }}/embed.js?key=YOUR_PUBLIC_KEY" async&gt;&lt;/script&gt;</code></pre>
            <p class="text-slate-600 text-sm">{{ __('docs.widget_note') }}</p>
            <p class="text-slate-600 text-sm">{{ __('docs.widget_endpoint') }}</p>
            <pre class="doc-code"><code>POST {{ $base }}/public/chat
{ "public_key": "pk_cortexgrid_…", "message": "{{ __('docs.example_greeting') }}", "conversation_id": null }</code></pre>
        </div>
    </main>
</div>

<style>
.doc-tab{padding:9px 16px;border-radius:10px;font-size:13.5px;font-weight:500;cursor:pointer;
    background:var(--panel);border:1px solid var(--line);color:var(--muted);transition:all .16s ease}
.doc-tab:hover{border-color:rgba(34,211,238,.4);color:var(--accent)}
.doc-tab.active{background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#04060d;border-color:transparent;
    box-shadow:0 6px 20px -8px rgba(34,211,238,.7)}
.doc-code{background:rgba(3,6,14,.86);color:#cfe6f5;border:1px solid var(--line);border-radius:var(--radius);
    padding:18px;overflow-x:auto;font-size:12.5px;line-height:1.65;font-family:'JetBrains Mono',monospace}
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
