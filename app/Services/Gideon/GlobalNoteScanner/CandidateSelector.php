<?php

namespace App\Services\Gideon\GlobalNoteScanner;

class CandidateSelector
{
    /**
     * Deterministic selection:
     * 1) lowest precedenceRank wins (10 beats 20 beats 30, etc.)
     * 2) if tie and both have dueAt, earliest dueAt wins
     * 3) if tie, most recent note wins
     */
    public function selectTopCandidate(array $candidates): Candidate
    {
        usort($candidates, function (Candidate $a, Candidate $b) {
            if ($a->precedenceRank !== $b->precedenceRank) {
                return $a->precedenceRank <=> $b->precedenceRank;
            }

            $aDue = $a->dueAt?->getTimestamp();
            $bDue = $b->dueAt?->getTimestamp();

            if ($aDue !== null && $bDue !== null && $aDue !== $bDue) {
                return $aDue <=> $bDue;
            }

            // Most recent note wins if still tied
            return $b->noteCreatedAt->getTimestamp() <=> $a->noteCreatedAt->getTimestamp();
        });

        return $candidates[0];
    }
}
