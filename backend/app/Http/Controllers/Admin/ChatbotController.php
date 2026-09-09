<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFaq;
use App\Models\ChatbotSetting;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    /**
     * Display chatbot settings page.
     */
    public function settings()
    {
        $settings = ChatbotSetting::getAllSettings();
        return view('admin-views.chatbot.settings', compact('settings'));
    }

    /**
     * Update chatbot settings.
     */
    public function updateSettings(Request $request)
    {
        $settingsToUpdate = [
            'chatbot_enabled' => $request->has('chatbot_enabled') ? 'true' : 'false',
            'ai_fallback_enabled' => $request->has('ai_fallback_enabled') ? 'true' : 'false',
            'ai_provider' => $request->input('ai_provider', 'openai'),
            'ai_api_key' => $request->input('ai_api_key'),
            'ai_model' => $request->input('ai_model', 'gpt-4o-mini'),
            'confidence_threshold' => $request->input('confidence_threshold', '0.7'),
            'auto_response_delay' => $request->input('auto_response_delay', '2'),
            'welcome_message' => $request->input('welcome_message', ''),
            'fallback_message' => $request->input('fallback_message', ''),
        ];

        foreach ($settingsToUpdate as $key => $value) {
            if ($value !== null) {
                ChatbotSetting::set($key, $value);
            }
        }

        ChatbotSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => translate('Chatbot settings updated successfully')
        ]);
    }

    /**
     * Display FAQ list.
     */
    public function faqIndex(Request $request)
    {
        $query = ChatbotFaq::query();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%")
                  ->orWhere('keywords', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status == '1');
        }

        $faqs = $query->orderBy('priority', 'desc')
                      ->orderBy('created_at', 'desc')
                      ->paginate(15);

        $categories = Category::where('position', 0)->orderBy('name')->get();

        return view('admin-views.chatbot.faq-index', compact('faqs', 'categories'));
    }

    /**
     * Store a new FAQ.
     */
    public function faqStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:2000',
            'keywords' => 'nullable|string|max:500',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $faq = ChatbotFaq::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'keywords' => $request->keywords,
            'category_id' => $request->category_id,
            'priority' => $request->priority ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => translate('FAQ created successfully'),
            'faq' => $faq
        ]);
    }

    /**
     * Get FAQ for editing.
     */
    public function faqEdit($id)
    {
        $faq = ChatbotFaq::findOrFail($id);
        return response()->json(['faq' => $faq]);
    }

    /**
     * Update an existing FAQ.
     */
    public function faqUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:2000',
            'keywords' => 'nullable|string|max:500',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $faq = ChatbotFaq::findOrFail($id);
        $faq->update([
            'question' => $request->question,
            'answer' => $request->answer,
            'keywords' => $request->keywords,
            'category_id' => $request->category_id,
            'priority' => $request->priority ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => translate('FAQ updated successfully'),
            'faq' => $faq
        ]);
    }

    /**
     * Delete an FAQ.
     */
    public function faqDelete($id)
    {
        $faq = ChatbotFaq::findOrFail($id);
        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => translate('FAQ deleted successfully')
        ]);
    }

    /**
     * Toggle FAQ status.
     */
    public function faqStatus($id, $status)
    {
        $faq = ChatbotFaq::findOrFail($id);
        $faq->update(['is_active' => $status == 1]);

        return response()->json([
            'success' => true,
            'message' => translate('FAQ status updated successfully')
        ]);
    }

    /**
     * Public chatbot response endpoint for customer-facing widget.
     */
    public function getPublicResponse(Request $request)
    {
        return $this->getResponse($request);
    }

    /**
     * Get chatbot response for a message (API endpoint for chat widget).
     */
    public function getResponse(Request $request)
    {
        if (!ChatbotSetting::isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Chatbot is disabled'
            ], 403);
        }

        $message = strtolower(trim($request->input('message', '')));

        if (empty($message)) {
            return response()->json([
                'success' => false,
                'message' => 'Message is required'
            ], 400);
        }

        // Search for matching FAQ
        $faq = $this->findMatchingFaq($message);

        if ($faq) {
            $faq->recordHit();
            return response()->json([
                'success' => true,
                'response' => $faq->answer,
                'source' => 'faq',
                'faq_id' => $faq->id
            ]);
        }

        // Check if AI fallback is enabled
        if (ChatbotSetting::isAiFallbackEnabled()) {
            $aiResponse = $this->getAiResponse($message);
            if ($aiResponse) {
                return response()->json([
                    'success' => true,
                    'response' => $aiResponse,
                    'source' => 'ai'
                ]);
            }
        }

        // Return fallback message
        $fallbackMessage = ChatbotSetting::get('fallback_message',
            "I'm sorry, I couldn't find an answer to your question. Please contact our support team for assistance.");

        return response()->json([
            'success' => true,
            'response' => $fallbackMessage,
            'source' => 'fallback'
        ]);
    }

    /**
     * Find matching FAQ based on message keywords.
     */
    private function findMatchingFaq(string $message): ?ChatbotFaq
    {
        $words = explode(' ', $message);

        // First try exact question match
        $faq = ChatbotFaq::active()
            ->whereRaw('LOWER(question) = ?', [$message])
            ->first();

        if ($faq) {
            return $faq;
        }

        // Try keyword matching
        $faqs = ChatbotFaq::active()->byPriority()->get();
        $bestMatch = null;
        $bestScore = 0;
        $threshold = ChatbotSetting::getConfidenceThreshold();

        foreach ($faqs as $faq) {
            $keywords = $faq->keywords_array;
            if (empty($keywords)) {
                continue;
            }

            $matchCount = 0;
            foreach ($keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $matchCount++;
                }
            }

            $score = count($keywords) > 0 ? $matchCount / count($keywords) : 0;

            if ($score >= $threshold && $score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $faq;
            }
        }

        return $bestMatch;
    }

    /**
     * Get AI response using configured provider.
     */
    private function getAiResponse(string $message): ?string
    {
        $provider = ChatbotSetting::get('ai_provider', 'openai');
        $apiKey = ChatbotSetting::get('ai_api_key');
        $model = ChatbotSetting::get('ai_model', 'gpt-4o-mini');

        if (empty($apiKey)) {
            return null;
        }

        try {
            if ($provider === 'openai') {
                return $this->getOpenAiResponse($message, $apiKey, $model);
            } elseif ($provider === 'gemini') {
                return $this->getGeminiResponse($message, $apiKey, $model);
            }
        } catch (\Exception $e) {
            \Log::error('Chatbot AI Error: ' . $e->getMessage());
            return null;
        }

        return null;
    }

    /**
     * Get response from OpenAI.
     */
    private function getOpenAiResponse(string $message, string $apiKey, string $model): ?string
    {
        $systemPrompt = "You are a helpful customer support assistant. Provide concise, friendly responses. " .
            "If you don't know something specific about the business, suggest contacting support directly.";

        $response = \Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 300,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? null;
        }

        return null;
    }

    /**
     * Get response from Google Gemini.
     */
    private function getGeminiResponse(string $message, string $apiKey, string $model): ?string
    {
        $systemPrompt = "You are a helpful customer support assistant. Provide concise, friendly responses. " .
            "If you don't know something specific about the business, suggest contacting support directly.";

        // Map model names to API endpoints
        $modelMap = [
            'gemini-pro' => 'gemini-pro',
            'gemini-1.5-flash' => 'gemini-2.0-flash',
            'gemini-1.5-pro' => 'gemini-2.0-flash',
            'gemini-2.0-flash' => 'gemini-2.0-flash',
        ];

        $apiModel = $modelMap[$model] ?? 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$apiModel}:generateContent?key={$apiKey}";

        $response = \Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nUser message: " . $message]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 300,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        \Log::error('Gemini API Error: ' . $response->body());
        return null;
    }

    /**
     * Get popular FAQs for quick replies.
     */
    public function getPopularFaqs()
    {
        $faqs = ChatbotFaq::active()
            ->popular()
            ->limit(5)
            ->get(['id', 'question']);

        return response()->json(['faqs' => $faqs]);
    }

    /**
     * Dashboard stats for chatbot.
     */
    public function stats()
    {
        $totalFaqs = ChatbotFaq::count();
        $activeFaqs = ChatbotFaq::active()->count();
        $totalHits = ChatbotFaq::sum('hit_count');
        $topFaqs = ChatbotFaq::active()->popular()->limit(10)->get();

        return response()->json([
            'total_faqs' => $totalFaqs,
            'active_faqs' => $activeFaqs,
            'total_hits' => $totalHits,
            'top_faqs' => $topFaqs
        ]);
    }

    /**
     * Suggest templates based on incoming message content.
     * Analyzes the message and returns matching templates.
     */
    public function suggestTemplates(Request $request)
    {
        $message = strtolower(trim($request->input('message', '')));

        if (empty($message)) {
            return response()->json(['templates' => []]);
        }

        // Get all active templates
        $templates = \App\Models\MessageTemplate::where('is_active', true)->get();

        if ($templates->isEmpty()) {
            return response()->json(['templates' => []]);
        }

        // Extract keywords from message
        $messageWords = $this->extractKeywords($message);

        // Score each template based on keyword matches
        $scoredTemplates = [];

        foreach ($templates as $template) {
            $templateText = strtolower($template->title . ' ' . $template->content);
            $templateWords = $this->extractKeywords($templateText);

            // Calculate match score
            $matchCount = 0;
            foreach ($messageWords as $word) {
                if (strlen($word) >= 3) { // Only consider words with 3+ chars
                    foreach ($templateWords as $tWord) {
                        if (stripos($tWord, $word) !== false || stripos($word, $tWord) !== false) {
                            $matchCount++;
                            break;
                        }
                    }
                }
            }

            if ($matchCount > 0) {
                $scoredTemplates[] = [
                    'template' => $template,
                    'score' => $matchCount
                ];
            }
        }

        // Sort by score descending
        usort($scoredTemplates, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        // Return top 3 templates
        $topTemplates = array_slice($scoredTemplates, 0, 3);
        $result = array_map(function($item) {
            return [
                'id' => $item['template']->id,
                'title' => $item['template']->title,
                'content' => $item['template']->content,
                'score' => $item['score']
            ];
        }, $topTemplates);

        return response()->json(['templates' => $result]);
    }

    /**
     * Extract meaningful keywords from text.
     */
    private function extractKeywords(string $text): array
    {
        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'must', 'shall', 'can', 'need', 'dare', 'ought', 'used',
            'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 'from', 'as', 'into',
            'through', 'during', 'before', 'after', 'above', 'below', 'between',
            'and', 'but', 'or', 'nor', 'so', 'yet', 'both', 'either', 'neither',
            'not', 'only', 'own', 'same', 'than', 'too', 'very', 'just', 'also',
            'i', 'me', 'my', 'you', 'your', 'he', 'she', 'it', 'we', 'they',
            'this', 'that', 'these', 'those', 'what', 'which', 'who', 'whom',
            'hi', 'hello', 'hey', 'please', 'thanks', 'thank', 'sorry'];

        // Split into words and filter
        $words = preg_split('/[\s\W]+/', $text);
        $keywords = [];

        foreach ($words as $word) {
            $word = trim(strtolower($word));
            if (strlen($word) >= 2 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    /**
     * Get AI-generated smart reply suggestions based on conversation context.
     */
    public function getSmartReplies(Request $request)
    {
        $message = $request->input('message', '');
        $conversationContext = $request->input('context', '');

        if (empty($message)) {
            return response()->json(['replies' => []]);
        }

        // First, try to find matching FAQ
        $faq = $this->findMatchingFaq(strtolower($message));
        if ($faq) {
            return response()->json([
                'replies' => [
                    ['text' => $faq->answer, 'source' => 'faq', 'confidence' => 'high']
                ]
            ]);
        }

        // If AI is enabled, generate suggestions
        if (ChatbotSetting::isAiFallbackEnabled()) {
            $aiResponse = $this->getAiSuggestion($message, $conversationContext);
            if ($aiResponse) {
                return response()->json([
                    'replies' => [
                        ['text' => $aiResponse, 'source' => 'ai', 'confidence' => 'medium']
                    ]
                ]);
            }
        }

        // Return template-based suggestions as fallback
        $templateSuggestions = $this->suggestTemplates($request);
        $templates = json_decode($templateSuggestions->getContent(), true)['templates'] ?? [];

        $replies = array_map(function($t) {
            return ['text' => $t['content'], 'source' => 'template', 'confidence' => 'low'];
        }, array_slice($templates, 0, 2));

        return response()->json(['replies' => $replies]);
    }

    /**
     * Get AI suggestion for admin reply.
     */
    private function getAiSuggestion(string $customerMessage, string $context = ''): ?string
    {
        $provider = ChatbotSetting::get('ai_provider', 'openai');
        $apiKey = ChatbotSetting::get('ai_api_key');
        $model = ChatbotSetting::get('ai_model', 'gpt-4o-mini');

        if (empty($apiKey)) {
            return null;
        }

        $prompt = "You are a helpful customer support agent. Based on this customer message, suggest a professional and helpful reply. Keep it concise and friendly.\n\n";
        if ($context) {
            $prompt .= "Previous context: {$context}\n\n";
        }
        $prompt .= "Customer message: {$customerMessage}\n\nSuggested reply:";

        try {
            if ($provider === 'gemini') {
                return $this->getGeminiResponse($prompt, $apiKey, $model);
            } else {
                return $this->getOpenAiResponse($prompt, $apiKey, $model);
            }
        } catch (\Exception $e) {
            \Log::error('AI Suggestion Error: ' . $e->getMessage());
            return null;
        }
    }
}
