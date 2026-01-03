<?php

namespace App\Services\Gideon\GlobalNoteScanner;

class TextNormalizer
{
    public function normalize(string $text): string
    {
        $t = mb_strtolower($text);

        // Replace punctuation with spaces (keeps words searchable)
        $t = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $t);

        // Collapse multiple spaces
        $t = preg_replace('/\s+/u', ' ', $t);

        return trim($t ?? '');
    }
}
