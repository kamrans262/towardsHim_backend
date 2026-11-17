<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Sign In · Toward HIM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('admin-assets/css/core/libs.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/css/hope-ui.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/css/toward-admin.css') }}?v=20261118-collapsible-days">
</head>
<body class="toward-admin guest-page">
    <main class="container d-flex align-items-center justify-content-center min-vh-100 py-4">
        @yield('content')
    </main>
</body>
</html>
