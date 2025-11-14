<!DOCTYPE html>
<html>
<head>
    <title>CRM — @yield('title')</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 15px; }
    </style>
</head>
<body>

<div class="nav">
    <a href="/dashboard">Dashboard</a>
    <a href="/book-of-business">Book of Business</a>
    <a href="/my-numbers">My Numbers</a>
    <a href="/service">Service</a>
    <a href="/settings">Settings</a>
</div>

<h1>@yield('header')</h1>

<div>
    @yield('content')
</div>

</body>
</html>
