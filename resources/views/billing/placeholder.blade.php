@extends('layouts.app')

@section('content')
<div class="ab-billing">

    {{-- Header Row --}}
    <div class="ab-billing__top">
        <div class="ab-billing__title-wrap">
            <h1 class="ab-billing__title">Billing</h1>
        </div>

        <div class="ab-billing__search">
            <span class="ab-billing__search-icon">🔍</span>
            <input type="text" class="ab-billing__search-input" placeholder="Search contacts, leads, or clients..." disabled>
        </div>

        <div class="ab-billing__icons">
            <button class="ab-billing__icon-btn" type="button" disabled title="Notifications">🔔</button>
            <div class="ab-billing__user-pill">
                <span class="ab-billing__user-badge">A</span>
                <span class="ab-billing__user-text">Agent</span>
            </div>
        </div>
    </div>

    {{-- Plan Bar --}}
    <div class="ab-billing__planbar">
        <div class="ab-billing__planbar-left">
            <span class="ab-billing__planbar-label">Your plan:</span>
            <span class="ab-billing__planbar-value">Pro Plan ($99 / month)</span>
        </div>
        <div class="ab-billing__planbar-right">
            <span class="ab-billing__planbar-label">Next Billing Date:</span>
            <span class="ab-billing__planbar-value">March 15, 2024</span>
            <span class="ab-billing__chev">›</span>
        </div>
    </div>

    {{-- Subscription Details --}}
    <h2 class="ab-billing__section-title">Subscription Details</h2>
    <div class="ab-card ab-card--big">
        <div class="ab-kv">
            <div class="ab-kv__row">
                <div class="ab-kv__k">Current Plan:</div>
                <div class="ab-kv__v">Pro Plan</div>
            </div>
            <div class="ab-kv__row">
                <div class="ab-kv__k">Price:</div>
                <div class="ab-kv__v">$99 / month</div>
            </div>
            <div class="ab-kv__row">
                <div class="ab-kv__k">Status:</div>
                <div class="ab-kv__v"><span class="ab-status">Active</span></div>
            </div>
            <div class="ab-kv__row">
                <div class="ab-kv__k">Billing Cycle:</div>
                <div class="ab-kv__v">Monthly</div>
            </div>
            <div class="ab-kv__row">
                <div class="ab-kv__k">Next billing:</div>
                <div class="ab-kv__v">March 15, 2024</div>
            </div>
        </div>

        <div class="ab-actions">
            <button type="button" class="ab-btn ab-btn--gold" disabled>Upgrade Plan</button>
            <button type="button" class="ab-btn ab-btn--ghost" disabled>Cancel Subscription</button>
        </div>

        <div class="ab-placeholder-note">
            <strong>Placeholder:</strong> Billing is not enabled yet. This layout is shown so the Billing tab matches the final design.
        </div>
    </div>

    {{-- Update Payment Method --}}
    <h2 class="ab-billing__section-title">Update Payment Method</h2>
    <div class="ab-card ab-card--row">
        <div class="ab-payment">
            <span class="ab-payment__brand">VISA</span>
            <span class="ab-payment__dots">•••• •••• ••••</span>
            <span class="ab-payment__last4">4242</span>
            <span class="ab-payment__exp">Exp: 10/25</span>
        </div>
        <button type="button" class="ab-btn ab-btn--ghost" disabled>Update Payment Method</button>
    </div>

    {{-- Invoices --}}
    <h2 class="ab-billing__section-title">Invoices &amp; Billing History</h2>
    <div class="ab-card">
        <div class="ab-table-wrap">
            <table class="ab-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice Number</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Feb 15, 2024</td>
                        <td>INV-1004</td>
                        <td>$99.00</td>
                        <td><span class="ab-pill ab-pill--paid">Paid</span></td>
                        <td><button class="ab-btn ab-btn--tiny" disabled>Download PDF</button></td>
                    </tr>
                    <tr>
                        <td>Jan 15, 2024</td>
                        <td>INV-0993</td>
                        <td>$99.00</td>
                        <td><span class="ab-pill ab-pill--paid">Paid</span></td>
                        <td><button class="ab-btn ab-btn--tiny" disabled>Download PDF</button></td>
                    </tr>
                    <tr>
                        <td>Dec 15, 2023</td>
                        <td>INV-0877</td>
                        <td>$99.00</td>
                        <td><span class="ab-pill ab-pill--paid">Paid</span></td>
                        <td><button class="ab-btn ab-btn--tiny" disabled>Download PDF</button></td>
                    </tr>
                    <tr>
                        <td>Nov 15, 2023</td>
                        <td>INV-0765</td>
                        <td>$99.00</td>
                        <td><span class="ab-pill ab-pill--paid">Paid</span></td>
                        <td><button class="ab-btn ab-btn--tiny" disabled>Download PDF</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="ab-table-footer">
            <a class="ab-link disabled" href="javascript:void(0)" aria-disabled="true">View All Billing History ›</a>
        </div>
    </div>

    {{-- Support + Manage --}}
    <div class="ab-grid-2">
        <div class="ab-card">
            <div class="ab-card__head">Billing Support</div>
            <div class="ab-card__body">
                Customer support is here to help with any billing questions or concerns.
            </div>
            <button type="button" class="ab-btn ab-btn--gold" disabled>Get Support</button>
        </div>

        <div class="ab-card">
            <div class="ab-card__head">Manage Account</div>
            <div class="ab-card__body">
                <div class="ab-manage">
                    <div class="ab-manage__item">› Update Billing Information</div>
                    <div class="ab-manage__item">› View Billing Portal</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer Mark --}}
    <div class="ab-footer-mark">
        <div class="ab-footer-logo">AGENCY BUILDER</div>
    </div>

</div>

<style>
/* ====== Canvas (match dashboard cream center) ====== */
.ab-billing{
    max-width: 1100px;
    margin: 0 auto;
    padding: 10px 8px 40px;
    background: #f4efe6;
    border-radius: 18px;
    box-shadow: 0 12px 26px rgba(0,0,0,0.12);
}

/* ====== Top row ====== */
.ab-billing__top{
    display:flex;
    align-items:center;
    gap:14px;
    padding: 14px 16px 6px;
}

.ab-billing__title{
    margin:0;
    font-size: 34px;
    font-weight: 800;
    color:#111827;
    letter-spacing: -0.02em;
}

.ab-billing__search{
    flex: 1;
    display:flex;
    align-items:center;
    background: rgba(255,255,255,0.75);
    border: 1px solid rgba(0,0,0,0.10);
    border-radius: 999px;
    padding: 10px 12px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
}

.ab-billing__search-icon{
    opacity: .55;
    margin-right: 8px;
}

.ab-billing__search-input{
    border: 0;
    outline: none;
    width: 100%;
    background: transparent;
    color: #6b7280;
    font-size: 14px;
}

.ab-billing__icons{
    display:flex;
    align-items:center;
    gap:10px;
}

.ab-billing__icon-btn{
    width: 40px;
    height: 40px;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.12);
    background: rgba(255,255,255,0.70);
    cursor: not-allowed;
    opacity: .9;
}

.ab-billing__user-pill{
    display:flex;
    align-items:center;
    gap:10px;
    padding: 8px 12px;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.12);
    background: rgba(255,255,255,0.70);
}

.ab-billing__user-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width: 26px;
    height: 26px;
    border-radius: 10px;
    background: #111827;
    color: #f4efe6;
    font-weight: 800;
    font-size: 13px;
}

.ab-billing__user-text{
    font-weight: 700;
    color: #111827;
}

/* ====== Plan bar ====== */
.ab-billing__planbar{
    margin: 10px 16px 14px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap: 14px;
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(255,255,255,0.60);
    border: 1px solid rgba(0,0,0,0.10);
}

.ab-billing__planbar-label{
    color:#6b7280;
    font-size: 13px;
    margin-right: 6px;
}

.ab-billing__planbar-value{
    color:#111827;
    font-weight: 800;
    font-size: 13px;
}

.ab-billing__chev{
    margin-left: 6px;
    opacity: .5;
}

/* ====== Section titles ====== */
.ab-billing__section-title{
    margin: 14px 16px 10px;
    font-size: 18px;
    font-weight: 900;
    color: #111827;
}

/* ====== Cards ====== */
.ab-card{
    margin: 0 16px 14px;
    background: rgba(255,255,255,0.65);
    border: 1px solid rgba(0,0,0,0.10);
    border-radius: 14px;
    padding: 14px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.08);
}

.ab-card--big{
    padding: 16px;
}

.ab-card--row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap: 12px;
}

.ab-card__head{
    font-weight: 900;
    color:#111827;
    margin-bottom: 8px;
}

.ab-card__body{
    color:#374151;
    font-size: 14px;
    margin-bottom: 10px;
}

/* ====== Key/Value list ====== */
.ab-kv{
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    background: rgba(244,239,230,0.55);
    padding: 10px 12px;
}

.ab-kv__row{
    display:flex;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0,0,0,0.06);
}

.ab-kv__row:last-child{ border-bottom: 0; }

.ab-kv__k{
    width: 140px;
    color:#6b7280;
    font-weight: 700;
}

.ab-kv__v{
    color:#111827;
    font-weight: 800;
}

/* ====== Buttons ====== */
.ab-actions{
    display:flex;
    gap: 12px;
    margin-top: 12px;
}

.ab-btn{
    height: 40px;
    padding: 0 16px;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.16);
    font-weight: 900;
    cursor: not-allowed;
    opacity: .92;
}

.ab-btn--gold{
    background: #D4AF37;
    color: #111827;
}

.ab-btn--ghost{
    background: rgba(255,255,255,0.65);
    color: #111827;
}

.ab-btn--tiny{
    height: 30px;
    padding: 0 12px;
    border-radius: 10px;
    font-size: 12px;
    background: rgba(255,255,255,0.65);
}

/* ====== Status ====== */
.ab-status{
    font-weight: 900;
    color:#111827;
}

/* ====== Payment row ====== */
.ab-payment{
    display:flex;
    align-items:center;
    gap: 10px;
    color:#111827;
    font-weight: 800;
}

.ab-payment__brand{
    background: #111827;
    color:#f4efe6;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 12px;
    letter-spacing: .06em;
}

.ab-payment__exp{
    color:#6b7280;
    font-weight: 800;
}

/* ====== Table ====== */
.ab-table-wrap{
    overflow-x: auto;
}

.ab-table{
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.ab-table th{
    text-align:left;
    color:#6b7280;
    font-weight: 900;
    padding: 10px 10px;
    border-bottom: 1px solid rgba(0,0,0,0.10);
}

.ab-table td{
    padding: 10px 10px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    color:#111827;
    font-weight: 700;
}

.ab-table tr:last-child td{
    border-bottom: 0;
}

.ab-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 900;
    border: 1px solid rgba(0,0,0,0.10);
}

.ab-pill--paid{
    background: rgba(34,197,94,0.20);
    color: #166534;
    border-color: rgba(22,101,52,0.30);
}

.ab-table-footer{
    display:flex;
    justify-content:flex-end;
    padding-top: 10px;
}

.ab-link{
    font-weight: 900;
    color:#1d4ed8;
    text-decoration: none;
}

.ab-link.disabled{
    opacity: .55;
    pointer-events: none;
}

/* ====== Grid bottom ====== */
.ab-grid-2{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin: 0 16px 14px;
}

.ab-grid-2 .ab-card{
    margin: 0;
}

.ab-manage__item{
    padding: 8px 0;
    color:#111827;
    font-weight: 800;
}

.ab-placeholder-note{
    margin-top: 12px;
    background: rgba(244,239,230,0.65);
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    padding: 10px 12px;
    color:#374151;
    font-size: 13px;
}

/* ====== Footer ====== */
.ab-footer-mark{
    display:flex;
    justify-content:center;
    padding: 18px 0 10px;
}

.ab-footer-logo{
    font-weight: 1000;
    letter-spacing: .12em;
    color:#111827;
    opacity: .65;
}

/* ====== Responsive ====== */
@media (max-width: 980px){
    .ab-grid-2{ grid-template-columns: 1fr; }
    .ab-billing__top{ flex-wrap: wrap; }
}
</style>
@endsection
