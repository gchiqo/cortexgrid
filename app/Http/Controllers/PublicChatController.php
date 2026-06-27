<?php

namespace App\Http\Controllers;

use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Services\Rag\AskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /** Streaming chat (SSE) for the widget — tokens stream in, then a final event with sources + message_id. */
    public function chatStream(Request $request, AskService $ask): StreamedResponse|JsonResponse
    {
        $data = $request->validate([
            'public_key' => ['required', 'string'],
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
            'visitor_id' => ['nullable', 'string', 'max:100'],
        ]);

        $origin = $request->headers->get('Origin');
        $config = AiConfig::where('public_key', $data['public_key'])->where('widget_enabled', true)->first();
        if (! $config || ! $config->isDomainAllowed($origin)) {
            return response()->json(['error' => 'denied'], 403)->withHeaders($this->corsHeaders($config, $origin));
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

        $history = $conversation->messages()->orderBy('id')->get(['role', 'content'])
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->all();

        Message::create(['conversation_id' => $conversation->id, 'tenant_id' => $config->tenant_id, 'role' => 'user', 'content' => $data['message']]);

        $headers = $this->corsHeaders($config, $origin) + [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ];

        return response()->stream(function () use ($ask, $config, $conversation, $data, $history) {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            $emit = function (array $obj) {
                echo 'data: '.json_encode($obj, JSON_UNESCAPED_UNICODE)."\n\n";
                @ob_flush();
                @flush();
            };

            $result = $ask->answerStream(
                $config->tenant_id, $data['message'], $config, $history,
                fn (string $token) => $emit(['t' => $token])
            );

            $assistant = Message::create([
                'conversation_id' => $conversation->id,
                'tenant_id' => $config->tenant_id,
                'role' => 'assistant',
                'content' => $result['answer'],
                'sources' => $result['sources'],
                'input_tokens' => $result['usage']['input_tokens'],
                'output_tokens' => $result['usage']['output_tokens'],
            ]);

            $emit([
                'conversation_id' => $conversation->id,
                'message_id' => $assistant->id,
                'sources' => $result['sources'],
                'answered' => $result['answered'],
            ]);
        }, 200, $headers);
    }

    /** Lead capture from the widget. */
    public function lead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'public_key' => ['required', 'string'],
            'conversation_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:60'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $origin = $request->headers->get('Origin');
        $config = AiConfig::where('public_key', $data['public_key'])->first();
        if (! $config) {
            return response()->json(['error' => 'invalid_key'], 404)->withHeaders($this->corsHeaders(null, $origin));
        }
        if (empty($data['email']) && empty($data['phone'])) {
            return response()->json(['error' => 'no_contact'], 422)->withHeaders($this->corsHeaders($config, $origin));
        }

        Lead::create([
            'tenant_id' => $config->tenant_id,
            'ai_config_id' => $config->id,
            'conversation_id' => $data['conversation_id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
        ]);

        return response()->json(['ok' => true])->withHeaders($this->corsHeaders($config, $origin));
    }

    /** CORS preflight. */
    public function preflight(Request $request): Response
    {
        return $this->cors(response('', 204), null);
    }

    private function cors(Response $response, ?AiConfig $config, ?string $origin = null): Response
    {
        return $response->withHeaders($this->corsHeaders($config, $origin));
    }

    /** @return array<string,string> */
    private function corsHeaders(?AiConfig $config, ?string $origin): array
    {
        $allow = '*';
        if ($config && ! empty(array_filter($config->allowed_domains ?? []))) {
            $allow = $config->isDomainAllowed($origin) ? ($origin ?: 'null') : 'null';
        }

        return [
            'Access-Control-Allow-Origin' => $allow,
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
            'Vary' => 'Origin',
        ];
    }
}
