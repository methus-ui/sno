<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmployeeChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeChatController extends Controller
{
    protected $chatService;

    public function __construct(EmployeeChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Get all conversations for authenticated employee
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function conversations(Request $request)
    {
        try {
            $admin = auth('sanctum')->user();

            // Allow super admins (role_id = 1) or approved employees (status = 1)
            if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only approved admin employees can access chat.',
                ], 403);
            }

            // Get or create UserInfo
            $userInfo = $this->chatService->createOrGetUserInfo($admin);

            // Get conversations
            $conversations = $this->chatService->getConversations($userInfo->id);

            // Format response
            $formattedConversations = $conversations->map(function ($conversation) use ($userInfo) {
                // Determine the other participant
                $otherUser = $conversation->sender_id === $userInfo->id
                    ? $conversation->receiver
                    : $conversation->sender;

                return [
                    'id' => $conversation->id,
                    'participant' => [
                        'id' => $otherUser->id,
                        'name' => trim(($otherUser->f_name ?? '') . ' ' . ($otherUser->l_name ?? '')),
                        'email' => $otherUser->email,
                        'image' => $otherUser->image_full_url,
                    ],
                    'last_message' => $conversation->last_message ? [
                        'id' => $conversation->last_message->id,
                        'text' => $conversation->last_message->message,
                        'created_at' => $conversation->last_message->created_at->toIso8601String(),
                        'is_mine' => $conversation->last_message->sender_id === $userInfo->id,
                    ] : null,
                    'unread_count' => $conversation->unread_message_count ?? 0,
                    'last_message_time' => $conversation->last_message_time ?
                        \Carbon\Carbon::parse($conversation->last_message_time)->toIso8601String() : null,
                ];
            });

            return response()->json([
                'success' => true,
                'conversations' => $formattedConversations,
                'pagination' => [
                    'current_page' => $conversations->currentPage(),
                    'last_page' => $conversations->lastPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee chat - conversations error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get messages in a conversation
     *
     * @param Request $request
     * @param int $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function messages(Request $request, $conversationId)
    {
        try {
            $admin = auth('sanctum')->user();

            // Allow super admins (role_id = 1) or approved employees (status = 1)
            if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Get UserInfo
            $userInfo = $this->chatService->createOrGetUserInfo($admin);

            // Get messages
            $messages = $this->chatService->getMessages($conversationId, $userInfo->id);

            if ($messages === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found or access denied',
                ], 404);
            }

            // Mark as read
            $this->chatService->markAsRead($conversationId, $userInfo->id);

            // Format messages
            $formattedMessages = $messages->map(function ($message) use ($userInfo) {
                return [
                    'id' => $message->id,
                    'text' => $message->message,
                    'files' => $message->file ? json_decode($message->file, true) : [],
                    'is_mine' => $message->sender_id === $userInfo->id,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => trim(($message->sender->f_name ?? '') . ' ' . ($message->sender->l_name ?? '')),
                        'image' => $message->sender->image_full_url,
                    ],
                    'is_seen' => $message->is_seen == 1,
                    'created_at' => $message->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'messages' => $formattedMessages,
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee chat - messages error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load messages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a message
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'receiver_id' => 'required|integer|exists:user_infos,id',
                'message' => 'nullable|string|max:5000',
                'files' => 'nullable|array|max:5',
                'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $admin = auth('sanctum')->user();

            // Allow super admins (role_id = 1) or approved employees (status = 1)
            if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Get UserInfo
            $userInfo = $this->chatService->createOrGetUserInfo($admin);

            // Handle file uploads
            $uploadedFiles = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('chat-files', $filename, 'public');

                    $uploadedFiles[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'url' => asset('storage/' . $path),
                        'type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            // Send message
            $message = $this->chatService->sendMessage(
                $userInfo->id,
                $request->receiver_id,
                $request->message,
                $uploadedFiles
            );

            // TODO: Broadcast event for real-time (Phase 3)
            // broadcast(new EmployeeMessageSent($message, $request->receiver_id));

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'text' => $message->message,
                    'files' => $uploadedFiles,
                    'is_mine' => true,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => trim(($message->sender->f_name ?? '') . ' ' . ($message->sender->l_name ?? '')),
                        'image' => $message->sender->image_full_url,
                    ],
                    'is_seen' => false,
                    'created_at' => $message->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Employee chat - send error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Poll for new messages
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function poll(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'since' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid timestamp',
                ], 422);
            }

            $admin = auth('sanctum')->user();

            // Allow super admins (role_id = 1) or approved employees (status = 1)
            if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Get UserInfo
            $userInfo = $this->chatService->createOrGetUserInfo($admin);

            // Poll for new messages
            $result = $this->chatService->pollNewMessages($userInfo->id, $request->since);

            // Format messages
            $formattedMessages = $result['messages']->map(function ($message) use ($userInfo) {
                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'text' => $message->message,
                    'files' => $message->file ? json_decode($message->file, true) : [],
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => trim(($message->sender->f_name ?? '') . ' ' . ($message->sender->l_name ?? '')),
                        'image' => $message->sender->image_full_url,
                    ],
                    'created_at' => $message->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'messages' => $formattedMessages,
                'timestamp' => $result['timestamp'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee chat - poll error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Polling failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of available employees
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function employees(Request $request)
    {
        try {
            $admin = auth('sanctum')->user();

            // Allow super admins (role_id = 1) or approved employees (status = 1)
            if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Get UserInfo
            $userInfo = $this->chatService->createOrGetUserInfo($admin);

            // Get available employees
            $employees = $this->chatService->getAvailableEmployees($userInfo->id);

            return response()->json([
                'success' => true,
                'employees' => $employees,
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee chat - employees error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load employees',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $admin = auth('sanctum')->user();

            // Allow super admins (role_id = 1) or approved employees (status = 1)
            if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $file = $request->file('file');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('chat-files', $filename, 'public');

            return response()->json([
                'success' => true,
                'file' => [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee chat - upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
