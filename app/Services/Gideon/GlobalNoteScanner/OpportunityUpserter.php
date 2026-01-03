<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Facades\DB;

class OpportunityUpserter
{
    public function upsertFromCandidate(Candidate $c): void
    {
        DB::transaction(function () use ($c) {
            $now = now();

            $existing = DB::table('gideon_opportunities')
                ->where('agency_id', $c->agencyId)
                ->where('entity_type', $c->entityType)
                ->where('entity_id', $c->entityId)
                ->where('rule_code', $c->ruleCode)
                ->where('source_type', 'note_index')
                ->where('source_id', $c->noteId)
                ->first();

            if ($existing) {
                DB::table('gideon_opportunities')
                    ->where('id', $existing->id)
                    ->update([
                        'user_id' => $c->userId,

                        'category' => $c->ruleGroup,
                        'title' => $c->title,
                        'short_reason' => $c->shortReason,
                        'recommended_action' => $c->recommendedAction,

                        'status' => 'open',
                        'due_at' => $c->dueAt,

                        'source_snapshot' => json_encode($c->sourceSnapshot),

                        // Keep your existing scoring model simple for now
                        'score' => max((int)($existing->score ?? 0), 50),

                        'last_detected_at' => $now,
                        'updated_at' => $now,
                    ]);
                return;
            }

            DB::table('gideon_opportunities')->insert([
                'agency_id' => $c->agencyId,
                'user_id' => $c->userId,

                'entity_type' => $c->entityType,
                'entity_id' => $c->entityId,

                'category' => $c->ruleGroup,

                'rule_code' => $c->ruleCode,
                'source_type' => 'note_index',
                'source_id' => $c->noteId,
                'due_at' => $c->dueAt,

                'title' => $c->title,
                'short_reason' => $c->shortReason,
                'recommended_action' => $c->recommendedAction,

                'score' => 50,
                'status' => 'open',

                'source_snapshot' => json_encode($c->sourceSnapshot),

                'first_detected_at' => $now,
                'last_detected_at' => $now,

                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function resolveAllOpenForEntity(int $agencyId, string $entityType, ?int $entityId, string $reason): void
    {
        DB::table('gideon_opportunities')
            ->where('agency_id', $agencyId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'open')
            ->update([
                'status' => 'completed',
                'resolved_at' => now(),
                'outcome_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function resolveOtherOpenForEntity(
        int $agencyId,
        string $entityType,
        ?int $entityId,
        string $keepRuleCode,
        int $keepSourceId
    ): void {
        DB::table('gideon_opportunities')
            ->where('agency_id', $agencyId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'open')
            ->where(function ($q) use ($keepRuleCode, $keepSourceId) {
                $q->where('rule_code', '!=', $keepRuleCode)
                  ->orWhere('source_id', '!=', $keepSourceId);
            })
            ->update([
                'status' => 'completed',
                'resolved_at' => now(),
                'outcome_reason' => 'replaced_by_higher_precedence',
                'updated_at' => now(),
            ]);
    }
}
