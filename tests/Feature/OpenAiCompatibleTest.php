<?php

namespace Tests\Feature;

use App\Services\Llm\OpenAiCompatible;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiCompatibleTest extends TestCase
{
    private function driver(): OpenAiCompatible
    {
        return new OpenAiCompatible('https://example.test/v1', 'sk-test', 'test-model');
    }

    private function reply(string $text = 'hi', array $toolCalls = []): array
    {
        $message = ['role' => 'assistant', 'content' => $text];
        if ($toolCalls !== []) {
            $message['tool_calls'] = $toolCalls;
        }

        return [
            'choices' => [['message' => $message, 'finish_reason' => $toolCalls ? 'tool_calls' : 'stop']],
            'usage' => ['prompt_tokens' => 11, 'completion_tokens' => 7],
        ];
    }

    public function test_the_system_prompt_becomes_the_first_message(): void
    {
        Http::fake(['*' => Http::response($this->reply())]);

        $this->driver()->chat('be terse', [['role' => 'user', 'content' => 'hello']]);

        Http::assertSent(function (Request $r) {
            $messages = $r['messages'];

            return $messages[0] === ['role' => 'system', 'content' => 'be terse']
                && $messages[1]['role'] === 'user';
        });
    }

    public function test_it_reports_usage_and_text(): void
    {
        Http::fake(['*' => Http::response($this->reply('the answer'))]);

        $res = $this->driver()->chat('s', [['role' => 'user', 'content' => 'q']]);

        $this->assertSame('the answer', $res['text']);
        $this->assertSame(11, $res['input_tokens']);
        $this->assertSame(7, $res['output_tokens']);
        $this->assertSame('stop', $res['stop_reason']);
    }

    public function test_anthropic_style_tools_are_translated_to_openai_functions(): void
    {
        Http::fake(['*' => Http::response($this->reply())]);

        $this->driver()->chat('s', [['role' => 'user', 'content' => 'q']], null, 100, [[
            'name' => 'add_item',
            'description' => 'adds',
            'inputSchema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]],
        ]]);

        Http::assertSent(function (Request $r) {
            $tool = $r['tools'][0];

            return $tool['type'] === 'function'
                && $tool['function']['name'] === 'add_item'
                && $tool['function']['parameters']['properties']['name']['type'] === 'string';
        });
    }

    public function test_anthropic_style_content_blocks_are_flattened(): void
    {
        Http::fake(['*' => Http::response($this->reply())]);

        $this->driver()->chat(
            [['type' => 'text', 'text' => 'block one'], ['type' => 'text', 'text' => 'block two']],
            [['role' => 'user', 'content' => [['type' => 'text', 'text' => 'asked']]]],
        );

        Http::assertSent(function (Request $r) {
            return $r['messages'][0]['content'] === "block one\nblock two"
                && $r['messages'][1]['content'] === 'asked';
        });
    }

    public function test_the_agent_loop_executes_tools_and_feeds_results_back(): void
    {
        $call = [
            'id' => 'call_1',
            'type' => 'function',
            'function' => ['name' => 'add_item', 'arguments' => '{"name":"RTX 5090"}'],
        ];

        Http::fakeSequence()
            ->push($this->reply('', [$call]))     // first turn: asks for the tool
            ->push($this->reply('added it'));     // second turn: final answer

        $executed = [];
        $res = $this->driver()->agentChat('s', [['role' => 'user', 'content' => 'add it']], [[
            'name' => 'add_item', 'description' => 'adds', 'inputSchema' => ['type' => 'object'],
        ]], function (string $name, array $input) use (&$executed) {
            $executed[] = [$name, $input];

            return 'stored #7';
        });

        $this->assertSame([['add_item', ['name' => 'RTX 5090']]], $executed);
        $this->assertSame('added it', $res['text']);
        $this->assertSame('add_item', $res['tool_calls'][0]['name']);
        // usage accumulates across both round trips
        $this->assertSame(22, $res['input_tokens']);

        Http::assertSent(function (Request $r) {
            $messages = $r['messages'];
            $last = end($messages);

            return $last['role'] === 'tool' && $last['content'] === 'stored #7';
        });
    }

    public function test_a_failing_tool_does_not_abort_the_loop(): void
    {
        $call = ['id' => 'c1', 'function' => ['name' => 'boom', 'arguments' => '{}']];

        Http::fakeSequence()
            ->push($this->reply('', [$call]))
            ->push($this->reply('recovered'));

        $res = $this->driver()->agentChat('s', [['role' => 'user', 'content' => 'go']], [[
            'name' => 'boom', 'description' => '', 'inputSchema' => ['type' => 'object'],
        ]], fn () => throw new \RuntimeException('tool exploded'));

        $this->assertSame('recovered', $res['text']);
        $this->assertStringContainsString('tool exploded', $res['tool_calls'][0]['result']);
    }

    public function test_a_provider_error_surfaces_as_an_exception(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'bad key']], 401)]);

        $this->expectException(\RuntimeException::class);
        $this->driver()->chat('s', [['role' => 'user', 'content' => 'q']]);
    }
}
