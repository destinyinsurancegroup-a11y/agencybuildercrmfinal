@extends('layouts.app')

@section('content')
<style>
    :root {
        --gold: #c9a227;
        --gold-soft: #f5e6b3;
        --bg-page: #f5f5f5;
        --text-main: #111827;
        --text-subtle: #4b5563;
        --text-faint: #9ca3af;
    }

    .sparring-page {
        padding: 30px 40px;
        background: var(--bg-page);
        min-height: 100vh;
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .sparring-header {
        margin-bottom: 24px;
    }

    .sparring-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sparring-badge {
        font-size: 11px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        background: var(--gold-soft);
        color: #92400e;
        padding: 3px 8px;
        border-radius: 999px;
        border: 1px solid rgba(0,0,0,0.08);
    }

    .sparring-subtitle {
        margin-top: 6px;
        font-size: 14px;
        color: var(--text-subtle);
    }

    .sparring-layout {
        display: grid;
        grid-template-columns: minmax(260px, 320px) minmax(0, 1fr);
        gap: 24px;
    }

    /* LEFT PANEL: instructions */
    .sparring-sidecard {
        background: #050509;
        border-radius: 20px;
        padding: 18px 20px;
        border: 1px solid rgba(201,162,39,0.4);
        color: #f9fafb;
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.45),
            0 8px 16px -8px rgba(0,0,0,0.35);
    }

    .sparring-side-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sparring-avatar {
        width: 32px;
        height: 32px;
        border-radius: 999px;
        background: radial-gradient(circle at 20% 20%, #fef3c7, #c9a227);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: #111827;
        font-size: 16px;
        box-shadow: 0 0 0 2px #111827;
    }

    .sparring-side-section-title {
        margin-top: 12px;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #d1d5db;
    }

    .sparring-side-list {
        margin-top: 6px;
        padding-left: 16px;
        font-size: 13px;
        color: #e5e7eb;
    }

    .sparring-side-list li {
        margin-bottom: 4px;
    }

    .sparring-side-footnote {
        margin-top: 12px;
        font-size: 11px;
        color: #9ca3af;
    }

    /* RIGHT PANEL: chat */
    .sparring-chat-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 140px);
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.30),
            0 8px 16px -8px rgba(0,0,0,0.18);
    }

    .sparring-chat-header {
        padding: 14px 18px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sparring-chat-title {
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-main);
    }

    .sparring-status-pill {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 999px;
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .sparring-chat-body {
        padding: 16px 18px;
        overflow-y: auto;
        flex: 1;
        background: linear-gradient(180deg,#f3f4f6 0,#ffffff 40%);
    }

    .sparring-message-row {
        display: flex;
        margin-bottom: 10px;
        gap: 8px;
    }

    .sparring-message-row.user {
        justify-content: flex-end;
    }

    .sparring-bubble {
        max-width: 75%;
        padding: 9px 11px;
        border-radius: 16px;
        font-size: 14px;
        line-height: 1.4;
    }

    .sparring-bubble.user {
        background: #111827;
        color: #f9fafb;
        border-bottom-right-radius: 4px;
    }

    .sparring-bubble.gideon {
        background: #fef3c7;
        color: #1f2937;
        border-bottom-left-radius: 4px;
        border: 1px solid rgba(201,162,39,0.4);
    }

    .sparring-message-meta {
        font-size: 11px;
        color: var(--text-faint);
        margin-top: 2px;
    }

    .sparring-chat-footer {
        border-top: 1px solid #e5e7eb;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        background: #f9fafb;
        border-radius: 0 0 20px 20px;
    }

    .sparring-input-row {
        display: flex;
        gap: 8px;
        align-items: flex-end;
    }

    .sparring-textarea {
        flex: 1;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        padding: 8px 10px;
        min-height: 52px;
        max-height: 110px;
        resize: vertical;
        font-size: 14px;
    }

    .sparring-send-btn {
        padding: 10px 16px;
        border-radius: 999px;
        border: none;
        background: var(--gold);
        color: #111827;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 6px 14px rgba(0,0,0,0.35);
        white-space: nowrap;
    }

    .sparring-send-btn[disabled] {
        opacity: 0.7;
        cursor: default;
        box-shadow: none;
    }

    .sparring-hint {
        font-size: 12px;
        color: var(--text-faint);
    }

</style>

<div class="sparring-page">
    <div class="sparring-header">
        <div class="sparring-title">
            Sparring Partner
            <span class="sparring-badge">Tier 1 · Beta</span>
        </div>
        <div class="sparring-subtitle">
            Practice scripts, objections, and presentations with Gideon.  
            This is a training-only environment and does not change client data.
        </div>
    </div>

    <div class="sparring-layout">
        {{-- LEFT: Instructions / examples --}}
        <div class="sparring-sidecard">
            <div class="sparring-side-title">
                <div class="sparring-avatar">G</div>
                Gideon Sparring Partner
            </div>

            <div class="sparring-side-section-title">Try asking:</div>
            <ul class="sparring-side-list">
                <li>“Help me practice a final expense presentation to a couple.”</li>
                <li>“Role-play a client who is worried about price; I’ll respond as the agent.”</li>
                <li>“Give me 3 ways to handle the objection: ‘I need to think about it.’”</li>
                <li>“Rewrite this script to sound more natural for a 70-year-old widow.”</li>
            </ul>

            <div class="sparring-side-section-title">Modes (behind the scenes)</div>
            <ul class="sparring-side-list">
                <li><strong>Sparring:</strong> Gideon plays the client so you can practice.</li>
                <li><strong>Coaching:</strong> Gideon gives feedback & suggestions.</li>
            </ul>

            <div class="sparring-side-footnote">
                Tier 1 uses a safe “fake mode” until billing is enabled.  
                Replies may be generic during this phase but the wiring and UX are live.
            </div>
        </div>

        {{-- RIGHT: Chat --}}
        <div class="sparring-chat-card" id="sparring-root">
            <div class="sparring-chat-header">
                <div class="sparring-chat-title">
                    <span>Live Session</span>
                </div>
                <span class="sparring-status-pill" id="sparring-status-pill">
                    Ready
                </span>
            </div>

            <div class="sparring-chat-body" id="sparring-messages">
                {{-- Initial Gideon welcome message --}}
                <div class="sparring-message-row gideon">
                    <div class="sparring-bubble gideon">
                        I’m Gideon, your Sparring Partner. Tell me what you want to practice:
                        a script, an objection, or a full presentation. I can either role-play
                        as the client or coach you on your wording.
                        <div class="sparring-message-meta">Gideon · just now</div>
                    </div>
                </div>
            </div>

            <div class="sparring-chat-footer">
                <div class="sparring-input-row">
                    <textarea
                        id="sparring-input"
                        class="sparring-textarea"
                        placeholder="Type what you want to practice. Example: ‘Play the client. I’m going to present a final expense policy to a 65-year-old couple.’"
                    ></textarea>

                    <button class="sparring-send-btn" id="sparring-send-btn">
                        <span>Send</span> <span>➤</span>
                    </button>
                </div>

                <div class="sparring-hint">
                    Gideon may return a “FAKE GIDEON REPLY” while the OpenAI API is in test mode.
                    Once billing is enabled, this same chat will use live responses automatically.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputEl   = document.getElementById('sparring-input');
    const sendBtn   = document.getElementById('sparring-send-btn');
    const messagesEl = document.getElementById('sparring-messages');
    const statusPill = document.getElementById('sparring-status-pill');

    const csrfTokenTag = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenTag ? csrfTokenTag.getAttribute('content') : '';

    function appendMessage(role, text) {
        const row = document.createElement('div');
        row.classList.add('sparring-message-row', role);

        const bubble = document.createElement('div');
        bubble.classList.add('sparring-bubble', role === 'user' ? 'user' : 'gideon');
        bubble.textContent = text;

        // meta
        const meta = document.createElement('div');
        meta.classList.add('sparring-message-meta');
        const who = (role === 'user') ? 'You' : 'Gideon';
        meta.textContent = who;

        bubble.appendChild(meta);
        row.appendChild(bubble);
        messagesEl.appendChild(row);

        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    async function sendMessage() {
        const text = (inputEl.value || '').trim();
        if (!text) return;

        appendMessage('user', text);
        inputEl.value = '';
        inputEl.focus();

        sendBtn.disabled = true;
        statusPill.textContent = 'Thinking…';

        try {
            const response = await fetch('/api/gideon/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    message: text,
                    mode: 'sparring' // our API can use this hint
                })
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();
            const reply = data.reply || '[Gideon had an unexpected response format.]';

            appendMessage('gideon', reply);
        } catch (e) {
            console.error(e);
            appendMessage('gideon', 'I had trouble reaching the server. Please try again in a moment.');
        } finally {
            sendBtn.disabled = false;
            statusPill.textContent = 'Ready';
        }
    }

    sendBtn.addEventListener('click', sendMessage);

    inputEl.addEventListener('keydown', function (evt) {
        if (evt.key === 'Enter' && !evt.shiftKey) {
            evt.preventDefault();
            sendMessage();
        }
    });
});
</script>
@endsection
