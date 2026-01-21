<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Agency Builder CRM')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: #f4f4f4;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background-color: #000;
            color: #D4AF37;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 25px;
            border-right: 3px solid #D4AF37;
            text-align: center;
            z-index: 10;
        }

        .sidebar-logo img {
            width: 200px;
            height: auto;
            margin-bottom: 15px;
        }

        .nav-item {
            padding: 14px 25px;
            font-size: 16px;
            color: #D4AF37;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid rgba(212,175,55,0.25);
            text-align: left;
            background: transparent;
        }

        .nav-item:hover {
            background-color: #1a1a1a;
            cursor: pointer;
        }

        /* Active nav highlight */
        .nav-item.active {
            background-color: #111;
            border-left: 4px solid #D4AF37;
            padding-left: 21px; /* compensate for border-left */
        }

        .main-content {
            margin-left: 250px;
            padding: 25px;
        }

        /* Logout button styled like nav links */
        .nav-button {
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
        }

        /* =========================================================
           ✅ ATTACHMENTS UI (paperclip + chips) — matches your design
           ========================================================= */
        .ab-attach-row{
            display:flex;
            align-items:center;
            gap:10px;
            margin-top:14px;
            flex-wrap:wrap;
        }
        .ab-attach-clip{
            width:34px;
            height:34px;
            border-radius:10px;
            border:1px solid rgba(201,162,39,0.65);
            background: #fff;
            display:flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            box-shadow:0 6px 14px rgba(0,0,0,0.08);
        }
        .ab-attach-clip:hover{
            background: rgba(201,162,39,0.08);
        }
        .ab-attach-label{
            font-weight:700;
            color:#111827;
            font-size:14px;
        }
        .ab-attach-chips{
            display:flex;
            gap:8px;
            align-items:center;
            flex-wrap:wrap;
        }
        .ab-file-chip{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:6px 10px;
            border-radius:10px;
            border:1px solid #e5e7eb;
            background:#f9fafb;
            text-decoration:none;
            color:#111827;
            font-size:13px;
            box-shadow:0 4px 10px rgba(0,0,0,0.06);
        }
        .ab-file-chip:hover{
            background:#f3f4f6;
        }
        .ab-file-icon{
            width:18px;
            height:18px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }
        .ab-file-more{
            font-weight:700;
            color:#6b7280;
            font-size:13px;
            padding:6px 6px;
        }
        .ab-file-empty{
            color:#6b7280;
            font-size:13px;
        }
    </style>

    {{-- ✅ OPTIONAL: page/partial CSS hooks --}}
    @stack('styles')
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="/images/agency-builder-logo.png" alt="Agency Builder CRM Logo">
        </div>

        <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
        <a class="nav-item {{ request()->routeIs('contacts.*') ? 'active' : '' }}" href="{{ route('contacts.index') }}">All Contacts</a>
        <a class="nav-item {{ request()->routeIs('book.*') ? 'active' : '' }}" href="{{ route('book.index') }}">Book of Business</a>
        <a class="nav-item {{ request()->routeIs('leads.*') ? 'active' : '' }}" href="{{ route('leads.index') }}">Leads</a>
        <a class="nav-item {{ request()->routeIs('service.*') ? 'active' : '' }}" href="{{ route('service.index') }}">Service</a>

        <a class="nav-item {{ request()->is('calendar') ? 'active' : '' }}" href="/calendar">Calendar</a>

        <a class="nav-item {{ request()->routeIs('gideon.second_brain') ? 'active' : '' }}" href="{{ route('gideon.second_brain') }}">Gideon Second Brain</a>

        <a class="nav-item {{ request()->is('settings') ? 'active' : '' }}" href="/settings">Settings</a>

        <a class="nav-item {{ request()->routeIs('billing') ? 'active' : '' }}" href="{{ route('billing') }}">Billing</a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-item nav-button">Logout</button>
        </form>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="container-fluid">
            @yield('content')
        </div>
    </div>

    {{-- ✅ GLOBAL MESSAGING MODALS + JS (included ONCE for entire app) --}}
    @include('partials.messaging')

    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- TIME DISPLAY SUPPORT -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const elements = document.querySelectorAll(".local-time");
            elements.forEach(el => {
                const ms = el.getAttribute("data-server-time-ms");
                if (ms) {
                    const n = parseInt(ms, 10);
                    if (!isNaN(n)) {
                        const localDate = new Date(n);
                        el.innerText = localDate.toLocaleString();
                        return;
                    }
                }

                const serverTime = el.getAttribute("data-server-time");
                if (serverTime) {
                    const localDate = new Date(serverTime + " UTC");
                    el.innerText = localDate.toLocaleString();
                }
            });
        });
    </script>

    <!-- ⭐ OPEN ACTIVITY POPUP ⭐ -->
    <script>
        function openActivityPopup() {
            fetch("{{ route('activity.popup') }}", {
                headers: { "X-Requested-With": "XMLHttpRequest" },
                cache: "no-store"
            })
                .then(res => res.text())
                .then(html => {
                    let wrap = document.createElement('div');
                    wrap.innerHTML = html;
                    document.body.appendChild(wrap);

                    let modalElement = wrap.querySelector('.modal');
                    let popup = new bootstrap.Modal(modalElement);

                    popup.show();

                    modalElement.addEventListener('hidden.bs.modal', () => wrap.remove());
                });
        }
    </script>

    <!-- ⭐ GLOBAL SAVE HANDLER FOR ACTIVITY POPUP ⭐ -->
    <script>
        document.addEventListener('click', function (e) {
            if (e.target.id !== 'saveActivityBtn') return;

            e.preventDefault();

            const form = document.getElementById('activityForm');
            if (!form) return;

            const formData = new FormData(form);

            fetch(form.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert('Error saving activity.');
                    return;
                }

                form.reset();

                let modalEl = document.querySelector('.modal.show');
                if (modalEl) {
                    bootstrap.Modal.getInstance(modalEl).hide();
                }

                window.dispatchEvent(new CustomEvent('activity:saved'));
            })
            .catch(() => {
                alert('Error saving activity.');
            });
        });
    </script>

    <!-- =========================================================
         ✅ ATTACHMENTS JS (paperclip -> file picker -> auto upload)
         ========================================================= -->
    <script>
        window.ABAttachments = {
            open: function (contactId) {
                const input = document.getElementById('ab-attach-input-' + contactId);
                if (!input) return;
                input.click();
            },

            submitIfSelected: function (contactId) {
                const form  = document.getElementById('ab-attach-form-' + contactId);
                const input = document.getElementById('ab-attach-input-' + contactId);
                if (!form || !input) return;

                if (!input.files || input.files.length === 0) return;

                // Simple + reliable: standard form submit (will refresh the page)
                // Controller redirects back via return_to so user lands on the same screen.
                form.submit();
            }
        };
    </script>

    {{-- ✅ REQUIRED --}}
    @stack('scripts')

</body>
</html>
