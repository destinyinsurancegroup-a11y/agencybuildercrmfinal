<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Start Date = now
        $startDate = Carbon::now();

        // End Date = 7 days from now
        $endDate = Carbon::now()->addDays(7);

        // Fetch events in the next 7 days
        $events = Event::where('start', '>=', $startDate)
            ->where('start', '<=', $endDate)
            ->orderBy('start', 'asc')
            ->get();

        return view('dashboard', [
            'events' => $events,
        ]);
    }
}
