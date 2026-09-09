<?php

use Illuminate\Support\Facades\Route;





Route::group(['namespace' => 'Admin', 'as' => 'admin.'], function () {

    Route::group(['middleware' => ['admin', 'current-module']], function () {
        Route::get('/test', function () {
        });
        Route::get('get-all-stores', 'VendorController@get_all_stores')->name('get_all_stores');
        Route::get('lang/{locale}', 'LanguageController@lang')->name('lang');
        Route::get('settings', 'SystemController@settings')->name('settings');
        Route::post('settings', 'SystemController@settings_update');
        Route::post('settings-password', 'SystemController@settings_password_update')->name('settings-password');
        Route::get('/get-store-data', 'SystemController@store_data')->name('get-store-data');
        Route::get('/check-new-bills', 'SystemController@check_new_bills')->name('check-new-bills');
        Route::post('/orders-live-status', 'SystemController@orders_live_status')->name('orders-live-status');
        Route::post('remove_image', 'BusinessSettingsController@remove_image')->name('remove_image');
        Route::get('system-currency', 'SystemController@system_currency')->name('system_currency');
        //dashboard
        Route::get('/', 'DashboardController@dashboard')->name('dashboard');
        Route::post('/dashboard-heartbeat', 'DashboardController@dashboardHeartbeat')->name('dashboard.heartbeat');
        Route::get('/dashboard-presence', 'DashboardController@dashboardPresence')->name('dashboard.presence');

        // Chat System - Main Route (Simple URL)
        Route::get('/chat', function () {
            $admin = auth('admin')->user();
            // Allow super admins (role_id = 1) or approved employees (status = 1)
            if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
                abort(403, 'Unauthorized. Only approved admin employees can access chat.');
            }

            // Generate token and admin info server-side (more reliable than AJAX)
            // Delete old tokens for this session
            $admin->tokens()->where('name', 'employee-chat')->delete();

            // Create new token with 8-hour expiration
            $token = $admin->createToken('employee-chat', ['*'], now()->addHours(8))->plainTextToken;
            $adminName = trim($admin->f_name . ' ' . $admin->l_name);
            $expiresAt = now()->addHours(8)->toIso8601String();

            return view('admin-views.employee-chat-iframe', compact('token', 'adminName', 'expiresAt'));
        })->name('chat');

        // Employee Chat (Legacy alias - redirects to /chat)
        Route::get('/employee-chat', function () {
            return redirect()->route('admin.chat');
        })->name('employee-chat');

        // Secure token endpoint - returns token via authenticated AJAX request
        Route::post('/employee-chat/get-token', function () {
            $admin = auth('admin')->user();
            if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Delete old tokens for this session
            $admin->tokens()->where('name', 'employee-chat')->delete();

            // Create new token with 8-hour expiration
            $token = $admin->createToken('employee-chat', ['*'], now()->addHours(8))->plainTextToken;
            $adminName = trim($admin->f_name . ' ' . $admin->l_name);

            return response()->json([
                'token' => $token,
                'adminName' => $adminName,
                'expiresAt' => now()->addHours(8)->toIso8601String()
            ]);
        })->name('employee-chat.get-token');

        // Chat widget - Check for new messages (polling endpoint)
        Route::get('/chat/check-new-messages', function () {
            $admin = auth('admin')->user();
            if (!$admin) {
                return response()->json(['new_messages' => 0]);
            }

            // Count unread messages for this admin
            // This will need to be adjusted based on your conversations table structure
            $conversations = \App\Models\Conversation::where(function($q) use ($admin) {
                $q->where('receiver_type', 'admin')
                  ->where('receiver_id', $admin->id);
            })
            ->where('unread_message_count', '>', 0)
            ->get();

            return response()->json([
                'new_messages' => $conversations->sum('unread_message_count'),
                'conversations' => $conversations->count()
            ]);
        })->name('chat.check-new-messages');

        // Delivery stats route - handles both page load and AJAX requests
          Route::get('/delivery-stats', 'DashboardController@delivery_stats')->name('delivery-stats');
    
    // AJAX data endpoint
    Route::get('/delivery-stats/data', 'DashboardController@delivery_stats')->name('delivery-stats.data');

       

    // Attendance Routes
        Route::get('attendance', 'AttendanceController@index')->name('attendance.index');
        Route::post('attendance/punch-in', 'AttendanceController@punchIn')->name('attendance.punch-in');
        Route::post('attendance/punch-out', 'AttendanceController@punchOut')->name('attendance.punch-out');
        Route::get('attendance/today', 'AttendanceController@getTodayAttendance')->name('attendance.today');
        Route::get('attendance/report', 'AttendanceController@attendanceReport')->name('attendance.report');
        Route::post('attendance/export', 'AttendanceController@exportAttendanceReport')->name('attendance.export');
        // Face-based attendance routes
        Route::post('attendance/punch-in-face', 'AttendanceController@punchInWithFace')->name('attendance.punch-in-face');
        Route::post('attendance/punch-out-face', 'AttendanceController@punchOutWithFace')->name('attendance.punch-out-face');

        // Break Routes
        Route::post('breaks/start', 'BreakController@startBreak')->name('breaks.start');
        Route::post('breaks/end', 'BreakController@endBreak')->name('breaks.end');
        Route::get('breaks/status', 'BreakController@getBreakStatus')->name('breaks.status');

        // Attendance Detail
        Route::get('attendance/{id}', 'AttendanceController@show')->name('attendance.show')->where('id', '[0-9]+');

        // Roster Roles
        Route::group(['prefix' => 'roster-roles', 'as' => 'roster-roles.'], function () {
            Route::get('/', 'RosterRoleController@index')->name('index');
            Route::post('/', 'RosterRoleController@store')->name('store');
            Route::put('{id}', 'RosterRoleController@update')->name('update');
            Route::post('{id}/toggle', 'RosterRoleController@toggleStatus')->name('toggle');
            Route::delete('{id}', 'RosterRoleController@destroy')->name('destroy');
        });

        // Face Recognition Routes
        Route::group(['prefix' => 'face', 'as' => 'face.'], function () {
            Route::post('register', 'FaceRecognitionController@registerFace')->name('register');
            Route::post('verify', 'FaceRecognitionController@verifyFace')->name('verify');
            Route::get('data', 'FaceRecognitionController@getFaceData')->name('data');
            Route::post('update', 'FaceRecognitionController@updateFace')->name('update');
            Route::post('remove', 'FaceRecognitionController@removeFace')->name('remove');
            Route::get('check-required', 'FaceRecognitionController@checkFaceRequired')->name('check-required');
        });

        // Leave Request Routes
        Route::resource('leave', 'LeaveRequestController');
        Route::post('leave/{id}/update-status', 'LeaveRequestController@updateStatus')->name('leave.update-status');

        // Shift Roster Routes
        Route::group(['prefix' => 'shift-roster', 'as' => 'shift-roster.'], function () {
            Route::get('/', 'ShiftRosterController@index')->name('index');
            Route::post('assign', 'ShiftRosterController@assign')->name('assign');
            Route::post('bulk-assign', 'ShiftRosterController@bulkAssign')->name('bulk-assign');
            Route::post('copy-week', 'ShiftRosterController@copyWeek')->name('copy-week');
            Route::get('my-shift', 'ShiftRosterController@myShift')->name('my-shift');
            Route::get('templates', 'ShiftRosterController@templates')->name('templates');
            Route::post('templates', 'ShiftRosterController@storeTemplate')->name('templates.store');
            Route::delete('templates/{id}', 'ShiftRosterController@deleteTemplate')->name('templates.delete');
            Route::post('auto-generate-next-week', 'ShiftRosterController@autoGenerateNextWeek')->name('auto-generate-next-week');
        });

    // Add at the end of your existing routes

        Route::get('maintenance-mode', 'SystemController@maintenance_mode')->name('maintenance-mode');
        Route::get('landing-page', 'SystemController@landing_page')->name('landing-page');

        Route::group(['prefix' => 'parcel', 'as' => 'parcel.', 'middleware' => ['module:parcel']], function () {
            Route::get('category/status/{id}/{status}', 'ParcelCategoryController@status')->name('category.status');
            Route::resource('category', 'ParcelCategoryController');
            Route::get('orders/{status}', 'ParcelController@orders')->name('orders');
            Route::get('orders/export/{status}/{file_type}', 'ParcelController@parcel_orders_export')->name('parcel_orders_export');
            Route::get('details/{id}', 'ParcelController@order_details')->name('order.details');
                     
                     
            Route::get('settings', 'ParcelController@settings')->name('settings');
            Route::post('settings', 'ParcelController@update_settings')->name('update.settings');
            Route::get('dispatch/{status}', 'ParcelController@dispatch_list')->name('list');
            Route::post('instruction', 'ParcelController@instruction')->name('instruction');
            Route::get('/instruction/{id}/{status}', 'ParcelController@instruction_status')->name('instruction_status');
            Route::put('instruction_edit/', 'ParcelController@instruction_edit')->name('instruction_edit');
            Route::delete('instruction_delete/{id}', 'ParcelController@instruction_delete')->name('instruction_delete');
        });

        Route::group(['prefix' => 'dashboard-stats', 'as' => 'dashboard-stats.'], function () {
            Route::post('order', 'DashboardController@order')->name('order');
            Route::post('zone', 'DashboardController@zone')->name('zone');
            Route::post('user-overview', 'DashboardController@user_overview')->name('user-overview');
            Route::post('commission-overview', 'DashboardController@commission_overview')->name('commission-overview');
            Route::post('business-overview', 'DashboardController@business_overview')->name('business-overview');
        });

        // Employee Performance Dashboard
        Route::get('dashboard/employee-performance-detail', 'DashboardController@employee_performance_detail')->name('dashboard.employee-performance-detail');

        Route::post('item/variant-price', 'ItemController@variant_price')->name('item.variant-price');

        Route::group(['prefix' => 'item', 'as' => 'item.', 'middleware' => ['module:item']], function () {
            Route::get('add-new', 'ItemController@index')->name('add-new');
            Route::post('variant-combination', 'ItemController@variant_combination')->name('variant-combination');
            Route::post('store', 'ItemController@store')->name('store');
            Route::get('edit/{id}', 'ItemController@edit')->name('edit');
            Route::post('update/{id}', 'ItemController@update')->name('update');
            Route::get('list', 'ItemController@list')->name('list');
            Route::delete('delete/{id}', 'ItemController@delete')->name('delete');
            Route::get('status/{id}/{status}', 'ItemController@status')->name('status');
            Route::get('review-status/{id}/{status}', 'ItemController@reviews_status')->name('reviews.status');
            Route::post('search', 'ItemController@search')->name('search');
            Route::post('store/{store_id}/search', 'ItemController@search_store')->name('store-search');
            Route::get('reviews', 'ItemController@review_list')->name('reviews');
            Route::get('regular-items', 'ItemController@regular_items')->name('regular_items');
            Route::post('regular-items/update', 'ItemController@regular_items_update')->name('regular_items.update');
            Route::post('regular-items/search', 'ItemController@regular_items_search')->name('regular_items.search');
                     
                     Route::post('toggle-status', 'ItemController@toggleStatus')->name('toggle-status');
            Route::post('remove-bg', 'ItemController@removeBg')->name('remove-bg');
            Route::post('save-edited-image', 'ItemController@saveEditedImage')->name('save-edited-image');

//            Route::post('regular-items/search-by-barcode', 'ItemController@searchByBarcode')->name('regular_items.search_by_barcode');
            Route::post('regular-items/search-with-serp', 'ItemController@searchByBarcodeWithSerp')->name('regular_items.search_with_serp');
            Route::post('regular-items/assign-barcode', 'ItemController@assignBarcode')->name('regular_items.assign_barcode');
            Route::get('barcode-scan', 'ItemController@barcodeScan')->name('barcode_scan');
                     
                     Route::post('barcode-scan/quick-update', 'ItemController@quickUpdate')->name('barcode_scan.quick_update');
            Route::post('barcode-scan/process', 'ItemController@processBarcodeScan')->name('barcode_scan.process');
            Route::post('barcode-scan/store',  'ItemController@storeBarcodeItem')->name('barcode_scan.store');
                     // Quick refresh for units dropdown (for product add/edit screens)
                     Route::get('get-units-refresh', function () {
                         $units = \App\Models\Unit::select('id', 'unit')->orderBy('unit')->get();
                         $html = '';
                         foreach ($units as $unit) {
                             $html .= "<option value='{$unit->id}'>{$unit->unit}</option>";
                         }
                         return $html;
                     })->name('get-units-refresh');

                     
                     
                     
            // Route::post('reviews/search', 'ItemController@review_search')->name('reviews.search');
            Route::get('remove-image', 'ItemController@remove_image')->name('remove-image');
            Route::get('view/{id}', 'ItemController@view')->name('view');
            Route::get('store-item-export', 'ItemController@store_item_export')->name('store-item-export');
            Route::get('reviews-export', 'ItemController@reviews_export')->name('reviews_export');
            Route::get('item-wise-reviews-export', 'ItemController@item_wise_reviews_export')->name('item_wise_reviews_export');

            Route::get('new/item/list', 'ItemController@approval_list')->name('approval_list');
            Route::get('approved', 'ItemController@approved')->name('approved');
            Route::get('product_denied', 'ItemController@deny')->name('deny');
            Route::get('requested/item/view/{id}', 'ItemController@requested_item_view')->name('requested_item_view');
            Route::get('product-gallery', 'ItemController@product_gallery')->name('product_gallery');

            //ajax request
            Route::get('get-categories', 'ItemController@get_categories')->name('get-categories');
            Route::get('get-items', 'ItemController@get_items')->name('getitems');
            Route::get('get-items-flashsale', 'ItemController@get_items_flashsale')->name('getitems-flashsale');
            Route::post('food-variation-generate', 'ItemController@food_variation_generator')->name('food-variation-generate');
            Route::post('variation-generate', 'ItemController@variation_generator')->name('variation-generate');


            Route::get('export', 'ItemController@export')->name('export');

            //Mainul
            Route::get('get-variations', 'ItemController@get_variations')->name('get-variations');
            Route::get('get-stock', 'ItemController@get_stock')->name('get_stock');
            Route::post('stock-update', 'ItemController@stock_update')->name('stock-update');
            Route::post('mark-out-of-stock/{id}', 'ItemController@markOutOfStock')->name('mark-out-of-stock');

            //Import and export
            Route::get('bulk-import', 'ItemController@bulk_import_index')->name('bulk-import');
            Route::post('bulk-import', 'ItemController@bulk_import_data');
            Route::get('bulk-export', 'ItemController@bulk_export_index')->name('bulk-export-index');
            Route::post('bulk-export', 'ItemController@bulk_export_data')->name('bulk-export');
        });













        Route::group(['prefix' => 'promotional-banner', 'as' => 'promotional-banner.', 'middleware' => ['module:banner']], function () {
            Route::get('add-new', 'OtherBannerController@promotional_index')->name('add-new');
            Route::get('add-video', 'OtherBannerController@promotional_video')->name('add-video');
            Route::post('store', 'OtherBannerController@promotional_store')->name('store');
            Route::get('edit/{id}', 'OtherBannerController@promotional_edit')->name('edit');
            Route::post('update/{id}', 'OtherBannerController@promotional_update')->name('update');
            Route::get('update-status/{id}/{status}', 'OtherBannerController@promotional_status')->name('update-status');
            Route::delete('delete/{banner}', 'OtherBannerController@promotional_destroy')->name('delete');
            Route::get('add-why-choose', 'OtherBannerController@promotional_why_choose')->name('add-why-choose');
            Route::post('why-choose/store', 'OtherBannerController@why_choose_store')->name('why-choose-store');
            Route::get('why-choose/edit/{id}', 'OtherBannerController@why_choose_edit')->name('why-choose-edit');
            Route::post('why-choose/update/{id}', 'OtherBannerController@why_choose_update')->name('why-choose-update');
            Route::get('why-choose/update-status/{id}/{status}', 'OtherBannerController@why_choose_status')->name('why-choose-status-update');
            Route::delete('why-choose/delete/{banner}', 'OtherBannerController@why_choose_destroy')->name('why-choose-delete');
            Route::post('video-content/store', 'OtherBannerController@video_content_store')->name('video-content-store');
            Route::post('video-image/store', 'OtherBannerController@video_image_store')->name('video-image-store');
        });
        Route::group(['prefix' => 'deliveryman', 'as' => 'deliveryman.'], function () {
            Route::group(['prefix' => 'attendance', 'as' => 'attendance.'], function () {
            Route::get('/', 'DeliverymanAttendanceController@index')->name('index');
            Route::get('/report', 'DeliverymanAttendanceController@report')->name('report');
            Route::post('/export', 'DeliverymanAttendanceController@export')->name('export');
            });

            // Offline Monitor Routes
            Route::group(['prefix' => 'offline-monitor', 'as' => 'offline-monitor.'], function () {
                Route::get('/test', function() {
                    return '<h1>Test Route Works!</h1><p>Controller and routes are functioning.</p><a href="' . route('admin.deliveryman.offline-monitor.index') . '">Try Main Page</a>';
                })->name('test');
                Route::get('/', 'DeliverymanOfflineMonitorController@index')->name('index');
                Route::get('/show/{id}', 'DeliverymanOfflineMonitorController@show')->name('show');
                Route::get('/live', 'DeliverymanOfflineMonitorController@liveMonitor')->name('live');
                Route::get('/daily-report', 'DeliverymanOfflineMonitorController@dailyReport')->name('daily-report');
                Route::get('/export', 'DeliverymanOfflineMonitorController@exportReport')->name('export');
                Route::get('/trends', 'DeliverymanOfflineMonitorController@trends')->name('trends');
                Route::get('/settings', 'DeliverymanOfflineMonitorController@settings')->name('settings');
                Route::post('/settings', 'DeliverymanOfflineMonitorController@settingsUpdate')->name('settings-update');
            });

            // Security Deposit Routes
            Route::group(['prefix' => 'security-deposit', 'as' => 'security-deposit.'], function () {
                Route::get('settings', 'SecurityDepositController@settings')->name('settings');
                Route::post('settings', 'SecurityDepositController@updateSettings')->name('update-settings');
                Route::get('/', 'SecurityDepositController@index')->name('index');
                Route::get('show/{id}', 'SecurityDepositController@show')->name('show');
                Route::post('refund/{id}', 'SecurityDepositController@refund')->name('refund');
                Route::get('payment/{paymentId}', 'SecurityDepositController@paymentDetails')->name('payment-details');
                Route::get('export', 'SecurityDepositController@export')->name('export');
            });
        });

        Route::group(['prefix' => 'campaign', 'as' => 'campaign.', 'middleware' => ['module:campaign']], function () {
            Route::get('{type}/add-new', 'CampaignController@index')->name('add-new');
            Route::post('store/basic', 'CampaignController@storeBasic')->name('store-basic');
            Route::post('store/item', 'CampaignController@storeItem')->name('store-item');
            Route::get('{type}/edit/{campaign}', 'CampaignController@edit')->name('edit');
            Route::get('{type}/view/{campaign}', 'CampaignController@view')->name('view');
            Route::post('basic/update/{campaign}', 'CampaignController@update')->name('update-basic');
            Route::post('item/update/{campaign}', 'CampaignController@updateItem')->name('update-item');
            Route::get('remove-store/{campaign}/{store}', 'CampaignController@remove_store')->name('remove-store');
            Route::post('add-store/{campaign}', 'CampaignController@addstore')->name('addstore');
            Route::get('{type}/list', 'CampaignController@list')->name('list');
            Route::get('status/{type}/{id}/{status}', 'CampaignController@status')->name('status');
            Route::delete('delete/{campaign}', 'CampaignController@delete')->name('delete');
            Route::delete('item/delete/{campaign}', 'CampaignController@delete_item')->name('delete-item');
            Route::post('basic-search', 'CampaignController@searchBasic')->name('searchBasic');
            Route::post('item-search', 'CampaignController@searchItem')->name('searchItem');
            Route::get('store-confirmation/{campaign}/{id}/{status}', 'CampaignController@store_confirmation')->name('store_confirmation');
            Route::get('basic-campaign-export', 'CampaignController@basic_campaign_export')->name('basic_campaign_export');
            Route::get('item-campaign-export', 'CampaignController@item_campaign_export')->name('item_campaign_export');

        });


        Route::group(['prefix' => 'flash-sale', 'as' => 'flash-sale.'], function () {
            Route::get('add-new', 'FlashSaleController@index')->name('add-new');
            Route::post('store', 'FlashSaleController@store')->name('store');
            Route::get('edit/{id}', 'FlashSaleController@edit')->name('edit');
            Route::post('update/{id}', 'FlashSaleController@update')->name('update');
            Route::get('publish/{id}/{publish}', 'FlashSaleController@publish')->name('publish');
            Route::delete('delete/{id}', 'FlashSaleController@delete')->name('delete');
            Route::get('add-product/{id}', 'FlashSaleController@add_product')->name('add-product');
            Route::post('store-product', 'FlashSaleController@store_product')->name('store-product');
            Route::delete('delete-product/{id}', 'FlashSaleController@delete_product')->name('delete-product');
            Route::get('status/{id}/{status}', 'FlashSaleController@status_product')->name('status-product');
        });

            // OLD LARAVEL CHAT SYSTEM - DISABLED (Replaced by React Chat at /admin/chat)
            // Kept commented for rollback safety - Remove after 30 days if no issues
            /*
            Route::group(['prefix' => 'message', 'as' => 'message.'], function () {
            Route::get('list', 'ConversationController@list')->name('list');
            Route::post('store/{user_id}', 'ConversationController@store')->name('store');
            Route::get('view/{conversation_id}/{user_id}', 'ConversationController@view')->name('view');
            Route::get('check', 'ConversationController@checkNewMessages')->name('check');

                // Template management routes
                Route::get('templates', 'ConversationController@getTemplates')->name('templates');
                Route::post('templates', 'ConversationController@storeTemplate')->name('templates.store');
                Route::put('templates/{id}', 'ConversationController@updateTemplate')->name('templates.update');
                Route::delete('templates/{id}', 'ConversationController@deleteTemplate')->name('templates.delete');
            });
            */

        // Next.js Admin Panel - Customer Chat API Routes
        Route::group(['prefix' => 'chat', 'as' => 'chat.'], function () {
            Route::get('customer-conversations', 'ConversationController@customer_conversations')->name('customer-conversations');
            Route::get('customer-messages/{id}', 'ConversationController@customer_messages')->name('customer-messages');
            Route::post('send-customer-message/{userId}', 'ConversationController@send_customer_message')->name('send-customer-message');
            Route::get('check-new-messages', 'ConversationController@check_new_customer_messages')->name('check-new-messages');
        });

        // Chatbot Management Routes
        Route::group(['prefix' => 'chatbot', 'as' => 'chatbot.'], function () {
            Route::get('settings', 'ChatbotController@settings')->name('settings');
            Route::post('settings/update', 'ChatbotController@updateSettings')->name('settings.update');
            Route::get('stats', 'ChatbotController@stats')->name('stats');
            Route::get('popular-faqs', 'ChatbotController@getPopularFaqs')->name('popular-faqs');

            // FAQ Management
            Route::get('faq', 'ChatbotController@faqIndex')->name('faq.index');
            Route::post('faq', 'ChatbotController@faqStore')->name('faq.store');
            Route::get('faq/{id}/edit', 'ChatbotController@faqEdit')->name('faq.edit');
            Route::put('faq/{id}', 'ChatbotController@faqUpdate')->name('faq.update');
            Route::delete('faq/{id}', 'ChatbotController@faqDelete')->name('faq.delete');
            Route::get('faq/{id}/status/{status}', 'ChatbotController@faqStatus')->name('faq.status');

            // Chatbot API endpoint
            Route::post('response', 'ChatbotController@getResponse')->name('response');

            // Template suggestions for admin replies
            Route::post('suggest-templates', 'ChatbotController@suggestTemplates')->name('suggest-templates');
            Route::post('smart-replies', 'ChatbotController@getSmartReplies')->name('smart-replies');

            // Conversation Learning
            Route::group(['prefix' => 'learning', 'as' => 'learning.'], function () {
                Route::get('/', 'ConversationLearningController@index')->name('index');
                Route::post('analyze', 'ConversationLearningController@analyze')->name('analyze');
                Route::post('generate-suggestions', 'ConversationLearningController@generateFaqSuggestions')->name('generate-suggestions');
                Route::post('keyword/{id}/approve', 'ConversationLearningController@approveKeyword')->name('keyword.approve');
                Route::post('keyword/{id}/reject', 'ConversationLearningController@rejectKeyword')->name('keyword.reject');
                Route::post('create-faq', 'ConversationLearningController@createFaqFromSuggestion')->name('create-faq');

                // New FAQ extraction routes
                Route::post('extract-qa', 'ConversationLearningController@extractQAPairs')->name('extract-qa');
                Route::post('bulk-create-faqs', 'ConversationLearningController@bulkCreateFaqs')->name('bulk-create-faqs');
                Route::post('auto-generate-faqs', 'ConversationLearningController@autoGenerateFaqs')->name('auto-generate-faqs');
                Route::get('conversation/{id}/history', 'ConversationLearningController@getConversationHistory')->name('conversation-history');
            });
        });



        Route::group(['prefix' => 'store', 'as' => 'store.'], function () {
            Route::get('get-stores-data/{store}', 'VendorController@get_store_data')->name('get-stores-data');
            Route::get('store-filter/{id}', 'VendorController@store_filter')->name('store-filter');
            Route::get('get-account-data/{store}', 'VendorController@get_account_data')->name('get-account-data');
            Route::get('get-stores', 'VendorController@get_stores')->name('get-stores');
            Route::get('get-providers', 'VendorController@get_providers')->name('get-providers');
            Route::get('get-addons', 'VendorController@get_addons')->name('get_addons');
            Route::group(['middleware' => ['module:store']], function () {
                Route::get('update-application/{id}/{status}', 'VendorController@update_application')->name('application');
                Route::get('add', 'VendorController@index')->name('add');
                Route::post('store', 'VendorController@store')->name('store');
                Route::get('edit/{id}', 'VendorController@edit')->name('edit');
                Route::post('update/{store}', 'VendorController@update')->name('update');
                Route::post('discount/{store}', 'VendorController@discountSetup')->name('discount');
                Route::post('update-settings/{store}', 'VendorController@updateStoreSettings')->name('update-settings');
                Route::post('update-meta-data/{store}', 'VendorController@updateStoreMetaData')->name('update-meta-data');
                Route::delete('delete/{store}', 'VendorController@destroy')->name('delete');
                Route::delete('clear-discount/{store}', 'VendorController@cleardiscount')->name('clear-discount');
                // Route::get('view/{store}', 'VendorController@view')->name('view_tab');
                Route::get('disbursement-export/{id}/{type}', 'VendorController@disbursement_export')->name('disbursement-export');
                Route::get('view/{store}/{tab?}/{sub_tab?}', 'VendorController@view')->name('view');
                Route::get('list', 'VendorController@list')->name('list');
                Route::get('pending-requests', 'VendorController@pending_requests')->name('pending-requests');
                Route::get('deny-requests', 'VendorController@deny_requests')->name('deny-requests');
                Route::post('search', 'VendorController@search')->name('search');
                Route::get('export', 'VendorController@export')->name('export');
                Route::get('store-wise-reviwe-export', 'VendorController@store_wise_reviwe_export')->name('store_wise_reviwe_export');
                Route::get('export/cash/{type}/{store_id}', 'VendorController@cash_export')->name('cash_export');
                Route::get('export/order/{type}/{store_id}', 'VendorController@order_export')->name('order_export');
                Route::get('export/withdraw/{type}/{store_id}', 'VendorController@withdraw_trans_export')->name('withdraw_trans_export');
                Route::get('status/{store}/{status}', 'VendorController@status')->name('status');
                Route::get('featured/{store}/{status}', 'VendorController@featured')->name('featured');
                Route::get('toggle-settings-status/{store}/{status}/{menu}', 'VendorController@store_status')->name('toggle-settings');
                Route::post('status-filter', 'VendorController@status_filter')->name('status-filter');



                Route::get('recommended-store', 'VendorController@recommended_store')->name('recommended_store');
                Route::get('recommended-store-add', 'VendorController@recommended_store_add')->name('recommended_store_add');
                Route::get('recommended-store-status/{id}/{status}', 'VendorController@recommended_store_status')->name('recommended_store_status');
                Route::delete('recommended-store-remove/{id}', 'VendorController@recommended_store_remove')->name('recommended_store_remove');
                Route::get('shuffle-recommended-store/{status}', 'VendorController@shuffle_recommended_store')->name('shuffle_recommended_store');

                Route::get('selected-stores', 'VendorController@selected_stores')->name('selected_stores');


                //Import and export
                Route::get('bulk-import', 'VendorController@bulk_import_index')->name('bulk-import');
                Route::post('bulk-import', 'VendorController@bulk_import_data');
                Route::get('bulk-export', 'VendorController@bulk_export_index')->name('bulk-export-index');
                Route::post('bulk-export', 'VendorController@bulk_export_data')->name('bulk-export');
                //Store shcedule
                Route::post('add-schedule', 'VendorController@add_schedule')->name('add-schedule');
                Route::get('remove-schedule/{store_schedule}', 'VendorController@remove_schedule')->name('remove-schedule');
            });

            Route::group(['middleware' => ['module:withdraw_list']], function () {
                Route::post('withdraw-status/{id}', 'VendorController@withdrawStatus')->name('withdraw_status');
                Route::get('withdraw_list', 'VendorController@withdraw')->name('withdraw_list');
                Route::post('withdraw_search', 'VendorController@withdraw_search')->name('withdraw_search');
                Route::get('withdraw_export', 'VendorController@withdraw_export')->name('withdraw_export');
                Route::get('withdraw-view/{withdraw_id}/{seller_id}', 'VendorController@withdraw_view')->name('withdraw_view');
            });

            // message
            Route::get('message/{conversation_id}/{user_id}', 'VendorController@conversation_view')->name('message-view');
            Route::get('message/list', 'VendorController@conversation_list')->name('message-list');
        });


        Route::get('addon/system-addons', function (){
            return to_route('admin.system-addon.index');
        })->name('addon.index');

        Route::get('order/generate-invoice/{id}', 'OrderController@generate_invoice')->name('order.generate-invoice');
        Route::get('order/print-invoice/{id}', 'OrderController@print_invoice')->name('order.print-invoice');
        Route::get('order/status', 'OrderController@status')->name('order.status');
        Route::get('order/offline-payment', 'OrderController@offline_payment')->name('order.offline_payment');
        Route::group(['prefix' => 'order', 'as' => 'order.', 'middleware' => ['module:order']], function () {
            Route::get('list/{status}', 'OrderController@list')->name('list');
            Route::get('details/{id}', 'OrderController@details')->name('details');
            Route::get('all-details/{id}', 'OrderController@all_details')->name('all-details');
                     
                 Route::post('update-item-mrp', 'OrderController@updateItemMrp')->name('update-item-mrp');
        Route::post('update-campaign-mrp', 'OrderController@updateCampaignMrp')->name('update-campaign-mrp');
        Route::post('approve-mrp-request', 'OrderController@approveMrpRequest')->name('approve-mrp-request');
        Route::post('mark-item-out-of-stock', 'OrderController@markItemOutOfStock')->name('mark-item-out-of-stock');
        Route::post('mark-campaign-out-of-stock', 'OrderController@markCampaignOutOfStock')->name('mark-campaign-out-of-stock');
        Route::post('assign', 'OrderController@assignOrder')->name('assign');
                     Route::post('find-replacements', 'OrderController@findReplacements')->name('find-replacements');
                         Route::post('replace-item', 'OrderController@replaceItem')->name('replace-item');
                         Route::post('scan-bill/{id}', 'OrderController@scanBill')->name('scan-bill');


            // Route::put('status-update/{id}', 'OrderController@status')->name('status-update');
            Route::get('view/{id}', 'OrderController@view')->name('view');
            Route::post('update-shipping/{order}', 'OrderController@update_shipping')->name('update-shipping');
            Route::delete('delete/{id}', 'OrderController@delete')->name('delete');

            Route::get('add-delivery-man/{order_id}/{delivery_man_id}', 'OrderController@add_delivery_man')->name('add-delivery-man');
            Route::get('get-deliverymen', 'OrderController@getDeliverymenByZone')->name('get-deliverymen');
            Route::get('payment-status', 'OrderController@payment_status')->name('payment-status');

            Route::post('add-payment-ref-code/{id}', 'OrderController@add_payment_ref_code')->name('add-payment-ref-code');
            Route::patch('update-payment-info/{id}', 'OrderController@update_payment_info')->name('update-payment-info');
            Route::patch('update-order-payment/{id}', 'OrderController@update_order_payment')->name('update-order-payment');
            Route::post('add-order-proof/{id}', 'OrderController@add_order_proof')->name('add-order-proof');
            Route::get('remove-proof-image', 'OrderController@remove_proof_image')->name('remove-proof-image');
            Route::get('store-filter/{store_id}', 'OrderController@restaurnt_filter')->name('order-store-filter');
            Route::get('filter/reset', 'OrderController@filter_reset');
            Route::post('filter', 'OrderController@filter')->name('filter');

            // Delivery tracking stats
            Route::get('tracking-stats/{order_id}', 'OrderController@getTrackingStats')->name('tracking-stats');
            Route::get('delivery-idle-ranking', 'OrderController@getDeliveryManIdleRanking')->name('delivery-idle-ranking');
            Route::get('search', 'OrderController@search')->name('search');
            Route::post('store/search', 'OrderController@store_order_search')->name('store-search');
            Route::get('store/export', 'OrderController@store_order_export')->name('store-export');
            //order update
            Route::post('add-to-cart', 'OrderController@add_to_cart')->name('add-to-cart');
            Route::post('remove-from-cart', 'OrderController@remove_from_cart')->name('remove-from-cart');
            Route::get('update/{order}', 'OrderController@update')->name('update');
            Route::get('edit-order/{order}', 'OrderController@edit')->name('edit');
            Route::post('save-edit-progress', 'OrderController@saveEditProgress')->name('save-edit-progress');
            Route::get('recover-edit-session', 'OrderController@recoverEditSession')->name('recover-edit-session');
            // Phase 2 & 3: Inline editing routes
            Route::post('inline-save-progress', 'OrderController@inlineSaveProgress')->name('inline-save-progress');
            Route::post('inline-update', 'OrderController@inlineUpdate')->name('inline-update');
            Route::get('quick-view', 'OrderController@quick_view')->name('quick-view');
            Route::get('quick-view-cart-item', 'OrderController@quick_view_cart_item')->name('quick-view-cart-item');
            Route::get('search-items-for-order', 'OrderController@searchItemsForOrder')->name('search-items-for-order');
            Route::get('edit-v2/{order}', 'OrderController@editV2')->name('edit-v2');
            Route::get('view-v2/{order}', 'OrderController@viewV2')->name('view-v2');
            Route::get('search-items-for-order-v2', 'OrderController@searchItemsForOrderV2')->name('search-items-for-order-v2');
            Route::get('cart-edit-items', 'OrderController@getEditCartItems')->name('cart-edit-items');
            Route::post('mark-item-unavailable', 'OrderController@markItemUnavailable')->name('mark-item-unavailable');
            Route::post('mark-outside-purchase', 'OrderController@mark_outside_purchase')->name('mark-outside-purchase');
            Route::post('mark-full-order-outside-purchase', 'OrderController@mark_full_order_outside_purchase')->name('mark-full-order-outside-purchase');
            Route::post('approve-outside-purchase', 'OrderController@approve_outside_purchase')->name('approve-outside-purchase');
            Route::post('reject-outside-purchase', 'OrderController@reject_outside_purchase')->name('reject-outside-purchase');
            Route::get('get-stores-for-outside-purchase', 'OrderController@get_stores_for_outside_purchase')->name('get-stores-for-outside-purchase');
            Route::get('export-orders/{file_type}/{status}/{type}', 'OrderController@export_orders')->name('export');
            Route::get('offline/payment/list/{status}', 'OrderController@offline_verification_list')->name('offline_verification_list');
            Route::get('cart-data', 'OrderController@getCartData')->name('cart-data');


        });



        // Refund
        Route::group(['prefix' => 'refund', 'as' => 'refund.', 'middleware' => ['module:order']], function () {
            Route::get('settings', 'OrderController@refund_settings')->name('refund_settings');
            Route::get('refund_mode', 'OrderController@refund_mode')->name('refund_mode');
            Route::post('refund_reason', 'OrderController@refund_reason')->name('refund_reason');
            Route::get('/status/{id}/{status}', 'OrderController@reason_status')->name('reason_status');
            Route::put('reason_edit/', 'OrderController@reason_edit')->name('reason_edit');
            Route::delete('reason_delete/{id}', 'OrderController@reason_delete')->name('reason_delete');
            Route::put('order_refund_rejection/', 'OrderController@order_refund_rejection')->name('order_refund_rejection');
            Route::get('/{status}', 'OrderController@list')->name('refund_attr');
        });



        Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.', 'middleware' => ['module:settings', 'actch']], function () {
            Route::get('business-setup/{tab?}', 'BusinessSettingsController@business_index')->name('business-setup');
            Route::get('react-setup', 'BusinessSettingsController@react_setup')->name('react-setup');
            Route::post('react-update', 'BusinessSettingsController@react_update')->name('react-update');
            Route::post('update-setup', 'BusinessSettingsController@business_setup')->name('update-setup');
            Route::post('update-landing-setup', 'BusinessSettingsController@landing_page_settings_update')->name('update-landing-setup');
            Route::delete('delete-custom-landing-page', 'BusinessSettingsController@delete_custom_landing_page')->name('delete-custom-landing-page');
            Route::post('update-dm', 'BusinessSettingsController@update_dm')->name('update-dm');
            Route::post('update-disbursement', 'BusinessSettingsController@update_disbursement')->name('update-disbursement');
            Route::post('update-websocket', 'BusinessSettingsController@update_websocket')->name('update-websocket');
            Route::post('update-store', 'BusinessSettingsController@update_store')->name('update-store');
            Route::post('update-order', 'BusinessSettingsController@update_order')->name('update-order');
            Route::post('update-priority', 'BusinessSettingsController@update_priority')->name('update-priority');
            Route::get('app-settings', 'BusinessSettingsController@app_settings')->name('app-settings');
            Route::POST('app-settings', 'BusinessSettingsController@update_app_settings')->name('update-app-settings');
            Route::get('pages/admin-landing-page-settings/{tab?}', 'BusinessSettingsController@admin_landing_page_settings')->name('admin-landing-page-settings');
            Route::POST('pages/admin-landing-page-settings/{tab}', 'BusinessSettingsController@update_admin_landing_page_settings')->name('update-admin-landing-page-settings');
            Route::get('promotional-status/{id}/{status}', 'BusinessSettingsController@promotional_status')->name('promotional-status');
            Route::get('pages/admin-landing-page-settings/promotional-section/edit/{id}', 'BusinessSettingsController@promotional_edit')->name('promotional-edit');
            Route::post('promotional-section/update/{id}', 'BusinessSettingsController@promotional_update')->name('promotional-update');
            Route::delete('banner/delete/{banner}', 'BusinessSettingsController@promotional_destroy')->name('promotional-delete');
            Route::get('feature-status/{id}/{status}', 'BusinessSettingsController@feature_status')->name('feature-status');
            Route::get('pages/admin-landing-page-settings/feature-list/edit/{id}', 'BusinessSettingsController@feature_edit')->name('feature-edit');
            Route::post('feature-section/update/{id}', 'BusinessSettingsController@feature_update')->name('feature-update');
            Route::delete('feature/delete/{feature}', 'BusinessSettingsController@feature_destroy')->name('feature-delete');
            Route::get('criteria-status/{id}/{status}', 'BusinessSettingsController@criteria_status')->name('criteria-status');
            Route::get('pages/admin-landing-page-settings/why-choose-us/criteria-list/edit/{id}', 'BusinessSettingsController@criteria_edit')->name('criteria-edit');
            Route::post('criteria-section/update/{id}', 'BusinessSettingsController@criteria_update')->name('criteria-update');
            Route::delete('admin/criteria/delete/{criteria}', 'BusinessSettingsController@criteria_destroy')->name('criteria-delete');
            Route::get('review-status/{id}/{status}', 'BusinessSettingsController@review_status')->name('review-status');
            Route::get('pages/admin-landing-page-settings/testimonials/review-list/edit/{id}', 'BusinessSettingsController@review_edit')->name('review-edit');
            Route::post('review-section/update/{id}', 'BusinessSettingsController@review_update')->name('review-update');
            Route::delete('review/delete/{review}', 'BusinessSettingsController@review_destroy')->name('review-delete');
            Route::get('pages/react-landing-page-settings/{tab?}', 'BusinessSettingsController@react_landing_page_settings')->name('react-landing-page-settings');
            Route::POST('pages/react-landing-page-settings/{tab?}',
                'BusinessSettingsController@update_react_landing_page_settings')->name('update-react-landing-page-settings');
            Route::DELETE('react-landing-page-settings/{tab}/{key}', 'BusinessSettingsController@delete_react_landing_page_settings')->name('react-landing-page-settings-delete');
            Route::get('review-react-status/{id}/{status}', 'BusinessSettingsController@review_react_status')->name('review-react-status');
            Route::get('pages/react-landing-page-settings/testimonials/review-react-list/edit/{id}', 'BusinessSettingsController@review_react_edit')->name('review-react-edit');
            Route::post('review-react-section/update/{id}', 'BusinessSettingsController@review_react_update')->name('review-react-update');
            Route::delete('review-react/delete/{review}', 'BusinessSettingsController@review_react_destroy')->name('review-react-delete');
            Route::get('pages/flutter-landing-page-settings/{tab?}', 'BusinessSettingsController@flutter_landing_page_settings')->name('flutter-landing-page-settings');
            Route::POST('pages/flutter-landing-page-settings/{tab}', 'BusinessSettingsController@update_flutter_landing_page_settings')->name('update-flutter-landing-page-settings');
            Route::get('flutter-criteria-status/{id}/{status}', 'BusinessSettingsController@flutter_criteria_status')->name('flutter-criteria-status');
            Route::get('pages/flutter-landing-page-settings/special-criteria/edit/{id}', 'BusinessSettingsController@flutter_criteria_edit')->name('flutter-criteria-edit');
            Route::post('flutter-criteria-section/update/{id}', 'BusinessSettingsController@flutter_criteria_update')->name('flutter-criteria-update');
            Route::delete('flutter/criteria/delete/{criteria}', 'BusinessSettingsController@flutter_criteria_destroy')->name('flutter-criteria-delete');
            Route::get('landing-page-settings/{tab?}', 'BusinessSettingsController@landing_page_settings')->name('landing-page-settings');
            Route::POST('landing-page-settings/{tab}', 'BusinessSettingsController@update_landing_page_settings')->name('update-landing-page-settings');
            Route::DELETE('landing-page-settings/{tab}/{key}', 'BusinessSettingsController@delete_landing_page_settings')->name('landing-page-settings-delete');

            // Centerlize login
            Route::group(['prefix' => 'login-settings', 'as' => 'login-settings.'], function () {
                Route::get('login-setup', 'BusinessSettingsController@login_settings')->name('index');
                Route::post('login-setup/update', 'BusinessSettingsController@login_settings_update')->name('update');
            });

            Route::get('login-url-setup', 'BusinessSettingsController@login_url_page')->name('login_url_page');
            Route::post('login-url-setup/update', 'BusinessSettingsController@login_url_page_update')->name('login_url_update');

            Route::get('email-setup/{type}/{tab?}', 'BusinessSettingsController@email_index')->name('email-setup');
            Route::POST('email-setup/{type}/{tab?}', 'BusinessSettingsController@update_email_index')->name('update-email-setup');
            Route::get('email-status/{type}/{tab}/{status}', 'BusinessSettingsController@update_email_status')->name('email-status');

            Route::get('toggle-settings/{key}/{value}', 'BusinessSettingsController@toggle_settings')->name('toggle-settings');
            Route::get('site_direction', 'BusinessSettingsController@site_direction')->name('site_direction');


            Route::get('fcm-index', 'BusinessSettingsController@fcm_index')->name('fcm-index');
            Route::get('fcm-config', 'BusinessSettingsController@fcm_config')->name('fcm-config');
            Route::post('update-fcm', 'BusinessSettingsController@update_fcm')->name('update-fcm');

            Route::post('update-fcm-messages', 'BusinessSettingsController@update_fcm_messages')->name('update-fcm-messages');
            Route::post('update-fcm-messages-rental', 'BusinessSettingsController@update_fcm_messages_rental')->name('update-fcm-messages-rental');

            Route::get('currency-add', 'BusinessSettingsController@currency_index')->name('currency-add');
            Route::post('currency-add', 'BusinessSettingsController@currency_store');
            Route::get('currency-update/{id}', 'BusinessSettingsController@currency_edit')->name('currency-update');
            Route::put('currency-update/{id}', 'BusinessSettingsController@currency_update');
            Route::delete('currency-delete/{id}', 'BusinessSettingsController@currency_delete')->name('currency-delete');

            Route::get('pages/business-page/terms-and-conditions', 'BusinessSettingsController@terms_and_conditions')->name('terms-and-conditions');
            Route::post('pages/business-page/terms-and-conditions', 'BusinessSettingsController@terms_and_conditions_update');

            Route::get('pages/business-page/privacy-policy', 'BusinessSettingsController@privacy_policy')->name('privacy-policy');
            Route::post('pages/business-page/privacy-policy', 'BusinessSettingsController@privacy_policy_update');

            Route::get('pages/business-page/about-us', 'BusinessSettingsController@about_us')->name('about-us');
            Route::post('pages/business-page/about-us', 'BusinessSettingsController@about_us_update');

            Route::get('pages/business-page/refund', 'BusinessSettingsController@refund_policy')->name('refund');
            Route::post('pages/business-page/refund', 'BusinessSettingsController@refund_update');
            Route::get('pages/refund-policy/{status}', 'BusinessSettingsController@refund_policy_status')->name('refund-policy-status');

            Route::get('pages/business-page/cancelation', 'BusinessSettingsController@cancellation_policy')->name('cancelation');
            Route::post('pages/business-page/cancelation', 'BusinessSettingsController@cancellation_policy_update');
            Route::get('pages/cancellation-policy/{status}', 'BusinessSettingsController@cancellation_policy_status')->name('cancellation-policy-status');

            Route::get('pages/business-page/shipping-policy', 'BusinessSettingsController@shipping_policy')->name('shipping-policy');
            Route::post('pages/business-page/shipping-policy', 'BusinessSettingsController@shipping_policy_update');
            Route::get('pages/shipping-policy/{status}', 'BusinessSettingsController@shipping_policy_status')->name('shipping-policy-status');
            // Social media
            Route::get('social-media/fetch', 'SocialMediaController@fetch')->name('social-media.fetch');
            Route::get('social-media/status-update', 'SocialMediaController@social_media_status_update')->name('social-media.status-update');
            Route::resource('pages/social-media', 'SocialMediaController');


            Route::get('notification-setup', 'BusinessSettingsController@notification_setup')->name('notification_setup');
            Route::get('notification-status-change/{key}/{user_type}/{type}', 'BusinessSettingsController@notification_status_change')->name('notification_status_change');



            Route::group(['prefix' => 'file-manager', 'as' => 'file-manager.'], function () {
                Route::get('/download/{file_name}/{storage?}', 'FileManagerController@download')->name('download');
                Route::get('/index/{folder_path?}/{storage?}', 'FileManagerController@index')->name('index');
                Route::post('/image-upload', 'FileManagerController@upload')->name('image-upload');
                Route::delete('/delete/{file_path}', 'FileManagerController@destroy')->name('destroy');
            });

            // Route::group(['prefix' => 'external-system', 'as' => 'external-system.'], function () {
            //     Route::get('drivemond-configuration', 'ExternalConfigurationController@index')->name('drivemond-configuration');
            //     Route::post('update-drivemond-configuration', 'ExternalConfigurationController@updateDrivemondConfiguration')->name('update-drivemond-configuration');
            // });
            Route::group(['prefix' => 'third-party', 'as' => 'third-party.'], function () {
                Route::get('sms-module', 'SMSModuleController@sms_index')->name('sms-module');
                Route::post('sms-module-update/{sms_module}', 'SMSModuleController@sms_update')->name('sms-module-update');
                Route::get('payment-method', 'BusinessSettingsController@payment_index')->name('payment-method');
                // Route::post('payment-method-update/{payment_method}', 'BusinessSettingsController@payment_update')->name('payment-method-update');
                Route::post('payment-method-update', 'BusinessSettingsController@payment_config_update')->name('payment-method-update');
                Route::get('config-setup', 'BusinessSettingsController@config_setup')->name('config-setup');
                Route::post('config-update', 'BusinessSettingsController@config_update')->name('config-update');
                Route::get('mail-config', 'BusinessSettingsController@mail_index')->name('mail-config');
                Route::get('test-mail', 'BusinessSettingsController@test_mail')->name('test');
                Route::post('mail-config', 'BusinessSettingsController@mail_config');
                Route::post('mail-config-status', 'BusinessSettingsController@mail_config_status')->name('mail-config-status');
                Route::get('send-mail', 'BusinessSettingsController@send_mail')->name('mail.send');
                // social media login
                Route::group(['prefix' => 'social-login', 'as' => 'social-login.'], function () {
                    Route::get('view', 'BusinessSettingsController@viewSocialLogin')->name('view');
                    Route::post('update/{service}', 'BusinessSettingsController@updateSocialLogin')->name('update');
                });
                //recaptcha
                Route::get('recaptcha', 'BusinessSettingsController@recaptcha_index')->name('recaptcha_index');
                Route::post('recaptcha-update', 'BusinessSettingsController@recaptcha_update')->name('recaptcha_update');
                //firebase-otp
                Route::get('firebase-otp', 'BusinessSettingsController@firebase_otp_index')->name('firebase_otp_index');
                Route::post('firebase-otp-update', 'BusinessSettingsController@firebase_otp_update')->name('firebase_otp_update');
                //file_system
                Route::get('storage-connection', 'BusinessSettingsController@storage_connection_index')->name('storage_connection_index');
                Route::post('storage-connection-update/{name}', 'BusinessSettingsController@storage_connection_update')->name('storage_connection_update');
            });
            // Offline payment Methods
            Route::get('/offline-payment', 'OfflinePaymentMethodController@index')->name('offline');
            Route::get('/offline-payment/new', 'OfflinePaymentMethodController@create')->name('offline.new');
            Route::post('/offline-payment/store', 'OfflinePaymentMethodController@store')->name('offline.store');
            Route::get('/offline-payment/edit/{id}', 'OfflinePaymentMethodController@edit')->name('offline.edit');
            Route::post('/offline-payment/update', 'OfflinePaymentMethodController@update')->name('offline.update');
            Route::post('/offline-payment/delete', 'OfflinePaymentMethodController@delete')->name('offline.delete');
            Route::get('/offline-payment/status/{id}', 'OfflinePaymentMethodController@status')->name('offline.status');



            //db clean
            Route::get('db-index', 'DatabaseSettingController@db_index')->name('db-index');
            Route::get('system-check', 'BusinessSettingsController@systemCheck')->name('system-check');
            Route::post('system-check/diagnose', 'BusinessSettingsController@systemCheckDiagnose')->name('system-check.diagnose');
            Route::post('db-clean', 'DatabaseSettingController@clean_db')->name('clean-db');

            Route::group(['prefix' => 'language', 'as' => 'language.'], function () {
                Route::get('', 'LanguageController@index')->name('index');
                Route::post('add-new', 'LanguageController@store')->name('add-new');
                Route::get('update-status', 'LanguageController@update_status')->name('update-status');
                Route::get('update-default-status', 'LanguageController@update_default_status')->name('update-default-status');
                Route::post('update', 'LanguageController@update')->name('update');
                Route::get('translate/{lang}', 'LanguageController@translate')->name('translate');
                Route::post('translate-submit/{lang}', 'LanguageController@translate_submit')->name('translate-submit');
                Route::post('remove-key/{lang}', 'LanguageController@translate_key_remove')->name('remove-key');
                Route::get('delete/{lang}', 'LanguageController@delete')->name('delete');
                Route::any('auto-translate/{lang}', 'LanguageController@auto_translate')->name('auto-translate');
                Route::get('auto-translate-all/{lang}', 'LanguageController@auto_translate_all')->name('auto_translate_all');

            });

            Route::get('order-cancel-reasons/status/{id}/{status}', 'OrderCancelReasonController@status')->name('order-cancel-reasons.status');
            Route::get('order-cancel-reasons', 'OrderCancelReasonController@index')->name('order-cancel-reasons.index');
            Route::post('order-cancel-reasons/store', 'OrderCancelReasonController@store')->name('order-cancel-reasons.store');
            Route::put('order-cancel-reasons/update', 'OrderCancelReasonController@update')->name('order-cancel-reasons.update');
            Route::delete('order-cancel-reasons/destroy/{id}', 'OrderCancelReasonController@destroy')->name('order-cancel-reasons.destroy');

            Route::post('automated-message/store', 'AutomatedMessageController@store')->name('automated_message.store');
            Route::put('automated-message/update', 'AutomatedMessageController@update')->name('automated_message.update');
            Route::get('automated-message/status/{id}/{status}', 'AutomatedMessageController@status')->name('automated_message.status');
            Route::delete('automated-message/destroy/{id}', 'AutomatedMessageController@destroy')->name('automated_message.destroy');

            Route::group(['namespace' => 'System','prefix' => 'system-addon', 'as' => 'system-addon.', 'middleware'=>['module:user_management']], function () {
                Route::get('/', 'AddonController@index')->name('index');
                Route::post('publish', 'AddonController@publish')->name('publish');
                Route::post('activation', 'AddonController@activation')->name('activation');
                Route::post('upload', 'AddonController@upload')->name('upload');
                Route::post('delete', 'AddonController@delete_theme')->name('delete');
            });

        });

        // Subscribed customer Routes
        Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {



            Route::group(['prefix' => 'wallet', 'as' => 'wallet.', 'middleware' => ['module:customer_wallet']], function () {
                Route::get('add-fund', 'CustomerWalletController@add_fund_view')->name('add-fund');
                Route::post('add-fund', 'CustomerWalletController@add_fund');
                Route::get('report', 'CustomerWalletController@report')->name('report');
            });
            Route::group(['middleware' => ['module:customer_management']], function () {

                // Subscribed customer Routes
                Route::get('subscribed', 'CustomerController@subscribedCustomers')->name('subscribed');
                // Route::post('subscriber-search', 'CustomerController@subscriberMailSearch')->name('subscriberMailSearch');
                Route::get('subscriber-search', 'CustomerController@subscribed_customer_export')->name('subscriber-export');

                Route::get('loyalty-point/report', 'LoyaltyPointController@report')->name('loyalty-point.report');
                Route::get('settings', 'CustomerController@settings')->name('settings');
                Route::post('update-settings', 'CustomerController@update_settings')->name('update-settings');
                Route::get('export', 'CustomerController@export')->name('export');
                Route::get('order-export', 'CustomerController@customer_order_export')->name('order-export');
                Route::get('trip-export', 'CustomerController@customer_trip_export')->name('trip-export');
            });
        });
        //Pos system
        Route::group(['prefix' => 'pos', 'as' => 'pos.'], function () {
                     // ADD THIS NEW LINE - AJAX Search Route (no page refresh)
                            Route::get('search-products', 'POSController@search_products')->name('search-products');
            Route::post('variant_price', 'POSController@variant_price')->name('variant_price');
            Route::group(['middleware' => ['module:pos']], function () {
                Route::get('/', 'POSController@index')->name('index');
                Route::get('quick-view', 'POSController@quick_view')->name('quick-view');
                Route::post('item-stock-view', 'POSController@item_stock_view')->name('item_stock_view');
                Route::post('item-stock-view-update', 'POSController@item_stock_view_update')->name('item_stock_view_update');
                Route::get('quick-view-cart-item', 'POSController@quick_view_card_item')->name('quick-view-cart-item');
                Route::post('add-to-cart', 'POSController@addToCart')->name('add-to-cart');
                Route::post('remove-from-cart', 'POSController@removeFromCart')->name('remove-from-cart');
                Route::post('cart-items', 'POSController@cart_items')->name('cart_items');
                Route::post('single-items', 'POSController@single_items')->name('single_items');
                Route::post('update-quantity', 'POSController@updateQuantity')->name('updateQuantity');
                Route::post('empty-cart', 'POSController@emptyCart')->name('emptyCart');
                Route::post('tax', 'POSController@update_tax')->name('tax');
                Route::post('discount', 'POSController@update_discount')->name('discount');
                Route::get('customers', 'POSController@get_customers')->name('customers');
                Route::post('order', 'POSController@place_order')->name('order');
                Route::get('invoice/{id}', 'POSController@generate_invoice');
                Route::post('customer-store', 'POSController@customer_store')->name('customer-store');
                Route::post('add-delivery-address', 'POSController@addDeliveryInfo')->name('add-delivery-address');
                Route::get('data', 'POSController@extra_charge')->name('extra_charge');
                Route::get('get-user-data', 'POSController@getUserData')->name('getUserData');
                        
            });
        });

        Route::group(['prefix' => 'reviews', 'as' => 'reviews.', 'middleware' => ['module:customer_management']], function () {
            Route::get('list', 'ReviewsController@list')->name('list');
            Route::post('search', 'ReviewsController@search')->name('search');
        });

        Route::group(['prefix' => 'report', 'as' => 'report.', 'middleware' => ['module:report']], function () {
            Route::get('order', 'ReportController@order_index')->name('order');
            Route::get('transaction-report', 'ReportController@day_wise_report')->name('transaction-report');
            Route::get('item-wise-report', 'ReportController@item_wise_report')->name('item-wise-report');
            Route::get('item-wise-export', 'ReportController@item_wise_export')->name('item-wise-export');
            Route::post('item-wise-report-search', 'ReportController@item_search')->name('item-wise-report-search');
            Route::post('day-wise-report-search', 'ReportController@day_search')->name('day-wise-report-search');
            Route::get('day-wise-report-export', 'ReportController@day_wise_export')->name('day-wise-report-export');
            Route::get('order-transactions', 'ReportController@order_transaction')->name('order-transaction');
            Route::get('earning', 'ReportController@earning_index')->name('earning');
            Route::post('set-date', 'ReportController@set_date')->name('set-date');
            Route::get('stock-report', 'ReportController@stock_report')->name('stock-report');
            Route::post('stock-report', 'ReportController@stock_search')->name('stock-search');
            Route::get('stock-wise-report-search', 'ReportController@stock_wise_export')->name('stock-wise-report-export');
            Route::get('order-report', 'ReportController@order_report')->name('order-report');
            Route::post('order-report-search', 'ReportController@search_order_report')->name('search_order_report');
            Route::get('order-report-export', 'ReportController@order_report_export')->name('order-report-export');
            Route::get('store-wise-report', 'ReportController@store_summary_report')->name('store-summary-report');
            Route::post('store-summary-report-search', 'ReportController@store_summary_search')->name('store-summary-report-search');
            Route::get('store-summary-report-export', 'ReportController@store_summary_export')->name('store-summary-report-export');
            Route::get('store-wise-sales-report', 'ReportController@store_sales_report')->name('store-sales-report');
            Route::get('store-wise-sales-report-export', 'ReportController@store_sales_export')->name('store-sales-report-export');
            Route::get('store-wise-order-report', 'ReportController@store_order_report')->name('store-order-report');
            Route::post('store-wise-order-report-search', 'ReportController@store_order_search')->name('store-order-report-search');
            Route::get('store-wise-order-report-export', 'ReportController@store_order_export')->name('store-order-report-export');
            Route::get('expense-report', 'ReportController@expense_report')->name('expense-report');
            Route::get('expense-export', 'ReportController@expense_export')->name('expense-export');
            Route::post('expense-report-search', 'ReportController@expense_search')->name('expense-report-search');
            Route::get('generate-statement/{id}', 'ReportController@generate_statement')->name('generate-statement');
        });

        Route::get('customer/select-list', 'CustomerController@get_customers')->name('customer.select-list');


        Route::group(['prefix' => 'customer', 'as' => 'customer.', 'middleware' => ['module:customer_management']], function () {
            Route::get('list', 'CustomerController@customer_list')->name('list');
            Route::get('view/{user_id}', 'CustomerController@view')->name('view');
            Route::post('search', 'CustomerController@search')->name('search');
            Route::get('status/{customer}/{status}', 'CustomerController@status')->name('status');
        });


        Route::group(['prefix' => 'file-manager', 'as' => 'file-manager.'], function () {
            Route::get('/download/{file_name}/{storage?}', 'FileManagerController@download')->name('download');
            Route::get('/index/{folder_path?}//{storage?}', 'FileManagerController@index')->name('index');
            Route::post('/image-upload', 'FileManagerController@upload')->name('image-upload');
            Route::delete('/delete/{file_path}', 'FileManagerController@destroy')->name('destroy');
        });

        // social media login
        Route::group(['prefix' => 'social-login', 'as' => 'social-login.', 'middleware' => ['module:business_settings']], function () {
            Route::get('view', 'BusinessSettingsController@viewSocialLogin')->name('view');
            Route::post('update/{service}', 'BusinessSettingsController@updateSocialLogin')->name('update');
        });
        Route::group(['prefix' => 'apple-login', 'as' => 'apple-login.'], function () {
            Route::post('update/{service}', 'BusinessSettingsController@updateAppleLogin')->name('update');
        });
        Route::get('store/report', function () {
            return view('store_report');
        });

        Route::group(['prefix' => 'dispatch', 'as' => 'dispatch.'], function () {
            Route::get('/', 'DashboardController@dispatch_dashboard')->name('dashboard');

            // Unified Dispatch Management Routes
            Route::get('/unified', 'DispatchController@unifiedDashboard')->name('unified');
            Route::post('/assign-delivery-man', 'DispatchController@assignDeliveryMan')->name('assign-delivery-man');
            Route::post('/bulk-assign', 'DispatchController@bulkAssign')->name('bulk-assign');
            Route::post('/auto-assign', 'DispatchController@autoAssign')->name('auto-assign');
            Route::get('/available-delivery-men', 'DispatchController@getAvailableDeliveryMen')->name('available-delivery-men');
            Route::get('/statistics', 'DispatchController@getStatistics')->name('statistics');
            Route::post('/update-priority', 'DispatchController@updatePriority')->name('update-priority');

            Route::group(['middleware' => ['module:order']], function () {
                Route::get('list/{module?}/{status?}', 'OrderController@dispatch_list')->name('list');
                Route::get('parcel/list/{module?}/{status?}', 'ParcelController@parcel_dispatch_list')->name('parcel.list');
                Route::get('order/details/{id}', 'OrderController@details')->name('order.details');
                Route::get('order/generate-invoice/{id}', 'OrderController@generate_invoice')->name('order.generate-invoice');
            });
        });

        Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
            Route::get('/', 'DashboardController@user_dashboard')->name('dashboard');
            // Route::get('disbursement-export/{id}/{type}', 'DeliveryManController@disbursement_export')->name('disbursement-export');
            // Route::get('export', 'DeliveryManController@export')->name('export');

            // Subscribed customer Routes
            Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {


                Route::group(['prefix' => 'wallet', 'as' => 'wallet.', 'middleware' => ['module:customer_management']], function () {
                    Route::get('add-fund', 'CustomerWalletController@add_fund_view')->name('add-fund');
                    Route::post('add-fund', 'CustomerWalletController@add_fund');
                    Route::post('set-date', 'CustomerWalletController@set_date')->name('set-date');
                    Route::get('report', 'CustomerWalletController@report')->name('report');
                    Route::get('export', 'CustomerWalletController@export')->name('export');
                });

                Route::group(['middleware' => ['module:customer_management']], function () {

                    // Subscribed customer Routes
                    Route::get('subscribed', 'CustomerController@subscribedCustomers')->name('subscribed');
                    // Route::post('subscriber-search', 'CustomerController@subscriberMailSearch')->name('subscriberMailSearch');
                    Route::get('subscriber-search', 'CustomerController@subscribed_customer_export')->name('subscriber-export');

                    Route::get('loyalty-point/report', 'LoyaltyPointController@report')->name('loyalty-point.report');
                    Route::get('loyalty-point/export', 'LoyaltyPointController@export')->name('loyalty-point.export');
                    Route::post('loyalty-point/set-date', 'LoyaltyPointController@set_date')->name('loyalty-point.set-date');
                    Route::get('settings', 'CustomerController@settings')->name('settings');
                    Route::post('update-settings', 'CustomerController@update_settings')->name('update-settings');
                    Route::get('export', 'CustomerController@export')->name('export');
                    Route::get('order-export', 'CustomerController@customer_order_export')->name('order-export');
                });
            });
            Route::get('customer/select-list', 'CustomerController@get_customers')->name('customer.select-list');

            Route::group(['prefix' => 'customer', 'as' => 'customer.', 'middleware' => ['module:customer_management']], function () {
                Route::get('list', 'CustomerController@customer_list')->name('list');
                Route::get('rental-view/{user_id}', 'CustomerController@rentalView')->name('rental.view');
                Route::get('view/{user_id}', 'CustomerController@view')->name('view');
                Route::post('search', 'CustomerController@search')->name('search');
                Route::get('status/{customer}/{status}file-manager', 'CustomerController@status')->name('status');
            });
            Route::group(['prefix' => 'contact', 'as' => 'contact.', 'middleware' => ['module:customer_management']], function () {
                Route::get('contact-list', 'ContactController@list')->name('contact-list');
                Route::get('contact-list-export', 'ContactController@exportList')->name('exportList');
                Route::delete('contact-delete/{id}', 'ContactController@destroy')->name('contact-delete');
                Route::get('contact-view/{id}', 'ContactController@view')->name('contact-view');
                Route::post('contact-update/{id}', 'ContactController@update')->name('contact-update');
                Route::post('contact-send-mail/{id}', 'ContactController@send_mail')->name('contact-send-mail');
                Route::post('contact-search', 'ContactController@search')->name('contact-search');
            });


        });
        Route::group(['prefix' => 'transactions', 'as' => 'transactions.'], function () {
            Route::get('/', 'DashboardController@transaction_dashboard')->name('dashboard');
            Route::get('order/details/{id}', 'OrderController@details')->name('order.details');
            Route::get('parcel/order/details/{id}', 'ParcelController@order_details')->name('parcel.order.details');
            Route::get('order/generate-invoice/{id}', 'OrderController@generate_invoice')->name('order.generate-invoice');
            Route::get('customer/view/{user_id}', 'CustomerController@view')->name('customer.view');
            Route::get('item/view/{id}', 'ItemController@view')->name('item.view');
            Route::group(['prefix' => 'report', 'as' => 'report.', 'middleware' => ['module:report']], function () {
                Route::get('order', 'ReportController@order_index')->name('order');
                Route::get('day-wise-report', 'ReportController@day_wise_report')->name('day-wise-report');
                Route::get('item-wise-report', 'ReportController@item_wise_report')->name('item-wise-report');
                Route::get('item-wise-export', 'ReportController@item_wise_export')->name('item-wise-export');
                Route::post('item-wise-report-search', 'ReportController@item_search')->name('item-wise-report-search');
                Route::post('day-wise-report-search', 'ReportController@day_search')->name('day-wise-report-search');
                Route::get('day-wise-report-export', 'ReportController@day_wise_export')->name('day-wise-report-export');
                Route::get('order-transactions', 'ReportController@order_transaction')->name('order-transaction');
                Route::get('earning', 'ReportController@earning_index')->name('earning');
                Route::post('set-date', 'ReportController@set_date')->name('set-date');
                Route::get('stock-report', 'ReportController@stock_report')->name('stock-report');
                Route::post('stock-report', 'ReportController@stock_search')->name('stock-search');
                Route::get('stock-wise-report-search', 'ReportController@stock_wise_export')->name('stock-wise-report-export');
                Route::get('order-report', 'ReportController@order_report')->name('order-report');
                Route::post('order-report-search', 'ReportController@search_order_report')->name('search_order_report');
                Route::get('order-report-export', 'ReportController@order_report_export')->name('order-report-export');
                Route::get('store-wise-report', 'ReportController@store_summary_report')->name('store-summary-report');
                Route::post('store-summary-report-search', 'ReportController@store_summary_search')->name('store-summary-report-search');
                Route::get('store-summary-report-export', 'ReportController@store_summary_export')->name('store-summary-report-export');
                Route::get('store-wise-sales-report', 'ReportController@store_sales_report')->name('store-sales-report');
                Route::get('store-wise-sales-report-export', 'ReportController@store_sales_export')->name('store-sales-report-export');
                Route::get('store-wise-order-report', 'ReportController@store_order_report')->name('store-order-report');
                Route::post('store-wise-order-report-search', 'ReportController@store_order_search')->name('store-order-report-search');
                Route::get('store-wise-order-report-export', 'ReportController@store_order_export')->name('store-order-report-export');
                Route::get('expense-report', 'ReportController@expense_report')->name('expense-report');
                Route::get('expense-export', 'ReportController@expense_export')->name('expense-export');
                Route::post('expense-report-search', 'ReportController@expense_search')->name('expense-report-search');
                Route::get('low-stock-report', 'ReportController@low_stock_report')->name('low-stock-report');
                Route::post('low-stock-report', 'ReportController@low_stock_search')->name('low-stock-search');
                Route::get('low-stock-wise-report-search', 'ReportController@low_stock_wise_export')->name('low-stock-wise-report-export');
                Route::get('disbursement-report/{tab?}', 'ReportController@disbursement_report')->name('disbursement_report');
                Route::get('disbursement-report-export/{type}/{tab?}', 'ReportController@disbursement_report_export')->name('disbursement_report_export');
            });

            Route::group(['prefix' => 'account-transaction', 'as' => 'account-transaction.', 'middleware' => ['module:collect_cash']], function () {
                Route::get('list', 'AccountTransactionController@index')->name('index');
                Route::post('store', 'AccountTransactionController@store')->name('store');
                Route::get('details/{id}', 'AccountTransactionController@show')->name('view');
                Route::delete('delete/{id}', 'AccountTransactionController@distroy')->name('delete');
                Route::post('search', 'EmployeeController@search')->name('search');
                Route::get('export', 'AccountTransactionController@export_account_transaction')->name('export');
                Route::post('search', 'AccountTransactionController@search_account_transaction')->name('search');
            });

            Route::resource('provide-deliveryman-earnings', 'ProvideDMEarningController')->middleware('module:provide_dm_earning');
            Route::get('export-deliveryman-earnings', 'ProvideDMEarningController@dm_earning_list_export')->name('export-deliveryman-earning');
            Route::post('deliveryman-earnings-search', 'ProvideDMEarningController@search_deliveryman_earning')->name('search-deliveryman-earning');

            Route::group(['prefix' => 'store', 'as' => 'store.'], function () {
                Route::get('view/{store}/{tab?}/{sub_tab?}', 'VendorController@view')->name('view');
                Route::post('status-filter', 'VendorController@status_filter')->name('status-filter');
                Route::post('withdraw-status/{id}', 'VendorController@withdrawStatus')->name('withdraw_status');
                Route::get('withdraw_list', 'VendorController@withdraw')->name('withdraw_list');
                Route::post('withdraw_search', 'VendorController@withdraw_search')->name('withdraw_search');
                Route::get('withdraw_export', 'VendorController@withdraw_export')->name('withdraw_export');
                Route::get('withdraw-view/{withdraw_id}/{seller_id}', 'VendorController@withdraw_view')->name('withdraw_view');
                Route::get('get-Withdraw-Details', 'VendorController@getWithdrawDetails')->name('getWithdrawDetails');

            });

            Route::group(['prefix' => 'withdraw-method', 'as' => 'withdraw-method.'], function () {
                Route::get('list', 'WithdrawalMethodController@list')->name('list');
                Route::get('create', 'WithdrawalMethodController@create')->name('create');
                Route::post('store', 'WithdrawalMethodController@store')->name('store');
                Route::get('edit/{id}', 'WithdrawalMethodController@edit')->name('edit');
                Route::put('update', 'WithdrawalMethodController@update')->name('update');
                Route::delete('delete/{id}', 'WithdrawalMethodController@delete')->name('delete');
                Route::post('status-update', 'WithdrawalMethodController@status_update')->name('status-update');
                Route::post('default-status-update', 'WithdrawalMethodController@default_status_update')->name('default-status-update');
                Route::get('get-method-info', 'WithdrawalMethodController@getMethodInfo')->name('getMethodInfo');
            });

            Route::group(['prefix' => 'store-disbursement', 'as' => 'store-disbursement.', 'middleware' => ['module:account']], function () {
                Route::get('list', 'StoreDisbursementController@list')->name('list');
                Route::get('details/{id}', 'StoreDisbursementController@view')->name('view');
                Route::get('status', 'StoreDisbursementController@status')->name('status');
                Route::get('change-status/{id}/{status}', 'StoreDisbursementController@statusById')->name('change-status');
                Route::get('export/{id}/{type?}', 'StoreDisbursementController@export')->name('export');
            });
            Route::group(['prefix' => 'dm-disbursement', 'as' => 'dm-disbursement.', 'middleware' => ['module:account']], function () {
                Route::get('list', 'DeliveryManDisbursementController@list')->name('list');
                Route::get('details/{id}', 'DeliveryManDisbursementController@view')->name('view');
                Route::get('export/{id}/{type?}', 'DeliveryManDisbursementController@export')->name('export');
                Route::get('status', 'DeliveryManDisbursementController@status')->name('status');
                Route::get('change-status/{id}/{status}', 'DeliveryManDisbursementController@statusById')->name('change-status');
                Route::get('export/{id}/{type?}', 'DeliveryManDisbursementController@export')->name('export');
            });

            // Payroll Management
            Route::group(['prefix' => 'payroll', 'as' => 'payroll.', 'namespace' => 'Payroll'], function () {
                Route::get('/', 'PayrollController@index')->name('index');
                Route::get('generate', 'PayrollController@generate')->name('generate');
                Route::post('generate', 'PayrollController@storePayroll')->name('store');
                Route::get('show/{id}', 'PayrollController@show')->name('show');
                Route::post('mark-paid/{id}', 'PayrollController@markPaid')->name('mark-paid');
                Route::post('cancel/{id}', 'PayrollController@cancel')->name('cancel');
                Route::post('regenerate/{id}', 'PayrollController@regenerate')->name('regenerate');
                Route::get('export', 'PayrollController@export')->name('export');

                Route::group(['prefix' => 'incentive-slabs', 'as' => 'incentive-slabs.'], function () {
                    Route::get('/', 'IncentiveSlabController@index')->name('index');
                    Route::post('store', 'IncentiveSlabController@store')->name('store');
                    Route::put('update/{id}', 'IncentiveSlabController@update')->name('update');
                    Route::delete('delete/{id}', 'IncentiveSlabController@delete')->name('delete');
                    Route::get('status/{id}/{status}', 'IncentiveSlabController@updateStatus')->name('status');
                });

                // Daily Incentive Rules
                Route::group(['prefix' => 'daily-incentives', 'as' => 'daily-incentives.'], function () {
                    Route::get('/', 'DailyIncentiveController@index')->name('index');
                    Route::post('store', 'DailyIncentiveController@store')->name('store');
                    Route::put('update/{id}', 'DailyIncentiveController@update')->name('update');
                    Route::delete('delete/{id}', 'DailyIncentiveController@delete')->name('delete');
                    Route::post('toggle/{id}', 'DailyIncentiveController@toggleStatus')->name('toggle');
                });

                // Time-Based Incentives
                Route::group(['prefix' => 'time-incentives', 'as' => 'time-incentives.'], function () {
                    Route::get('/', 'TimeBasedIncentiveController@index')->name('index');
                    Route::post('store', 'TimeBasedIncentiveController@store')->name('store');
                    Route::put('update/{id}', 'TimeBasedIncentiveController@update')->name('update');
                    Route::delete('delete/{id}', 'TimeBasedIncentiveController@delete')->name('delete');
                    Route::post('toggle/{id}', 'TimeBasedIncentiveController@toggleStatus')->name('toggle');
                });

                // Rush Incentives
                Route::group(['prefix' => 'rush-incentives', 'as' => 'rush-incentives.'], function () {
                    Route::get('/', 'RushIncentiveController@index')->name('index');
                    Route::post('store', 'RushIncentiveController@store')->name('store');
                    Route::put('update/{id}', 'RushIncentiveController@update')->name('update');
                    Route::delete('delete/{id}', 'RushIncentiveController@delete')->name('delete');
                    Route::post('toggle/{id}', 'RushIncentiveController@toggleStatus')->name('toggle');
                    Route::get('active', 'RushIncentiveController@activeRushes')->name('active');
                    Route::post('manual-activate', 'RushIncentiveController@manualActivate')->name('manual-activate');
                    Route::post('deactivate/{id}', 'RushIncentiveController@deactivateRush')->name('deactivate');
                });

                // Fuel Incentives
                Route::group(['prefix' => 'fuel-incentives', 'as' => 'fuel-incentives.'], function () {
                    Route::get('/', 'FuelIncentiveController@index')->name('index');
                    Route::post('store', 'FuelIncentiveController@store')->name('store');
                    Route::put('update/{id}', 'FuelIncentiveController@update')->name('update');
                    Route::delete('delete/{id}', 'FuelIncentiveController@destroy')->name('delete');
                });

                // Min Wage Guarantee
                Route::group(['prefix' => 'min-wage', 'as' => 'min-wage.'], function () {
                    Route::get('/', 'MinWageController@index')->name('index');
                    Route::post('store', 'MinWageController@store')->name('store');
                    Route::delete('delete/{id}', 'MinWageController@destroy')->name('delete');
                    Route::post('approve-adjustment/{id}', 'MinWageController@approveAdjustment')->name('approve-adjustment');
                    Route::post('reject-adjustment/{id}', 'MinWageController@rejectAdjustment')->name('reject-adjustment');
                });
            });

            // Performance Tiers & Leaderboard & Shift Management
            Route::group(['prefix' => 'dm-performance', 'as' => 'dm-performance.', 'namespace' => 'DeliveryMan'], function () {
                // Performance Tiers
                Route::group(['prefix' => 'tiers', 'as' => 'tiers.'], function () {
                    Route::get('/', 'PerformanceTierController@index')->name('index');
                    Route::post('store', 'PerformanceTierController@store')->name('store');
                    Route::put('update/{id}', 'PerformanceTierController@update')->name('update');
                    Route::delete('delete/{id}', 'PerformanceTierController@destroy')->name('delete');
                    Route::post('toggle/{id}', 'PerformanceTierController@toggleStatus')->name('toggle');
                    Route::post('recalculate-all', 'PerformanceTierController@recalculateAllTiers')->name('recalculate');
                });

                // Leaderboard
                Route::group(['prefix' => 'leaderboard', 'as' => 'leaderboard.'], function () {
                    Route::get('/', 'LeaderboardController@index')->name('index');
                    Route::post('generate', 'LeaderboardController@generateLeaderboard')->name('generate');
                });

                // Shift Management
                Route::group(['prefix' => 'shifts', 'as' => 'shifts.'], function () {
                    Route::get('/', 'ShiftManagementController@index')->name('index');
                    Route::post('assign', 'ShiftManagementController@assignShift')->name('assign');
                    Route::post('bulk-assign', 'ShiftManagementController@bulkAssign')->name('bulk-assign');
                    Route::get('swap-requests', 'ShiftManagementController@shiftSwapRequests')->name('swap-requests');
                    Route::post('approve-swap/{id}', 'ShiftManagementController@approveSwap')->name('approve-swap');
                    Route::post('reject-swap/{id}', 'ShiftManagementController@rejectSwap')->name('reject-swap');
                });

                // Auto Assignment Settings
                Route::group(['prefix' => 'auto-assign', 'as' => 'auto-assign.'], function () {
                    Route::get('/', 'AutoAssignSettingsController@index')->name('index');
                    Route::post('store', 'AutoAssignSettingsController@store')->name('store');
                    Route::delete('delete/{id}', 'AutoAssignSettingsController@destroy')->name('delete');
                });

                // SOS Dashboard
                Route::group(['prefix' => 'sos', 'as' => 'sos.'], function () {
                    Route::get('/', 'SosController@index')->name('index');
                    Route::post('resolve/{id}', 'SosController@resolve')->name('resolve');
                });

                // Instant Withdrawals
                Route::group(['prefix' => 'instant-withdrawals', 'as' => 'instant-withdrawals.'], function () {
                    Route::get('/', 'InstantWithdrawalController@index')->name('index');
                    Route::post('approve/{id}', 'InstantWithdrawalController@approve')->name('approve');
                    Route::post('reject/{id}', 'InstantWithdrawalController@reject')->name('reject');
                });
            });

        });

    // Bargaining Mode Management (Phase 4 - Admin Panel)
    Route::group(['prefix' => 'bargaining', 'as' => 'bargaining.'], function () {
        Route::get('dashboard', 'BargainingController@dashboard')->name('dashboard');
        Route::get('requests', 'BargainingController@requests')->name('requests');
        Route::get('requests/{id}', 'BargainingController@requestDetails')->name('request-details');
        Route::get('analytics', 'BargainingController@analytics')->name('analytics');
        Route::get('settings', 'BargainingController@settings')->name('settings');
        Route::post('settings', 'BargainingController@updateSettings')->name('update-settings');
        Route::post('settings/store/{storeId}', 'BargainingController@updateStoreSettings')->name('update-store-settings');
        Route::get('export', 'BargainingController@export')->name('export');
        Route::post('requests/{id}/cancel', 'BargainingController@cancelRequest')->name('cancel-request');
    });

    // ==============================================================================
    // WhatsApp Webhooks - PUBLIC ENDPOINTS (No Auth Middleware)
    // ==============================================================================
    // These endpoints are called by WhatsApp Business API
    Route::prefix('whatsapp/webhook')->name('whatsapp.webhook.')->group(function () {
        // Webhook verification (GET) - WhatsApp calls this during setup
        Route::get('/', 'Admin\WhatsApp\WhatsAppWebhookController@verify')->name('verify');

        // Webhook handler (POST) - WhatsApp calls this for status updates & inbound messages
        Route::post('/', 'Admin\WhatsApp\WhatsAppWebhookController@handle')->name('handle');

        // Health check
        Route::get('/health', 'Admin\WhatsApp\WhatsAppWebhookController@health')->name('health');
    });

        // WhatsApp Campaigns - Enhanced with Customer Targeting & Analytics
        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            // Dashboard
            Route::get('/', 'WhatsApp\DashboardController@index')->name('dashboard');

            // Customers
            Route::get('customers', 'WhatsApp\CustomerController@index')->name('customers.index');
            Route::get('customers/{id}', 'WhatsApp\CustomerController@show')->name('customers.show');

            // Segments
            Route::get('segments', 'WhatsApp\SegmentController@index')->name('segments.index');
            Route::post('segments', 'WhatsApp\SegmentController@store')->name('segments.store');
            Route::get('segments/{id}/customers', 'WhatsApp\SegmentController@preview')->name('segments.preview');
            Route::post('segments/{id}/refresh', 'WhatsApp\SegmentController@refresh')->name('segments.refresh');

            // Campaigns
            Route::get('campaigns', 'WhatsApp\CampaignController@index')->name('campaigns.index');
            Route::get('campaigns/create', 'WhatsApp\CampaignController@create')->name('campaigns.create');
            Route::post('campaigns', 'WhatsApp\CampaignController@store')->name('campaigns.store');
            Route::get('campaigns/{id}', 'WhatsApp\CampaignController@show')->name('campaigns.show');
            Route::post('campaigns/{id}/cancel', 'WhatsApp\CampaignController@cancel')->name('campaigns.cancel');
            Route::post('campaigns/{id}/clone', 'WhatsApp\CampaignController@clone')->name('campaigns.clone');
            Route::post('campaigns/{id}/pause', 'WhatsApp\CampaignController@pause')->name('campaigns.pause');
            Route::post('campaigns/{id}/resume', 'WhatsApp\CampaignController@resume')->name('campaigns.resume');
            Route::get('campaigns/{id}/export', 'WhatsApp\CampaignController@export')->name('campaigns.export');

            // Templates (NEW) - DISABLED: TemplateController not implemented yet
            // Route::prefix('templates')->name('templates.')->group(function () {
            //     Route::get('/', 'WhatsApp\TemplateController@index')->name('index');
            //     Route::get('/create', 'WhatsApp\TemplateController@create')->name('create');
            //     Route::post('/', 'WhatsApp\TemplateController@store')->name('store');
            //     Route::get('/{id}', 'WhatsApp\TemplateController@show')->name('show');
            //     Route::get('/{id}/edit', 'WhatsApp\TemplateController@edit')->name('edit');
            //     Route::put('/{id}', 'WhatsApp\TemplateController@update')->name('update');
            //     Route::delete('/{id}', 'WhatsApp\TemplateController@destroy')->name('destroy');
            //     Route::post('/{id}/submit', 'WhatsApp\TemplateController@submit')->name('submit');
            //     Route::post('/preview', 'WhatsApp\TemplateController@preview')->name('preview');
            // });

            // A/B Tests (NEW) - DISABLED: ABTestController not implemented yet
            // Route::prefix('ab-tests')->name('ab-tests.')->group(function () {
            //     Route::get('/', 'WhatsApp\ABTestController@index')->name('index');
            //     Route::get('/create', 'WhatsApp\ABTestController@create')->name('create');
            //     Route::post('/', 'WhatsApp\ABTestController@store')->name('store');
            //     Route::get('/{id}', 'WhatsApp\ABTestController@show')->name('show');
            //     Route::get('/{id}/results', 'WhatsApp\ABTestController@results')->name('results');
            //     Route::post('/{id}/send-winner', 'WhatsApp\ABTestController@sendWinner')->name('send-winner');
            // });

            // Inbox (NEW) - DISABLED: InboxController not implemented yet
            // Route::prefix('inbox')->name('inbox.')->group(function () {
            //     Route::get('/', 'WhatsApp\InboxController@index')->name('index');
            //     Route::get('/{id}', 'WhatsApp\InboxController@show')->name('show');
            //     Route::post('/{id}/reply', 'WhatsApp\InboxController@reply')->name('reply');
            //     Route::post('/{id}/assign', 'WhatsApp\InboxController@assign')->name('assign');
            //     Route::post('/{id}/tag', 'WhatsApp\InboxController@tag')->name('tag');
            //     Route::post('/{id}/mark-read', 'WhatsApp\InboxController@markRead')->name('mark-read');
            // });

            // Legacy routes (backward compatibility with old dashboard)
            Route::get('legacy', 'WhatsAppController@index')->name('legacy.index');
            Route::post('upload-media', 'WhatsAppController@uploadMedia')->name('upload');
            Route::post('send-blast', 'WhatsAppController@sendBlast')->name('blast');
            Route::get('status', 'WhatsAppController@status')->name('status');
        });

    });

    // Employee Application Management (Super Admin Only) - Separate middleware group
    Route::group(['prefix' => 'employee-application', 'as' => 'employee-application.', 'middleware' => ['admin', 'super-admin']], function () {
        // List applications
        Route::get('/', 'EmployeeApplicationController@index')->name('list');

        // View application details
        Route::get('{applicationId}/view', 'EmployeeApplicationController@view')->name('view');

        // Edit application
        Route::get('{applicationId}/edit', 'EmployeeApplicationController@edit')->name('edit');
        Route::post('{applicationId}/update', 'EmployeeApplicationController@update')->name('update');

        // Approve/Deny actions
        Route::post('{applicationId}/approve', 'EmployeeApplicationController@approve')->name('approve');
        Route::post('{applicationId}/deny', 'EmployeeApplicationController@deny')->name('deny');

        // Invitation management
        Route::get('invite', 'EmployeeApplicationController@showInviteForm')->name('invite');
        Route::post('invite', 'EmployeeApplicationController@sendInvitation')->name('invite.send');
    });
});
Route::post('admin/regular-items/search-by-barcode', [\App\Http\Controllers\Admin\ItemController::class, 'searchByBarcode'])->name('admin.regular_items.search_by_barcode');
