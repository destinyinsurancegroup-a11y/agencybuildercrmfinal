@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        {{-- Left: (placeholder) Coaching HUD later --}}
        <div class="col-lg-3 mb-3">
            <div class="card" style="background:#0b0b0b; color:#eee; border:1px solid #222;">
                <div class="card-body">
                    <div class="text-uppercase small" style="color:#bfa24a; letter-spacing:.08em;">Live Coaching</div>
                    <div class="mt-3 small text-muted">
                        (Coming next) Rapport / Clarity / Resistance / Momentum + Coaching Whisper.
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Main sparring experience --}}
        <div class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h2 class="mb-0" style="color:#fff;">Sparring Partner</h2>
                    <div class="text-muted">Live conversation training with a human-like prospect.</div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    {{-- Timer pill (static for now; you can wire later) --}}
                    <div class="px-3 py-2 rounded-pill"
                         style="background:#111; border:1px solid #222; color:#ddd; font-size:.9rem;">
                        ⏱ <span id="sessionTimer">00:00</span>
                    </div>

                    <button id="endSessionBtn" class="btn btn-outline-danger btn-sm" type="button">
                        End Session
                    </button>
                </div>
            </div>

            {{-- Top controls row --}}
            <div class="card mb-3" style="background:#0b0b0b; color:#eee; border:1px solid #222;">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label mb-1" style="color:#bfa24a;">Scenario</label>
                            <select id="scenarioSelect" class="form-select" style="background:#111; color:#eee; border:1px solid #222;">
                                @forelse(($scenarios ?? collect()) as $scenario)
                                    <option
                                        value="{{ $scenario->code }}"
                                        data-description="{{ $scenario->description ?? '' }}"
                                    >
                                        {{ $scenario->name }}@if($scenario->product_type) ({{ $scenario->product_type }})@endif
                                    </option>
                                @empty
                                    <option value="" data-description="">No scenarios seeded yet</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-1" style="color:#bfa24a;">Prospect persona</label>
                            <select id="personaSelect" class="form-select" style="background:#111; color:#eee; border:1px solid #222;">
                                <option value="soft_conflict_avoidant" selected>Soft / conflict-avoidant</option>
                                <option value="neutral_balanced">Neutral / balanced</option>
                                <option value="direct_analytical">Direct / analytical</option>
                                <option value="skeptical_guarded">Skeptical / guarded</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1" style="color:#bfa24a;">Mode</label>
                            <select id="modeSelect" class="form-select" style="background:#111; color:#eee; border:1px solid #222;">
                                <option value="prospect_simulation" selected>You = Agent</option>
                                <option value="agent_simulation">Role reversal</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-1" style="color:#bfa24a;">Environment</label>
                            <div class="d-flex gap-2">
                                <button id="envPhone" type="button" class="btn btn-sm w-50"
                                        style="background:#111; color:#eee; border:1px solid #222;">
                                    📞 Phone
                                </button>
                                <button id="envInPerson" type="button" class="btn btn-sm w-50"
                                        style="background:#bfa24a; color:#000; border:1px solid #bfa24a;">
                                    In Person
                                </button>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="small text-muted" id="scenarioDescription"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Avatar + Subtitle Panel --}}
            <div class="card mb-3" style="background:#0b0b0b; color:#eee; border:1px solid #222;">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="text-uppercase small" style="color:#bfa24a; letter-spacing:.08em;">
                                Prospect
                            </div>
                            <span id="expressionLabel"
                                  class="px-2 py-1 rounded-pill"
                                  style="background:#111; border:1px solid #222; color:#ddd; font-size:.75rem;">
                                Neutral
                            </span>
                        </div>

                        <div id="speakingStatus" class="small text-muted"></div>
                    </div>

                    <div id="avatarStage"
                         class="d-flex align-items-center justify-content-center"
                         style="height:320px; background:#070707; border:1px solid #222; border-radius:12px; position:relative; overflow:hidden;">

                        {{-- Phone image (hidden by default) --}}
                        <div id="phoneVisual" style="display:none; text-align:center;">
                            <div style="font-size:4rem;">📞</div>
                            <div class="text-muted small">Phone call mode</div>
                        </div>

                        {{-- In-person avatar (default) --}}
                        <div id="avatarVisual" style="text-align:center;">
                            {{-- Replace this with your real avatar image/video later --}}
                            <div style="
                                width:220px; height:220px; border-radius:50%;
                                border:3px solid #bfa24a;
                                background:#111;
                                display:flex; align-items:center; justify-content:center;
                                box-shadow:0 0 30px rgba(191,162,74,.15);
                                margin:0 auto;">
                                <span id="avatarStateEmoji" style="font-size:3rem;">🙂</span>
                            </div>
                            <div class="text-muted small mt-2">In-person avatar</div>
                        </div>

                        {{-- Subtitle overlay --}}
                        <div id="subtitleBox"
                             style="
                                position:absolute; left:50%; bottom:18px; transform:translateX(-50%);
                                width:min(720px, 92%);
                                background:rgba(0,0,0,.65);
                                border:1px solid rgba(191,162,74,.35);
                                border-radius:12px;
                                padding:12px 14px;
                                font-size:1.05rem;
                                line-height:1.35;
                                display:none;">
                            <div id="subtitleText" style="color:#fff;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Input Row --}}
            <div class="card" style="background:#0b0b0b; color:#eee; border:1px solid #222;">
                <div class="card-body">
                    <form id="sparringForm" class="d-flex gap-2">
                        <input id="sparringInput"
                               type="text"
                               class="form-control"
                               placeholder="Say your next line to the prospect..."
                               autocomplete="off"
                               style="background:#111; color:#eee; border:1px solid #222;" />

                        <button id="sendBtn" class="btn" type="submit"
                                style="background:#bfa24a; color:#000; border:1px solid #bfa24a;">
                            Send
                        </button>
                    </form>

                    <div class="mt-2 small text-muted" id="sparringStatus"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

    const scenarioSelectEl = document.getElementById('scenarioSelect');
    const personaSelectEl  = document.getElementById('personaSelect');
    const modeSelectEl     = document.getElementById('modeSelect');
    const descEl           = document.getElementById('scenarioDescription');

    const formEl  = document.getElementById('sparringForm');
    const inputEl = document.getElementById('sparringInput');
    const sendBtn = document.getElementById('sendBtn');
    const endBtn  = document.getElementById('endSessionBtn');
    const statusEl= document.getElementById('sparringStatus');

    const envPhoneBtn   = document.getElementById('envPhone');
    const envInPersonBtn= document.getElementById('envInPerson');
    const phoneVisual   = document.getElementById('phoneVisual');
    const avatarVisual  = document.getElementById('avatarVisual');

    const subtitleBox   = document.getElementById('subtitleBox');
    const subtitleText  = document.getElementById('subtitleText');
    const speakingStatus= document.getElementById('speakingStatus');
    const avatarStateEmoji = document.getElementById('avatarStateEmoji');

    let currentSessionId = null;
    let isSending = false;

    // Environment: "in_person" default
    let environment = 'in_person';

    // Speech lock (No Interrupt)
    let isSpeaking = false;

    function setStatus(msg) {
        if (statusEl) statusEl.textContent = msg || '';
    }

    function setSendEnabled(enabled) {
        if (sendBtn) sendBtn.disabled = !enabled;
        // You can allow typing while speaking if you want.
        // For strict no-interrupt, disable input too:
        if (inputEl) inputEl.disabled = !enabled;
    }

    function showSubtitle(text) {
        if (!text) return;
        subtitleText.textContent = text;
        subtitleBox.style.display = 'block';
    }

    function hideSubtitle() {
        subtitleBox.style.display = 'none';
        subtitleText.textContent = '';
    }

    function setAvatarState(state) {
        // MVP visuals; replace with real avatar state hooks later
        if (state === 'idle') {
            avatarStateEmoji.textContent = '🙂';
            speakingStatus.textContent = '';
        } else if (state === 'listening') {
            avatarStateEmoji.textContent = '👂';
            speakingStatus.textContent = 'Listening...';
        } else if (state === 'thinking') {
            avatarStateEmoji.textContent = '🤔';
            speakingStatus.textContent = 'Thinking...';
        } else if (state === 'speaking') {
            avatarStateEmoji.textContent = '🗣️';
            speakingStatus.textContent = 'Speaking...';
        }
    }

    function updateScenarioDescription() {
        if (!scenarioSelectEl || !descEl) return;
        const opt = scenarioSelectEl.options[scenarioSelectEl.selectedIndex];
        const d = opt ? (opt.getAttribute('data-description') || '') : '';
        descEl.textContent = d || 'No description available for this scenario.';
    }

    if (scenarioSelectEl) {
        scenarioSelectEl.addEventListener('change', function () {
            updateScenarioDescription();
            // reset session
            currentSessionId = null;
            hideSubtitle();
            setStatus('Session reset. Say your first line to start.');
        });
        updateScenarioDescription();
    }

    function setEnvironment(env) {
        environment = env;
        if (env === 'phone') {
            phoneVisual.style.display = 'block';
            avatarVisual.style.display = 'none';

            envPhoneBtn.style.background = '#bfa24a';
            envPhoneBtn.style.color = '#000';
            envPhoneBtn.style.borderColor = '#bfa24a';

            envInPersonBtn.style.background = '#111';
            envInPersonBtn.style.color = '#eee';
            envInPersonBtn.style.borderColor = '#222';
        } else {
            phoneVisual.style.display = 'none';
            avatarVisual.style.display = 'block';

            envInPersonBtn.style.background = '#bfa24a';
            envInPersonBtn.style.color = '#000';
            envInPersonBtn.style.borderColor = '#bfa24a';

            envPhoneBtn.style.background = '#111';
            envPhoneBtn.style.color = '#eee';
            envPhoneBtn.style.borderColor = '#222';
        }
    }

    envPhoneBtn?.addEventListener('click', () => setEnvironment('phone'));
    envInPersonBtn?.addEventListener('click', () => setEnvironment('in_person'));
    setEnvironment('in_person');

    // --- Speech (Browser TTS MVP) ---
    function speakText(text) {
        return new Promise((resolve) => {
            // If browser doesn't support TTS, just show subtitles.
            if (!('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
                resolve();
                return;
            }

            // Cancel anything queued (safety)
            try { window.speechSynthesis.cancel(); } catch (e) {}

            const utter = new SpeechSynthesisUtterance(text);

            // Optional: tune voice slightly; keep simple for MVP
            utter.rate = 1.0;
            utter.pitch = 1.0;
            utter.volume = 1.0;

            utter.onstart = () => {
                isSpeaking = true;
                setSendEnabled(false);        // ✅ No interrupt
                setAvatarState('speaking');
            };

            utter.onend = () => {
                isSpeaking = false;
                setSendEnabled(true);
                setAvatarState('idle');
                // Let subtitle linger briefly
                setTimeout(() => {
                    // keep it visible a bit; or comment out if you want persistent
                    // hideSubtitle();
                }, 800);
                resolve();
            };

            utter.onerror = () => {
                isSpeaking = false;
                setSendEnabled(true);
                setAvatarState('idle');
                resolve();
            };

            window.speechSynthesis.speak(utter);
        });
    }

    async function sendToGideon(message) {
        if (!csrfToken) {
            setStatus('Error: missing CSRF token.');
            return;
        }
        if (isSpeaking) {
            // strict no-interrupt safety
            return;
        }

        const scenarioCode = scenarioSelectEl ? scenarioSelectEl.value : null;
        if (!scenarioCode) {
            setStatus('Please choose a scenario first.');
            return;
        }

        const persona = personaSelectEl ? personaSelectEl.value : 'soft_conflict_avoidant';
        const mode = modeSelectEl ? modeSelectEl.value : 'prospect_simulation';

        isSending = true;
        setStatus('Sending...');
        setAvatarState('listening');

        try {
            const response = await fetch('/api/gideon/sparring/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    scenario_code: scenarioCode,
                    mode: mode,
                    persona: persona,
                    session_id: currentSessionId,
                    message: message,
                    // environment is front-end only for now; we can store later if you want:
                    // environment: environment,
                }),
            });

            if (!response.ok) {
                const text = await response.text();
                console.error('Ask error:', response.status, text);
                setAvatarState('idle');
                setStatus('Error talking to Gideon (check logs).');
                return;
            }

            const data = await response.json();

            if (data.session && data.session.id) currentSessionId = data.session.id;
            else if (data.session_id) currentSessionId = data.session_id;

            const reply = (data.gideon_reply && data.gideon_reply.content) ? data.gideon_reply.content : null;

            if (!reply) {
                setAvatarState('idle');
                setStatus('No reply received.');
                return;
            }

            // Show subtitles and speak EVERY response
            showSubtitle(reply);
            setAvatarState('thinking');
            setStatus('Prospect responding...');
            await speakText(reply);

            setStatus('Your turn.');
        } catch (err) {
            console.error(err);
            setAvatarState('idle');
            setStatus('Error talking to Gideon. Check Runtime Logs + browser console.');
        } finally {
            isSending = false;
        }
    }

    async function endSession() {
        if (!currentSessionId) {
            setStatus('No active session to end.');
            return;
        }
        if (!csrfToken) {
            setStatus('Error: missing CSRF token.');
            return;
        }
        if (isSpeaking) return;

        setStatus('Ending session...');
        setAvatarState('thinking');

        try {
            const response = await fetch('/api/gideon/sparring/end', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ session_id: currentSessionId }),
            });

            if (!response.ok) {
                const text = await response.text();
                console.error('End error:', response.status, text);
                setAvatarState('idle');
                setStatus('Error ending session (check logs).');
                return;
            }

            currentSessionId = null;
            setAvatarState('idle');
            setStatus('Session ended.');
        } catch (err) {
            console.error(err);
            setAvatarState('idle');
            setStatus('Error ending session. Check Runtime Logs.');
        }
    }

    endBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        if (isSending || isSpeaking) return;
        endSession();
    });

    formEl?.addEventListener('submit', function (e) {
        e.preventDefault();
        if (isSending || isSpeaking) return;

        const value = inputEl ? (inputEl.value || '').trim() : '';
        if (!value) return;

        inputEl.value = '';
        sendToGideon(value);
    });

    // initial UI state
    setSendEnabled(true);
    setAvatarState('idle');
    setStatus('Say your first line to start.');
});
</script>
@endsection
