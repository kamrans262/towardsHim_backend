<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $query = User::query()->where('role', 'member');

        if (! empty($data['q'])) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $data['q']);
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }
        if (isset($data['status'])) {
            $query->where('is_active', $data['status'] === 'active');
        }

        return view('admin.members.index', [
            'members' => $query->latest()->paginate(20)->withQueryString(),
            'q' => $data['q'] ?? '',
            'status' => $data['status'] ?? '',
        ]);
    }

    public function show(User $member): View
    {
        abort_unless($member->role === 'member', 404);
        return view('admin.members.show', compact('member'));
    }

    public function toggleStatus(Request $request, User $member): RedirectResponse
    {
        abort_unless($member->role === 'member', 404);
        $request->validate(['is_active' => ['required', 'boolean']]);
        $active = $request->boolean('is_active');

        $member->forceFill(['is_active' => $active])->save();
        if (! $active) {
            $member->accessTokens()->delete();
        }

        return back()->with('success', $active ? 'Member activated.' : 'Member deactivated. Existing mobile sessions revoked.');
    }
}
