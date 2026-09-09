<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIProductService
{
    private $provider;
    private $openaiKey;
    private $geminiKey;

    public function __construct()
    {
        $this->provider = config('ai.default_provider', 'gemini');
        $this->openaiKey = config('ai.openai_api_key');
        $this->geminiKey = config('ai.gemini_api_key');
    }

    /**
     * Generate product details using AI
     */
    public function generateProductDetails($productName, $category = null, $provider = null)
    {
        $provider = $provider ?? $this->provider;

        $prompt = $this->buildPrompt($productName, $category);

        try {
            if ($provider === 'openai') {
                return $this->generateWithOpenAI($prompt);
            } else {
                return $this->generateWithGemini($prompt);
            }
        } catch (Exception $e) {
            Log::error('AI Product Generation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error generating product details: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build the prompt for product generation
     */
    private function buildPrompt($productName, $category = null)
    {
        $categoryText = $category ? " in the {$category} category" : "";

        return "Generate product details for: \"{$productName}\"{$categoryText}.

Please provide the following in JSON format:
{
    \"name\": \"Clean product name\",
    \"description\": \"A detailed product description (150-200 words) highlighting features, benefits, and uses\",
    \"short_description\": \"A brief one-line description (under 50 words)\",
    \"tags\": [\"tag1\", \"tag2\", \"tag3\", \"tag4\", \"tag5\"],
    \"suggested_price_range\": {\"min\": 0, \"max\": 0, \"currency\": \"USD\"},
    \"features\": [\"feature1\", \"feature2\", \"feature3\"],
    \"ingredients\": \"List main ingredients if applicable, or null\",
    \"usage_instructions\": \"How to use the product, or null if not applicable\",
    \"storage_info\": \"Storage instructions if applicable, or null\"
}

Only respond with valid JSON, no additional text.";
    }

    /**
     * Generate using OpenAI (GPT-4 or GPT-3.5)
     */
    private function generateWithOpenAI($prompt)
    {
        if (empty($this->openaiKey)) {
            return [
                'success' => false,
                'message' => 'OpenAI API key not configured'
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model' => config('ai.openai_model', 'gpt-3.5-turbo'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful product catalog assistant. Always respond with valid JSON only.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.7
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if ($content) {
                // Parse JSON response
                $jsonContent = $this->extractJson($content);
                if ($jsonContent) {
                    return [
                        'success' => true,
                        'provider' => 'openai',
                        'data' => $jsonContent
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Invalid response format from OpenAI'
            ];
        }

        return [
            'success' => false,
            'message' => 'OpenAI API error: ' . ($response->json()['error']['message'] ?? 'Unknown error')
        ];
    }

    /**
     * Generate using Google Gemini
     */
    private function generateWithGemini($prompt)
    {
        if (empty($this->geminiKey)) {
            return [
                'success' => false,
                'message' => 'Gemini API key not configured'
            ];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->geminiKey;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($content) {
                // Parse JSON response
                $jsonContent = $this->extractJson($content);
                if ($jsonContent) {
                    return [
                        'success' => true,
                        'provider' => 'gemini',
                        'data' => $jsonContent
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Invalid response format from Gemini'
            ];
        }

        return [
            'success' => false,
            'message' => 'Gemini API error: ' . ($response->json()['error']['message'] ?? 'Unknown error')
        ];
    }

    /**
     * Extract JSON from response text
     */
    private function extractJson($text)
    {
        // Try to find JSON in the response
        $text = trim($text);

        // Remove markdown code blocks if present
        $text = preg_replace('/^```json\s*/i', '', $text);
        $text = preg_replace('/^```\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        // Try to decode
        $decoded = json_decode($text, true);
        if ($decoded !== null) {
            return $decoded;
        }

        // Try to find JSON object in text
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Check if AI service is configured
     */
    public function isConfigured($provider = null)
    {
        $provider = $provider ?? $this->provider;

        if ($provider === 'openai') {
            return !empty($this->openaiKey);
        } else {
            return !empty($this->geminiKey);
        }
    }

    /**
     * Get available providers
     */
    public function getAvailableProviders()
    {
        $providers = [];

        if (!empty($this->geminiKey)) {
            $providers[] = [
                'id' => 'gemini',
                'name' => 'Google Gemini',
                'default' => $this->provider === 'gemini'
            ];
        }

        if (!empty($this->openaiKey)) {
            $providers[] = [
                'id' => 'openai',
                'name' => 'OpenAI (ChatGPT)',
                'default' => $this->provider === 'openai'
            ];
        }

        return $providers;
    }
}
