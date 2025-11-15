<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agency Builder CRM</title>

    <!-- GOOGLE FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

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
            color: #D4AF37; /* TRUE GOLD */
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 25px;
            border-right: 3px solid #D4AF37;
            text-align: center;
        }

        /* LOGO */
        .sidebar-logo img {
            width: 200px;   /* 200% Larger */
            height: auto;
            margin-bottom: 15px;
        }

        .sidebar h2 {
            display: none;
        }

        /* NAV BUTTONS */
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

        /* === MAIN CONTENT AREA === */
        .main-content {
            margin-left: 250px;
            padding: 25px;
        }

        /* TAB BUTTONS (Day/Week/Month/Quarter/Year) */
        .tab-button {
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid #ccc;
            color: #333;
            background: #f2f2f2;
            font-size: 14px;
            cursor: pointer;
        }

        .tab-button.active {
            background-color: #D4AF37 !important;
            color: #000 !important;
            border-color: #D4AF37;
            font-weight: 600;
        }

        /* Cards stay the same */
        .content-box {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .header {
            display: none;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="/images/agency-builder-logo.png" alt="Agency Builder CRM Logo">
        </div>

        <a class="nav-item" href="/dashboard">Dashboard</a>
        <a class="nav-item" href="/all-contacts">All Contacts</a>
        <a class="nav-item" href="/book-of-business">Book of Business</a>
        <a class="nav-item" href="/leads">Leads</a>
        <a class="nav-item" href="/service">Service</a>
        <a class="nav-item" href="/activity">Activity</a>
        <a class="nav-item" href="/calendar">Calendar</a>
        <a class="nav-item" href="/settings">Settings</a>
        <a class="nav-item" href="/billing">Billing</a>
        <a class="nav-item" href="/logout">Logout</a>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="main-content">
        @yield('content')
    </div>

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

        // TAB LOGIC
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("tab-button")) {
                document.querySelectorAll(".tab-button").forEach(btn => btn.classList.remove("active"));
                e.target.classList.add("active");
            }
        });
    </script>

</body>
</html>
