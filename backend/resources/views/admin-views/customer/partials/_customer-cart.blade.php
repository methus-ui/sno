<!-- Customer Active Cart -->
@if(isset($customerCart) && $customerCart->count() > 0)
<div class="card mb-3">
    <div class="card-header">
        <h4 class="card-title d-flex align-items-center gap-2">
            <span class="card-header-icon">
                <i class="tio-shopping-cart"></i>
            </span>
            <span>{{ translate('Active Cart') }}</span>
            <span class="badge badge-soft-primary">{{ $customerCart->count() }} items</span>
        </h4>
    </div>
    
    <div class="card-body">
        <!-- Cart Summary -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <strong>{{ translate('Total Items:') }}</strong>
                    <span class="badge badge-soft-info">{{ $customerCart->sum('quantity') }}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <strong>{{ translate('Cart Total:') }}</strong>
                    <span class="text-primary h4 mb-0">
                        @php
                            $cartTotal = $customerCart->sum(function($item) {
                                return $item->price * $item->quantity;
                            });
                        @endphp
                        {{\App\CentralLogics\Helpers::format_currency($cartTotal)}}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Cart Items List -->
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('#') }}</th>
                        <th>{{ translate('Item') }}</th>
                        <th class="text-center">{{ translate('Quantity') }}</th>
                        <th class="text-right">{{ translate('Unit Price') }}</th>
                        <th class="text-right">{{ translate('Total') }}</th>
                        <th class="text-center">{{ translate('Added') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customerCart as $index => $cartItem)
                        @php
                            $item = $cartItems->get($cartItem->item_id);
                            $itemTotal = $cartItem->price * $cartItem->quantity;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if($item)
                                    <div class="d-flex align-items-center gap-2">
                                        @if($item->image)
                                            <img class="avatar avatar-sm rounded"
                                                 onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                                 src="{{ $item->image_full_url }}"
                                                 alt="{{ $item->name }}">
                                        @endif
                                        <div>
                                            <strong>{{ $item->name }}</strong>
                                            @if($cartItem->variation && $cartItem->variation != '[]')
                                                <div class="small text-muted">
                                                    @php
                                                        $variations = json_decode($cartItem->variation, true);
                                                    @endphp
                                                    @if(is_array($variations) && count($variations) > 0)
                                                        {{ $variations[0]['type'] ?? '' }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">{{ translate('Item not found') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-primary">{{ $cartItem->quantity }}</span>
                            </td>
                            <td class="text-right">
                                {{\App\CentralLogics\Helpers::format_currency($cartItem->price)}}
                            </td>
                            <td class="text-right">
                                <strong class="text-primary">
                                    {{\App\CentralLogics\Helpers::format_currency($itemTotal)}}
                                </strong>
                            </td>
                            <td class="text-center">
                                <small class="text-muted">
                                    {{\Carbon\Carbon::parse($cartItem->created_at)->diffForHumans()}}
                                </small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <td colspan="4" class="text-right"><strong>{{ translate('Grand Total:') }}</strong></td>
                        <td class="text-right">
                            <h4 class="mb-0 text-success">
                                {{\App\CentralLogics\Helpers::format_currency($cartTotal)}}
                            </h4>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Cart Age Info -->
        <div class="mt-3 p-3 bg-light rounded">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="tio-time text-muted"></i>
                    <span class="text-muted">{{ translate('Last Updated:') }}</span>
                    <strong>{{\Carbon\Carbon::parse($customerCart->first()->updated_at)->format('d M Y, h:i A')}}</strong>
                </div>
                <div>
                    @php
                        $cartAge = \Carbon\Carbon::parse($customerCart->first()->created_at)->diffInMinutes(now());
                    @endphp
                    @if($cartAge < 30)
                        <span class="badge badge-danger">
                            <i class="tio-bolt"></i> HOT Cart ({{ $cartAge }}m ago)
                        </span>
                    @elseif($cartAge < 1440)
                        <span class="badge badge-warning">
                            Active Cart ({{ \Carbon\Carbon::parse($customerCart->first()->created_at)->diffForHumans() }})
                        </span>
                    @else
                        <span class="badge badge-secondary">
                            Stale Cart ({{ \Carbon\Carbon::parse($customerCart->first()->created_at)->diffForHumans() }})
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="card mb-3">
    <div class="card-body text-center py-5">
        <img src="{{asset('public/assets/admin/svg/illustrations/sorry.svg')}}" alt="Empty cart" style="width: 80px; opacity: 0.5;">
        <h5 class="mt-3 text-muted">{{ translate('No Active Cart') }}</h5>
        <p class="text-muted">{{ translate('Customer has no items in cart at the moment') }}</p>
    </div>
</div>
@endif

