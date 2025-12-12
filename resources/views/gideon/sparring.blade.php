@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-3">Gideon Sparring Partner</h1>
    <p class="text-muted mb-4">
        Choose a scenario, then type what you would say to the prospect.
        Gideon will push back like a real human so you can practice.
    </p>

    @php
        $firstScenario = ($scenarios ?? collect())->first();
        $firstDescription = $firstScenario?->description ?? 'Select a scenario to see its description.';
    @endphp

    {{-- Scenario + Persona + Mode selector --}}
    <div class="card mb-3">
        <div class="card-body row g-3 align-items-center">
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
            </div>

            <div class="col-md-3">
                <label for="personaSelect" class="form-label mb-1">Prospect persona</label>
                <select id="personaSelect" class="form-select">
                    <option value="soft_conflict_avoidant" selected>Soft / conflict-avoidant</option>
                    <option value="neutral_balanced">Neutral / balanced</option>
                    <option value="direct_analytical">Direct / analytical</option>
                </select>
                <div class="small text-muted mt-1">
                    Choose how Gideon should “feel” before you start.
                </div>
            </div>

            <div class="col-md-2">
                <label for="modeSelect" class="form-label mb-1">Mode</label>
                <select id="modeSelect" class="form-select">
                    <option value="prospect_simulation" selected>Prospect sim</option>
                    <option value="role_reversal">Role reversal</option>
                </select>
                <div class="small text-muted mt-1">
                    Swap who plays who.
                </div>
            </div>

            <div class="col-md-3 d-flex flex-column align-items-md-end align-items-start gap-2">
                <button id="resetSessionBtn" class="btn btn-outline-secondary btn-sm" type="button">
                    Reset Session
                </button>
                <button id="endSessionBtn" class="btn btn-outline-danger btn-sm" type="button">
                    End Session (Assessment)
                </button>
            </div>

            <div class="col-12">
                <label class="form-label mb-1 d-block">Scenario description</label>
                <div id="scenarioDescription" class="small text-muted">
                    {{ $firstDescription }}
                </div>
            </div>
        </div>
    </div>

    {{-- Transcript window --}}
    <div class="card mb-3">
        <div class="card-header">
            Conversation
        </div>
        <div id="sparringTranscript"
             class="card-body"
             style="min-height: 260px; max-height: 420px; overflow-y: auto; background-color:#111; color:#eee;">
            <div class="text-muted small">
                Select a scenario and send your first message to start a session.
            </div>
        </div>
    </div>

    {{-- Input box --}}
    <div class="card">
        <div class="card-body">
            <form id="sparringForm" class="d-flex gap-2">
                <input id="sparringInput"
                       type="text"
                       class="form-control"
                       placeholder="Type what you’d say to the prospect and press Enter..."
                       autocomplete="off" />

                <button id="sendBtn" class="btn btn-warning" type="submit">
                    Send
                </button>
            </form>

            <div class="mt-2 small text-muted" id="sparringStatus"></div>
        </div>
    </div>

    {{-- Session Assessment --}}
    <div class="card mt-4" id="assessmentCard" style="display:none;">
        <div class="card-header">
            Session Assessment
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Rapport:</strong> <span id="assRapport">–</span>
                </div>
                <div class="col-md-3">
                    <strong>Discovery:</strong> <span id="assDiscovery">–</span>
                </div>
                <div class="col-md-3">
                    <strong>Deal killers:</strong> <span id="assDealKillers">–</span>
                </div>
                <div class="col-md-3">
                    <strong>Closing clarity:</strong> <span id="assClosingClarity">–</span>
                </div>
            </div>

            <div class="mb-3">
                <h6>Strengths</h6>
                <p class="mb-0 small" id="assStrengths">–</p>
            </div>

            <div>
                <h6>Improvements</h6>
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

    var currentSessionId = null;
    var isSending = false;

    function appendMessage(sender, text) {
        if (!text) return;

        var wrapper = document.createElement('div');
        wrapper.className = 'mb-2 small';

        var label = document.createElement('strong');
        label.textContent = (sender === 'agent') ? 'You: ' : 'Gideon: ';
        label.style.color = (sender === 'agent') ? '#ffd54f' : '#64b5f6';

        var span = document.createElement('span');
        span.textContent = text;

        wrapper.appendChild(label);
        wrapper.appendChild(span);

        transcriptEl.appendChild(wrapper);
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

    function resetSession() {
        currentSessionId = null;
        transcriptEl.innerHTML = '<div class="text-muted small">Session reset. Send a new message to start again.</div>';
        clearAssessment();
        setStatus('');
        enableInput();
        if (inputEl) inputEl.value = '';
    }

    function updateScenarioDescription() {
        if (!scenarioSelectEl || !scenarioDescriptionEl) return;
        var opt = scenarioSelectEl.options[scenarioSelectEl.selectedIndex];
        var desc = opt ? (opt.getAttribute('data-description') || '') : '';
        scenarioDescriptionEl.textContent = desc || 'No description available for this scenario.';
    }

    if (scenarioSelectEl) {
        scenarioSelectEl.addEventListener('change', function () {
            updateScenarioDescription();
            resetSession();
        });
        updateScenarioDescription();
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
        var mode = modeSelectEl ? modeSelectEl.value : 'prospect_simulation';

        isSending = true;
        setStatus('Talking to Gideon...');
        appendMessage('agent', message);

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
                    mode: mode,
                    persona: persona,
                    session_id: currentSessionId,
                    message: message,
                }),
            });

            if (!response.ok) throw new Error('HTTP ' + response.status);

            var data = await response.json();

            if (data.session && data.session.id) currentSessionId = data.session.id;
            else if (data.session_id) currentSessionId = data.session_id;

            if (data.opening_line) appendMessage('gideon', data.opening_line);
            if (data.gideon_reply && data.gideon_reply.content) appendMessage('gideon', data.gideon_reply.content);

            setStatus('Session active. Keep going!');
        } catch (err) {
            console.error(err);
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
        } catch (err) {
            console.error(err);
            setStatus('Error ending session / generating assessment. Check Runtime Logs.');
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
