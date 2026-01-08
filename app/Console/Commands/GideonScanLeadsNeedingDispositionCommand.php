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

    private const CATEGORY  = 'lead_disposition_opportunity';
    private const RULE_CODE = 'LEAD_DISPOSITION_14_DAYS_NEW';

    /**
     * TEMP ONLY: force fire for verification.
     * After you confirm the dashboard card + modal + actions work,
     * set this back to 14 (or your chosen production threshold).
     */
    private const AGE_DAYS  = 0; // TEMP: force fire for verification

    // Final / explicit dispositions (do NOT flag)
    // NOTE: In your data, lead statuses include "New" and "Not Interested".
    // The UI also supports "Sold" and "Follow Up".
    private const DISPOSITION_SOLD           = 'Sold';
    private const DISPOSITION_NOT_INTERESTED = 'Not Interested';
    private const DISPOSITION_FOLLOW_UP      = 'Follow Up';

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
        // ✅ FIX: your contact_type values are lowercase ("lead"), so match case-insensitively.
        $leadQuery = DB::table('contacts')
            ->whereRaw('LOWER(contact_type) = ?', ['lead'])
            ->where('created_at', '<=', $cutoff);

        // Optional agency scoping if contacts has agency_id
        if (in_array('agency_id', $contactsCols, true)) {
            if ($agencyOpt !== null && $agencyOpt !== '') {
                $leadQuery->where('agency_id', (int) $agencyOpt);
            }
        } else {
            // Fail safely if no agency_id (multi-tenant safety).
            $this->error('contacts.agency_id not found; cannot scope multi-tenant safely.');
            return self::FAILURE;
        }

        /**
         * "Needs disposition" means: still effectively NEW.
         *
         * You confirmed disposition options are:
         * - Sold (final)
         * - Follow Up (explicit, not final but it IS a disposition)
         * - Not Interested (final)
         *
         * So this scanner should ONLY flag leads that are still New/blank.
         *
         * With AGE_DAYS = 0 (TEMP), this will flag "New" leads immediately
         * so you can verify dashboard behavior.
         */
        $leadQuery->where(function ($q) {
            // Normalize whitespace/case; cover NULL/empty just in case.
            $q->whereNull('status')
              ->orWhereRaw('TRIM(status) = ""')
              ->orWhereRaw('LOWER(TRIM(status)) = ?', ['new']);
        });

        // Defensive: ensure we never flag explicitly dispositioned leads, even if data gets messy.
        $leadQuery->whereNotIn('status', [
            self::DISPOSITION_SOLD,
            self::DISPOSITION_NOT_INTERESTED,
            self::DISPOSITION_FOLLOW_UP,
        ]);

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
        $upserts = 0;

        DB::beginTransaction();

        try {
            foreach ($leads as $lead) {
                $contactId = (int) $lead->id;
                $agencyId  = (int) $lead->agency_id;

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
                    $payload['recommended_action'] = 'Review and set disposition: Sold, Follow Up, or Not Interested.';
                }

                if ($hasOppScore) {
                    $ageDays = (int) ($snapshot['age_days'] ?? self::AGE_DAYS);
                    $payload['score'] = min(100, 50 + $ageDays);
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

        return self::SUCCESS;
    }

    private function buildContactName(object $lead): string
    {
        $full = trim((string) ($lead->full_name ?? ''));
        if ($full !== '') return $full;

        $first = trim((string) ($lead->first_name ?? ''));
        $last  = trim((string) ($lead->last_name ?? ''));

        $name = trim($first . ' ' . $last);
        return $name !== '' ? $name : 'Unnamed Lead';
    }

    /**
     * Build a deep-link to open the lead record if the named route exists.
     * Optional to avoid hard failures in environments without the route.
     */
    private function buildLeadOpenUrl(int $contactId): ?string
    {
        try {
            if (Route::has('leads.show')) {
                return route('leads.show', $contactId);
            }
            if (Route::has('contacts.show')) {
                return route('contacts.show', $contactId);
            }
            if (Route::has('book.open')) {
                return route('book.open', $contactId);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }
}
