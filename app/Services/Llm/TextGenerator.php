<?php

namespace App\Services\Llm;

/**
 * The answer-generation brain, behind one interface so the provider is a
 * config choice rather than a code change.
 *
 * Tool definitions are passed in Anthropic's shape (name / description /
 * inputSchema) because that is what ToolRegistry emits; an implementation
 * targeting another wire format translates them itself.
 */
interface TextGenerator
{
    /**
     * One completion.
     *
     * @param  string|array<int,array<string,mixed>>  $system
     * @param  array<int,array<string,mixed>>  $messages
     * @param  array<int,array<string,mixed>>|null  $tools
     * @return array{text:string,input_tokens:int,output_tokens:int,stop_reason:?string}
     */
    public function chat(
        string|array $system,
        array $messages,
        ?string $model = null,
        int $maxTokens = 1500,
        ?array $tools = null,
    ): array;

    /**
     * Streaming completion. $onText is called with each text delta.
     *
     * @param  array<int,array<string,mixed>>  $messages
     * @return array{text:string,input_tokens:int,output_tokens:int}
     */
    public function stream(
        string $system,
        array $messages,
        ?string $model,
        int $maxTokens,
        callable $onText,
    ): array;

    /**
     * Agentic loop: keeps calling tools until the model stops asking.
     * $execute(string $name, array $input) returns the tool's result string.
     *
     * @param  string|array<int,array<string,mixed>>  $system
     * @param  array<int,array<string,mixed>>  $messages
     * @param  list<array<string,mixed>>  $tools
     * @return array{text:string,input_tokens:int,output_tokens:int,tool_calls:list<array<string,mixed>>,stop_reason:?string}
     */
    public function agentChat(
        string|array $system,
        array $messages,
        array $tools,
        callable $execute,
        ?string $model = null,
        int $maxTokens = 2000,
        int $maxIters = 5,
    ): array;
}
