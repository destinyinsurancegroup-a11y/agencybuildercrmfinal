<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Settings | Agency Builder CRM</title>
<style>
<?php echo file_get_contents(__DIR__ . '/dashboard.php', false, null, strpos(file_get_contents(__DIR__ . '/dashboard.php'), '<style>') + 7, strpos(file_get_contents(__DIR__ . '/dashboard.php'), '</style>') - strpos(file_get_contents(__DIR__ . '/dashboard.php'), '<style>') - 7); ?>
form {margin-top:20px;}
form label {display:block;margin:6px 0 2px;}
form input {width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;margin-bottom:10px;}
form button {background:var(--gold);border:none;padding:10px 14px;border-radius:8px;font-weight:bold;cursor:pointer;}
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
  <h1>Settings</h1>
  <p class="greeting">Update your profile, preferences, and account details.</p>

  <div class="card">
    <form>
      <label>Agent Name</label><input type="text" value="Your Name">
      <label>Email Address</label><input type="email" value="you@agencybuilder.com">
      <label>Password</label><input type="password" value="">
      <button>💾 Save Changes</button>
    </form>
  </div>

  <div class="footer">© 2025 Agency Builder CRM — Tier 1</div>
</div>
</body>
</html>
