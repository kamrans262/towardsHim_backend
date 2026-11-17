<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LoginOtpService;
use App\Services\MemberTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginOtpController extends Controller
{
    public function verify(Request $request, LoginOtpService $otp, MemberTokenService $tokens): JsonResponse
    {
        $validated = $request->validate([
            'challenge' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]+\z/'],
            'code' => ['required', 'digits:6'],
        ]);

        return response()->json([
            'data' => $otp->verify($validated['challenge'], $validated['code'], $tokens),
        ]);
    }

    public function resend(Request $request, LoginOtpService $otp): JsonResponse
    {
        $validated = $request->validate([
            'challenge' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]+\z/'],
        ]);

        return response()->json(['data' => $otp->resend($validated['challenge'])]);
    }
}
