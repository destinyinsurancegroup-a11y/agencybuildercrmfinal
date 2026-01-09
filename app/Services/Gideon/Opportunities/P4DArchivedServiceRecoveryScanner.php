<?php

namespace App\Services\Gideon\Opportunities;

use App\Models\GideonOpportunity;
use App\Models\ServiceEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class P4DArchivedServiceRecoveryScanner
{
    public const RULE_CODE = 'P4-ARCHIVED-SERVICE-RECOVERY';
    public const CATEGORY  = 'Policy Recovery';

    // Keep these in-code to match your existing P1/P2 approach (no new .env/config).
    private const MIN_ARCHIVE_DAYS = 120;
    private const MONTHLY_CAP = 2;

    // Allowlist signals (your requested additions included)
    private const ALLOWLIST = [
        'found something cheaper',
        'cheaper',
        'lower premium',
        'too expensive',
        'couldn\'t afford',
        'cannot afford',
        'premium too high',
        'price too high',

        'got something better',
        'something better',
        'better coverage',
        'switched',
        'switched policies',
        'went with another company',
        'another agent',
        'got their coverage',
        'already covered',
        'have coverage now',
        'taken care of',
        'handled elsewhere',

        'misled',
        'was misled',
        'confused',
        'not what i thought',
        'explained wrong',
        'inferior coverage',
        'inferor coverage', // common misspelling
    ];

    // Hard disqualifiers
    private const BLOCKLIST = [
        'do not contact',
        'stop calling',
        'remove me',
        'leave me alone',
        'never call',

        'complaint',
        'reported',
        'attorney',
        'lawsuit',
        'insurance department',

        'scam',
        'fraud',
        'angry',
        'threatened',
    ];

    /**
     * Run scan for a single agency.
     * $dryRun = true => NO inserts; logs only.
     */
    public function scan(int $agencyId, bool $dryRun = true): array
    {
        $cutoff = Carbon::now()->subDays(self::MIN_ARCHIVE_DAYS);

        // 1) Monthly cap: count created this month for this agency + rule
        $createdThisMonth = GideonOpportunity::query()
            ->where('agency_id', $agencyId)
            ->where('rule_code', self::RULE_CODE)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        if ($createdThisMonth >= self::MONTHLY_CAP) {
            $this->log($agencyId, $dryRun, "Monthly cap reached.");
            return ['created' => 0, 'matched' => 0, 'skipped' => 0, 'reason' => 'cap_reached'];
        }

        $remaining = self::MONTHLY_CAP - $createdThisMonth;

        // 2) Build candidate query from service_events (defensive schema handling)
        $serviceTable = (new ServiceEvent())->getTable();

        if (!Schema::hasColumn($serviceTable, 'agency_id') || !Schema::hasColumn($serviceTable, 'contact_id')) {
            $this->log($agencyId, $dryRun, "Missing agency_id/contact_id on {$serviceTable}. Aborting safely.");
            return ['created' => 0, 'matched' => 0, 'skipped' => 0, 'reason' => 'schema_missing'];
        }

        $q = ServiceEvent::query()->where('agency_id', $agencyId);

        // ARCHIVE DETECTION (this is where we must match your existing implementation)
        // We try common patterns, and if none exist, we abort (safe).
        $hasArchivedAt = Schema::hasColumn($serviceTable, 'archived_at');
        $hasIsArchived = Schema::hasColumn($serviceTable, 'is_archived');
        $hasStatus     = Schema::hasColumn($serviceTable, 'status');
        $hasDisposition = Schema::hasColumn($serviceTable, 'disposition');
        $hasOutcome     = Schema::hasColumn($serviceTable, 'outcome');

        if ($hasArchivedAt) {
            $q->whereNotNull('archived_at')
              ->where('archived_at', '<=', $cutoff)
              ->orderBy('archived_at', 'asc');
        } elseif ($hasIsArchived) {
            $q->where('is_archived', true)
              ->where((Schema::hasColumn($serviceTable, 'updated_at') ? 'updated_at' : 'created_at'), '<=', $cutoff)
              ->orderBy((Schema::hasColumn($serviceTable, 'updated_at') ? 'updated_at' : 'created_at'), 'asc');
        } elseif ($hasStatus) {
            // You likely encode “Not Interested” here (based on your UI badge)
            $q->whereIn('status', ['archived', 'not_interested', 'Not Interested'])
              ->where((Schema::hasColumn($serviceTable, 'updated_at') ? 'updated_at' : 'created_at'), '<=', $cutoff)
              ->orderBy((Schema::hasColumn($serviceTable, 'updated_at') ? 'updated_at' : 'created_at'), 'asc');
        } else {
            $this->log($agencyId, $dryRun, "No archive indicator found on {$serviceTable}. Aborting safely.");
            return ['created' => 0, 'matched' => 0, 'skipped' => 0, 'reason' => 'no_archive_indicator'];
        }

        // Optional tightening: if you store “Not Interested” in a separate column, enforce it.
        if ($hasDisposition) {
            $q->whereIn('disposition', ['not_interested', 'Not Interested', 'not interested']);
        } elseif ($hasOutcome) {
            $q->whereIn('outcome', ['not_interested', 'Not Interested', 'not interested']);
        }

        // Idempotency: never create twice for same contact+rule
        $q->whereNotExists(function ($sub) use ($agencyId) {
            $sub->select(DB::raw(1))
                ->from('gideon_opportunities as go')
                ->where('go.agency_id', $agencyId)
                ->where('go.entity_type', 'contact')
                ->whereColumn('go.entity_id', 'service_events.contact_id')
                ->where('go.rule_code', self::RULE_CODE);
        });

        $candidates = $q->limit($remaining)->get();

        $matched = 0;
        $created = 0;
        $skipped = 0;

        foreach ($candidates as $serviceEvent) {
            $contactId = (int) $serviceEvent->contact_id;

            $corpus = $this->getContactTextCorpus($agencyId, $contactId);
            if ($corpus === null) {
                $skipped++;
                continue;
            }

            if ($this->containsAny($corpus, self::BLOCKLIST)) {
                $skipped++;
                continue;
            }

            if (!$this->containsAny($corpus, self::ALLOWLIST)) {
                $skipped++;
                continue;
            }

            $matched++;

            if ($dryRun) {
                $this->log($agencyId, true, "DRY MATCH contact_id={$contactId}, service_event_id={$serviceEvent->id}");
                continue;
            }

            // safe insert
            GideonOpportunity::query()->firstOrCreate(
                [
                    'agency_id'   => $agencyId,
                    'entity_type' => 'contact',
                    'entity_id'   => $contactId,
                    'rule_code'   => self::RULE_CODE,
                ],
                [
                    'category'    => self::CATEGORY,
                    'title'       => 'Archived Service Recovery',
                    'description' => 'Archived service client with affordability/misled/better/covered signals. Optional recovery check.',
                    'priority'    => 10, // low
                    'metadata'    => [
                        'service_event_id' => $serviceEvent->id,
                        'archived_at'      => $hasArchivedAt ? $serviceEvent->archived_at : null,
                        'min_archive_days' => self::MIN_ARCHIVE_DAYS,
                    ],
                ]
            );

            $created++;
        }

        $this->log($agencyId, $dryRun, "Done. candidates={$candidates->count()} matched={$matched} created={$created} skipped={$skipped}");

        return compact('matched', 'created', 'skipped') + ['reason' => 'ok'];
    }

    /**
     * Prefer gideon_note_index if present; fallback to notes/contact_notes.
     * If nothing exists, returns null (safe: no guessing).
     */
    private function getContactTextCorpus(int $agencyId, int $contactId): ?string
    {
        // 1) gideon_note_index (you have migrations for it)
        if (Schema::hasTable('gideon_note_index')) {
            $cols = Schema::getColumnListing('gideon_note_index');
            $agencyCol  = in_array('agency_id', $cols, true) ? 'agency_id' : null;
            $contactCol = in_array('contact_id', $cols, true) ? 'contact_id' : null;
            $textCol    = in_array('content', $cols, true) ? 'content' : (in_array('text', $cols, true) ? 'text' : null);

            if ($contactCol && $textCol) {
                $q = DB::table('gideon_note_index')->where($contactCol, $contactId);
                if ($agencyCol) $q->where($agencyCol, $agencyId);

                $parts = $q->pluck($textCol)->filter()->all();
                return strtolower(implode(' ', $parts));
            }
        }

        // 2) notes table
        if (Schema::hasTable('notes')) {
            $cols = Schema::getColumnListing('notes');
            if (in_array('contact_id', $cols, true)) {
                $textCol = in_array('body', $cols, true) ? 'body' : (in_array('content', $cols, true) ? 'content' : null);
                if ($textCol) {
                    $q = DB::table('notes')->where('contact_id', $contactId);
                    if (in_array('agency_id', $cols, true)) $q->where('agency_id', $agencyId);
                    $parts = $q->pluck($textCol)->filter()->all();
                    return strtolower(implode(' ', $parts));
                }
            }
        }

        // 3) contact_notes table (you have create_contact_notes_table)
        if (Schema::hasTable('contact_notes')) {
            $cols = Schema::getColumnListing('contact_notes');
            if (in_array('contact_id', $cols, true)) {
                $textCol = in_array('body', $cols, true) ? 'body' : (in_array('note', $cols, true) ? 'note' : null);
                if ($textCol) {
                    $q = DB::table('contact_notes')->where('contact_id', $contactId);
                    if (in_array('agency_id', $cols, true)) $q->where('agency_id', $agencyId);
                    $parts = $q->pluck($textCol)->filter()->all();
                    return strtolower(implode(' ', $parts));
                }
            }
        }

        return null;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            $n = strtolower(trim($n));
            if ($n !== '' && str_contains($haystack, $n)) {
                return true;
            }
        }
        return false;
    }

    private function log(int $agencyId, bool $dryRun, string $msg): void
    {
        Log::info('[GIDEON:P4-D] ' . ($dryRun ? '[DRY]' : '[LIVE]') . " agency_id={$agencyId} {$msg}");
    }
}
