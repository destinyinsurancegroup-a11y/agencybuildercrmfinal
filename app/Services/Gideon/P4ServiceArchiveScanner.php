<?php

namespace App\Services\Gideon;

use App\Models\Contact;
use App\Models\GideonOpportunity;
use Illuminate\Support\Facades\DB;

class P4ServiceArchiveScanner
{
    public function run(int $agencyId): int
    {
        $keywords = [
            'cheaper',
            'found something cheaper',
            'better deal',
            'might come back',
            'call back',
            'follow up',
            'later',
            'down the road',
            'revisit',
            'thinking about it'
        ];

        $created = 0;

        // 90–180 days first, then older
        $contacts = Contact::where('agency_id', $agencyId)
            ->where('contact_type', 'service')
            ->whereNotNull('service_archived_at')
            ->where('service_archived_at', '<=', now()->subDays(90))
            ->orderBy('service_archived_at')
            ->get();

        foreach ($contacts as $contact) {

            // skip if P4 already exists
            $already = GideonOpportunity::where('contact_id', $contact->id)
                ->where('category', 'p4_service_recovery')
                ->exists();

            if ($already) {
                continue;
            }

            // load notes
            $notes = DB::table('notes')
                ->where('contact_id', $contact->id)
                ->get();

            if ($notes->isEmpty()) {
                continue;
            }

            $text = strtolower(
                $notes->pluck('note')
                      ->merge($notes->pluck('body'))
                      ->filter()
                      ->implode(' ')
            );

            foreach ($keywords as $word) {
                if (str_contains($text, $word)) {

                    GideonOpportunity::create([
                        'agency_id' => $agencyId,
                        'contact_id' => $contact->id,
                        'category' => 'p4_service_recovery',
                        'title' => 'Archived Service Re-engagement',
                        'summary' => 'Archived service contact showed hesitation or price concern.',
                        'score' => 35,
                        'status' => 'open',
                        'source' => 'p4',
                        'created_at' => now(),
                    ]);

                    $created++;
                    break;
                }
            }
        }

        return $created;
    }
}
