<?php

namespace App\Http\Controllers;

use App\Models\Event;        // Calendar events
use App\Models\Contact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // =========================================================
        // 0. REQUIRE AUTHENTICATED USER
        // =========================================================
        $user = Auth::user();

        // If somehow not logged in, always force login first
        if (!$user) {
            return redirect()->route('login');
        }

        // =========================================================
        // 1. DATE RANGE FOR INSIGHTS (Next 7 Days)
        // =========================================================
        $startDate = Carbon::now();
        $endDate   = Carbon::now()->addDays(7);

        // =========================================================
        // 2. UPCOMING APPOINTMENTS FROM CALENDAR (PER AGENCY)
        // =========================================================
        $upcomingAppointments = Event::where('agency_id', $user->agency_id)
            ->whereBetween('start', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->orderBy('start', 'asc')
            ->get();

        // =========================================================
        // 3. UPCOMING BIRTHDAYS (Next 7 Days, PER AGENCY)
        // =========================================================
        $birthdays = Contact::where('agency_id', $user->agency_id)
            ->whereNotNull('date_of_birth')
            ->get()
            ->filter(function ($contact) use ($startDate, $endDate) {
                // Take this year's birthday for the contact
                $next = $contact->date_of_birth->copy()->year(now()->year);

                // If this year's date has passed, move to next year
                if ($next->isPast()) {
                    $next->addYear();
                }

                // Only keep birthdays in the next 7 days window
                return $next->between($startDate, $endDate);
            });

        // =========================================================
        // 4. UPCOMING ANNIVERSARIES (Next 7 Days, PER AGENCY)
        // =========================================================
        $anniversaries = Contact::where('agency_id', $user->agency_id)
            ->whereNotNull('anniversary')
            ->get()
            ->filter(function ($contact) use ($startDate, $endDate) {
                $next = $contact->anniversary->copy()->year(now()->year);

                if ($next->isPast()) {
                    $next->addYear();
                }

                return $next->between($startDate, $endDate);
            });

        // =========================================================
        // 5. SEND ALL DATA TO VIEW
        // =========================================================
        return view('dashboard', [
            'events'        => $upcomingAppointments,
            'birthdays'     => $birthdays,
            'anniversaries' => $anniversaries,
        ]);
    }
}
