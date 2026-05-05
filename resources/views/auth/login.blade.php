<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Project Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eff5ff 0%, #f7fbff 45%, #eefaf4 100%);
            display: grid;
            place-items: center;
        }
        .login-card {
            width: 100%;
            max-width: 430px;
            border: 0;
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(31, 61, 120, 0.14);
        }
        .brand-badge {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            background: linear-gradient(135deg, #2f6bff, #31b36b);
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 24px;
            margin-bottom: 16px;
        }
        .form-control {
            min-height: 48px;
            border-radius: 14px;
        }
        .btn-primary {
            min-height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #2f6bff, #2458d4);
            border: 0;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <div class="brand-badge">PM</div>
            <h1 class="h3 mb-2">Sign in</h1>
            <p class="text-muted mb-4">Use an existing user from the users table to access the system.</p>

            @if ($errors->any())
                <div class="alert alert-danger rounded-4">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
