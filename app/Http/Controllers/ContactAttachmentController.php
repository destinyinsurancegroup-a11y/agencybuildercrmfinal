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
                // common business file types (expand later safely)
                'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,csv,txt',
            ],
            'return_to' => 'nullable|string|max:2000',
        ]);

        $agencyId = $this->agencyIdFor($contact, $user);

        $baseDir = "private/agencies/{$agencyId}/contacts/{$contact->id}/attachments";
        Storage::disk('local')->makeDirectory($baseDir);

        foreach ($request->file('files', []) as $file) {
            if (!$file || !$file->isValid()) continue;

            $original = $file->getClientOriginalName();
            $ext = strtolower((string) $file->getClientOriginalExtension());
            $stored = (string) Str::uuid() . ($ext ? ".{$ext}" : '');
            $path = $file->storeAs($baseDir, $stored, 'local');

            Attachment::create([
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
        }

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

        // Inline view
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

        // delete file first (best effort)
        try {
            Storage::disk($attachment->storage_disk)->delete($attachment->storage_path);
        } catch (\Throwable $e) {
            // ignore; still delete DB row to prevent UI leaks
        }

        $attachment->delete();

        return redirect()->to($this->safeReturnTo($request->input('return_to')));
    }

    // ============================================================
    // Security helpers
    // ============================================================

    private function assertSameAgency(Contact $contact): void
    {
        $user = Auth::user();
        if (!$user) abort(403);

        // Your app mainly uses agency_id; Contact has TenantScoped too.
        // This is extra safety.
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
        // Defense-in-depth: agency_id is stored on attachments.
        if (!empty($attachment->agency_id) && !empty($user->agency_id)) {
            if ((int) $attachment->agency_id !== (int) $user->agency_id) {
                abort(403, 'Unauthorized');
            }
        }

        // Also ensure the attachable itself is scoped
        if ($attachment->attachable_type === Contact::class) {
            $contact = Contact::findOrFail($attachment->attachable_id);
            $this->assertSameAgency($contact);
        }
    }

    private function agencyIdFor(Contact $contact, $user): int
    {
        // prefer contact->agency_id, fallback to user->agency_id, then 1
        $agency = (int) ($contact->agency_id ?? ($user->agency_id ?? 1));
        return $agency > 0 ? $agency : 1;
    }

    private function safeReturnTo(?string $returnTo): string
    {
        $fallback = url()->previous() ?: route('dashboard');
        if (!$returnTo) return $fallback;

        // prevent open redirect (only allow same-host URLs)
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

        // Basic ASCII fallback for headers
        $safe = preg_replace('/[^A-Za-z0-9\.\-\_ ]/', '', $name) ?? 'file';
        $safe = trim($safe);
        return $safe !== '' ? $safe : 'file';
    }
}
