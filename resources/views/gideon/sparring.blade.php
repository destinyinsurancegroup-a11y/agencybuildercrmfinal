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
                    End Session (Assessment)
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

    {{-- Assessment panel --}}
    <div id="assessmentCard" class="card mt-3 d-none">
        <div class="card-header">
            Session Assessment
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Rapport:</strong>
                    <div id="scoreRapport">–</div>
                </div>
                <div class="col-md-3">
                    <strong>Discovery:</strong>
                    <div id="scoreDiscovery">–</div>
                </div>
                <div class="col-md-3">
                    <strong>Deal killers:</strong>
                    <div id="scoreDealKillers">–</div>
                </div>
                <div class="col-md-3">
                    <strong>Closing clarity:</strong>
                    <div id="scoreClosingClarity">–</div>
                </div>
            </div>

            <div class="mb-3">
                <strong>Strengths</strong>
                <div id="assessmentStrengths" class="small text-muted">
                    –
                </div>
            </div>

            <div>
                <strong>Improvements</strong>
                <div id="assessmentImprovements" class="small text-muted">
                    –
                </div>
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
        let sessionActive = false;

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

        function resetSessionUI(message) {
            currentSessionId = null;
            sessionActive = false;

            transcriptEl.innerHTML =
                `<div class="text-muted small">${message || 'Session reset. Send a new message to start again.'}</div>`;

            setStatus('');
            if (assessmentCard) {
                assessmentCard.classList.add('d-none');
            }
        }

        resetBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            resetSessionUI();
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

                sessionActive = true;

                // 1) opening line on first call
                if (data.opening_line) {
                    appendMessage('gideon', data.opening_line);
                }

                // 2) Gideon reply
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
                setStatus('Missing CSRF token.');
                return;
            }

            try {
                setStatus('Ending session and generating assessment...');

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
                console.log('Gideon end-session response', data);

                const scores = (data.assessment && data.assessment.scores) || {};

                scoreRapportEl.textContent = scores.rapport ?? '–';
                scoreDiscoveryEl.textContent = scores.discovery ?? '–';
                scoreDealKillersEl.textContent = scores.deal_killers ?? '–';
                scoreClosingClarityEl.textContent = scores.closing_clarity ?? '–';

                strengthsEl.textContent = data.assessment.strengths
                    || 'Placeholder assessment. Automated coaching logic to be implemented.';
                improvementsEl.textContent = data.assessment.improvements
                    || 'Placeholder assessment. Automated coaching logic to be implemented.';

                assessmentCard.classList.remove('d-none');

                resetSessionUI('Session ended. Review your assessment below.');
            } catch (err) {
                console.error(err);
                setStatus('Error ending session. Check console.');
            }
        }

        endBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            endSession();
        });

        formEl?.addEventListener('submit', (e) => {
            e.preventDefault();
            if (isSending) return;

            const value = (inputEl?.value || '').trim();
            if (!value) return;

            inputEl.value = '';
            sendToGideon(value);
        });
    })();
</script>
@endsection
