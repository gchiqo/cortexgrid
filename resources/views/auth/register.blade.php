@extends('layout')
@section('title', __('auth.register'))
@push('head')
<style>
.auth-wrap{display:grid;grid-template-columns:1.05fr .95fr;min-height:100vh}
@media (max-width:900px){.auth-wrap{grid-template-columns:1fr}.auth-aside{display:none}}
.auth-aside{padding:48px 44px;display:flex;flex-direction:column;gap:18px;justify-content:center;
    border-inline-end:1px solid var(--line);background:rgba(6,10,20,.5);position:relative;overflow:hidden}
.auth-aside::after{content:'';position:absolute;inset:auto -20% -40% -20%;height:60%;
    background:radial-gradient(30rem 18rem at 40% 50%,rgba(34,211,238,.16),transparent 70%)}
.auth-brand{display:flex;align-items:center;gap:11px;text-decoration:none;font-size:17px;font-weight:600;
    color:var(--text);margin-bottom:8px}
.auth-brand b{color:var(--accent);font-weight:600}
.auth-brand .side-mark{width:32px;height:32px;border-radius:9px;display:grid;place-items:center;
    font-family:'JetBrains Mono',monospace;font-size:12.5px;font-weight:600;color:#04060d;
    background:linear-gradient(135deg,var(--accent),var(--accent-2));box-shadow:0 6px 20px -6px rgba(34,211,238,.8)}
.auth-h{font-size:30px;font-weight:700;line-height:1.15;letter-spacing:-.03em;
    background:linear-gradient(96deg,var(--text) 20%,var(--accent) 62%,var(--accent-2));
    -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;max-width:16ch}
.auth-p{color:var(--muted);font-size:14px;line-height:1.6;max-width:44ch}
.auth-list{display:flex;flex-direction:column;gap:9px;margin-top:6px}
.auth-list li{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--text)}
.auth-tick{width:6px;height:6px;border-radius:50%;background:var(--accent);box-shadow:0 0 9px var(--accent)}
.auth-engines{display:flex;gap:7px;flex-wrap:wrap;margin-top:14px}
.auth-engines span{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--dim);
    border:1px solid var(--line);border-radius:999px;padding:4px 11px}
.auth-side{display:flex;align-items:center;justify-content:center;padding:34px 22px}
.auth-card{width:100%;max-width:400px}
</style>
@endpush

@section('body')
<div class="fixed top-4 right-4 z-10 flex items-center gap-1">@include('partials.lang-toggle')@include('partials.theme-toggle')</div>
<div class="auth-wrap">
@include('partials.auth-aside')
<div class="auth-side"><div class="auth-card">
    <div class="bg-white rounded-2xl shadow p-8">
        <h1 class="text-2xl font-bold mb-1">{{ __('auth.register') }}</h1>
        <p class="text-slate-500 mb-6">{{ __('auth.register_subtitle') }}</p>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-3 text-sm">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="/register" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('auth.name') }}</label>
                <input name="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('auth.email') }}</label>
                <input name="email" type="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('auth.password') }}</label>
                <input name="password" type="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('auth.password_confirm') }}</label>
                <input name="password_confirmation" type="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg py-2.5 font-medium">
                {{ __('auth.register') }}
            </button>
        </form>

        <div class="my-5 flex items-center gap-3 text-slate-400 text-sm">
            <span class="h-px bg-slate-200 flex-1"></span> {{ __('auth.or') }} <span class="h-px bg-slate-200 flex-1"></span>
        </div>

        <a href="/auth/google"
           class="flex items-center justify-center gap-2 w-full border border-slate-300 rounded-lg py-2.5 font-medium hover:bg-slate-50">
            <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 4.75c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 1.46 14.97.5 12 .5A11 11 0 0 0 2.18 7.06l3.66 2.84C6.71 6.68 9.14 4.75 12 4.75z"/></svg>
            {{ __('auth.google_register') }}
        </a>

        <p class="text-center text-sm text-slate-500 mt-6">
            {{ __('auth.have_account') }} <a href="/login" class="text-indigo-600 font-medium">{{ __('auth.login') }}</a>
        </p>
    </div></div></div>
@endsection
