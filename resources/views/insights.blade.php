@extends('layout')
@section('title', 'ანალიტიკა')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span> · ანალიტიკა</div>
            <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">← პანელი</a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8 space-y-8">
        @php $total = $up + $down; $sat = $total ? round($up / $total * 100) : null; @endphp
        <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5"><div class="text-slate-500 text-sm">👍 მოეწონა</div><div class="text-3xl font-bold mt-1 text-emerald-600">{{ $up }}</div></div>
            <div class="bg-white rounded-xl shadow-sm p-5"><div class="text-slate-500 text-sm">👎 არ მოეწონა</div><div class="text-3xl font-bold mt-1 text-red-500">{{ $down }}</div></div>
            <div class="bg-white rounded-xl shadow-sm p-5"><div class="text-slate-500 text-sm">კმაყოფილება</div><div class="text-3xl font-bold mt-1">{{ $sat !== null ? $sat.'%' : '—' }}</div></div>
            <div class="bg-white rounded-xl shadow-sm p-5"><div class="text-slate-500 text-sm">უპასუხო კითხვები</div><div class="text-3xl font-bold mt-1 text-amber-500">{{ $unanswered }}</div></div>
        </section>

        <section>
            <h2 class="font-semibold text-lg mb-1">გასაუმჯობესებელი პასუხები (👎)</h2>
            <p class="text-slate-500 text-sm mb-4">ნახე კითხვა, პასუხი და მოძიებული წყაროები — ასე გაარკვევ პრობლემაა მონაცემებში, ჩანკინგში თუ პრომპტში.</p>
            @if ($items->isEmpty())
                <p class="text-slate-400 text-sm">ჯერ უარყოფითი შეფასება არ არის. ვიჯეტში 👎 დაჭერა აქ გამოჩნდება.</p>
            @else
                <div class="space-y-3">
                    @foreach ($items as $it)
                        <div class="bg-white rounded-xl shadow-sm p-5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded">{{ $it['chatbot'] ?? 'ჩატბოტი' }}</span>
                                <a href="/dashboard/conversations/{{ $it['conversation_id'] }}" class="text-xs text-indigo-600 hover:underline">საუბრის ნახვა →</a>
                            </div>
                            @if ($it['question'])<div class="text-sm"><span class="text-slate-400">კითხვა:</span> {{ $it['question'] }}</div>@endif
                            <div class="text-sm mt-1"><span class="text-slate-400">პასუხი:</span> {{ \Illuminate\Support\Str::limit($it['answer'], 240) }}</div>
                            @if (!empty($it['sources']))
                                <div class="text-xs text-slate-500 mt-2">წყაროები: {{ collect($it['sources'])->map(fn ($s) => '[#'.$s['ref'].'] '.($s['title'] ?? ''))->implode('  ') }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
</div>
@endsection
