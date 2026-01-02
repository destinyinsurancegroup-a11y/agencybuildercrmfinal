@extends('layouts.app')

@section('content')
<div class="ab-wrap">
    <div class="ab-header">
        <h1 class="ab-title">Billing Management</h1>
        <div class="ab-subtitle">
            Billing is not enabled yet. This section is a placeholder until Stripe is configured.
        </div>
    </div>

    <div class="ab-card">
        <div class="ab-row">
            <div>
                <div class="ab-label">Status</div>
                <div class="ab-value">
                    <span class="ab-pill">Under Development</span>
                </div>
            </div>

            <div class="ab-actions">
                <a href="{{ route('dashboard') }}" class="ab-btn ab-btn-gold">Back to Dashboard</a>
            </div>
        </div>

        <div class="ab-divider"></div>

        <div class="ab-body">
            <div class="ab-section-title">What will appear here later</div>
            <ul class="ab-list">
                <li>Current plan and next billing date</li>
                <li>Payment method update</li>
                <li>Invoice history</li>
                <li>Stripe customer portal access</li>
            </ul>

            <div class="ab-note">
                <strong>Note:</strong> You can keep the Billing tab live with this placeholder while Tier 1 ships.
                When ready, we’ll swap this view for the full Stripe implementation.
            </div>
        </div>
    </div>
</div>

<style>
/*
  Billing placeholder styling to match ABC dashboard center:
  - Cream canvas
  - Soft card shadows
  - Minimal gold accents
  - Leaves sidebar styling to layouts.app
*/

.ab-wrap{
    max-width: 1100px;
    margin: 0 auto;
    padding: 10px 10px 60px;
}

.ab-header{
    margin: 8px 0 18px;
}

.ab-title{
    font-size: 40px;
    font-weight: 800;
    margin: 0;
    color: #111827;
    letter-spacing: -0.02em;
}

.ab-subtitle{
    margin-top: 6px;
    font-size: 16px;
    color: #4b5563;
}

.ab-card{
    background: #f4efe6; /* cream */
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 16px;
    box-shadow: 0 14px 30px rgba(0,0,0,0.10);
    padding: 20px;
}

.ab-row{
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

.ab-label{
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .10em;
    color: #6b7280;
}

.ab-value{
    margin-top: 6px;
}

.ab-pill{
    display: inline-flex;
    align-items: center;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(212,175,55,0.20);
    border: 1px solid rgba(212,175,55,0.45);
    color: #6b4e00;
    font-weight: 800;
    font-size: 13px;
}

.ab-actions{
    display: flex;
    gap: 10px;
}

.ab-btn{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    padding: 0 16px;
    border-radius: 12px;
    font-weight: 800;
    text-decoration: none;
    border: 1px solid rgba(0,0,0,0.18);
}

.ab-btn-gold{
    background: #D4AF37;
    color: #111827;
}

.ab-btn-gold:hover{
    filter: brightness(0.98);
}

.ab-divider{
    height: 1px;
    background: rgba(0,0,0,0.10);
    margin: 18px 0;
}

.ab-body{
    color: #1f2937;
    font-size: 15px;
}

.ab-section-title{
    font-size: 16px;
    font-weight: 800;
    margin-bottom: 10px;
    color: #111827;
}

.ab-list{
    margin: 0 0 14px 18px;
    color: #374151;
}

.ab-note{
    margin-top: 14px;
    padding: 14px;
    background: rgba(255,255,255,0.55);
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 12px;
    color: #374151;
}
</style>
@endsection
