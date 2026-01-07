<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BackfillGideonNoteIndex extends Command
{
    /**
     * Usage:
     *  php artisan gideon:notes:backfill-index
     *  php artisan gideon:notes:backfill-index --agency=123
     *  php artisan gideon:notes:backfill-index --chunk=500
     *  php artisan gideon:notes:backfill-index --source=contact_notes
     */
    protected $signature = 'gideon:notes:backfill-index {--agency=} {--chunk=1000} {--source=all}';

    protected $description = 'Backfill gideon_note_index from notes and/or contact_notes tables';

    public function handle(): int
    {
        $agencyOpt = $this->option('agency');
        $agencyId = ($agencyOpt !== null && $agencyOpt !== '') ? (int) $agencyOpt : null;

        $chunk = max(100, (int) $this->option('chunk'));
        $source = strtolower((string) $this->option('source'));

        if (!in_array($source, ['all', 'notes', 'contact_notes'], true)) {
            $this->error("Invalid --source value. Use: all | notes | contact_notes");
            return self::FAILURE;
        }

        $this->info("Backfill starting...");
        $this->info("agency: " . ($agencyId ?: 'ALL'));
        $this->info("chunk: {$chunk}");
        $this->info("source: {$source}");

        $total = 0;

        if ($source === 'all' || $source === 'notes') {
            $total += $this->backfillFromNotesTable($agencyId, $chunk);
        }

        if ($source === 'all' || $source === 'contact_notes') {
            $total += $this->backfillFromContactNotesTable($agencyId, $chunk);
        }

        $this->info("Backfill complete. Rows upserted: {$total}");

        return self::SUCCESS;
    }

    /**
     * notes table:
     * notes: id, contact_id, created_by, tenant_id, note, created_at, updated_at
     */
    private function backfillFromNotesTable(?int $agencyId, int $chunk): int
    {
        $this->line("Backfilling from notes...");

        $base = DB::table('notes')
            ->join('contacts', 'contacts.id', '=', 'notes.contact_id')
            ->select([
                'notes.id as id', // IMPORTANT for chunkById
                'notes.id as note_id',
                'notes.contact_id as entity_id',
                'notes.created_by as author_user_id',
                'notes.note as note_text',
                'notes.created_at as note_created_at',
                'notes.updated_at as note_updated_at',
                'contacts.agency_id as agency_id',
            ])
            ->orderBy('notes.id');

        if ($agencyId !== null) {
            $base->where('contacts.agency_id', $agencyId);
        }

        $count = 0;

        $base->chunkById(
            $chunk,
            function ($rows) use (&$count) {
                $payload = [];

                foreach ($rows as $r) {
                    $aid = (int) ($r->agency_id ?? 0);
                    if ($aid <= 0) {
                        continue;
                    }

                    $payload[] = [
                        'tenant_id' => $this->agencyUuid($aid),

                        'agency_id' => $aid,
                        'entity_type' => 'contact',
                        'entity_id' => (int) $r->entity_id,

                        'note_id' => (int) $r->note_id,
                        'author_user_id' => $r->author_user_id ? (int) $r->author_user_id : null,

                        'note_text' => (string) ($r->note_text ?? ''),

                        'note_created_at' => $r->note_created_at ? Carbon::parse($r->note_created_at) : now(),
                        'note_updated_at' => $r->note_updated_at ? Carbon::parse($r->note_updated_at) : null,

                        'source_system' => 'notes',
                        'visibility' => 'public',

                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($payload)) {
                    DB::table('gideon_note_index')->upsert(
                        $payload,
                        ['agency_id', 'source_system', 'note_id'],
                        [
                            'tenant_id',
                            'author_user_id',
                            'entity_type',
                            'entity_id',
                            'note_text',
                            'note_created_at',
                            'note_updated_at',
                            'visibility',
                            'updated_at',
                        ]
                    );

                    $count += count($payload);
                    $this->line("  upserted: " . count($payload));
                }
            },
            'notes.id', // chunk column in DB
            'id'        // alias in select results
        );

        return $count;
    }

    /**
     * contact_notes table:
     * contact_notes: id, tenant_id, contact_id, created_by, body, created_at, updated_at
     */
    private function backfillFromContactNotesTable(?int $agencyId, int $chunk): int
    {
        $this->line("Backfilling from contact_notes...");

        $base = DB::table('contact_notes')
            ->join('contacts', 'contacts.id', '=', 'contact_notes.contact_id')
            ->select([
                'contact_notes.id as id', // IMPORTANT for chunkById
                'contact_notes.id as note_id',
                'contact_notes.contact_id as entity_id',
                'contact_notes.created_by as author_user_id',
                'contact_notes.body as note_text',
                'contact_notes.created_at as note_created_at',
                'contact_notes.updated_at as note_updated_at',
                'contacts.agency_id as agency_id',
            ])
            ->orderBy('contact_notes.id');

        if ($agencyId !== null) {
            $base->where('contacts.agency_id', $agencyId);
        }

        $count = 0;

        $base->chunkById(
            $chunk,
            function ($rows) use (&$count) {
                $payload = [];

                foreach ($rows as $r) {
                    $aid = (int) ($r->agency_id ?? 0);
                    if ($aid <= 0) {
                        continue;
                    }

                    $payload[] = [
                        'tenant_id' => $this->agencyUuid($aid),

                        'agency_id' => $aid,
                        'entity_type' => 'contact',
                        'entity_id' => (int) $r->entity_id,

                        'note_id' => (int) $r->note_id,
                        'author_user_id' => $r->author_user_id ? (int) $r->author_user_id : null,

                        'note_text' => (string) ($r->note_text ?? ''),

                        'note_created_at' => $r->note_created_at ? Carbon::parse($r->note_created_at) : now(),
                        'note_updated_at' => $r->note_updated_at ? Carbon::parse($r->note_updated_at) : null,

                        'source_system' => 'contact_notes',
                        'visibility' => 'public',

                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($payload)) {
                    DB::table('gideon_note_index')->upsert(
                        $payload,
                        ['agency_id', 'source_system', 'note_id'],
                        [
                            'tenant_id',
                            'author_user_id',
                            'entity_type',
                            'entity_id',
                            'note_text',
                            'note_created_at',
                            'note_updated_at',
                            'visibility',
                            'updated_at',
                        ]
                    );

                    $count += count($payload);
                    $this->line("  upserted: " . count($payload));
                }
            },
            'contact_notes.id', // chunk column in DB
            'id'                // alias in select results
        );

        return $count;
    }

    /**
     * Deterministic UUID per agency (no external package).
     * Produces a valid UUID-like string using md5 and forces version bits.
     */
    private function agencyUuid(int $agencyId): string
    {
        $hash = md5('agency-' . $agencyId);

        // Format as UUID v5-ish (not cryptographically important here)
        $timeLow = substr($hash, 0, 8);
        $timeMid = substr($hash, 8, 4);
        $timeHi  = substr($hash, 12, 4);
        $clkSeq  = substr($hash, 16, 4);
        $node    = substr($hash, 20, 12);

        // force version 5 (0101)
        $timeHi = dechex((hexdec($timeHi) & 0x0fff) | 0x5000);
        // force variant (10xx)
        $clkSeq = dechex((hexdec($clkSeq) & 0x3fff) | 0x8000);

        return sprintf(
            '%08s-%04s-%04s-%04s-%012s',
            $timeLow,
            $timeMid,
            str_pad($timeHi, 4, '0', STR_PAD_LEFT),
            str_pad($clkSeq, 4, '0', STR_PAD_LEFT),
            $node
        );
    }
}
