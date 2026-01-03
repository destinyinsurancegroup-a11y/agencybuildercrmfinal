<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Carbon;

class Candidate
{
    public function __construct(
        public readonly int $agencyId,
        public readonly ?int $userId,

        public readonly string $entityType,
        public readonly ?int $entityId,

        public readonly string $ruleCode,
        public readonly string $ruleGroup,       // save|followup|quote|expansion|referral
        public readonly int $precedenceRank,     // lower = higher priority

        public readonly int $noteId,
        public readonly Carbon $noteCreatedAt,

        public readonly ?Carbon $dueAt,
        public readonly bool $requiresDueAt,

        public readonly string $title,
        public readonly string $shortReason,
        public readonly string $recommendedAction,

        public readonly array $sourceSnapshot
    ) {}
}
