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

        {{-- ✅ Activity removed from sidebar.
            Activity logging still exists and is accessed via the "Log Production" button on the dashboard.
            Do NOT delete backend routes or popup view.
        --}}

        <a class="nav-item {{ request()->is('calendar') ? 'active' : '' }}" href="/calendar">Calendar</a>

        <!-- Gideon -->
        <a class="nav-item {{ request()->routeIs('gideon.second_brain') ? 'active' : '' }}" href="{{ route('gideon.second_brain') }}">Gideon Second Brain</a>

        {{-- ❌ REMOVED: Gideon Opportunities should NOT be a sidebar tab.
            All opportunity groups (BEC + Notes) should display in the Gideon Opportunities card on the dashboard.
            The /gideon/opportunities page can still exist, but it is not linked in the sidebar.
        --}}

        <a class="nav-item {{ request()->is('settings') ? 'active' : '' }}" href="/settings">Settings</a>

        <!-- ✅ Billing now uses a real named route (stops 404) -->
        <a class="nav-item {{ request()->routeIs('billing') ? 'active' : '' }}" href="{{ route('billing') }}">Billing</a>

        <!-- ✅ Logout must be POST (secure + matches web.php) -->
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

    <!-- TIME DISPLAY SUPPORT (handles both legacy and ms-based attributes safely) -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const elements = document.querySelectorAll(".local-time");
            elements.forEach(el => {
                // Preferred (your dashboard): epoch milliseconds
                const ms = el.getAttribute("data-server-time-ms");
                if (ms) {
                    const n = parseInt(ms, 10);
                    if (!isNaN(n)) {
                        const localDate = new Date(n);
                        el.innerText = localDate.toLocaleString();
                        return;
                    }
                }

                // Legacy fallback: data-server-time string
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

                // Broadcast "activity saved" so any page that cares can refresh itself.
                window.dispatchEvent(new CustomEvent('activity:saved'));
            })
            .catch(() => {
                alert('Error saving activity.');
            });
        });
    </script>

    {{-- ✅ REQUIRED: renders @push('scripts') from partials/pages (Gideon wiring lives here) --}}
    @stack('scripts')

</body>
</html>
