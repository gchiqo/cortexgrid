{{-- Application shell: persistent rail on the left, contextual bar on top. --}}
@extends('layout')

@push('head')
<style>
    .shell { display:grid; grid-template-columns:236px minmax(0,1fr); min-height:100vh; }
    @media (max-width:960px){ .shell{ grid-template-columns:minmax(0,1fr) } }

    /* ---------- rail ---------- */
    .side{ position:sticky; top:0; height:100vh; display:flex; flex-direction:column; gap:6px;
        padding:18px 12px; border-right:1px solid var(--line);
        background:rgba(6,10,20,.66); backdrop-filter:blur(18px) saturate(1.2); }
    html.light .side{ background:rgba(255,255,255,.7); }
    @media (max-width:960px){
        .side{ position:fixed; z-index:60; width:236px; left:0; transform:translateX(-102%);
            transition:transform .22s ease; box-shadow:24px 0 60px -30px #000; }
        .side.open{ transform:none }
    }
    .side-brand{ display:flex; align-items:center; gap:10px; padding:6px 10px 16px; text-decoration:none }
    .side-mark{ width:32px;height:32px;border-radius:9px;display:grid;place-items:center;
        font-family:'JetBrains Mono',monospace;font-size:12.5px;font-weight:600;color:#04060d;
        background:linear-gradient(135deg,var(--accent),var(--accent-2));
        box-shadow:0 6px 20px -6px rgba(34,211,238,.8) }
    .side-word{ font-size:15px;font-weight:600;letter-spacing:-.02em;color:var(--text) }
    .side-word b{ font-weight:600;color:var(--accent) }

    .side-nav{ display:flex; flex-direction:column; gap:2px; flex:1 }
    .side-link{ display:flex;align-items:center;gap:11px;padding:9px 11px;border-radius:10px;
        color:var(--muted);text-decoration:none;font-size:13.5px;position:relative;transition:all .15s ease }
    .side-link .ico{ width:17px;height:17px;flex:0 0 auto;opacity:.8 }
    .side-link:hover{ color:var(--text); background:rgba(255,255,255,.04) }
    .side-link.is-active{ color:var(--text); background:rgba(34,211,238,.10) }
    .side-link.is-active .ico{ color:var(--accent); opacity:1 }
    .side-link.is-active::before{ content:'';position:absolute;left:-12px;top:9px;bottom:9px;width:2px;border-radius:0 2px 2px 0;
        background:linear-gradient(180deg,var(--accent),var(--accent-2));box-shadow:0 0 12px rgba(34,211,238,.9) }
    .side-sep{ height:1px;background:var(--line);margin:10px 4px }

    .side-foot{ display:flex;flex-direction:column;gap:4px;padding-top:10px;border-top:1px solid var(--line) }
    .side-stat{ display:flex;align-items:center;gap:9px;padding:7px 10px;border-radius:9px;text-decoration:none;
        transition:background .15s ease }
    a.side-stat:hover{ background:rgba(255,255,255,.04) }
    .side-stat-k{ font-size:10px;text-transform:uppercase;letter-spacing:.11em;color:var(--dim) }
    .side-stat-v{ font-family:'JetBrains Mono',monospace;font-size:12.5px;color:var(--text);
        overflow:hidden;text-overflow:ellipsis;white-space:nowrap }
    .dot{ width:7px;height:7px;border-radius:50%;background:var(--accent-3);flex:0 0 auto;
        box-shadow:0 0 9px var(--accent-3);animation:blip 2.4s ease-in-out infinite }
    .dot-2{ background:var(--accent);box-shadow:0 0 9px var(--accent) }
    .side-user{ display:flex;align-items:center;gap:9px;padding:6px 8px }
    .side-avatar{ width:26px;height:26px;border-radius:8px;display:grid;place-items:center;flex:0 0 auto;
        font-size:12px;font-weight:600;color:var(--accent);border:1px solid rgba(34,211,238,.3);
        background:rgba(34,211,238,.09) }
    .side-email{ font-size:11.5px;color:var(--dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1 }
    .side-out{ background:none;border:0;color:var(--dim);cursor:pointer;font-size:14px;padding:2px 4px;border-radius:6px }
    .side-out:hover{ color:var(--danger);background:rgba(251,113,133,.1) }
    @keyframes blip{0%,100%{opacity:1}50%{opacity:.3}}

    /* ---------- top bar ---------- */
    .topbar{ position:sticky;top:0;z-index:30;display:flex;align-items:center;gap:14px;
        padding:0 22px;height:60px;border-bottom:1px solid var(--line);
        background:rgba(4,7,14,.7);backdrop-filter:blur(16px) saturate(1.3) }
    html.light .topbar{ background:rgba(255,255,255,.76) }
    .topbar h1{ font-size:15.5px;font-weight:600;letter-spacing:-.02em;margin:0;white-space:nowrap }
    .crumb{ font-size:11px;color:var(--dim);text-transform:uppercase;letter-spacing:.12em;display:block;margin-bottom:1px }
    .topbar-actions{ margin-inline-start:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end }
    .icon-btn{ width:34px;height:34px;display:grid;place-items:center;border-radius:9px;border:1px solid var(--line);
        color:var(--muted);background:rgba(255,255,255,.03);cursor:pointer;transition:all .15s ease }
    .icon-btn:hover{ color:var(--accent);border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.08) }
    .icon-btn .ico{ width:17px;height:17px }
    .kbd{ font-family:'JetBrains Mono',monospace;font-size:10px;border:1px solid var(--line);border-radius:5px;
        padding:1px 5px;color:var(--dim) }
    .cmd-open{ display:flex;align-items:center;gap:9px;height:34px;padding:0 11px;border-radius:9px;
        border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--dim);cursor:pointer;
        font-size:12.5px;transition:all .15s ease }
    .cmd-open:hover{ border-color:rgba(34,211,238,.4);color:var(--accent) }
    @media (max-width:720px){ .cmd-open span.lbl{ display:none } }
    .topbar-meta{ font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--dim);
        border:1px solid var(--line);border-radius:8px;padding:5px 10px;white-space:nowrap }
    .burger{ display:none }
    @media (max-width:960px){ .burger{ display:grid } }

    .main-pad{ padding:26px 22px 70px; max-width:1180px }

    /* ---------- command palette ---------- */
    .cmdk{ position:fixed;inset:0;z-index:80;display:none;align-items:flex-start;justify-content:center;
        padding-top:14vh;background:rgba(2,4,10,.7);backdrop-filter:blur(6px) }
    .cmdk.open{ display:flex }
    .cmdk-box{ width:min(560px,92vw);border-radius:16px;border:1px solid rgba(34,211,238,.28);
        background:rgba(10,16,30,.96);box-shadow:0 40px 90px -30px #000,0 0 0 1px rgba(34,211,238,.1);overflow:hidden }
    .cmdk input{ width:100%;border:0!important;background:transparent!important;padding:16px 18px;font-size:15px;
        border-bottom:1px solid var(--line)!important;border-radius:0!important }
    .cmdk input:focus{ box-shadow:none!important }
    .cmdk-list{ max-height:52vh;overflow-y:auto;padding:6px }
    .cmdk-item{ display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:9px;cursor:pointer;
        color:var(--muted);font-size:13.5px;text-decoration:none }
    .cmdk-item .ico{ width:16px;height:16px }
    .cmdk-item.sel,.cmdk-item:hover{ background:rgba(34,211,238,.11);color:var(--text) }
    .cmdk-item.sel .ico{ color:var(--accent) }
    .cmdk-empty{ padding:18px;color:var(--dim);font-size:13px;text-align:center }
</style>
@endpush

@section('body')
<div class="shell">
    @include('partials.sidebar')

    <div class="min-w-0">
        <div class="topbar">
            <button class="icon-btn burger" id="burger" aria-label="menu"><x-icon name="menu" /></button>
            <div class="min-w-0">
                @hasSection('crumb')<span class="crumb">@yield('crumb')</span>@endif
                <h1>@yield('heading', __('common.app_title'))</h1>
            </div>
            <div class="topbar-actions">
                @yield('actions')
                <button class="cmd-open" id="cmdOpen">
                    <x-icon name="search" class="ico" style="width:15px;height:15px" />
                    <span class="lbl">{{ __('common.search') }}</span>
                    <span class="kbd">⌘K</span>
                </button>
                @include('partials.lang-toggle')
                @include('partials.theme-toggle')
            </div>
        </div>

        <main class="main-pad">
            @yield('content')
        </main>
    </div>
</div>

{{-- Command palette: every destination reachable without leaving the keyboard. --}}
<div class="cmdk" id="cmdk">
    <div class="cmdk-box">
        <input id="cmdInput" type="text" autocomplete="off" placeholder="{{ __('common.search_placeholder') }}">
        <div class="cmdk-list" id="cmdList"></div>
    </div>
</div>

@php($__cmd = [
    ['href' => '/dashboard',               'label' => __('nav.overview')],
    ['href' => '/dashboard/console',       'label' => __('nav.console')],
    ['href' => '/dashboard/conversations', 'label' => __('nav.conversations')],
    ['href' => '/dashboard/insights',      'label' => __('nav.insights')],
    ['href' => '/dashboard/leads',         'label' => __('nav.leads')],
    ['href' => '/dashboard/docs',          'label' => __('nav.api')],
    ['href' => '/dashboard/billing',       'label' => __('nav.billing')],
])
<script>
(function () {
    var burger = document.getElementById('burger'), side = document.getElementById('side');
    if (burger) burger.addEventListener('click', function () { side.classList.toggle('open'); });

    var items = @json($__cmd);

    var box = document.getElementById('cmdk'), input = document.getElementById('cmdInput'),
        list = document.getElementById('cmdList'), sel = 0, shown = items;

    function render() {
        list.innerHTML = shown.length
            ? shown.map(function (it, i) {
                return '<a class="cmdk-item' + (i === sel ? ' sel' : '') + '" href="' + it.href + '">' +
                       '<span>' + it.label + '</span></a>';
              }).join('')
            : '<div class="cmdk-empty">—</div>';
    }
    function open() { box.classList.add('open'); input.value = ''; shown = items; sel = 0; render(); input.focus(); }
    function close() { box.classList.remove('open'); }

    document.getElementById('cmdOpen').addEventListener('click', open);
    box.addEventListener('click', function (e) { if (e.target === box) close(); });
    input.addEventListener('input', function () {
        var q = input.value.toLowerCase();
        shown = items.filter(function (i) { return i.label.toLowerCase().indexOf(q) > -1; });
        sel = 0; render();
    });
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); open(); return; }
        if (!box.classList.contains('open')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowDown') { e.preventDefault(); sel = Math.min(sel + 1, shown.length - 1); render(); }
        if (e.key === 'ArrowUp') { e.preventDefault(); sel = Math.max(sel - 1, 0); render(); }
        if (e.key === 'Enter' && shown[sel]) { window.location = shown[sel].href; }
    });
})();
</script>
@endsection
