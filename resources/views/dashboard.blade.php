@extends('layouts.app')

@section('content')
<h1>Dashboard</h1>

<p>Welcome to the Agency Builder CRM Dashboard.</p>

<div style="margin-top: 20px;">

    <div style="
        background: #131316;
        border: 1px solid #222;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    ">
        <h2 style="color: #D4AF37; margin-top: 0;">Quick Metrics</h2>
        <p style="margin: 8px 0;">• Contacts: <strong>0</strong></p>
        <p style="margin: 8px 0;">• Leads: <strong>0</strong></p>
        <p style="margin: 8px 0;">• Policies: <strong>0</strong></p>
        <p style="margin: 8px 0;">• Pending Service Tickets: <strong>0</strong></p>
    </div>

    <div style="
        background: #131316;
        border: 1px solid #222;
        border-radius: 8px;
        padding: 20px;
    ">
        <h2 style="color: #D4AF37; margin-top: 0;">Recent Activity</h2>
        <p>No recent activity logged.</p>
    </div>

</div>

@endsection
