<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GlobalNoteScannerService
{
    public function __construct(
        private readonly NoteIndexRepository $notes,
        private readonly FollowUpRepository $followUps,
        private readonly RuleMatcher $matcher,
        private readonly CandidateSelector $selector,
        private readonly OpportunityUpserter $upserter,
        private readonly TextNormalizer $normalizer
    ) {}

    /**
     * Deep Scan: scans notes (all modules) for missed opportunities.
     *
     * @param string|null $tenantId scan only one tenant if provided
     * @param Carbon|null $since scan only notes changed since time if provided
     */
    public function runDeepScan(?string $tenantId = null, ?Carbon $since = null): void
    {
        $cursor = null;
        $batchSize = 500;

        do {
            $batch = $this->notes->fetchBatch(
                tenantId: $tenantId,
                since: $since,
                cursor: $cursor,
                limit: $batchSize
            );

            foreach ($batch->items as $noteRow) {
                $cursor = $noteRow->cursor;

                // Skip private notes (v1)
                if (($noteRow->visibility ?? 'public') !== 'public') {
                    continue;
                }

                $tenant = (string) $noteRow->tenant_id;
                $entityType = (string) $noteRow->entity_type;
                $entityId = (int) $noteRow->entity_id;

                // 1) If follow-up is already scheduled, Gideon stays quiet
                if ($this->followUps->hasFutureFollowUp($tenant, $entityType, $entityId)) {
                    $this->upserter->resolveAllOpenForEntity(
                        tenantId: $tenant,
                        entityType: $entityType,
                        entityId: $entityId,
                        reason: 'future_followup_exists'
                    );
                    continue;
                }

                // 2) Normalize note text for matching
                $originalText = (string) $noteRow->note_text;
                $normalized = $this->normalizer->normalize($originalText);

                if ($normalized === '') {
                    continue;
                }

                // 3) Hard exclusions shut Gideon up
                if ($this->matcher->isHardExcluded($normalized)) {
                    $this->upserter->resolveAllOpenForEntity(
                        tenantId: $tenant,
                        entityType: $entityType,
                        entityId: $entityId,
                        reason: 'hard_exclusion_note'
                    );
                    continue;
                }

                // 4) Convert note into candidate opportunities
                $noteCreatedAt = Carbon::parse($noteRow->note_created_at);

                $candidates = $this->matcher->matchCandidates(
                    tenantId: $tenant,
                    entityType: $entityType,
                    entityId: $entityId,
                    noteId: (int) $noteRow->note_id,
                    noteCreatedAt: $noteCreatedAt,
                    originalText: $originalText,
                    normalizedText: $normalized
                );

                if (empty($candidates)) {
                    continue;
                }

                // 5) Pick top candidate so we don't spam
                $best = $this->selector->selectTopCandidate($candidates);

                // 6) Follow-up rules only surface when due/overdue
                if ($best->requiresDueAt) {
                    if ($best->dueAt === null) {
                        // If we can't calculate a due date, we stay silent (v1)
                        continue;
                    }
                    if (now()->lt($best->dueAt)) {
                        // Not due yet, stay silent
                        continue;
                    }
                }

                // 7) Save the opportunity (idempotent)
                $this->upserter->upsertFromCandidate($best);

                // 8) Optional: enforce "one open opportunity per entity"
                $this->upserter->resolveOtherOpenForEntity(
                    tenantId: $tenant,
                    entityType: $entityType,
                    entityId: $entityId,
                    keepRuleCode: $best->ruleCode,
                    keepSourceId: $best->noteId
                );
            }

        } while ($batch->hasMore);

        Log::info('Gideon Global Note Deep Scan finished', [
            'tenantId' => $tenantId,
            'since' => $since?->toIso8601String(),
        ]);
    }
}
