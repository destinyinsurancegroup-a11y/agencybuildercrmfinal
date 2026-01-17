<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageTemplateController extends Controller
{
    /**
     * Scope helper: only templates that belong to the logged-in user's agency,
     * and either global (tenant_id null) or matching tenant_id.
     */
    private function scopedTemplatesForUser(Request $request)
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

    public function index(Request $request)
    {
        $templates = $this->scopedTemplatesForUser($request)
            ->orderBy('channel')
            ->orderBy('name')
            ->paginate(25);

        return view('settings.messaging.templates.index', [
            'templates' => $templates,
        ]);
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
            'is_active' => ['nullable'],
        ]);

        // Normalize checkbox -> boolean
        $data['is_active'] = $request->boolean('is_active');

        // Email subject required if channel=email
        if ($data['channel'] === 'email' && empty($data['subject'])) {
            return back()
                ->withErrors(['subject' => 'Subject is required for Email templates.'])
                ->withInput();
        }

        // SMS subject must be null
        if ($data['channel'] === 'sms') {
            $data['subject'] = null;
        }

        $template = MessageTemplate::create([
            'agency_id'       => $user->agency_id,
            'tenant_id'       => $user->tenant_id, // Tier 1 may be null — OK
            'channel'         => $data['channel'],
            'name'            => $data['name'],
            'subject'         => $data['subject'] ?? null,
            'body'            => $data['body'],
            'is_active'       => $data['is_active'] ?? true,
            'created_by'      => $user->id,
            'updated_by'      => $user->id,
            'variables_json'  => null, // optional later
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
            ->route('settings.messaging.templates.index')
            ->with('success', 'Template created.');
    }

    public function edit(Request $request, MessageTemplate $messageTemplate)
    {
        // Security: only allow editing templates the user should be able to see
        $template = $this->scopedTemplatesForUser($request)
            ->whereKey($messageTemplate->id)
            ->firstOrFail();

        return view('settings.messaging.templates.edit', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, MessageTemplate $messageTemplate)
    {
        $user = $request->user();

        // Security: only allow updating templates the user should be able to see
        $template = $this->scopedTemplatesForUser($request)
            ->whereKey($messageTemplate->id)
            ->firstOrFail();

        $data = $request->validate([
            'channel'   => ['required', 'in:sms,email'],
            'name'      => ['required', 'string', 'max:120'],
            'subject'   => ['nullable', 'string', 'max:255'],
            'body'      => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable'],
        ]);

        // Normalize checkbox -> boolean
        $data['is_active'] = $request->boolean('is_active');

        // Email subject required if channel=email
        if ($data['channel'] === 'email' && empty($data['subject'])) {
            return back()
                ->withErrors(['subject' => 'Subject is required for Email templates.'])
                ->withInput();
        }

        // SMS subject must be null
        if ($data['channel'] === 'sms') {
            $data['subject'] = null;
        }

        $template->update([
            'channel'     => $data['channel'],
            'name'        => $data['name'],
            'subject'     => $data['subject'] ?? null,
            'body'        => $data['body'],
            'is_active'   => $data['is_active'] ?? true,
            'updated_by'  => $user->id,
        ]);

        Log::info('message_template.updated', [
            'agency_id'   => $user->agency_id,
            'tenant_id'   => $user->tenant_id,
            'user_id'     => $user->id,
            'template_id' => $template->id,
            'channel'     => $template->channel,
            'is_active'   => $template->is_active,
        ]);

        return redirect()
            ->route('settings.messaging.templates.index')
            ->with('success', 'Template updated.');
    }
}
