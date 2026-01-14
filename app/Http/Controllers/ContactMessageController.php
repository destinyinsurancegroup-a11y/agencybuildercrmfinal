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
     * Returns JSON (for AJAX UI).
     */
    public function index(Request $request, Contact $contact)
    {
        $this->enforceScopeOrAbort($contact);

        $perPage = (int) $request->query('per_page', 25);
        if ($perPage < 1) $perPage = 25;
        if ($perPage > 100) $perPage = 100;

        $query = Message::query()
            ->where('contact_id', $contact->id)
            ->orderByDesc('created_at');

        // Optional filters for UI (safe defaults)
        if ($request->filled('channel')) {
            $channel = $request->query('channel');
            if (in_array($channel, ['sms', 'email'], true)) {
                $query->where('channel', $channel);
            }
        }

        if ($request->filled('status')) {
            $status = $request->query('status');
            if (in_array($status, ['queued', 'sent', 'failed'], true)) {
                $query->where('status', $status);
            }
        }

        $messages = $query->paginate($perPage);

        return response()->json([
            'success'  => true,
            'contact'  => [
                'id'   => $contact->id,
                'name' => $contact->full_name ?? trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')),
            ],
            'messages' => $messages,
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
