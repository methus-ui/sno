<div class="table-responsive">
    <table class="table table-hover table-borderless">
        <thead class="thead-light">
            <tr>
                <th>{{ translate('messages.item') }}</th>
                <th class="text-center">{{ translate('messages.quantity') }}</th>
                <th class="text-right">{{ translate('messages.price') }}</th>
                <th class="text-right">{{ translate('messages.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotal = 0;
                $totalItems = 0;
            @endphp
            
            @forelse($carts as $cart)
                @php
                    $itemTotal = $cart->price * $cart->quantity;
                    $grandTotal += $itemTotal;
                    $totalItems += $cart->quantity;
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <img class="avatar avatar-sm mr-2 rounded"
                                 src="{{ $cart->item?->image_full_url ?? asset('public/assets/admin/img/100x100/2.png') }}"
                                 onerror="this.src='{{ asset('public/assets/admin/img/100x100/2.png') }}'"
                                 alt="Item Image">
                            <div>
                                <h6 class="mb-0">{{ $cart->item->name ?? translate('messages.item_not_found') }}</h6>
                                @if($cart->variation && count(json_decode($cart->variation, true)) > 0)
                                    <small class="text-muted">
                                        @foreach(json_decode($cart->variation, true) as $key => $value)
                                            {{ $key }}: {{ $value }}
                                        @endforeach
                                    </small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-soft-primary badge-pill">{{ $cart->quantity }}</span>
                    </td>
                    <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($cart->price) }}</td>
                    <td class="text-right">
                        <strong>{{ \App\CentralLogics\Helpers::format_currency($itemTotal) }}</strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}"
                             alt="No items"
                             class="w-100px mb-3">
                        <p class="text-muted">{{ translate('messages.no_items_in_cart') }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
        
        @if($carts->count() > 0)
        <tfoot class="border-top">
            <tr>
                <td colspan="2">
                    <strong>{{ translate('messages.total_items') }}:</strong>
                    <span class="badge badge-primary ml-2">{{ $totalItems }}</span>
                </td>
                <td class="text-right">
                    <strong>{{ translate('messages.grand_total') }}:</strong>
                </td>
                <td class="text-right">
                    <h4 class="text-primary mb-0">
                        {{ \App\CentralLogics\Helpers::format_currency($grandTotal) }}
                    </h4>
                </td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<!-- Customer Info -->
<div class="card bg-light mt-3">
    <div class="card-body">
        <h6 class="mb-3">{{ translate('messages.customer_information') }}</h6>
        <div class="row">
            <div class="col-6">
                <p class="mb-1">
                    <strong>{{ translate('messages.name') }}:</strong><br>
                    {{ $user->f_name }} {{ $user->l_name }}
                </p>
            </div>
            <div class="col-6">
                <p class="mb-1">
                    <strong>{{ translate('messages.phone') }}:</strong><br>
                    <a href="tel:{{ $user->phone }}">{{ $user->phone ?? 'N/A' }}</a>
                </p>
            </div>
            <div class="col-12 mt-2">
                <p class="mb-0">
                    <strong>{{ translate('messages.email') }}:</strong><br>
                    <a href="mailto:{{ $user->email }}">{{ $user->email ?? 'N/A' }}</a>
                </p>
            </div>
        </div>
    </div>
</div>
