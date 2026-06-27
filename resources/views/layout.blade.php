<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GTUH AI პლატფორმა')</title>
    <script>if (localStorage.theme === 'dark') document.documentElement.classList.add('dark');</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <style>
        /* --- Dark theme: override the common light utilities when html.dark is set --- */
        html.dark body { background:#0f172a; color:#e2e8f0; }
        html.dark .bg-white { background:#1e293b !important; }
        html.dark .bg-slate-50 { background:#0f172a !important; }
        html.dark .bg-slate-100 { background:#334155 !important; }
        html.dark .bg-gradient-to-b { background-image:none !important; background:#0f172a !important; }
        html.dark .bg-indigo-50 { background:rgba(99,102,241,.15) !important; }
        html.dark .bg-emerald-50 { background:rgba(16,185,129,.13) !important; }
        html.dark .bg-red-50 { background:rgba(239,68,68,.13) !important; }
        html.dark .bg-amber-50 { background:rgba(245,158,11,.13) !important; }
        html.dark .bg-indigo-100 { background:rgba(99,102,241,.22) !important; }
        html.dark .text-slate-900, html.dark .text-slate-800 { color:#e2e8f0 !important; }
        html.dark .text-slate-700 { color:#cbd5e1 !important; }
        html.dark .text-slate-600, html.dark .text-slate-500 { color:#94a3b8 !important; }
        html.dark .text-slate-400 { color:#64748b !important; }
        html.dark .text-indigo-600, html.dark .text-indigo-700 { color:#818cf8 !important; }
        html.dark .text-indigo-900 { color:#c7d2fe !important; }
        html.dark .text-emerald-800, html.dark .text-emerald-700 { color:#6ee7b7 !important; }
        html.dark .text-red-700 { color:#fca5a5 !important; }
        html.dark .border, html.dark .border-b, html.dark .border-t,
        html.dark .border-slate-200, html.dark .border-slate-300 { border-color:#334155 !important; }
        html.dark .border-indigo-200 { border-color:rgba(99,102,241,.4) !important; }
        html.dark .border-emerald-200 { border-color:rgba(16,185,129,.4) !important; }
        html.dark .border-red-200 { border-color:rgba(239,68,68,.4) !important; }
        html.dark .divide-slate-200 > * { border-color:#334155 !important; }
        html.dark .shadow-sm, html.dark .shadow, html.dark .shadow-2xl { box-shadow:0 1px 3px rgba(0,0,0,.4) !important; }
        html.dark input, html.dark textarea, html.dark select {
            background:#0f172a !important; color:#e2e8f0 !important; border-color:#334155 !important;
        }
        html.dark input::placeholder, html.dark textarea::placeholder { color:#64748b; }
        html.dark code { background:#334155; color:#e2e8f0; }
        html.dark table thead { color:#64748b; }
        /* theme toggle button */
        #themeToggle { position:fixed; left:18px; bottom:18px; width:44px; height:44px; border-radius:50%;
            background:#fff; border:1px solid #e2e8f0; box-shadow:0 4px 14px rgba(0,0,0,.12); cursor:pointer;
            font-size:18px; display:flex; align-items:center; justify-content:center; z-index:2147482000; }
        html.dark #themeToggle { background:#1e293b; border-color:#334155; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    @yield('body')

    <button id="themeToggle" onclick="(function(){var d=document.documentElement.classList.toggle('dark');localStorage.theme=d?'dark':'light';document.getElementById('themeToggle').textContent=d?'☀️':'🌙';})()" title="თემის გადართვა">🌙</button>
    <script>document.getElementById('themeToggle').textContent = document.documentElement.classList.contains('dark') ? '☀️' : '🌙';</script>
</body>
</html>
