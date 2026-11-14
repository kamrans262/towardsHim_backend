<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformContentController extends Controller
{
    public function edit(): View
    {
        return view('admin.content.edit', [
            'content' => PlatformContent::publicContent(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'welcome_heading' => ['required', 'string', 'max:180'],
            'introduction_text' => ['required', 'string', 'max:2000'],
            'about_information' => ['required', 'string', 'max:10000'],
        ]);

        PlatformContent::query()->updateOrCreate(['id' => 1], [
            'welcome_heading' => trim($input['welcome_heading']),
            'introduction_text' => trim($input['introduction_text']),
            'about_information' => trim($input['about_information']),
        ]);

        return back()->with('success', 'Welcome and About information saved.');
    }
}
