<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ContactMessageController extends Controller
{
    public function store(Request $request, Contact $contact)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        // ✅ Tenant/agency isolation (best-effort based on your schema)
        // If agency_id exists, enforce it.
        if (Schema::hasColumn('contacts', 'agency_id') && isset($contact->agency_id) && isset($user->agency_id)) {
            if ((string)$contact->agency_id !== (string)$user->agency_id) {
                abort(403, 'Unauthorized');
            }
        }

        // If tenant_id exists, enforce it when both are set.
        if (Schema::hasColumn('contacts', 'tenant_id') && isset($contact->tenant_id) && isset($user->tenant_id)) {
            if ((string)$contact->tenant_id !== (string)$user->tenant_id) {
                abort(403, 'Unauthorized');
            }
        }

        $data = $request->validate([
            'channel' => 'required|in:sms,email',
            'body'    => 'required|string|max:5000',
            'subject' => 'nullable|string|max:255',
        ]);

        // Normalize recipient from contact record
        $toAddress = null;

        if ($data['channel'] === 'sms') {
            $toAddress = $contact->phone;
            if (!$toAddress) {
                return response()->json(['success' => false, 'message' => 'Contact has no phone number.'], 422);
            }
        }

        if ($data['channel'] === 'email') {
            $toAddress = $contact->email;
            if (!$toAddress) {
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
            'subject'    => $data['channel'] === 'email' ? trim($data['subject']) : null,
            'body'       => trim($data['body']),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 201);
    }
}
