@extends('layout')
@section('title', 'შესვლა')
@section('body')
<div class="fixed top-4 right-4 z-10">@include('partials.theme-toggle')</div>
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow p-8">
        <h1 class="text-2xl font-bold mb-1">შესვლა</h1>
        <p class="text-slate-500 mb-6">GTUH AI პლატფორმა</p>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/login" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">ელ. ფოსტა</label>
                <input name="email" type="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">პაროლი</label>
                <input name="password" type="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember"> დამიმახსოვრე
            </label>
            <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg py-2.5 font-medium">
                შესვლა
            </button>
        </form>

        <div class="my-5 flex items-center gap-3 text-slate-400 text-sm">
            <span class="h-px bg-slate-200 flex-1"></span> ან <span class="h-px bg-slate-200 flex-1"></span>
        </div>

        <a href="/auth/google"
           class="flex items-center justify-center gap-2 w-full border border-slate-300 rounded-lg py-2.5 font-medium hover:bg-slate-50">
            <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 4.75c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 1.46 14.97.5 12 .5A11 11 0 0 0 2.18 7.06l3.66 2.84C6.71 6.68 9.14 4.75 12 4.75z"/></svg>
            Google-ით შესვლა
        </a>

        <p class="text-center text-sm text-slate-500 mt-6">
            არ გაქვს ანგარიში? <a href="/register" class="text-indigo-600 font-medium">რეგისტრაცია</a>
        </p>
    </div>
</div>
@endsection
