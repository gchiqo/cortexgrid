<?php

namespace Tests\Unit;

use App\Services\Rag\LexicalQuery;
use PHPUnit\Framework\TestCase;

class LexicalQueryTest extends TestCase
{
    public function test_it_ors_terms_so_a_question_still_matches(): void
    {
        // The regression this guards: plainto_tsquery ANDed these, so a chunk
        // reading "RTX 4070 ... 2100 ლარი" matched nothing.
        $this->assertSame(
            'რა | ღირს | rtx | 4070',
            LexicalQuery::build('რა ღირს RTX 4070?')
        );
    }

    public function test_it_strips_punctuation_and_lowercases(): void
    {
        $this->assertSame('hello | world', LexicalQuery::build('Hello, WORLD!!'));
    }

    public function test_it_drops_single_characters_and_duplicates(): void
    {
        $this->assertSame('rtx | 4070', LexicalQuery::build('RTX a 4070 RTX'));
    }

    public function test_it_returns_null_when_nothing_is_searchable(): void
    {
        $this->assertNull(LexicalQuery::build('?! ...'));
        $this->assertNull(LexicalQuery::build(''));
    }

    public function test_tsquery_operators_cannot_be_injected(): void
    {
        $built = LexicalQuery::build('foo & bar | baz ! qux:*');

        $this->assertSame('foo | bar | baz | qux', $built);
    }

    public function test_it_caps_very_long_queries(): void
    {
        $built = LexicalQuery::build(implode(' ', array_map(fn ($i) => "term{$i}", range(1, 100))));

        $this->assertCount(40, explode(' | ', (string) $built));
    }
}
