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
     * Single-turn chat. Returns ['text' => string, 'input_tokens' => int, 'output_tokens' => int, 'stop_reason' => ?string].
     *
     * @param  string|array<int,array<string,mixed>>  $system
     * @param  array<int,array<string,mixed>>|null  $tools
     */
    public function chat(
        string|array $system,
        string $userMessage,
        ?string $model = null,
        int $maxTokens = 1500,
        ?array $tools = null,
    ): array {
        $args = [
            'maxTokens' => $maxTokens,
            'model' => $model ?? config('services.anthropic.model'),
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $userMessage]],
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
}
