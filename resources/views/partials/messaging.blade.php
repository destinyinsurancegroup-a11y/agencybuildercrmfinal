{{-- resources/views/partials/messaging.blade.php --}}

<!-- =========================
     SMS MODAL
========================= -->
<div class="modal fade" id="abSmsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" onsubmit="ABMessaging.sendSms(event)">
            <div class="modal-header">
                <h5 class="modal-title">Send Text</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="ab_sms_contact_id">
                <div class="fw-bold" id="ab_sms_contact_name"></div>
                <div class="text-muted mb-2" id="ab_sms_to"></div>

                {{-- ✅ TEMPLATE SELECTOR (SMS) --}}
                <div class="mt-2">
                    <label class="form-label small text-muted mb-1">Template</label>
                    <select id="ab_sms_template_id" class="form-select">
                        <option value="">— Select a template —</option>
                    </select>
                    <div class="small text-muted mt-1">
                        Selecting a template will fill the message. You can still edit before sending.
                    </div>
                </div>

                <div class="small text-muted mb-1 mt-3" id="ab_sms_history_label">Loading…</div>
                <div id="ab_sms_history" class="ab-history"></div>

                <textarea id="ab_sms_body"
                          class="form-control mt-2"
                          rows="4"
                          placeholder="Type message..."></textarea>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-gold" type="submit">Send</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================
     EMAIL MODAL
========================= -->
<div class="modal fade" id="abEmailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" onsubmit="ABMessaging.sendEmail(event)">
            <div class="modal-header">
                <h5 class="modal-title">Send Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="ab_email_contact_id">
                <div class="fw-bold" id="ab_email_contact_name"></div>
                <div class="text-muted mb-2" id="ab_email_to"></div>

                {{-- ✅ TEMPLATE SELECTOR (EMAIL) --}}
                <div class="mt-2">
                    <label class="form-label small text-muted mb-1">Template</label>
                    <select id="ab_email_template_id" class="form-select">
                        <option value="">— Select a template —</option>
                    </select>
                    <div class="small text-muted mt-1">
                        Selecting a template will fill subject + body. You can still edit before sending.
                    </div>
                </div>

                <div class="small text-muted mb-1 mt-3" id="ab_email_history_label">Loading…</div>
                <div id="ab_email_history" class="ab-history"></div>

                <input id="ab_email_subject"
                       class="form-control mt-2"
                       placeholder="Subject">

                <textarea id="ab_email_body"
                          class="form-control mt-2"
                          rows="6"
                          placeholder="Type email..."></textarea>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-gold" type="submit">Send</button>
            </div>
        </form>
    </div>
</div>

<style>
.ab-history {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
    padding: 10px;
    max-height: 260px;
    overflow-y: auto;
    margin-bottom: 8px;
}
.ab-msg {
    max-width: 85%;
    border-radius: 12px;
    padding: 8px 10px;
    margin: 6px 0;
    font-size: 13px;
}
.ab-msg.outbound {
    margin-left: auto;
    background: #fdf6e3;
}
.ab-msg.inbound {
    margin-right: auto;
    background: #f3f4f6;
}
.ab-msg-meta {
    font-size: 11px;
    color: #6b7280;
    margin-top: 4px;
}
</style>

<script>
if (!window.ABMessaging) {
    window.ABMessaging = (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        // ✅ These routes are added in Step 2a
        const ROUTES = {
            listTemplates: '/settings/messaging/templates/json',      // ?channel=sms|email
            getTemplate:   '/settings/messaging/templates',           // /{id}/json
            messagesBase:  '/contacts'                                // /{contactId}/messages
        };

        // ----------------------------
        // History rendering (existing)
        // ----------------------------
        function escapeHtml(s) {
            return String(s || '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function renderHistory(target, label, items) {
            const box = document.getElementById(target);
            const lbl = document.getElementById(label);
            if (!box) return;

            box.innerHTML = '';
            if (!items.length) {
                if (lbl) lbl.textContent = 'No messages yet.';
                return;
            }

            if (lbl) lbl.textContent = 'Message history';
            items.forEach(m => {
                const div = document.createElement('div');
                div.className = 'ab-msg ' + (m.direction === 'inbound' ? 'inbound' : 'outbound');

                // ✅ Safe: body is escaped before inserting
                const dt = m.created_at ? new Date(m.created_at) : null;
                const stamp = (dt && !isNaN(dt.getTime())) ? dt.toLocaleString() : '';

                div.innerHTML =
                    `<div>${escapeHtml(m.body || '')}</div>
                     <div class="ab-msg-meta">${escapeHtml(stamp)}${m.status ? ' · ' + escapeHtml(m.status) : ''}</div>`;

                box.appendChild(div);
            });
            box.scrollTop = box.scrollHeight;
        }

        function fetchHistory(contactId, channel, target, label) {
            return fetch(`${ROUTES.messagesBase}/${encodeURIComponent(contactId)}/messages?channel=${encodeURIComponent(channel)}&limit=200`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json().catch(() => ({})))
            .then(d => renderHistory(target, label, d.items || []))
            .catch(() => {
                const lbl = document.getElementById(label);
                const box = document.getElementById(target);
                if (lbl) lbl.textContent = 'Failed to load history.';
                if (box) box.innerHTML = '';
            });
        }

        // ----------------------------
        // ✅ Template loading helpers
        // ----------------------------
        const templateCache = {
            sms: null,   // array
            email: null  // array
        };

        function setSelectOptions(selectEl, items) {
            if (!selectEl) return;

            // Keep the first placeholder option, remove the rest
            while (selectEl.options.length > 1) {
                selectEl.remove(1);
            }

            (items || []).forEach(t => {
                const opt = document.createElement('option');
                opt.value = String(t.id);
                opt.textContent = t.name || ('Template #' + t.id);
                selectEl.appendChild(opt);
            });
        }

        function loadTemplatesForChannel(channel) {
            if (templateCache[channel]) return Promise.resolve(templateCache[channel]);

            const url = `${ROUTES.listTemplates}?channel=${encodeURIComponent(channel)}`;
            return fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json().catch(() => ({})).then(d => {
                if (!r.ok || !d.success) throw new Error(d.message || 'Failed to load templates.');
                return d.items || [];
            }))
            .then(items => {
                templateCache[channel] = items;
                return items;
            })
            .catch(err => {
                console.error(err);
                templateCache[channel] = []; // prevent repeated hammering
                return [];
            });
        }

        function loadTemplateById(id) {
            const url = `${ROUTES.getTemplate}/${encodeURIComponent(id)}/json`;
            return fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json().catch(() => ({})).then(d => {
                if (!r.ok || !d.success) throw new Error(d.message || 'Failed to load template.');
                return d.item;
            }));
        }

        function bindTemplateSelector(selectId, channel, onApply) {
            const selectEl = document.getElementById(selectId);
            if (!selectEl) return;

            // Populate options when modal opens (openSms/openEmail)
            // Apply template on selection
            selectEl.addEventListener('change', function () {
                const tplId = String(selectEl.value || '');
                if (!tplId) return;

                loadTemplateById(tplId)
                    .then(item => {
                        // ✅ Enforce channel rules client-side too
                        if (!item || item.channel !== channel) {
                            alert('That template does not match this channel.');
                            selectEl.value = '';
                            return;
                        }
                        onApply(item);
                    })
                    .catch(err => {
                        console.error(err);
                        alert(err.message || 'Failed to load template.');
                    });
            });
        }

        // Apply behavior (safe: only set .value, never innerHTML)
        function applySmsTemplate(item) {
            const bodyEl = document.getElementById('ab_sms_body');
            if (bodyEl) bodyEl.value = String(item.body || '');
        }

        function applyEmailTemplate(item) {
            const subjEl = document.getElementById('ab_email_subject');
            const bodyEl = document.getElementById('ab_email_body');

            if (subjEl) subjEl.value = String(item.subject || '');
            if (bodyEl) bodyEl.value = String(item.body || '');
        }

        // Bind once
        bindTemplateSelector('ab_sms_template_id', 'sms', applySmsTemplate);
        bindTemplateSelector('ab_email_template_id', 'email', applyEmailTemplate);

        // ----------------------------
        // Public API
        // ----------------------------
        return {
            openSms(id, name, phone) {
                document.getElementById('ab_sms_contact_id').value = id;
                document.getElementById('ab_sms_contact_name').textContent = name || '';
                document.getElementById('ab_sms_to').textContent = phone ? `To: ${phone}` : 'No phone';

                // Reset fields
                document.getElementById('ab_sms_body').value = '';

                // Reset + load templates
                const sel = document.getElementById('ab_sms_template_id');
                if (sel) sel.value = '';
                loadTemplatesForChannel('sms').then(items => setSelectOptions(sel, items));

                // Load history
                fetchHistory(id, 'sms', 'ab_sms_history', 'ab_sms_history_label');

                new bootstrap.Modal(document.getElementById('abSmsModal')).show();
            },

            openEmail(id, name, email) {
                document.getElementById('ab_email_contact_id').value = id;
                document.getElementById('ab_email_contact_name').textContent = name || '';
                document.getElementById('ab_email_to').textContent = email ? `To: ${email}` : 'No email';

                // Reset fields
                document.getElementById('ab_email_subject').value = '';
                document.getElementById('ab_email_body').value = '';

                // Reset + load templates
                const sel = document.getElementById('ab_email_template_id');
                if (sel) sel.value = '';
                loadTemplatesForChannel('email').then(items => setSelectOptions(sel, items));

                // Load history
                fetchHistory(id, 'email', 'ab_email_history', 'ab_email_history_label');

                new bootstrap.Modal(document.getElementById('abEmailModal')).show();
            },

            sendSms(e) {
                e.preventDefault();
                const id = String(document.getElementById('ab_sms_contact_id').value || '').trim();
                const body = String(document.getElementById('ab_sms_body').value || '').trim();
                if (!body) return alert('Message empty');

                fetch(`${ROUTES.messagesBase}/${encodeURIComponent(id)}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ channel: 'sms', body })
                })
                .then(r => r.json().catch(() => ({})).then(d => {
                    if (!r.ok) throw new Error(d.message || 'Failed to send SMS.');
                    return d;
                }))
                .then(() => fetchHistory(id, 'sms', 'ab_sms_history', 'ab_sms_history_label'))
                .catch(err => {
                    console.error(err);
                    alert(err.message || 'Failed to send SMS.');
                });
            },

            sendEmail(e) {
                e.preventDefault();
                const id = String(document.getElementById('ab_email_contact_id').value || '').trim();
                const subject = String(document.getElementById('ab_email_subject').value || '').trim();
                const body = String(document.getElementById('ab_email_body').value || '').trim();

                if (!subject) return alert('Subject is required');
                if (!body) return alert('Body is required');

                fetch(`${ROUTES.messagesBase}/${encodeURIComponent(id)}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ channel: 'email', subject, body })
                })
                .then(r => r.json().catch(() => ({})).then(d => {
                    if (!r.ok) throw new Error(d.message || 'Failed to send email.');
                    return d;
                }))
                .then(() => fetchHistory(id, 'email', 'ab_email_history', 'ab_email_history_label'))
                .catch(err => {
                    console.error(err);
                    alert(err.message || 'Failed to send email.');
                });
            }
        };
    })();
}
</script>
