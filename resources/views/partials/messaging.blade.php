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

                <div class="small text-muted mb-1" id="ab_sms_history_label">Loading…</div>
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

                <div class="small text-muted mb-1" id="ab_email_history_label">Loading…</div>
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

        function renderHistory(target, label, items) {
            const box = document.getElementById(target);
            const lbl = document.getElementById(label);
            if (!box) return;

            box.innerHTML = '';
            if (!items.length) {
                lbl.textContent = 'No messages yet.';
                return;
            }

            lbl.textContent = 'Message history';
            items.forEach(m => {
                const div = document.createElement('div');
                div.className = 'ab-msg ' + (m.direction === 'inbound' ? 'inbound' : 'outbound');
                div.innerHTML =
                    `<div>${m.body || ''}</div>
                     <div class="ab-msg-meta">${new Date(m.created_at).toLocaleString()} · ${m.status}</div>`;
                box.appendChild(div);
            });
            box.scrollTop = box.scrollHeight;
        }

        function fetchHistory(contactId, channel, target, label) {
            fetch(`/contacts/${contactId}/messages?channel=${channel}&limit=200`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(d => renderHistory(target, label, d.items || []));
        }

        return {
            openSms(id, name, phone) {
                document.getElementById('ab_sms_contact_id').value = id;
                document.getElementById('ab_sms_contact_name').textContent = name;
                document.getElementById('ab_sms_to').textContent = phone ? `To: ${phone}` : 'No phone';
                document.getElementById('ab_sms_body').value = '';
                fetchHistory(id, 'sms', 'ab_sms_history', 'ab_sms_history_label');
                new bootstrap.Modal(document.getElementById('abSmsModal')).show();
            },

            openEmail(id, name, email) {
                document.getElementById('ab_email_contact_id').value = id;
                document.getElementById('ab_email_contact_name').textContent = name;
                document.getElementById('ab_email_to').textContent = email ? `To: ${email}` : 'No email';
                document.getElementById('ab_email_subject').value = '';
                document.getElementById('ab_email_body').value = '';
                fetchHistory(id, 'email', 'ab_email_history', 'ab_email_history_label');
                new bootstrap.Modal(document.getElementById('abEmailModal')).show();
            },

            sendSms(e) {
                e.preventDefault();
                const id = ab_sms_contact_id.value;
                const body = ab_sms_body.value.trim();
                if (!body) return alert('Message empty');

                fetch(`/contacts/${id}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ channel: 'sms', body })
                }).then(() => fetchHistory(id, 'sms', 'ab_sms_history', 'ab_sms_history_label'));
            },

            sendEmail(e) {
                e.preventDefault();
                const id = ab_email_contact_id.value;
                const subject = ab_email_subject.value.trim();
                const body = ab_email_body.value.trim();
                if (!subject || !body) return alert('Missing fields');

                fetch(`/contacts/${id}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ channel: 'email', subject, body })
                }).then(() => fetchHistory(id, 'email', 'ab_email_history', 'ab_email_history_label'));
            }
        };
    })();
}
</script>
