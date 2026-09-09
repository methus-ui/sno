<!-- Cart Grid Items -->
@php $index = 1; @endphp
@foreach($latest_carts as $userId => $userCarts)
    @php
        $customer = $users->get($userId);
        if (!$customer) continue;
        
        $firstCartItem = $userCarts->first();
        $firstItem = $items->get($firstCartItem->item_id);
        $store = $firstItem ? \App\Models\Store::find($firstItem->store_id) : null;
        
        $totalItems = $userCarts->sum('quantity');
        $cartTotal = $userCarts->sum(function($cart) {
            return ($cart->price ?? 0) * ($cart->quantity ?? 0);
        });
        
        $cartAge = \Carbon\Carbon::parse($userCarts->first()->created_at);
        $minutesAgo = $cartAge->diffInMinutes(now());
        
        $storeIds = collect();
        foreach($userCarts as $cart) {
            $item = $items->get($cart->item_id);
            if ($item && $item->store_id) {
                $storeIds->push($item->store_id);
            }
        }
        $uniqueStores = $storeIds->unique()->count();
    @endphp
    
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3 cart-card-item cart-card-animated" data-index="{{$index}}" style="{{$index > 8 ? 'display: none;' : ''}}">
        <div class="card card-hover-shadow h-100" onclick="showCartDetails({{$userId}}, {{$customer->id}})">
            <div class="card-header border-0 pb-2">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm avatar-circle mr-2">
                        <img class="avatar-img"
                             onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                             src="{{$customer->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg')}}">
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <h6 class="mb-0 text-body text-truncate">{{$customer->f_name}} {{$customer->l_name}}</h6>
                        <small class="text-muted d-block text-truncate">
                            <i class="tio-call"></i> {{$customer->phone ?? 'N/A'}}
                        </small>
                    </div>
                    @if($minutesAgo < 5)
                        <span class="badge badge-danger">HOT</span>
                    @endif
                </div>
            </div>

            <div class="card-body p-2">
                <div class="product-grid">
                    @foreach($userCarts->take(4) as $cartItem)
                        @php
                            $item = $items->get($cartItem->item_id);
                        @endphp
                        @if($item)
                            <div class="product-img-box">
                                <img class="img-fluid rounded"
                                     onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                     src="{{$item->image_full_url}}"
                                     alt="{{$item->name}}">
                                <span class="qty-badge badge badge-primary">{{$cartItem->quantity}}</span>
                            </div>
                        @endif
                    @endforeach
                    @if($userCarts->count() > 4)
                        <div class="product-img-box more-items">
                            <div class="more-text">+{{$userCarts->count() - 4}}</div>
                        </div>
                    @endif
                </div>
            </div>

            @if($store)
            <div class="px-3 pb-2">
                <div class="media align-items-center">
                    <i class="tio-shop text-primary mr-2"></i>
                    <div class="media-body text-truncate">
                        <small class="text-body">{{Str::limit($store->name, 18)}}</small>
                    </div>
                    @if($uniqueStores > 1)
                        <span class="badge badge-soft-primary badge-pill">+{{$uniqueStores - 1}}</span>
                    @endif
                </div>
            </div>
            @endif

            <div class="card-footer border-top">
                <div class="row align-items-center gx-2">
                    <div class="col">
                        <span class="badge badge-soft-info">{{$totalItems}} items</span>
                        <span class="badge badge-soft-secondary ml-1">
                            @if($minutesAgo < 60)
                                {{$minutesAgo}}m
                            @elseif($minutesAgo < 1440)
                                {{$cartAge->diffInHours(now())}}h
                            @else
                                {{$cartAge->diffInDays(now())}}d
                            @endif
                        </span>
                    </div>
                    <div class="col-auto">
                        <h5 class="mb-0 text-primary animated-value">
                            {{\App\CentralLogics\Helpers::format_currency($cartTotal)}}
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @php $index++; @endphp
@endforeach

