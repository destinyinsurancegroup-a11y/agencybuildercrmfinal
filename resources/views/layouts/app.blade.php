<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agency Builder CRM</title>

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
        }

        .nav-item:hover {
            background-color: #1a1a1a;
            cursor: pointer;
        }

        .main-content {
            margin-left: 250px;
            padding: 25px;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="/images/agency-builder-logo.png" alt="Agency Builder CRM Logo">
        </div>

        <a class="nav-item" href="{{ route('dashboard') }}">Dashboard</a>
        <a class="nav-item" href="{{ route('contacts.index') }}">All Contacts</a>
        <a class="nav-item" href="{{ route('book.index') }}">Book of Business</a>
        <a class="nav-item" href="{{ route('leads.index') }}">Leads</a>
        <a class="nav-item" href="{{ route('service.index') }}">Service</a>

        <!-- ⭐ FIXED — ACTIVITY NOW OPENS POPUP, NOT PAGE ⭐ -->
        <a class="nav-item" href="#" onclick="openActivityPopup()">Activity</a>

        <a class="nav-item" href="/calendar">Calendar</a>
        <a class="nav-item" href="/settings">Settings</a>
        <a class="nav-item" href="/billing">Billing</a>
        <a class="nav-item" href="/logout">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="container-fluid">
            @yield('content')
        </div>
    </div>

    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- TIMEZONE FIX -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const elements = document.querySelectorAll(".local-time");
            elements.forEach(el => {
                const serverTime = el.getAttribute("data-server-time");
                if (serverTime) {
                    const localDate = new Date(serverTime + " UTC");
                    el.innerText = localDate.toLocaleString();
                }
            });
        });
    </script>

    <!-- ⭐ OPEN ACTIVITY POPUP (REAL MODAL) ⭐ -->
    <script>
    function openActivityPopup() {
        fetch("{{ route('activity.popup') }}")
            .then(res => res.text())
            .then(html => {

                // Create wrapper
                let wrap = document.createElement('div');
                wrap.innerHTML = html;
                document.body.appendChild(wrap);

                // Find modal inside HTML
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
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert('Error saving activity.');
                    return;
                }

                form.reset();

                // Close modal
                let modalEl = document.querySelector('.modal.show');
                if (modalEl) {
                    bootstrap.Modal.getInstance(modalEl).hide();
                }

                if (typeof window.refreshProductionCard === 'function') {
                    window.refreshProductionCard();
                }
            });
        });
    </script>

    @stack('scripts')

</body>
</html>
