<?php

namespace App\Http\Controllers;

use App\Models\CrmEvent;
use App\Models\Contact;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ================================
        // 1. DATE RANGE FOR INSIGHTS (7 DAYS)
        // ================================
        $startDate = Carbon::now();
        $endDate   = Carbon::now()->addDays(7);

        // ================================
        // 2. CRM EVENTS (Your existing logic)
        // ================================
        $events = CrmEvent::where('start', '>=', $startDate)
            ->where('start', '<=', $endDate)
            ->orderBy('start', 'asc')
            ->get();

        // ================================
        // 3. UPCOMING BIRTHDAYS (7 days out)
        // ================================
        $upcomingBirthdays = Contact::whereNotNull('date_of_birth')
            ->get()
            ->filter(function ($contact) use ($startDate, $endDate) {

                // Convert DOB to "this year's" birthday
                $thisYearBirthday = $contact->date_of_birth
                    ->copy()
                    ->year(now()->year);

                // If birthday this year already passed, use next year
                if ($thisYearBirthday->isPast()) {
                    $thisYearBirthday->addYear();
                }

                return $thisYearBirthday->between($startDate, $endDate);
            });

        // ================================
        // 4. UPCOMING ANNIVERSARIES (7 days out)
        // ================================
        $upcomingAnniversaries = Contact::whereNotNull('anniversary')
            ->get()
            ->filter(function ($contact) use ($startDate, $endDate) {

                $thisYearAnniversary = $contact->anniversary
                    ->copy()
                    ->year(now()->year);

                if ($thisYearAnniversary->isPast()) {
                    $thisYearAnniversary->addYear();
                }

                return $thisYearAnniversary->between($startDate, $endDate);
            });

        // ================================
        // 5. SEND EVERYTHING TO DASHBOARD
        // ================================
        return view('dashboard', [
            'events'               => $events,
            'upcomingBirthdays'    => $upcomingBirthdays,
            'upcomingAnniversaries'=> $upcomingAnniversaries,
        ]);
    }
}
