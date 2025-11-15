@extends('layouts.app')

@section('content')
<div style="padding: 20px;">
    <h1 style="font-size: 28px; font-weight: bold;">Dashboard</h1>
    <p>Welcome to Agency Builder CRM Tier 1.</p>

    <div style="margin-top: 30px; padding: 20px; background: #f7f7f7; border-radius: 8px;">
        <h3 style="margin-bottom: 10px;">Current Production Snapshot (placeholder)</h3>

        <ul>
            <li>Daily Calls: --</li>
            <li>Weekly Calls: --</li>
            <li>Monthly Calls: --</li>
            <li>Stops: --</li>
            <li>Presentations: --</li>
            <li>Apps Sold: --</li>
            <li>Premium Collected: --</li>
            <li>Annualized Premium (AP): --</li>
            <li>Leads Worked: --</li>
        </ul>

        <p style="color: gray; font-size: 14px; margin-top: 10px;">
            (Dynamic data will populate after we add the backend modules)
        </p>
    </div>
</div>
@endsection
