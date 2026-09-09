@forelse($deliverymen as $dm)
    @include('admin-views.order.partials._dm-list-item', ['dm' => $dm, 'isInZone' => $isInZone])
@empty
    <li class="list-group-item text-center py-4">
        <img src="{{ asset('public/assets/admin/img/empty-box.png') }}" alt="" style="width: 60px;">
        <p class="text-muted mt-2 mb-0">
            @if($isInZone)
                {{ translate('messages.no_deliveryman_in_zone') }}
            @else
                {{ translate('messages.no_other_deliveryman') }}
            @endif
        </p>
    </li>
@endforelse
