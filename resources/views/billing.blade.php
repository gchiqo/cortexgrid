@extends('layout')
@section('title', 'ბილინგი')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg"><a href="/">GTUH <span class="text-indigo-600">AI</span></a> · ბილინგი</div>
            <div class="flex items-center gap-2">
                @include('partials.theme-toggle')
                <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">← პანელი</a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8 space-y-8">
        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        {{-- Balance --}}
        <section class="bg-white rounded-xl shadow-sm p-6 flex items-center justify-between">
            <div>
                <div class="text-slate-500 text-sm">ბალანსი</div>
                <div class="text-4xl font-bold mt-1">{{ number_format($credits) }} <span class="text-lg text-slate-400 font-normal">კრედიტი</span></div>
                <div class="text-xs text-slate-400 mt-1">≈ {{ number_format(intdiv($credits, 1000)) }} პასუხი (1 კრედიტი ≈ 1 ტოკენი)</div>
            </div>
            <div class="text-5xl">💳</div>
        </section>

        {{-- Buy --}}
        <section>
            <h2 class="font-semibold text-lg mb-4">კრედიტების შევსება</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach ($packs as $i => $pack)
                    <form method="POST" action="{{ route('flitt.buy') }}" class="bg-white rounded-xl shadow-sm p-5 text-center">
                        @csrf
                        <input type="hidden" name="pack" value="{{ $i }}">
                        <div class="text-2xl font-bold">{{ number_format($pack['credits']) }}</div>
                        <div class="text-slate-500 text-sm">კრედიტი</div>
                        <button class="mt-4 w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg py-2.5 font-medium">{{ $pack['gel'] }} ₾ — ყიდვა</button>
                    </form>
                @endforeach
            </div>
            <p class="text-xs text-slate-400 mt-3">გადახდა მუშავდება Flitt-ით. სატესტო რეჟიმი — გამოიყენე სატესტო ბარათი.</p>
        </section>

        {{-- History --}}
        <section class="bg-white rounded-xl shadow-sm overflow-hidden">
            <h2 class="font-semibold text-lg p-5 pb-3">გადახდების ისტორია</h2>
            @if ($payments->isEmpty())
                <p class="text-slate-400 text-sm px-5 pb-5">ჯერ გადახდები არ არის.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-slate-400 text-left border-b"><tr><th class="py-2 px-5">თანხა</th><th>კრედიტი</th><th>სტატუსი</th><th>თარიღი</th></tr></thead>
                    <tbody>
                        @foreach ($payments as $p)
                            <tr class="border-b">
                                <td class="py-2 px-5">{{ $p->amount_gel }} ₾</td>
                                <td>{{ number_format($p->credits) }}</td>
                                <td>
                                    <span class="text-xs px-2 py-0.5 rounded {{ $p->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($p->status === 'failed' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700') }}">{{ $p->status }}</span>
                                </td>
                                <td class="text-slate-500">{{ $p->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </main>
</div>
@endsection
