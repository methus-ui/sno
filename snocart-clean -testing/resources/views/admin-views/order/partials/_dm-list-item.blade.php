<li class="list-group-item dm-list-item p-3 mb-2 border rounded {{ ($dm['has_same_route_order'] ?? false) ? 'border-success' : '' }}" style="{{ ($dm['has_same_route_order'] ?? false) ? 'background: #f0fff4;' : '' }}">
    <div class="d-flex align-items-start">
        <span class="dm_list flex-grow-1" role='button' data-id="{{ $dm['id'] }}">
            <div class="d-flex">
                <!-- Profile Image -->
                <div class="mr-3">
                    <img class="avatar avatar-lg avatar-circle onerror-image"
                         data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                         src="{{ $dm['image_full_url'] ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"
                         alt="{{ $dm['name'] }}"
                         style="width: 60px; height: 60px; object-fit: cover;">
                </div>

                <!-- Info -->
                <div class="flex-grow-1">
                    <h5 class="mb-1 dm-name dm-phone" data-phone="{{ $dm['phone'] ?? '' }}">
                        {{ $dm['name'] }}
                        @if($dm['has_same_route_order'] ?? false)
                            <span class="badge badge-success ml-1" style="font-size: 10px; vertical-align: middle;">
                                <i class="tio-route"></i> {{ translate('messages.same_route') }}
                            </span>
                        @endif
                    </h5>

                    @if($dm['phone'])
                    <small class="text-muted d-block">
                        <i class="tio-call-talking mr-1"></i>{{ $dm['phone'] }}
                    </small>
                    @endif

                    @if($dm['vehicle_type'])
                    <small class="text-muted d-block">
                        <i class="tio-car mr-1"></i>{{ $dm['vehicle_type'] }}
                    </small>
                    @endif

                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <!-- Distance Badge (Prominent) -->
                        <span class="badge badge-{{ $dm['distance_color'] ?? 'secondary' }} px-2 py-1" title="{{ translate('messages.distance_from_store') }}">
                            <i class="tio-location-outlined"></i>
                            {{ $dm['distance_text'] ?? translate('messages.unknown') }}
                        </span>

                        <!-- Rating -->
                        <span class="badge badge-soft-warning px-2 py-1" title="{{ translate('messages.rating') }}">
                            <i class="tio-star"></i>
                            {{ $dm['avg_rating'] > 0 ? $dm['avg_rating'] : '-' }}
                            @if($dm['rating_count'] > 0)
                                <small>({{ $dm['rating_count'] }})</small>
                            @endif
                        </span>

                        <!-- Current Orders -->
                        <span class="badge badge-soft-info px-2 py-1" title="{{ translate('messages.current_orders') }}">
                            <i class="tio-shopping-basket"></i>
                            {{ $dm['current_orders'] }} {{ translate('messages.active') }}
                        </span>

                        <!-- Total Delivered -->
                        <span class="badge badge-soft-success px-2 py-1" title="{{ translate('messages.total_delivered') }}">
                            <i class="tio-checkmark-circle"></i>
                            {{ $dm['total_delivered_orders'] }} {{ translate('messages.delivered') }}
                        </span>

                        <!-- Cash In Hand -->
                        @if($dm['cash_in_hand'] > 0)
                        <span class="badge badge-soft-danger px-2 py-1" title="{{ translate('messages.cash_in_hand') }}">
                            <i class="tio-money"></i>
                            {{ \App\CentralLogics\Helpers::format_currency($dm['cash_in_hand']) }}
                        </span>
                        @endif
                    </div>

                    <!-- Active orders on this DM -->
                    @if(!empty($dm['active_orders']))
                    <div class="mt-2 p-2 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                        <small class="text-dark font-weight-bold d-block mb-1">
                            <i class="tio-shopping-basket mr-1"></i>{{ translate('messages.existing_orders') }} ({{ count($dm['active_orders']) }}):
                        </small>
                        @foreach($dm['active_orders'] as $activeOrder)
                        <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 11px;">
                            <span>
                                <a href="{{ route('admin.order.details', $activeOrder['id']) }}" target="_blank" class="text-primary">#{{ $activeOrder['id'] }}</a>
                                <span class="text-muted">- {{ $activeOrder['store_name'] }}</span>
                                <span class="badge badge-soft-info badge-sm ml-1">{{ ucfirst(str_replace('_', ' ', $activeOrder['status'])) }}</span>
                            </span>
                            @if($activeOrder['same_route'])
                            <span class="badge badge-success badge-sm px-2">
                                <i class="tio-route mr-1"></i>{{ translate('messages.same_route') }}
                            </span>
                            @endif
                        </div>
                        @endforeach
                        @if($dm['has_same_route_order'] ?? false)
                        <div class="mt-1 text-success" style="font-size: 11px;">
                            <i class="tio-checkmark-circle-outlined mr-1"></i>
                            <strong>{{ translate('messages.has_order_on_same_route') }}</strong>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Zone name for out-of-zone deliverymen -->
                    @if(!$isInZone && !empty($dm['zone_name']))
                    <small class="text-info d-block mt-1">
                        <i class="tio-globe mr-1"></i>{{ translate('messages.zone') }}: {{ $dm['zone_name'] }}
                    </small>
                    @endif

                    @if($dm['location'])
                    <small class="text-muted d-block mt-1">
                        <i class="tio-poi mr-1"></i>{{ Str::limit($dm['location'], 40) }}
                    </small>
                    @endif
                </div>
            </div>
        </span>
        <div class="ml-2">
            <a class="btn btn-primary btn-sm add-delivery-man"
               data-id="{{ $dm['id'] }}"
               data-lat="{{ $dm['lat'] ?? '' }}"
               data-lng="{{ $dm['lng'] ?? '' }}">
                {{ translate('messages.assign') }}
            </a>
        </div>
    </div>
</li>
