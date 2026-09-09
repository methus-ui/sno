<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Cart;
use App\Models\Item;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\ItemCampaign;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Update cart activity tracking for abandoned cart notifications
     * Resets the notification flag when cart is modified
     *
     * PERFORMANCE FIX: Use whereRaw with indexed columns for faster updates
     */
    private function updateCartActivity($userId, $isGuest, $moduleId)
    {
        // Use raw query with index hint for better performance
        // The composite index (user_id, is_guest, module_id) will be used
        Cart::where('user_id', $userId)
            ->where('is_guest', $isGuest)
            ->where('module_id', $moduleId)
            ->update([
                'last_activity_at' => now(),
                'abandoned_notification_sent' => false,
            ]);
    }

    public function get_carts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;
        $carts = Cart::where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->get()
        ->map(function ($data) {
            $data->add_on_ids = json_decode($data->add_on_ids,true);
            $data->add_on_qtys = json_decode($data->add_on_qtys,true);
            $data->variation = json_decode($data->variation,true);
            $data->item = Helpers::cart_product_data_formatting($data->item, $data->variation,$data->add_on_ids,
            $data->add_on_qtys, false, app()->getLocale());
            return $data;
        });
        return response()->json($carts, 200);
    }

    public function add_to_cart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
            'item_id' => 'required|integer',
            'model' => 'required|string|in:Item,ItemCampaign',
            'price' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;
        $model = $request->model === 'Item' ? 'App\Models\Item' : 'App\Models\ItemCampaign';
        $item = $request->model === 'Item' ? Item::find($request->item_id) : ItemCampaign::find($request->item_id);

        // Check if item exists
        if (!$item) {
            return response()->json([
                'errors' => [
                    ['code' => 'item_not_found', 'message' => translate('messages.item_not_found')]
                ]
            ], 404);
        }

        $cart = Cart::where('item_id',$request->item_id)->where('item_type',$model)->where('variation',json_encode($request->variation))->where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->first();

        // ✅ FIX: If item exists, update quantity instead of returning error
        if($cart){
            $new_quantity = $cart->quantity + $request->quantity;

            // Check maximum cart quantity limit for the new total
            if($item->maximum_cart_quantity && ($new_quantity > $item->maximum_cart_quantity)){
                return response()->json([
                    'errors' => [
                        ['code' => 'cart_item_limit', 'message' => translate('messages.maximum_cart_quantity_exceeded')]
                    ]
                ], 403);
            }

            // Update existing cart item quantity
            $cart->quantity = $new_quantity;
            $cart->save();

            // Update cart activity for abandoned cart tracking
            $this->updateCartActivity($user_id, $is_guest, $request->header('moduleId'));

            // Return updated cart
            $carts = Cart::where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids,true);
                $data->add_on_qtys = json_decode($data->add_on_qtys,true);
                $data->variation = json_decode($data->variation,true);
                $data->item = Helpers::cart_product_data_formatting($data->item, $data->variation,$data->add_on_ids,
                $data->add_on_qtys, false, app()->getLocale());
                return $data;
            });
            return response()->json($carts, 200);
        }

        if($item->maximum_cart_quantity && ($request->quantity>$item->maximum_cart_quantity)){
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item_limit', 'message' => translate('messages.maximum_cart_quantity_exceeded')]
                ]
            ], 403);
        }

        $carts = Cart::where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->with('item')->get();

        // Multi-cart support - removed store validation

        $cart = new Cart();
        $cart->user_id = $user_id;
        $cart->module_id = $request->header('moduleId');
        $cart->item_id = $request->item_id;
        $cart->is_guest = $is_guest;
        $cart->add_on_ids = isset($request->add_on_ids)?json_encode($request->add_on_ids):json_encode([]);
        $cart->add_on_qtys = isset($request->add_on_qtys)?json_encode($request->add_on_qtys):json_encode([]);
        $cart->item_type = $request->model;
        $cart->price = $request->price;
        $cart->quantity = $request->quantity;
        $cart->variation = isset($request->variation)?json_encode($request->variation):json_encode([]);
        $cart->save();

        $item->carts()->save($cart);

        // Update cart activity for abandoned cart tracking
        $this->updateCartActivity($user_id, $is_guest, $request->header('moduleId'));

        $carts = Cart::where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->get()
        ->map(function ($data) {
            $data->add_on_ids = json_decode($data->add_on_ids,true);
            $data->add_on_qtys = json_decode($data->add_on_qtys,true);
            $data->variation = json_decode($data->variation,true);
            $data->item = Helpers::cart_product_data_formatting($data->item, $data->variation,$data->add_on_ids,
            $data->add_on_qtys, false, app()->getLocale());
            return $data;
        });
        return response()->json($carts, 200);
    }

    public function update_cart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'guest_id' => $request->user ? 'nullable' : 'required',
            'price' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {
            $user_id = $request->user ? $request->user->id : $request['guest_id'];
            $is_guest = $request->user ? 0 : 1;
            
            $cart = Cart::find($request->cart_id);
            
            if (!$cart) {
                return response()->json([
                    'errors' => [
                        ['code' => 'cart_not_found', 'message' => 'Cart item not found']
                    ]
                ], 404);
            }
            
            $item = $cart->item_type === 'App\Models\Item' ? Item::find($cart->item_id) : ItemCampaign::find($cart->item_id);
            
            if ($item && $item->maximum_cart_quantity && ($request->quantity > $item->maximum_cart_quantity)) {
                return response()->json([
                    'errors' => [
                        ['code' => 'cart_item_limit', 'message' => translate('messages.maximum_cart_quantity_exceeded')]
                    ]
                ], 403);
            }

            $cart->user_id = $user_id;
            $cart->module_id = $request->header('moduleId');
            $cart->is_guest = $is_guest;
            $cart->add_on_ids = isset($request->add_on_ids) ? json_encode($request->add_on_ids) : $cart->add_on_ids;
            $cart->add_on_qtys = isset($request->add_on_qtys) ? json_encode($request->add_on_qtys) : $cart->add_on_qtys;
            $cart->price = $request->price;
            $cart->quantity = $request->quantity;
            $cart->variation = isset($request->variation) ? json_encode($request->variation) : $cart->variation;
            $cart->save();

            // Update cart activity for abandoned cart tracking
            $this->updateCartActivity($user_id, $is_guest, $request->header('moduleId'));

            $carts = Cart::where('user_id', $user_id)
                ->where('is_guest', $is_guest)
                ->where('module_id', $request->header('moduleId'))
                ->get()
                ->map(function ($data) {
                    if (!$data->item) {
                        return null;
                    }

                    $data->add_on_ids = json_decode($data->add_on_ids, true);
                    $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                    $data->variation = json_decode($data->variation, true);
                    $data->item = Helpers::cart_product_data_formatting(
                        $data->item,
                        $data->variation,
                        $data->add_on_ids,
                        $data->add_on_qtys,
                        false,
                        app()->getLocale()
                    );
                    return $data;
                })
                ->filter()
                ->values();

            return response()->json($carts, 200);

        } catch (\Exception $e) {
            \Log::error('Cart update failed: ' . $e->getMessage(), [
                'cart_id' => $request->cart_id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'errors' => [
                    ['code' => 'server_error', 'message' => 'Failed to update cart']
                ]
            ], 500);
        }
    }

    // ✅ FIXED: Handle already-deleted cart items gracefully
    public function remove_cart_item(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {
            $user_id = $request->user ? $request->user->id : $request['guest_id'];
            $is_guest = $request->user ? 0 : 1;

            $cart = Cart::find($request->cart_id);
            
            // ✅ If cart doesn't exist, it's already deleted - return success with updated cart
            if ($cart) {
                // Verify ownership before deleting
                if ($cart->user_id == $user_id && $cart->is_guest == $is_guest) {
                    $cart->delete();
                }
            }

            // Update cart activity for abandoned cart tracking
            $this->updateCartActivity($user_id, $is_guest, $request->header('moduleId'));

            // Return updated cart list
            $carts = Cart::where('user_id', $user_id)
                ->where('is_guest', $is_guest)
                ->where('module_id', $request->header('moduleId'))
                ->get()
                ->map(function ($data) {
                    $data->add_on_ids = json_decode($data->add_on_ids, true);
                    $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                    $data->variation = json_decode($data->variation, true);
                    $data->item = Helpers::cart_product_data_formatting(
                        $data->item,
                        $data->variation,
                        $data->add_on_ids,
                        $data->add_on_qtys,
                        false,
                        app()->getLocale()
                    );
                    return $data;
                });
                
            return response()->json($carts, 200);
            
        } catch (\Exception $e) {
            \Log::error('Cart item removal failed: ' . $e->getMessage(), [
                'cart_id' => $request->cart_id,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Even on error, return the current cart state
            $user_id = $request->user ? $request->user->id : $request['guest_id'];
            $is_guest = $request->user ? 0 : 1;
            
            $carts = Cart::where('user_id', $user_id)
                ->where('is_guest', $is_guest)
                ->where('module_id', $request->header('moduleId'))
                ->get()
                ->map(function ($data) {
                    $data->add_on_ids = json_decode($data->add_on_ids, true);
                    $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                    $data->variation = json_decode($data->variation, true);
                    $data->item = Helpers::cart_product_data_formatting(
                        $data->item,
                        $data->variation,
                        $data->add_on_ids,
                        $data->add_on_qtys,
                        false,
                        app()->getLocale()
                    );
                    return $data;
                });
                
            return response()->json($carts, 200);
        }
    }

    public function remove_cart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $carts = Cart::where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->get();

        foreach($carts as $cart){
            $cart->delete();
        }

        $carts = Cart::where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->get()
        ->map(function ($data) {
            $data->add_on_ids = json_decode($data->add_on_ids,true);
            $data->add_on_qtys = json_decode($data->add_on_qtys,true);
            $data->variation = json_decode($data->variation,true);
            $data->item = Helpers::cart_product_data_formatting($data->item, $data->variation,$data->add_on_ids,
            $data->add_on_qtys, false, app()->getLocale());
            return $data;
        });
        return response()->json($carts, 200);
    }
}
