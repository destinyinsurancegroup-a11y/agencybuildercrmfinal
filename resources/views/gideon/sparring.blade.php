@extends('layouts.app')

@section('content')
@php
    $defaultScenario = ($scenarios ?? collect())->first();
    $defaultScenarioCode = $defaultScenario?->code ?? null;
@endphp

<div class="abc-sp-container">
    <div class="abc-sp-header">
        <div>
            <h1 class="abc-sp-title">ABC Sparring Partner</h1>
            <div class="abc-sp-subtitle">Live conversation training with a human-like prospect.</div>
        </div>

        <div class="abc-sp-header-actions">
            <div class="abc-pill" id="timerPill">⏱ 00:00</div>

            <button class="abc-btn" id="ttsToggleBtn" type="button" title="Turn voice on/off">
                🔊 Voice: ON
            </button>

            <button class="abc-btn abc-btn-danger" id="endSessionBtn" type="button">End Session</button>
        </div>
    </div>

    <div class="abc-sp-grid">

        {{-- LEFT --}}
        <div class="abc-card abc-right">

            {{-- Difficulty (TOP / CENTERED) --}}
            <div class="abc-difficulty-top">
                <div class="abc-control abc-control-center">
                    <div class="abc-label">Difficulty</div>
                    <div class="abc-seg abc-seg-row abc-seg-center">
                        <button type="button" class="abc-seg-btn" id="diffBeginnerBtn">Beginner</button>
                        <button type="button" class="abc-seg-btn is-active" id="diffIntermediateBtn">Intermediate</button>
                        <button type="button" class="abc-seg-btn" id="diffAdvancedBtn">Advanced</button>
                    </div>
                </div>
            </div>

            {{-- Stage --}}
            <div class="abc-avatar-panel" id="stagePanel">

                <div class="abc-stage-hud">
                    <div class="abc-stage-group">
                        <div class="abc-stage-label">Environment</div>
                        <div class="abc-seg abc-seg-row">
                            <button type="button" class="abc-seg-btn abc-seg-btn-sm is-active" id="envPhoneBtn">Phone</button>
                            <button type="button" class="abc-seg-btn abc-seg-btn-sm" id="envInPersonBtn">In Person</button>
                        </div>
                    </div>

                    <div class="abc-stage-group abc-stage-group-right">
                        <div class="abc-stage-label">Prospect</div>
                        <div class="abc-seg abc-seg-row">
                            <button type="button" class="abc-seg-btn abc-seg-btn-sm is-active" data-prospect="p1" id="prospectP1Btn">P1</button>
                            <button type="button" class="abc-seg-btn abc-seg-btn-sm" data-prospect="p2" id="prospectP2Btn">P2</button>
                            <button type="button" class="abc-seg-btn abc-seg-btn-sm" data-prospect="p3" id="prospectP3Btn">P3</button>
                            <button type="button" class="abc-seg-btn abc-seg-btn-sm" data-prospect="p4" id="prospectP4Btn">P4</button>
                        </div>
                        <div class="abc-stage-prospect-name" id="prospectName">Prospect 1</div>
                    </div>
                </div>

                {{-- PHONE mode --}}
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

                {{-- IN PERSON mode --}}
                <div id="avatarPanel" class="abc-avatar-wrap" style="display:none;">

                    <div class="abc-avatar-mode-row">
                        <div class="abc-avatar-mode-label">Avatar Mode</div>
                        <div class="abc-seg abc-seg-row">
                            <button type="button" class="abc-seg-btn is-active" id="avatarModePhotoBtn">Photo</button>
                            <button type="button" class="abc-seg-btn" id="avatarModeLive2dBtn">Live2D</button>
                            <button type="button" class="abc-seg-btn" id="avatarMode3dBtn">3D</button>
                        </div>
                        <div class="abc-help">Photo uses the prospect library. Live2D/3D are placeholders for now.</div>
                    </div>

                    {{-- PHOTO --}}
                    <div id="avatarPhotoWrap" class="abc-avatar-photo">
                        <div class="abc-avatar-ring avatar-react-neutral" id="avatarRing">
                            <div class="abc-avatar-head" id="avatarHead">
                                <img
                                    src="{{ url('/images/gideon/prospect_default.jpg') }}"
                                    alt="Prospect avatar"
                                    class="abc-avatar-img"
                                    id="avatarImg"
                                    loading="eager"
                                    decoding="async"
                                >
                                <div class="abc-blink" id="avatarBlink"></div>
                            </div>

                            <div class="abc-mouth" id="avatarMouth"></div>

                            <div class="abc-avatar-missing" id="avatarMissing" style="display:none;">
                                <div style="font-weight:700;margin-bottom:6px;">Missing avatar file</div>
                                <div style="opacity:.8;margin-bottom:6px;">Tried:</div>
                                <code id="avatarMissingUrl" style="display:block;word-break:break-all;opacity:.95;"></code>
                                <div style="opacity:.8;margin-top:8px;">
                                    Ensure files exist in<br>
                                    <code>public/images/gideon/</code>
                                </div>
                            </div>
                        </div>

                        <div class="abc-avatar-caption" id="avatarCaption">Listening…</div>
                    </div>

                    {{-- LIVE2D --}}
                    <div id="avatarLive2dWrap" class="abc-avatar-live2d" style="display:none;">
                        <div class="abc-live2d-canvas-wrap" id="live2dWrap">
                            <canvas id="live2dCanvas" width="420" height="420"></canvas>
                            <div class="abc-live2d-overlay">Live2D placeholder (ready to wire)</div>
                        </div>
                        <div class="abc-avatar-caption" id="live2dCaption">Listening…</div>
                    </div>

                    {{-- 3D --}}
                    <div id="avatar3dWrap" class="abc-avatar-3d" style="display:none;">
                        <div class="abc-rpm-frame-wrap" id="rpmFrameWrap">
                            <div class="abc-rpm-overlay" id="rpmOverlay">3D placeholder (wiring later)</div>
                        </div>
                        <div class="abc-avatar-caption" id="rpmCaption">Listening…</div>
                    </div>

                </div>
            </div>

            <div class="abc-center-actions">
                <button type="button" class="abc-btn abc-btn-gold abc-btn-lg" id="startSessionBtn">
                    ▶ Start Sparring Session
                </button>
            </div>

            <div class="abc-training-bottom">
                <div class="abc-control abc-control-center">
                    <div class="abc-label">Training</div>
                    <div class="abc-seg abc-seg-row abc-seg-center">
                        <button type="button" class="abc-seg-btn is-active" id="trainFullBtn">Full Presentation</button>
                        <button type="button" class="abc-seg-btn" id="trainDiscoBtn">Disco</button>
                        <button type="button" class="abc-seg-btn" id="trainSegmentsBtn">Segments</button>
                    </div>
                </div>

                <div class="abc-control abc-control-center" id="segmentWrap" style="display:none; margin-top:10px;">
                    <div class="abc-label">Segment</div>
                    <select id="segmentSelect" class="abc-select" style="max-width:340px;">
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

        {{-- RIGHT --}}
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
/* keep your CSS as-is */
{!! '' !!}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const APP_BASE = "{{ rtrim(url('/'), '/') }}";

    const FALLBACK_AVATAR = APP_BASE + "/images/gideon/prospect_default.jpg";

    const PROSPECTS = {
        p1: { name: 'Prospect 1', images: { neutral: APP_BASE + "/images/gideon/avatar_01.jpg", friendly: APP_BASE + "/images/gideon/avatar_01.jpg", skeptical: APP_BASE + "/images/gideon/avatar_01.jpg" }},
        p2: { name: 'Prospect 2', images: { neutral: APP_BASE + "/images/gideon/avatar_02.jpg", friendly: APP_BASE + "/images/gideon/avatar_02.jpg", skeptical: APP_BASE + "/images/gideon/avatar_02.jpg" }},
        p3: { name: 'Prospect 3', images: { neutral: APP_BASE + "/images/gideon/avatar_03.jpg", friendly: APP_BASE + "/images/gideon/avatar_03.jpg", skeptical: APP_BASE + "/images/gideon/avatar_03.jpg" }},
        p4: { name: 'Prospect 4', images: { neutral: APP_BASE + "/images/gideon/avatar_04.jpg", friendly: APP_BASE + "/images/gideon/avatar_04.jpg", skeptical: APP_BASE + "/images/gideon/avatar_04.jpg" }},
    };

    const THINKING_MIN_MS = 350;
    const THINKING_MAX_MS = 900;

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

    const diffBeginnerBtn = document.getElementById('diffBeginnerBtn');
    const diffIntermediateBtn = document.getElementById('diffIntermediateBtn');
    const diffAdvancedBtn = document.getElementById('diffAdvancedBtn');

    const trainFullBtn = document.getElementById('trainFullBtn');
    const trainDiscoBtn = document.getElementById('trainDiscoBtn');
    const trainSegmentsBtn = document.getElementById('trainSegmentsBtn');
    const segmentWrap = document.getElementById('segmentWrap');
    const segmentSelect = document.getElementById('segmentSelect');

    const waveHint = document.getElementById('waveHint');
    const waveBars = document.getElementById('waveBars');

    const timerPill = document.getElementById('timerPill');
    const ttsToggleBtn = document.getElementById('ttsToggleBtn');

    const avatarModePhotoBtn = document.getElementById('avatarModePhotoBtn');
    const avatarModeLive2dBtn = document.getElementById('avatarModeLive2dBtn');
    const avatarMode3dBtn = document.getElementById('avatarMode3dBtn');

    const avatarPhotoWrap = document.getElementById('avatarPhotoWrap');
    const avatarLive2dWrap = document.getElementById('avatarLive2dWrap');
    const avatar3dWrap = document.getElementById('avatar3dWrap');
    const live2dCaption = document.getElementById('live2dCaption');
    const rpmCaption = document.getElementById('rpmCaption');

    const avatarRing = document.getElementById('avatarRing');
    const avatarImg = document.getElementById('avatarImg');
    const avatarCaption = document.getElementById('avatarCaption');
    const avatarMouth = document.getElementById('avatarMouth');

    const avatarMissing = document.getElementById('avatarMissing');
    const avatarMissingUrl = document.getElementById('avatarMissingUrl');

    const prospectNameEl = document.getElementById('prospectName');
    const prospectBtns = [
        document.getElementById('prospectP1Btn'),
        document.getElementById('prospectP2Btn'),
        document.getElementById('prospectP3Btn'),
        document.getElementById('prospectP4Btn'),
    ].filter(Boolean);

    let currentSessionId = null;
    let isSending = false;
    let sessionStarted = false;

    const uiMode = 'prospect_simulation';

    let difficulty = 'intermediate';
    let personaKey = 'neutral_balanced';
    let environment = 'phone';

    let trainingMode = 'full';
    let selectedSegment = segmentSelect?.value || 'discovery';

    let avatarMode = 'photo';
    let selectedProspectId = 'p1';

    let timerInt = null;
    let seconds = 0;

    let ttsEnabled = true;
    let speakingTimer = null;
    let visemeTimer = null;

    function setStatus(msg){ statusEl.textContent = msg || ''; }
    function setActive(btn, group){ group.forEach(b=>b.classList.remove('is-active')); btn.classList.add('is-active'); }
    function randInt(min, max){ return Math.floor(Math.random()*(max-min+1))+min; }

    function mapTrainingModeForApi(uiVal){
        if (uiVal === 'segments') return 'stages';
        if (uiVal === 'disco') return 'discovery_start';
        return 'full_presentation';
    }
    function mapDifficultyForApi(uiVal){
        if (uiVal === 'beginner') return 'easy';
        if (uiVal === 'advanced') return 'hard';
        return 'normal';
    }

    function fmtTime(s){
        const mm = String(Math.floor(s/60)).padStart(2,'0');
        const ss = String(s%60).padStart(2,'0');
        return `${mm}:${ss}`;
    }

    function startTimer(){
        stopTimer();
        seconds = 0;
        timerPill.textContent = `⏱ ${fmtTime(seconds)}`;
        timerInt = setInterval(()=>{ seconds++; timerPill.textContent = `⏱ ${fmtTime(seconds)}`; }, 1000);
    }
    function stopTimer(){
        if (timerInt) clearInterval(timerInt);
        timerInt = null;
    }

    function mapDifficulty(d){
        if (d === 'beginner') return { persona:'soft_conflict_avoidant', face:'friendly', react:'friendly' };
        if (d === 'advanced') return { persona:'skeptical_guarded', face:'skeptical', react:'skeptical' };
        return { persona:'neutral_balanced', face:'neutral', react:'neutral' };
    }

    function currentProspect(){
        return PROSPECTS[selectedProspectId] || PROSPECTS.p1;
    }

    function showMissingAvatar(show, triedUrl){
        if (!avatarMissing) return;
        avatarMissing.style.display = show ? 'flex' : 'none';
        if (avatarMissingUrl && triedUrl) avatarMissingUrl.textContent = triedUrl;
    }

    function setAvatarSrcSafe(url){
        if (!avatarImg) return;

        const cacheBust = `cb=${Date.now()}`;
        const finalUrl = (url && url.includes('?')) ? `${url}&${cacheBust}` : `${url}?${cacheBust}`;
        showMissingAvatar(false);

        const test = new Image();
        test.decoding = 'async';
        test.onload = () => { avatarImg.src = finalUrl; showMissingAvatar(false); };
        test.onerror = () => {
            console.warn('[Sparring] Avatar image missing (404):', finalUrl);
            avatarImg.src = `${FALLBACK_AVATAR}?${cacheBust}`;
            showMissingAvatar(true, finalUrl);
        };
        test.src = finalUrl;
    }

    function setAvatarFace(faceKey){
        const p = currentProspect();
        const src = p?.images?.[faceKey] || p?.images?.neutral || FALLBACK_AVATAR;
        setAvatarSrcSafe(src);
    }

    function setReactionStyle(react){
        if (!avatarRing) return;
        avatarRing.classList.remove('avatar-react-friendly','avatar-react-neutral','avatar-react-skeptical');
        avatarRing.classList.add(`avatar-react-${react || 'neutral'}`);
    }

    function setProspectCaption(text){
        if (avatarCaption) avatarCaption.textContent = text;
        if (live2dCaption) live2dCaption.textContent = text;
        if (rpmCaption) rpmCaption.textContent = text;
        if (waveHint) waveHint.textContent = text;
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

    function showTypingIndicator(){
        const existing = document.getElementById('typingIndicator');
        if (existing) return;

        const wrap = document.createElement('div');
        wrap.id = 'typingIndicator';
        wrap.className = 'abc-msg';

        const bubble = document.createElement('div');
        bubble.className = 'abc-bubble them';
        bubble.textContent = '…';

        const time = document.createElement('div');
        time.className = 'abc-time';
        time.textContent = 'typing…';

        wrap.appendChild(bubble);
        wrap.appendChild(time);
        transcriptEl.appendChild(wrap);
        transcriptEl.scrollTop = transcriptEl.scrollHeight;
    }
    function removeTypingIndicator(){
        const existing = document.getElementById('typingIndicator');
        if (existing) existing.remove();
    }

    function setSpeaking(on){
        if (!avatarRing) return;
        if (on) avatarRing.classList.add('avatar-speaking');
        else avatarRing.classList.remove('avatar-speaking');
    }
    function setMouthShapeClosed(){
        if (!avatarMouth) return;
        avatarMouth.style.width = '62px';
        avatarMouth.style.height = '10px';
        avatarMouth.style.borderRadius = '999px';
    }
    function startVisemes(text){
        stopVisemes();
        if (!text || !avatarMouth) return;

        const chars = String(text).split('');
        let i = 0;

        visemeTimer = setInterval(()=>{
            const c = (chars[i] || ' ').toLowerCase();
            i++; if (i >= chars.length) i = 0;

            let w = 62, h = 10, r = 999;
            if ('ou'.includes(c)) { w = 32; h = 20; r = 16; }
            else if ('aei'.includes(c)) { w = 74; h = 12; r = 14; }
            else if ('bmp'.includes(c) || c === ' ') { w = 56; h = 8; r = 999; }
            else { w = 60; h = 18; r = 16; }

            avatarMouth.style.width = `${w}px`;
            avatarMouth.style.height = `${h}px`;
            avatarMouth.style.borderRadius = `${r}px`;
        }, 90);
    }
    function stopVisemes(){
        if (visemeTimer) clearInterval(visemeTimer);
        visemeTimer = null;
        setMouthShapeClosed();
    }

    function speakProspect(text){
        setSpeaking(true);
        setProspectCaption('Speaking…');
        startVisemes(text);

        const bars = waveBars ? Array.from(waveBars.querySelectorAll('span')) : [];
        const waveTimer = setInterval(()=>{
            bars.forEach(b=>{
                const v = 0.6 + Math.random() * 3.2;
                b.style.transform = `scaleY(${v})`;
                b.style.opacity = 0.6 + Math.random()*0.4;
            });
        }, 95);

        function endSpeaking(){
            clearInterval(waveTimer);
            if (bars.length){
                bars.forEach(b=>{ b.style.transform = 'scaleY(.8)'; b.style.opacity = '.55'; });
            }
            stopVisemes();
            setSpeaking(false);
            setProspectCaption('Listening…');
        }

        if (ttsEnabled && ('speechSynthesis' in window) && typeof SpeechSynthesisUtterance !== 'undefined'){
            try { window.speechSynthesis.cancel(); } catch(e) {}
            const u = new SpeechSynthesisUtterance(text);
            u.rate = 1.02; u.pitch = 0.95; u.volume = 1;

            const voices = window.speechSynthesis.getVoices?.() || [];
            const preferred = voices.find(v => /en/i.test(v.lang)) || voices[0];
            if (preferred) u.voice = preferred;

            u.onend = endSpeaking;
            u.onerror = endSpeaking;

            window.speechSynthesis.speak(u);
        } else {
            const ms = Math.min(5200, 650 + String(text).length * 24);
            if (speakingTimer) clearTimeout(speakingTimer);
            speakingTimer = setTimeout(endSpeaking, ms);
        }
    }

    function resetSession(){
        currentSessionId = null;
        sessionStarted = false;
        isSending = false;

        try { window.speechSynthesis.cancel(); } catch(e) {}

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

        stopVisemes();
        setSpeaking(false);
        setProspectCaption('Waiting…');
        removeTypingIndicator();
    }

    function unlockInput(){
        inputEl.disabled = false;
        sendBtn.disabled = false;
        inputEl.focus();
    }

    function setProspect(id){
        if (!PROSPECTS[id]) return;
        selectedProspectId = id;

        prospectBtns.forEach(b=>b.classList.remove('is-active'));
        const activeBtn = prospectBtns.find(b => b.dataset.prospect === id);
        if (activeBtn) activeBtn.classList.add('is-active');

        if (prospectNameEl) prospectNameEl.textContent = PROSPECTS[id].name;

        const mapped = mapDifficulty(difficulty);
        setReactionStyle(mapped.react);
        setAvatarFace(mapped.face);
    }
    prospectBtns.forEach(btn => btn.addEventListener('click', ()=> setProspect(btn.dataset.prospect)));

    envPhoneBtn.addEventListener('click', ()=>{
        environment = 'phone';
        setActive(envPhoneBtn, [envPhoneBtn, envInPersonBtn]);
        phonePanel.style.display = 'flex';
        avatarPanel.style.display = 'none';
        setProspectCaption('Waiting…');
    });

    envInPersonBtn.addEventListener('click', ()=>{
        environment = 'in_person';
        setActive(envInPersonBtn, [envPhoneBtn, envInPersonBtn]);
        phonePanel.style.display = 'none';
        avatarPanel.style.display = 'flex';
        setProspectCaption('Listening…');
    });

    function setDifficulty(d, btn){
        difficulty = d;
        const mapped = mapDifficulty(difficulty);
        personaKey = mapped.persona;
        setReactionStyle(mapped.react);
        setAvatarFace(mapped.face);
        setActive(btn, [diffBeginnerBtn, diffIntermediateBtn, diffAdvancedBtn]);
    }
    diffBeginnerBtn.addEventListener('click', ()=>setDifficulty('beginner', diffBeginnerBtn));
    diffIntermediateBtn.addEventListener('click', ()=>setDifficulty('intermediate', diffIntermediateBtn));
    diffAdvancedBtn.addEventListener('click', ()=>setDifficulty('advanced', diffAdvancedBtn));

    function setTraining(mode, btn){
        trainingMode = mode;
        setActive(btn, [trainFullBtn, trainDiscoBtn, trainSegmentsBtn]);
        segmentWrap.style.display = (trainingMode === 'segments') ? 'block' : 'none';
    }
    trainFullBtn.addEventListener('click', ()=>setTraining('full', trainFullBtn));
    trainDiscoBtn.addEventListener('click', ()=>setTraining('disco', trainDiscoBtn));
    trainSegmentsBtn.addEventListener('click', ()=>setTraining('segments', trainSegmentsBtn));
    segmentSelect?.addEventListener('change', (e)=>{ selectedSegment = e.target.value; });

    function setAvatarMode(mode, btn){
        avatarMode = mode;
        setActive(btn, [avatarModePhotoBtn, avatarModeLive2dBtn, avatarMode3dBtn]);
        avatarPhotoWrap.style.display = (avatarMode === 'photo') ? 'flex' : 'none';
        avatarLive2dWrap.style.display = (avatarMode === 'live2d') ? 'flex' : 'none';
        avatar3dWrap.style.display = (avatarMode === '3d') ? 'flex' : 'none';
    }
    avatarModePhotoBtn.addEventListener('click', ()=>setAvatarMode('photo', avatarModePhotoBtn));
    avatarModeLive2dBtn.addEventListener('click', ()=>setAvatarMode('live2d', avatarModeLive2dBtn));
    avatarMode3dBtn.addEventListener('click', ()=>setAvatarMode('3d', avatarMode3dBtn));

    ttsToggleBtn.addEventListener('click', ()=>{
        ttsEnabled = !ttsEnabled;
        ttsToggleBtn.textContent = ttsEnabled ? '🔊 Voice: ON' : '🔇 Voice: OFF';
        if (!ttsEnabled) {
            try { window.speechSynthesis.cancel(); } catch(e) {}
            stopVisemes();
            setSpeaking(false);
            setProspectCaption('Listening…');
        }
    });

    async function readErrorMessage(res){
        try {
            const j = await res.json();
            return j?.message || JSON.stringify(j);
        } catch(e) {
            return `HTTP ${res.status}`;
        }
    }

    // START (creates server session)
    startBtn.addEventListener('click', async ()=>{
        if (!csrfToken) { setStatus('Missing CSRF token.'); return; }
        if (!scenarioCodeEl.value) { setStatus('No scenarios found. Seed at least one GideonScenario.'); return; }
        if (isSending) return;

        isSending = true;
        setStatus('Starting session...');
        setProspectCaption('Starting…');

        try {
            const res = await fetch('/api/gideon/sparring/start', {
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

                    training_mode: mapTrainingModeForApi(trainingMode),
                    selected_stage: (trainingMode === 'segments') ? selectedSegment : null,
                    difficulty: mapDifficultyForApi(difficulty),
                }),
            });

            if (!res.ok) {
                const msg = await readErrorMessage(res);
                throw new Error(msg);
            }

            const data = await res.json();
            if (!data.session?.id) throw new Error('No session returned');

            currentSessionId = data.session.id;
            sessionStarted = true;

            unlockInput();
            startTimer();

            if (data.opening_line) {
                appendBubble('them', data.opening_line);
                speakProspect(data.opening_line);
            } else {
                setProspectCaption(environment === 'phone' ? 'Waiting…' : 'Listening…');
            }

            setStatus('Session started. Say your first line.');
        } catch (err) {
            console.error('[StartSession] Error:', err);
            setStatus(`Error starting session: ${err?.message || 'unknown error'}`);
            setProspectCaption('Waiting…');
            sessionStarted = false;
            currentSessionId = null;
        } finally {
            isSending = false;
        }
    });

    resetBtn.addEventListener('click', (e)=>{ e.preventDefault(); resetSession(); });

    async function postAsk(message){
        if (!csrfToken) { setStatus('Missing CSRF token.'); return; }
        if (!scenarioCodeEl.value) { setStatus('No scenario available.'); return; }
        if (!sessionStarted || !currentSessionId) { setStatus('Click Start Sparring Session first.'); return; }

        isSending = true;
        setStatus('Talking to Gideon...');
        appendBubble('you', message);

        showTypingIndicator();
        setProspectCaption('Thinking…');

        try {
            const res = await fetch('/api/gideon/sparring/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    session_id: currentSessionId,
                    scenario_code: scenarioCodeEl.value,
                    mode: uiMode,
                    persona: personaKey,
                    message: message,
                }),
            });

            if (!res.ok) {
                const msg = await readErrorMessage(res);
                throw new Error(msg);
            }

            const data = await res.json();

            const thinkDelay = randInt(THINKING_MIN_MS, THINKING_MAX_MS);
            await new Promise(r => setTimeout(r, thinkDelay));

            removeTypingIndicator();

            if (data.gideon_reply?.content) {
                appendBubble('them', data.gideon_reply.content);
                speakProspect(data.gideon_reply.content);
            } else {
                setProspectCaption('Listening…');
            }

            setStatus('Session active.');
        } catch (err) {
            console.error(err);
            removeTypingIndicator();
            stopVisemes();
            setSpeaking(false);
            setProspectCaption('Listening…');
            setStatus(`Error talking to Gideon: ${err?.message || 'unknown error'}`);
        } finally {
            isSending = false;
        }
    }

    formEl.addEventListener('submit', (e)=>{
        e.preventDefault();
        if (isSending) return;
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

            if (!res.ok) {
                const msg = await readErrorMessage(res);
                throw new Error(msg);
            }

            try { window.speechSynthesis.cancel(); } catch(e) {}
            stopVisemes();
            setSpeaking(false);

            stopTimer();
            setStatus('Session ended.');
            inputEl.disabled = true;
            sendBtn.disabled = true;
            setProspectCaption('Session ended.');
            sessionStarted = false;
            currentSessionId = null;
        } catch (err) {
            console.error(err);
            setStatus(`Error ending session: ${err?.message || 'unknown error'}`);
        } finally {
            isSending = false;
        }
    });

    // INIT
    resetSession();
    setProspect('p1');
    setDifficulty('intermediate', diffIntermediateBtn);
    setTraining('full', trainFullBtn);
    setAvatarMode('photo', avatarModePhotoBtn);
    envPhoneBtn.click();

    if ('speechSynthesis' in window) {
        window.speechSynthesis.onvoiceschanged = () => {};
    }
});
</script>
@endsection
