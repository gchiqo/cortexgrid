<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('common.app_title'))</title>
    {{-- Dark is the default here; "light" is the opt-out. --}}
    <script>
        document.documentElement.classList.add('js');
        if (localStorage.theme === 'light') document.documentElement.classList.add('light');
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Space Grotesk carries Latin; Noto Sans Georgian carries ქართული. --}}
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Noto+Sans+Georgian:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <style>
    /* ============================================================
       CortexGrid — cyber design system.
       The views speak a small utility vocabulary (bg-white, shadow-sm,
       text-slate-*, bg-indigo-600 …). Rather than rewrite 20 templates,
       that vocabulary is remapped onto these tokens, so the whole app
       changes from here.
       ============================================================ */
    :root {
        /* Lifted slate rather than near-black: still dark, far less heavy. */
        --bg:        #182231;
        --bg-2:      #202c3f;
        --panel:     rgba(42, 57, 82, .74);
        --panel-2:   rgba(51, 68, 96, .86);
        --line:      rgba(163, 205, 250, .19);
        --line-soft: rgba(163, 205, 250, .11);
        --text:      #e9eff9;
        --muted:     #a8b8d0;
        --dim:       #8496ae;

        /* Named surfaces so depth is tuned here, not scattered per view. */
        --bar:           rgba(32, 44, 64, .82);
        --surface:       rgba(39, 53, 77, .7);
        --surface-solid: #27334a;
        --code-bg:       rgba(20, 29, 45, .92);
        --input-bg:      rgba(25, 35, 53, .85);
        --overlay:       rgba(16, 23, 36, .76);
        --nav-bg:        rgba(9, 14, 24, .74);
        --hover:         rgba(255, 255, 255, .05);
        --on-accent:     #04060d;
        --cta-shadow:       0 8px 24px -8px rgba(34, 211, 238, .75);
        --cta-shadow-hover: 0 12px 30px -8px rgba(34, 211, 238, .95);
        --accent:    #22d3ee;
        --accent-2:  #a78bfa;
        --accent-3:  #34d399;
        --danger:    #fb7185;
        --warn:      #fbbf24;
        --glow:      0 0 0 1px rgba(34,211,238,.16), 0 8px 32px -8px rgba(34,211,238,.22);
        --radius:    14px;
    }
    /* ------------------------------------------------------------------
       Light is not the dark theme with white swapped in. Glass becomes
       solid paper, neon glow becomes real elevation, and the accent darkens
       so it holds contrast against white.
       ------------------------------------------------------------------ */
    html.light {
        --bg:        #f2f6fc;
        --bg-2:      #e7eef8;
        --panel:     #ffffff;
        --panel-2:   #ffffff;
        --line:      #dde5f0;
        --line-soft: #eaf0f8;
        --text:      #0e1a2b;
        --muted:     #52627b;
        --dim:       #8393aa;
        --accent:    #0b8ba8;
        --accent-2:  #6544e8;
        --accent-3:  #0e9c74;
        --danger:    #d6455d;
        --warn:      #b06a06;

        /* layered elevation, not a ring of light */
        --glow: 0 1px 2px rgba(14,26,43,.05), 0 12px 30px -16px rgba(14,26,43,.28);

        --bar:           rgba(255,255,255,.85);
        --surface:       #ffffff;
        --surface-solid: #ffffff;
        --code-bg:       #0e1a2b;
        --input-bg:      #ffffff;
        --overlay:       rgba(203,213,228,.72);
        --nav-bg:        rgba(255,255,255,.88);
        --hover:         rgba(14,26,43,.05);
        --on-accent:     #ffffff;
        --cta-shadow:       0 6px 18px -8px rgba(11,139,168,.55);
        --cta-shadow-hover: 0 12px 26px -10px rgba(11,139,168,.7);
    }

    /* Light-mode corrections: the dark theme leans on glow, which reads as
       smudge on paper. Swap it for crisp edges and soft shadow. */
    html.light body::after {
        background:
            radial-gradient(40rem 24rem at 16% 4%, rgba(11,139,168,.10), transparent 66%),
            radial-gradient(36rem 22rem at 84% 0%, rgba(101,68,232,.09), transparent 66%);
    }
    html.light .bg-white, html.light .bg-slate-50, html.light .bg-slate-100 {
        backdrop-filter: none; -webkit-backdrop-filter: none;
    }
    html.light a.rounded-xl:hover, html.light a.shadow-sm:hover, html.light .hover\:shadow:hover {
        border-color: rgba(11,139,168,.4) !important;
        box-shadow: 0 2px 4px rgba(14,26,43,.05), 0 18px 38px -18px rgba(14,26,43,.4) !important;
    }
    html.light .bg-indigo-600, html.light .bg-slate-800 { box-shadow: var(--cta-shadow); }
    html.light .hover\:bg-indigo-700:hover, html.light .bg-indigo-600:hover, html.light .bg-slate-800:hover {
        box-shadow: var(--cta-shadow-hover);
    }
    html.light .js-theme-toggle:hover, html.light .js-lang-toggle:hover { box-shadow: none; }
    html.light code { background: rgba(11,139,168,.09) !important; color: #0a6d84 !important; }
    html.light pre code { color: #d7e6f2 !important; }
    html.light table tbody tr:hover { background: rgba(11,139,168,.05); }
    html.light input:focus, html.light textarea:focus, html.light select:focus {
        border-color: rgba(11,139,168,.65) !important;
        box-shadow: 0 0 0 3px rgba(11,139,168,.13) !important;
    }
    html.light ::-webkit-scrollbar-thumb { background: rgba(14,26,43,.18); }
    html.light ::selection { background: rgba(11,139,168,.22); }

    html, body { background: var(--bg); }
    body {
        color: var(--text);
        font-family: 'Space Grotesk', 'Noto Sans Georgian', system-ui, -apple-system, sans-serif;
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
    }

    /* --- Ambient: grid mesh + two slow aurora blooms --- */
    body::before {
        content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
        background-image:
            linear-gradient(var(--line-soft) 1px, transparent 1px),
            linear-gradient(90deg, var(--line-soft) 1px, transparent 1px);
        background-size: 54px 54px;
        mask-image: radial-gradient(ellipse 90% 60% at 50% 0%, #000 30%, transparent 78%);
        -webkit-mask-image: radial-gradient(ellipse 90% 60% at 50% 0%, #000 30%, transparent 78%);
    }
    body::after {
        content: ''; position: fixed; inset: -30% -10% auto -10%; height: 90vh; z-index: 0; pointer-events: none;
        background:
            radial-gradient(42rem 26rem at 18% 8%,  rgba(34,211,238,.10), transparent 65%),
            radial-gradient(38rem 24rem at 82% 2%, rgba(167,139,250,.10), transparent 65%);
        animation: drift 22s ease-in-out infinite alternate;
    }
    @keyframes drift { from { transform: translate3d(-2%, 0, 0) } to { transform: translate3d(2%, 2%, 0) } }
    body > * { position: relative; z-index: 1; }

    ::selection { background: rgba(34,211,238,.28); color: var(--text); }

    /* --- Surfaces ------------------------------------------------ */
    .bg-white, .bg-slate-50, .bg-slate-100 {
        background: var(--panel) !important;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }
    body.bg-slate-50, .min-h-screen { background: transparent !important; }
    .bg-gradient-to-b { background-image: none !important; background: transparent !important; }

    .rounded-xl, .rounded-2xl { border-radius: var(--radius) !important; }
    .shadow-sm, .shadow, .shadow-2xl { box-shadow: var(--glow) !important; }

    /* Panels get a hairline that brightens toward the accent on hover. */
    .shadow-sm, .shadow, .shadow-2xl, .rounded-xl, .rounded-2xl { border: 1px solid var(--line); }
    a.rounded-xl:hover, a.shadow-sm:hover, .hover\:shadow:hover {
        border-color: rgba(34,211,238,.34) !important;
        box-shadow: 0 0 0 1px rgba(34,211,238,.3), 0 14px 40px -12px rgba(34,211,238,.3) !important;
        transform: translateY(-1px);
    }
    a.rounded-xl, a.shadow-sm, .hover\:shadow { transition: all .18s ease; }

    /* --- Type ---------------------------------------------------- */
    .text-slate-900, .text-slate-800, .text-slate-700 { color: var(--text) !important; }
    .text-slate-600, .text-slate-500 { color: var(--muted) !important; }
    .text-slate-400 { color: var(--dim) !important; }
    .text-indigo-600, .text-indigo-700, .text-indigo-900, .text-indigo-500, .text-indigo-300 { color: var(--accent) !important; }
    .text-emerald-800, .text-emerald-700, .text-emerald-600, .text-emerald-300 { color: var(--accent-3) !important; }
    .text-red-700, .text-red-600, .text-red-500, .text-red-400 { color: var(--danger) !important; }
    .text-amber-800, .text-amber-700, .text-amber-500 { color: var(--warn) !important; }
    .font-bold, .font-semibold { letter-spacing: -.015em; }
    h1, h2, h3, .text-4xl, .text-5xl, .text-3xl, .text-2xl { letter-spacing: -.03em; }

    /* Numbers and identifiers read as data. */
    code, pre, .font-mono, table td:nth-child(n+2) { font-family: 'JetBrains Mono', ui-monospace, monospace; }
    .text-3xl.font-bold, .text-4xl.font-bold { font-family: 'JetBrains Mono', ui-monospace, monospace; letter-spacing: -.04em; }

    /* --- Tinted status blocks ------------------------------------ */
    .bg-indigo-50, .bg-indigo-100 { background: rgba(34,211,238,.10) !important; }
    .bg-emerald-50 { background: rgba(52,211,153,.10) !important; }
    .bg-red-50    { background: rgba(251,113,133,.10) !important; }
    .bg-amber-50  { background: rgba(251,191,36,.10) !important; }
    .border-indigo-200  { border-color: rgba(34,211,238,.32) !important; }
    .border-emerald-200 { border-color: rgba(52,211,153,.32) !important; }
    .border-red-200     { border-color: rgba(251,113,133,.32) !important; }
    .border-amber-200   { border-color: rgba(251,191,36,.32) !important; }

    .border, .border-b, .border-t, .border-r,
    .border-slate-200, .border-slate-300 { border-color: var(--line) !important; }
    .divide-slate-200 > * { border-color: var(--line) !important; }

    /* --- Accent actions ------------------------------------------ */
    .bg-indigo-600, .bg-slate-800 {
        background: linear-gradient(135deg, var(--accent), var(--accent-2)) !important;
        color: var(--on-accent) !important;
        border: 0 !important;
        box-shadow: 0 6px 22px -8px rgba(34,211,238,.6);
        font-weight: 600;
    }
    .hover\:bg-indigo-700:hover, .bg-indigo-600:hover, .bg-slate-800:hover {
        filter: brightness(1.12) saturate(1.1);
        box-shadow: 0 10px 30px -8px rgba(34,211,238,.75);
    }
    .bg-indigo-600, .bg-slate-800 { transition: filter .16s ease, box-shadow .16s ease; }
    .bg-emerald-600 { background: linear-gradient(135deg, var(--accent-3), var(--accent)) !important; color: var(--on-accent) !important; }
    /* bg-slate-900 is only ever a <pre> in this app — a code surface, not a button. */
    .bg-slate-900 { background: var(--code-bg) !important; border: 1px solid var(--line); }
    .text-slate-100, .text-slate-200, .text-slate-300 { color: #cfe0f2 !important; }

    /* --- Inputs -------------------------------------------------- */
    input, textarea, select {
        background: var(--input-bg) !important;
        color: var(--text) !important;
        border: 1px solid var(--line) !important;
        border-radius: 10px !important;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    html.light input, html.light textarea, html.light select { background: var(--input-bg) !important; }
    input:focus, textarea:focus, select:focus {
        outline: none !important;
        border-color: rgba(34,211,238,.6) !important;
        box-shadow: 0 0 0 3px rgba(34,211,238,.14) !important;
    }
    input::placeholder, textarea::placeholder { color: var(--dim); }
    input[type="color"] { padding: 2px; }
    input[type="checkbox"], input[type="radio"] { accent-color: var(--accent); width: 15px; height: 15px; }
    input[type="file"] { padding: 8px 10px; }
    input[type="file"]::file-selector-button {
        background: rgba(34,211,238,.1); color: var(--accent);
        border: 1px solid rgba(34,211,238,.32); border-radius: 8px;
        padding: 6px 14px; margin-right: 12px; cursor: pointer; font: inherit; font-size: 13px;
        transition: all .16s ease;
    }
    input[type="file"]::file-selector-button:hover {
        background: rgba(34,211,238,.2); box-shadow: 0 0 16px -5px rgba(34,211,238,.8);
    }
    /* Native dropdown lists render in the OS palette; keep options readable. */
    select option { background: var(--surface-solid); color: var(--text); }

    /* --- Code ---------------------------------------------------- */
    code {
        background: rgba(34,211,238,.09) !important;
        color: var(--accent) !important;
        border: 1px solid var(--line-soft);
        border-radius: 6px; padding: 1px 5px; font-size: .92em;
    }
    pre { background: var(--code-bg) !important; border: 1px solid var(--line); border-radius: var(--radius); }
    pre code { background: none !important; border: 0; color: #cfe6f5 !important; padding: 0; }

    /* --- Tables -------------------------------------------------- */
    table thead { color: var(--dim); text-transform: uppercase; letter-spacing: .09em; font-size: 10.5px; }
    table tbody tr { transition: background .15s ease; }
    table tbody tr:hover { background: rgba(34,211,238,.05); }

    /* --- Header: sticky glass bar -------------------------------- */
    header {
        position: sticky; top: 0; z-index: 40;
        background: var(--bar) !important;
        backdrop-filter: blur(16px) saturate(1.3);
        -webkit-backdrop-filter: blur(16px) saturate(1.3);
        border-bottom: 1px solid var(--line) !important;
    }
    html.light header { background: var(--bar) !important; }
    header a { transition: color .15s ease; }

    /* Wordmark gets the gradient treatment. */
    header .font-bold > a, header a.font-bold, .font-bold > a[href="/"] {
        background: linear-gradient(100deg, var(--text) 20%, var(--accent) 60%, var(--accent-2));
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: -.04em;
    }

    /* --- Toggles ------------------------------------------------- */
    .js-theme-toggle, .js-lang-toggle {
        cursor: pointer; text-decoration: none;
        border: 1px solid var(--line); border-radius: 10px;
        color: var(--muted) !important; background: rgba(255,255,255,.03);
        transition: all .16s ease;
    }
    .js-theme-toggle:hover, .js-lang-toggle:hover {
        border-color: rgba(34,211,238,.45) !important;
        color: var(--accent) !important;
        background: rgba(34,211,238,.08) !important;
        box-shadow: 0 0 18px -4px rgba(34,211,238,.5);
    }

    /* --- Misc ---------------------------------------------------- */
    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(125,211,252,.18); border-radius: 8px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(34,211,238,.4); }
    @media (prefers-reduced-motion: reduce) {
        body::after { animation: none; }
        * { transition: none !important; }
    }
    </style>
    @stack('head')
</head>
<body class="antialiased">
    @yield('body')

    <script>
        (function () {
            function isLight() { return document.documentElement.classList.contains('light'); }
            function ico() { return isLight() ? '🌙' : '☀'; }
            function sync() { document.querySelectorAll('.js-theme-toggle').forEach(function (b) { b.textContent = ico(); }); }
            document.addEventListener('click', function (e) {
                var b = e.target.closest('.js-theme-toggle');
                if (!b) return;
                var light = document.documentElement.classList.toggle('light');
                localStorage.theme = light ? 'light' : 'dark';
                sync();
            });
            sync();
        })();
    </script>
</body>
</html>
