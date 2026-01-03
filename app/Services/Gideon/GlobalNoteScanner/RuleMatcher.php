<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Carbon;

class RuleMatcher
{
    public function __construct(
        private readonly DueTimeParser $dueTimeParser
    ) {}

    /**
     * If any "hard exclusion" phrase exists, Gideon must not surface opportunities.
     */
    public function isHardExcluded(string $normalizedText): bool
    {
        foreach (RuleLibrary::hardExclusions() as $rx) {
            if (preg_match($rx, $normalizedText)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Look at ONE note and produce zero or more Candidates.
     * We keep it deterministic: pattern match only.
     */
    public function matchCandidates(
        string $tenantId,
        string $entityType,
        int $entityId,
        int $noteId,
        Carbon $noteCreatedAt,
        string $originalText,
        string $normalizedText
    ): array {
        $candidates = [];

        foreach (RuleLibrary::rules() as $rule) {
            foreach ($rule['patterns'] as $rx) {
                if (!preg_match($rx, $normalizedText)) {
                    continue;
                }

                $dueAt = null;
                $requiresDueAt = (bool) ($rule['requiresDueAt'] ?? false);

                if ($requiresDueAt) {
                    $dueAt = $this->dueTimeParser->parseDueAt($normalizedText, $noteCreatedAt);
                }

                $candidates[] = new Candidate(
                    tenantId: $tenantId,
                    entityType: $entityType,
                    entityId: $entityId,

                    ruleCode: $rule['ruleCode'],
                    ruleGroup: $rule['ruleGroup'],
                    precedenceRank: (int) $rule['precedenceRank'],

                    noteId: $noteId,
                    noteCreatedAt: $noteCreatedAt,

                    dueAt: $dueAt,
                    requiresDueAt: $requiresDueAt,

                    title: $rule['title'],
                    whyItMatters: $rule['why'],
                    nextStep: $rule['next'],

                    evidence: [
                        'note_id' => $noteId,
                        'note_created_at' => $noteCreatedAt->toIso8601String(),
                        'matched_rule' => $rule['ruleCode'],
                        'matched_excerpt' => $this->excerpt($originalText),
                    ]
                );

                // Stop after first match inside this rule (keeps output stable)
                break;
            }
        }

        return $candidates;
    }

    private function excerpt(string $text, int $max = 140): string
    {
        $t = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if (mb_strlen($t) <= $max) {
            return $t;
        }
        return mb_substr($t, 0, $max - 1) . '…';
    }
}
