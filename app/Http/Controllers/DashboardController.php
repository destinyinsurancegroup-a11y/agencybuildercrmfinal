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
        // 1. DATE RANGE (Next 7 days)
        // ================================
        $startDate = Carbon::now();
        $endDate   = Carbon::now()->addDays(7);

        // ================================
        // 2. Upcoming CRM Events
        // ================================
        $events = CrmEvent::whereBetween('start', [$startDate, $endDate])
            ->orderBy('start', 'asc')
            ->get();

        // ================================
        // 3. UPCOMING BIRTHDAYS
        // ================================
        $upcomingBirthdays = Contact::whereNotNull('date_of_birth')->get()
            ->filter(function ($contact) use ($startDate, $endDate) {

                $birthday = $contact->date_of_birth->copy()->year(now()->year);

                if ($birthday->isPast()) {
                    $birthday->addYear();
                }

                return $birthday->between($startDate, $endDate);
            });

        // ================================
        // 4. UPCOMING ANNIVERSARIES
        // ================================
        $upcomingAnniversaries = Contact::whereNotNull('anniversary')->get()
            ->filter(function ($contact) use ($startDate, $endDate) {

                $anniv = $contact->anniversary->copy()->year(now()->year);

                if ($anniv->isPast()) {
                    $anniv->addYear();
                }

                return $anniv->between($startDate, $endDate);
            });

        // ================================
        // 5. RETURN VIEW WITH CORRECT VARIABLES
        // ================================
        return view('dashboard', [
            'events'                => $events,
            'upcomingBirthdays'     => $upcomingBirthdays,
            'upcomingAnniversaries' => $upcomingAnniversaries,
        ]);
    }
}
