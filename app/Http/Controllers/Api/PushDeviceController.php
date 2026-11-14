<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberPushDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushDeviceController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $input = $request->validate([
            'token' => ['required', 'string', 'min:20', 'max:512'],
            'platform' => ['required', 'in:android,ios'],
        ]);
        MemberPushDevice::query()->updateOrCreate(
            ['token_hash' => hash('sha256', $input['token'])],
            ['user_id' => $request->user()->id, 'token' => $input['token'], 'platform' => $input['platform']]
        );

        return response()->json(['message' => 'Push device registered.']);
    }

    public function unregister(Request $request): JsonResponse
    {
        $input = $request->validate(['token' => ['required', 'string', 'max:512']]);
        MemberPushDevice::where('user_id', $request->user()->id)
            ->where('token_hash', hash('sha256', $input['token']))->delete();

        return response()->json(['message' => 'Device unregistered.']);
    }
}
