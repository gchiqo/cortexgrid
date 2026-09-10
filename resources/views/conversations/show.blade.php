@extends('layout-app')
@section('title', __('conversations.one'))
@section('heading', __('conversations.one'))
@section('actions')
    <a href="/dashboard/conversations" class="cmd-open" style="text-decoration:none">{{ __('conversations.back') }}</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm p-5 flex items-center justify-between">
            <div>
                <div class="font-semibold">{{ $conversation->title ?: __('conversations.untitled', ['id' => $conversation->id]) }}</div>
                <div class="text-sm text-slate-500">{{ __('conversations.chatbot_label') }}: {{ $conversation->config?->name }}</div>
            </div>
            <div class="text-right text-sm">
                <div class="text-slate-500">{{ trans_choice('conversations.message_count', $conversation->messages->count(), ['count' => $conversation->messages->count()]) }}</div>
                <div class="text-slate-500">{{ trans_choice('conversations.token_count', $tokens, ['count' => number_format($tokens)]) }}</div>
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
                                {{ __('conversations.sources') }}: {{ collect($m->sources)->map(fn ($s) => '[#'.$s['ref'].'] '.($s['title'] ?? ''))->implode('  ') }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
@endsection
