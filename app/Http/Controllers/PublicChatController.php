<?php

namespace App\Http\Controllers;

use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Rag\AskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public, browser-facing chat endpoint for the embeddable widget.
 * Authenticated by a chatbot's PUBLIC key (safe to expose) + CORS origin allowlist.
 */
class PublicChatController extends Controller
{
    public function chat(Request $request, AskService $ask): JsonResponse
    {
        $data = $request->validate([
            'public_key' => ['required', 'string'],
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
            'visitor_id' => ['nullable', 'string', 'max:100'],
        ]);

        $origin = $request->headers->get('Origin');

        $config = AiConfig::where('public_key', $data['public_key'])->where('widget_enabled', true)->first();
        if (! $config) {
            return $this->cors(response()->json(['error' => 'invalid_or_disabled_key'], 404), null);
        }

        if (! $config->isDomainAllowed($origin)) {
            return $this->cors(response()->json(['error' => 'origin_not_allowed'], 403), $config, $origin);
        }

        $conversation = ! empty($data['conversation_id'])
            ? Conversation::where('ai_config_id', $config->id)->find($data['conversation_id'])
            : null;

        if (! $conversation) {
            $conversation = Conversation::create([
                'tenant_id' => $config->tenant_id,
                'ai_config_id' => $config->id,
                'visitor_id' => $data['visitor_id'] ?? null,
                'title' => mb_substr($data['message'], 0, 60),
            ]);
        }

        // Prior turns (before inserting the current message) give the model memory.
        $history = $conversation->messages()
            ->orderBy('id')
            ->get(['role', 'content'])
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();

        Message::create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $config->tenant_id,
            'role' => 'user',
            'content' => $data['message'],
        ]);

        $result = $ask->answer($config->tenant_id, $data['message'], $config, history: $history);

        $assistant = Message::create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $config->tenant_id,
            'role' => 'assistant',
            'content' => $result['answer'],
            'sources' => $result['sources'],
            'input_tokens' => $result['usage']['input_tokens'],
            'output_tokens' => $result['usage']['output_tokens'],
        ]);

        return $this->cors(response()->json([
            'conversation_id' => $conversation->id,
            'message_id' => $assistant->id,
            'answer' => $result['answer'],
            'sources' => $result['sources'],
        ]), $config, $origin);
    }

    /** 👍/👎 from the widget on an assistant message. */
    public function feedback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'public_key' => ['required', 'string'],
            'message_id' => ['required', 'integer'],
            'value' => ['required', 'integer', 'in:-1,1'],
        ]);

        $origin = $request->headers->get('Origin');

        $config = AiConfig::where('public_key', $data['public_key'])->where('widget_enabled', true)->first();
        if (! $config) {
            return $this->cors(response()->json(['error' => 'invalid_key'], 404), null);
        }

        // The message must be an assistant message in a conversation of this chatbot.
        $message = Message::where('id', $data['message_id'])->where('role', 'assistant')->first();
        if ($message && Conversation::where('id', $message->conversation_id)->where('ai_config_id', $config->id)->exists()) {
            $message->update(['feedback' => $data['value']]);
        }

        return $this->cors(response()->json(['ok' => true]), $config, $origin);
    }

    /** CORS preflight. */
    public function preflight(Request $request): Response
    {
        return $this->cors(response('', 204), null);
    }

    private function cors(Response $response, ?AiConfig $config, ?string $origin = null): Response
    {
        $allow = '*';
        if ($config && ! empty(array_filter($config->allowed_domains ?? []))) {
            $allow = $config->isDomainAllowed($origin) ? ($origin ?: 'null') : 'null';
        }

        return $response->withHeaders([
            'Access-Control-Allow-Origin' => $allow,
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
            'Vary' => 'Origin',
        ]);
    }
}
