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

            {{-- UI Role Mode --}}
            <div class="abc-top-controls abc-top-controls-single">
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

            {{-- Stage --}}
            <div class="abc-avatar-panel" id="stagePanel">

                {{-- Environment toggle (top-left) --}}
                <div class="abc-stage-env">
                    <div class="abc-stage-env-label">Environment</div>
                    <div class="abc-seg abc-seg-row">
                        <button type="button" class="abc-seg-btn abc-seg-btn-sm is-active" id="envPhoneBtn">Phone</button>
                        <button type="button" class="abc-seg-btn abc-seg-btn-sm" id="envInPersonBtn">In Person</button>
                    </div>
                </div>

                {{-- Prospect picker (top-right) --}}
                <div class="abc-stage-prospect">
                    <div class="abc-stage-env-label">Prospect</div>
                    <div class="abc-seg abc-seg-row">
                        <button type="button" class="abc-seg-btn abc-seg-btn-sm is-active" data-prospect="p1" id="prospectP1Btn">P1</button>
                        <button type="button" class="abc-seg-btn abc-seg-btn-sm" data-prospect="p2" id="prospectP2Btn">P2</button>
                        <button type="button" class="abc-seg-btn abc-seg-btn-sm" data-prospect="p3" id="prospectP3Btn">P3</button>
                        <button type="button" class="abc-seg-btn abc-seg-btn-sm" data-prospect="p4" id="prospectP4Btn">P4</button>
                    </div>
                    <div class="abc-stage-prospect-name" id="prospectName">Prospect 1</div>
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
                        <div class="abc-avatar-ring" id="avatarRing">
                            <img
                                src="{{ url('/images/gideon/prospect_default.jpg') }}"
                                alt="Prospect avatar"
                                class="abc-avatar-img"
                                id="avatarImg"
                                loading="eager"
                                decoding="async"
                            >
                            <div class="abc-avatar-missing" id="avatarMissing" style="display:none;">
                                <div style="font-weight:700;margin-bottom:6px;">Missing avatar file</div>
                                <div style="opacity:.8;margin-bottom:6px;">Tried:</div>
                                <code id="avatarMissingUrl" style="display:block;word-break:break-all;opacity:.95;"></code>
                                <div style="opacity:.8;margin-top:8px;">
                                    Ensure files exist in<br>
                                    <code>public/images/gideon/</code>
                                </div>
                            </div>
                            <div class="abc-mouth" id="avatarMouth"></div>
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
                            <iframe
                                id="rpmIframe"
                                src=""
                                title="3D Avatar"
                                allow="autoplay; microphone; camera; clipboard-read; clipboard-write"
                                loading="lazy"
                                referrerpolicy="no-referrer"
                                sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                            ></iframe>
                            <div class="abc-rpm-overlay" id="rpmOverlay">
                                Paste your ReadyPlayerMe avatar URL in the JS config to enable.
                            </div>
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
    :root{
        --abc-border: rgba(255,215,100,.14);
        --abc-text: rgba(255,255,255,.92);
        --abc-muted: rgba(255,255,255,.58);
        --abc-gold: #d6a24a;
        --abc-gold-2: #b9893f;
        --abc-danger: #ff4d4f;
    }
    .abc-sp-container{ padding: 22px 22px 28px; max-width: 1320px; margin: 0 auto; color: var(--abc-text); }
    .abc-sp-header{ display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:16px; }
    .abc-sp-title{ font-size:44px; letter-spacing:.5px; margin:0; }
    .abc-sp-subtitle{ color: var(--abc-muted); margin-top:4px; }
    .abc-sp-header-actions{ display:flex; align-items:center; gap:10px; }
    .abc-pill{ padding:8px 12px; border-radius:999px; background:rgba(0,0,0,.45); border:1px solid var(--abc-border); font-size:14px; }

    .abc-sp-grid{ display:grid; grid-template-columns: 1fr 420px; gap:18px; align-items:stretch; }

    .abc-card{
        background: radial-gradient(1200px 600px at 20% 10%, rgba(214,162,74,.18), transparent 55%),
                    radial-gradient(800px 500px at 90% 30%, rgba(214,162,74,.08), transparent 55%),
                    linear-gradient(180deg, rgba(20,22,29,.9), rgba(10,11,15,.9));
        border:1px solid rgba(255,215,100,.10);
        border-radius:18px;
        box-shadow:0 18px 55px rgba(0,0,0,.55);
        overflow:hidden;
    }
    .abc-left{ padding:14px; display:flex; flex-direction:column; min-height:720px; }
    .abc-right{ padding:14px; min-height:720px; display:flex; flex-direction:column; }

    .abc-card-header{ display:flex; align-items:center; justify-content:space-between; padding:10px 10px 12px; border-bottom:1px solid rgba(255,215,100,.08); }
    .abc-card-title{ font-size:12px; letter-spacing:.18em; color:rgba(255,215,100,.85); }

    .abc-transcript{
        padding:12px 10px;
        margin-top:10px;
        border-radius:14px;
        border:1px dashed rgba(255,215,100,.18);
        background:rgba(0,0,0,.22);
        flex:1;
        overflow-y:auto;
    }
    .abc-muted{ color:var(--abc-muted); font-size:13px; }

    .abc-msg{ display:flex; flex-direction:column; gap:6px; margin:10px 0; }
    .abc-bubble{
        max-width:92%;
        border-radius:14px;
        padding:10px 12px;
        line-height:1.35;
        border:1px solid rgba(255,215,100,.12);
        background:rgba(0,0,0,.30);
        font-size:14px;
        white-space:pre-wrap;
    }
    .abc-bubble.you{ margin-left:auto; border-color:rgba(214,162,74,.30); background:rgba(214,162,74,.12); }
    .abc-bubble.them{ margin-right:auto; border-color:rgba(255,255,255,.10); background:rgba(255,255,255,.06); }
    .abc-time{ font-size:12px; color:rgba(255,255,255,.45); }

    .abc-input-row{ display:flex; gap:10px; margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,215,100,.08); }
    .abc-input{
        flex:1;
        background:rgba(0,0,0,.35);
        border:1px solid rgba(255,215,100,.14);
        color:var(--abc-text);
        border-radius:12px;
        padding:12px 12px;
        outline:none;
    }
    .abc-input:focus{ border-color:rgba(214,162,74,.45); }
    .abc-status{ margin-top:10px; color:rgba(255,255,255,.55); font-size:12px; min-height:16px; }

    .abc-btn{
        border-radius:12px;
        padding:10px 12px;
        border:1px solid rgba(255,215,100,.14);
        background:rgba(0,0,0,.35);
        color:var(--abc-text);
        cursor:pointer;
        white-space:nowrap;
    }
    .abc-btn:hover{ border-color:rgba(214,162,74,.40); }
    .abc-btn-ghost{ background:transparent; }
    .abc-btn-danger{ border-color:rgba(255,77,79,.45); background:rgba(255,77,79,.10); }
    .abc-btn-gold{
        background: linear-gradient(180deg, rgba(214,162,74,.95), rgba(185,137,63,.95));
        border-color: rgba(214,162,74,.55);
        color:#0b0c0f;
        font-weight:700;
    }
    .abc-btn-lg{ padding:12px 18px; border-radius:999px; min-width:260px; }

    .abc-control .abc-label{ font-size:12px; color:rgba(255,215,100,.85); letter-spacing:.10em; margin-bottom:8px; }
    .abc-help{ font-size:12px; color:rgba(255,255,255,.45); margin-top:8px; }

    .abc-seg{ display:flex; gap:10px; flex-wrap:wrap; }
    .abc-seg-row{ flex-wrap:nowrap; }
    .abc-seg-center{ justify-content:center; }
    .abc-seg-btn{
        padding:10px 12px;
        border-radius:12px;
        border:1px solid rgba(255,215,100,.14);
        background:rgba(0,0,0,.28);
        color:rgba(255,255,255,.85);
        cursor:pointer;
    }
    .abc-seg-btn.is-active{
        border-color:rgba(214,162,74,.55);
        background:rgba(214,162,74,.18);
        color:rgba(255,255,255,.95);
    }
    .abc-seg-btn-sm{
        padding:7px 10px;
        border-radius:10px;
        font-size:12px;
        letter-spacing:.02em;
    }

    .abc-difficulty-top{
        display:flex;
        justify-content:center;
        padding: 4px 0 12px;
        border-bottom:1px solid rgba(255,215,100,.08);
        margin-bottom:12px;
    }

    .abc-top-controls{ display:grid; grid-template-columns:1fr; gap:12px; margin-bottom:14px; }
    .abc-control-center{ text-align:center; }

    .abc-avatar-panel{
        flex:1;
        border-radius:18px;
        border:1px solid rgba(255,215,100,.10);
        background:rgba(0,0,0,.22);
        display:flex;
        align-items:center;
        justify-content:center;
        position:relative;
        min-height:420px;
        padding:18px;
    }

    .abc-stage-env, .abc-stage-prospect{
        position:absolute;
        top:12px;
        padding:10px 10px;
        border-radius:14px;
        background:rgba(0,0,0,.40);
        border:1px solid rgba(255,215,100,.14);
        z-index: 6;
        display:flex;
        flex-direction:column;
        gap:8px;
    }
    .abc-stage-env{ left:12px; }
    .abc-stage-prospect{ right:12px; align-items:flex-end; }

    .abc-stage-env-label{
        font-size:11px;
        letter-spacing:.12em;
        color:rgba(255,215,100,.88);
        text-transform:uppercase;
    }
    .abc-stage-prospect-name{
        font-size:12px;
        color:rgba(255,255,255,.70);
        line-height:1;
        margin-top:2px;
    }

    .abc-wave-wrap{
        width:min(520px,100%);
        padding:22px;
        border-radius:18px;
        border:1px solid rgba(255,215,100,.12);
        background:rgba(0,0,0,.28);
        text-align:center;
    }
    .abc-wave-title{ font-size:18px; font-weight:700; }
    .abc-wave-sub{ margin-top:4px; font-size:13px; color:rgba(255,255,255,.65); }
    .abc-wave-hint{ margin-top:14px; font-size:12px; color:rgba(255,255,255,.55); }

    .abc-wave-bars{
        margin:18px auto 0;
        height:110px;
        width:min(420px,100%);
        display:flex;
        align-items:flex-end;
        justify-content:center;
        gap:8px;
        padding:14px;
        border-radius:16px;
        border:1px solid rgba(255,255,255,.06);
        background: radial-gradient(600px 300px at 20% 10%, rgba(214,162,74,.12), transparent 60%),
                    rgba(255,255,255,.03);
        overflow:hidden;
    }
    .abc-wave-bars span{
        display:block;
        width:10px;
        height:14px;
        border-radius:999px;
        background:rgba(214,162,74,.55);
        transform-origin:bottom;
        opacity:.55;
        transform: scaleY(.8);
    }

    .abc-avatar-wrap{ display:flex; flex-direction:column; align-items:center; gap: 14px; width:100%; }
    .abc-avatar-mode-row{
        width: min(740px, 100%);
        border:1px solid rgba(255,215,100,.10);
        background: rgba(0,0,0,.18);
        border-radius: 16px;
        padding: 12px 12px;
    }
    .abc-avatar-mode-label{ font-size: 12px; color: rgba(255,215,100,.85); letter-spacing:.10em; margin-bottom:8px; }

    .abc-avatar-photo{ display:flex; flex-direction:column; align-items:center; gap: 10px; }
    .abc-avatar-caption{ font-size:12px; color: rgba(255,255,255,.60); }

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
        overflow:hidden;
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
        display:block;
    }
    .abc-avatar-missing{
        position:absolute;
        inset: 10px;
        border-radius: 999px;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        text-align:center;
        padding: 18px;
        color: rgba(255,255,255,.80);
        background: rgba(0,0,0,.62);
        border: 1px dashed rgba(255,215,100,.30);
        font-size: 12px;
        line-height: 1.35;
        z-index: 3;
    }
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
        z-index: 5;
    }

    .abc-center-actions{ display:flex; justify-content:center; margin-top:14px; }
    .abc-training-bottom{
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid rgba(255,215,100,.08);
        display:flex;
        flex-direction:column;
        align-items:center;
    }
    .abc-select{
        width:100%;
        background:rgba(0,0,0,.35);
        border:1px solid rgba(255,215,100,.14);
        color:rgba(255,255,255,.88);
        border-radius:12px;
        padding:10px 12px;
        outline:none;
    }

    @media (max-width:1100px){
        .abc-sp-grid{ grid-template-columns:1fr; }
        .abc-left{ min-height:520px; }
        .abc-right{ min-height:620px; }
        .abc-seg-row{ flex-wrap:wrap; }
        .abc-stage-env, .abc-stage-prospect{
            position: static;
            margin-bottom: 10px;
            width: 100%;
            max-width: 520px;
            align-items:center;
        }
        .abc-stage-prospect{ align-items:center; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const APP_BASE = "{{ rtrim(url('/'), '/') }}";

    const FALLBACK_AVATAR = APP_BASE + "/images/gideon/prospect_default.jpg";

    // ✅ FIXED PATHS (NO /avatars/ folder)
    const PROSPECTS = {
        p1: { name: 'Prospect 1', images: {
            neutral:   APP_BASE + "/images/gideon/avatar_01.jpg",
            friendly:  APP_BASE + "/images/gideon/avatar_01.jpg",
            skeptical: APP_BASE + "/images/gideon/avatar_01.jpg",
        }},
        p2: { name: 'Prospect 2', images: {
            neutral:   APP_BASE + "/images/gideon/avatar_02.jpg",
            friendly:  APP_BASE + "/images/gideon/avatar_02.jpg",
            skeptical: APP_BASE + "/images/gideon/avatar_02.jpg",
        }},
        p3: { name: 'Prospect 3', images: {
            neutral:   APP_BASE + "/images/gideon/avatar_03.jpg",
            friendly:  APP_BASE + "/images/gideon/avatar_03.jpg",
            skeptical: APP_BASE + "/images/gideon/avatar_03.jpg",
        }},
        p4: { name: 'Prospect 4', images: {
            neutral:   APP_BASE + "/images/gideon/avatar_04.jpg",
            friendly:  APP_BASE + "/images/gideon/avatar_04.jpg",
            skeptical: APP_BASE + "/images/gideon/avatar_04.jpg",
        }},
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

    const modeYouAgentBtn = document.getElementById('modeYouAgentBtn');
    const modeYouProspectBtn = document.getElementById('modeYouProspectBtn');

    const diffBeginnerBtn = document.getElementById('diffBeginnerBtn');
    const diffIntermediateBtn = document.getElementById('diffIntermediateBtn');
    const diffAdvancedBtn = document.getElementById('diffAdvancedBtn');

    const trainFullBtn = document.getElementById('trainFullBtn');
    const trainDiscoBtn = document.getElementById('trainDiscoBtn');
    const trainSegmentsBtn = document.getElementById('trainSegmentsBtn');
    const segmentWrap = document.getElementById('segmentWrap');
    const segmentSelect = document.getElementById('segmentSelect');

    const timerPill = document.getElementById('timerPill');

    const waveHint = document.getElementById('waveHint');

    const avatarImg = document.getElementById('avatarImg');
    const avatarCaption = document.getElementById('avatarCaption');

    const avatarMissing = document.getElementById('avatarMissing');
    const avatarMissingUrl = document.getElementById('avatarMissingUrl');

    const ttsToggleBtn = document.getElementById('ttsToggleBtn');

    const avatarModePhotoBtn = document.getElementById('avatarModePhotoBtn');
    const avatarModeLive2dBtn = document.getElementById('avatarModeLive2dBtn');
    const avatarMode3dBtn = document.getElementById('avatarMode3dBtn');

    const avatarPhotoWrap = document.getElementById('avatarPhotoWrap');
    const avatarLive2dWrap = document.getElementById('avatarLive2dWrap');
    const avatar3dWrap = document.getElementById('avatar3dWrap');

    const live2dCaption = document.getElementById('live2dCaption');
    const rpmCaption = document.getElementById('rpmCaption');

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

    let uiMode = 'prospect_simulation';
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

    function setStatus(msg){ statusEl.textContent = msg || ''; }
    function setActive(btn, group){ group.forEach(b=>b.classList.remove('is-active')); btn.classList.add('is-active'); }
    function randInt(min, max){ return Math.floor(Math.random()*(max-min+1))+min; }

    function fmtTime(s){
        const mm = String(Math.floor(s/60)).padStart(2,'0');
        const ss = String(s%60).padStart(2,'0');
        return `${mm}:${ss}`;
    }
    function startTimer(){
        stopTimer();
        seconds = 0;
        timerPill.textContent = `⏱ ${fmtTime(seconds)}`;
        timerInt = setInterval(()=> {
            seconds++;
            timerPill.textContent = `⏱ ${fmtTime(seconds)}`;
        }, 1000);
    }
    function stopTimer(){
        if (timerInt) clearInterval(timerInt);
        timerInt = null;
    }

    function mapDifficulty(d){
        if (d === 'beginner') return { persona:'soft_conflict_avoidant', face:'friendly' };
        if (d === 'advanced') return { persona:'skeptical_guarded', face:'skeptical' };
        return { persona:'neutral_balanced', face:'neutral' };
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
        test.onload = () => {
            avatarImg.src = finalUrl;
            showMissingAvatar(false);
        };
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
        setAvatarFace(mapped.face);
    }

    prospectBtns.forEach(btn=>{
        btn.addEventListener('click', ()=> setProspect(btn.dataset.prospect));
    });

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

    modeYouAgentBtn.addEventListener('click', ()=>{
        uiMode = 'prospect_simulation';
        setActive(modeYouAgentBtn, [modeYouAgentBtn, modeYouProspectBtn]);
    });
    modeYouProspectBtn.addEventListener('click', ()=>{
        uiMode = 'agent_simulation';
        setActive(modeYouProspectBtn, [modeYouAgentBtn, modeYouProspectBtn]);
    });

    function setDifficulty(d, btn){
        difficulty = d;
        const mapped = mapDifficulty(difficulty);
        personaKey = mapped.persona;
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
            setProspectCaption('Listening…');
        }
    });

    startBtn.addEventListener('click', ()=>{
        if (!scenarioCodeEl.value) {
            setStatus('No scenarios found. Seed at least one GideonScenario.');
            return;
        }
        sessionStarted = true;
        unlockInput();
        startTimer();
        setStatus('Session started. Say your first line.');
        setProspectCaption('Listening…');
    });

    resetBtn.addEventListener('click', (e)=>{ e.preventDefault(); resetSession(); });

    async function postAsk(message){
        if (!csrfToken) { setStatus('Missing CSRF token.'); return; }
        if (!scenarioCodeEl.value) { setStatus('No scenario available.'); return; }
        if (!sessionStarted) { setStatus('Click Start Sparring Session first.'); return; }

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
                    scenario_code: scenarioCodeEl.value,
                    mode: uiMode,
                    persona: personaKey,
                    session_id: currentSessionId,
                    message: message,
                    difficulty: difficulty,
                    environment: environment,
                    training_mode: trainingMode,
                    selected_stage: selectedSegment,
                    avatar_mode: avatarMode,
                    selected_avatar_id: selectedProspectId,
                }),
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            if (data.session?.id) currentSessionId = data.session.id;

            const thinkDelay = randInt(THINKING_MIN_MS, THINKING_MAX_MS);
            await new Promise(r => setTimeout(r, thinkDelay));

            removeTypingIndicator();

            if (data.opening_line) appendBubble('them', data.opening_line);
            if (data.gideon_reply?.content) appendBubble('them', data.gideon_reply.content);

            setProspectCaption('Listening…');
            setStatus('Session active.');
        } catch (err) {
            console.error(err);
            removeTypingIndicator();
            setProspectCaption('Listening…');
            setStatus('Error talking to Gideon. Check runtime logs + browser console.');
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
            if (!res.ok) throw new Error('HTTP ' + res.status);

            try { window.speechSynthesis.cancel(); } catch(e) {}

            stopTimer();
            setStatus('Session ended.');
            inputEl.disabled = true;
            sendBtn.disabled = true;
            setProspectCaption('Session ended.');
        } catch (err) {
            console.error(err);
            setStatus('Error ending session. Check runtime logs.');
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

    // Force initial avatar load
    setAvatarFace('neutral');

    if ('speechSynthesis' in window) {
        window.speechSynthesis.onvoiceschanged = () => {};
    }
});
</script>
@endsection
