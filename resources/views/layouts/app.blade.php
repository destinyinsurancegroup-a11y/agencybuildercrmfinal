<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agency Builder CRM</title>

    <!-- CSRF for AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- GOOGLE FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- ⭐ ADD BOOTSTRAP (CRITICAL FOR GRID LAYOUT) ⭐ -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- GLOBAL STYLES -->
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: #f4f4f4;
        }

        /* === SIDEBAR === */
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
            border-bottom: 1px solid rgba(212, 175, 55, 0.25);
            text-align: left;
        }

        .nav-item:hover {
            background-color: #1a1a1a;
            cursor: pointer;
        }

        /* ⭐ MAIN CONTENT FIX ⭐ */
        .main-content {
            margin-left: 250px;
            padding: 25px;
        }

        /* ⭐ RIGHT PANEL FOR POPUPS ⭐ */
        #right-panel {
            position: fixed;
            top: 0;
            right: -450px;
            width: 450px;
            height: 100%;
            background: #ffffff;
            box-shadow: -3px 0 10px rgba(0,0,0,0.2);
            transition: right 0.3s ease;
            z-index: 2000;
            overflow-y: auto;
            padding: 20px;
        }

        #right-panel.open {
            right: 0;
        }
    </style>
</head>

<body>

    <!-- ⭐ RIGHT PANEL (CRITICAL FOR ACTIVITY POPUP) ⭐ -->
    <div id="right-panel"></div>

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

        <a class="nav-item" href="/activity">Activity</a>
        <a class="nav-item" href="/calendar">Calendar</a>

        <a class="nav-item" href="/settings">Settings</a>
        <a class="nav-item" href="/billing">Billing</a>
        <a class="nav-item" href="/logout">Logout</a>
    </div>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="main-content">
        <div class="container-fluid">
            @yield('content')
        </div>
    </div>

    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AUTO USER TIMEZONE FIX -->
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

        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("tab-button")) {
                document.querySelectorAll(".tab-button").forEach(btn =>
                    btn.classList.remove("active")
                );
                e.target.classList.add("active");
            }
        });
    </script>

    <!-- ⭐ GLOBAL HANDLER FOR ACTIVITY POPUP SAVE ⭐ -->
    <script>
        document.addEventListener('click', function (e) {

            if (!e.target || e.target.id !== 'saveActivityBtn') return;

            e.preventDefault();

            const form = document.getElementById('activityForm');
            if (!form) return;

            const formData = new FormData(form);

            fetch(form.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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

                document.getElementById('right-panel').classList.remove('open');

                if (typeof window.refreshProductionCard === 'function') {
                    window.refreshProductionCard();
                }
            });
        });
    </script>

    @stack('scripts')

</body>
</html>
