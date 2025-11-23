<?php

namespace App\Http\Controllers;

use App\Models\Event;        // <-- FIXED: Real event model
use App\Models\Contact;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ==========================================
        // 1. DATE RANGE FOR INSIGHTS (Next 7 Days)
        // ==========================================
        $startDate = Carbon::now();
        $endDate   = Carbon::now()->addDays(7);

        // ==========================================
        // 2. UPCOMING APPOINTMENTS FROM CALENDAR
        // ==========================================
        $upcomingAppointments = Event::whereBetween('start', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay()
            ])
            ->orderBy('start', 'asc')
            ->get();

        // ==========================================
        // 3. UPCOMING BIRTHDAYS (Next 7 Days)
        // ==========================================
        $birthdays = Contact::whereNotNull('date_of_birth')
            ->get()
            ->filter(function ($contact) use ($startDate, $endDate) {

                $next = $contact->date_of_birth->copy()->year(now()->year);

                if ($next->isPast()) {
                    $next->addYear();
                }

                return $next->between($startDate, $endDate);
            });

        // ==========================================
        // 4. UPCOMING ANNIVERSARIES (Next 7 Days)
        // ==========================================
        $anniversaries = Contact::whereNotNull('anniversary')
            ->get()
            ->filter(function ($contact) use ($startDate, $endDate) {

                $next = $contact->anniversary->copy()->year(now()->year);

                if ($next->isPast()) {
                    $next->addYear();
                }

                return $next->between($startDate, $endDate);
            });

        // ==========================================
        // 5. SEND ALL DATA TO VIEW
        // ==========================================
        return view('dashboard', [
            'events'               => $upcomingAppointments,  // <-- FIXED: correct var name
            'birthdays'            => $birthdays,
            'anniversaries'        => $anniversaries,
        ]);
    }
}
