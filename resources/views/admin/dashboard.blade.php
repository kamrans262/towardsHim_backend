@extends('admin.layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
 <div><span class="text-muted">Published Weekly Challenges:</span> <strong>{{ $publishedChallenges }}</strong></div>
 <a href="{{ route('admin.challenges.index') }}" class="btn btn-outline-primary btn-sm">Manage Towards HIM 3</a>
</div>
<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Welcome back</h1>
    <p class="text-muted mb-0">Manage member accounts and platform access.</p>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-0"><div class="card-body p-4">
        <p class="text-muted mb-2">Total Members</p><h2 class="fw-bold mb-0">{{ number_format($members) }}</h2>
    </div></div></div>
    <div class="col-md-4"><div class="card border-0"><div class="card-body p-4">
        <p class="text-muted mb-2">Active Members</p><h2 class="fw-bold mb-0">{{ number_format($activeMembers) }}</h2>
    </div></div></div>
    <div class="col-md-4"><div class="card border-0"><div class="card-body p-4">
        <p class="text-muted mb-2">Inactive Members</p><h2 class="fw-bold mb-0">{{ number_format($inactiveMembers) }}</h2>
    </div></div></div>
</div>
<div class="row g-3 mb-4">
    @foreach (['podcasts' => 'podcast', 'meditations' => 'meditation', 'prayers' => 'prayer'] as $slug => $type)
    <div class="col-md-4">
        <a class="text-decoration-none" href="{{ route('admin.audio.index', $slug) }}">
            <div class="card border-0"><div class="card-body p-4">
                <p class="text-muted mb-2">{{ ucfirst($slug) }}</p>
                <h2 class="fw-bold mb-0">{{ number_format($audioCounts[$type] ?? 0) }}</h2>
            </div></div>
        </a>
    </div>
    @endforeach
</div>
<div class="card border-0">
    <div class="card-header bg-white d-flex align-items-center justify-content-between p-4">
        <h2 class="h5 fw-bold mb-0">Recent Members</h2>
        <a href="{{ route('admin.members.index') }}" class="btn btn-outline-primary btn-sm">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th><th></th></tr></thead>
                <tbody>
                    @forelse ($recentMembers as $member)
                        <tr>
                            <td class="fw-semibold">{{ $member->name }}</td>
                            <td>{{ $member->email ?? 'Apple private account' }}</td>
                            <td><span class="badge {{ $member->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $member->created_at->format('d M Y') }}</td>
                            <td><a href="{{ route('admin.members.show', $member) }}" class="btn btn-outline-primary btn-sm">Details</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No members registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
