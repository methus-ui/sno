{{-- Customer Carts Grid Partial - resources/views/vendor-views/partials/_customer-carts-grid.blade.php --}}

@foreach($latest_carts as $userId => $carts)
    @php
        $user = $users->get($userId);
        if (!$user) continue;
        
        $cartTotal = $carts->sum(function($cart) {
            return ($cart->price ?? 0) * ($cart->quantity ?? 0);
        });
        $totalItems = $carts->sum('quantity');
        $cartAge = \Carbon\Carbon::parse($carts->first()->created_at)->diffForHumans();
        $minutesAgo = \Carbon\Carbon::parse($carts->first()->created_at)->diffInMinutes(now());
        
        // Get unique stores
        $storeIds = collect();
        foreach($carts as $cart) {
            $item = $items->get($cart->item_id);
            if ($item && $item->store_id) {
                $storeIds->push($item->store_id);
            }
        }
        $uniqueStores = $storeIds->unique()->count();
        
        // Get first 4 items with images
        $displayItems = $carts->take(4);
        $remainingCount = max(0, $carts->count() - 4);
    @endphp
    
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3 cart-card-item cart-card-animated"
         style="{{$loop->index >= 8 ? 'display: none;' : ''}}">
        <div class="card card-hover-shadow h-100" onclick="showCartDetails({{$userId}}, {{$user->id}})">
            <div class="card-header border-0 pb-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center min-width-0 flex-grow-1">
                        <div class="avatar avatar-circle mr-2">
                            <img class="avatar-img"
                                 onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                                 src="{{$user->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                 alt="{{$user->f_name}}">
                        </div>
                        <div class="min-width-0">
                            <h6 class="mb-0 text-truncate">{{$user->f_name}} {{$user->l_name}}</h6>
                            <small class="text-muted text-truncate d-block">{{$cartAge}}</small>
                        </div>
                    </div>
                    @if($minutesAgo < 5)
                        <span class="badge badge-soft-danger badge-pill ml-2">
                            <i class="tio-bolt"></i>
                        </span>
                    @endif
                </div>
            </div>
            
            <div class="card-body py-2">
                {{-- Product Grid --}}
                <div class="product-grid mb-2">
                    @foreach($displayItems as $index => $cart)
                        @php
                            $item = $items->get($cart->item_id);
                        @endphp
                        @if($item)
                            <div class="product-img-box">
                                <img src="{{$item->image_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg')}}"
                                     onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                     alt="{{$item->name}}">
                                <span class="badge badge-primary qty-badge">{{$cart->quantity}}</span>
                            </div>
                        @endif
                    @endforeach
                    
                    @if($remainingCount > 0)
                        <div class="product-img-box more-items">
                            <div class="more-text">+{{$remainingCount}}</div>
                        </div>
                    @endif
                </div>
                
                {{-- Stats --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">
                        <i class="tio-shopping-basket"></i> {{$totalItems}} {{translate('messages.items')}}
                    </small>
                    @if($uniqueStores > 0)
                        <small class="text-muted">
                            <i class="tio-shop"></i> {{$uniqueStores}} {{translate('messages.store')}}{{$uniqueStores > 1 ? 's' : ''}}
                        </small>
                    @endif
                </div>
            </div>
            
            <div class="card-footer border-top pt-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">{{translate('messages.cart_total')}}</span>
                    <h5 class="mb-0 text-primary">{{\App\CentralLogics\Helpers::format_currency($cartTotal)}}</h5>
                </div>
            </div>
        </div>
    </div>
@endforeach

@if($latest_carts->count() == 0)
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <img src="{{asset('public/assets/admin/img/empty-cart.png')}}"
                     alt="No carts"
                     class="mb-3"
                     style="width: 100px;">
                <h5>{{translate('messages.no_active_carts')}}</h5>
                <p class="text-muted">{{translate('messages.customers_have_no_items_in_cart')}}</p>
            </div>
        </div>
    </div>
@endif
