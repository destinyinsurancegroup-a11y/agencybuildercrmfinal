@extends('layouts.app')

@section('content')
<style>
    /* Quick ABC black + gold vibe (minimal, no full redesign yet) */
    .abc-panel { background:#0b0b0b; border:1px solid rgba(255,215,0,.18); color:#eee; }
    .abc-muted { color: rgba(255,255,255,.65); }
    .abc-gold { color:#ffd54f; }
    .abc-btn-gold { background:#ffd54f; border-color:#ffd54f; color:#111; font-weight:600; }
    .abc-btn-outline { border-color: rgba(255,215,0,.35); color:#ffd54f; }
    .abc-btn-outline:hover { background: rgba(255,215,0,.08); }
    .abc-chip { display:inline-flex; align-items:center; gap:.4rem; padding:.25rem .6rem; border-radius:999px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.18); color:#ffd54f; font-size:.85rem; }
    .abc-transcript { background:#0f0f10; border:1px solid rgba(255,215,0,.12); border-radius:.5rem; }
    .bubble { max-width: 85%; padding:.55rem .7rem; border-radius: .75rem; border:1px solid rgba(255,255,255,.08); }
    .bubble.agent { margin-left:auto; background: rgba(255,215,0,.08); border-color: rgba(255,215,0,.20); }
    .bubble.system { margin-right:auto; background: rgba(100,181,246,.08); border-color: rgba(100,181,246,.18); }
    .bubble small { display:block; margin-top:.25rem; color: rgba(255,255,255,.55); }
</style>

<div class="container py-4">

    {{-- Header --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="mb-1">Gideon Sparring Partner</h1>
            <div class="abc-muted">
                Practice a selling cycle. Sessions advance toward an end and produce coaching feedback.
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="abc-chip">
                <span>⏱</span> <span id="timerPill">00:00</span>
            </span>
            <button id="endSessionBtn" class="btn btn-outline-danger btn-sm" type="button">
                End Session
            </button>
        </div>
    </div>

    @php
        $firstScenario = ($scenarios ?? collect())->first();
        $firstDescription = $firstScenario?->description ?? 'Select a scenario to see its description.';
    @endphp

    {{-- Setup panel --}}
    <div class="card abc-panel mb-3">
        <div class="card-body row g-3 align-items-center">

            {{-- Scenario --}}
            <div class="col-md-4">
                <label for="scenarioSelect" class="form-label mb-1">Scenario</label>
                <select id="scenarioSelect" class="form-select">
                    @forelse(($scenarios ?? collect()) as $scenario)
                        <option
                            value="{{ $scenario->code }}"
                            data-description="{{ $scenario->description ?? '' }}"
                        >
                            {{ $scenario->name }}
                            @if($scenario->product_type)
                                ({{ $scenario->product_type }})
                            @endif
                        </option>
                    @empty
                        <option value="" data-description="">No scenarios seeded yet</option>
                    @endforelse
                </select>
                <div class="small abc-muted mt-1" id="scenarioDescription">{{ $firstDescription }}</div>
            </div>

            {{-- Training Mode (3 modes) --}}
            <div class="col-md-3">
                <label for="trainingModeSelect" class="form-label mb-1">Training Mode</label>
                <select id="trainingModeSelect" class="form-select">
                    <option value="stages">Stages (practice one stage)</option>
                    <option value="discovery_start" selected>Discovery-Start</option>
                    <option value="full_presentation">Full Presentation</option>
                </select>
                <div class="small abc-muted mt-1">
                    Controls where the session starts and how it ends.
                </div>
            </div>

            {{-- Stage (only when stages mode) --}}
            <div class="col-md-2">
                <label for="stageSelect" class="form-label mb-1">Stage</label>
                <select id="stageSelect" class="form-select">
                    <option value="intro">Intro</option>
                    <option value="discovery" selected>Discovery</option>
                    <option value="education">Education</option>
                    <option value="qualify">Qualify</option>
                    <option value="quote">Quote</option>
                    <option value="close">Close</option>
                </select>
                <div class="small abc-muted mt-1" id="stageHint">Used only in Stages Mode.</div>
            </div>

            {{-- Difficulty --}}
            <div class="col-md-3">
                <label for="difficultySelect" class="form-label mb-1">Difficulty</label>
                <select id="difficultySelect" class="form-select">
                    <option value="easy">Easy</option>
                    <option value="normal" selected>Normal</option>
                    <option value="hard">Hard</option>
                </select>
                <div class="small abc-muted mt-1">Affects how skeptical the prospect behaves.</div>
            </div>

            {{-- Persona + UI Mode --}}
            <div class="col-md-4">
                <label for="personaSelect" class="form-label mb-1">Prospect Persona</label>
                <select id="personaSelect" class="form-select">
                    <option value="soft_conflict_avoidant" selected>Soft / conflict-avoidant</option>
                    <option value="neutral_balanced">Neutral / balanced</option>
                    <option value="direct_analytical">Direct / analytical</option>
                </select>
            </div>

            <div class="col-md-3">
                <label for="modeSelect" class="form-label mb-1">UI Role Mode</label>
                <select id="modeSelect" class="form-select">
                    <option value="prospect_simulation" selected>Prospect sim (You=Agent)</option>
                    <option value="agent_simulation">Role reversal (You=Prospect)</option>
                </select>
                <div class="small abc-muted mt-1">This is not Training Mode.</div>
            </div>

            <div class="col-md-5 d-flex flex-column align-items-md-end align-items-start gap-2">
                <button id="resetSessionBtn" class="btn abc-btn-outline btn-sm" type="button">
                    Reset Session
                </button>
            </div>
        </div>
    </div>

    {{-- Transcript --}}
    <div class="abc-transcript mb-3 p-3" style="min-height: 280px; max-height: 520px; overflow-y: auto;" id="sparringTranscript">
        <div class="abc-muted small">
            Select a scenario and send your first message to start a session.
        </div>
    </div>

    {{-- Input --}}
    <div class="card abc-panel">
        <div class="card-body">
            <form id="sparringForm" class="d-flex gap-2">
                <input id="sparringInput"
                       type="text"
                       class="form-control"
                       placeholder="Type what you’d say and press Enter..."
                       autocomplete="off" />

                <button id="sendBtn" class="btn abc-btn-gold" type="submit">Send</button>
            </form>
            <div class="mt-2 small abc-muted" id="sparringStatus"></div>
        </div>
    </div>

    {{-- Assessment --}}
    <div class="card abc-panel mt-4" id="assessmentCard" style="display:none;">
        <div class="card-body">
            <h5 class="mb-3">Session Assessment</h5>

            <div class="row mb-3">
                <div class="col-md-3"><strong>Rapport:</strong> <span id="assRapport">–</span></div>
                <div class="col-md-3"><strong>Discovery:</strong> <span id="assDiscovery">–</span></div>
                <div class="col-md-3"><strong>Deal killers:</strong> <span id="assDealKillers">–</span></div>
                <div class="col-md-3"><strong>Closing clarity:</strong> <span id="assClosingClarity">–</span></div>
            </div>

            <div class="mb-3">
                <h6 class="abc-gold">Strengths</h6>
                <p class="mb-0 small" id="assStrengths">–</p>
            </div>

            <div>
                <h6 class="abc-gold">Improvements</h6>
                <p class="mb-0 small" id="assImprovements">–</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

    var transcriptEl          = document.getElementById('sparringTranscript');
    var inputEl               = document.getElementById('sparringInput');
    var formEl                = document.getElementById('sparringForm');
    var sendBtn               = document.getElementById('sendBtn');
    var scenarioSelectEl      = document.getElementById('scenarioSelect');
    var personaSelectEl       = document.getElementById('personaSelect');
    var modeSelectEl          = document.getElementById('modeSelect');

    var trainingModeEl        = document.getElementById('trainingModeSelect');
    var stageSelectEl         = document.getElementById('stageSelect');
    var stageHintEl           = document.getElementById('stageHint');
    var difficultyEl          = document.getElementById('difficultySelect');

    var scenarioDescriptionEl = document.getElementById('scenarioDescription');
    var statusEl              = document.getElementById('sparringStatus');
    var resetBtn              = document.getElementById('resetSessionBtn');
    var endBtn                = document.getElementById('endSessionBtn');

    var assessmentCard        = document.getElementById('assessmentCard');
    var assRapportEl          = document.getElementById('assRapport');
    var assDiscoveryEl        = document.getElementById('assDiscovery');
    var assDealKillersEl      = document.getElementById('assDealKillers');
    var assClosingClarityEl   = document.getElementById('assClosingClarity');
    var assStrengthsEl        = document.getElementById('assStrengths');
    var assImprovementsEl     = document.getElementById('assImprovements');

    var timerPillEl           = document.getElementById('timerPill');

    var currentSessionId = null;
    var isSending = false;
    var timerStart = null;
    var timerInt = null;

    function fmtTime(ms) {
        var s = Math.floor(ms / 1000);
        var m = Math.floor(s / 60);
        s = s % 60;
        return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    }

    function startTimer() {
        timerStart = Date.now();
        if (timerInt) clearInterval(timerInt);
        timerInt = setInterval(function(){
            if (!timerStart || !timerPillEl) return;
            timerPillEl.textContent = fmtTime(Date.now() - timerStart);
        }, 500);
    }

    function stopTimer() {
        if (timerInt) clearInterval(timerInt);
        timerInt = null;
        timerStart = null;
        if (timerPillEl) timerPillEl.textContent = '00:00';
    }

    function appendBubble(who, text) {
        if (!text) return;

        var wrap = document.createElement('div');
        wrap.className = 'd-flex mb-2';

        var bubble = document.createElement('div');
        bubble.className = 'bubble ' + ((who === 'agent') ? 'agent' : 'system');

        bubble.textContent = text;

        var ts = document.createElement('small');
        ts.textContent = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
        bubble.appendChild(ts);

        if (who === 'agent') wrap.classList.add('justify-content-end');
        else wrap.classList.add('justify-content-start');

        wrap.appendChild(bubble);
        transcriptEl.appendChild(wrap);
        transcriptEl.scrollTop = transcriptEl.scrollHeight;
    }

    function setStatus(msg) {
        if (statusEl) statusEl.textContent = msg || '';
    }

    function enableInput() {
        if (inputEl) inputEl.disabled = false;
        if (sendBtn) sendBtn.disabled = false;
    }

    function disableInput() {
        if (inputEl) inputEl.disabled = true;
        if (sendBtn) sendBtn.disabled = true;
    }

    function clearAssessment() {
        if (!assessmentCard) return;
        assessmentCard.style.display = 'none';
        assRapportEl.textContent = '–';
        assDiscoveryEl.textContent = '–';
        assDealKillersEl.textContent = '–';
        assClosingClarityEl.textContent = '–';
        assStrengthsEl.textContent = '–';
        assImprovementsEl.textContent = '–';
    }

    function updateScenarioDescription() {
        if (!scenarioSelectEl || !scenarioDescriptionEl) return;
        var opt = scenarioSelectEl.options[scenarioSelectEl.selectedIndex];
        var desc = opt ? (opt.getAttribute('data-description') || '') : '';
        scenarioDescriptionEl.textContent = desc || 'No description available for this scenario.';
    }

    function updateStageEnabled() {
        var tm = trainingModeEl ? trainingModeEl.value : 'discovery_start';
        var isStages = (tm === 'stages');
        if (stageSelectEl) stageSelectEl.disabled = !isStages;
        if (stageHintEl) stageHintEl.textContent = isStages ? 'Practice only this stage.' : 'Used only in Stages Mode.';
    }

    function resetSession() {
        currentSessionId = null;
        transcriptEl.innerHTML = '<div class="abc-muted small">Session reset. Send a new message to start again.</div>';
        clearAssessment();
        setStatus('');
        enableInput();
        if (inputEl) inputEl.value = '';
        stopTimer();
    }

    if (scenarioSelectEl) {
        scenarioSelectEl.addEventListener('change', function () {
            updateScenarioDescription();
            resetSession();
        });
        updateScenarioDescription();
    }

    if (trainingModeEl) {
        trainingModeEl.addEventListener('change', function () {
            updateStageEnabled();
            resetSession();
        });
        updateStageEnabled();
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function (e) {
            e.preventDefault();
            resetSession();
        });
    }

    async function sendToGideon(message) {
        if (!csrfToken) {
            setStatus('Error: missing CSRF token.');
            return;
        }

        var scenarioCode = scenarioSelectEl ? scenarioSelectEl.value : null;
        if (!scenarioCode) {
            setStatus('Please choose a scenario first.');
            return;
        }

        var persona = personaSelectEl ? personaSelectEl.value : 'soft_conflict_avoidant';
        var uiMode = modeSelectEl ? modeSelectEl.value : 'prospect_simulation';

        var trainingMode = trainingModeEl ? trainingModeEl.value : 'discovery_start';
        var selectedStage = stageSelectEl ? stageSelectEl.value : 'discovery';
        var difficulty = difficultyEl ? difficultyEl.value : 'normal';

        isSending = true;
        setStatus('Talking to Gideon...');
        appendBubble('agent', message);

        try {
            var response = await fetch('/api/gideon/sparring/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    scenario_code: scenarioCode,
                    mode: uiMode,
                    persona: persona,
                    session_id: currentSessionId,
                    message: message,

                    // ✅ NEW: training controls (safe to ignore until engine uses them)
                    training_mode: trainingMode,
                    selected_stage: selectedStage,
                    difficulty: difficulty,
                }),
            });

            if (!response.ok) throw new Error('HTTP ' + response.status);

            var data = await response.json();

            if (data.session && data.session.id) currentSessionId = data.session.id;
            else if (data.session_id) currentSessionId = data.session_id;

            if (!timerStart) startTimer();

            if (data.opening_line) appendBubble('system', data.opening_line);
            if (data.gideon_reply && data.gideon_reply.content) appendBubble('system', data.gideon_reply.content);

            setStatus('Session active. Keep going!');
        } catch (err) {
            console.error(err);
            setStatus('Error talking to Gideon. Check runtime logs + browser console.');
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

        isSending = true;
        setStatus('Ending session and generating assessment...');

        try {
            var response = await fetch('/api/gideon/sparring/end', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ session_id: currentSessionId }),
            });

            if (!response.ok) throw new Error('HTTP ' + response.status);

            var data = await response.json();

            if (data.assessment && data.assessment.scores) {
                var scores = data.assessment.scores;
                assRapportEl.textContent = (scores.rapport != null) ? scores.rapport : '–';
                assDiscoveryEl.textContent = (scores.discovery != null) ? scores.discovery : '–';
                assDealKillersEl.textContent = (scores.deal_killers != null) ? scores.deal_killers : '–';
                assClosingClarityEl.textContent = (scores.closing_clarity != null) ? scores.closing_clarity : '–';
            }

            assStrengthsEl.textContent = (data.assessment && data.assessment.strengths) ? data.assessment.strengths : '–';
            assImprovementsEl.textContent = (data.assessment && data.assessment.improvements) ? data.assessment.improvements : '–';

            assessmentCard.style.display = 'block';
            setStatus('Session ended. Review your assessment below.');
            disableInput();
            stopTimer();
        } catch (err) {
            console.error(err);
            setStatus('Error ending session / generating assessment. Check runtime logs.');
        } finally {
            isSending = false;
        }
    }

    if (endBtn) {
        endBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (isSending) return;
            endSession();
        });
    }

    if (formEl) {
        formEl.addEventListener('submit', function (e) {
            e.preventDefault();
            if (isSending) return;

            var value = inputEl ? (inputEl.value || '').trim() : '';
            if (!value) return;

            inputEl.value = '';
            clearAssessment();
            enableInput();
            sendToGideon(value);
        });
    }
});
</script>
@endsection
