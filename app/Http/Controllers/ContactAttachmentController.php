<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContactAttachmentController extends Controller
{
    /**
     * ✅ LIST attachments for a Contact (used by the modal).
     * GET /contacts/{contact}/attachments
     * Returns JSON: { success: true, attachments: [...] }
     */
    public function index(Contact $contact)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $this->assertSameAgency($contact);

        // 🚫 No attachments for leads
        if (strtolower((string) $contact->contact_type) === 'lead') {
            return response()->json([
                'success' => false,
                'message' => 'Attachments are not enabled for leads.',
                'attachments' => [],
            ], 403);
        }

        $agencyId = $this->agencyIdFor($contact, $user);

        $attachments = Attachment::query()
            ->where('agency_id', $agencyId)
            ->where('attachable_type', Contact::class)
            ->where('attachable_id', $contact->id)
            ->latest()
            ->get()
            ->map(function (Attachment $a) {
                return [
                    'id'            => $a->id,
                    'name'          => $a->original_name ?: $a->stored_name,
                    'original_name' => $a->original_name ?: $a->stored_name,
                    'mime'          => $a->mime_type,
                    'size_bytes'    => (int) ($a->size_bytes ?? 0),
                    'created_at'    => optional($a->created_at)->toIso8601String(),
                    'created_at_local' => optional($a->created_at)->format('m/d/Y g:i A'),

                    // These routes are GLOBAL (not nested) — see web.php changes next.
                    'view_url'      => route('attachments.show', $a->id),
                    'download_url'  => route('attachments.download', $a->id),
                    'delete_url'    => route('attachments.destroy', $a->id),
                ];
            });

        return response()->json([
            'success' => true,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Upload one or more files to a Contact (Option A).
     * POST /contacts/{contact}/attachments
     */
    public function store(Request $request, Contact $contact)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $this->assertSameAgency($contact);

        // 🚫 No attachments for leads
        if (strtolower((string) $contact->contact_type) === 'lead') {
            abort(403, 'Attachments are not enabled for leads.');
        }

        $validated = $request->validate([
            'files'   => 'required|array|min:1|max:10',
            'files.*' => [
                'file',
                'max:51200', // 50MB per file
                'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,csv,txt',
            ],
            'return_to' => 'nullable|string|max:2000',
        ]);

        $agencyId = $this->agencyIdFor($contact, $user);

        $baseDir = "private/agencies/{$agencyId}/contacts/{$contact->id}/attachments";
        Storage::disk('local')->makeDirectory($baseDir);

        $createdIds = [];

        foreach ($request->file('files', []) as $file) {
            if (!$file || !$file->isValid()) continue;

            $original = $file->getClientOriginalName();
            $ext = strtolower((string) $file->getClientOriginalExtension());
            $stored = (string) Str::uuid() . ($ext ? ".{$ext}" : '');
            $path = $file->storeAs($baseDir, $stored, 'local');

            $att = Attachment::create([
                'agency_id'       => $agencyId,
                'attachable_type' => Contact::class,
                'attachable_id'   => $contact->id,
                'original_name'   => $original ?: $stored,
                'stored_name'     => $stored,
                'mime_type'       => $file->getClientMimeType(),
                'size_bytes'      => (int) $file->getSize(),
                'storage_disk'    => 'local',
                'storage_path'    => $path,
                'created_by'      => $user->id,
            ]);

            $createdIds[] = $att->id;
        }

        // ✅ If modal (AJAX), return JSON so UI can refresh without a full reload.
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'created_ids' => $createdIds,
            ], 201);
        }

        // Normal form submit fallback
        return redirect()->to($this->safeReturnTo($request->input('return_to')));
    }

    /**
     * View inline (for modal preview).
     * GET /attachments/{attachment}
     */
    public function show(Attachment $attachment)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $this->assertAttachmentAgency($attachment, $user);

        $path = $attachment->storage_path;
        if (!Storage::disk($attachment->storage_disk)->exists($path)) {
            abort(404);
        }

        $abs = Storage::disk($attachment->storage_disk)->path($path);
        $mime = $attachment->mime_type ?: 'application/octet-stream';

        return response()->file($abs, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $this->asciiFallbackName($attachment->original_name) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Download (forces download).
     * GET /attachments/{attachment}/download
     */
    public function download(Attachment $attachment)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $this->assertAttachmentAgency($attachment, $user);

        $path = $attachment->storage_path;
        if (!Storage::disk($attachment->storage_disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($attachment->storage_disk)->download(
            $path,
            $attachment->original_name ?: $attachment->stored_name,
            ['X-Content-Type-Options' => 'nosniff']
        );
    }

    /**
     * Delete.
     * DELETE /attachments/{attachment}
     */
    public function destroy(Request $request, Attachment $attachment)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $this->assertAttachmentAgency($attachment, $user);

        try {
            Storage::disk($attachment->storage_disk)->delete($attachment->storage_path);
        } catch (\Throwable $e) {
            // ignore
        }

        $attachment->delete();

        // ✅ If modal (AJAX), return JSON so UI can remove it instantly.
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->to($this->safeReturnTo($request->input('return_to')));
    }

    // ============================================================
    // Security helpers
    // ============================================================

    private function assertSameAgency(Contact $contact): void
    {
        $user = Auth::user();
        if (!$user) abort(403);

        if (Schema::hasColumn('contacts', 'agency_id')) {
            $c = (int) ($contact->agency_id ?? 0);
            $u = (int) ($user->agency_id ?? 0);
            if ($c && $u && $c !== $u) abort(403, 'Unauthorized');
        } elseif (Schema::hasColumn('contacts', 'tenant_id')) {
            $c = (int) ($contact->tenant_id ?? 0);
            $u = (int) ($user->tenant_id ?? 0);
            if ($c && $u && $c !== $u) abort(403, 'Unauthorized');
        }
    }

    private function assertAttachmentAgency(Attachment $attachment, $user): void
    {
        if (!empty($attachment->agency_id) && !empty($user->agency_id)) {
            if ((int) $attachment->agency_id !== (int) $user->agency_id) {
                abort(403, 'Unauthorized');
            }
        }

        if ($attachment->attachable_type === Contact::class) {
            $contact = Contact::findOrFail($attachment->attachable_id);
            $this->assertSameAgency($contact);
        }
    }

    private function agencyIdFor(Contact $contact, $user): int
    {
        $agency = (int) ($contact->agency_id ?? ($user->agency_id ?? 1));
        return $agency > 0 ? $agency : 1;
    }

    private function safeReturnTo(?string $returnTo): string
    {
        $fallback = url()->previous() ?: route('dashboard');
        if (!$returnTo) return $fallback;

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $targetHost = parse_url($returnTo, PHP_URL_HOST);

        if ($targetHost && $appHost && strtolower($targetHost) !== strtolower($appHost)) {
            return $fallback;
        }

        return $returnTo;
    }

    private function asciiFallbackName(string $name): string
    {
        $name = trim($name);
        if ($name === '') return 'file';

        $safe = preg_replace('/[^A-Za-z0-9\.\-\_ ]/', '', $name) ?? 'file';
        $safe = trim($safe);
        return $safe !== '' ? $safe : 'file';
    }
}
