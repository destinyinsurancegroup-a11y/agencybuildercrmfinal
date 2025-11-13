<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard | Agency Builder CRM</title>
<style>
:root {
  --gold:#D4AF37;
  --cream:#fffdf7;
  --dark:#000;
  --text:#111;
}
body {
  margin:0;
  font-family:'Segoe UI',Tahoma,sans-serif;
  background:var(--cream);
  color:var(--text);
  display:flex;
  height:100vh;
}
.sidebar {
  width:260px;
  background:var(--dark);
  padding:30px 20px;
  display:flex;
  flex-direction:column;
  align-items:center;
  box-shadow:3px 0 10px rgba(0,0,0,0.4);
}
.sidebar img {
  width:280px; /* 200% larger logo */
  margin-bottom:30px;
}
.sidebar-nav {width:100%;}
.nav-item {
  display:block;
  color:#f5f5f5;
  text-decoration:none;
  padding:12px 16px;
  margin:6px 0;
  border-radius:6px;
  font-size:15px;
  background:rgba(255,255,255,0.05);
  transition:0.3s;
}
.nav-item:hover {background:var(--gold);color:#000;}
.main {
  flex-grow:1;
  background:var(--cream);
  padding:40px;
  overflow-y:auto;
}
h1 {font-size:30px;margin:0;color:var(--dark);}
.greeting {color:var(--gold);font-weight:600;margin:10px 0 25px;}
.search-bar {
  display:flex;
  border:2px solid var(--gold);
  border-radius:10px;
  overflow:hidden;
  margin-bottom:25px;
  width:fit-content;
}
.search-bar input {
  border:none;
  outline:none;
  padding:10px 12px;
  width:240px;
  background:var(--cream);
}
.search-bar button {
  background:var(--gold);
  border:none;
  padding:10px 14px;
  cursor:pointer;
  font-weight:600;
}
.dashboard-grid {
  display:flex;
  flex-wrap:wrap;
  gap:20px;
}
.card {
  flex:1;
  min-width:280px;
  background:var(--cream);
  border:1px solid #ddd;
  border-radius:10px;
  padding:20px;
  box-shadow:0 6px 14px rgba(0,0,0,0.1);
}
.card h3 {
  border-left:4px solid var(--gold);
  padding-left:8px;
  color:var(--dark);
}
.footer {
  text-align:center;
  font-size:13px;
  color:#666;
  margin-top:30px;
}
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
  <h1>Dashboard</h1>
  <p class="greeting">Good afternoon, Agent — here’s your daily overview.</p>

  <div class="search-bar">
    <input type="text" placeholder="Search contacts, leads, or clients...">
    <button>🔍</button>
  </div>

  <div class="dashboard-grid">
    <div class="card">
      <h3>📈 Current Production</h3>
      <ul>
        <li>Calls: 35</li>
        <li>Presentations: 9</li>
        <li>Sales: 3</li>
        <li>Premium: $2,950</li>
      </ul>
    </div>
    <div class="card">
      <h3>📅 Upcoming Appointments</h3>
      <ul>
        <li>John Smith — Tues 2PM</li>
        <li>Maria Lopez — Wed 11AM</li>
      </ul>
    </div>
    <div class="card">
      <h3>🌟 Today’s Insights</h3>
      <ul>
        <li>🎂 2 Birthdays this week</li>
        <li>💍 1 Anniversary coming up</li>
      </ul>
    </div>
    <div class="card">
      <h3>🆕 Recently Added</h3>
      <ul>
        <li>Olivia Chen — Lead</li>
        <li>James Carter — Client</li>
      </ul>
    </div>
  </div>

  <div class="footer">© 2025 Agency Builder CRM — Tier 1</div>
</div>
</body>
</html>
