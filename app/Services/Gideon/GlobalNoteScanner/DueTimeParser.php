<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Carbon;

class DueTimeParser
{
    /**
     * Uses the note timestamp as the base time.
     * Locked rules:
     * - tomorrow  = +1 day
     * - next week = +7 days
     * - in X weeks = + (7*X) days
     */
    public function parseDueAt(string $normalizedText, Carbon $noteTimestamp): ?Carbon
    {
        // tomorrow => +1 day
        if (preg_match('/\btomorrow\b/', $normalizedText)) {
            return $noteTimestamp->copy()->addDay();
        }

        // next week => +7 days (LOCKED)
        if (preg_match('/\bnext week\b/', $normalizedText)) {
            return $noteTimestamp->copy()->addDays(7);
        }

        // in X weeks => +7*X days
        if (preg_match('/\bin\s+(\d+)\s*weeks?\b/', $normalizedText, $m)) {
            $weeks = (int) $m[1];

            // simple safety bounds
            if ($weeks > 0 && $weeks <= 52) {
                return $noteTimestamp->copy()->addDays(7 * $weeks);
            }
        }

        return null;
    }
}
