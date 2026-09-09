<?php

use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\EmployeeChatController;
use App\Http\Controllers\Api\V1\Admin\UnifiedChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee & Customer Chat API Routes
|--------------------------------------------------------------------------
|
| React-based unified chat system for admin employees
| - Employee-to-employee conversations
| - Admin-to-customer conversations
| Authentication: Laravel Sanctum (Bearer token)
|
*/

// Auth endpoints (uses admin session)
Route::group(['prefix' => 'admin'], function () {
    Route::get('auth/token', [AuthController::class, 'token'])
        ->middleware('auth:admin')
        ->name('admin.auth.token');
});

// Chat endpoints (uses Sanctum token + rate limiting)
Route::group([
    'prefix' => 'admin/employee-chat',
    'middleware' => ['auth:sanctum', 'throttle:chat-api']
], function () {
    // Get authenticated admin profile (secure name verification)
    Route::get('profile', function(\Illuminate\Http\Request $request) {
        $admin = $request->user();
        if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'id' => $admin->id,
            'name' => trim($admin->f_name . ' ' . $admin->l_name),
            'f_name' => $admin->f_name,
            'l_name' => $admin->l_name,
            'email' => $admin->email,
            'role_id' => $admin->role_id,
            'status' => $admin->status
        ]);
    })->name('admin.employee-chat.profile');

    // Conversations
    Route::get('conversations', [EmployeeChatController::class, 'conversations'])
        ->name('admin.employee-chat.conversations');

    Route::get('conversations/{id}', [EmployeeChatController::class, 'messages'])
        ->name('admin.employee-chat.messages');

    // Send message (stricter rate limit)
    Route::post('messages', [EmployeeChatController::class, 'send'])
        ->middleware('throttle:chat-send')
        ->name('admin.employee-chat.send');

    // Polling for real-time updates
    Route::get('poll', [EmployeeChatController::class, 'poll'])
        ->name('admin.employee-chat.poll');

    // Get available employees
    Route::get('employees', [EmployeeChatController::class, 'employees'])
        ->name('admin.employee-chat.employees');

    // File upload
    Route::post('upload', [EmployeeChatController::class, 'upload'])
        ->name('admin.employee-chat.upload');

    // Token verification
    Route::get('auth/verify', [AuthController::class, 'verify'])
        ->name('admin.auth.verify');
});

// Customer chat endpoints (uses Sanctum token)
Route::group([
    'prefix' => 'admin/chat',
    'middleware' => ['auth:sanctum']
], function () {
    // Customer conversations
    Route::get('customer-conversations', [UnifiedChatController::class, 'getCustomerConversations'])
        ->name('admin.chat.customer-conversations');

    Route::get('customer-messages/{conversationId}', [UnifiedChatController::class, 'getCustomerMessages'])
        ->name('admin.chat.customer-messages');

    // Send message to customer (stricter rate limit)
    Route::post('send-customer-message/{userId}', [UnifiedChatController::class, 'sendCustomerMessage'])
        ->middleware('throttle:chat-send')
        ->name('admin.chat.send-customer-message');

    // Check for new customer messages (polling)
    Route::get('check-new-messages', function(\Illuminate\Http\Request $request) {
        $controller = new \App\Http\Controllers\Admin\ConversationController();
        return $controller->checkNewMessages($request);
    })->name('admin.chat.check-new-messages');
});

// Message Templates API (used by both employee and customer chat)
Route::group([
    'prefix' => 'admin/message',
    'middleware' => ['auth:sanctum']
], function () {
    Route::get('templates', function() {
        $controller = new \App\Http\Controllers\Admin\ConversationController();
        return $controller->getTemplates();
    })->name('admin.message.templates');

    Route::post('templates', function(\Illuminate\Http\Request $request) {
        $controller = new \App\Http\Controllers\Admin\ConversationController();
        return $controller->storeTemplate($request);
    })->name('admin.message.templates.store');

    Route::put('templates/{id}', function(\Illuminate\Http\Request $request, $id) {
        $controller = new \App\Http\Controllers\Admin\ConversationController();
        return $controller->updateTemplate($request, $id);
    })->name('admin.message.templates.update');

    Route::delete('templates/{id}', function($id) {
        $controller = new \App\Http\Controllers\Admin\ConversationController();
        return $controller->deleteTemplate($id);
    })->name('admin.message.templates.delete');

    Route::post('templates/{id}/track-usage', function($id) {
        $controller = new \App\Http\Controllers\Admin\ConversationController();
        return $controller->trackTemplateUsage($id);
    })->middleware('throttle:chat-templates')
      ->name('admin.message.templates.track-usage');
});
