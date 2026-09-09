<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ChatbotFaq;
use App\Models\LearnedKeyword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationLearningController extends Controller
{
    /**
     * Display the conversation learning dashboard.
     */
    public function index()
    {
        try {
            // Get conversation statistics
            $stats = [
                'total_conversations' => Conversation::count(),
                'total_messages' => Message::count(),
                'customer_messages' => 0,
                'learned_keywords' => LearnedKeyword::count(),
                'pending_suggestions' => LearnedKeyword::where('status', 'pending')->count(),
                'total_faqs' => ChatbotFaq::count(),
                'active_faqs' => ChatbotFaq::where('is_active', true)->count(),
            ];

            // Try to count customer messages (may fail if conversation table structure differs)
            try {
                $stats['customer_messages'] = Message::whereHas('conversation', function($q) {
                    $q->where('sender_type', 'customer');
                })->count();
            } catch (\Exception $e) {
                $stats['customer_messages'] = Message::count();
            }

            // Get recent unanswered questions (messages without responses)
            $unansweredQuestions = $this->getUnansweredQuestions(10);

            // Get learned keywords
            $learnedKeywords = LearnedKeyword::orderBy('frequency', 'desc')
                ->limit(20)
                ->get();

            // Get suggested FAQs (based on common question patterns)
            $suggestedFaqs = $this->getSuggestedFaqs(5);

            // Get recently created FAQs
            $recentFaqs = ChatbotFaq::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return view('admin-views.chatbot.conversation-learning', compact(
                'stats',
                'unansweredQuestions',
                'learnedKeywords',
                'suggestedFaqs',
                'recentFaqs'
            ));
        } catch (\Exception $e) {
            \Log::error('Conversation Learning index error: ' . $e->getMessage());
            return view('admin-views.chatbot.conversation-learning', [
                'stats' => [
                    'total_conversations' => 0,
                    'total_messages' => 0,
                    'customer_messages' => 0,
                    'learned_keywords' => 0,
                    'pending_suggestions' => 0,
                    'total_faqs' => 0,
                    'active_faqs' => 0,
                ],
                'unansweredQuestions' => [],
                'learnedKeywords' => collect([]),
                'suggestedFaqs' => [],
                'recentFaqs' => collect([])
            ]);
        }
    }

    /**
     * Analyze conversations and extract keywords/patterns.
     */
    public function analyze(Request $request)
    {
        $limit = $request->input('limit', 500);
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());

        // Get customer messages
        $messages = Message::with('conversation')
            ->whereHas('conversation', function($q) {
                $q->where('sender_type', 'customer');
            })
            ->where('created_at', '>=', $fromDate)
            ->whereNotNull('message')
            ->where('message', '!=', '')
            ->limit($limit)
            ->get();

        $keywordCounts = [];
        $questionPatterns = [];

        foreach ($messages as $message) {
            $text = strtolower($message->message);

            // Extract keywords
            $keywords = $this->extractMeaningfulKeywords($text);
            foreach ($keywords as $keyword) {
                if (!isset($keywordCounts[$keyword])) {
                    $keywordCounts[$keyword] = 0;
                }
                $keywordCounts[$keyword]++;
            }

            // Identify question patterns
            if ($this->isQuestion($text)) {
                $pattern = $this->normalizeQuestion($text);
                if (!isset($questionPatterns[$pattern])) {
                    $questionPatterns[$pattern] = [
                        'count' => 0,
                        'examples' => [],
                        'responses' => []
                    ];
                }
                $questionPatterns[$pattern]['count']++;
                if (count($questionPatterns[$pattern]['examples']) < 3) {
                    $questionPatterns[$pattern]['examples'][] = $message->message;
                }

                // Find admin responses to this question
                $response = $this->findAdminResponse($message);
                if ($response && !in_array($response, $questionPatterns[$pattern]['responses'])) {
                    $questionPatterns[$pattern]['responses'][] = $response;
                }
            }
        }

        // Save learned keywords
        arsort($keywordCounts);
        $savedKeywords = 0;
        foreach (array_slice($keywordCounts, 0, 100, true) as $keyword => $count) {
            if ($count >= 3) { // Only save keywords that appear at least 3 times
                LearnedKeyword::updateOrCreate(
                    ['keyword' => $keyword],
                    [
                        'frequency' => $count,
                        'status' => 'pending',
                        'last_seen_at' => now()
                    ]
                );
                $savedKeywords++;
            }
        }

        // Sort patterns by frequency
        uasort($questionPatterns, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return response()->json([
            'success' => true,
            'message' => translate('Analysis completed'),
            'stats' => [
                'messages_analyzed' => $messages->count(),
                'unique_keywords' => count($keywordCounts),
                'keywords_saved' => $savedKeywords,
                'question_patterns' => count($questionPatterns)
            ],
            'top_keywords' => array_slice($keywordCounts, 0, 20, true),
            'common_questions' => array_slice($questionPatterns, 0, 10, true)
        ]);
    }

    /**
     * Generate FAQ suggestions from conversation analysis.
     */
    public function generateFaqSuggestions(Request $request)
    {
        $suggestions = $this->getSuggestedFaqs(20);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Approve a learned keyword for FAQ generation.
     */
    public function approveKeyword(Request $request, $id)
    {
        $keyword = LearnedKeyword::findOrFail($id);
        $keyword->update(['status' => 'approved']);

        return response()->json([
            'success' => true,
            'message' => translate('Keyword approved')
        ]);
    }

    /**
     * Reject a learned keyword.
     */
    public function rejectKeyword(Request $request, $id)
    {
        $keyword = LearnedKeyword::findOrFail($id);
        $keyword->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => translate('Keyword rejected')
        ]);
    }

    /**
     * Create FAQ from suggestion.
     */
    public function createFaqFromSuggestion(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:2000',
            'keywords' => 'nullable|string|max:500'
        ]);

        $faq = ChatbotFaq::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'keywords' => $request->keywords,
            'priority' => 0,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => translate('FAQ created successfully'),
            'faq' => $faq
        ]);
    }

    /**
     * Get common unanswered questions.
     */
    private function getUnansweredQuestions($limit = 10)
    {
        try {
            // Find customer messages that look like questions
            $messages = Message::with(['conversation'])
                ->whereNotNull('message')
                ->where('message', '!=', '')
                ->where('message', 'LIKE', '%?%') // Only questions
                ->orderBy('created_at', 'desc')
                ->limit($limit * 5)
                ->get();

            // Filter to unique questions
            $uniqueQuestions = [];
            foreach ($messages as $message) {
                if (!$message->message) continue;

                $normalized = $this->normalizeQuestion($message->message);
                if (!isset($uniqueQuestions[$normalized])) {
                    $uniqueQuestions[$normalized] = [
                        'message' => $message->message,
                        'conversation_id' => $message->conversation_id ?? 0,
                        'created_at' => $message->created_at ?? now(),
                        'sender' => null
                    ];
                }
            }

            return array_slice(array_values($uniqueQuestions), 0, $limit);
        } catch (\Exception $e) {
            \Log::error('getUnansweredQuestions error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get suggested FAQs based on conversation patterns.
     */
    private function getSuggestedFaqs($limit = 5)
    {
        try {
            // Get most common question patterns using simple query
            $messages = Message::whereNotNull('message')
                ->where('message', '!=', '')
                ->where('message', 'LIKE', '%?%')
                ->select('message', DB::raw('COUNT(*) as count'))
                ->groupBy('message')
                ->having('count', '>=', 2)
                ->orderBy('count', 'desc')
                ->limit($limit * 3)
                ->get();

            $suggestions = [];
            foreach ($messages as $msg) {
                if (!$msg->message) continue;

                // Check if this question already exists as FAQ
                $existingFaq = ChatbotFaq::whereRaw('LOWER(question) = ?', [strtolower($msg->message)])->first();
                if (!$existingFaq) {
                    // Try to find admin responses
                    $response = $this->findAdminResponseForPattern($msg->message);

                    $suggestions[] = [
                        'question' => $msg->message,
                        'suggested_answer' => $response,
                        'frequency' => $msg->count ?? 1,
                        'keywords' => implode(', ', $this->extractMeaningfulKeywords($msg->message))
                    ];
                }
            }

            return array_slice($suggestions, 0, $limit);
        } catch (\Exception $e) {
            \Log::error('getSuggestedFaqs error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract meaningful keywords from text.
     */
    private function extractMeaningfulKeywords($text)
    {
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'must', 'shall', 'can', 'need', 'to', 'of', 'in', 'for', 'on',
            'with', 'at', 'by', 'from', 'as', 'into', 'and', 'but', 'or', 'nor', 'so',
            'not', 'only', 'i', 'me', 'my', 'you', 'your', 'he', 'she', 'it', 'we', 'they',
            'this', 'that', 'what', 'which', 'who', 'how', 'when', 'where', 'why',
            'hi', 'hello', 'hey', 'please', 'thanks', 'thank', 'sorry', 'want', 'know',
            'get', 'got', 'make', 'just', 'like', 'time', 'good', 'new', 'also', 'any'];

        $words = preg_split('/[\s\W]+/', strtolower($text));
        $keywords = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) >= 3 && !in_array($word, $stopWords) && !is_numeric($word)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    /**
     * Check if text is a question.
     */
    private function isQuestion($text)
    {
        $questionWords = ['what', 'where', 'when', 'why', 'how', 'can', 'could', 'would', 'will', 'do', 'does', 'is', 'are'];
        $text = strtolower(trim($text));

        // Contains question mark
        if (strpos($text, '?') !== false) {
            return true;
        }

        // Starts with question word
        foreach ($questionWords as $word) {
            if (strpos($text, $word . ' ') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize question for grouping similar questions.
     */
    private function normalizeQuestion($text)
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^\w\s]/', '', $text); // Remove punctuation
        $text = preg_replace('/\s+/', ' ', $text); // Normalize whitespace
        return $text;
    }

    /**
     * Find admin response to a message.
     */
    private function findAdminResponse($message)
    {
        try {
            if (!$message || !$message->conversation_id) {
                return null;
            }

            // Find the next message in the same conversation from a different sender
            $response = Message::where('conversation_id', $message->conversation_id)
                ->where('created_at', '>', $message->created_at)
                ->where('sender_id', '!=', $message->sender_id)
                ->whereNotNull('message')
                ->where('message', '!=', '')
                ->orderBy('created_at', 'asc')
                ->first();

            return $response ? $response->message : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Find admin response for a question pattern.
     */
    private function findAdminResponseForPattern($question)
    {
        try {
            if (empty($question)) {
                return null;
            }

            // Find a message with this question
            $message = Message::where('message', $question)
                ->whereNotNull('conversation_id')
                ->first();

            if ($message) {
                return $this->findAdminResponse($message);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract all question-answer pairs from old conversations.
     */
    public function extractQAPairs(Request $request)
    {
        $limit = $request->input('limit', 1000);
        $minResponseLength = $request->input('min_response_length', 20);
        $daysBack = $request->input('days_back', 90);

        $fromDate = now()->subDays($daysBack)->toDateString();

        try {
            // Get all conversations with messages
            $conversations = Conversation::with(['messages' => function($q) {
                    $q->whereNotNull('message')
                      ->where('message', '!=', '')
                      ->orderBy('created_at', 'asc');
                }])
                ->where('created_at', '>=', $fromDate)
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Extract Q&A pairs error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => translate('Error loading conversations: ') . $e->getMessage()
            ], 500);
        }

        $qaPairs = [];
        $processedQuestions = [];

        foreach ($conversations as $conversation) {
            $messages = $conversation->messages;

            for ($i = 0; $i < count($messages) - 1; $i++) {
                $currentMsg = $messages[$i];
                $nextMsg = $messages[$i + 1];

                // Check if current is customer question and next is admin response
                if ($currentMsg->sender_id != $nextMsg->sender_id) {
                    $question = trim($currentMsg->message);
                    $answer = trim($nextMsg->message);

                    // Validate the pair
                    if ($this->isValidQAPair($question, $answer, $minResponseLength)) {
                        $normalizedQ = $this->normalizeQuestion($question);

                        // Avoid duplicates
                        if (!isset($processedQuestions[$normalizedQ])) {
                            $processedQuestions[$normalizedQ] = true;

                            // Check if FAQ already exists
                            $existingFaq = ChatbotFaq::whereRaw('LOWER(question) LIKE ?', ['%' . strtolower(substr($question, 0, 50)) . '%'])->first();

                            if (!$existingFaq) {
                                $qaPairs[] = [
                                    'question' => $question,
                                    'answer' => $answer,
                                    'keywords' => implode(', ', $this->extractMeaningfulKeywords($question)),
                                    'conversation_id' => $conversation->id,
                                    'created_at' => $currentMsg->created_at->format('Y-m-d H:i')
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Sort by question length (shorter questions are usually more reusable)
        usort($qaPairs, function($a, $b) {
            return strlen($a['question']) - strlen($b['question']);
        });

        return response()->json([
            'success' => true,
            'message' => translate('Extracted Q&A pairs from conversations'),
            'total_conversations' => $conversations->count(),
            'qa_pairs' => array_slice($qaPairs, 0, 50), // Return top 50
            'total_pairs_found' => count($qaPairs)
        ]);
    }

    /**
     * Validate if a question-answer pair is suitable for FAQ.
     */
    private function isValidQAPair($question, $answer, $minResponseLength = 20)
    {
        // Question should be meaningful
        if (strlen($question) < 10) return false;
        if (strlen($question) > 500) return false;

        // Answer should be substantial
        if (strlen($answer) < $minResponseLength) return false;
        if (strlen($answer) > 2000) return false;

        // Question should look like a question
        if (!$this->isQuestion($question) && !$this->looksLikeInquiry($question)) {
            return false;
        }

        // Answer should not be just a greeting or short response
        $shortResponses = ['ok', 'okay', 'yes', 'no', 'sure', 'thanks', 'thank you', 'hi', 'hello', 'welcome'];
        if (in_array(strtolower(trim($answer)), $shortResponses)) {
            return false;
        }

        return true;
    }

    /**
     * Check if text looks like an inquiry/request.
     */
    private function looksLikeInquiry($text)
    {
        $inquiryPatterns = [
            'i want', 'i need', 'i would like', 'please', 'could you',
            'can you', 'help me', 'tell me', 'show me', 'give me',
            'looking for', 'interested in', 'need help', 'problem with',
            'issue with', 'not working', 'doesn\'t work', 'how to', 'how do'
        ];

        $text = strtolower($text);
        foreach ($inquiryPatterns as $pattern) {
            if (strpos($text, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bulk create FAQs from selected Q&A pairs.
     */
    public function bulkCreateFaqs(Request $request)
    {
        $pairs = $request->input('pairs', []);

        if (empty($pairs)) {
            return response()->json([
                'success' => false,
                'message' => translate('No Q&A pairs provided')
            ], 400);
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($pairs as $pair) {
            try {
                // Validate
                if (empty($pair['question']) || empty($pair['answer'])) {
                    $skipped++;
                    continue;
                }

                // Check for duplicate
                $existing = ChatbotFaq::whereRaw('LOWER(question) = ?', [strtolower($pair['question'])])->first();
                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Create FAQ
                ChatbotFaq::create([
                    'question' => $pair['question'],
                    'answer' => $pair['answer'],
                    'keywords' => $pair['keywords'] ?? implode(', ', $this->extractMeaningfulKeywords($pair['question'])),
                    'priority' => 0,
                    'is_active' => true
                ]);

                $created++;
            } catch (\Exception $e) {
                $errors[] = $pair['question'] . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => translate('FAQs created successfully'),
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors
        ]);
    }

    /**
     * Auto-generate FAQs from conversations (one-click).
     */
    public function autoGenerateFaqs(Request $request)
    {
        $maxFaqs = $request->input('max_faqs', 20);
        $minFrequency = $request->input('min_frequency', 2);

        try {
            // Get frequently asked questions with their responses
            $frequentQuestions = DB::table('messages as m1')
                ->join('conversations as c', 'm1.conversation_id', '=', 'c.id')
                ->where('c.sender_type', 'customer')
                ->whereNotNull('m1.message')
                ->where('m1.message', '!=', '')
                ->where(function($q) {
                    $q->where('m1.message', 'LIKE', '%?%')
                      ->orWhere('m1.message', 'LIKE', 'how %')
                      ->orWhere('m1.message', 'LIKE', 'what %')
                      ->orWhere('m1.message', 'LIKE', 'where %')
                      ->orWhere('m1.message', 'LIKE', 'when %')
                      ->orWhere('m1.message', 'LIKE', 'why %')
                      ->orWhere('m1.message', 'LIKE', 'can %')
                      ->orWhere('m1.message', 'LIKE', 'i need %')
                      ->orWhere('m1.message', 'LIKE', 'i want %');
                })
                ->select('m1.message', DB::raw('COUNT(*) as frequency'))
                ->groupBy('m1.message')
                ->having('frequency', '>=', $minFrequency)
                ->orderBy('frequency', 'desc')
                ->limit($maxFaqs * 2)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Auto-generate FAQs error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => translate('Error querying conversations: ') . $e->getMessage()
            ], 500);
        }

        $created = 0;
        $faqsCreated = [];

        foreach ($frequentQuestions as $fq) {
            if ($created >= $maxFaqs) break;

            $question = $fq->message;

            // Check if already exists
            $existing = ChatbotFaq::whereRaw('LOWER(question) = ?', [strtolower($question)])->first();
            if ($existing) continue;

            // Find the best answer
            $answer = $this->findBestAnswer($question);

            if ($answer && strlen($answer) >= 20) {
                $keywords = implode(', ', $this->extractMeaningfulKeywords($question));

                $faq = ChatbotFaq::create([
                    'question' => $question,
                    'answer' => $answer,
                    'keywords' => $keywords,
                    'priority' => min($fq->frequency, 10),
                    'is_active' => true
                ]);

                $faqsCreated[] = [
                    'id' => $faq->id,
                    'question' => $question,
                    'answer' => $answer,
                    'frequency' => $fq->frequency
                ];

                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => translate('Auto-generated FAQs from conversations'),
            'created' => $created,
            'faqs' => $faqsCreated
        ]);
    }

    /**
     * Find the best answer for a question from conversation history.
     */
    private function findBestAnswer($question)
    {
        try {
            if (empty($question)) {
                return null;
            }

            // Find all instances of this question
            $customerMessages = Message::where('message', $question)
                ->whereNotNull('conversation_id')
                ->limit(10)
                ->get();

            $answers = [];

            foreach ($customerMessages as $msg) {
                $response = $this->findAdminResponse($msg);
                if ($response && strlen($response) >= 20) {
                    // Score the answer
                    $score = strlen($response); // Longer answers usually better

                    // Boost if answer contains helpful keywords
                    $helpfulWords = ['please', 'thank', 'help', 'order', 'delivery', 'contact', 'support'];
                    foreach ($helpfulWords as $word) {
                        if (stripos($response, $word) !== false) {
                            $score += 10;
                        }
                    }

                    $answers[] = [
                        'text' => $response,
                        'score' => $score
                    ];
                }
            }

            if (empty($answers)) {
                return null;
            }

            // Sort by score and return best
            usort($answers, function($a, $b) {
                return $b['score'] - $a['score'];
            });

            return $answers[0]['text'];
        } catch (\Exception $e) {
            \Log::error('findBestAnswer error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get conversation history for a specific conversation (for review).
     */
    public function getConversationHistory($conversationId)
    {
        $conversation = Conversation::with(['messages' => function($q) {
                $q->orderBy('created_at', 'asc');
            }, 'sender'])
            ->findOrFail($conversationId);

        $qaPairs = [];
        $messages = $conversation->messages;

        for ($i = 0; $i < count($messages) - 1; $i++) {
            $currentMsg = $messages[$i];
            $nextMsg = $messages[$i + 1];

            if ($currentMsg->sender_id != $nextMsg->sender_id) {
                $qaPairs[] = [
                    'question' => $currentMsg->message,
                    'answer' => $nextMsg->message,
                    'question_time' => $currentMsg->created_at,
                    'answer_time' => $nextMsg->created_at
                ];
            }
        }

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
            'qa_pairs' => $qaPairs
        ]);
    }
}
