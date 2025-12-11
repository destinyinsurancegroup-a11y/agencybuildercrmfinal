@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-3">Gideon Sparring Partner</h1>
    <p class="text-muted mb-4">
        Choose a scenario, then type what you would say to the prospect.
        Gideon will push back like a real human so you can practice.
    </p>

    {{-- Scenario selector --}}
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <div>
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

            <div class="flex-grow-1">
                <label class="form-label mb-1 d-block">Scenario description</label>
                <div id="scenarioDescription" class="small text-muted">
                    @if($scenarios->count())
                        {{ $scenarios->first()->description }}
                    @else
                        Ask your admin to run the GideonCoreSeeder.
                    @endif
                </div>
            </div>

            <div class="d-flex flex-column gap-2">
                <button id="resetSessionBtn" class="btn btn-outline-secondary btn-sm" type="button">
                    Reset Session
                </button>
                <button id="endSessionBtn" class="btn btn-outline-danger btn-sm" type="button">
                    End Session
                </button>
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

                <button class="btn btn-warning" type="submit">
                    Send
                </button>
            </form>

            <div class="mt-2 small text-muted" id="sparringStatus"></div>
        </div>
    </div>

    {{-- Assessment card (hidden until session is ended) --}}
    <div id="assessmentCard" class="card mt-3" style="display: none;">
        <div class="card-header">
            Session Assessment
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6 col-md-3">
                    <strong>Rapport:</strong>
                    <div id="scoreRapport">–</div>
                </div>
                <div class="col-6 col-md-3">
                    <strong>Discovery:</strong>
                    <div id="scoreDiscovery">–</div>
                </div>
                <div class="col-6 col-md-3">
                    <strong>Deal killers:</strong>
                    <div id="scoreDealKillers">–</div>
                </div>
                <div class="col-6 col-md-3">
                    <strong>Closing clarity:</strong>
                    <div id="scoreClosingClarity">–</div>
                </div>
            </div>

            <h5>Strengths</h5>
            <p id="assessmentStrengths" class="mb-3">–</p>

            <h5>Improvements</h5>
            <p id="assessmentImprovements" class="mb-0">–</p>
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
        const transcriptEl = document.getElementById('sparringTranscript');
        const inputEl = document.getElementById('sparringInput');
        const formEl = document.getElementById('sparringForm');
        const scenarioSelectEl = document.getElementById('scenarioSelect');
        const scenarioDescriptionEl = document.getElementById('scenarioDescription');
        const statusEl = document.getElementById('sparringStatus');
        const resetBtn = document.getElementById('resetSessionBtn');
        const endBtn = document.getElementById('endSessionBtn');

        const assessmentCard = document.getElementById('assessmentCard');
        const scoreRapportEl = document.getElementById('scoreRapport');
        const scoreDiscoveryEl = document.getElementById('scoreDiscovery');
        const scoreDealKillersEl = document.getElementById('scoreDealKillers');
        const scoreClosingClarityEl = document.getElementById('scoreClosingClarity');
        const strengthsEl = document.getElementById('assessmentStrengths');
        const improvementsEl = document.getElementById('assessmentImprovements');

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

        function hideAssessment() {
            if (!assessmentCard) return;
            assessmentCard.style.display = 'none';

            scoreRapportEl.textContent = '–';
            scoreDiscoveryEl.textContent = '–';
            scoreDealKillersEl.textContent = '–';
            scoreClosingClarityEl.textContent = '–';
            strengthsEl.textContent = '–';
            improvementsEl.textContent = '–';
        }

        function resetSession() {
            currentSessionId = null;
            transcriptEl.innerHTML =
                '<div class="text-muted small">Session reset. Send a new message to start again.</div>';
            setStatus('');
            hideAssessment();
        }

        resetBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            resetSession();
        });

        function showAssessment(assessment) {
            if (!assessmentCard) return;

            const scores = assessment.scores || {};

            scoreRapportEl.textContent = scores.rapport ?? '–';
            scoreDiscoveryEl.textContent = scores.discovery ?? '–';
            scoreDealKillersEl.textContent = scores.deal_killers ?? '–';
            scoreClosingClarityEl.textContent = scores.closing_clarity ?? '–';

            strengthsEl.textContent = assessment.strengths || '–';
            improvementsEl.textContent = assessment.improvements || '–';

            assessmentCard.style.display = 'block';
        }

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

                // 2) generic v1 structure: { agent_message: {...}, gideon_reply: {...} }
                if (data.agent_message && data.agent_message.content) {
                    appendMessage('agent', data.agent_message.content);
                }
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

        // End session + fetch assessment
        endBtn?.addEventListener('click', async (e) => {
            e.preventDefault();

            if (!currentSessionId) {
                alert('No active session to end.');
                return;
            }

            if (!csrfToken) {
                console.error('No CSRF token found');
                setStatus('Error: missing CSRF token.');
                return;
            }

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
                console.log('End session response', data);

                if (data.assessment) {
                    showAssessment(data.assessment);
                }

                setStatus('Session ended. Review your assessment below.');
                // Keep currentSessionId if you want to allow replay; or clear it:
                currentSessionId = null;
            } catch (err) {
                console.error(err);
                setStatus('Error ending session. Check console.');
            }
        });

        formEl?.addEventListener('submit', (e) => {
            e.preventDefault();
            if (isSending) return;

            const value = (inputEl?.value || '').trim();
            if (!value) return;

            inputEl.value = '';
            sendToGideon(value);
        });

        // (Optional) – if you later pass scenario descriptions as data attributes,
        // you can update scenarioDescriptionEl on change here.
    })();
</script>
@endsection
