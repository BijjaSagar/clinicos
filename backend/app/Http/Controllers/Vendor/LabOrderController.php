<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * External vendor lab-order integration controller.
 * Stub implementation — full vendor integration pending.
 */
class LabOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => [], 'total' => 0]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'External lab vendor integration not yet configured.',
        ], 501);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'External lab vendor integration not yet configured.',
        ], 501);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'External lab vendor integration not yet configured.',
        ], 501);
    }

    public function uploadResult(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'External lab vendor integration not yet configured.',
        ], 501);
    }

    public function sendResult(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'External lab vendor integration not yet configured.',
        ], 501);
    }
}
