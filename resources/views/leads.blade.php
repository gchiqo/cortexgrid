@extends('layout')
@section('title', 'ლიდები')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg"><a href="/">GTUH <span class="text-indigo-600">AI</span></a> · ლიდები</div>
            <div class="flex items-center gap-2">
                @include('partials.theme-toggle')
                <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">← პანელი</a>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-xl font-bold mb-1">ლიდები</h1>
        <p class="text-slate-500 text-sm mb-6">ვიჯეტში დატოვებული კონტაქტები (როცა ბოტმა ვერ უპასუხა ან მომხმარებელმა თავად დატოვა).</p>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            @if ($leads->isEmpty())
                <p class="text-slate-400 text-sm p-6">ჯერ ლიდები არ არის.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-slate-400 text-left border-b">
                        <tr><th class="py-2 px-4">სახელი</th><th>კონტაქტი</th><th>ჩატბოტი</th><th>თარიღი</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($leads as $lead)
                            <tr class="border-b hover:bg-slate-50">
                                <td class="py-2 px-4">{{ $lead->name ?: '—' }}</td>
                                <td>
                                    @if ($lead->email)<a href="mailto:{{ $lead->email }}" class="text-indigo-600 hover:underline">{{ $lead->email }}</a>@endif
                                    @if ($lead->phone)<span class="text-slate-600">{{ $lead->phone }}</span>@endif
                                </td>
                                <td class="text-slate-600">{{ $lead->config?->name ?? '—' }}</td>
                                <td class="text-slate-500">{{ $lead->created_at?->diffForHumans() }}</td>
                                <td class="text-right pr-4">
                                    @if ($lead->conversation_id)
                                        <a href="/dashboard/conversations/{{ $lead->conversation_id }}" class="text-xs text-indigo-600 hover:underline">საუბარი →</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </main>
</div>
@endsection
