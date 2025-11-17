<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Get today's date (no time)
        $today = Carbon::today();

        // Fetch events for today → next 7 days
        $events = Event::query()
            // Optional multi-tenant protection (enable if needed):
            // ->where('tenant_id', Auth::user()->tenant_id)
            ->whereDate('start', '>=', $today)
            ->whereDate('start', '<=', $today->clone()->addDays(7))
            ->orderBy('start', 'asc')
            ->get();

        // Pass data to the dashboard blade
        return view('dashboard', [
            'events' => $events,
        ]);
    }
}
