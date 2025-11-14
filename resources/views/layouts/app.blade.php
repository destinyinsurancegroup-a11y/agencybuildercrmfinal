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

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background-color: #000;
            color: #FFD700;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
            border-right: 3px solid #FFD700;
        }

        .sidebar h2 {
            text-align: center;
            font-size: 22px;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }

        .nav-item {
            padding: 14px 25px;
            font-size: 16px;
            color: #FFD700;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
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
            color: #FFD700;
            font-size: 18px;
            font-weight: 600;
            border-bottom: 3px solid #FFD700;
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
        <h2>Agency Builder CRM</h2>

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
