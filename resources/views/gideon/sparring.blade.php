@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-3">Gideon Sparring Partner</h1>
    <p class="text-muted mb-4">
        Choose a scenario, then type what you would say to the prospect.
        Gideon will push back like a real human so you can practice.
    </p>

    {{-- Scenario + Persona selector --}}
    <div class="card mb-3">
        <div class="card-body row g-3 align-items-center">
            <div class="col-md-4">
                <label for="scenarioSelect" class="form-label mb-1">Scenario</label>
                <select id="scenarioSelect" class="form-select">
                    @forelse($scenarios as $scenario)
                        <option value="{{ $scenario->code }}">
                            {{ $scenario->name }}
                            @if($scenario->product_type)
                                ({{ $scenario->product_type }})
                            @endif
                        </option>
                    @empty
                        <option value="">No scenarios seeded yet</option>
                    @endforelse
                </select>
            </div>

            <div class="col-md-4">
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

            <div class="col-md-4 d-flex flex-column align-items-md-end align-items-start gap-2">
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
                    @if($scenarios->count())
                        {{ $scenarios->first()->description }}
                    @else
                        Ask your admin to run the GideonCoreSeeder.
                    @endif
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

    {{-- Session Assessment (hidden until session is ended) --}}
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
                <p class="mb-0 small" id="assStrengths">
                    –
                </p>
            </div>

            <div>
                <h6>Improvements</h6>
                <p class="mb-0 small" id="assImprovements">
                    –
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Simple inline JS for now --}}
<script>
    (() => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        // DOM elements
        const transcriptEl            = document.getElementById('sparringTranscript');
        const inputEl                 = document.getElementById('sparringInput');
        const formEl                  = document.getElementById('sparringForm');
        const sendBtn                 = document.getElementById('sendBtn');
        const scenarioSelectEl        = document.getElementById('scenarioSelect');
        const personaSelectEl         = document.getElementById('personaSelect');
        const scenarioDescriptionEl   = document.getElementById('scenarioDescription');
        const statusEl                = document.getElementById('sparringStatus');
        const resetBtn                = document.getElementById('resetSessionBtn');
        const endBtn                  = document.getElementById('endSessionBtn');

        // Assessment elements
        const assessmentCard          = document.getElementById('assessmentCard');
        const assRapportEl            = document.getElementById('assRapport');
        const assDiscoveryEl          = document.getElementById('assDiscovery');
        const assDealKillersEl        = document.getElementById('assDealKillers');
        const assClosingClarityEl     = document.getElementById('assClosingClarity');
        const assStrengthsEl          = document.getElementById('assStrengths');
        const assImprovementsEl       = document.getElementById('assImprovements');

        // Session state in JS
        let currentSessionId = null;
        let isSending = false;

        function appendMessage(sender, text) {
            if (!text) return;

            const wrapper = document.createElement('div');
            wrapper.classList.add('mb-2', 'small');

            const label = document.createElement('strong');
            label.textContent = sender === 'agent' ? 'You: ' : 'Gideon: ';
            label.style.color = sender === 'agent' ? '#ffd54f' : '#64b5f6';

            const span = document.createElement('span');
            span.textContent = text;

            wrapper.appendChild(label);
            wrapper.appendChild(span);

            transcriptEl.appendChild(wrapper);
            transcriptEl.scrollTop = transcriptEl.scrollHeight;
        }

        function setStatus(msg) {
            if (!statusEl) return;
            statusEl.textContent = msg || '';
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
            assessmentCard.style.display   = 'none';
            assRapportEl.textContent       = '–';
            assDiscoveryEl.textContent     = '–';
            assDealKillersEl.textContent   = '–';
            assClosingClarityEl.textContent= '–';
            assStrengthsEl.textContent     = '–';
            assImprovementsEl.textContent  = '–';
        }

        function resetSession() {
            currentSessionId = null;
            transcriptEl.innerHTML =
                '<div class="text-muted small">Session reset. Send a new message to start again.</div>';
            clearAssessment();
            setStatus('');
            enableInput();
            if (inputEl) inputEl.value = '';
        }

        resetBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            resetSession();
        });

        // Optional: if you later store scenario descriptions in JS, update description on change.
        scenarioSelectEl?.addEventListener('change', () => {
            // For now we only change session; description stays as initial
            resetSession();
        });

        async function sendToGideon(message) {
            if (!csrfToken) {
                console.error('No CSRF token found');
                setStatus('Error: missing CSRF token.');
                return;
            }
            const scenarioCode = scenarioSelectEl?.value || null;
            if (!scenarioCode) {
                setStatus('Please choose a scenario first.');
                return;
            }

            const persona = personaSelectEl?.value || 'soft_conflict_avoidant';

            isSending = true;
            setStatus('Talking to Gideon...');
            appendMessage('agent', message);

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
                        mode: 'prospect_simulation',
                        persona: persona,
                        session_id: currentSessionId,
                        message: message,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('Gideon response', data);

                // Capture session id from response
                if (data.session && data.session.id) {
                    currentSessionId = data.session.id;
                } else if (data.session_id) {
                    currentSessionId = data.session_id;
                }

                // 1) opening line on first call
                if (data.opening_line) {
                    appendMessage('gideon', data.opening_line);
                }

                // 2) Gideon's reply for this turn
                if (data.gideon_reply && data.gideon_reply.content) {
                    appendMessage('gideon', data.gideon_reply.content);
                }

                setStatus('Session active. Keep going!');
            } catch (err) {
                console.error(err);
                setStatus('Error talking to Gideon. Check console.');
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
                const response = await fetch('/api/gideon/sparring/end', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        session_id: currentSessionId,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('Assessment response', data);

                if (data.assessment && data.assessment.scores) {
                    const scores = data.assessment.scores;

                    assRapportEl.textContent        = scores.rapport ?? '–';
                    assDiscoveryEl.textContent      = scores.discovery ?? '–';
                    assDealKillersEl.textContent    = scores.deal_killers ?? '–';
                    assClosingClarityEl.textContent = scores.closing_clarity ?? '–';
                }

                assStrengthsEl.textContent   = data.assessment?.strengths
                    ?? 'Placeholder assessment. Automated coaching logic to be implemented.';
                assImprovementsEl.textContent = data.assessment?.improvements
                    ?? 'Placeholder assessment. Automated coaching logic to be implemented.';

                assessmentCard.style.display = 'block';
                setStatus('Session ended. Review your assessment below.');
                disableInput();
            } catch (err) {
                console.error(err);
                setStatus('Error ending session / generating assessment.');
            } finally {
                isSending = false;
            }
        }

        endBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            if (isSending) return;
            endSession();
        });

        formEl?.addEventListener('submit', (e) => {
            e.preventDefault();
            if (isSending) return;

            const value = (inputEl?.value || '').trim();
            if (!value) return;

            inputEl.value = '';
            clearAssessment(); // if they start talking again, hide old assessment
            enableInput();
            sendToGideon(value);
        });
    })();
</script>
@endsection
