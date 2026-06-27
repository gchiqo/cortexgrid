@extends('layout')
@section('title', 'პანელი')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="/" class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span></a>
            <div class="flex items-center gap-4 text-sm">
                <a href="/dashboard/console" class="text-slate-600 hover:text-indigo-600">ტესტ-კონსოლი</a>
                <a href="/dashboard/docs" class="text-slate-600 hover:text-indigo-600">API</a>
                <a href="/dashboard/insights" class="text-slate-600 hover:text-indigo-600">ანალიტიკა</a>
                <a href="/dashboard/conversations" class="text-slate-600 hover:text-indigo-600">საუბრები</a>
                @include('partials.theme-toggle')
                <span class="text-slate-500">{{ $user->email }}</span>
                <form method="POST" action="/logout">@csrf
                    <button class="text-slate-600 hover:text-red-600">გასვლა</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8 space-y-8">

        @if (session('new_api_key'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4">
                <div class="font-medium text-emerald-800">ახალი API გასაღები (ნაჩვენებია მხოლოდ ერთხელ):</div>
                <code class="block mt-2 bg-white border rounded px-3 py-2 text-sm break-all">{{ session('new_api_key') }}</code>
            </div>
        @endif
        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        {{-- Usage --}}
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-slate-500 text-sm">ჩატვირთული დოკუმენტები</div>
                <div class="text-3xl font-bold mt-1">{{ $usage['ingest'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-slate-500 text-sm">კითხვები</div>
                <div class="text-3xl font-bold mt-1">{{ $usage['query'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-slate-500 text-sm">გამოყენებული ტოკენები</div>
                <div class="text-3xl font-bold mt-1">{{ number_format($usage['tokens']) }}</div>
            </div>
        </section>

        {{-- Datasets --}}
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-lg">დატასეტები</h2>
            </div>
            <p class="text-slate-500 text-sm mb-4">თითო დატასეტი = ერთი ბიზნესი/თემა. შეიძლება მრავალი ფაილით/წყაროთი შეივსოს და მრავალი ჩატბოტი ჰქონდეს.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($datasets as $ds)
                    <a href="/dashboard/datasets/{{ $ds->id }}" class="block bg-white rounded-xl shadow-sm p-5 hover:shadow transition">
                        <div class="font-semibold">{{ $ds->name }}</div>
                        @if ($ds->description)<p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ $ds->description }}</p>@endif
                        <div class="flex gap-3 mt-3 text-xs text-slate-500">
                            <span>{{ $ds->sources_count }} წყარო</span>
                            <span>·</span>
                            <span>{{ $ds->ai_configs_count }} ჩატბოტი</span>
                        </div>
                    </a>
                @endforeach

                {{-- New dataset card --}}
                <form method="POST" action="/dashboard/datasets" class="bg-white rounded-xl shadow-sm p-5 border-2 border-dashed border-slate-200 space-y-2">
                    @csrf
                    <div class="font-medium text-slate-700">+ ახალი დატასეტი</div>
                    <input name="name" required placeholder="მაგ. კომპიუტერების მაღაზია"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input name="description" placeholder="აღწერა (არასავალდებულო)"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg py-2 text-sm font-medium">შექმნა</button>
                </form>
            </div>
        </section>

        {{-- API keys --}}
        <section class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-lg mb-4">API გასაღებები</h2>
            <form method="POST" action="/dashboard/keys" class="flex gap-2 mb-4">@csrf
                <input name="label" placeholder="სახელი (არასავალდებულო)"
                       class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <button class="bg-slate-800 text-white rounded-lg px-4 py-2 text-sm font-medium">ახალი</button>
            </form>
            <table class="w-full text-sm">
                <thead class="text-slate-400 text-left">
                    <tr><th class="py-1">პრეფიქსი</th><th>სტატუსი</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($apiKeys as $key)
                        <tr class="border-t">
                            <td class="py-2"><code>{{ $key->prefix }}…</code> <span class="text-slate-400">{{ $key->label }}</span></td>
                            <td>@if ($key->revoked_at)<span class="text-red-500">გაუქმებული</span>@else<span class="text-emerald-600">აქტიური</span>@endif</td>
                            <td class="text-right">
                                @unless ($key->revoked_at)
                                    <form method="POST" action="/dashboard/keys/{{ $key->id }}/revoke">@csrf
                                        <button class="text-red-500 hover:underline">გაუქმება</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </main>
</div>
@endsection
