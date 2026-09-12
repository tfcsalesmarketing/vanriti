<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - {{ store_name() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <style>
        :root {
            --vanriti-green: #263D25;
            --vanriti-green-light: #6F8736;
            --vanriti-cream: #F7F4EA;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #F7F4EA 0%, #EEF2E3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(38, 61, 37, 0.15);
            background: #fff;
        }
        .brand-badge {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: var(--vanriti-green);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: 0.08em;
        }
        .form-control {
            padding: 0.7rem 0.9rem;
            border-radius: 0.5rem;
        }
        .form-control:focus {
            border-color: var(--vanriti-green-light);
            box-shadow: 0 0 0 0.2rem rgba(111, 135, 54, 0.15);
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="brand-badge mb-3">V</span>
                <h1 class="h4 mb-1 fw-bold text-dark">VANRITI Admin</h1>
                <p class="text-muted mb-0 small">Sign in to manage your store</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success py-2 small">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email Address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                           name="email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                           name="password" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label small" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>