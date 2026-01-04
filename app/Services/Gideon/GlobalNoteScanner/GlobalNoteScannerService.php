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
     * @param int|null    $agencyId scan only one agency if provided
     * @param Carbon|null $since    scan only notes changed since time if provided
     */
    public function runDeepScan(?int $agencyId = null, ?Carbon $since = null): void
    {
        $cursor = null;
        $batchSize = 500;

        do {
            // Fetch only notes for this agency (if provided)
            $batch = $this->notes->fetchBatch(
                agencyId: $agencyId,
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

                // Safety: note must be tied to an agency
                $rowAgencyId = (int) ($noteRow->agency_id ?? 0);
                if ($rowAgencyId <= 0) {
                    continue;
                }

                $userId = isset($noteRow->author_user_id) ? (int) $noteRow->author_user_id : null;

                $entityType = (string) $noteRow->entity_type;
                $entityId = isset($noteRow->entity_id) ? (int) $noteRow->entity_id : null;

                // 1) If follow-up is already scheduled, Gideon stays quiet
                if ($this->followUps->hasFutureFollowUp($rowAgencyId, $entityType, (int) $entityId)) {
                    $this->upserter->resolveAllOpenForEntity(
                        agencyId: $rowAgencyId,
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
                        agencyId: $rowAgencyId,
                        entityType: $entityType,
                        entityId: $entityId,
                        reason: 'hard_exclusion_note'
                    );
                    continue;
                }

                // 4) Convert note into candidate opportunities
                $noteCreatedAt = Carbon::parse($noteRow->note_created_at);

                $candidates = $this->matcher->matchCandidates(
                    agencyId: $rowAgencyId,
                    userId: $userId,
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
                        continue; // can't compute due date => silent
                    }
                    if (now()->lt($best->dueAt)) {
                        continue; // not due yet => silent
                    }
                }

                // 7) Save the opportunity (idempotent)
                $this->upserter->upsertFromCandidate($best);

                // 8) Optional: enforce "one open opportunity per entity"
                $this->upserter->resolveOtherOpenForEntity(
                    agencyId: $rowAgencyId,
                    entityType: $entityType,
                    entityId: $entityId,
                    keepRuleCode: $best->ruleCode,
                    keepSourceId: $best->noteId
                );
            }

        } while ($batch->hasMore);

        Log::info('Gideon Global Note Deep Scan finished', [
            'agencyId' => $agencyId,
            'since' => $since?->toIso8601String(),
        ]);
    }
}
