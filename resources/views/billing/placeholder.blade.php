@extends('layouts.app')

@section('content')
<div class="ab-page">
    <div class="ab-header">
        <h1 class="ab-title">Billing Management</h1>
        <p class="ab-subtitle">
            Billing is not enabled yet. This section is a placeholder until Stripe is configured.
        </p>
    </div>

    <div class="ab-card">
        <div class="ab-card-row">
            <div>
                <div class="ab-label">Status</div>
                <div class="ab-value"><span class="ab-badge">Under Development</span></div>
            </div>
            <div class="ab-actions">
                <a href="{{ route('dashboard') }}" class="ab-btn ab-btn-gold">Back to Dashboard</a>
            </div>
        </div>

        <div class="ab-divider"></div>

        <div class="ab-muted">
            When billing is turned on, this page will show:
            <ul class="ab-list">
                <li>Current plan and next billing date</li>
                <li>Payment method update</li>
                <li>Invoice history</li>
                <li>Link to Stripe customer portal</li>
            </ul>
        </div>
    </div>
</div>

<style>
/* ===== Agency Builder placeholder styling (center cream / subtle shadows) ===== */
.ab-page{
    max-width: 980px;
    margin: 0 auto;
    padding: 28px 20px 60px;
}
.ab-header{
    margin-bottom: 18px;
}
.ab-title{
    font-size: 34px;
    line-height: 1.1;
    margin: 0;
    font-weight: 800;
    color: #1f2937;
}
.ab-subtitle{
    margin: 10px 0 0;
    color: #4b5563;
    font-size: 15px;
}
.ab-card{
    background: rgba(255,255,255,0.65);
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    padding: 18px 18px;
}
.ab-card-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.ab-label{
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #6b7280;
}
.ab-value{
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin-top: 4px;
}
.ab-actions{
    display:flex;
    gap: 10px;
    align-items:center;
}
.ab-divider{
    height:1px;
    background: rgba(0,0,0,0.10);
    margin: 16px 0;
}
.ab-muted{
    color:#374151;
    font-size: 14px;
}
.ab-list{
    margin: 10px 0 0 18px;
}
.ab-badge{
    display:inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    background: rgba(234,179,8,0.18);
    border: 1px solid rgba(234,179,8,0.35);
    color: #7c5b00;
}
.ab-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    height: 40px;
    padding: 0 14px;
    border-radius: 10px;
    font-weight: 800;
    text-decoration:none;
    border: 1px solid rgba(0,0,0,0.18);
}
.ab-btn-gold{
    background: linear-gradient(180deg, #e7c45a, #cfa73a);
    color:#111827;
}
.ab-btn-gold:hover{
    filter: brightness(1.02);
}
</style>
@endsection
