<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Nunito', sans-serif;
        }
        .logo-title svg {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body class="bg-light text-dark d-flex align-items-center min-vh-100">

    <div class="container text-center">
        <div class="logo-title mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="400" height="120" viewBox="0 0 400 120">
                <defs>
                    <linearGradient id="bissGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#38b6ff" />
                        <stop offset="100%" stop-color="#0077b6" />
                    </linearGradient>
                </defs>
                <text 
                    x="50%" 
                    y="50%" 
                    text-anchor="middle" 
                    dominant-baseline="central" 
                    font-family="Segoe UI, Helvetica, Arial, sans-serif" 
                    font-size="110" 
                    font-weight="bold" 
                    fill="url(#bissGradient)">
                    Biss
                </text>
            </svg>
        </div>

        <p class="lead">A Business App built by BSM Accounting</p>

        <div class="mt-4">
            <a href="{{ route('login') }}" class="btn btn-primary px-4">Log in</a>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
