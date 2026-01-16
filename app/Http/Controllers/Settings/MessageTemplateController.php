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

        // Email subject required if channel=email
        if (($data['channel'] ?? null) === 'email' && empty($data['subject'])) {
            return back()
                ->withErrors(['subject' => 'Subject is required for Email templates.'])
                ->withInput();
        }

        // SMS subject must be null
        if (($data['channel'] ?? null) === 'sms') {
            $data['subject'] = null;
        }

        $template = MessageTemplate::create([
            'agency_id'   => $user->agency_id,
            'tenant_id'   => $user->tenant_id, // Tier 1 may be null — OK
            'channel'     => $data['channel'],
            'name'        => $data['name'],
            'subject'     => $data['subject'] ?? null,
            'body'        => $data['body'],
            'is_active'   => (bool)($data['is_active'] ?? true),
            'created_by'  => $user->id,
            'updated_by'  => $user->id,
            'variables_json' => null, // optional later
        ]);

        // SOC-2 style audit log (no PHI content, no template body in logs)
        Log::info('message_template.created', [
            'agency_id' => $user->agency_id,
            'tenant_id' => $user->tenant_id,
            'user_id'   => $user->id,
            'template_id' => $template->id,
            'channel'   => $template->channel,
            'is_active' => $template->is_active,
        ]);

        return redirect()
            ->route('settings.messaging.templates.index')
            ->with('success', 'Template created.');
    }
}
