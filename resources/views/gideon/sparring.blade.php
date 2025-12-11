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

            <div class="ms-auto d-flex gap-2">
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

    {{-- Prospect state HUD --}}
    <div class="card mb-3">
        <div class="card-header">
            Prospect state (how Gideon “feels”)
        </div>
        <div class="card-body small" id="statePanel">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="d-flex justify-content-between">
                        <span>Trust</span>
                        <span id="stateTrustValue">–</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div id="stateTrustBar" class="progress-bar" role="progressbar" style="width:0%"></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="d-flex justify-content-between">
                        <span>Urgency</span>
                        <span id="stateUrgencyValue">–</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div id="stateUrgencyBar" class="progress-bar" role="progressbar" style="width:0%"></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="d-flex justify-content-between">
                        <span>Motivation</span>
                        <span id="stateMotivationValue">–</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div id="stateMotivationBar" class="progress-bar" role="progressbar" style="width:0%"></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="d-flex justify-content-between">
                        <span>Resistance</span>
                        <span id="stateResistanceValue">–</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div id="stateResistanceBar" class="progress-bar bg-danger" role="progressbar" style="width:0%"></div>
                    </div>
                </div>
            </div>
            <div class="mt-2 text-muted">
                Aim to grow <strong>trust / motivation / urgency</strong> while bringing
                <strong>resistance</strong> down through good discovery and empathy.
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
</div>

{{-- Simple inline JS for now --}}
<script>
    (() => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        // DOM elements
        const transcriptEl           = document.getElementById('sparringTranscript');
        const inputEl                = document.getElementById('sparringInput');
        const formEl                 = document.getElementById('sparringForm');
        const scenarioSelectEl       = document.getElementById('scenarioSelect');
        const scenarioDescriptionEl  = document.getElementById('scenarioDescription');
        const statusEl               = document.getElementById('sparringStatus');
        const resetBtn               = document.getElementById('resetSessionBtn');
        const endBtn                 = document.getElementById('endSessionBtn');

        // State HUD elements
        const stateTrustBar         = document.getElementById('stateTrustBar');
        const stateTrustValue       = document.getElementById('stateTrustValue');
        const stateUrgencyBar       = document.getElementById('stateUrgencyBar');
        const stateUrgencyValue     = document.getElementById('stateUrgencyValue');
        const stateMotivationBar    = document.getElementById('stateMotivationBar');
        const stateMotivationValue  = document.getElementById('stateMotivationValue');
        const stateResistanceBar    = document.getElementById('stateResistanceBar');
        const stateResistanceValue  = document.getElementById('stateResistanceValue');

        // Session state in JS
        let currentSessionId = null;
        let isSending        = false;

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

        function resetHud() {
            updateState(null);
        }

        function resetSession() {
            currentSessionId = null;
            transcriptEl.innerHTML =
                '<div class="text-muted small">Session reset. Send a new message to start again.</div>';
            setStatus('');
            resetHud();
        }

        resetBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            resetSession();
        });

        endBtn?.addEventListener('click', async (e) => {
            e.preventDefault();
            if (!currentSessionId) return;

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
                console.log('Session ended', data);

                setStatus('Session ended. Review your assessment below (coming soon).');
                currentSessionId = null;
            } catch (err) {
                console.error(err);
                setStatus('Error ending session. Check console.');
            }
        });

        function updateState(state) {
            const defaults = {
                trust: 35,
                urgency: 30,
                motivation: 40,
                resistance: 60
            };

            if (!state) {
                stateTrustBar.style.width        = '0%';
                stateUrgencyBar.style.width      = '0%';
                stateMotivationBar.style.width   = '0%';
                stateResistanceBar.style.width   = '0%';

                stateTrustValue.textContent      = '–';
                stateUrgencyValue.textContent    = '–';
                stateMotivationValue.textContent = '–';
                stateResistanceValue.textContent = '–';
                return;
            }

            const s = Object.assign({}, defaults, state);

            function clamp(num) {
                num = parseInt(num, 10);
                if (Number.isNaN(num)) return 0;
                return Math.max(0, Math.min(100, num));
            }

            const trust      = clamp(s.trust);
            const urgency    = clamp(s.urgency);
            const motivation = clamp(s.motivation);
            const resistance = clamp(s.resistance);

            stateTrustBar.style.width        = trust + '%';
            stateUrgencyBar.style.width      = urgency + '%';
            stateMotivationBar.style.width   = motivation + '%';
            stateResistanceBar.style.width   = resistance + '%';

            stateTrustValue.textContent      = trust;
            stateUrgencyValue.textContent    = urgency;
            stateMotivationValue.textContent = motivation;
            stateResistanceValue.textContent = resistance;
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

                // Update HUD with latest state
                if (data.state) {
                    updateState(data.state);
                } else if (data.session && data.session.state) {
                    updateState(data.session.state);
                }

                // 1) opening line on first call (we currently don't send one from service, but keep for future)
                if (data.opening_line) {
                    appendMessage('gideon', data.opening_line);
                }

                // 2) generic v1 structure: { agent_message: {...}, gideon_reply: {...} }
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

        formEl?.addEventListener('submit', (e) => {
            e.preventDefault();
            if (isSending) return;

            const value = (inputEl?.value || '').trim();
            if (!value) return;

            inputEl.value = '';
            sendToGideon(value);
        });

        // Initial HUD state
        resetHud();
    })();
</script>
@endsection
