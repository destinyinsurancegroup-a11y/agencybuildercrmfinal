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

                /**
                 * OPTION B (LOCKED):
                 * - If the note text contains time intent (e.g., "next week"),
                 *   we set dueAt + requiresDueAt automatically, even if the rule
                 *   does not explicitly require a due date.
                 *
                 * - If the rule explicitly requires a due date but we cannot parse one,
                 *   dueAt stays null and requiresDueAt stays true (scanner will stay silent).
                 */
                $ruleRequiresDueAt = (bool) ($rule['requiresDueAt'] ?? false);

                // Always attempt to parse a due date from the note text
                $parsedDueAt = $this->dueTimeParser->parseDueAt($normalizedText, $noteCreatedAt);

                $requiresDueAt = $ruleRequiresDueAt || ($parsedDueAt !== null);
                $dueAt = $parsedDueAt; // may be null

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
                        'due_at' => $dueAt?->toIso8601String(),
                        'requires_due_at' => $requiresDueAt,
                    ]
                );

                // One candidate per rule
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
