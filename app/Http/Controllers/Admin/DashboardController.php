<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'members' => User::where('role', 'member')->count(),
            'activeMembers' => User::where('role', 'member')->where('is_active', true)->count(),
            'inactiveMembers' => User::where('role', 'member')->where('is_active', false)->count(),
            'recentMembers' => User::where('role', 'member')->latest()->limit(5)->get(),
        ]);
    }
}
