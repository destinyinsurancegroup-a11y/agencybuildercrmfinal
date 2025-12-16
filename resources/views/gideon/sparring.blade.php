@extends('layouts.app')

@section('content')
@php
    $defaultScenario = ($scenarios ?? collect())->first();
    $defaultScenarioCode = $defaultScenario?->code ?? null;
@endphp

<div class="abc-sp-container">

    {{-- Header --}}
    <div class="abc-sp-header">
        <div>
            <h1 class="abc-sp-title">ABC Sparring Partner</h1>
            <div class="abc-sp-subtitle">
                Live conversation training with a human-like prospect.
            </div>
        </div>

        <div class="abc-sp-header-actions">
            <div class="abc-pill" id="timerPill">⏱ 00:00</div>

            {{-- NEW: Voice toggle (TTS) --}}
            <button class="abc-btn" id="ttsToggleBtn" type="button" title="Turn voice on/off">
                🔊 Voice: ON
            </button>

            <button class="abc-btn abc-btn-danger" id="endSessionBtn" type="button">End Session</button>
        </div>
    </div>

    <div class="abc-sp-grid">

        {{-- LEFT: Controls + Avatar/Phone --}}
        <div class="abc-card abc-right">

            {{-- Top control row --}}
            <div class="abc-top-controls">

                <div class="abc-control">
                    <div class="abc-label">Environment</div>
                    <div class="abc-seg">
                        <button type="button" class="abc-seg-btn is-active" id="envPhoneBtn">Phone</button>
                        <button type="button" class="abc-seg-btn" id="envInPersonBtn">In Person</button>
                    </div>
                    <div class="abc-help">Phone shows voice waves. In-person shows the human avatar.</div>
                </div>

                <div class="abc-control">
                    <div class="abc-label">UI Role Mode</div>
                    <div class="abc-seg">
                        <button type="button" class="abc-seg-btn is-active" data-uimode="prospect_simulation" id="modeYouAgentBtn">
                            Prospect sim (You = Agent)
                        </button>
                        <button type="button" class="abc-seg-btn" data-uimode="agent_simulation" id="modeYouProspectBtn">
                            Role reversal (You = Prospect)
                        </button>
                    </div>
                    <div class="abc-help">This is not Training Mode.</div>
                </div>
            </div>

            {{-- Avatar/Phone Panel --}}
            <div class="abc-avatar-panel" id="stagePanel">

                <div class="abc-prospect-badge">
                    <span class="abc-prospect-label">PROSPECT</span>
                    <span class="abc-prospect-emotion" id="emotionLabel">Neutral</span>
                </div>

                {{-- PHONE view (VOICE WAVES) --}}
                <div id="phonePanel" class="abc-phone-panel">
                    <div class="abc-wave-wrap">
                        <div class="abc-wave-title">On the phone…</div>
                        <div class="abc-wave-sub">Voice activity</div>

                        <div class="abc-wave-bars" id="waveBars" aria-label="voice waveform">
                            <span></span><span></span><span></span><span></span><span></span>
                            <span></span><span></span><span></span><span></span><span></span>
                            <span></span><span></span><span></span><span></span><span></span>
                        </div>

                        <div class="abc-wave-hint" id="waveHint">Waiting…</div>
                    </div>
                </div>

                {{-- IN-PERSON view (HUMAN AVATAR + idle/speaking animations) --}}
                <div id="avatarPanel" class="abc-avatar-wrap" style="display:none;">
                    <div class="abc-avatar-ring" id="avatarRing">
                        <img
                            src="{{ asset('images/gideon/prospect_default.jpg') }}"
                            alt="Prospect avatar"
                            class="abc-avatar-img"
                            id="avatarImg"
                        >
                        {{-- simple “mouth” overlay (optional visual cue) --}}
                        <div class="abc-mouth" id="avatarMouth"></div>
                    </div>
                    <div class="abc-avatar-caption" id="avatarCaption">Listening…</div>
                </div>

            </div>

            <div class="abc-center-actions">
                <button type="button" class="abc-btn abc-btn-gold abc-btn-lg" id="startSessionBtn">
                    ▶ Start Sparring Session
                </button>
            </div>

            {{-- Bottom controls --}}
            <div class="abc-bottom-controls">
                <div class="abc-control">
                    <div class="abc-label">Difficulty</div>
                    <div class="abc-seg abc-seg-row">
                        <button type="button" class="abc-seg-btn" data-difficulty="beginner" id="diffBeginnerBtn">Beginner</button>
                        <button type="button" class="abc-seg-btn is-active" data-difficulty="intermediate" id="diffIntermediateBtn">Intermediate</button>
                        <button type="button" class="abc-seg-btn" data-difficulty="advanced" id="diffAdvancedBtn">Advanced</button>
                    </div>
                </div>

                <div class="abc-control">
                    <div class="abc-label">Training</div>
                    <div class="abc-seg abc-seg-row">
                        <button type="button" class="abc-seg-btn is-active" id="trainFullBtn">Full Presentation</button>
                        <button type="button" class="abc-seg-btn" id="trainDiscoBtn">Disco</button>
                        <button type="button" class="abc-seg-btn" id="trainSegmentsBtn">Segments</button>
                    </div>
                </div>

                <div class="abc-control abc-control-right" id="segmentWrap" style="display:none;">
                    <div class="abc-label">Segment</div>
                    <select id="segmentSelect" class="abc-select">
                        <option value="intro">Intro</option>
                        <option value="discovery" selected>Disco</option>
                        <option value="education">Educ</option>
                        <option value="qualify">Qual</option>
                        <option value="quote">Quote</option>
                        <option value="close">Close</option>
                    </select>
                    <div class="abc-help">Highlights what you’re training.</div>
                </div>
            </div>

        </div>

        {{-- RIGHT: Live conversation panel --}}
        <div class="abc-card abc-left">
            <div class="abc-card-header">
                <div class="abc-card-title">LIVE CONVERSATION</div>
                <button class="abc-btn abc-btn-ghost" id="resetSessionBtn" type="button">Reset</button>
            </div>

            <div id="sparringTranscript" class="abc-transcript">
                <div class="abc-muted">
                    Click <strong>Start Sparring Session</strong>, then type your first line.
                </div>
            </div>

            <form id="sparringForm" class="abc-input-row">
                <input type="hidden" id="scenarioCode" value="{{ $defaultScenarioCode }}">

                <input id="sparringInput"
                       type="text"
                       class="abc-input"
                       placeholder="Type what you'd say and press Enter..."
                       autocomplete="off" />

                <button id="sendBtn" class="abc-btn abc-btn-gold" type="submit">Send</button>
            </form>

            <div class="abc-status" id="sparringStatus"></div>
        </div>

    </div>
</div>

<style>
    :root{
        --abc-border: rgba(255,215,100,.14);
        --abc-text: rgba(255,255,255,.92);
        --abc-muted: rgba(255,255,255,.58);
        --abc-gold: #d6a24a;
        --abc-gold-2: #b9893f;
        --abc-danger: #ff4d4f;
    }
    .abc-sp-container{ padding: 22px 22px 28px; max-width: 1320px; margin: 0 auto; color: var(--abc-text); }
    .abc-sp-header{ display:flex; justify-content:space-between; align-items:flex-start; gap: 16px; margin-bottom: 16px; }
    .abc-sp-title{ font-size: 44px; letter-spacing:.5px; margin:0; }
    .abc-sp-subtitle{ color: var(--abc-muted); margin-top: 4px; }
    .abc-sp-header-actions{ display:flex; align-items:center; gap: 10px; }
    .abc-pill{ padding: 8px 12px; border-radius: 999px; background: rgba(0,0,0,.45); border: 1px solid var(--abc-border); font-size: 14px; }

    .abc-sp-grid{ display:grid; grid-template-columns: 1fr 420px; gap: 18px; align-items: stretch; }

    .abc-card{
        background: radial-gradient(1200px 600px at 20% 10%, rgba(214,162,74,.18), transparent 55%),
                    radial-gradient(800px 500px at 90% 30%, rgba(214,162,74,.08), transparent 55%),
                    linear-gradient(180deg, rgba(20,22,29,.9), rgba(10,11,15,.9));
        border: 1px solid rgba(255,215,100,.10);
        border-radius: 18px;
        box-shadow: 0 18px 55px rgba(0,0,0,.55);
        overflow:hidden;
    }
    .abc-left{ padding: 14px; display:flex; flex-direction:column; min-height: 720px;}
    .abc-right{ padding: 14px; min-height: 720px; display:flex; flex-direction:column; }

    .abc-card-header{ display:flex; align-items:center; justify-content:space-between; padding: 10px 10px 12px; border-bottom: 1px solid rgba(255,215,100,.08); }
    .abc-card-title{ font-size: 12px; letter-spacing: .18em; color: rgba(255,215,100,.85); }

    .abc-transcript{
        padding: 12px 10px;
        margin-top: 10px;
        border-radius: 14px;
        border: 1px dashed rgba(255,215,100,.18);
        background: rgba(0,0,0,.22);
        flex: 1;
        overflow-y:auto;
    }
    .abc-muted{ color: var(--abc-muted); font-size: 13px; }

    .abc-msg{ display:flex; flex-direction:column; gap:6px; margin: 10px 0; }
    .abc-bubble{
        max-width: 92%;
        border-radius: 14px;
        padding: 10px 12px;
        line-height: 1.35;
        border: 1px solid rgba(255,215,100,.12);
        background: rgba(0,0,0,.30);
        font-size: 14px;
        white-space: pre-wrap;
    }
    .abc-bubble.you{ margin-left:auto; border-color: rgba(214,162,74,.30); background: rgba(214,162,74,.12); }
    .abc-bubble.them{ margin-right:auto; border-color: rgba(255,255,255,.10); background: rgba(255,255,255,.06); }
    .abc-time{ font-size: 12px; color: rgba(255,255,255,.45); }

    .abc-input-row{ display:flex; gap:10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,215,100,.08); }
    .abc-input{
        flex: 1;
        background: rgba(0,0,0,.35);
        border: 1px solid rgba(255,215,100,.14);
        color: var(--abc-text);
        border-radius: 12px;
        padding: 12px 12px;
        outline:none;
    }
    .abc-input:focus{ border-color: rgba(214,162,74,.45); }
    .abc-status{ margin-top: 10px; color: rgba(255,255,255,.55); font-size: 12px; min-height: 16px; }

    .abc-btn{
        border-radius: 12px;
        padding: 10px 12px;
        border: 1px solid rgba(255,215,100,.14);
        background: rgba(0,0,0,.35);
        color: var(--abc-text);
        cursor:pointer;
        white-space:nowrap;
    }
    .abc-btn:hover{ border-color: rgba(214,162,74,.40); }
    .abc-btn-ghost{ background: transparent; }
    .abc-btn-danger{ border-color: rgba(255,77,79,.45); background: rgba(255,77,79,.10); }
    .abc-btn-gold{
        background: linear-gradient(180deg, rgba(214,162,74,.95), rgba(185,137,63,.95));
        border-color: rgba(214,162,74,.55);
        color: #0b0c0f;
        font-weight: 700;
    }
    .abc-btn-lg{ padding: 12px 18px; border-radius: 999px; min-width: 260px; }

    .abc-top-controls{ display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
    .abc-bottom-controls{ display:grid; grid-template-columns: 1fr 1fr 280px; gap: 12px; margin-top: 14px; }

    .abc-control .abc-label{ font-size: 12px; color: rgba(255,215,100,.85); letter-spacing: .10em; margin-bottom: 8px; }
    .abc-help{ font-size: 12px; color: rgba(255,255,255,.45); margin-top: 8px; }

    .abc-seg{ display:flex; gap: 10px; flex-wrap:wrap; }
    .abc-seg-row{ flex-wrap:nowrap; }
    .abc-seg-btn{
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid rgba(255,215,100,.14);
        background: rgba(0,0,0,.28);
        color: rgba(255,255,255,.85);
        cursor:pointer;
    }
    .abc-seg-btn.is-active{
        border-color: rgba(214,162,74,.55);
        background: rgba(214,162,74,.18);
        color: rgba(255,255,255,.95);
    }

    .abc-avatar-panel{
        flex: 1;
        border-radius: 18px;
        border: 1px solid rgba(255,215,100,.10);
        background: rgba(0,0,0,.22);
        display:flex;
        align-items:center;
        justify-content:center;
        position:relative;
        min-height: 420px;
        padding: 18px;
    }
    .abc-prospect-badge{
        position:absolute;
        top: 12px;
        left: 12px;
        display:flex;
        gap: 10px;
        align-items:center;
        padding: 8px 10px;
        border-radius: 999px;
        background: rgba(0,0,0,.40);
        border: 1px solid rgba(255,215,100,.14);
    }
    .abc-prospect-label{ font-size: 12px; letter-spacing:.10em; color: rgba(255,215,100,.9); }
    .abc-prospect-emotion{ font-size: 12px; color: rgba(255,255,255,.75); }

    /* =========================
       PHONE: VOICE WAVES
       ========================= */
    .abc-wave-wrap{
        width: min(520px, 100%);
        padding: 22px;
        border-radius: 18px;
        border: 1px solid rgba(255,215,100,.12);
        background: rgba(0,0,0,.28);
        text-align:center;
    }
    .abc-wave-title{ font-size: 18px; font-weight: 700; }
    .abc-wave-sub{ margin-top: 4px; font-size: 13px; color: rgba(255,255,255,.65); }
    .abc-wave-hint{ margin-top: 14px; font-size: 12px; color: rgba(255,255,255,.55); }

    .abc-wave-bars{
        margin: 18px auto 0;
        height: 110px;
        width: min(420px, 100%);
        display:flex;
        align-items:flex-end;
        justify-content:center;
        gap: 8px;
        padding: 14px;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,.06);
        background: radial-gradient(600px 300px at 20% 10%, rgba(214,162,74,.12), transparent 60%),
                    rgba(255,255,255,.03);
        overflow:hidden;
    }
    .abc-wave-bars span{
        display:block;
        width: 10px;
        height: 14px;
        border-radius: 999px;
        background: rgba(214,162,74,.55);
        box-shadow: 0 0 18px rgba(214,162,74,.12);
        transform-origin: bottom;
        opacity: .55;
    }
    /* speaking state */
    .is-speaking .abc-wave-bars span{
        opacity: .95;
        animation: abcWaveBounce 520ms ease-in-out infinite;
    }
    .is-speaking .abc-wave-hint{ color: rgba(214,162,74,.9); }

    /* stagger bars */
    .abc-wave-bars span:nth-child(1){ animation-delay: 0ms; }
    .abc-wave-bars span:nth-child(2){ animation-delay: 60ms; }
    .abc-wave-bars span:nth-child(3){ animation-delay: 120ms; }
    .abc-wave-bars span:nth-child(4){ animation-delay: 180ms; }
    .abc-wave-bars span:nth-child(5){ animation-delay: 240ms; }
    .abc-wave-bars span:nth-child(6){ animation-delay: 60ms; }
    .abc-wave-bars span:nth-child(7){ animation-delay: 120ms; }
    .abc-wave-bars span:nth-child(8){ animation-delay: 180ms; }
    .abc-wave-bars span:nth-child(9){ animation-delay: 240ms; }
    .abc-wave-bars span:nth-child(10){ animation-delay: 300ms; }
    .abc-wave-bars span:nth-child(11){ animation-delay: 80ms; }
    .abc-wave-bars span:nth-child(12){ animation-delay: 140ms; }
    .abc-wave-bars span:nth-child(13){ animation-delay: 200ms; }
    .abc-wave-bars span:nth-child(14){ animation-delay: 260ms; }
    .abc-wave-bars span:nth-child(15){ animation-delay: 320ms; }

    @keyframes abcWaveBounce{
        0%{ transform: scaleY(.6); }
        50%{ transform: scaleY(3.3); }
        100%{ transform: scaleY(.8); }
    }

    /* =========================
       IN-PERSON: AVATAR IDLE + SPEAK
       ========================= */
    .abc-avatar-wrap{ display:flex; flex-direction:column; align-items:center; gap: 12px; }
    .abc-avatar-caption{ font-size: 12px; color: rgba(255,255,255,.60); }

    .abc-avatar-ring{
        width: 340px;
        height: 340px;
        border-radius: 999px;
        border: 2px solid rgba(214,162,74,.55);
        padding: 10px;
        display:flex;
        align-items:center;
        justify-content:center;
        background: radial-gradient(circle at 30% 20%, rgba(214,162,74,.12), rgba(0,0,0,.10));
        position: relative;
        transform: translateZ(0);
        animation: abcAvatarIdle 3.2s ease-in-out infinite;
    }
    @keyframes abcAvatarIdle{
        0%{ transform: translateY(0px) scale(1); }
        50%{ transform: translateY(-6px) scale(1.01); }
        100%{ transform: translateY(0px) scale(1); }
    }

    .abc-avatar-img{
        width: 100%;
        height: 100%;
        border-radius: 999px;
        object-fit: cover;
        border: 1px solid rgba(255,255,255,.08);
        filter: saturate(1.05) contrast(1.05);
    }

    /* mouth indicator */
    .abc-mouth{
        position:absolute;
        bottom: 76px;
        left: 50%;
        transform: translateX(-50%);
        width: 74px;
        height: 10px;
        border-radius: 999px;
        background: rgba(0,0,0,.35);
        border: 1px solid rgba(255,255,255,.10);
        opacity: .0;
    }

    .avatar-speaking .abc-avatar-ring{
        animation: abcAvatarSpeak 640ms ease-in-out infinite;
        box-shadow: 0 0 40px rgba(214,162,74,.20);
        border-color: rgba(214,162,74,.85);
    }
    .avatar-speaking .abc-mouth{
        opacity: .95;
        animation: abcMouth 220ms ease-in-out infinite;
        background: rgba(214,162,74,.55);
        border-color: rgba(214,162,74,.75);
    }

    @keyframes abcAvatarSpeak{
        0%{ transform: translateY(0px) scale(1.00); }
        50%{ transform: translateY(-2px) scale(1.02); }
        100%{ transform: translateY(0px) scale(1.00); }
    }
    @keyframes abcMouth{
        0%{ transform: translateX(-50%) scaleX(.8); height: 8px; }
        50%{ transform: translateX(-50%) scaleX(1.2); height: 14px; }
        100%{ transform: translateX(-50%) scaleX(.9); height: 9px; }
    }

    .abc-center-actions{ display:flex; justify-content:center; margin-top: 14px; }
    .abc-select{
        width: 100%;
        background: rgba(0,0,0,.35);
        border: 1px solid rgba(255,215,100,.14);
        color: rgba(255,255,255,.88);
        border-radius: 12px;
        padding: 10px 12px;
        outline:none;
    }

    @media (max-width: 1100px){
        .abc-sp-grid{ grid-template-columns: 1fr; }
        .abc-left{ min-height: 520px; }
        .abc-right{ min-height: 620px; }
        .abc-top-controls{ grid-template-columns: 1fr; }
        .abc-bottom-controls{ grid-template-columns: 1fr; }
        .abc-seg-row{ flex-wrap: wrap; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const transcriptEl = document.getElementById('sparringTranscript');
    const inputEl = document.getElementById('sparringInput');
    const formEl = document.getElementById('sparringForm');
    const sendBtn = document.getElementById('sendBtn');
    const statusEl = document.getElementById('sparringStatus');

    const resetBtn = document.getElementById('resetSessionBtn');
    const endBtn = document.getElementById('endSessionBtn');
    const startBtn = document.getElementById('startSessionBtn');

    const scenarioCodeEl = document.getElementById('scenarioCode');

    const envPhoneBtn = document.getElementById('envPhoneBtn');
    const envInPersonBtn = document.getElementById('envInPersonBtn');
    const phonePanel = document.getElementById('phonePanel');
    const avatarPanel = document.getElementById('avatarPanel');

    const modeYouAgentBtn = document.getElementById('modeYouAgentBtn');
    const modeYouProspectBtn = document.getElementById('modeYouProspectBtn');

    const diffBeginnerBtn = document.getElementById('diffBeginnerBtn');
    const diffIntermediateBtn = document.getElementById('diffIntermediateBtn');
    const diffAdvancedBtn = document.getElementById('diffAdvancedBtn');

    const trainFullBtn = document.getElementById('trainFullBtn');
    const trainDiscoBtn = document.getElementById('trainDiscoBtn');
    const trainSegmentsBtn = document.getElementById('trainSegmentsBtn');
    const segmentWrap = document.getElementById('segmentWrap');

    const emotionLabel = document.getElementById('emotionLabel');
    const timerPill = document.getElementById('timerPill');

    // NEW: speaking UI elements
    const stagePanel = document.getElementById('stagePanel');
    const waveHint = document.getElementById('waveHint');
    const avatarCaption = document.getElementById('avatarCaption');
    const ttsToggleBtn = document.getElementById('ttsToggleBtn');

    let currentSessionId = null;
    let isSending = false;
    let sessionStarted = false;

    let uiMode = 'prospect_simulation';
    let difficulty = 'intermediate';
    let personaKey = 'neutral_balanced';
    let environment = 'phone';

    // training (UI only for now)
    let trainingMode = 'full'; // full | disco | segments
    let selectedSegment = 'discovery';

    // timer
    let timerInt = null;
    let seconds = 0;

    // voice / speech
    let ttsEnabled = true;
    let speakingLock = false;

    function fmtTime(s){
        const mm = String(Math.floor(s/60)).padStart(2,'0');
        const ss = String(s%60).padStart(2,'0');
        return `${mm}:${ss}`;
    }
    function startTimer(){
        stopTimer();
        seconds = 0;
        timerPill.textContent = `⏱ ${fmtTime(seconds)}`;
        timerInt = setInterval(()=>{
            seconds++;
            timerPill.textContent = `⏱ ${fmtTime(seconds)}`;
        }, 1000);
    }
    function stopTimer(){
        if (timerInt) clearInterval(timerInt);
        timerInt = null;
    }

    function setStatus(msg){ statusEl.textContent = msg || ''; }

    function setActive(btn, group){
        group.forEach(b=>b.classList.remove('is-active'));
        btn.classList.add('is-active');
    }

    function mapDifficultyToPersona(d){
        if (d === 'beginner') return { persona: 'soft_conflict_avoidant', emotion: 'Neutral' };
        if (d === 'advanced') return { persona: 'skeptical_guarded', emotion: 'Skeptical' };
        return { persona: 'neutral_balanced', emotion: 'Neutral' };
    }

    function appendBubble(who, text){
        if (!text) return;

        const msg = document.createElement('div');
        msg.className = 'abc-msg';

        const bubble = document.createElement('div');
        bubble.className = 'abc-bubble ' + (who === 'you' ? 'you' : 'them');
        bubble.textContent = text;

        const time = document.createElement('div');
        time.className = 'abc-time';
        const now = new Date();
        time.textContent = now.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});

        msg.appendChild(bubble);
        msg.appendChild(time);
        transcriptEl.appendChild(msg);
        transcriptEl.scrollTop = transcriptEl.scrollHeight;
    }

    function resetSession(){
        currentSessionId = null;
        sessionStarted = false;
        isSending = false;
        stopTimer();
        timerPill.textContent = '⏱ 00:00';

        transcriptEl.innerHTML = `
            <div class="abc-muted">
                Click <strong>Start Sparring Session</strong>, then type your first line.
            </div>
        `;
        inputEl.value = '';
        inputEl.disabled = true;
        sendBtn.disabled = true;
        setStatus('');

        // reset speaking UI
        stopProspectSpeakingUi();
        if (waveHint) waveHint.textContent = 'Waiting…';
        if (avatarCaption) avatarCaption.textContent = 'Listening…';
    }

    function unlockInput(){
        inputEl.disabled = false;
        sendBtn.disabled = false;
        inputEl.focus();
    }

    // ==========
    // Speaking UI helpers
    // ==========
    function startProspectSpeakingUi(){
        // Phone waves animate
        if (stagePanel) stagePanel.classList.add('is-speaking');

        // Avatar “speaking” animation
        document.body.classList.add('avatar-speaking');
        if (avatarCaption) avatarCaption.textContent = 'Speaking…';
        if (waveHint) waveHint.textContent = 'Speaking…';
    }

    function stopProspectSpeakingUi(){
        if (stagePanel) stagePanel.classList.remove('is-speaking');
        document.body.classList.remove('avatar-speaking');
        if (avatarCaption) avatarCaption.textContent = 'Listening…';
        if (waveHint) waveHint.textContent = 'Waiting…';
    }

    // Speak text out loud (optional)
    function speakProspect(text){
        if (!ttsEnabled) {
            // still do a short animation for “game feel”
            startProspectSpeakingUi();
            setTimeout(stopProspectSpeakingUi, Math.min(1600, 400 + (text.length * 18)));
            return;
        }

        if (!('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
            // No TTS available, fallback to animation only
            startProspectSpeakingUi();
            setTimeout(stopProspectSpeakingUi, Math.min(1600, 400 + (text.length * 18)));
            return;
        }

        // cancel anything currently speaking
        try { window.speechSynthesis.cancel(); } catch (e) {}

        const u = new SpeechSynthesisUtterance(text);
        u.rate = 1.02;     // slight “natural”
        u.pitch = 0.95;
        u.volume = 1;

        // If you want to force an English voice when available:
        // const voices = window.speechSynthesis.getVoices();
        // const v = voices.find(v => /en/i.test(v.lang));
        // if (v) u.voice = v;

        speakingLock = true;
        startProspectSpeakingUi();

        u.onend = () => {
            speakingLock = false;
            stopProspectSpeakingUi();
        };
        u.onerror = () => {
            speakingLock = false;
            stopProspectSpeakingUi();
        };

        window.speechSynthesis.speak(u);
    }

    // ==========
    // Environment toggles
    // ==========
    envPhoneBtn.addEventListener('click', ()=>{
        environment = 'phone';
        setActive(envPhoneBtn, [envPhoneBtn, envInPersonBtn]);
        phonePanel.style.display = 'flex';
        avatarPanel.style.display = 'none';
    });
    envInPersonBtn.addEventListener('click', ()=>{
        environment = 'in_person';
        setActive(envInPersonBtn, [envPhoneBtn, envInPersonBtn]);
        phonePanel.style.display = 'none';
        avatarPanel.style.display = 'flex';
    });

    // ==========
    // Role mode toggles
    // ==========
    modeYouAgentBtn.addEventListener('click', ()=>{
        uiMode = 'prospect_simulation';
        setActive(modeYouAgentBtn, [modeYouAgentBtn, modeYouProspectBtn]);
    });
    modeYouProspectBtn.addEventListener('click', ()=>{
        uiMode = 'agent_simulation';
        setActive(modeYouProspectBtn, [modeYouAgentBtn, modeYouProspectBtn]);
    });

    // ==========
    // Difficulty toggles
    // ==========
    function setDifficulty(d, btn){
        difficulty = d;
        const mapped = mapDifficultyToPersona(difficulty);
        personaKey = mapped.persona;
        emotionLabel.textContent = mapped.emotion;
        setActive(btn, [diffBeginnerBtn, diffIntermediateBtn, diffAdvancedBtn]);
    }
    diffBeginnerBtn.addEventListener('click', ()=>setDifficulty('beginner', diffBeginnerBtn));
    diffIntermediateBtn.addEventListener('click', ()=>setDifficulty('intermediate', diffIntermediateBtn));
    diffAdvancedBtn.addEventListener('click', ()=>setDifficulty('advanced', diffAdvancedBtn));

    // ==========
    // Training toggles
    // ==========
    function setTraining(mode, btn){
        trainingMode = mode;
        setActive(btn, [trainFullBtn, trainDiscoBtn, trainSegmentsBtn]);

        if (trainingMode === 'segments'){
            segmentWrap.style.display = 'block';
        } else {
            segmentWrap.style.display = 'none';
        }
    }
    trainFullBtn.addEventListener('click', ()=>setTraining('full', trainFullBtn));
    trainDiscoBtn.addEventListener('click', ()=>setTraining('disco', trainDiscoBtn));
    trainSegmentsBtn.addEventListener('click', ()=>setTraining('segments', trainSegmentsBtn));

    document.getElementById('segmentSelect')?.addEventListener('change', (e)=>{
        selectedSegment = e.target.value;
    });

    // ==========
    // Voice toggle
    // ==========
    ttsToggleBtn.addEventListener('click', ()=>{
        ttsEnabled = !ttsEnabled;
        ttsToggleBtn.textContent = ttsEnabled ? '🔊 Voice: ON' : '🔇 Voice: OFF';
        if (!ttsEnabled) {
            try { window.speechSynthesis.cancel(); } catch (e) {}
            stopProspectSpeakingUi();
        }
    });

    // ==========
    // Start session
    // ==========
    startBtn.addEventListener('click', ()=>{
        if (!scenarioCodeEl.value) {
            setStatus('No scenarios found. Seed at least one GideonScenario.');
            return;
        }
        sessionStarted = true;
        unlockInput();
        startTimer();
        setStatus('Session started. Say your first line.');
    });

    resetBtn.addEventListener('click', (e)=>{ e.preventDefault(); resetSession(); });

    async function postAsk(message){
        if (!csrfToken) { setStatus('Missing CSRF token.'); return; }
        if (!scenarioCodeEl.value) { setStatus('No scenario available.'); return; }
        if (!sessionStarted) { setStatus('Click Start Sparring Session first.'); return; }

        isSending = true;
        setStatus('Talking to Gideon...');
        appendBubble('you', message);

        try {
            const res = await fetch('/api/gideon/sparring/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    scenario_code: scenarioCodeEl.value,
                    mode: uiMode,
                    persona: personaKey,
                    session_id: currentSessionId,
                    message: message,

                    // UI extras (backend can ignore for now)
                    difficulty: difficulty,
                    environment: environment,
                    training_mode: trainingMode,
                    selected_stage: selectedSegment, // you said stage name "intro" stays "intro"
                }),
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            if (data.session?.id) currentSessionId = data.session.id;

            if (data.opening_line) {
                appendBubble('them', data.opening_line);
                speakProspect(data.opening_line);
            }

            if (data.gideon_reply?.content) {
                appendBubble('them', data.gideon_reply.content);
                speakProspect(data.gideon_reply.content);
            }

            setStatus('Session active.');
        } catch (err) {
            console.error(err);
            setStatus('Error talking to Gideon. Check runtime logs + browser console.');
        } finally {
            isSending = false;
        }
    }

    formEl.addEventListener('submit', (e)=>{
        e.preventDefault();
        if (isSending) return;
        if (speakingLock) {
            // optional: prevent interrupting while the prospect is speaking
            // return;
        }
        const msg = (inputEl.value || '').trim();
        if (!msg) return;
        inputEl.value = '';
        postAsk(msg);
    });

    endBtn.addEventListener('click', async (e)=>{
        e.preventDefault();
        if (!currentSessionId) { setStatus('No active session.'); return; }
        if (!csrfToken) { setStatus('Missing CSRF token.'); return; }

        isSending = true;
        setStatus('Ending session...');
        try {
            const res = await fetch('/api/gideon/sparring/end', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ session_id: currentSessionId }),
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            try { window.speechSynthesis.cancel(); } catch (e) {}
            stopProspectSpeakingUi();

            stopTimer();
            setStatus('Session ended.');
            inputEl.disabled = true;
            sendBtn.disabled = true;
        } catch (err) {
            console.error(err);
            setStatus('Error ending session. Check runtime logs.');
        } finally {
            isSending = false;
        }
    });

    // init
    resetSession();
    setDifficulty('intermediate', diffIntermediateBtn);
    setTraining('full', trainFullBtn);
    envPhoneBtn.click(); // default to Phone (waves)
});
</script>
@endsection
