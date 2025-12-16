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

            <button class="abc-btn" id="ttsToggleBtn" type="button" title="Turn voice on/off">
                🔊 Voice: ON
            </button>

            <button class="abc-btn abc-btn-danger" id="endSessionBtn" type="button">End Session</button>
        </div>
    </div>

    <div class="abc-sp-grid">

        {{-- LEFT: Controls + Stage --}}
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

            {{-- Top controls (UI Role Mode only) --}}
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

                {{-- PHONE mode: voice waves --}}
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

                {{-- IN PERSON mode: avatar (Photo / Live2D / RPM 3D) --}}
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

                    {{-- PHOTO AVATAR --}}
                    <div id="avatarPhotoWrap" class="abc-avatar-photo">
                        <div class="abc-avatar-ring" id="avatarRing">
                            {{-- Start with a guaranteed fallback file --}}
                            <img
                                src="{{ asset('images/gideon/prospect_default.jpg') }}"
                                alt="Prospect avatar"
                                class="abc-avatar-img"
                                id="avatarImg"
                                loading="eager"
                                decoding="async"
                            >
                            <div class="abc-avatar-missing" id="avatarMissing" style="display:none;">
                                Missing avatar file.<br>
                                Check <code>public/images/gideon/avatars/</code>
                            </div>
                            <div class="abc-avatar-blink" id="avatarBlink"></div>
                            <div class="abc-mouth" id="avatarMouth"></div>
                        </div>
                        <div class="abc-avatar-caption" id="avatarCaption">Listening…</div>
                    </div>

                    {{-- LIVE2D PLACEHOLDER --}}
                    <div id="avatarLive2dWrap" class="abc-avatar-live2d" style="display:none;">
                        <div class="abc-live2d-canvas-wrap" id="live2dWrap">
                            <canvas id="live2dCanvas" width="420" height="420"></canvas>
                            <div class="abc-live2d-overlay">
                                Live2D placeholder (ready to wire)
                            </div>
                        </div>
                        <div class="abc-avatar-caption" id="live2dCaption">Listening…</div>
                    </div>

                    {{-- 3D RPM (iframe embed) --}}
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

            {{-- Start + Training (BOTTOM / CENTERED) --}}
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

        {{-- RIGHT: Live conversation --}}
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

    /* PHONE WAVES */
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
        transition: transform 70ms linear, opacity 70ms linear;
    }
    .is-speaking .abc-wave-bars span{ opacity:.95; }
    .is-speaking .abc-wave-hint{ color: rgba(214,162,74,.9); }

    /* IN PERSON */
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
        animation: abcAvatarIdle 3.2s ease-in-out infinite;
        transform: translateZ(0);
        will-change: transform, filter;
        overflow:hidden;
    }
    @keyframes abcAvatarIdle{
        0%{ transform: translateY(0px) scale(1); }
        50%{ transform: translateY(-6px) scale(1.01); }
        100%{ transform: translateY(0px) scale(1); }
    }

    /* IMPORTANT: keep image visible and inside circle */
    .abc-avatar-img{
        width: 100%;
        height: 100%;
        border-radius: 999px;
        object-fit: cover;
        border: 1px solid rgba(255,255,255,.08);
        filter: saturate(1.05) contrast(1.05);
        display:block;
        background: rgba(0,0,0,.08);
        will-change: transform, filter;
        transform: translateY(0) rotate(0deg);
    }

    .abc-avatar-missing{
        position:absolute;
        inset: 10px;
        border-radius: 999px;
        display:flex;
        align-items:center;
        justify-content:center;
        text-align:center;
        padding: 18px;
        color: rgba(255,255,255,.75);
        background: rgba(0,0,0,.55);
        border: 1px dashed rgba(255,215,100,.30);
        font-size: 12px;
        line-height: 1.35;
        z-index: 2;
    }
    .abc-avatar-missing code{
        color: rgba(255,215,100,.92);
    }

    .abc-avatar-blink{ position:absolute; inset:0; opacity:0; pointer-events:none; z-index:3; }
    .blink-now .abc-avatar-blink{ opacity:1; animation: abcBlink 120ms ease-in-out 1; background: rgba(0,0,0,.55); }
    @keyframes abcBlink{ 0%{opacity:0;} 45%{opacity:1;} 100%{opacity:0;} }

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
        will-change: height, width, transform, opacity;
        z-index: 4;
    }

    .avatar-speaking .abc-avatar-ring{ box-shadow: 0 0 40px rgba(214,162,74,.20); border-color: rgba(214,162,74,.85); }
    .avatar-speaking .abc-mouth{ opacity:.95; background: rgba(214,162,74,.55); border-color: rgba(214,162,74,.75); }

    .react-skeptical .abc-avatar-img{ filter: saturate(0.96) contrast(1.12) brightness(0.96); }
    .react-friendly .abc-avatar-img{ filter: saturate(1.12) contrast(1.04) brightness(1.03); }
    .react-assertive .abc-avatar-img{ filter: saturate(1.05) contrast(1.14) brightness(0.98); }
    .react-thinking .abc-avatar-ring{ box-shadow: 0 0 28px rgba(214,162,74,.14); }
    .react-listening .abc-avatar-ring{ box-shadow: 0 0 18px rgba(214,162,74,.08); }

    .abc-avatar-live2d, .abc-avatar-3d{ width: min(740px, 100%); display:flex; flex-direction:column; align-items:center; gap:10px; }
    .abc-live2d-canvas-wrap{
        width: min(520px, 100%);
        aspect-ratio: 1 / 1;
        border-radius: 18px;
        border: 1px solid rgba(255,215,100,.12);
        background: rgba(0,0,0,.26);
        position: relative;
        overflow: hidden;
        display:flex;
        align-items:center;
        justify-content:center;
    }
    #live2dCanvas{ width: 100%; height: 100%; display:block; }
    .abc-live2d-overlay{
        position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
        font-size: 13px; color: rgba(255,255,255,.65);
        background: radial-gradient(600px 300px at 20% 10%, rgba(214,162,74,.12), transparent 60%);
        pointer-events:none; text-align:center; padding: 14px;
    }

    .abc-rpm-frame-wrap{
        width: min(720px, 100%);
        height: 420px;
        border-radius: 18px;
        border: 1px solid rgba(255,215,100,.12);
        background: rgba(0,0,0,.26);
        position: relative;
        overflow:hidden;
    }
    #rpmIframe{ width:100%; height:100%; border:0; display:block; background: rgba(0,0,0,.26); }
    .abc-rpm-overlay{
        position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
        text-align:center; padding: 18px; color: rgba(255,255,255,.65);
        background: radial-gradient(600px 300px at 20% 10%, rgba(214,162,74,.12), transparent 60%);
        pointer-events:none; font-size: 13px;
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
        .abc-rpm-frame-wrap{ height: 360px; }

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

    // Guaranteed fallback image (must exist)
    const FALLBACK_AVATAR = "{{ asset('images/gideon/prospect_default.jpg') }}";

    // === PROSPECT LIBRARY (Photo avatars) ===
    // REQUIRED location:
    // public/images/gideon/avatars/avatar_01.jpg ... avatar_04.jpg
    const PROSPECTS = {
        p1: { name: 'Prospect 1', images: {
            neutral:   "{{ asset('images/gideon/avatars/avatar_01.jpg') }}",
            friendly:  "{{ asset('images/gideon/avatars/avatar_01.jpg') }}",
            skeptical: "{{ asset('images/gideon/avatars/avatar_01.jpg') }}",
        }},
        p2: { name: 'Prospect 2', images: {
            neutral:   "{{ asset('images/gideon/avatars/avatar_02.jpg') }}",
            friendly:  "{{ asset('images/gideon/avatars/avatar_02.jpg') }}",
            skeptical: "{{ asset('images/gideon/avatars/avatar_02.jpg') }}",
        }},
        p3: { name: 'Prospect 3', images: {
            neutral:   "{{ asset('images/gideon/avatars/avatar_03.jpg') }}",
            friendly:  "{{ asset('images/gideon/avatars/avatar_03.jpg') }}",
            skeptical: "{{ asset('images/gideon/avatars/avatar_03.jpg') }}",
        }},
        p4: { name: 'Prospect 4', images: {
            neutral:   "{{ asset('images/gideon/avatars/avatar_04.jpg') }}",
            friendly:  "{{ asset('images/gideon/avatars/avatar_04.jpg') }}",
            skeptical: "{{ asset('images/gideon/avatars/avatar_04.jpg') }}",
        }},
    };

    const RPM_AVATAR_URL = "";
    const THINKING_MIN_MS = 350;
    const THINKING_MAX_MS = 900;
    const ENERGY = { idleTarget: 0.06, speakingFloor: 0.18, peakWord: 0.92 };

    // ====== DOM ======
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

    const stagePanel = document.getElementById('stagePanel');
    const waveHint = document.getElementById('waveHint');
    const waveBars = document.getElementById('waveBars');

    const avatarRing = document.getElementById('avatarRing');
    const avatarImg = document.getElementById('avatarImg');
    const avatarMouth = document.getElementById('avatarMouth');
    const avatarCaption = document.getElementById('avatarCaption');
    const avatarMissing = document.getElementById('avatarMissing');

    const ttsToggleBtn = document.getElementById('ttsToggleBtn');

    const avatarModePhotoBtn = document.getElementById('avatarModePhotoBtn');
    const avatarModeLive2dBtn = document.getElementById('avatarModeLive2dBtn');
    const avatarMode3dBtn = document.getElementById('avatarMode3dBtn');

    const avatarPhotoWrap = document.getElementById('avatarPhotoWrap');
    const avatarLive2dWrap = document.getElementById('avatarLive2dWrap');
    const avatar3dWrap = document.getElementById('avatar3dWrap');
    const live2dCaption = document.getElementById('live2dCaption');
    const rpmCaption = document.getElementById('rpmCaption');
    const rpmIframe = document.getElementById('rpmIframe');
    const rpmOverlay = document.getElementById('rpmOverlay');

    const prospectNameEl = document.getElementById('prospectName');
    const prospectBtns = [
        document.getElementById('prospectP1Btn'),
        document.getElementById('prospectP2Btn'),
        document.getElementById('prospectP3Btn'),
        document.getElementById('prospectP4Btn'),
    ].filter(Boolean);

    // ====== STATE ======
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
    let speakingLock = false;

    let rafId = null;
    let lastTs = 0;

    let speechEnergy = 0;
    let speechEnergyTarget = 0;
    let speechEnergyVel = 0;

    let speakUntilTs = 0;
    let nextBlinkAt = 0;

    const AVATAR_STATE = { IDLE:'idle', LISTENING:'listening', THINKING:'thinking', SPEAKING:'speaking', REACTING:'reacting' };
    let avatarState = AVATAR_STATE.IDLE;
    let reactUntilTs = 0;
    let reactionClass = '';

    // ====== HELPERS ======
    function nowMs(){ return (performance && performance.now) ? performance.now() : Date.now(); }
    function fmtTime(s){ const mm=String(Math.floor(s/60)).padStart(2,'0'); const ss=String(s%60).padStart(2,'0'); return `${mm}:${ss}`; }
    function startTimer(){ stopTimer(); seconds=0; timerPill.textContent=`⏱ ${fmtTime(seconds)}`; timerInt=setInterval(()=>{ seconds++; timerPill.textContent=`⏱ ${fmtTime(seconds)}`; },1000); }
    function stopTimer(){ if (timerInt) clearInterval(timerInt); timerInt=null; }

    function setStatus(msg){ statusEl.textContent = msg || ''; }
    function setActive(btn, group){ group.forEach(b=>b.classList.remove('is-active')); btn.classList.add('is-active'); }
    function randInt(min, max){ return Math.floor(Math.random()*(max-min+1))+min; }
    function clamp(v, min, max){ return Math.max(min, Math.min(max, v)); }

    function mapDifficulty(d){
        if (d === 'beginner') return { persona:'soft_conflict_avoidant', face:'friendly' };
        if (d === 'advanced') return { persona:'skeptical_guarded', face:'skeptical' };
        return { persona:'neutral_balanced', face:'neutral' };
    }

    function currentProspect(){
        return PROSPECTS[selectedProspectId] || PROSPECTS.p1;
    }

    function showMissingAvatar(show){
        if (!avatarMissing) return;
        avatarMissing.style.display = show ? 'flex' : 'none';
    }

    // Load image safely (so 404 doesn't silently fail)
    function setAvatarSrcSafe(url){
        if (!avatarImg) return;
        showMissingAvatar(false);

        // cache-bust to avoid stale
        const cacheBust = `cb=${Date.now()}`;
        const finalUrl = (url && url.includes('?')) ? `${url}&${cacheBust}` : `${url}?${cacheBust}`;

        const test = new Image();
        test.decoding = 'async';
        test.onload = () => {
            avatarImg.src = finalUrl;
            showMissingAvatar(false);
        };
        test.onerror = () => {
            // fallback to guaranteed default
            avatarImg.src = `${FALLBACK_AVATAR}?${cacheBust}`;
            showMissingAvatar(true);
            console.warn('[Sparring] Avatar image missing:', url);
        };
        test.src = finalUrl;
    }

    function setAvatarFace(faceKey){
        const p = currentProspect();
        const src = p?.images?.[faceKey] || p?.images?.neutral || FALLBACK_AVATAR;
        setAvatarSrcSafe(src);
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

    function setProspectCaption(text){
        if (avatarCaption) avatarCaption.textContent = text;
        if (live2dCaption) live2dCaption.textContent = text;
        if (rpmCaption) rpmCaption.textContent = text;
        if (waveHint) waveHint.textContent = text;
    }

    function clearReactionClasses(){
        document.body.classList.remove('react-skeptical','react-friendly','react-assertive','react-thinking','react-listening');
        if (reactionClass) document.body.classList.remove(reactionClass);
        reactionClass = '';
    }

    function setAvatarState(next){
        avatarState = next;
        clearReactionClasses();
        if (avatarState === AVATAR_STATE.THINKING) document.body.classList.add('react-thinking');
        if (avatarState === AVATAR_STATE.LISTENING) document.body.classList.add('react-listening');

        if (avatarState === AVATAR_STATE.SPEAKING) {
            if (stagePanel) stagePanel.classList.add('is-speaking');
            document.body.classList.add('avatar-speaking');
        } else {
            if (stagePanel) stagePanel.classList.remove('is-speaking');
            document.body.classList.remove('avatar-speaking');
        }
    }

    function applyReactionFromText(text){
        const t = (text || '').trim();
        if (!t) return;

        let r = '';
        if (difficulty === 'advanced' && (t.includes('?') || /why|how|really|sure/i.test(t))) r = 'react-skeptical';
        else if (t.includes('?')) r = 'react-skeptical';
        else if (t.includes('!')) r = 'react-assertive';
        else if (difficulty === 'beginner') r = 'react-friendly';

        if (!r) return;

        clearReactionClasses();
        document.body.classList.add(r);
        reactionClass = r;

        setAvatarState(AVATAR_STATE.REACTING);
        reactUntilTs = nowMs() + 650;
    }

    function setEnergyTarget(v){ speechEnergyTarget = clamp(v, 0, 1); }
    function pulseWord(){
        setEnergyTarget(Math.max(speechEnergyTarget, ENERGY.peakWord));
        setTimeout(()=>setEnergyTarget(Math.max(ENERGY.speakingFloor, ENERGY.idleTarget)), 90);
    }
    function smoothEnergy(dt){
        const k = 18;
        const d = 0.84;
        speechEnergyVel += (speechEnergyTarget - speechEnergy) * k * dt;
        speechEnergyVel *= Math.pow(d, dt * 60);
        speechEnergy += speechEnergyVel * dt;
        speechEnergy = clamp(speechEnergy, 0, 1);
    }

    function renderWaveBars(energy, t){
        if (!waveBars) return;
        const bars = waveBars.querySelectorAll('span');
        const n = bars.length || 0;
        if (!n) return;

        for (let i = 0; i < n; i++){
            const phase = (i / n) * Math.PI * 2;
            const wobble = Math.sin(t * 0.008 + phase) * 0.35 + Math.sin(t * 0.014 + phase * 1.7) * 0.22;
            const jitter = (Math.sin(t * 0.032 + i * 2.1) * 0.08);
            const base = 0.65 + wobble + jitter;
            const amp = 0.45 + energy * 3.4;
            const v = clamp(base * amp, 0.25, 4.0);

            bars[i].style.transform = `scaleY(${v.toFixed(3)})`;
            bars[i].style.opacity = (0.45 + energy * 0.55).toFixed(2);
        }
    }

    function renderMouthAndBob(energy, t){
        if (avatarMouth){
            const trem = (Math.sin(t * 0.028) + Math.sin(t * 0.041)) * 0.9;
            const open = clamp(energy * 16 + trem, 0, 22);
            const wide = clamp(62 + energy * 44 + trem * 2, 50, 96);

            avatarMouth.style.height = `${(8 + open).toFixed(1)}px`;
            avatarMouth.style.width = `${wide.toFixed(1)}px`;
            avatarMouth.style.transform = `translateX(-50%) translateY(${(-energy * 1.6).toFixed(2)}px)`;
        }

        if (avatarImg){
            const bob = (energy * 1.5) + (Math.sin(t * 0.006) * 0.35);
            const tilt = (Math.sin(t * 0.004) * 0.35) + (energy * 0.25);
            avatarImg.style.transform = `translateY(${(-bob).toFixed(2)}px) rotate(${tilt.toFixed(2)}deg)`;
        }
    }

    function scheduleNextBlink(){ nextBlinkAt = nowMs() + randInt(2600, 5200); }
    function doBlink(){
        if (!avatarRing) return;
        avatarRing.classList.add('blink-now');
        setTimeout(()=>avatarRing.classList.remove('blink-now'), 140);
        scheduleNextBlink();
    }

    function animationLoop(ts){
        if (!lastTs) lastTs = ts;
        const dt = clamp((ts - lastTs) / 1000, 0.001, 0.05);
        lastTs = ts;

        const now = nowMs();
        if (avatarState === AVATAR_STATE.REACTING && reactUntilTs && now > reactUntilTs) {
            clearReactionClasses();
            if (now < speakUntilTs || speakingLock) setAvatarState(AVATAR_STATE.SPEAKING);
            else setAvatarState(sessionStarted ? AVATAR_STATE.LISTENING : AVATAR_STATE.IDLE);
        }

        if (!speakingLock && now < speakUntilTs) {
            setAvatarState(AVATAR_STATE.SPEAKING);
            setEnergyTarget(Math.max(ENERGY.speakingFloor, speechEnergyTarget));
        }
        if (!speakingLock && now >= speakUntilTs && avatarState === AVATAR_STATE.SPEAKING) {
            setEnergyTarget(ENERGY.idleTarget);
            setAvatarState(sessionStarted ? AVATAR_STATE.LISTENING : AVATAR_STATE.IDLE);
        }

        if (avatarState === AVATAR_STATE.THINKING) setEnergyTarget(Math.max(speechEnergyTarget, 0.10));
        if (avatarState === AVATAR_STATE.LISTENING) setEnergyTarget(Math.max(speechEnergyTarget, ENERGY.idleTarget));
        if (avatarState === AVATAR_STATE.IDLE) setEnergyTarget(ENERGY.idleTarget);

        smoothEnergy(dt);
        renderWaveBars(speechEnergy, ts);
        renderMouthAndBob(speechEnergy, ts);

        if (now > nextBlinkAt && speechEnergy < 0.55) doBlink();
        rafId = requestAnimationFrame(animationLoop);
    }

    function startAnimLoop(){
        if (rafId) cancelAnimationFrame(rafId);
        lastTs = 0;
        scheduleNextBlink();
        rafId = requestAnimationFrame(animationLoop);
    }

    function speakProspect(text){
        const t = (text || '').trim();
        if (!t) return;

        setAvatarState(AVATAR_STATE.SPEAKING);
        setProspectCaption('Speaking…');

        const approxMs = Math.min(6500, 600 + t.length * 28);
        speakUntilTs = nowMs() + approxMs;
        setEnergyTarget(Math.max(ENERGY.speakingFloor, 0.28));

        if (!ttsEnabled || !('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
            const words = t.split(/\s+/).filter(Boolean);
            let i = 0;
            const tick = () => {
                if (nowMs() >= speakUntilTs) return;
                pulseWord();
                i++;
                if (i < words.length) setTimeout(tick, randInt(110, 190));
            };
            setTimeout(tick, 60);
            return;
        }

        try { window.speechSynthesis.cancel(); } catch(e) {}

        const u = new SpeechSynthesisUtterance(t);
        u.rate = 1.02;
        u.pitch = 0.95;
        u.volume = 1;

        const voices = window.speechSynthesis.getVoices?.() || [];
        const preferred = voices.find(v => /en/i.test(v.lang) && /male|daniel|alex|google us english/i.test(v.name))
                       || voices.find(v => /en/i.test(v.lang))
                       || voices[0];
        if (preferred) u.voice = preferred;

        speakingLock = true;

        u.onboundary = () => { pulseWord(); speakUntilTs = Math.max(speakUntilTs, nowMs() + 220); };
        u.onstart = () => { setAvatarState(AVATAR_STATE.SPEAKING); setEnergyTarget(Math.max(ENERGY.speakingFloor, 0.34)); };
        u.onend = () => {
            speakingLock = false;
            setEnergyTarget(ENERGY.idleTarget);
            setProspectCaption('Listening…');
            setAvatarState(sessionStarted ? AVATAR_STATE.LISTENING : AVATAR_STATE.IDLE);
        };
        u.onerror = () => {
            speakingLock = false;
            setEnergyTarget(ENERGY.idleTarget);
            setProspectCaption('Listening…');
            setAvatarState(sessionStarted ? AVATAR_STATE.LISTENING : AVATAR_STATE.IDLE);
        };

        window.speechSynthesis.speak(u);
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

        speakUntilTs = 0;
        speechEnergy = 0;
        speechEnergyTarget = ENERGY.idleTarget;
        speechEnergyVel = 0;

        clearReactionClasses();
        setAvatarState(AVATAR_STATE.IDLE);
        setProspectCaption('Waiting…');
        removeTypingIndicator();
    }

    function unlockInput(){
        inputEl.disabled = false;
        sendBtn.disabled = false;
        inputEl.focus();
    }

    // ====== Prospect Picker ======
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

    // ====== ENVIRONMENT ======
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

    // ====== ROLE MODE ======
    modeYouAgentBtn.addEventListener('click', ()=>{
        uiMode = 'prospect_simulation';
        setActive(modeYouAgentBtn, [modeYouAgentBtn, modeYouProspectBtn]);
    });
    modeYouProspectBtn.addEventListener('click', ()=>{
        uiMode = 'agent_simulation';
        setActive(modeYouProspectBtn, [modeYouAgentBtn, modeYouProspectBtn]);
    });

    // ====== DIFFICULTY ======
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

    // ====== TRAINING ======
    function setTraining(mode, btn){
        trainingMode = mode;
        setActive(btn, [trainFullBtn, trainDiscoBtn, trainSegmentsBtn]);
        segmentWrap.style.display = (trainingMode === 'segments') ? 'block' : 'none';
    }
    trainFullBtn.addEventListener('click', ()=>setTraining('full', trainFullBtn));
    trainDiscoBtn.addEventListener('click', ()=>setTraining('disco', trainDiscoBtn));
    trainSegmentsBtn.addEventListener('click', ()=>setTraining('segments', trainSegmentsBtn));
    segmentSelect?.addEventListener('change', (e)=>{ selectedSegment = e.target.value; });

    // ====== AVATAR MODE ======
    function setAvatarMode(mode, btn){
        avatarMode = mode;
        setActive(btn, [avatarModePhotoBtn, avatarModeLive2dBtn, avatarMode3dBtn]);

        avatarPhotoWrap.style.display = (avatarMode === 'photo') ? 'flex' : 'none';
        avatarLive2dWrap.style.display = (avatarMode === 'live2d') ? 'flex' : 'none';
        avatar3dWrap.style.display = (avatarMode === '3d') ? 'flex' : 'none';

        if (avatarMode === '3d') {
            if (RPM_AVATAR_URL && RPM_AVATAR_URL.trim() !== '') {
                rpmOverlay.style.display = 'none';
                rpmIframe.src = RPM_AVATAR_URL;
            } else {
                rpmOverlay.style.display = 'flex';
                rpmIframe.src = '';
            }
        }
    }
    avatarModePhotoBtn.addEventListener('click', ()=>setAvatarMode('photo', avatarModePhotoBtn));
    avatarModeLive2dBtn.addEventListener('click', ()=>setAvatarMode('live2d', avatarModeLive2dBtn));
    avatarMode3dBtn.addEventListener('click', ()=>setAvatarMode('3d', avatarMode3dBtn));

    // ====== VOICE TOGGLE ======
    ttsToggleBtn.addEventListener('click', ()=>{
        ttsEnabled = !ttsEnabled;
        ttsToggleBtn.textContent = ttsEnabled ? '🔊 Voice: ON' : '🔇 Voice: OFF';
        if (!ttsEnabled) {
            try { window.speechSynthesis.cancel(); } catch(e) {}
            speakingLock = false;
            speakUntilTs = 0;
            setEnergyTarget(ENERGY.idleTarget);
            setProspectCaption('Listening…');
            setAvatarState(sessionStarted ? AVATAR_STATE.LISTENING : AVATAR_STATE.IDLE);
        }
    });

    // ====== START ======
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
        setAvatarState(AVATAR_STATE.LISTENING);
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
        setAvatarState(AVATAR_STATE.THINKING);
        setEnergyTarget(0.12);

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

            if (data.opening_line) {
                appendBubble('them', data.opening_line);
                applyReactionFromText(data.opening_line);
                speakProspect(data.opening_line);
            }

            if (data.gideon_reply?.content) {
                appendBubble('them', data.gideon_reply.content);
                applyReactionFromText(data.gideon_reply.content);
                speakProspect(data.gideon_reply.content);
            } else {
                setProspectCaption('Listening…');
                setAvatarState(AVATAR_STATE.LISTENING);
                setEnergyTarget(ENERGY.idleTarget);
            }

            setStatus('Session active.');
        } catch (err) {
            console.error(err);
            removeTypingIndicator();
            speakingLock = false;
            speakUntilTs = 0;
            setEnergyTarget(ENERGY.idleTarget);
            setProspectCaption('Listening…');
            setAvatarState(sessionStarted ? AVATAR_STATE.LISTENING : AVATAR_STATE.IDLE);
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
            speakingLock = false;
            speakUntilTs = 0;

            stopTimer();
            setStatus('Session ended.');
            inputEl.disabled = true;
            sendBtn.disabled = true;
            setProspectCaption('Session ended.');
            setAvatarState(AVATAR_STATE.IDLE);
            setEnergyTarget(ENERGY.idleTarget);
        } catch (err) {
            console.error(err);
            setStatus('Error ending session. Check runtime logs.');
        } finally {
            isSending = false;
        }
    });

    // ====== INIT ======
    resetSession();
    setProspect('p1');
    setDifficulty('intermediate', diffIntermediateBtn);
    setTraining('full', trainFullBtn);
    setAvatarMode('photo', avatarModePhotoBtn);
    envPhoneBtn.click();

    // Force initial avatar set now (prevents blank state)
    const initialMapped = mapDifficulty(difficulty);
    setAvatarFace(initialMapped.face);

    startAnimLoop();

    if ('speechSynthesis' in window) {
        window.speechSynthesis.onvoiceschanged = () => {};
    }
});
</script>
@endsection
