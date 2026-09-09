<!-- Cart Table Content (for AJAX refresh) -->
<tbody id="cart-table-body">
    @php
        $index = 1;
        $totalRevenue = 0;
        $totalCartItems = 0;
        foreach($latest_carts as $carts) {
            $totalCartItems += $carts->sum('quantity');
        }
    @endphp
    @foreach($latest_carts as $userId => $userCarts)
        @php
            $customer = $users->get($userId);
            if (!$customer) continue;
            
            $totalItems = $userCarts->sum('quantity');
            $cartTotal = $userCarts->sum(function($cart) {
                return ($cart->price ?? 0) * ($cart->quantity ?? 0);
            });
            $totalRevenue += $cartTotal;
            $cartAge = \Carbon\Carbon::parse($userCarts->first()->created_at);
            $minutesAgo = $cartAge->diffInMinutes(now());
        @endphp
        
        <tr class="cart-row-item" data-index="{{$index}}">
            <!-- Index -->
            <td class="text-center">
                <span class="badge badge-soft-secondary">{{$index++}}</span>
            </td>
            
            <!-- Customer Info -->
            <td>
                <div class="media align-items-center">
                    <div class="avatar avatar-circle mr-3">
                        <img class="avatar-img"
                             onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                             src="{{$customer->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg')}}"
                             alt="{{$customer->f_name}}">
                    </div>
                    <div class="media-body">
                        @if(Route::has('admin.customer.view'))
                            <a class="text-body" href="{{route('admin.customer.view', ['user_id' => $customer->id])}}">
                                <strong>{{$customer->f_name}} {{$customer->l_name}}</strong>
                            </a>
                        @else
                            <strong class="text-dark">{{$customer->f_name}} {{$customer->l_name}}</strong>
                        @endif
                        @if($customer->phone)
                            <a href="tel:{{$customer->phone}}" class="d-flex align-items-center text-primary text-decoration-none">
                                <i class="tio-call mr-1"></i>
                                <span>{{$customer->phone}}</span>
                            </a>
                        @endif
                    </div>
                </div>
            </td>
            
            <!-- Cart Items -->
            <td>
                <div style="max-height: 120px; overflow-y: auto;">
                    @foreach($userCarts as $cartItem)
                        @php
                            $item = $items->get($cartItem->item_id);
                        @endphp
                        @if($item)
                            <div class="d-flex align-items-center justify-content-between mb-1 p-2 border rounded">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <span class="badge badge-primary mr-2">{{$cartItem->quantity}}x</span>
                                    <span class="text-dark">{{Str::limit($item->name ?? 'Item', 40)}}</span>
                                </div>
                                <span class="text-muted font-weight-bold ml-2">
                                    {{\App\CentralLogics\Helpers::format_currency($cartItem->price * $cartItem->quantity)}}
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </td>
            
            <!-- Total Items -->
            <td class="text-center">
                <span class="badge badge-soft-info">{{$totalItems}} items</span>
            </td>
            
            <!-- Cart Total -->
            <td class="text-right">
                <strong class="text-primary" style="font-size: 16px;">
                    {{\App\CentralLogics\Helpers::format_currency($cartTotal)}}
                </strong>
            </td>
            
            <!-- Cart Age -->
            <td class="text-center">
                @if($minutesAgo < 5)
                    <span class="badge badge-danger">
                        <i class="tio-time"></i> {{$minutesAgo}}m
                    </span>
                    <div class="small text-danger mt-1">HOT</div>
                @elseif($minutesAgo < 30)
                    <span class="badge badge-warning">
                        <i class="tio-time"></i> {{$minutesAgo}}m
                    </span>
                    <div class="small text-muted mt-1">Active</div>
                @elseif($minutesAgo < 1440)
                    <span class="badge badge-info">
                        <i class="tio-time"></i> {{$cartAge->diffInHours(now())}}h
                    </span>
                @else
                    <span class="badge badge-secondary">
                        <i class="tio-time"></i> {{$cartAge->diffInDays(now())}}d
                    </span>
                @endif
            </td>
            
            <!-- Actions -->
            <td class="text-center">
                @if(Route::has('admin.customer.view'))
                    <a class="btn btn-sm btn-outline-primary action-btn"
                       href="{{route('admin.customer.view', ['user_id' => $customer->id])}}"
                       data-toggle="tooltip"
                       title="View Customer">
                        <i class="tio-visible"></i>
                    </a>
                @endif
            </td>
        </tr>
    @endforeach
</tbody>

<!-- Footer Summary -->
<tfoot class="thead-light">
    <tr>
        <td colspan="3" class="text-right">
            <strong>Total Potential Revenue:</strong>
        </td>
        <td class="text-center">
            <strong id="total-items-count">{{$totalCartItems}}</strong>
        </td>
        <td class="text-right">
            <strong class="text-success" style="font-size: 16px;" id="total-revenue">
                {{\App\CentralLogics\Helpers::format_currency($totalRevenue)}}
            </strong>
        </td>
        <td colspan="2"></td>
    </tr>
</tfoot>

