<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Calendar & Activity | Agency Builder CRM</title>
<style>
<?php echo file_get_contents(__DIR__ . '/dashboard.php', false, null, strpos(file_get_contents(__DIR__ . '/dashboard.php'), '<style>') + 7, strpos(file_get_contents(__DIR__ . '/dashboard.php'), '</style>') - strpos(file_get_contents(__DIR__ . '/dashboard.php'), '<style>') - 7); ?>
</style>
</head>
<body>
<div class="sidebar">
  <img src="/assets/images/logo.png" alt="Agency Builder Logo">
  <nav class="sidebar-nav">
    <a href="/index.php?page=dashboard" class="nav-item">🏠 Dashboard</a>
    <a href="/index.php?page=all_contacts" class="nav-item">👥 All Contacts</a>
    <a href="/index.php?page=book_of_business" class="nav-item">📘 Book of Business</a>
    <a href="/index.php?page=leads" class="nav-item">💬 Leads</a>
    <a href="/index.php?page=service" class="nav-item">🧰 Service</a>
    <a href="/index.php?page=calendar_activity" class="nav-item">📅 Calendar / Activity</a>
    <a href="/index.php?page=activity" class="nav-item">📊 Activity</a>
    <a href="/index.php?page=billing" class="nav-item">💳 Billing</a>
    <a href="/index.php?page=settings" class="nav-item">⚙️ Settings</a>
    <a href="/index.php?page=logout" class="nav-item">🚪 Logout</a>
  </nav>
</div>

<div class="main">
  <h1>Calendar & Activity</h1>
  <p class="greeting">Record and view your production activity daily, weekly, and monthly.</p>

  <div class="dashboard-grid">
    <div class="card">
      <h3>🗓️ This Week</h3>
      <ul>
        <li>Calls: 45</li>
        <li>Presentations: 12</li>
        <li>Sales: 4</li>
      </ul>
    </div>

    <div class="card">
      <h3>📅 Upcoming Schedule</h3>
      <ul>
        <li>Tuesday — Follow-up with Maria Lopez</li>
        <li>Friday — Team meeting (9 AM)</li>
      </ul>
    </div>

    <div class="card">
      <h3>📈 Monthly Totals</h3>
      <ul>
        <li>Calls: 210</li>
        <li>Presentations: 54</li>
        <li>Sales: 18</li>
      </ul>
    </div>
  </div>

  <div class="footer">© 2025 Agency Builder CRM — Tier 1</div>
</div>
</body>
</html>
