<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Agency Builder CRM' }}</title>

    <style>
        :root {
            --gold: #D4AF37;
            --bg: #0b0b0c;
            --panel: #131316;
            --soft: #1b1b20;
            --paper: #fffdf7;
            --line: #e7e3cc;
            --ink: #111;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: white;
            font-family: Arial, sans-serif;
        }

        .layout {
            display: flex;
            height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            background: var(--panel);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #222;
        }

        .sidebar h2 {
            color: var(--gold);
            text-align: center;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .nav-link {
            padding: 14px 25px;
            text-decoration: none;
            color: white;
            display: block;
            font-size: 15px;
            border-left: 4px solid transparent;
        }

        .nav-link:hover {
            background: var(--soft);
            border-left-color: var(--gold);
        }

        /* MAIN CONTENT */
        .main {
            flex: 1;
            padding: 20px 30px;
            overflow-y: auto;
        }

        .card {
            background: var(--panel);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #222;
        }

        h1 {
            color: var(--gold);
            margin-top: 0;
        }
    </style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2>Agency Builder CRM</h2>

        <a href="/dashboard" class="nav-link">Dashboard</a>
        <a href="/all-contacts" class="nav-link">All Contacts</a>
        <a href="/book-of-business" class="nav-link">Book of Business</a>
        <a href="/leads" class="nav-link">Leads</a>
        <a href="/service" class="nav-link">Service</a>
        <a href="/activity" class="nav-link">Activity</a>
        <a href="/calendar" class="nav-link">Calendar</a>
        <a href="/settings" class="nav-link">Settings</a>
        <a href="/billing" class="nav-link">Billing</a>
        <a href="/logout" class="nav-link">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="card">
            @yield('content')
        </div>
    </div>

</div>

</body>
</html>
