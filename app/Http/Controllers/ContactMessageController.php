<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

// ✅ Option A: server-side variable replacement for {{first_name}}, etc.
use App\Services\Messaging\TemplateVariableResolver;

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
     * Returns true if allowed, false if not.
     */
    protected function canAccessContact(Contact $contact): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // Agency check (only if the column exists and both have values)
        if ($this->contactsHasAgencyId() && isset($contact->agency_id) && isset($user->agency_id)) {
            if ((string) $contact->agency_id !== (string) $user->agency_id) {
                return false;
            }
        }

        // Tenant check (only if the column exists and both have values)
        if ($this->contactsHasTenantId() && isset($contact->tenant_id) && isset($user->tenant_id)) {
            if ((string) $contact->tenant_id !== (string) $user->tenant_id) {
                return false;
            }
        }

        return true;
    }

    protected function enforceScopeOrAbort(Contact $contact): void
    {
        if (!Auth::user()) {
            abort(403);
        }
        if (!$this->canAccessContact($contact)) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Phase 3: message history for a contact
     * GET /contacts/{contact}/messages
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

        $channel  = $request->query('channel');
        $beforeId = $request->query('before_id');

        $q = Message::query()->where('contact_id', $contact->id);

        if (in_array($channel, ['sms', 'email'], true)) {
            $q->where('channel', $channel);
        }

        if (is_numeric($beforeId)) {
            $q->where('id', '<', (int) $beforeId);
        }

        $rows = $q->orderByDesc('id')
            ->limit($limit + 1)
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

        // Return oldest->newest in UI
        $rows = $rows->reverse()->values();

        $nextBeforeId = null;
        if ($rows->isNotEmpty()) {
            $nextBeforeId = (int) $rows->first()->id;
        }

        $contactName = $contact->full_name ?? trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));

        return response()->json([
            'success' => true,
            'contact' => [
                'id'   => $contact->id,
                'name' => $contactName ?: '(No Name)',
            ],
            'items' => $rows,
            'has_more' => $hasMore,
            'next_before_id' => $hasMore ? $nextBeforeId : null,
        ]);
    }

    /**
     * Phase 2: store outbound messages (queued)
     * POST /contacts/{contact}/messages
     */
    public function store(Request $request, Contact $contact)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $this->enforceScopeOrAbort($contact);

        $data = $request->validate([
            'channel' => 'required|in:sms,email',
            'body'    => 'required|string|max:5000',
            'subject' => 'nullable|string|max:255',
        ]);

        $channel = $data['channel'];

        $toAddress = null;

        if ($channel === 'sms') {
            $toAddress = $contact->phone;
            if (!$toAddress) {
                return response()->json(['success' => false, 'message' => 'Contact has no phone number.'], 422);
            }
        }

        if ($channel === 'email') {
            $toAddress = $contact->email;
            if (!$toAddress) {
                return response()->json(['success' => false, 'message' => 'Contact has no email address.'], 422);
            }
            if (empty($data['subject'])) {
                return response()->json(['success' => false, 'message' => 'Subject is required for email.'], 422);
            }
        }

        // ✅ Option A: Resolve template variables at SEND time (saved history shows real values)
        $rawBody = trim((string) ($data['body'] ?? ''));
        $resolvedBody = TemplateVariableResolver::resolve($rawBody, $contact, $user);

        $resolvedSubject = null;
        if ($channel === 'email') {
            $rawSubject = trim((string) ($data['subject'] ?? ''));
            $resolvedSubject = TemplateVariableResolver::resolve($rawSubject, $contact, $user);
        }

        $message = Message::create([
            'agency_id'  => $contact->agency_id ?? ($user->agency_id ?? null),
            'tenant_id'  => $contact->tenant_id ?? ($user->tenant_id ?? null),
            'contact_id' => $contact->id,
            'created_by' => $user->id,

            'channel'    => $channel,
            'direction'  => 'outbound',
            'status'     => 'queued',

            'to_address' => $toAddress,
            'subject'    => $resolvedSubject,
            'body'       => $resolvedBody,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 201);
    }

    /**
     * ✅ Bulk queue SMS (checkbox-selected contacts)
     * POST /contacts/messages/bulk
     *
     * Payload:
     * - contact_ids: array<int>
     * - body: string
     */
    public function bulkStore(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'contact_ids'   => 'required|array|min:1',
            'contact_ids.*' => 'integer',
            'body'          => 'required|string|max:5000',
        ]);

        $ids  = array_values(array_unique(array_map('intval', $data['contact_ids'])));
        $rawBody = trim((string) ($data['body'] ?? ''));

        // Pull contacts (best-effort scoping in SQL when possible)
        $contactsQ = Contact::query()->whereIn('id', $ids);

        // If contacts has agency_id, match same agency (or allow null if your legacy data uses null)
        if ($this->contactsHasAgencyId() && isset($user->agency_id)) {
            $contactsQ->where(function ($q) use ($user) {
                $q->whereNull('agency_id')->orWhere('agency_id', $user->agency_id);
            });
        }

        // If contacts has tenant_id, match same tenant (or allow null if "global")
        if ($this->contactsHasTenantId() && isset($user->tenant_id)) {
            $contactsQ->where(function ($q) use ($user) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $user->tenant_id);
            });
        }

        // We need names for per-contact variable resolution
        $contacts = $contactsQ->get([
            'id',
            'first_name',
            'last_name',
            'full_name',
            'phone',
            'email',
            'agency_id',
            'tenant_id',
        ]);

        $queued = 0;
        $skippedNoPhone = 0;
        $skippedUnauthorized = 0;

        // Contacts not returned by SQL filter (not found or outside scope)
        $skippedNotFoundOrUnauthorized = max(0, count($ids) - $contacts->count());

        foreach ($contacts as $contact) {
            // Final per-row security check (but do NOT abort mid-loop)
            if (!$this->canAccessContact($contact)) {
                $skippedUnauthorized++;
                continue;
            }

            $to = trim((string) ($contact->phone ?? ''));
            if ($to === '') {
                $skippedNoPhone++;
                continue;
            }

            // ✅ Option A for Bulk: resolve variables per-contact at SEND time
            $resolvedBody = TemplateVariableResolver::resolve($rawBody, $contact, $user);

            Message::create([
                'agency_id'  => $contact->agency_id ?? ($user->agency_id ?? null),
                'tenant_id'  => $contact->tenant_id ?? ($user->tenant_id ?? null),
                'contact_id' => $contact->id,
                'created_by' => $user->id,

                'channel'    => 'sms',
                'direction'  => 'outbound',
                'status'     => 'queued',

                'to_address' => $to,
                'subject'    => null,
                'body'       => $resolvedBody,
            ]);

            $queued++;
        }

        return response()->json([
            'success' => true,
            'queued'  => $queued,
            'skipped' => [
                'no_phone' => $skippedNoPhone,
                'unauthorized' => $skippedUnauthorized,
                'not_found_or_unauthorized' => $skippedNotFoundOrUnauthorized,
            ],
        ], 201);
    }
}
