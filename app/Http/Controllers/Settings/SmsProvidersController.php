<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SmsProvidersController extends Controller
{
    /**
     * Universal list of providers (connected + available).
     * Tier 1: UI-first. Backend connections will be wired later.
     */
    public function index(Request $request)
    {
        // For now: just display provider options.
        // Later: load connected providers from provider_connections table.
        $availableProviders = [
            [
                'key' => 'twilio',
                'name' => 'Twilio',
                'status' => 'not_connected', // later: active/disabled/needs_attention
                'supports_now' => true,
            ],
            [
                'key' => 'telnyx',
                'name' => 'Telnyx',
                'status' => 'coming_soon',
                'supports_now' => false,
            ],
            [
                'key' => 'plivo',
                'name' => 'Plivo',
                'status' => 'coming_soon',
                'supports_now' => false,
            ],
            [
                'key' => 'vonage',
                'name' => 'Vonage',
                'status' => 'coming_soon',
                'supports_now' => false,
            ],
        ];

        return view('settings.messaging.sms_providers.index', [
            'activeTab' => 'messaging',      // if you still keep top tabs somewhere
            'settingsPage' => 'sms_providers', // highlights SMS Providers in left menu
            'providers' => $availableProviders,
        ]);
    }

    /**
     * Provider configuration screen (provider-specific UI).
     * Twilio supported first; others show a "coming soon" panel.
     */
    public function configure(Request $request, string $provider)
    {
        $provider = strtolower($provider);

        if ($provider === 'twilio') {
            return view('settings.messaging.sms_providers.configure_twilio', [
                'activeTab' => 'messaging',
                'settingsPage' => 'sms_providers',
                'providerKey' => 'twilio',
                'providerName' => 'Twilio',
            ]);
        }

        // Provider not implemented yet — still shows universal design.
        return view('settings.messaging.sms_providers.configure_coming_soon', [
            'activeTab' => 'messaging',
            'settingsPage' => 'sms_providers',
            'providerKey' => $provider,
            'providerName' => ucfirst($provider),
        ]);
    }
}
