<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Feedback insights — the self-improving loop: 👍/👎 counts, unanswered questions,
 * and the recent 👎 answers (with question + retrieved sources) so the user can debug.
 */
class InsightsController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;

        $up = Message::where('tenant_id', $tenantId)->where('feedback', 1)->count();
        $down = Message::where('tenant_id', $tenantId)->where('feedback', -1)->count();
        $unanswered = Message::where('tenant_id', $tenantId)
            ->where('role', 'assistant')
            ->where('content', 'like', 'ბოდიში, ამ კითხვაზე%')
            ->count();

        $items = Message::where('tenant_id', $tenantId)
            ->where('feedback', -1)
            ->with('conversation.config')
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (Message $m) => [
                'chatbot' => $m->conversation?->config?->name,
                'conversation_id' => $m->conversation_id,
                'question' => Message::where('conversation_id', $m->conversation_id)
                    ->where('id', '<', $m->id)->where('role', 'user')->latest('id')->value('content'),
                'answer' => $m->content,
                'sources' => $m->sources ?? [],
            ]);

        return view('insights', compact('up', 'down', 'unanswered', 'items'));
    }
}
