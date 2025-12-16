@extends('layouts.app')

@section('content')
<style>
  :root{
    --abc-bg: #0b0b0d;
    --abc-panel: #101113;
    --abc-panel-2: #0f0f10;
    --abc-gold: #caa35a;
    --abc-gold-2:#e0c27a;
    --abc-text: #f2f2f2;
    --abc-muted:#a7a7a7;
    --abc-line: rgba(202,163,90,.28);
    --abc-shadow: 0 18px 55px rgba(0,0,0,.55);
    --abc-radius: 18px;
  }

  /* Page frame */
  .abc-wrap{
    min-height: calc(100vh - 110px);
    background: radial-gradient(1200px 600px at 40% 15%, rgba(202,163,90,.10), transparent 60%),
                radial-gradient(800px 400px at 80% 55%, rgba(202,163,90,.08), transparent 60%),
                var(--abc-bg);
    border-radius: 22px;
    padding: 26px;
    color: var(--abc-text);
  }

  .abc-topbar{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    margin-bottom:18px;
  }
  .abc-title h1{
    font-size: 40px;
    letter-spacing: .2px;
    margin:0;
    line-height: 1.05;
  }
  .abc-title p{
    margin: 6px 0 0 0;
    color: var(--abc-muted);
    font-size: 14px;
  }

  .abc-actions{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .abc-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding: 8px 12px;
    border-radius: 999px;
    border: 1px solid var(--abc-line);
    background: rgba(16,17,19,.75);
    color: var(--abc-text);
    font-size: 13px;
  }
  .abc-btn-danger{
    border-radius: 10px;
    padding: 9px 14px;
    border: 1px solid rgba(220,53,69,.75);
    background: rgba(220,53,69,.08);
    color: #ffd6dc;
    font-weight: 600;
  }
  .abc-btn-danger:hover{ background: rgba(220,53,69,.14); }

  /* Layout columns */
  .abc-grid{
    display:grid;
    grid-template-columns: 380px 1fr;
    gap: 18px;
    align-items: start;
  }

  .abc-panel{
    background: linear-gradient(180deg, rgba(255,255,255,.02), transparent 35%),
                var(--abc-panel);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: var(--abc-radius);
    box-shadow: var(--abc-shadow);
    overflow:hidden;
  }
  .abc-panel-header{
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    display:flex;
    align-items:center;
    justify-content:space-between;
  }
  .abc-panel-header .kicker{
    color: var(--abc-gold-2);
    font-size: 12px;
    letter-spacing: .18em;
    text-transform: uppercase;
  }

  /* LEFT: live conversation */
  .abc-chat{
    display:flex;
    flex-direction:column;
    height: 640px;
  }
  .abc-chat-body{
    padding: 14px 14px 8px 14px;
    overflow:auto;
    flex: 1;
  }
  .abc-chat-hint{
    color: var(--abc-muted);
    font-size: 13px;
    padding: 12px;
    border: 1px dashed rgba(202,163,90,.22);
    border-radius: 14px;
    background: rgba(0,0,0,.25);
  }
  .bubble{
    max-width: 88%;
    padding: 10px 12px;
    border-radius: 16px;
    margin: 10px 0;
    border: 1px solid rgba(255,255,255,.06);
    background: rgba(0,0,0,.28);
    position:relative;
  }
  .bubble.you{
    margin-left:auto;
    border-color: rgba(202,163,90,.35);
    background: rgba(202,163,90,.10);
  }
  .bubble.them{
    margin-right:auto;
    border-color: rgba(255,255,255,.08);
    background: rgba(255,255,255,.04);
  }
  .bubble .meta{
    margin-top: 6px;
    font-size: 11px;
    color: rgba(255,255,255,.55);
    display:flex;
    justify-content:space-between;
    gap:10px;
  }

  .abc-chat-input{
    padding: 12px;
    border-top: 1px solid rgba(255,255,255,.06);
    background: rgba(0,0,0,.18);
  }
  .abc-chat-row{
    display:flex;
    gap:10px;
    align-items:center;
  }
  .abc-input{
    flex:1;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(10,10,12,.6);
    color: var(--abc-text);
    padding: 12px 12px;
    outline:none;
  }
  .abc-input:focus{
    border-color: rgba(202,163,90,.55);
    box-shadow: 0 0 0 3px rgba(202,163,90,.12);
  }
  .abc-send{
    border-radius: 12px;
    padding: 11px 16px;
    border: 1px solid rgba(202,163,90,.65);
    background: linear-gradient(180deg, rgba(202,163,90,.95), rgba(170,128,58,.95));
    color: #0b0b0d;
    font-weight: 800;
  }
  .abc-send:disabled{ opacity:.55; }

  .abc-status{
    margin-top: 8px;
    font-size: 12px;
    color: var(--abc-muted);
  }

  /* RIGHT: hero avatar + controls */
  .abc-hero{
    padding: 18px;
  }

  .abc-row{
    display:flex;
    gap:12px;
    align-items:flex-start;
    flex-wrap:wrap;
    margin-bottom: 14px;
  }

  .abc-field{
    min-width: 260px;
    flex: 1;
  }
  .abc-label{
    font-size: 12px;
    color: rgba(255,255,255,.75);
    margin-bottom: 6px;
  }
  .abc-select{
    width:100%;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(10,10,12,.55);
    color: var(--abc-text);
    padding: 10px 12px;
  }
  .abc-help{
    margin-top:6px;
    font-size:12px;
    color: rgba(255,255,255,.55);
  }

  .abc-toggle{
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
  }
  .segmented{
    display:inline-flex;
    border: 1px solid rgba(202,163,90,.35);
    border-radius: 14px;
    overflow:hidden;
    background: rgba(0,0,0,.28);
  }
  .segmented button{
    padding: 10px 14px;
    border: 0;
    background: transparent;
    color: rgba(255,255,255,.82);
    font-weight: 700;
    min-width: 140px;
  }
  .segmented button.active{
    background: rgba(202,163,90,.22);
    color: var(--abc-gold-2);
  }

  .avatar-zone{
    margin-top: 12px;
    padding: 20px 14px;
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,.06);
    background: radial-gradient(520px 220px at 50% 30%, rgba(202,163,90,.10), transparent 55%),
                rgba(0,0,0,.20);
    display:flex;
    align-items:center;
    justify-content:center;
    min-height: 340px;
    position:relative;
  }

  .avatar-ring{
    width: 280px;
    height: 280px;
    border-radius: 999px;
    border: 2px solid rgba(202,163,90,.75);
    box-shadow: 0 0 0 12px rgba(202,163,90,.06);
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    background: rgba(0,0,0,.25);
  }
  .avatar-ring img{
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: contrast(1.03) saturate(1.03);
  }

  .expression-pill{
    position:absolute;
    top: 14px;
    left: 14px;
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding: 7px 10px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(0,0,0,.30);
    color: rgba(255,255,255,.86);
    font-size: 12px;
  }

  .start-cta{
    margin-top: 14px;
    display:flex;
    justify-content:center;
  }
  .abc-start{
    border: 0;
    border-radius: 999px;
    padding: 12px 18px;
    min-width: 240px;
    background: linear-gradient(180deg, rgba(202,163,90,.95), rgba(170,128,58,.95));
    color: #0b0b0d;
    font-weight: 900;
    letter-spacing: .2px;
  }

  .abc-bottom-controls{
    margin-top: 18px;
    display:grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
    align-items:start;
  }

  .abc-chiprow{
    display:flex;
    gap:10px;
    justify-content:center;
    flex-wrap:wrap;
  }
  .chip{
    padding: 10px 14px;
    border-radius: 14px;
    border: 1px solid rgba(202,163,90,.30);
    background: rgba(0,0,0,.22);
    color: rgba(255,255,255,.80);
    font-weight: 800;
    min-width: 160px;
    text-align:center;
  }
  .chip.active{
    background: rgba(202,163,90,.20);
    color: var(--abc-gold-2);
    border-color: rgba(202,163,90,.55);
  }

  .segments-mini{
    margin-top: 10px;
    display:flex;
    justify-content:center;
  }
  .segments-mini select{
    width: 210px;
  }

  /* Responsive */
  @media (max-width: 1100px){
    .abc-grid{ grid-template-columns: 1fr; }
    .abc-chat{ height: 520px; }
  }
</style>

@php
  $firstScenario = ($scenarios ?? collect())->first();
@endphp

<div class="abc-wrap">
  <div class="abc-topbar">
    <div class="abc-title">
      <h1>ABC Sparring Partner</h1>
      <p>Live conversation training with a human-like prospect. Practice in segments or run a full presentation.</p>
    </div>

    <div class="abc-actions">
      <div class="abc-pill" title="Session timer">
        ⏱ <span id="timerText">00:00</span>
      </div>
      <button class="abc-btn-danger" id="endSessionBtn" type="button">End Session</button>
    </div>
  </div>

  <div class="abc-grid">
    {{-- LEFT: Live conversation panel (your “blank area”) --}}
    <div class="abc-panel abc-chat">
      <div class="abc-panel-header">
        <div class="kicker">Live Conversation</div>
        <button id="resetSessionBtn" class="abc-pill" type="button" title="Reset session">Reset</button>
      </div>

      <div class="abc-chat-body" id="chatBody">
        <div class="abc-chat-hint" id="chatHint">
          Click <b>Start Sparring Session</b>, then type your first line. Gideon will respond like a real prospect.
        </div>
      </div>

      <div class="abc-chat-input">
        <div class="abc-chat-row">
          <input id="sparringInput" class="abc-input" type="text" placeholder="Type what you’d say and press Enter…" autocomplete="off" />
          <button id="sendBtn" class="abc-send" type="button">Send</button>
        </div>
        <div class="abc-status" id="sparringStatus"></div>
      </div>
    </div>

    {{-- RIGHT: Hero avatar + configuration (matches your first pic layout) --}}
    <div class="abc-panel">
      <div class="abc-hero">
        <div class="abc-row">
          <div class="abc-field">
            <div class="abc-label">Scenario</div>
            <select id="scenarioSelect" class="abc-select">
              @forelse(($scenarios ?? collect()) as $scenario)
                <option value="{{ $scenario->code }}" data-description="{{ $scenario->description ?? '' }}">
                  {{ $scenario->name }}@if($scenario->product_type) ({{ $scenario->product_type }})@endif
                </option>
              @empty
                <option value="">No scenarios seeded yet</option>
              @endforelse
            </select>
            <div class="abc-help" id="scenarioDescription">
              {{ $firstScenario?->description ?? 'Select a scenario to see its description.' }}
            </div>
          </div>

          <div class="abc-field">
            <div class="abc-label">Prospect persona</div>
            <select id="personaSelect" class="abc-select">
              <option value="soft_conflict_avoidant" selected>Soft / conflict-avoidant</option>
              <option value="neutral_balanced">Neutral / balanced</option>
              <option value="direct_analytical">Direct / analytical</option>
              <option value="skeptical_guarded">Skeptical / guarded</option>
            </select>
            <div class="abc-help">Sets how skeptical the prospect starts.</div>
          </div>

          <div class="abc-field">
            <div class="abc-label">UI Role Mode</div>
            <select id="uiModeSelect" class="abc-select">
              <option value="prospect_simulation" selected>Prospect sim (You = Agent)</option>
              <option value="agent_simulation">Role reversal (You = Prospect)</option>
            </select>
            <div class="abc-help">This is not Training Mode.</div>
          </div>

          <div class="abc-field">
            <div class="abc-label">Environment</div>
            <div class="abc-toggle">
              <div class="segmented" role="group" aria-label="Environment toggle">
                <button type="button" id="envPhone" class="active">Phone</button>
                <button type="button" id="envInPerson">In Person</button>
              </div>
            </div>
            <div class="abc-help">Phone shows a phone panel. In-person shows the avatar.</div>
          </div>
        </div>

        <div class="avatar-zone">
          <div class="expression-pill">
            PROSPECT • <span id="expressionLabel">Neutral</span>
          </div>

          <div class="avatar-ring" id="avatarRing">
            {{-- Replace this with your real “human prospect” asset later --}}
            <img id="avatarImg"
                 src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=800&q=80"
                 alt="Prospect avatar" />
          </div>
        </div>

        <div class="start-cta">
          <button id="startSessionBtn" class="abc-start" type="button">▶ Start Sparring Session</button>
        </div>

        <div class="abc-bottom-controls">
          <div>
            <div class="abc-chiprow">
              <div class="chip" data-difficulty="beginner" id="diffBeginner">Beginner</div>
              <div class="chip active" data-difficulty="intermediate" id="diffIntermediate">Intermediate</div>
              <div class="chip" data-difficulty="advanced" id="diffAdvanced">Advanced</div>
            </div>
          </div>

          <div>
            <div class="abc-chiprow">
              <div class="chip active" data-training="full_presentation" id="trainFull">Full Preso</div>
              <div class="chip" data-training="stages" id="trainSegments">Segments</div>
              <div class="chip" data-training="discovery_start" id="trainDisco">Disco</div>
            </div>
          </div>

          <div>
            <div class="segments-mini">
              <div>
                <div class="abc-label" style="text-align:center;">Segments</div>
                <select id="segmentSelect" class="abc-select">
                  <option value="intro">Intro</option>
                  <option value="discovery" selected>Disco</option>
                  <option value="education">Educ</option>
                  <option value="qualify">Qual</option>
                  <option value="quote">Quote</option>
                  <option value="close">Close</option>
                </select>
                <div class="abc-help" style="text-align:center;">Highlights what you’re training.</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  const chatBody = document.getElementById('chatBody');
  const chatHint = document.getElementById('chatHint');
  const inputEl  = document.getElementById('sparringInput');
  const sendBtn  = document.getElementById('sendBtn');
  const statusEl = document.getElementById('sparringStatus');

  const scenarioSelect = document.getElementById('scenarioSelect');
  const scenarioDesc   = document.getElementById('scenarioDescription');
  const personaSelect  = document.getElementById('personaSelect');
  const uiModeSelect   = document.getElementById('uiModeSelect');

  const envPhone = document.getElementById('envPhone');
  const envInPerson = document.getElementById('envInPerson');
  const avatarRing = document.getElementById('avatarRing');

  const startBtn = document.getElementById('startSessionBtn');
  const resetBtn = document.getElementById('resetSessionBtn');
  const endBtn   = document.getElementById('endSessionBtn');

  const timerText = document.getElementById('timerText');
  const expressionLabel = document.getElementById('expressionLabel');

  // Visual-only for now (backend integration later)
  const segmentSelect = document.getElementById('segmentSelect');
  const diffChips = [document.getElementById('diffBeginner'), document.getElementById('diffIntermediate'), document.getElementById('diffAdvanced')];
  const trainChips = [document.getElementById('trainFull'), document.getElementById('trainSegments'), document.getElementById('trainDisco')];

  let currentSessionId = null;
  let started = false;
  let isSending = false;

  // Timer
  let timerStart = null;
  let timerHandle = null;

  function setStatus(msg){ statusEl.textContent = msg || ''; }

  function fmtTime(ms){
    const total = Math.floor(ms/1000);
    const m = String(Math.floor(total/60)).padStart(2,'0');
    const s = String(total%60).padStart(2,'0');
    return `${m}:${s}`;
  }

  function startTimer(){
    timerStart = Date.now();
    if (timerHandle) clearInterval(timerHandle);
    timerHandle = setInterval(() => {
      timerText.textContent = fmtTime(Date.now() - timerStart);
    }, 250);
  }

  function stopTimer(){
    if (timerHandle) clearInterval(timerHandle);
    timerHandle = null;
  }

  function resetSessionUi(){
    currentSessionId = null;
    started = false;
    isSending = false;
    stopTimer();
    timerText.textContent = '00:00';
    expressionLabel.textContent = 'Neutral';
    chatBody.innerHTML = '';
    const hint = document.createElement('div');
    hint.className = 'abc-chat-hint';
    hint.id = 'chatHint';
    hint.innerHTML = 'Click <b>Start Sparring Session</b>, then type your first line. Gideon will respond like a real prospect.';
    chatBody.appendChild(hint);
    setStatus('');
    inputEl.value = '';
    inputEl.disabled = false;
    sendBtn.disabled = false;
  }

  function updateScenarioDescription(){
    const opt = scenarioSelect?.options[scenarioSelect.selectedIndex];
    const desc = opt?.getAttribute('data-description') || '';
    scenarioDesc.textContent = desc || 'No description available for this scenario.';
  }

  function bubble(who, text){
    const wrap = document.createElement('div');
    wrap.className = `bubble ${who === 'you' ? 'you' : 'them'}`;
    wrap.innerHTML = `
      <div>${escapeHtml(text)}</div>
      <div class="meta">
        <span>${who === 'you' ? 'You' : 'Prospect'}</span>
        <span>${new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
      </div>
    `;
    chatBody.appendChild(wrap);
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  function escapeHtml(str){
    return String(str).replace(/[&<>"']/g, (m) => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  // Expression heuristic (visual only)
  function setExpressionFromState(state){
    // If your backend starts returning richer “expression”, replace this.
    const trust = Number(state?.trust ?? 50);
    const resistance = Number(state?.resistance ?? 50);

    let label = 'Neutral';
    if (resistance >= 75) label = 'Skeptical';
    else if (trust >= 70) label = 'Open';
    else if (trust <= 30) label = 'Guarded';

    expressionLabel.textContent = label;
  }

  async function sendMessage(){
    if (!csrf) return setStatus('Missing CSRF token.');
    const scenarioCode = scenarioSelect?.value;
    if (!scenarioCode) return setStatus('Pick a scenario first.');

    const msg = (inputEl.value || '').trim();
    if (!msg) return;

    if (!started){
      started = true;
      startTimer();
    }

    inputEl.value = '';
    isSending = true;
    sendBtn.disabled = true;

    // You are always “You” in the UI; backend handles roles via ui_mode
    bubble('you', msg);
    setStatus('Talking to Gideon…');

    try{
      const payload = {
        scenario_code: scenarioCode,
        session_id: currentSessionId,
        message: msg,
        persona: personaSelect?.value || 'soft_conflict_avoidant',
        mode: uiModeSelect?.value || 'prospect_simulation',
        // These are UI-only right now; safe to ignore server-side (Laravel validation ignores extra keys)
        training_mode: document.querySelector('.chip[data-training].active')?.dataset.training || 'full_presentation',
        selected_stage: segmentSelect?.value || 'discovery',
        difficulty: document.querySelector('.chip[data-difficulty].active')?.dataset.difficulty || 'intermediate',
      };

      const res = await fetch('/api/gideon/sparring/ask', {
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'Accept':'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify(payload),
      });

      if(!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();

      if (data.session?.id) currentSessionId = data.session.id;

      if (data.opening_line) bubble('them', data.opening_line);
      if (data.gideon_reply?.content) bubble('them', data.gideon_reply.content);

      if (data.state) setExpressionFromState(data.state);

      setStatus('Session active.');
    } catch(e){
      console.error(e);
      setStatus('Error talking to Gideon. Check runtime logs + browser console.');
    } finally {
      isSending = false;
      sendBtn.disabled = false;
      inputEl.focus();
    }
  }

  async function endSession(){
    if (!csrf) return setStatus('Missing CSRF token.');
    if (!currentSessionId) return setStatus('No active session to end.');

    isSending = true;
    sendBtn.disabled = true;
    setStatus('Ending session…');

    try{
      const res = await fetch('/api/gideon/sparring/end', {
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'Accept':'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ session_id: currentSessionId }),
      });

      if(!res.ok) throw new Error(`HTTP ${res.status}`);
      stopTimer();
      setStatus('Session ended.');
      inputEl.disabled = true;
      sendBtn.disabled = true;
    } catch(e){
      console.error(e);
      setStatus('Error ending session. Check runtime logs.');
      sendBtn.disabled = false;
    } finally {
      isSending = false;
    }
  }

  // Environment toggle (visual)
  function setEnv(isInPerson){
    if (isInPerson){
      envInPerson.classList.add('active');
      envPhone.classList.remove('active');
      // Show avatar (already)
      avatarRing.style.opacity = '1';
    } else {
      envPhone.classList.add('active');
      envInPerson.classList.remove('active');
      // Still keep avatar visible (or dim). If you want a phone image, swap here.
      avatarRing.style.opacity = '0.65';
    }
  }

  // Chips (visual only)
  function activateChip(group, el){
    group.forEach(c => c.classList.remove('active'));
    el.classList.add('active');
  }

  // Wire up
  updateScenarioDescription();
  scenarioSelect?.addEventListener('change', () => { updateScenarioDescription(); resetSessionUi(); });

  envPhone?.addEventListener('click', () => setEnv(false));
  envInPerson?.addEventListener('click', () => setEnv(true));

  diffChips.forEach(chip => chip?.addEventListener('click', () => activateChip(diffChips, chip)));
  trainChips.forEach(chip => chip?.addEventListener('click', () => activateChip(trainChips, chip)));

  startBtn?.addEventListener('click', () => {
    started = true;
    startTimer();
    setStatus('Ready. Say your first line.');
    inputEl.focus();
  });

  resetBtn?.addEventListener('click', resetSessionUi);
  endBtn?.addEventListener('click', endSession);

  sendBtn?.addEventListener('click', () => { if(!isSending) sendMessage(); });
  inputEl?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter'){
      e.preventDefault();
      if(!isSending) sendMessage();
    }
  });

  // Default env = In Person like your mock
  setEnv(true);
});
</script>
@endsection
