<?php

namespace App\Services\Gideon;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\BookClient;
use App\Models\ServiceRecord;

/**
 * GideonOpportunityEngine
 *
 * This service loads data for each client/lead, builds a context array,
 * and hands that context to the GideonRulesEngine for evaluation.
 *
 * Later, this class will also:
 * - Save opportunities into the gideon_opportunities table
 * - Refresh opportunities on schedule
 * - Provide data for the dashboard UI
 *
 * For now, this is the SKELETON structure so we can safely build forward.
 */
class GideonOpportunityEngine
{
    protected GideonRulesEngine $rules;

    public function __construct(GideonRulesEngine $rules)
    {
        $this->rules = $rules;
    }

    /**
     * MAIN ENTRY POINT
     *
     * Run opportunity analysis for all clients & leads in this tenant.
     * Later, this will save results into gideon_opportunities.
     *
     * @return array A big list of opportunities (temporary, until DB is added).
     */
    public function analyzeAll(): array
    {
        $results = [];

        // Load clients from Book of Business
        $clients = BookClient::with(['contact', 'policies', 'notes'])->get();

        foreach ($clients as $client) {
            $context = $this->buildContextForBookClient($client);
            $ops = $this->rules->evaluate($context);

            $results[] = [
                'contact_id'    => $client->contact_id,
                'contact_name'  => $client->contact->full_name ?? 'Unknown',
                'opportunities' => $ops,
            ];
        }

        // Load leads as well (so Gideon can revive old leads)
        $leads = Lead::with(['contact', 'notes'])->get();

        foreach ($leads as $lead) {
            $context = $this->buildContextForLead($lead);
            $ops = $this->rules->evaluate($context);

            $results[] = [
                'contact_id'    => $lead->contact_id,
                'contact_name'  => $lead->contact->full_name ?? 'Unknown Lead',
                'opportunities' => $ops,
            ];
        }

        // Service records → may create save-the-business opportunities
        $serviceRecords = ServiceRecord::with(['contact', 'notes'])->get();

        foreach ($serviceRecords as $record) {
            $context = $this->buildContextForService($record);
            $ops = $this->rules->evaluate($context);

            $results[] = [
                'contact_id'    => $record->contact_id,
                'contact_name'  => $record->contact->full_name ?? 'Unknown',
                'opportunities' => $ops,
            ];
        }

        return $results;
    }

    /**
     * Build context array for a Book of Business client.
     */
    protected function buildContextForBookClient($client): array
    {
        return [
            'contact'   => $client->contact?->toArray(),
            'policies'  => $client->policies?->toArray(),
            'notes'     => $client->notes?->toArray(),
            'tags'      => $client->contact?->tags?->pluck('name')->toArray() ?? [],
            'household' => [], // Later: household relationships
            'leads'     => [],
            'service'   => [],
        ];
    }

    /**
     * Build context array for a Lead.
     */
    protected function buildContextForLead($lead): array
    {
        return [
            'contact'   => $lead->contact?->toArray(),
            'notes'     => $lead->notes?->toArray(),
            'policies'  => [],
            'tags'      => $lead->contact?->tags?->pluck('name')->toArray() ?? [],
            'household' => [],
            'leads'     => [$lead->toArray()],
            'service'   => [],
        ];
    }

    /**
     * Build context array for a Service Record.
     */
    protected function buildContextForService($record): array
    {
        return [
            'contact'   => $record->contact?->toArray(),
            'notes'     => $record->notes?->toArray(),
            'policies'  => [], // Later: link to policy record
            'tags'      => $record->contact?->tags?->pluck('name')->toArray() ?? [],
            'household' => [],
            'leads'     => [],
            'service'   => [$record->toArray()],
        ];
    }
}
