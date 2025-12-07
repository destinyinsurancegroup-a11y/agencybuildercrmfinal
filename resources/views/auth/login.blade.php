<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agency Builder CRM – Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000; /* black */
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #f8f9fa;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #111;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            border: 1px solid #333;
        }

        .login-title {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 4px;
            color: #D4AF37; /* gold */
        }

        .login-subtitle {
            font-size: 0.9rem;
            color: #aaa;
            margin-bottom: 24px;
        }

        .form-label {
            font-size: 0.85rem;
            color: #ddd;
        }

        .form-control {
            background: #000;
            border: 1px solid #333;
            color: #f8f9fa;
        }

        .form-control:focus {
            border-color: #D4AF37;
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }

        .btn-gold {
            width: 100%;
            background: #D4AF37;
            border-color: #D4AF37;
            color: #000;
            font-weight: 600;
        }

        .btn-gold:hover {
            background: #e0bd52;
            border-color: #e0bd52;
            color: #000;
        }

        .error-text {
            color: #ff6b6b;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="mb-3 text-center">
        <div class="login-title">Agency Builder CRM</div>
        <div class="login-subtitle">Log in to your production command center</div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <strong>Login failed.</strong>
            <div class="mt-1">
                {{ $errors->first() }}
            </div>
        </div>
    @endif

    <form method="POST" action="{{ url('/login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input
                type="email"
                name="email"
                id="email"
                class="form-control"
                value="{{ old('email') }}"
                required
                autofocus
            >
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input
                type="password"
                name="password"
                id="password"
                class="form-control"
                required
            >
        </div>

        <button type="submit" class="btn btn-gold mt-2">
            Log in
        </button>
    </form>

    <div class="mt-3 text-center" style="font-size: 0.8rem; color: #888;">
        Test accounts:<br>
        agentA@example.com / password<br>
        agentB@example.com / password
    </div>
</div>
</body>
</html>
