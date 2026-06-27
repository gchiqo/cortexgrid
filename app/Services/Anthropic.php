<?php

namespace App\Services;

use Anthropic\Client;
use Illuminate\Support\Facades\Http;

/**
 * Anthropic Claude — the main reasoning brain: grounded Georgian answers,
 * agentic function calling, structured extraction.
 */
class Anthropic
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(apiKey: (string) config('services.anthropic.key'));
    }

    /**
     * Chat completion. Returns ['text' => string, 'input_tokens' => int, 'output_tokens' => int, 'stop_reason' => ?string].
     *
     * @param  string|array<int,array<string,mixed>>  $system
     * @param  array<int,array{role:string,content:string}>  $messages  full turn list (user/assistant)
     * @param  array<int,array<string,mixed>>|null  $tools
     */
    public function chat(
        string|array $system,
        array $messages,
        ?string $model = null,
        int $maxTokens = 1500,
        ?array $tools = null,
    ): array {
        $args = [
            'maxTokens' => $maxTokens,
            'model' => $model ?? config('services.anthropic.model'),
            'system' => $system,
            'messages' => array_values($messages),
        ];

        if ($tools !== null) {
            $args['tools'] = $tools;
        }

        $message = $this->client->messages->create(...$args);

        $text = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $text .= $block->text;
            }
        }

        return [
            'text' => $text,
            'input_tokens' => $message->usage->inputTokens ?? 0,
            'output_tokens' => $message->usage->outputTokens ?? 0,
            'stop_reason' => $message->stopReason ?? null,
            'message' => $message,
        ];
    }

    /**
     * Streaming completion via raw SSE. Calls $onText($delta) per token; returns final usage + text.
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     */
    public function stream(string $system, array $messages, ?string $model, int $maxTokens, callable $onText): array
    {
        $response = Http::withHeaders([
            'x-api-key' => (string) config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->withOptions(['stream' => true])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model ?? config('services.anthropic.model'),
            'max_tokens' => $maxTokens,
            'system' => $system,
            'messages' => array_values($messages),
            'stream' => true,
        ]);

        $body = $response->toPsrResponse()->getBody();
        $text = '';
        $in = 0;
        $out = 0;
        $buf = '';

        while (! $body->eof()) {
            $buf .= $body->read(2048);
            while (($pos = strpos($buf, "\n\n")) !== false) {
                $chunk = substr($buf, 0, $pos);
                $buf = substr($buf, $pos + 2);
                if (! preg_match('/data: (.*)/s', $chunk, $m)) {
                    continue;
                }
                $data = json_decode(trim($m[1]), true);
                if (! is_array($data)) {
                    continue;
                }
                $type = $data['type'] ?? '';
                if ($type === 'content_block_delta' && (($data['delta']['type'] ?? '') === 'text_delta')) {
                    $t = $data['delta']['text'] ?? '';
                    if ($t !== '') {
                        $text .= $t;
                        $onText($t);
                    }
                } elseif ($type === 'message_start') {
                    $in = $data['message']['usage']['input_tokens'] ?? 0;
                } elseif ($type === 'message_delta') {
                    $out = $data['usage']['output_tokens'] ?? $out;
                }
            }
        }

        return ['text' => $text, 'input_tokens' => $in, 'output_tokens' => $out];
    }

    /**
     * Agentic chat: runs the tool-use loop. $execute(name, input[]) => result string.
     * Returns ['text', 'input_tokens', 'output_tokens', 'tool_calls' => [...], 'stop_reason'].
     *
     * @param  string|array<int,array<string,mixed>>  $system
     * @param  array<int,array<string,mixed>>  $messages
     * @param  list<array<string,mixed>>  $tools
     */
    public function agentChat(
        string|array $system,
        array $messages,
        array $tools,
        callable $execute,
        ?string $model = null,
        int $maxTokens = 2000,
        int $maxIters = 5,
    ): array {
        $model = $model ?? config('services.anthropic.model');
        $msgs = array_values($messages);

        $inTok = 0;
        $outTok = 0;
        $toolCalls = [];
        $text = '';
        $stop = null;

        for ($i = 0; $i < $maxIters; $i++) {
            $message = $this->client->messages->create(
                maxTokens: $maxTokens,
                model: $model,
                system: $system,
                tools: $tools,
                messages: $msgs,
            );

            $inTok += $message->usage->inputTokens ?? 0;
            $outTok += $message->usage->outputTokens ?? 0;
            $stop = $message->stopReason ?? null;

            $text = '';
            foreach ($message->content as $block) {
                if (($block->type ?? null) === 'text') {
                    $text .= $block->text;
                }
            }

            if ($stop !== 'tool_use') {
                break;
            }

            // Execute each requested tool, collect tool_result blocks.
            $toolResults = [];
            foreach ($message->content as $block) {
                if (($block->type ?? null) !== 'tool_use') {
                    continue;
                }
                $input = json_decode(json_encode($block->input), true) ?: [];
                try {
                    $out = (string) $execute($block->name, $input);
                } catch (\Throwable $e) {
                    report($e);
                    $out = 'შეცდომა: '.$e->getMessage();
                }
                $toolCalls[] = ['name' => $block->name, 'input' => $input, 'result' => mb_substr($out, 0, 300)];
                $toolResults[] = ['type' => 'tool_result', 'toolUseID' => $block->id, 'content' => $out];
            }

            // Echo the assistant's content back, then return the tool results.
            $msgs[] = ['role' => 'assistant', 'content' => $message->content];
            $msgs[] = ['role' => 'user', 'content' => $toolResults];
        }

        return [
            'text' => $text,
            'input_tokens' => $inTok,
            'output_tokens' => $outTok,
            'tool_calls' => $toolCalls,
            'stop_reason' => $stop,
        ];
    }
}
