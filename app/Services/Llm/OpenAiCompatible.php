<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Generation over any OpenAI-compatible /chat/completions endpoint.
 *
 * Groq, OpenRouter, NVIDIA NIM, Cerebras and Together all speak this format,
 * so switching provider is a base_url + key + model change in .env, not code.
 */
class OpenAiCompatible implements TextGenerator
{
    public function __construct(
        private string $baseUrl,
        private string $key,
        private string $model,
        /** @var array<string,string> extra headers some providers want (e.g. OpenRouter attribution) */
        private array $headers = [],
    ) {}

    public function chat(
        string|array $system,
        array $messages,
        ?string $model = null,
        int $maxTokens = 1500,
        ?array $tools = null,
    ): array {
        $payload = [
            'model' => $model ?: $this->model,
            'max_tokens' => $maxTokens,
            'messages' => $this->buildMessages($system, $messages),
        ];
        if ($tools !== null && $tools !== []) {
            $payload['tools'] = $this->translateTools($tools);
        }

        $choice = $this->post($payload);
        $msg = $choice['body']['choices'][0]['message'] ?? [];

        return [
            'text' => (string) ($msg['content'] ?? ''),
            'input_tokens' => (int) ($choice['body']['usage']['prompt_tokens'] ?? 0),
            'output_tokens' => (int) ($choice['body']['usage']['completion_tokens'] ?? 0),
            'stop_reason' => $choice['body']['choices'][0]['finish_reason'] ?? null,
        ];
    }

    public function stream(
        string $system,
        array $messages,
        ?string $model,
        int $maxTokens,
        callable $onText,
    ): array {
        $response = Http::withToken($this->key)
            ->withHeaders($this->headers + ['content-type' => 'application/json'])
            ->withOptions(['stream' => true])
            ->timeout(120)
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $model ?: $this->model,
                'max_tokens' => $maxTokens,
                'messages' => $this->buildMessages($system, $messages),
                'stream' => true,
                'stream_options' => ['include_usage' => true],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('LLM stream failed: '.$response->status().' '.$response->body());
        }

        $body = $response->toPsrResponse()->getBody();
        $text = '';
        $in = 0;
        $out = 0;
        $buf = '';

        while (! $body->eof()) {
            $buf .= $body->read(2048);

            // SSE frames are separated by a blank line.
            while (($pos = strpos($buf, "\n\n")) !== false) {
                $frame = substr($buf, 0, $pos);
                $buf = substr($buf, $pos + 2);

                foreach (explode("\n", $frame) as $line) {
                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }
                    $raw = trim(substr($line, 5));
                    if ($raw === '' || $raw === '[DONE]') {
                        continue;
                    }
                    $data = json_decode($raw, true);
                    if (! is_array($data)) {
                        continue;
                    }
                    $delta = $data['choices'][0]['delta']['content'] ?? '';
                    if (is_string($delta) && $delta !== '') {
                        $text .= $delta;
                        $onText($delta);
                    }
                    // Usage arrives on the final frame when include_usage is set.
                    $in = (int) ($data['usage']['prompt_tokens'] ?? $in);
                    $out = (int) ($data['usage']['completion_tokens'] ?? $out);
                }
            }
        }

        return ['text' => $text, 'input_tokens' => $in, 'output_tokens' => $out];
    }

    public function agentChat(
        string|array $system,
        array $messages,
        array $tools,
        callable $execute,
        ?string $model = null,
        int $maxTokens = 2000,
        int $maxIters = 5,
    ): array {
        $model = $model ?: $this->model;
        $msgs = $this->buildMessages($system, $messages);
        $toolDefs = $this->translateTools($tools);

        $inTok = 0;
        $outTok = 0;
        $toolCalls = [];
        $text = '';
        $stop = null;

        for ($i = 0; $i < $maxIters; $i++) {
            $res = $this->post([
                'model' => $model,
                'max_tokens' => $maxTokens,
                'messages' => $msgs,
                'tools' => $toolDefs,
            ]);

            $body = $res['body'];
            $inTok += (int) ($body['usage']['prompt_tokens'] ?? 0);
            $outTok += (int) ($body['usage']['completion_tokens'] ?? 0);

            $msg = $body['choices'][0]['message'] ?? [];
            $stop = $body['choices'][0]['finish_reason'] ?? null;
            $text = (string) ($msg['content'] ?? '');

            $calls = $msg['tool_calls'] ?? [];
            if ($calls === []) {
                break;
            }

            // Echo the assistant turn back verbatim, then one tool message per call.
            $msgs[] = [
                'role' => 'assistant',
                'content' => $msg['content'] ?? '',
                'tool_calls' => $calls,
            ];

            foreach ($calls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode((string) ($call['function']['arguments'] ?? '{}'), true) ?: [];
                try {
                    $out = (string) $execute($name, $args);
                } catch (\Throwable $e) {
                    report($e);
                    $out = 'შეცდომა: '.$e->getMessage();
                }
                $toolCalls[] = ['name' => $name, 'input' => $args, 'result' => mb_substr($out, 0, 300)];
                $msgs[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? $name,
                    'content' => $out,
                ];
            }
        }

        return [
            'text' => $text,
            'input_tokens' => $inTok,
            'output_tokens' => $outTok,
            'tool_calls' => $toolCalls,
            'stop_reason' => $stop,
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{body:array<string,mixed>}
     */
    private function post(array $payload): array
    {
        $resp = Http::withToken($this->key)
            ->withHeaders($this->headers)
            ->timeout(120)
            ->retry(2, 500)
            ->post($this->baseUrl.'/chat/completions', $payload);

        if ($resp->failed()) {
            throw new RuntimeException('LLM chat failed: '.$resp->status().' '.$resp->body());
        }

        return ['body' => $resp->json() ?? []];
    }

    /**
     * Anthropic keeps the system prompt in its own field; OpenAI wants it as
     * the first message. Anthropic content can also be an array of blocks.
     *
     * @param  string|array<int,array<string,mixed>>  $system
     * @param  array<int,array<string,mixed>>  $messages
     * @return list<array<string,mixed>>
     */
    private function buildMessages(string|array $system, array $messages): array
    {
        $out = [];

        $sys = is_array($system) ? $this->flatten($system) : $system;
        if (trim($sys) !== '') {
            $out[] = ['role' => 'system', 'content' => $sys];
        }

        foreach (array_values($messages) as $m) {
            $out[] = [
                'role' => $m['role'] ?? 'user',
                'content' => is_array($m['content'] ?? null) ? $this->flatten($m['content']) : (string) ($m['content'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Flatten Anthropic-style content blocks to plain text.
     *
     * @param  array<int,mixed>  $blocks
     */
    private function flatten(array $blocks): string
    {
        $parts = [];
        foreach ($blocks as $b) {
            if (is_string($b)) {
                $parts[] = $b;
            } elseif (is_array($b) && isset($b['text'])) {
                $parts[] = (string) $b['text'];
            }
        }

        return implode("\n", $parts);
    }

    /**
     * ToolRegistry emits Anthropic's shape; OpenAI nests it under `function`
     * and calls the schema `parameters`.
     *
     * @param  list<array<string,mixed>>  $tools
     * @return list<array<string,mixed>>
     */
    private function translateTools(array $tools): array
    {
        return array_values(array_map(fn (array $t) => [
            'type' => 'function',
            'function' => [
                'name' => $t['name'] ?? '',
                'description' => $t['description'] ?? '',
                'parameters' => $t['inputSchema'] ?? $t['input_schema'] ?? ['type' => 'object', 'properties' => []],
            ],
        ], $tools));
    }
}
