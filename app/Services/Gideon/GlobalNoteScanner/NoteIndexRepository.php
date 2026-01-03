<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NoteBatch
{
    public function __construct(
        public array $items,
        public bool $hasMore
    ) {}
}

class NoteIndexRepository
{
    /**
     * Fetch notes from gideon_note_index in ID order.
     * We use cursor pagination so it scales.
     */
    public function fetchBatch(?string $tenantId, ?Carbon $since, ?int $cursor, int $limit): NoteBatch
    {
        $q = DB::table('gideon_note_index')
            ->select([
                'id',
                'tenant_id',
                'entity_type',
                'entity_id',
                'note_id',
                'note_text',
                'note_created_at',
                'note_updated_at',
                'visibility',
            ])
            ->orderBy('id')
            ->limit($limit);

        if ($tenantId) {
            $q->where('tenant_id', $tenantId);
        }

        if ($since) {
            $q->where(function ($qq) use ($since) {
                $qq->where('note_updated_at', '>=', $since)
                   ->orWhere('note_created_at', '>=', $since);
            });
        }

        if ($cursor !== null) {
            $q->where('id', '>', $cursor);
        }

        $rows = $q->get();
        $items = [];

        foreach ($rows as $r) {
            // attach cursor for the scanner to continue
            $r->cursor = (int) $r->id;
            $items[] = $r;
        }

        return new NoteBatch(
            items: $items,
            hasMore: count($items) === $limit
        );
    }
}
