@extends('admin.layouts.app')
@section('title', 'Members')

@section('content')
<div class="card border-0">
    <div class="card-header bg-white p-4">
        <h1 class="h4 fw-bold mb-3">Members</h1>
        <form method="GET" action="{{ route('admin.members.index') }}" class="row g-2">
            <div class="col-md-6">
                <input class="form-control" name="q" placeholder="Search name or email" value="{{ $q }}" maxlength="100">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Search</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.members.index') }}">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th><th></th></tr></thead>
            <tbody>
                @forelse ($members as $member)
                <tr>
                    <td class="fw-semibold">{{ $member->name }}</td>
                    <td>{{ $member->email ?? 'Not supplied' }}</td>
                    <td><span class="badge {{ $member->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>{{ $member->created_at->format('d M Y') }}</td>
                    <td><a href="{{ route('admin.members.show', $member) }}" class="btn btn-outline-primary btn-sm">View Details</a></td>
                </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No members found.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
    @if ($members->hasPages())<div class="card-footer bg-white p-3">{{ $members->links() }}</div>@endif
</div>
@endsection
