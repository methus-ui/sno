<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $model;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-2.0-flash');
    }

    /**
     * Generate notification content based on user prompt and context
     *
     * @param string $prompt User's description of the notification
     * @param string|null $zone Zone name (null for all zones)
     * @param string $targetAudience Target audience (customer, deliveryman, store)
     * @return array
     */
    public function generateNotificationContent(string $prompt, ?string $zone = null, string $targetAudience = 'customer'): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Gemini API key is not configured. Please add GEMINI_API_KEY to your .env file.',
            ];
        }

        $zoneContext = $zone ? "for the {$zone} zone" : "for all zones";
        $audienceLabels = [
            'customer' => 'customers',
            'deliveryman' => 'delivery partners/drivers',
            'store' => 'store owners/vendors',
        ];
        $audience = $audienceLabels[$targetAudience] ?? 'customers';

        $systemPrompt = "You're a marketing expert for SnoCart - quick commerce app (groceries, medicines, bakery, dry fruits, parcels). Style: Blinkit/Zepto/Swiggy inspired.

Rules: Human & friendly tone, witty wordplay, 1-2 emojis max, create FOMO, snappy text.
Target: {$audience} {$zoneContext}

Examples:
- '🌧️ Rainy day? Samosas + chai = sorted!'
- 'Forgot milk again? We got you. 10 mins flat.'

Return JSON only: {\"title\": \"max 50 chars\", \"description\": \"max 150 chars\"}";

        $userMessage = "{$prompt}";

        try {
            $response = Http::timeout(30)->post(
                "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt . "\n\n" . $userMessage]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 1024,
                    ]
                ]
            );

            if ($response->failed()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Unknown API error';
                Log::error('Gemini API error', ['response' => $errorBody]);
                return [
                    'success' => false,
                    'error' => "Gemini API error: {$errorMessage}",
                ];
            }

            $data = $response->json();
            $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$generatedText) {
                return [
                    'success' => false,
                    'error' => 'No content generated from Gemini API',
                ];
            }

            // Clean up the response - remove markdown code blocks if present
            $generatedText = trim($generatedText);
            // Remove opening ```json or ``` with any whitespace/newlines
            $generatedText = preg_replace('/^```(?:json)?\s*/is', '', $generatedText);
            // Remove closing ```
            $generatedText = preg_replace('/\s*```\s*$/is', '', $generatedText);
            $generatedText = trim($generatedText);

            // Try to extract JSON object if there's extra text
            $firstBrace = strpos($generatedText, '{');
            $lastBrace = strrpos($generatedText, '}');
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $generatedText = substr($generatedText, $firstBrace, $lastBrace - $firstBrace + 1);
            }

            $parsed = json_decode($generatedText, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($parsed['title']) || !isset($parsed['description'])) {
                Log::warning('Gemini response parsing failed', ['text' => $generatedText]);
                return [
                    'success' => false,
                    'error' => 'Failed to parse AI response. Please try again.',
                ];
            }

            return [
                'success' => true,
                'title' => $parsed['title'],
                'description' => $parsed['description'],
            ];

        } catch (\Exception $e) {
            Log::error('Gemini service exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Failed to connect to Gemini API: ' . $e->getMessage(),
            ];
        }
    }
}
