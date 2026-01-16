<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Hard-scope to agency (non-negotiable multi-tenant isolation)
        $agencyId = $user->agency_id;

        // Tenant can be null in Tier 1, but keep safe scoping anyway
        $tenantId = $user->tenant_id;

        $templates = MessageTemplate::query()
            ->where('agency_id', $agencyId)
            ->when($tenantId, function ($q) use ($tenantId) {
                // If tenant_id is used, show tenant-specific + agency defaults (tenant_id null)
                $q->where(function ($qq) use ($tenantId) {
                    $qq->whereNull('tenant_id')
                       ->orWhere('tenant_id', $tenantId);
                });
            }, function ($q) {
                // Tier 1: tenant_id unused → keep it null-only for clarity
                $q->whereNull('tenant_id');
            })
            ->orderBy('channel')
            ->orderBy('name')
            ->paginate(25);

        return view('settings.messaging.templates.index', [
            'templates' => $templates,
        ]);
    }
}
