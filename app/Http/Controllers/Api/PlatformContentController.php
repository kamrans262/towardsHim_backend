<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformContent;
use Illuminate\Http\JsonResponse;

class PlatformContentController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['data' => PlatformContent::publicContent()]);
    }
}
