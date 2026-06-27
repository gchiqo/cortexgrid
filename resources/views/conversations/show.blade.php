@extends('layout')
@section('title', 'საუბარი')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="/" class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span></a>
            <a href="/dashboard/conversations" class="text-sm text-slate-600 hover:text-slate-900">← საუბრები</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8 space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-5 flex items-center justify-between">
            <div>
                <div class="font-semibold">{{ $conversation->title ?: 'საუბარი #'.$conversation->id }}</div>
                <div class="text-sm text-slate-500">ჩატბოტი: {{ $conversation->config?->name }}</div>
            </div>
            <div class="text-right text-sm">
                <div class="text-slate-500">{{ $conversation->messages->count() }} შეტყობინება</div>
                <div class="text-slate-500">{{ number_format($tokens) }} ტოკენი</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 space-y-3">
            @foreach ($conversation->messages as $m)
                <div class="flex {{ $m->role === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[80%] rounded-2xl px-4 py-2 text-sm whitespace-pre-wrap
                        {{ $m->role === 'user' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-800' }}">
                        {{ $m->content }}
                        @if ($m->role === 'assistant' && !empty($m->sources))
                            <div class="mt-1 text-xs text-slate-500">
                                წყაროები: {{ collect($m->sources)->map(fn ($s) => '[#'.$s['ref'].'] '.($s['title'] ?? ''))->implode('  ') }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </main>
</div>
@endsection
