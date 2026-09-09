<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeminiController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Generate notification content using Gemini AI
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateNotification(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|min:3|max:500',
            'zone_id' => 'nullable|string',
            'target' => 'nullable|string|in:customer,deliveryman,store',
        ]);

        $prompt = $request->input('prompt');
        $zoneId = $request->input('zone_id');
        $target = $request->input('target', 'customer');

        // Get zone name if zone_id is provided and not 'all'
        $zoneName = null;
        if ($zoneId && $zoneId !== 'all') {
            $zone = \App\Models\Zone::find($zoneId);
            $zoneName = $zone ? $zone->name : null;
        }

        $result = $this->geminiService->generateNotificationContent($prompt, $zoneName, $target);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'title' => $result['title'],
                'description' => $result['description'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to generate notification content',
        ], 422);
    }
}
