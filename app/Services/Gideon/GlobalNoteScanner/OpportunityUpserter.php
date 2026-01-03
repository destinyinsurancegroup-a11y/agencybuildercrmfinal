<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Facades\DB;

class OpportunityUpserter
{
    /**
     * Create or update an opportunity (idempotent).
     * Dedupe key: tenant_id + entity_type + entity_id + rule_code + note_id
     */
    public function upsertFromCandidate(Candidate $c): void
    {
        DB::transaction(function () use ($c) {
            $now = now();

            $existing = DB::table('gideon_opportunities')
                ->where('tenant_id', $c->tenantId)
                ->where('entity_type', $c->entityType)
                ->where('entity_id', $c->entityId)
                ->where('rule_code', $c->ruleCode)
                ->where('source_type', 'note')
                ->where('source_id', $c->noteId)
                ->first();

            if ($existing) {
                DB::table('gideon_opportunities')
                    ->where('id', $existing->id)
                    ->update([
                        'status' => 'open',
                        'due_at' => $c->dueAt,
                        'title' => $c->title,
                        'why_it_matters' => $c->whyItMatters,
                        'next_step' => $c->nextStep,
                        'evidence' => json_encode($c->evidence),
                        'last_detected_at' => $now,
                        'updated_at' => $now,
                    ]);
                return;
            }

            DB::table('gideon_opportunities')->insert([
                'tenant_id' => $c->tenantId,
                'entity_type' => $c->entityType,
                'entity_id' => $c->entityId,

                'rule_code' => $c->ruleCode,
                'source_type' => 'note',
                'source_id' => $c->noteId,

                'status' => 'open',
                'due_at' => $c->dueAt,

                'title' => $c->title,
                'why_it_matters' => $c->whyItMatters,
                'next_step' => $c->nextStep,

                'evidence' => json_encode($c->evidence),

                // Hook later into your priority hierarchy
                'priority_score' => 0,
                'severity' => 'high',

                'first_detected_at' => $now,
                'last_detected_at' => $now,

                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    /**
     * Resolve all open opportunities for an entity (used when a future follow-up exists, or hard exclusion).
     */
    public function resolveAllOpenForEntity(string $tenantId, string $entityType, int $entityId, string $reason): void
    {
        DB::table('gideon_opportunities')
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'open')
            ->update([
                'status' => 'resolved',
                'updated_at' => now(),
            ]);

        // (Optional) You can also append resolution reason into evidence_json later.
    }

    /**
     * If you enforce "one open opportunity per entity at a time", this resolves others.
     */
    public function resolveOtherOpenForEntity(
        string $tenantId,
        string $entityType,
        int $entityId,
        string $keepRuleCode,
        int $keepSourceId
    ): void {
        DB::table('gideon_opportunities')
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'open')
            ->where(function ($q) use ($keepRuleCode, $keepSourceId) {
                $q->where('rule_code', '!=', $keepRuleCode)
                  ->orWhere('source_id', '!=', $keepSourceId);
            })
            ->update([
                'status' => 'resolved',
                'updated_at' => now(),
            ]);
    }
}
