@php $index = 1; @endphp
@forelse($latest_carts as $userId => $userCarts)
    @php
        $customer = $users->get($userId);
        if (!$customer) continue;
        
        $totalItems = $userCarts->sum('quantity');
        $cartTotal = $userCarts->sum(function($cart) {
            return ($cart->price ?? 0) * ($cart->quantity ?? 0);
        });
        $cartAge = \Carbon\Carbon::parse($userCarts->first()->created_at);
        $minutesAgo = $cartAge->diffInMinutes(now());
    @endphp
    
    <!-- Compact Cart Card -->
    <div class="cart-card-item" data-index="{{$index}}" style="{{$index > 8 ? 'display: none;' : ''}}">
        <div class="card h-100 cart-card-modern">
            <!-- Top Section: Customer -->
            <div class="card-header bg-white border-bottom-0 pb-2">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm avatar-circle mr-2">
                        <img class="avatar-img"
                             onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                             src="{{$customer->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg')}}">
                    </div>
                    <div class="flex-grow-1">
                        <div class="font-weight-semibold text-dark" style="font-size: 13px;">
                            {{$customer->f_name}} {{$customer->l_name}}
                        </div>
                        <small class="text-muted d-block">
                            <i class="tio-call-outlined"></i> {{$customer->phone ?? 'N/A'}}
                        </small>
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="card-body py-2 px-3">
                <div class="cart-items-compact">
                    @foreach($userCarts->take(3) as $cartItem)
                        @php
                            $item = $items->get($cartItem->item_id);
                        @endphp
                        @if($item)
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="text-truncate flex-grow-1 pr-2">
                                    <span class="badge badge-primary badge-sm">{{$cartItem->quantity}}</span>
                                    <small class="ml-1">{{Str::limit($item->name ?? 'Item', 20)}}</small>
                                </div>
                                <small class="text-muted">
                                    {{\App\CentralLogics\Helpers::format_currency($cartItem->price * $cartItem->quantity)}}
                                </small>
                            </div>
                        @endif
                    @endforeach
                    @if($userCarts->count() > 3)
                        <button class="btn btn-link btn-sm p-0 text-primary"
                                onclick="showAllItems(event, {{$userId}})">
                            <i class="tio-add"></i> {{$userCarts->count() - 3}} more items
                        </button>
                    @endif
                </div>
            </div>

            <!-- Bottom: Summary -->
            <div class="card-footer bg-light py-2 px-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge badge-soft-info badge-sm">{{$totalItems}} items</span>
                    @if($minutesAgo < 5)
                        <span class="badge badge-danger badge-sm">
                            <i class="tio-time"></i> {{$minutesAgo}}m ago
                        </span>
                    @elseif($minutesAgo < 30)
                        <span class="badge badge-warning badge-sm">
                            <i class="tio-time"></i> {{$minutesAgo}}m ago
                        </span>
                    @elseif($minutesAgo < 1440)
                        <span class="badge badge-info badge-sm">
                            <i class="tio-time"></i> {{$cartAge->diffInHours(now())}}h ago
                        </span>
                    @else
                        <span class="badge badge-secondary badge-sm">
                            <i class="tio-time"></i> {{$cartAge->diffInDays(now())}}d ago
                        </span>
                    @endif
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-primary">
                        {{\App\CentralLogics\Helpers::format_currency($cartTotal)}}
                    </strong>
                    <button class="btn btn-sm btn-primary" onclick="convertToOrder({{$userId}})">
                        <i class="tio-checkmark-circle"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    @php $index++; @endphp
@empty
    <div class="col-12 text-center py-5">
        <img src="{{asset('public/assets/admin/svg/illustrations/sorry.svg')}}" alt="No carts" class="w-100px mb-3">
        <h5 class="text-muted">{{ translate('messages.no_active_carts') }}</h5>
    </div>
@endforelse
