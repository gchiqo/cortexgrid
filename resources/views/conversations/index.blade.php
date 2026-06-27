@extends('layout')
@section('title', 'საუბრები')
@section('body')
<div class="min-h-screen">
    <header class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="/" class="font-bold text-lg">GTUH <span class="text-indigo-600">AI</span></a>
            <div class="flex items-center gap-2">
                @include('partials.theme-toggle')
                <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">← პანელი</a>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-xl font-bold mb-6">საუბრები</h1>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            @if ($conversations->isEmpty())
                <p class="text-slate-400 text-sm p-6">ჯერ საუბრები არ არის. ჩასვი ვიჯეტი შენს საიტზე და სტუმრები დაიწყებენ წერას.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-slate-400 text-left border-b">
                        <tr><th class="py-2 px-4">საუბარი</th><th>ჩატბოტი</th><th>შეტყობინებები</th><th>განახლდა</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($conversations as $c)
                            <tr class="border-b hover:bg-slate-50">
                                <td class="py-2 px-4">
                                    <a href="/dashboard/conversations/{{ $c->id }}" class="text-indigo-600 hover:underline">
                                        {{ $c->title ?: 'საუბარი #'.$c->id }}
                                    </a>
                                </td>
                                <td class="text-slate-600">{{ $c->config?->name }}</td>
                                <td>{{ $c->messages_count }}</td>
                                <td class="text-slate-500">{{ $c->updated_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </main>
</div>
@endsection
