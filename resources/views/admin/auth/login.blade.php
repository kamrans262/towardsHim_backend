@extends('admin.layouts.guest')

@section('content')
<div class="card auth-card border-0 shadow-sm overflow-hidden" style="max-width:480px;width:100%">
    <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
            <img src="{{ asset('admin-assets/toward_him_logo.svg') }}" class="admin-login-logo" alt="Toward HIM">
            <h1 class="h3 fw-bold mt-3">Admin Sign In</h1>
            <p class="text-muted">Manage your Toward HIM community</p>
        </div>
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('admin.login') }}" autocomplete="on">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input id="email" type="email" class="form-control form-control-lg" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" maxlength="255">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" class="form-control form-control-lg" name="password" required autocomplete="current-password">
            </div>
            <label class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" value="1">
                <span class="form-check-label">Remember me</span>
            </label>
            <button class="btn btn-primary w-100 py-3" type="submit">Sign In</button>
        </form>
        <p class="text-center small text-muted mt-4 mb-0">Administrator access only</p>
    </div>
</div>
@endsection
