<?php

namespace App\Services\Rag;

/**
 * Builds the tsquery for the lexical half of hybrid search.
 *
 * Postgres' plainto_tsquery ANDs every term, so a natural question like
 * "რა ღირს RTX 4070?" demanded that the chunk also contain the question
 * words — which it never does. Lexical search therefore matched nothing and
 * retrieval was silently vector-only. OR-ing the terms restores recall;
 * ts_rank and the RRF fusion downstream decide what actually ranks.
 */
class LexicalQuery
{
    /** Terms shorter than this are noise once the query is tokenised. */
    private const MIN_TERM_LENGTH = 2;

    /** Guards against a pathologically long query producing a huge tsquery. */
    private const MAX_TERMS = 40;

    /**
     * @return string|null an OR tsquery, or null when nothing is searchable
     */
    public static function build(string $query): ?string
    {
        // Only letters and digits survive, so no user input can reach
        // to_tsquery as an operator such as & | ! or :*.
        $terms = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $terms = array_values(array_unique(array_filter(
            $terms,
            fn (string $term): bool => mb_strlen($term) >= self::MIN_TERM_LENGTH,
        )));

        if ($terms === []) {
            return null;
        }

        return implode(' | ', array_slice($terms, 0, self::MAX_TERMS));
    }
}
