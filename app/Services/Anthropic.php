<?php

namespace App\Services;

use Anthropic\Client;

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
