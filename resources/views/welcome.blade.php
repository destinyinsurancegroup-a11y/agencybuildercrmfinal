<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Numbers | Agency Builder CRM</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background-color: #f8f5ee;
            color: #222;
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background-color: #0e0e0e;
            width: 260px;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.5);
        }

        .sidebar img {
            width: 140px;
            margin-bottom: 15px;
            filter: drop-shadow(0 0 4px rgba(201,164,76,0.6));
        }

        .sidebar h2 {
            color: #c9a44c;
            font-size: 19px;
            margin-bottom: 25px;
        }

        .nav-item {
            width: 100%;
            padding: 12px 16px;
            margin: 6px 0;
            background-color: #1b1b1b;
            border-radius: 6px;
            text-align: left;
            color: #e4e4e4;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
        }

        .nav-item:hover {
            background-color: #d6b15d;
            color: #111;
        }

        .nav-item.active {
            background-color: #d6b15d;
            color: #111;
            font-weight: 600;
            box-shadow: 0 0 6px rgba(214,177,93,0.6);
        }

        /* Main Section */
        .main {
            flex-grow: 1;
            padding: 40px;
            background-color: #f8f5ee;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: #111;
            font-weight: 700;
        }

        .header p {
            color: #666;
            font-size: 15px;
        }

        /* Performance Cards */
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 40px;
        }

        .card {
            background-color: #fffdfa;
            border: 1px solid #e0d8c8;
            border-radius: 10px;
            padding: 25px;
            width: 240px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(214,177,93,0.25);
        }

        .card h3 {
            color: #b4903d;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 24px;
            font-weight: 600;
        }

        /* Goal Progress */
        .goal {
            background: #fffdfa;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #e0d8c8;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .progress-bar {
            height: 14px;
            width: 100%;
            background: #eee3c9;
            border-radius: 7px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-bar-inner {
            height: 100%;
            width: 70%; /* Example progress */
            background: linear-gradient(90deg, #d6b15d, #b4903d);
            border-radius: 7px;
            transition: width 0.5s;
        }

        .goal-title {
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }

    </style>
</head>
<body>
    <div class="sidebar">
        <img src="/assets/images/logo.png" alt="Agency Builder Logo">
        <h2>Agency Builder</h2>
        <div class="nav-item">🏠 Dashboard</div>
        <div class="nav-item active">📈 My Numbers</div>
        <div class="nav-item">💬 Leads</div>
        <div class="nav-item">📞 Calls</div>
        <div class="nav-item">🧾 Sales</div>
    </div>

    <div class="main">
        <div class="header">
            <h1>My Numbers</h1>
            <p>Performance overview — October 2025</p>
        </div>

        <div class="stats">
            <div class="card"><h3>Calls Made</h3><p>127</p></div>
            <div class="card"><h3>Appointments Set</h3><p>34</p></div>
            <div class="card"><h3>Presentations</h3><p>20</p></div>
            <div class="card"><h3>Sales Closed</h3><p>7</p></div>
            <div class="card"><h3>Conversion Rate</h3><p>35%</p></div>
        </div>

        <div class="goal">
            <div class="goal-title">Monthly Sales Goal</div>
            <div class="progress-bar"><div class="progress-bar-inner"></div></div>
            <p style="font-size:14px;color:#555;margin-top:6px;">$7,000 of $10,000 target (70%)</p>
        </div>

        <div class="goal">
            <div class="goal-title">Weekly Call Goal</div>
            <div class="progress-bar"><div class="progress-bar-inner" style="width:85%;"></div></div>
            <p style="font-size:14px;color:#555;margin-top:6px;">85 of 100 calls (85%)</p>
        </div>
    </div>
</body>
</html>
