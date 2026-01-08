<?php

namespace App\Console\Commands;

use App\Models\GideonOpportunity;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class GideonScanPolicyReviewsDueCommand extends Command
{
    /**
     * Run:
     *   php artisan gideon:scan:policy-review
     *   php artisan gideon:scan:policy-review --agency=1
     *   php artisan gideon:scan:policy-review --dry-run
     */
    protected $signature = 'gideon:scan:policy-review {--agency=} {--dry-run}';

    protected $description = 'Gideon scanner (P2): 6-month policy review due for book-of-business clients.';

    private const CATEGORY  = 'policy_review_due_opportunity';
    private const RULE_CODE = 'POLICY_REVIEW_6_MONTHS_DUE';

    // Trigger threshold
    private const DUE_MONTHS = 6;

    // Disqualify if touched recently (notes present AND updated_at within N days)
    private const RECENT_TOUCH_DAYS = 45;

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
        $oppCols = Schema::getColumnListing('gideon_opportunities');

        // Multi-tenant safety: require agency_id on contacts
        if (! in_array('agency_id', $contactsCols, true)) {
            $this->error('contacts.agency_id not found; cannot scope safely.');
            return self::FAILURE;
        }

        $hasTenantId = in_array('tenant_id', $contactsCols, true);

        // Optional contact columns
        $hasInBoB = in_array('in_book_of_business', $contactsCols, true);
        $hasPolicyIssue = in_array('policy_issue_date', $contactsCols, true);
        $hasNotes = in_array('notes', $contactsCols, true);
        $hasServiceStatus = in_array('service_status', $contactsCols, true);
        $hasServiceArchivedAt = in_array('service_archived_at', $contactsCols, true);

        if (! $hasPolicyIssue) {
            $this->error('contacts.policy_issue_date not found; cannot run P2 review logic.');
            return self::FAILURE;
        }

        // GideonOpportunity columns vary; guard everything we write.
        $hasOppTitle = in_array('title', $oppCols, true);
        $hasOppRecommendedAction = in_array('recommended_action', $oppCols, true);
        $hasOppScore = in_array('score', $oppCols, true);
        $hasOppSourceType = in_array('source_type', $oppCols, true);
        $hasOppSourceSnapshot = in_array('source_snapshot', $oppCols, true);
        $hasOppSnoozedUntil = in_array('snoozed_until', $oppCols, true);

        $dueCutoff = now()->subMonthsNoOverflow(self::DUE_MONTHS)->startOfDay();
        $recentTouchCutoff = now()->subDays(self::RECENT_TOUCH_DAYS);

        // Base query: contacts scoped to agency + book-of-business
        $q = DB::table('contacts')
            ->whereNotNull('policy_issue_date')
            ->where('policy_issue_date', '<=', $dueCutoff);

        if ($agencyOpt !== null && $agencyOpt !== '') {
            $q->where('agency_id', (int) $agencyOpt);
        }

        // Book-of-business scoping:
        // Use in_book_of_business when available; otherwise fall back to contact_type = book/client
        if ($hasInBoB) {
            $q->where(function ($qq) {
                // Supports tinyint(1), boolean-ish, or stringy values
                $qq->where('in_book_of_business', 1)
                   ->orWhere('in_book_of_business', true)
                   ->orWhere('in_book_of_business', '1');
            });
        } else {
            $q->where(function ($qq) {
                $qq->whereRaw('LOWER(contact_type) = ?', ['book'])
                   ->orWhereRaw('LOWER(contact_type) = ?', ['client']);
            });
        }

        // Disqualifier A: "recent touch" inferred from notes + updated_at
        if ($hasNotes && in_array('updated_at', $contactsCols, true)) {
            $q->where(function ($qq) use ($recentTouchCutoff) {
                // qualify if either:
                // - notes empty/null, OR
                // - updated_at older than cutoff
                $qq->whereNull('notes')
                   ->orWhereRaw('TRIM(notes) = ""')
                   ->orWhere('updated_at', '<=', $recentTouchCutoff);
            });
        }

        // Disqualifier B: active service work
        // If service_archived_at exists and is NULL, and service_status has content => treat as active (skip)
        if ($hasServiceArchivedAt && $hasServiceStatus) {
            $q->where(function ($qq) {
                $qq->whereNotNull('service_archived_at')
                   ->orWhereNull('service_status')
                   ->orWhereRaw('TRIM(service_status) = ""');
            });
        }

        // Minimal select
        $select = ['id', 'agency_id', 'created_at', 'updated_at', 'contact_type', 'first_name', 'last_name', 'full_name', 'policy_issue_date'];
        if ($hasTenantId) $select[] = 'tenant_id';
        if ($hasInBoB) $select[] = 'in_book_of_business';
        if ($hasNotes) $select[] = 'notes';
        if ($hasServiceStatus) $select[] = 'service_status';
        if ($hasServiceArchivedAt) $select[] = 'service_archived_at';

        $rows = $q->select($select)->orderBy('policy_issue_date')->get();

        $this->info('Policy reviews qualifying: ' . $rows->count());
        if ($dryRun) {
            $this->warn('Dry run enabled — no writes will occur.');
            return self::SUCCESS;
        }

        $now = now();
        $upserts = 0;

        DB::beginTransaction();

        try {
            foreach ($rows as $c) {
                $contactId = (int) $c->id;
                $agencyId  = (int) $c->agency_id;

                $name = $this->buildContactName($c);

                $policyIssue = Carbon::parse($c->policy_issue_date);
                $ageMonths = $policyIssue->diffInMonths($now);

                // open_url: prefer book.open for book-of-business contacts
                $openUrl = $this->buildOpenUrlForContact($contactId);

                $snapshot = [
                    'contact_id' => $contactId,
                    'contact_type' => 'book',
                    'contact_name' => $name,
                    'policy_issue_date' => $policyIssue->toDateString(),
                    'policy_age_months' => $ageMonths,
                    'last_record_update_at' => !empty($c->updated_at) ? Carbon::parse($c->updated_at)->toDateTimeString() : null,
                    'recent_touch_days_threshold' => self::RECENT_TOUCH_DAYS,
                ];

                if ($openUrl) $snapshot['open_url'] = $openUrl;

                // Find existing opp
                $existing = GideonOpportunity::query()
                    ->where('agency_id', $agencyId)
                    ->where('category', self::CATEGORY)
                    ->where('rule_code', self::RULE_CODE)
                    ->where('entity_type', 'contact')
                    ->where('entity_id', $contactId)
                    ->first();

                // Respect completed/dismissed
                if ($existing && in_array($existing->status, ['completed', 'dismissed'], true)) {
                    continue;
                }

                // Respect active snooze
                if ($existing && $hasOppSnoozedUntil && $existing->status === 'snoozed' && $existing->snoozed_until) {
                    $until = Carbon::parse($existing->snoozed_until);
                    if ($until->isFuture()) {
                        // Update snapshot but keep snooze
                        if ($hasOppSourceSnapshot) {
                            GideonOpportunity::query()->whereKey($existing->id)->update([
                                'source_snapshot' => json_encode($snapshot),
                            ]);
                        }
                        continue;
                    }
                }

                $payload = [
                    'agency_id' => $agencyId,
                    'category' => self::CATEGORY,
                    'rule_code' => self::RULE_CODE,
                    'entity_type' => 'contact',
                    'entity_id' => $contactId,
                    'status' => 'open',
                ];

                if ($hasTenantId && in_array('tenant_id', $oppCols, true)) {
                    $payload['tenant_id'] = $c->tenant_id;
                }

                if ($hasOppSourceType) $payload['source_type'] = 'policy_review_scan';

                if ($hasOppTitle) $payload['title'] = 'Policy review due (6 months)';

                if ($hasOppRecommendedAction) {
                    $payload['recommended_action'] = 'Contact client for a 6-month policy review and confirm beneficiaries, coverage needs, and referrals.';
                }

                if ($hasOppScore) {
                    // Mild scoring; older policies a bit higher, but still P2
                    $payload['score'] = min(90, 30 + ($ageMonths * 2));
                }

                if ($hasOppSourceSnapshot) $payload['source_snapshot'] = json_encode($snapshot);

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

    private function buildContactName(object $c): string
    {
        $full = trim((string) ($c->full_name ?? ''));
        if ($full !== '') return $full;

        $first = trim((string) ($c->first_name ?? ''));
        $last  = trim((string) ($c->last_name ?? ''));

        $name = trim($first . ' ' . $last);
        return $name !== '' ? $name : 'Unnamed Client';
    }

    private function buildOpenUrlForContact(int $contactId): ?string
    {
        try {
            if (Route::has('book.open')) return route('book.open', $contactId);
            if (Route::has('contacts.show')) return route('contacts.show', $contactId);
        } catch (\Throwable $e) {
            report($e);
        }
        return null;
    }
}
