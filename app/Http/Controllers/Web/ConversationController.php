<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = Conversation::with('config')
            ->where('tenant_id', $request->user()->tenant_id)
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        return view('conversations.index', compact('conversations'));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        abort_unless($conversation->tenant_id === $request->user()->tenant_id, 403);

        $conversation->load('config', 'messages');

        $tokens = (int) $conversation->messages->sum(fn ($m) => $m->input_tokens + $m->output_tokens);

        return view('conversations.show', compact('conversation', 'tokens'));
    }
}
