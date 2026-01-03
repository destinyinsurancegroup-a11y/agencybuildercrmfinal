<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Carbon;

class RuleMatcher
{
    public function __construct(
        private readonly DueTimeParser $dueTimeParser
    ) {}

    public function isHardExcluded(string $normalizedText): bool
    {
        foreach (RuleLibrary::hardExclusions() as $rx) {
            if (preg_match($rx, $normalizedText)) {
                return true;
            }
        }
        return false;
    }

    public function matchCandidates(
        int $agencyId,
        ?int $userId,
        string $entityType,
        ?int $entityId,
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

                $requiresDueAt = (bool) ($rule['requiresDueAt'] ?? false);
                $dueAt = null;

                if ($requiresDueAt) {
                    $dueAt = $this->dueTimeParser->parseDueAt($normalizedText, $noteCreatedAt);
                }

                $candidates[] = new Candidate(
                    agencyId: $agencyId,
                    userId: $userId,
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
                    shortReason: $rule['why'],
                    recommendedAction: $rule['next'],

                    sourceSnapshot: [
                        'source_type' => 'note_index',
                        'note_id' => $noteId,
                        'note_created_at' => $noteCreatedAt->toIso8601String(),
                        'matched_rule' => $rule['ruleCode'],
                        'matched_excerpt' => $this->excerpt($originalText),
                    ]
                );

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
