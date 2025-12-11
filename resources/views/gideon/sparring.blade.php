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
        <div class="card-body d-flex flex-wrap align-items-start gap-3">

            {{-- Scenario --}}
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

            {{-- Persona --}}
            <div>
                <label for="personaSelect" class="form-label mb-1">Prospect persona</label>
                <select id="personaSelect" class="form-select">
                    <option value="adaptive" selected>Adaptive (recommended)</option>
                    <option value="soft_conflict_avoidant">Soft / conflict-avoidant</option>
                    <option value="neutral_realistic">Neutral / realistic</option>
                    <option value="skeptical_guarded">Skeptical / guarded</option>
                </select>
                <div class="form-text small">
                    Choose how Gideon should “feel” before you start.
                </div>
            </div>

            {{-- Scenario description --}}
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

            {{-- Reset button --}}
            <div class="ms-auto">
                <button id="resetSessionBtn" class="btn btn-outline-secondary btn-sm" type="button">
                    Reset Session
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
        const scenarioSelectEl        = document.getElementById('scenarioSelect');
        const personaSelectEl         = document.getElementById('personaSelect');
        const scenarioDescriptionEl   = document.getElementById('scenarioDescription');
        const statusEl                = document.getElementById('sparringStatus');
        const resetBtn                = document.getElementById('resetSessionBtn');

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

        function resetSession() {
            currentSessionId = null;
            transcriptEl.innerHTML =
                '<div class="text-muted small">Session reset. Send a new message to start again.</div>';
            setStatus('');
        }

        resetBtn?.addEventListener('click', (e) => {
            e.preventDefault();
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

            const persona = personaSelectEl?.value || 'adaptive';

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

                // Opening line on first call
                if (data.opening_line) {
                    appendMessage('gideon', data.opening_line);
                }

                // Agent + Gideon messages from service
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
