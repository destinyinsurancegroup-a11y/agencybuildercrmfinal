<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $agencyId = $user->agency_id;
        $tenantId = $user->tenant_id;

        $templates = MessageTemplate::query()
            ->where('agency_id', $agencyId)
            ->when($tenantId, function ($q) use ($tenantId) {
                $q->where(function ($qq) use ($tenantId) {
                    $qq->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenantId);
                });
            }, function ($q) {
                $q->whereNull('tenant_id');
            })
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
            'agency_id'    => $user->agency_id,
            'tenant_id'    => $user->tenant_id,
            'user_id'      => $user->id,
            'template_id'  => $template->id,
            'channel'      => $template->channel,
            'is_active'    => $template->is_active,
        ]);

        return redirect()
            ->route('settings.messaging.templates.index')
            ->with('success', 'Template created.');
    }

    public function edit(Request $request, MessageTemplate $messageTemplate)
    {
        $user = $request->user();

        // Simple tenant/agency guard so users can't edit other agencies' templates
        if ((int)$messageTemplate->agency_id !== (int)$user->agency_id) {
            abort(404);
        }

        // If user has a tenant_id, allow tenant-specific OR global (null)
        if ($user->tenant_id) {
            if (!is_null($messageTemplate->tenant_id) && (int)$messageTemplate->tenant_id !== (int)$user->tenant_id) {
                abort(404);
            }
        } else {
            // If user has no tenant, only allow global (null) templates
            if (!is_null($messageTemplate->tenant_id)) {
                abort(404);
            }
        }

        return view('settings.messaging.templates.edit', [
            'template' => $messageTemplate,
        ]);
    }

    public function update(Request $request, MessageTemplate $messageTemplate)
    {
        $user = $request->user();

        if ((int)$messageTemplate->agency_id !== (int)$user->agency_id) {
            abort(404);
        }

        if ($user->tenant_id) {
            if (!is_null($messageTemplate->tenant_id) && (int)$messageTemplate->tenant_id !== (int)$user->tenant_id) {
                abort(404);
            }
        } else {
            if (!is_null($messageTemplate->tenant_id)) {
                abort(404);
            }
        }

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
            ->route('settings.messaging.templates.edit', $messageTemplate->id)
            ->with('success', 'Template updated.');
    }
}
