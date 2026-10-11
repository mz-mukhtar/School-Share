<?php

namespace App\Http\Controllers;

use App\DeploymentHealth;
use Illuminate\Http\JsonResponse;

class ReadinessController extends Controller
{
    public function __invoke(DeploymentHealth $health): JsonResponse
    {
        $ready = ! in_array(false, $health->checks(), true);

        return response()->json(['status' => $ready ? 'ok' : 'unavailable'], $ready ? 200 : 503)
            ->header('Cache-Control', 'no-store');
    }
}
