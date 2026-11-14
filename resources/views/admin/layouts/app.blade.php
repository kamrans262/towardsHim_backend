<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · Toward HIM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('admin-assets/css/hope-ui.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/css/toward-admin.css') }}">
</head>
<body class="toward-admin">
<div class="d-flex min-vh-100 admin-shell">
    <aside class="sidebar sidebar-default sidebar-white sidebar-base navs-rounded-all admin-sidebar" id="adminSidebar">
        <div class="sidebar-header px-4 py-4">
            <a href="{{ route('admin.dashboard') }}" class="navbar-brand d-flex align-items-center gap-2 text-decoration-none">
                <img src="{{ asset('admin-assets/toward_him_logo.svg') }}" alt="Toward HIM" class="sidebar-logo">
                <span class="logo-title fw-bold">Toward HIM</span>
            </a>
        </div>
        <nav class="nav flex-column px-3" aria-label="Admin navigation">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a class="nav-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}" href="{{ route('admin.members.index') }}">Members</a>
            <div class="small text-muted fw-semibold mt-4 mb-2 px-3 text-uppercase">Coming in M2–M5</div>
            <span class="nav-link disabled">Podcasts</span>
            <span class="nav-link disabled">Meditations</span>
            <span class="nav-link disabled">Prayers</span>
            <span class="nav-link disabled">Towards HIM 3</span>
            <span class="nav-link disabled">Community</span>
            <span class="nav-link disabled">Welcome &amp; About</span>
        </nav>
        <div class="mt-auto p-4">
            <form method="POST" action="{{ route('admin.logout') }}">@csrf
                <button type="submit" class="btn btn-outline-primary w-100">Log Out</button>
            </form>
        </div>
    </aside>
    <div class="main-content flex-grow-1">
        <header class="admin-topbar navbar navbar-expand-lg bg-white border-bottom px-3 px-md-4 py-3">
            <button class="btn btn-outline-secondary d-lg-none me-3" type="button" data-bs-toggle="collapse" data-bs-target="#adminMobileSidebar" aria-expanded="false" aria-controls="adminMobileSidebar">Menu</button>
            <span class="fw-semibold fs-5">@yield('title', 'Admin Dashboard')</span>
            <span class="ms-auto text-muted small">Signed in as {{ auth()->user()->name }}</span>
        </header>
        <nav class="collapse d-lg-none bg-white p-3 border-bottom" id="adminMobileSidebar" aria-label="Mobile admin navigation">
            <a class="d-block py-2" href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a class="d-block py-2" href="{{ route('admin.members.index') }}">Members</a>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf <button class="btn btn-outline-primary mt-2">Log Out</button></form>
        </nav>
        <main class="container-fluid p-3 p-md-4">
            @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
