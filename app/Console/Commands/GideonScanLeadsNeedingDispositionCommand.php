<?php

namespace App\Console\Commands;

use App\Models\GideonOpportunity;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class GideonScanLeadsNeedingDispositionCommand extends Command
{
    /**
     * Run:
     *   php artisan gideon:scan:lead-disposition
     *   php artisan gideon:scan:lead-disposition --agency=1
     *   php artisan gideon:scan:lead-disposition --dry-run
     */
    protected $signature = 'gideon:scan:lead-disposition {--agency=} {--dry-run}';

    protected $description = 'Gideon scanner: Leads needing disposition (14+ days still New/blank).';

    private const CATEGORY = 'lead_disposition_opportunity';
    private const RULE_CODE = 'LEAD_DISPOSITION_14_DAYS_NEW';
    private const AGE_DAYS = 14;

    // Final / explicit dispositions (do NOT flag)
    private const DISPOSITION_SOLD = 'Sold';
    private const DISPOSITION_NOT_INTERESTED = 'Not Interested';
    private const DISPOSITION_FOLLOW_UP = 'Follow Up';

    public function handle(): int
    {
        $agencyOpt = $this->option('agency');
        $dryRun = (bool) $this->option('dry-run');

        if (! Schema::hasTable('contacts')) {
            $this->error('contacts table not found.');
            return self::FAILURE;
        }

        if (! Schema::hasTable('gideon_opportunities')) {
            $this->error('gideon_opportunities table not found.');
            return self::FAILURE;
        }

        $contactsCols = Schema::getColumnListing('contacts');
        $hasTenantId = in_array('tenant_id', $contactsCols, true);

        // GideonOpportunity columns vary; guard everything we write.
        $oppCols = Schema::getColumnListing('gideon_opportunities');

        $hasOppTitle = in_array('title', $oppCols, true);
        $hasOppRecommendedAction = in_array('recommended_action', $oppCols, true);
        $hasOppScore = in_array('score', $oppCols, true);
        $hasOppSourceType = in_array('source_type', $oppCols, true);
        $hasOppSourceSnapshot = in_array('source_snapshot', $oppCols, true);
        $hasOppSnoozedUntil = in_array('snoozed_until', $oppCols, true);

        $cutoff = now()->subDays(self::AGE_DAYS);

        // Build base lead query
        $leadQuery = DB::table('contacts')
            ->where('contact_type', 'Lead')
            ->where('created_at', '<=', $cutoff);

        // Optional agency scoping if contacts has agency_id
        if (in_array('agency_id', $contactsCols, true)) {
            if ($agencyOpt !== null && $agencyOpt !== '') {
                $leadQuery->where('agency_id', (int) $agencyOpt);
            }
        } else {
            // Your system already uses agency_id, but if not, fail safely.
            $this->error('contacts.agency_id not found; cannot scope multi-tenant safely.');
            return self::FAILURE;
        }

        // "Needs disposition" means: status is null/empty/"New"
        // and NOT one of the explicit disposition states.
        $leadQuery->where(function ($q) {
            $q->whereNull('status')
              ->orWhere('status', '')
              ->orWhere('status', 'New');
        });

        // Pull minimal columns for naming/snapshot
        $select = ['id', 'agency_id', 'created_at', 'status', 'first_name', 'last_name', 'full_name'];
        if ($hasTenantId) $select[] = 'tenant_id';

        $leads = $leadQuery->select($select)->orderBy('created_at')->get();

        $this->info('Leads qualifying: ' . $leads->count());
        if ($dryRun) {
            $this->warn('Dry run enabled — no writes will occur.');
            return self::SUCCESS;
        }

        $now = now();

        // Upsert opportunities for each qualifying lead
        $upserts = 0;

        DB::beginTransaction();

        try {
            foreach ($leads as $lead) {
                $contactId = (int) $lead->id;
                $agencyId = (int) $lead->agency_id;

                $name = $this->buildContactName($lead);

                $openUrl = $this->buildLeadOpenUrl($contactId);

                $snapshot = [
                    'contact_id' => $contactId,
                    'contact_type' => 'lead',
                    'contact_name' => $name,
                    'lead_created_at' => Carbon::parse($lead->created_at)->toDateString(),
                    'age_days' => Carbon::parse($lead->created_at)->diffInDays($now),
                ];

                if ($openUrl !== null) {
                    $snapshot['open_url'] = $openUrl;
                }

                // Identify an existing opp row for this lead+rule+category
                $existing = GideonOpportunity::query()
                    ->where('agency_id', $agencyId)
                    ->where('category', self::CATEGORY)
                    ->where('rule_code', self::RULE_CODE)
                    ->where('entity_type', 'contact')
                    ->where('entity_id', $contactId)
                    ->first();

                // Respect completed/dismissed items (agent intentionally hid it)
                if ($existing && in_array($existing->status, ['completed', 'dismissed'], true)) {
                    // If you later want enforcement to resurface, this is the line to change.
                    continue;
                }

                // If snoozed and still snoozed, do not unsnooze by rewriting status.
                if ($existing && $hasOppSnoozedUntil && $existing->status === 'snoozed' && $existing->snoozed_until) {
                    $snoozedUntil = Carbon::parse($existing->snoozed_until);
                    if ($snoozedUntil->isFuture()) {
                        // Still snoozed; update snapshot silently but keep status.
                        $updates = [];
                        if ($hasOppSourceSnapshot) $updates['source_snapshot'] = json_encode($snapshot);
                        if (! empty($updates)) {
                            GideonOpportunity::query()->whereKey($existing->id)->update($updates);
                        }
                        continue;
                    }
                }

                // Create or update (idempotent)
                $payload = [
                    'agency_id' => $agencyId,
                    'category' => self::CATEGORY,
                    'rule_code' => self::RULE_CODE,
                    'entity_type' => 'contact',
                    'entity_id' => $contactId,
                    'status' => 'open',
                ];

                if ($hasTenantId && in_array('tenant_id', $oppCols, true)) {
                    $payload['tenant_id'] = $lead->tenant_id;
                }

                if ($hasOppSourceType) {
                    $payload['source_type'] = 'lead_disposition_scan';
                }

                if ($hasOppTitle) {
                    $payload['title'] = 'Leads need disposition';
                }

                if ($hasOppRecommendedAction) {
                    $payload['recommended_action'] = 'Set disposition: Sold, Follow Up, or Not Interested.';
                }

                if ($hasOppScore) {
                    // Score can be simple: older leads slightly higher
                    $ageDays = (int) ($snapshot['age_days'] ?? self::AGE_DAYS);
                    $payload['score'] = min(100, 50 + $ageDays); // bounded
                }

                if ($hasOppSourceSnapshot) {
                    $payload['source_snapshot'] = json_encode($snapshot);
                }

                if ($existing) {
                    GideonOpportunity::query()->whereKey($existing->id)->update($payload);
                } else {
                    GideonOpportunity::query()->create($payload);
                }

                $upserts++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            $this->error('Scan failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Opportunities upserted: ' . $upserts);

        // Optional cleanup:
        // We do NOT auto-close old opps here because it depends on how you want history handled.
        // If you want cleanup (e.g., mark as completed when lead is Sold/Not Interested),
        // say so and I’ll add a safe, scoped closure pass.

        return self::SUCCESS;
    }

    private function buildContactName(object $lead): string
    {
        $full = trim((string) ($lead->full_name ?? ''));
        if ($full !== '') return $full;

        $first = trim((string) ($lead->first_name ?? ''));
        $last = trim((string) ($lead->last_name ?? ''));

        $name = trim($first . ' ' . $last);
        return $name !== '' ? $name : 'Unnamed Lead';
    }

    /**
     * Build a deep-link to open the lead record if the named route exists.
     * We keep this optional to avoid hard failures in environments without the route.
     */
    private function buildLeadOpenUrl(int $contactId): ?string
    {
        try {
            // Your controller already uses leads.show in places; prefer it if it exists
            if (Route::has('leads.show')) {
                return route('leads.show', $contactId);
            }
            // Fallback if leads are opened via contacts.show
            if (Route::has('contacts.show')) {
                return route('contacts.show', $contactId);
            }
            // Or book.open if your "book" view is your primary contact view
            if (Route::has('book.open')) {
                return route('book.open', $contactId);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }
}
