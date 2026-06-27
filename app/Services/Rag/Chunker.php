<?php

namespace App\Services\Rag;

/**
 * Structure-aware-ish text chunker: groups paragraphs up to a character budget,
 * hard-splitting any paragraph that is itself too long (with a little overlap).
 */
class Chunker
{
    /** @return list<string> */
    public function chunk(string $text, int $maxChars = 1200, int $overlap = 150): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $paras = preg_split('/\n\s*\n/', $text) ?: [$text];
        $chunks = [];
        $buf = '';

        foreach ($paras as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }

            if (mb_strlen($buf) + mb_strlen($p) + 2 <= $maxChars) {
                $buf = $buf === '' ? $p : $buf."\n\n".$p;

                continue;
            }

            if ($buf !== '') {
                $chunks[] = $buf;
                $buf = '';
            }

            if (mb_strlen($p) <= $maxChars) {
                $buf = $p;
            } else {
                foreach ($this->hardSplit($p, $maxChars, $overlap) as $piece) {
                    $chunks[] = $piece;
                }
            }
        }

        if ($buf !== '') {
            $chunks[] = $buf;
        }

        return $chunks;
    }

    /** @return list<string> */
    private function hardSplit(string $text, int $maxChars, int $overlap): array
    {
        $pieces = [];
        $len = mb_strlen($text);
        $start = 0;
        $step = max(1, $maxChars - $overlap);

        while ($start < $len) {
            $pieces[] = mb_substr($text, $start, $maxChars);
            $start += $step;
        }

        return $pieces;
    }
}
