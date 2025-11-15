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
        :root {
            --abc-gold: #F5D254; /* Correct ABC Gold */
        }

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
            color: var(--abc-gold);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
            border-right: 3px solid var(--abc-gold);
        }

        /* LOGO AREA */
        .sidebar-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar-logo img {
            max-width: 120px;
            height: auto;
            margin-bottom: 10px;
        }

        .sidebar-logo .logo-text {
            color: var(--abc-gold);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            line-height: 1.2;
        }

        .nav-item {
            padding: 14px 25px;
            font-size: 16px;
            color: var(--abc-gold);
            text-decoration: none;
            display: block;
            border-bottom: 1px solid rgba(245, 210, 84, 0.25);
        }

        .nav-item:hover {
            background-color: #1a1a1a;
            cursor: pointer;
        }

        /* HEADER */
        .header {
            margin-left: 250px;
            background-color: #000;
            padding: 12px 25px;
            color: var(--abc-gold);
            font-size: 18px;
            font-weight: 600;
            border-bottom: 3px solid var(--abc-gold);
        }

        /* MAIN CONTENT */
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

    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">

        <div class="sidebar-logo">
            <img src="{{ asset('images/agency-builder-logo.png') }}" alt="Agency Builder CRM">

            <div class="logo-text">
                AGENCY BUILDER<br>CRM
            </div>
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

    <!-- HEADER -->
    <div class="header">
        Welcome to Agency Builder CRM
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-box">
            @yield('content')
        </div>
    </div>

</body>
</html>
