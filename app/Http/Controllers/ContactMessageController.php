<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ContactMessageController extends Controller
{
    /**
     * Cache the "does column exist" checks so we don't hit information_schema every request.
     */
    protected static ?bool $contactsHasAgencyId = null;
    protected static ?bool $contactsHasTenantId = null;

    protected function contactsHasAgencyId(): bool
    {
        if (self::$contactsHasAgencyId === null) {
            self::$contactsHasAgencyId = Schema::hasColumn('contacts', 'agency_id');
        }
        return self::$contactsHasAgencyId;
    }

    protected function contactsHasTenantId(): bool
    {
        if (self::$contactsHasTenantId === null) {
            self::$contactsHasTenantId = Schema::hasColumn('contacts', 'tenant_id');
        }
        return self::$contactsHasTenantId;
    }

    /**
     * Best-effort tenant/agency isolation based on your schema.
     */
    protected function enforceScopeOrAbort(Contact $contact): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // If agency_id exists, enforce it when both are present
        if ($this->contactsHasAgencyId() && isset($contact->agency_id) && isset($user->agency_id)) {
            if ((string) $contact->agency_id !== (string) $user->agency_id) {
                abort(403, 'Unauthorized');
            }
        }

        // If tenant_id exists, enforce it when both are present
        if ($this->contactsHasTenantId() && isset($contact->tenant_id) && isset($user->tenant_id)) {
            if ((string) $contact->tenant_id !== (string) $user->tenant_id) {
                abort(403, 'Unauthorized');
            }
        }
    }

    /**
     * Phase 3: message history for a contact
     * GET /contacts/{contact}/messages
     *
     * UI-friendly response for chat/popup:
     * - returns items in ASC order (old -> new)
     * - supports "before_id" to load older messages
     *
     * Query params:
     * - limit (default 50, max 200)
     * - channel: sms|email (optional)
     * - before_id: int (optional) load messages with id < before_id
     */
    public function index(Request $request, Contact $contact)
    {
        $this->enforceScopeOrAbort($contact);

        $limit = (int) $request->query('limit', 50);
        if ($limit < 1) $limit = 50;
        if ($limit > 200) $limit = 200;

        $channel = $request->query('channel');
        $beforeId = $request->query('before_id');

        $q = Message::query()
            ->where('contact_id', $contact->id);

        if (in_array($channel, ['sms', 'email'], true)) {
            $q->where('channel', $channel);
        }

        if (is_numeric($beforeId)) {
            $q->where('id', '<', (int) $beforeId);
        }

        // Pull newest first (cheap for paging), then reverse in memory to display old->new
        $rows = $q->orderByDesc('id')
            ->limit($limit + 1) // +1 to detect has_more
            ->get([
                'id',
                'contact_id',
                'created_by',
                'channel',
                'direction',
                'status',
                'to_address',
                'subject',
                'body',
                'provider',
                'provider_message_id',
                'error_message',
                'created_at',
                'updated_at',
            ]);

        $hasMore = $rows->count() > $limit;
        if ($hasMore) {
            $rows = $rows->slice(0, $limit);
        }

        // For chat UI we want ascending order (oldest -> newest)
        $rows = $rows->reverse()->values();

        $nextBeforeId = null;
        if ($rows->isNotEmpty()) {
            // To load older, we ask for id < oldest currently returned
            $nextBeforeId = (int) $rows->first()->id;
        }

        $contactName = $contact->full_name ?? trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));

        return response()->json([
            'success' => true,
            'contact' => [
                'id'   => $contact->id,
                'name' => $contactName ?: '(No Name)',
            ],
            // What your popup should render:
            'items' => $rows,
            // For "Load earlier messages" / infinite scroll:
            'has_more' => $hasMore,
            'next_before_id' => $hasMore ? $nextBeforeId : null,
        ]);
    }

    /**
     * Phase 2: store outbound messages
     * POST /contacts/{contact}/messages
     */
    public function store(Request $request, Contact $contact)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $this->enforceScopeOrAbort($contact);

        $data = $request->validate([
            'channel' => 'required|in:sms,email',
            'body'    => 'required|string|max:5000',
            'subject' => 'nullable|string|max:255',
        ]);

        // Normalize recipient from contact record
        $toAddress = null;

        if ($data['channel'] === 'sms') {
            $toAddress = $contact->phone;
            if (! $toAddress) {
                return response()->json(['success' => false, 'message' => 'Contact has no phone number.'], 422);
            }
        }

        if ($data['channel'] === 'email') {
            $toAddress = $contact->email;
            if (! $toAddress) {
                return response()->json(['success' => false, 'message' => 'Contact has no email address.'], 422);
            }
            if (empty($data['subject'])) {
                return response()->json(['success' => false, 'message' => 'Subject is required for email.'], 422);
            }
        }

        $message = Message::create([
            'agency_id'  => $contact->agency_id ?? ($user->agency_id ?? null),
            'tenant_id'  => $contact->tenant_id ?? ($user->tenant_id ?? null),
            'contact_id' => $contact->id,
            'created_by' => $user->id,

            'channel'    => $data['channel'],
            'direction'  => 'outbound',
            'status'     => 'queued',

            'to_address' => $toAddress,
            'subject'    => $data['channel'] === 'email' ? trim((string) $data['subject']) : null,
            'body'       => trim((string) $data['body']),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 201);
    }
}
