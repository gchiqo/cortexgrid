@extends('layout-app')
@section('title', __('dashboard.title'))
@section('crumb', __('common.app_title'))
@section('heading', __('nav.overview'))

@push('head')
<style>
    .band{ display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr;gap:1px;background:var(--line);
        border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;margin-bottom:26px }
    .band > *{ background:var(--surface);padding:16px 18px;text-decoration:none;display:block;
        transition:background .16s ease }
    html.light .band > *{ background:var(--surface) }
    a.band-cell:hover{ background:rgba(34,211,238,.07) }
    .band-k{ font-size:10px;text-transform:uppercase;letter-spacing:.12em;color:var(--dim);
        display:flex;align-items:center;gap:7px }
    .band-v{ font-family:'JetBrains Mono',monospace;font-size:24px;font-weight:500;letter-spacing:-.04em;
        margin-top:7px;color:var(--text) }
    .band-v.accent{ background:linear-gradient(120deg,var(--accent),var(--accent-2));
        -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent }
    .band-sub{ font-size:11px;color:var(--dim);margin-top:3px;font-family:'JetBrains Mono',monospace }
    @media (max-width:860px){ .band{ grid-template-columns:1fr 1fr } }

    .cols{ display:grid;grid-template-columns:minmax(0,1.55fr) minmax(0,1fr);gap:22px;align-items:start }
    @media (max-width:940px){ .cols{ grid-template-columns:minmax(0,1fr) } }

    .sec-head{ display:flex;align-items:center;gap:10px;margin-bottom:12px }
    .sec-head h2{ font-size:14px;font-weight:600;letter-spacing:.01em;margin:0 }
    .count{ font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--accent);
        border:1px solid rgba(34,211,238,.28);background:rgba(34,211,238,.07);border-radius:999px;padding:1px 8px }

    /* Datasets read as rows — a work list, not a wall of tiles. */
    .rows{ border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;background:var(--surface) }
    html.light .rows{ background:var(--bar) }
    .row-item{ display:flex;align-items:center;gap:14px;padding:14px 16px;text-decoration:none;
        border-bottom:1px solid var(--line);transition:background .15s ease;position:relative }
    .row-item:last-of-type{ border-bottom:0 }
    .row-item:hover{ background:rgba(34,211,238,.06) }
    .row-item:hover::before{ content:'';position:absolute;left:0;top:0;bottom:0;width:2px;
        background:linear-gradient(180deg,var(--accent),var(--accent-2)) }
    .row-glyph{ width:34px;height:34px;border-radius:10px;display:grid;place-items:center;flex:0 0 auto;
        font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--accent);
        border:1px solid rgba(34,211,238,.26);background:rgba(34,211,238,.07) }
    .row-name{ font-weight:600;font-size:14px }
    .row-desc{ font-size:12px;color:var(--dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap }
    .row-meta{ display:flex;gap:14px;margin-inline-start:auto;flex:0 0 auto;text-align:right }
    .row-n{ font-family:'JetBrains Mono',monospace;font-size:14px;color:var(--text) }
    .row-l{ font-size:9.5px;text-transform:uppercase;letter-spacing:.1em;color:var(--dim) }
    .row-add{ display:flex;align-items:center;gap:10px;padding:12px 16px;background:rgba(255,255,255,.02) }
    .row-add input{ flex:1;padding:8px 11px;font-size:13px }
    .row-add button{ padding:8px 16px;font-size:13px;border-radius:9px;white-space:nowrap }

    .panel{ border:1px solid var(--line);border-radius:var(--radius);padding:16px;background:var(--surface) }
    html.light .panel{ background:var(--bar) }
    .keyrow{ display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px solid var(--line) }
    .keyrow:first-of-type{ border-top:0 }
    .pill{ font-size:10px;padding:1px 8px;border-radius:999px;border:1px solid }
    .pill-on{ color:var(--accent-3);border-color:rgba(52,211,153,.35);background:rgba(52,211,153,.08) }
    .pill-off{ color:var(--danger);border-color:rgba(251,113,133,.35);background:rgba(251,113,133,.08) }
</style>
@endpush

@section('content')
    @if (session('new_api_key'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 mb-5">
            <div class="font-medium text-emerald-800 text-sm">{{ __('dashboard.new_key_notice') }}</div>
            <div class="flex items-center gap-2 mt-2">
                <code id="newApiKey" class="flex-1 px-3 py-2 text-sm break-all">{{ session('new_api_key') }}</code>
                <button type="button" data-copied="{{ __('common.copied') }}"
                        onclick="navigator.clipboard.writeText(document.getElementById('newApiKey').textContent).then(()=>{this.textContent=this.dataset.copied;})"
                        class="shrink-0 bg-emerald-600 rounded-lg px-3 py-2 text-sm font-medium">📋 {{ __('common.copy') }}</button>
            </div>
        </div>
    @endif
    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800 mb-5">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 mb-5">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    {{-- Instrument band: what the platform is running on, and what it has spent. --}}
    @php($llm = \App\Services\Llm\LlmConfig::active())
    <section class="band">
        <a href="/dashboard/billing" class="band-cell">
            <div class="band-k"><span class="dot dot-2"></span>{{ __('dashboard.credits') }}</div>
            <div class="band-v accent">{{ number_format($tenant->credits) }}</div>
            <div class="band-sub">{{ __('dashboard.top_up') }} →</div>
        </a>
        <div>
            <div class="band-k">{{ __('dashboard.documents_ingested') }}</div>
            <div class="band-v">{{ number_format($usage['ingest']) }}</div>
        </div>
        <div>
            <div class="band-k">{{ __('dashboard.queries') }}</div>
            <div class="band-v">{{ number_format($usage['query']) }}</div>
        </div>
        <div>
            <div class="band-k">{{ __('dashboard.tokens_used') }}</div>
            <div class="band-v">{{ number_format($usage['tokens']) }}</div>
            <div class="band-sub">{{ $llm['provider'] }} · {{ \Illuminate\Support\Str::limit($llm['model'], 22) }}</div>
        </div>
    </section>

    <div class="cols">
        <div>
            <div class="sec-head">
                <h2>{{ __('dashboard.datasets') }}</h2>
                <span class="count">{{ count($datasets) }}</span>
            </div>

            <div class="rows">
                @foreach ($datasets as $ds)
                    <a href="/dashboard/datasets/{{ $ds->id }}" class="row-item">
                        <span class="row-glyph">{{ mb_strtoupper(mb_substr($ds->name, 0, 2)) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="row-name block">{{ $ds->name }}</span>
                            <span class="row-desc block">{{ $ds->description ?: __('dashboard.datasets_hint') }}</span>
                        </span>
                        <span class="row-meta">
                            <span>
                                <span class="row-n block">{{ $ds->sources_count }}</span>
                                <span class="row-l block">{{ __('explorer.overview.sources') }}</span>
                            </span>
                            <span>
                                <span class="row-n block">{{ $ds->ai_configs_count }}</span>
                                <span class="row-l block">{{ __('dataset.chatbots') }}</span>
                            </span>
                        </span>
                    </a>
                @endforeach

                <form method="POST" action="/dashboard/datasets" class="row-add">@csrf
                    <input name="name" required placeholder="{{ __('dashboard.new_dataset_name_placeholder') }}">
                    <input name="description" placeholder="{{ __('dashboard.new_dataset_desc_placeholder') }}">
                    <button class="bg-indigo-600 font-medium">{{ __('common.create') }}</button>
                </form>
            </div>
        </div>

        <div class="space-y-5">
            <div class="panel">
                <div class="sec-head">
                    <h2>{{ __('dashboard.api_keys') }}</h2>
                    <span class="count">{{ count($apiKeys) }}</span>
                </div>
                <form method="POST" action="/dashboard/keys" class="flex gap-2 mb-3">@csrf
                    <input name="label" placeholder="{{ __('dashboard.key_label_placeholder') }}"
                           class="flex-1 px-3 py-2 text-sm">
                    <button class="bg-indigo-600 rounded-lg px-4 py-2 text-sm font-medium">{{ __('common.new') }}</button>
                </form>
                @forelse ($apiKeys as $key)
                    <div class="keyrow">
                        <code class="text-xs">{{ $key->prefix }}…</code>
                        <span class="text-xs text-slate-400 flex-1 truncate">{{ $key->label }}</span>
                        @if ($key->revoked_at)
                            <span class="pill pill-off">{{ __('dashboard.key_revoked') }}</span>
                        @else
                            <span class="pill pill-on">{{ __('dashboard.key_active') }}</span>
                            <form method="POST" action="/dashboard/keys/{{ $key->id }}/revoke">@csrf
                                <button class="text-xs text-red-500 hover:underline">{{ __('dashboard.revoke') }}</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400">—</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
