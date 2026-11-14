@extends('admin.layouts.app')
@section('title', 'Member Details')

@section('content')
<a href="{{ route('admin.members.index') }}" class="d-inline-block mb-3 text-decoration-none">← Back to Members</a>
<div class="card border-0" style="max-width:720px">
    <div class="card-header bg-white p-4"><h1 class="h4 mb-0 fw-bold">Member Details</h1></div>
    <div class="card-body p-4">
        <dl class="row mb-4">
            <dt class="col-sm-4">Full Name</dt><dd class="col-sm-8">{{ $member->name }}</dd>
            <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $member->email ?? 'Not provided' }}</dd>
            <dt class="col-sm-4">Bio</dt><dd class="col-sm-8">{{ $member->bio ?: '—' }}</dd>
            <dt class="col-sm-4">Joined</dt><dd class="col-sm-8">{{ $member->created_at->format('d M Y, h:i A') }}</dd>
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8"><span class="badge {{ $member->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></dd>
        </dl>
        <form method="POST" action="{{ route('admin.members.status', $member) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="is_active" value="{{ $member->is_active ? 0 : 1 }}">
            <button class="btn {{ $member->is_active ? 'btn-outline-danger' : 'btn-primary' }}" type="submit"
                onclick="return confirm('Are you sure you want to {{ $member->is_active ? 'deactivate' : 'activate' }} this member?')">
                {{ $member->is_active ? 'Deactivate Member' : 'Activate Member' }}
            </button>
        </form>
    </div>
</div>
@endsection
