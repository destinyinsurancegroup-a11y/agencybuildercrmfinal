<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Carbon;

class Candidate
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $entityType,
        public readonly int $entityId,

        public readonly string $ruleCode,
        public readonly string $ruleGroup, // save|followup|quote|expansion|referral|life_event
        public readonly int $precedenceRank, // lower is higher priority

        public readonly int $noteId,
        public readonly Carbon $noteCreatedAt,

        public readonly ?Carbon $dueAt,
        public readonly bool $requiresDueAt,

        public readonly string $title,
        public readonly string $whyItMatters,
        public readonly string $nextStep,

        public readonly array $evidence
    ) {}
}
