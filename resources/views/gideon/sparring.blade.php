@extends('layouts.app')

@section('content')
<style>
    :root{
        --abc-bg: #0b0b0c;
        --abc-panel: rgba(18,18,20,.92);
        --abc-panel-2: rgba(14,14,16,.88);
        --abc-border: rgba(255, 205, 90, .18);
        --abc-border-2: rgba(255, 205, 90, .10);
        --abc-gold: #d8b25a;
        --abc-gold-2: #b88b3d;
        --abc-text: rgba(255,255,255,.92);
        --abc-muted: rgba(255,255,255,.62);
        --abc-shadow: 0 18px 50px rgba(0,0,0,.55);
        --abc-radius: 18px;
        --abc-radius-sm: 14px;
    }

    .abc-wrap {
        padding: 24px;
        background: radial-gradient(1200px 600px at 30% 0%, rgba(216,178,90,.12), transparent 60%),
                    radial-gradient(900px 500px at 80% 30%, rgba(216,178,90,.08), transparent 55%),
                    var(--abc-bg);
        min-height: calc(100vh - 80px);
    }

    .abc-shell{
        max-width: 1320px;
        margin: 0 auto;
        border-radius: 26px;
        background: linear-gradient(180deg, rgba(20,20,22,.95), rgba(10,10,11,.92));
        border: 1px solid rgba(255,255,255,.06);
        box-shadow: var(--abc-shadow);
        padding: 22px;
    }

    .abc-topbar{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap: 16px;
        padding: 6px 6px 18px 6px;
    }

    .abc-title{
        font-size: 38px;
        font-weight: 700;
        color: var(--abc-text);
        letter-spacing: .2px;
        line-height: 1.1;
        margin: 0;
    }
    .abc-subtitle{
        color: var(--abc-muted);
        margin-top: 6px;
        font-size: 14px;
    }

    .abc-pill{
        display:inline-flex;
        align-items:center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,.08);
        background: rgba(0,0,0,.35);
        color: var(--abc-text);
        font-size: 13px;
        white-space: nowrap;
    }
    .abc-btn-danger{
        border: 1px solid rgba(255, 80, 80, .35);
        background: rgba(255, 80, 80, .10);
        color: rgba(255,255,255,.9);
        padding: 10px 14px;
        border-radius: 999px;
        font-size: 13px;
        cursor:pointer;
    }
    .abc-btn-danger:hover{
        background: rgba(255, 80, 80, .16);
    }

    /* MAIN GRID (LEFT avatar/phone, RIGHT conversation) */
    .abc-grid{
        display:grid;
        grid-template-columns: 1.25fr 1fr;
        gap: 18px;
    }

    .abc-card{
        border-radius: var(--abc-radius);
        border: 1px solid rgba(255,255,255,.07);
        background: linear-gradient(180deg, rgba(15,15,16,.92), rgba(10,10,11,.88));
        box-shadow: 0 12px 28px rgba(0,0,0,.35);
        overflow:hidden;
    }

    .abc-card-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,255,255,.06);
    }

    .abc-card-title{
        color: rgba(255,255,255,.86);
        font-size: 12px;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .abc-btn-outline{
        border: 1px solid rgba(255,205,90,.22);
        background: rgba(0,0,0,.18);
        color: rgba(255,255,255,.86);
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 12px;
        cursor:pointer;
    }
    .abc-btn-outline:hover{
        border-color: rgba(255,205,90,.35);
        background: rgba(0,0,0,.26);
    }

    /* LEFT PANEL (avatar/phone) */
    .abc-controls{
        display:grid;
        grid-template-columns: 1.2fr 1fr 1fr;
        gap: 12px;
        padding: 14px 16px 8px 16px;
    }

    .abc-control{
        background: rgba(0,0,0,.25);
        border: 1px solid rgba(255,255,255,.06);
        border-radius: var(--abc-radius-sm);
        padding: 12px 12px;
    }
    .abc-label{
        color: rgba(255,255,255,.72);
        font-size: 11px;
        letter-spacing: .10em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .abc-seg-row{
        display:flex;
        gap: 10px;
        align-items:center;
        flex-wrap: wrap;
        padding: 10px 16px 12px 16px;
    }

    .abc-toggle{
        display:inline-flex;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,.07);
        overflow:hidden;
        background: rgba(0,0,0,.25);
    }
    .abc-toggle button{
        padding: 10px 14px;
        font-size: 13px;
        border: 0;
        cursor:pointer;
        color: rgba(255,255,255,.85);
        background: transparent;
        min-width: 120px;
    }
    .abc-toggle button.active{
        background: linear-gradient(180deg, rgba(216,178,90,.25), rgba(184,139,61,.18));
        border-right: 1px solid rgba(255,255,255,.05);
        color: rgba(255,255,255,.95);
    }

    .abc-avatar-area{
        padding: 12px 16px 8px 16px;
    }
    .abc-avatar-stage{
        border-radius: var(--abc-radius);
        border: 1px solid rgba(255,255,255,.06);
        background: radial-gradient(520px 220px at 50% 0%, rgba(216,178,90,.10), transparent 60%),
                    rgba(0,0,0,.22);
        min-height: 360px;
        display:flex;
        align-items:center;
        justify-content:center;
        position: relative;
    }

    .abc-prospect-pill{
        position:absolute;
        top: 14px;
        left: 14px;
        display:inline-flex;
        gap: 8px;
        align-items:center;
        padding: 8px 10px;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,.08);
        background: rgba(0,0,0,.30);
        color: rgba(255,255,255,.90);
        font-size: 12px;
    }
    .abc-prospect-pill .dot{
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: rgba(216,178,90,.9);
        box-shadow: 0 0 0 3px rgba(216,178,90,.15);
    }

    .abc-avatar-circle{
        width: 250px;
        height: 250px;
        border-radius: 999px;
        border: 2px solid rgba(216,178,90,.55);
        background: rgba(0,0,0,.35);
        display:flex;
        align-items:center;
        justify-content:center;
        overflow:hidden;
        box-shadow: 0 18px 40px rgba(0,0,0,.55);
    }
    .abc-avatar-circle img{
        width: 100%;
        height: 100%;
        object-fit: cover;
        display:block;
    }

    .abc-phone-panel{
        width: 250px;
        height: 250px;
        border-radius: 24px;
        border: 2px solid rgba(216,178,90,.40);
        background: linear-gradient(180deg, rgba(0,0,0,.40), rgba(0,0,0,.18));
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap: 10px;
        box-shadow: 0 18px 40px rgba(0,0,0,.55);
        color: rgba(255,255,255,.9);
        font-size: 14px;
    }
    .abc-phone-icon{
        width: 56px;
        height: 56px;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,.10);
        background: rgba(216,178,90,.20);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 28px;
    }

    .abc-center-actions{
        padding: 10px 16px 16px 16px;
        display:flex;
        justify-content:center;
    }
    .abc-btn-gold{
        border: 0;
        background: linear-gradient(180deg, rgba(216,178,90,.95), rgba(184,139,61,.95));
        color: rgba(0,0,0,.85);
        font-weight: 700;
        padding: 12px 18px;
        border-radius: 999px;
        cursor:pointer;
        min-width: 240px;
        display:inline-flex;
        justify-content:center;
        align-items:center;
        gap: 10px;
        box-shadow: 0 10px 20px rgba(0,0,0,.35);
    }
    .abc-btn-gold:hover{
        filter: brightness(1.02);
    }

    /* RIGHT PANEL (conversation) */
    .abc-convo-body{
        padding: 14px 16px;
        display:flex;
        flex-direction:column;
        gap: 12px;
        min-height: 520px;
    }

    .abc-transcript{
        flex: 1 1 auto;
        border-radius: var(--abc-radius);
        border: 1px solid rgba(255,255,255,.06);
        background: rgba(0,0,0,.25);
        padding: 14px;
        overflow-y:auto;
        min-height: 360px;
    }

    .abc-bubble{
        max-width: 85%;
        padding: 10px 12px;
        border-radius: 14px;
        border: 1px solid rgba(255,255,255,.06);
        margin-bottom: 10px;
        font-size: 14px;
        line-height: 1.35;
        position: relative;
        word-wrap: break-word;
    }
    .abc-bubble .ts{
        display:block;
        font-size: 11px;
        color: rgba(255,255,255,.55);
        margin-top: 6px;
    }
    .abc-bubble.agent{
        margin-left:auto;
        background: rgba(216,178,90,.14);
        border-color: rgba(216,178,90,.20);
    }
    .abc-bubble.system{
        margin-right:auto;
        background: rgba(255,255,255,.06);
    }

    .abc-input-row{
        display:flex;
        gap: 10px;
        align-items:center;
    }
    .abc-input{
        flex: 1 1 auto;
        border-radius: 14px;
        border: 1px solid rgba(255,255,255,.08);
        background: rgba(0,0,0,.28);
        color: rgba(255,255,255,.92);
        padding: 12px 12px;
        outline:none;
    }
    .abc-input::placeholder{ color: rgba(255,255,255,.40); }
    .abc-send{
        border: 0;
        border-radius: 12px;
        padding: 12px 16px;
        background: linear-gradient(180deg, rgba(216,178,90,.95), rgba(184,139,61,.95));
        color: rgba(0,0,0,.85);
        font-weight: 700;
        cursor:pointer;
        min-width: 86px;
    }

    .abc-muted{
        color: rgba(255,255,255,.55);
        font-size: 12px;
    }

    @media (max-width: 1100px){
        .abc-grid{ grid-template-columns: 1fr; }
        .abc-convo-body{ min-height: unset; }
    }
</style>

@php
    // You said: remove scenario + persona dropdowns from the TOP.
    // This page assumes scenario_code exists in the backend, but we can use the first seeded scenario silently.
    $firstScenario = ($scenarios ?? collect())->first();
    $scenarioCode = $firstScenario?->code ?? null;
@endphp

<div class="abc-wrap">
    <div class="abc-shell">
        <div class="abc-topbar">
            <div>
                <h1 class="abc-title">ABC Sparring Partner</h1>
                <div class="abc-subtitle">
                    Live conversation training with a human-like prospect. Practice in segments or run a full presentation.
                </div>
            </div>

            <div style="display:flex; gap:10px; align-items:center;">
                <div class="abc-pill">
                    ⏱ <span id="timerText">00:00</span>
                </div>
                <button id="endSessionBtn" class="abc-btn-danger" type="button">End Session</button>
            </div>
        </div>

        <div class="abc-grid">

            {{-- LEFT: Avatar / Phone (environment) --}}
            <div class="abc-card">
                <div class="abc-card-head">
                    <div class="abc-card-title">Prospect</div>
                    <button id="resetSessionBtn" class="abc-btn-outline" type="button">Reset</button>
                </div>

                {{-- Controls (keep minimal; beginner/intermediate/advanced etc are UI only for now) --}}
                <div class="abc-controls">
                    <div class="abc-control">
                        <div class="abc-label">Difficulty</div>
                        <div class="abc-toggle" role="tablist" aria-label="Difficulty">
                            <button type="button" class="active" data-difficulty="beginner">Beginner</button>
                            <button type="button" data-difficulty="intermediate">Intermediate</button>
                            <button type="button" data-difficulty="advanced">Advanced</button>
                        </div>
                        <div class="abc-muted" style="margin-top:8px;">Affects how skeptical the prospect starts.</div>
                    </div>

                    <div class="abc-control">
                        <div class="abc-label">Training</div>
                        <div class="abc-toggle" role="tablist" aria-label="Training Mode">
                            <button type="button" class="active" data-training="full">Full Preso</button>
                            <button type="button" data-training="segments">Segments</button>
                            <button type="button" data-training="disco">Disco</button>
                        </div>
                        <div class="abc-muted" style="margin-top:8px;">Controls where the session starts/ends.</div>
                    </div>

                    <div class="abc-control">
                        <div class="abc-label">Environment</div>
                        <div class="abc-toggle" role="tablist" aria-label="Environment">
                            <button id="envPhoneBtn" type="button" class="active" data-env="phone">📞 Phone</button>
                            <button id="envInPersonBtn" type="button" data-env="in_person">In Person</button>
                        </div>
                        <div class="abc-muted" style="margin-top:8px;">Phone shows a phone panel. In-person shows the avatar.</div>
                    </div>
                </div>

                <div class="abc-seg-row">
                    <div style="min-width: 220px;">
                        <div class="abc-label">Segments</div>
                        <select id="segmentSelect" class="abc-input" style="padding:10px 12px;">
                            <option value="intro">Intro</option>
                            <option value="discovery" selected>Disco</option>
                            <option value="education">Educ</option>
                            <option value="qualify">Qual</option>
                            <option value="quote">Quote</option>
                            <option value="close">Close</option>
                        </select>
                        <div class="abc-muted" style="margin-top:8px;">Highlights what you’re training.</div>
                    </div>
                </div>

                <div class="abc-avatar-area">
                    <div class="abc-avatar-stage">
                        <div class="abc-prospect-pill">
                            <span class="dot"></span>
                            <span>PROSPECT</span>
                            <span style="opacity:.75;">•</span>
                            <span id="expressionText">Neutral</span>
                        </div>

                        {{-- In-Person Avatar --}}
                        <div id="avatarPanel" class="abc-avatar-circle" style="display:flex;">
                            {{-- Use your own stored image later. For now, a safe placeholder --}}
                            <img
                                src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=500&q=60"
                                alt="Prospect Avatar"
                            />
                        </div>

                        {{-- Phone Panel --}}
                        <div id="phonePanel" class="abc-phone-panel" style="display:none;">
                            <div class="abc-phone-icon">📞</div>
                            <div style="font-weight:700;">Phone Call</div>
                            <div class="abc-muted">Prospect responds by voice + text.</div>
                        </div>
                    </div>
                </div>

                <div class="abc-center-actions">
                    <button id="startSessionBtn" class="abc-btn-gold" type="button">
                        ▶ Start Sparring Session
                    </button>
                </div>

                <div class="abc-muted" style="padding: 0 16px 16px 16px;">
                    <span id="sparringStatus"></span>
                </div>
            </div>

            {{-- RIGHT: LIVE CONVERSATION (moved to the right of avatar/phone) --}}
            <div class="abc-card">
                <div class="abc-card-head">
                    <div class="abc-card-title">Live Conversation</div>
                    <div class="abc-muted">Type your next line and press Enter.</div>
                </div>

                <div class="abc-convo-body">
                    <div id="sparringTranscript" class="abc-transcript">
                        <div class="abc-muted">Click <strong>Start Sparring Session</strong>, then send your first message.</div>
                    </div>

                    <form id="sparringForm" class="abc-input-row">
                        <input id="sparringInput" class="abc-input" type="text" autocomplete="off"
                               placeholder="Type what you’d say and press Enter..." />
                        <button id="sendBtn" class="abc-send" type="submit">Send</button>
                    </form>

                    <div class="abc-muted" id="sparringHint">
                        {{ $scenarioCode ? '' : 'No scenarios found. Seed at least one scenario to start sparring.' }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

    const transcriptEl = document.getElementById('sparringTranscript');
    const inputEl      = document.getElementById('sparringInput');
    const formEl       = document.getElementById('sparringForm');
    const sendBtn      = document.getElementById('sendBtn');

    const resetBtn     = document.getElementById('resetSessionBtn');
    const endBtn       = document.getElementById('endSessionBtn');
    const startBtn     = document.getElementById('startSessionBtn');

    const statusEl     = document.getElementById('sparringStatus');
    const timerTextEl  = document.getElementById('timerText');

    const envPhoneBtn  = document.getElementById('envPhoneBtn');
    const envInBtn     = document.getElementById('envInPersonBtn');
    const avatarPanel  = document.getElementById('avatarPanel');
    const phonePanel   = document.getElementById('phonePanel');

    const expressionTextEl = document.getElementById('expressionText');
    const segmentSelectEl  = document.getElementById('segmentSelect');

    const scenarioCode = @json($scenarioCode);

    let currentSessionId = null;
    let isSending = false;

    // simple timer (UI only)
    let timerInterval = null;
    let startedAt = null;

    function setStatus(msg){
        if (!statusEl) return;
        statusEl.textContent = msg || '';
    }

    function fmtTime(seconds){
        const m = String(Math.floor(seconds / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        return `${m}:${s}`;
    }

    function startTimer(){
        startedAt = Date.now();
        if (timerInterval) clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            const secs = Math.floor((Date.now() - startedAt) / 1000);
            if (timerTextEl) timerTextEl.textContent = fmtTime(secs);
        }, 250);
    }

    function stopTimer(){
        if (timerInterval) clearInterval(timerInterval);
        timerInterval = null;
        if (timerTextEl) timerTextEl.textContent = '00:00';
        startedAt = null;
    }

    function resetSession(){
        currentSessionId = null;
        isSending = false;
        stopTimer();
        transcriptEl.innerHTML = '<div class="abc-muted">Session reset. Click <strong>Start Sparring Session</strong>, then send your first message.</div>';
        setStatus('');
        if (inputEl) inputEl.value = '';
        if (inputEl) inputEl.disabled = false;
        if (sendBtn) sendBtn.disabled = false;
        if (expressionTextEl) expressionTextEl.textContent = 'Neutral';
    }

    function appendBubble(role, text, createdAt){
        if (!text) return;

        const wrap = document.createElement('div');
        wrap.className = `abc-bubble ${role === 'agent' ? 'agent' : 'system'}`;

        const content = document.createElement('div');
        content.textContent = text;

        const ts = document.createElement('span');
        ts.className = 'ts';
        const d = createdAt ? new Date(createdAt) : new Date();
        ts.textContent = d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});

        wrap.appendChild(content);
        wrap.appendChild(ts);

        transcriptEl.appendChild(wrap);
        transcriptEl.scrollTop = transcriptEl.scrollHeight;
    }

    function setEnv(env){
        // env is UI-only; does not change backend text responses.
        if (env === 'phone') {
            envPhoneBtn.classList.add('active');
            envInBtn.classList.remove('active');
            phonePanel.style.display = 'flex';
            avatarPanel.style.display = 'none';
        } else {
            envInBtn.classList.add('active');
            envPhoneBtn.classList.remove('active');
            avatarPanel.style.display = 'flex';
            phonePanel.style.display = 'none';
        }
    }

    if (envPhoneBtn) envPhoneBtn.addEventListener('click', () => setEnv('phone'));
    if (envInBtn)    envInBtn.addEventListener('click', () => setEnv('in_person'));

    // default env = in person (matches your vision)
    setEnv('in_person');

    async function startSessionOnly(){
        if (!scenarioCode) {
            setStatus('No scenario available. Seed at least one scenario.');
            return;
        }
        if (!csrfToken) {
            setStatus('Missing CSRF token.');
            return;
        }

        // We create the session on first message in your controller.
        // But user asked for "Start Session" UX.
        // So we start the timer + prime the UI, and first message will create session.
        startTimer();
        setStatus('Session ready. Send your first line.');
        if (inputEl) inputEl.focus();
    }

    async function sendToGideon(message){
        if (!scenarioCode) {
            setStatus('No scenario available. Seed at least one scenario.');
            return;
        }
        if (!csrfToken) {
            setStatus('Missing CSRF token.');
            return;
        }

        isSending = true;
        setStatus('Talking to Gideon...');
        appendBubble('agent', message);

        // UI role mode: keep prospect_simulation for now (You = Agent)
        const uiMode = 'prospect_simulation';

        // Segment is UI-only right now; we pass it in meta via "persona" or "message" later if needed.
        // For now: keep persona = adaptive (service default) to avoid mismatch.
        const persona = 'adaptive';

        try {
            const resp = await fetch('/api/gideon/sparring/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    session_id: currentSessionId,
                    scenario_code: scenarioCode,
                    mode: uiMode,
                    persona: persona,
                    message: message,
                }),
            });

            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();

            if (data.session && data.session.id) currentSessionId = data.session.id;

            // opening line (if any)
            if (data.opening_line) {
                appendBubble('system', data.opening_line);
            }

            if (data.gideon_reply && data.gideon_reply.content) {
                appendBubble('system', data.gideon_reply.content, data.gideon_reply.created_at);

                // cheap expression mapping (UI-only)
                // You can replace this later with a real state machine.
                const txt = (data.gideon_reply.content || '').toLowerCase();
                let expr = 'Neutral';
                if (txt.includes('not sure') || txt.includes('skept') || txt.includes('guard')) expr = 'Skeptical';
                if (txt.includes('ok') || txt.includes('thanks')) expr = 'Neutral';
                if (txt.includes('concern') || txt.includes('worried')) expr = 'Concerned';
                if (expressionTextEl) expressionTextEl.textContent = expr;
            }

            setStatus('Session active.');
        } catch (e) {
            console.error(e);
            setStatus('Error talking to Gideon. Check runtime logs + browser console.');
        } finally {
            isSending = false;
        }
    }

    async function endSession(){
        if (!currentSessionId) {
            setStatus('No active session to end.');
            return;
        }
        if (!csrfToken) {
            setStatus('Missing CSRF token.');
            return;
        }

        isSending = true;
        setStatus('Ending session...');
        try {
            const resp = await fetch('/api/gideon/sparring/end', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ session_id: currentSessionId }),
            });

            if (!resp.ok) throw new Error('HTTP ' + resp.status);

            stopTimer();
            setStatus('Session ended.');
            if (inputEl) inputEl.disabled = true;
            if (sendBtn) sendBtn.disabled = true;
        } catch (e) {
            console.error(e);
            setStatus('Error ending session. Check runtime logs.');
        } finally {
            isSending = false;
        }
    }

    if (resetBtn) resetBtn.addEventListener('click', (e) => { e.preventDefault(); resetSession(); });
    if (endBtn)   endBtn.addEventListener('click',   (e) => { e.preventDefault(); if (!isSending) endSession(); });
    if (startBtn) startBtn.addEventListener('click', (e) => { e.preventDefault(); startSessionOnly(); });

    if (formEl) {
        formEl.addEventListener('submit', function(e){
            e.preventDefault();
            if (isSending) return;

            const value = (inputEl.value || '').trim();
            if (!value) return;

            // if they never hit Start, we still start timer on first send
            if (!startedAt) startTimer();

            inputEl.value = '';
            sendToGideon(value);
        });
    }

    resetSession();
});
</script>
@endsection
