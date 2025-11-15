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
            width: 140px;   /* Increased 400% from your original small render */
            height: auto;
            margin-bottom: 15px;
        }

        /* Removed the text below the logo */
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

        .content-box {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        /* REMOVE TOP BLACK BAR */
        .header {
            display: none;
        }

        /* GOLD THEME FOR BUTTONS & HIGHLIGHTED ELEMENTS */
        .btn-gold,
        button,
        .tab.active {
            background-color: #D4AF37 !important;
            color: #000 !important;
        }

        /* Fix gold text accents */
        .gold-text {
            color: #D4AF37 !important;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">

        <!-- LOGO -->
        <div class="sidebar-logo">
            <img src="/images/agency-builder-logo.png" alt="Agency Builder CRM Logo">
        </div>

        <!-- NAVIGATION -->
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

    <!-- MAIN CONTENT WRAPPER -->
    <div class="main-content">
        @yield('content')
    </div>

</body>
</html>
