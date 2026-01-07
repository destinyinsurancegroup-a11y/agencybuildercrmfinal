<?php

namespace App\Services\Gideon\Opportunities;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GideonOpportunityGrouper
{
    /**
     * Returns groups for the dashboard: one row per group with count + description.
     */
    public function groupsForAgency(int $agencyId): array
    {
        $rows = GideonOpportunityQuery::actionableForAgency($agencyId)
            ->select([
                'category',
                'source_type',
                DB::raw('COUNT(*) as total'),
                DB::raw('MAX(score) as max_score'),
            ])
            ->groupBy('category', 'source_type')
            ->orderByDesc('max_score')
            ->get();

        return $rows->map(function ($r) {
            $meta = $this->groupMeta((string)$r->category, (string)($r->source_type ?? ''));

            return [
                'key' => $meta['key'],
                'pill' => $meta['pill'], // P1/P2 later
                'title' => $meta['title'],
                'explanation' => $meta['explanation'],
                'why' => $meta['why'],
                'next' => $meta['next'],
                'total' => (int)$r->total,
                'category' => (string)$r->category,
                'source_type' => (string)($r->source_type ?? null),
            ];
        })->values()->all();
    }

    /**
     * When user clicks a group, return the list of items (names + links).
     */
    public function itemsForGroup(int $agencyId, string $category, ?string $sourceType = null): array
    {
        $q = GideonOpportunityQuery::actionableForAgency($agencyId)
            ->where('category', $category);

        if ($sourceType !== null && $sourceType !== '') {
            $q->where('source_type', $sourceType);
        }

        // Join contacts for display name when entity_type=contact
        $items = $q->leftJoin('contacts', function ($join) {
                $join->on('contacts.id', '=', 'gideon_opportunities.entity_id');
            })
            ->select([
                'gideon_opportunities.id',
                'gideon_opportunities.entity_type',
                'gideon_opportunities.entity_id',
                'gideon_opportunities.title',
                'gideon_opportunities.short_reason',
                'gideon_opportunities.recommended_action',
                'gideon_opportunities.source_snapshot',
                'contacts.first_name',
                'contacts.last_name',
            ])
            ->orderByDesc('gideon_opportunities.score')
            ->limit(200)
            ->get();

        return $items->map(function ($row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            if ($name === '') {
                $name = $row->title ?? 'Opportunity';
            }

            $snapshot = $row->source_snapshot ? json_decode($row->source_snapshot, true) : null;
            $excerpt = is_array($snapshot) ? ($snapshot['matched_excerpt'] ?? null) : null;

            return [
                'id' => (int)$row->id,
                'name' => $name,
                'entity_type' => $row->entity_type,
                'entity_id' => $row->entity_id,
                'short_reason' => $row->short_reason,
                'recommended_action' => $row->recommended_action,
                'matched_excerpt' => $excerpt,
            ];
        })->values()->all();
    }

    /**
     * Defines the grouped-card copy (NO tab names, manager style).
     */
    private function groupMeta(string $category, string $sourceType): array
    {
        // Group BEC (Beneficiary & Emergency Contact)
        if ($category === 'beneficiary_emergency_opportunity') {
            return [
                'key' => 'bec',
                'pill' => 'P1',
                'title' => 'Beneficiaries & emergency contacts opportunities available',
                'explanation' => 'These clients are missing key protection details.',
                'why' => 'Fixing this reduces ghosting risk and often opens upsell conversations.',
                'next' => 'Click to see names. Open each client and add at least 2 beneficiaries and 1 emergency contact.',
            ];
        }

        // Group Note-based follow-ups (Deep Scan)
        if ($sourceType === 'note_index') {
            return [
                'key' => 'note_followups',
                'pill' => 'P1',
                'title' => 'Follow-ups due from notes',
                'explanation' => 'These are real follow-ups found inside notes that are due now.',
                'why' => 'This protects revenue by catching opportunities you wrote down but didn’t schedule.',
                'next' => 'Click to see names and the note excerpt. Contact them and complete the follow-up.',
            ];
        }

        // Default fallback group
        return [
            'key' => $category . ':' . $sourceType,
            'pill' => 'P2',
            'title' => 'Opportunities available',
            'explanation' => 'Gideon found actionable items that need attention.',
            'why' => 'These are ranked by business impact.',
            'next' => 'Click to review items and take action.',
        ];
    }
}
