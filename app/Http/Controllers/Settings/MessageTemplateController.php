<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageTemplateController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Base query scoped to the logged-in user's agency + tenant rules.
     */
    private function scopedTemplatesQuery(Request $request)
    {
        $user = $request->user();

        $agencyId = $user->agency_id;
        $tenantId = $user->tenant_id;

        return MessageTemplate::query()
            ->where('agency_id', $agencyId)
            ->when($tenantId, function ($q) use ($tenantId) {
                $q->where(function ($qq) use ($tenantId) {
                    $qq->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenantId);
                });
            }, function ($q) {
                $q->whereNull('tenant_id');
            });
    }

    /**
     * Guard so a user can't access another agency/tenant template.
     */
    private function guardTemplateAccess(Request $request, MessageTemplate $messageTemplate): void
    {
        $user = $request->user();

        // Must match agency
        if ((int) $messageTemplate->agency_id !== (int) $user->agency_id) {
            abort(404);
        }

        // Tenant rules
        if ($user->tenant_id) {
            // allow tenant-specific OR global (null)
            if (!is_null($messageTemplate->tenant_id) && (int) $messageTemplate->tenant_id !== (int) $user->tenant_id) {
                abort(404);
            }
        } else {
            // if user has no tenant, only allow global templates
            if (!is_null($messageTemplate->tenant_id)) {
                abort(404);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ NEW: JSON endpoints (for template dropdown in send modals)
    |--------------------------------------------------------------------------
    | These are safe, scoped, and channel-filtered.
    | Step 2a will add the routes that call these.
    */

    /**
     * GET /settings/messaging/templates/json?channel=sms|email
     * Returns active templates for the given channel.
     */
    public function json(Request $request)
    {
        $data = $request->validate([
            'channel' => ['required', 'in:sms,email'],
        ]);

        $templates = $this->scopedTemplatesQuery($request)
            ->where('channel', $data['channel'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'channel', 'name']);

        return response()->json([
            'success' => true,
            'items'   => $templates,
        ]);
    }

    /**
     * GET /settings/messaging/templates/{messageTemplate}/json
     * Returns one template (scoped) including subject/body (subject may be null for sms).
     */
    public function showJson(Request $request, MessageTemplate $messageTemplate)
    {
        $this->guardTemplateAccess($request, $messageTemplate);

        return response()->json([
            'success' => true,
            'item' => [
                'id'       => $messageTemplate->id,
                'channel'  => $messageTemplate->channel,
                'name'     => $messageTemplate->name,
                'subject'  => $messageTemplate->subject, // null for sms
                'body'     => $messageTemplate->body,
                'is_active'=> (bool) $messageTemplate->is_active,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Chooser + Libraries
    |--------------------------------------------------------------------------
    */

    /**
     * GET /settings/messaging/templates
     * Shows chooser card: Email vs Text
     */
    public function choose(Request $request)
    {
        return view('settings.messaging.templates.choose');
    }

    /**
     * GET /settings/messaging/templates/email
     * Email templates library
     */
    public function emailIndex(Request $request)
    {
        $templates = $this->scopedTemplatesQuery($request)
            ->where('channel', 'email')
            ->orderBy('name')
            ->paginate(25);

        return view('settings.messaging.templates.email.index', [
            'templates' => $templates,
        ]);
    }

    /**
     * GET /settings/messaging/templates/sms
     * SMS templates library
     */
    public function smsIndex(Request $request)
    {
        $templates = $this->scopedTemplatesQuery($request)
            ->where('channel', 'sms')
            ->orderBy('name')
            ->paginate(25);

        return view('settings.messaging.templates.sms.index', [
            'templates' => $templates,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Existing CRUD
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        return redirect()->route('settings.messaging.templates.choose');
    }

    public function create(Request $request)
    {
        return view('settings.messaging.templates.create');
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'channel'   => ['required', 'in:sms,email'],
            'name'      => ['required', 'string', 'max:120'],
            'subject'   => ['nullable', 'string', 'max:255'],
            'body'      => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (($data['channel'] ?? null) === 'email' && empty($data['subject'])) {
            return back()
                ->withErrors(['subject' => 'Subject is required for Email templates.'])
                ->withInput();
        }

        if (($data['channel'] ?? null) === 'sms') {
            $data['subject'] = null;
        }

        $template = MessageTemplate::create([
            'agency_id'      => $user->agency_id,
            'tenant_id'      => $user->tenant_id,
            'channel'        => $data['channel'],
            'name'           => $data['name'],
            'subject'        => $data['subject'] ?? null,
            'body'           => $data['body'],
            'is_active'      => (bool)($data['is_active'] ?? true),
            'created_by'     => $user->id,
            'updated_by'     => $user->id,
            'variables_json' => null,
        ]);

        Log::info('message_template.created', [
            'agency_id'   => $user->agency_id,
            'tenant_id'   => $user->tenant_id,
            'user_id'     => $user->id,
            'template_id' => $template->id,
            'channel'     => $template->channel,
            'is_active'   => $template->is_active,
        ]);

        return redirect()
            ->route($template->channel === 'email'
                ? 'settings.messaging.templates.email'
                : 'settings.messaging.templates.sms')
            ->with('success', 'Template created.');
    }

    /**
     * Optional "show" page. For now, just redirect to edit.
     */
    public function show(Request $request, MessageTemplate $messageTemplate)
    {
        $this->guardTemplateAccess($request, $messageTemplate);

        return redirect()->route('settings.messaging.templates.edit', $messageTemplate->id);
    }

    public function edit(Request $request, MessageTemplate $messageTemplate)
    {
        $this->guardTemplateAccess($request, $messageTemplate);

        return view('settings.messaging.templates.edit', [
            'template' => $messageTemplate,
        ]);
    }

    public function update(Request $request, MessageTemplate $messageTemplate)
    {
        $user = $request->user();

        $this->guardTemplateAccess($request, $messageTemplate);

        $data = $request->validate([
            'channel'   => ['required', 'in:sms,email'],
            'name'      => ['required', 'string', 'max:120'],
            'subject'   => ['nullable', 'string', 'max:255'],
            'body'      => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (($data['channel'] ?? null) === 'email' && empty($data['subject'])) {
            return back()
                ->withErrors(['subject' => 'Subject is required for Email templates.'])
                ->withInput();
        }

        if (($data['channel'] ?? null) === 'sms') {
            $data['subject'] = null;
        }

        $messageTemplate->update([
            'channel'    => $data['channel'],
            'name'       => $data['name'],
            'subject'    => $data['subject'] ?? null,
            'body'       => $data['body'],
            'is_active'  => (bool)($data['is_active'] ?? false),
            'updated_by' => $user->id,
        ]);

        Log::info('message_template.updated', [
            'agency_id'   => $user->agency_id,
            'tenant_id'   => $user->tenant_id,
            'user_id'     => $user->id,
            'template_id' => $messageTemplate->id,
            'channel'     => $messageTemplate->channel,
            'is_active'   => $messageTemplate->is_active,
        ]);

        return redirect()
            ->route($messageTemplate->channel === 'email'
                ? 'settings.messaging.templates.email'
                : 'settings.messaging.templates.sms')
            ->with('success', 'Template updated.');
    }

    public function destroy(Request $request, MessageTemplate $messageTemplate)
    {
        $user = $request->user();

        $this->guardTemplateAccess($request, $messageTemplate);

        $channel = $messageTemplate->channel;
        $id = $messageTemplate->id;

        $messageTemplate->delete();

        Log::info('message_template.deleted', [
            'agency_id'   => $user->agency_id,
            'tenant_id'   => $user->tenant_id,
            'user_id'     => $user->id,
            'template_id' => $id,
            'channel'     => $channel,
        ]);

        return redirect()
            ->route($channel === 'email'
                ? 'settings.messaging.templates.email'
                : 'settings.messaging.templates.sms')
            ->with('success', 'Template deleted.');
    }
}
