<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * Show the activity entry page.
     */
    public function index()
    {
        // The view folder is /resources/views/activity/
        return view('activity.index');
    }

    /**
     * Show the right-side activity summary panel (AJAX).
     */
    public function panel()
    {
        return view('activity.panel');
    }

    /**
     * Store a new activity entry.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'leads_worked'      => 'nullable|integer',
            'calls'             => 'nullable|integer',
            'stops'             => 'nullable|integer',
            'presentations'     => 'nullable|integer',
            'apps_written'      => 'nullable|integer',
            'premium_collected' => 'nullable|numeric',
            'ap'                => 'nullable|numeric',
            'created_at'        => 'nullable|date',
        ]);

        $data['user_id'] = Auth::id();

        // Force created_at so entries show on correct day
        if (!empty($data['created_at'])) {
            $data['created_at'] = Carbon::parse($data['created_at']);
        }

        Activity::create($data);

        return response()->json(['success' => true]);
    }

    /**
     * Get totals for day/week/month/quarter/year for dashboard.
     */
    public function totals($range)
    {
        $userId = Auth::id();
        $query  = Activity::where('user_id', $userId);

        $now = Carbon::now();

        switch ($range) {
            case 'day':
                $query->whereDate('created_at', $now->toDateString());
                break;

            case 'week':
                $query->whereBetween('created_at', [
                    $now->startOfWeek(), $now->endOfWeek()
                ]);
                break;

            case 'month':
                $query->whereYear('created_at', $now->year)
                      ->whereMonth('created_at', $now->month);
                break;

            case 'quarter':
                $query->whereBetween('created_at', [
                    $now->copy()->firstOfQuarter(),
                    $now->copy()->lastOfQuarter(),
                ]);
                break;

            case 'year':
                $query->whereYear('created_at', $now->year);
                break;

            default:
                return response()->json(['error' => 'Invalid range'], 400);
        }

        $totals = $query->selectRaw("
            COALESCE(SUM(leads_worked), 0) AS leads_worked,
            COALESCE(SUM(calls), 0) AS calls,
            COALESCE(SUM(stops), 0) AS stops,
            COALESCE(SUM(presentations), 0) AS presentations,
            COALESCE(SUM(apps_written), 0) AS apps_written,
            COALESCE(SUM(premium_collected), 0) AS premium_collected,
            COALESCE(SUM(ap), 0) AS ap
        ")->first();

        return response()->json($totals);
    }
}
