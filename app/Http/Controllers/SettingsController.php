<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Tier 1 Settings landing: redirect to Profile tab.
     */
    public function redirect()
    {
        return redirect()->route('settings.profile');
    }

    /**
     * Settings -> Profile
     */
    public function profile(Request $request)
    {
        return view('settings.profile', [
            'activeTab' => 'profile',
            'user'      => $request->user(),
        ]);
    }

    /**
     * Settings -> Messaging (placeholder for Twilio BYOT + Templates + Drips)
     */
    public function messaging(Request $request)
    {
        return view('settings.messaging', [
            'activeTab' => 'messaging',
        ]);
    }

    /**
     * Settings -> Billing (separate from your existing /billing route)
     */
    public function billing(Request $request)
    {
        return view('settings.billing', [
            'activeTab' => 'billing',
        ]);
    }
}
