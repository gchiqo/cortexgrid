@extends('layout')
@section('title', 'GTUH AI — ცოდნის პლატფორმა')
@section('body')
<div class="min-h-screen bg-gradient-to-b from-slate-50 to-indigo-50/40">
    <header class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span></div>
        <div class="flex items-center gap-3 text-sm">
            <a href="/login" class="text-slate-600 hover:text-slate-900 px-3 py-2">შესვლა</a>
            <a href="/register" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 font-medium">დაწყება</a>
        </div>
    </header>

    {{-- Hero --}}
    <section class="max-w-4xl mx-auto px-4 text-center pt-16 pb-10">
        <div class="inline-block text-xs font-medium bg-indigo-100 text-indigo-700 rounded-full px-3 py-1 mb-5">
            ცოდნის AI პლატფორმა — არა უბრალოდ ჩატბოტი
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight">
            შენი მონაცემები →<br><span class="text-indigo-600">ინტელექტუალური AI აგენტები</span>
        </h1>
        <p class="text-lg text-slate-500 mt-5 max-w-2xl mx-auto">
            დააკავშირე ფაილები და მონაცემები, პლატფორმა გაიგებს მათ, და მიიღე ქართულენოვანი AI აგენტები,
            რომლებსაც შენს საიტზე ერთი კოდით ჩასვამ.
        </p>
        <div class="flex items-center justify-center gap-3 mt-8">
            <a href="/register" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-6 py-3 font-medium">უფასოდ დაწყება</a>
            <a href="/login" class="border border-slate-300 hover:bg-white rounded-lg px-6 py-3 font-medium">შესვლა</a>
        </div>
    </section>

    {{-- Animated data flow --}}
    <section class="max-w-5xl mx-auto px-4 py-12">
        <p class="text-center text-sm text-slate-400 mb-8">როგორ მუშავდება მონაცემები</p>
        <div class="flow">
            @foreach ([['🔌','დაკავშირება'],['📥','იმპორტი'],['✂️','დაყოფა'],['🧠','ემბედინგი'],['🔍','ჰიბრიდული ძებნა'],['🤖','AI აგენტი'],['💬','ვიჯეტი']] as $i => $s)
                <div class="flow-node" style="--d: {{ $i * 0.35 }}s">
                    <div class="flow-dot">{{ $s[0] }}</div>
                    <span>{{ $s[1] }}</span>
                </div>
                @if (!$loop->last)<div class="flow-link"><span class="flow-packet" style="--d: {{ $i * 0.35 }}s"></span></div>@endif
            @endforeach
        </div>
    </section>

    {{-- Features --}}
    <section class="max-w-6xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach ([
                ['📂','დატასეტები','ერთი ბიზნესი = ერთი დატასეტი, შევსებული მრავალი ფაილით (PDF, CSV, XLSX) ან API-ით.'],
                ['🤖','მრავალი AI აგენტი','თითო დატასეტზე რამდენიმე აგენტი — მომხმარებლისთვის და ადმინისთვის, განსხვავებული ქცევით.'],
                ['🔎','ჰიბრიდული ძებნა','ვექტორული (pgvector) + ლექსიკური (BM25) ძებნა, შერწყმული RRF-ით — ზუსტი, ციტირებული პასუხები.'],
                ['🪟','Glass-box კონსოლი','რეალურ დროში ნახე როგორ ფიქრობს სისტემა — embedding, ძებნა, შერწყმა, Claude.'],
                ['📊','ცოდნის მკვლევარი','პლატფორმა აანალიზებს მონაცემებს — ბრენდები, კატეგორიები, კავშირები, ნაკლული ინფო.'],
                ['🛠️','მოქმედი აგენტები','ადმინ-აგენტი ჩატით ამატებს და აახლებს მონაცემებს — მაშინვე ძებნადს.'],
            ] as $f)
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="text-3xl mb-3">{{ $f[0] }}</div>
                    <div class="font-semibold mb-1">{{ $f[1] }}</div>
                    <p class="text-sm text-slate-500">{{ $f[2] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="max-w-3xl mx-auto px-4 py-16 text-center">
        <h2 class="text-2xl font-bold">მზად ხარ?</h2>
        <p class="text-slate-500 mt-2">შექმენი ანგარიში და რამდენიმე წუთში გექნება მუშა AI აგენტი შენს მონაცემებზე.</p>
        <a href="/register" class="inline-block mt-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-8 py-3 font-medium">დაწყება</a>
    </section>

    <footer class="text-center text-slate-400 text-xs py-8">GTUH AI · Technological Hackathon 2026</footer>
</div>

<style>
.flow{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:nowrap;overflow-x:auto;padding:8px 4px}
.flow-node{display:flex;flex-direction:column;align-items:center;gap:8px;flex:0 0 auto;width:72px}
.flow-node span{font-size:11px;color:#64748b;text-align:center}
.flow-dot{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;
    background:#fff;border:2px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,.04);animation:nodePulse 2.45s ease-in-out infinite;animation-delay:var(--d)}
@keyframes nodePulse{0%,72%,100%{border-color:#e2e8f0;transform:scale(1)}82%{border-color:#6366f1;transform:scale(1.12);box-shadow:0 0 0 8px rgba(99,102,241,.12)}}
.flow-link{flex:1;height:2px;background:#e2e8f0;margin-top:25px;position:relative;min-width:18px;border-radius:2px}
.flow-packet{position:absolute;top:-2px;left:0;width:6px;height:6px;border-radius:50%;background:#6366f1;
    box-shadow:0 0 8px #6366f1;animation:packet 2.45s ease-in-out infinite;animation-delay:var(--d)}
@keyframes packet{0%,72%{left:0;opacity:0}74%{opacity:1}100%{left:100%;opacity:0}}
</style>
@endsection
