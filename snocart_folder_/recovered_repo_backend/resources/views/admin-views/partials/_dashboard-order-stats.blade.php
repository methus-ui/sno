{{-- Summary Cards --}}
<div class="dash-stat-summary-grid">
    <div class="dash-stat-summary">
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/items.svg')}}" alt="">
        <div class="stat-name">{{ translate('messages.items') }}</div>
        <div class="stat-count">{{ $data['total_items'] }}</div>
        <div class="stat-new">{{ $data['new_items'] }} {{ translate('newly added') }}</div>
    </div>
    <div class="dash-stat-summary">
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/orders.svg')}}" alt="">
        <div class="stat-name">{{ translate('messages.orders') }}</div>
        <div class="stat-count">{{ $data['total_orders'] }}</div>
        <div class="stat-new">{{ $data['new_orders'] }} {{ translate('newly added') }}</div>
    </div>
    <div class="dash-stat-summary">
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/stores.svg')}}" alt="">
        <div class="stat-name">{{ translate('Grocery Stores') }}</div>
        <div class="stat-count">{{ $data['total_stores'] }}</div>
        <div class="stat-new">{{ $data['new_stores'] }} {{ translate('newly added') }}</div>
    </div>
    <div class="dash-stat-summary">
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/customers.svg')}}" alt="">
        <div class="stat-name">{{ translate('messages.customers') }}</div>
        <div class="stat-count">{{ $data['total_customers'] }}</div>
        <div class="stat-new">{{ $data['new_customers'] }} {{ translate('newly added') }}</div>
    </div>
    <div class="dash-stat-summary">
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/items.svg')}}" alt="">
        <div class="stat-name">{{ translate('Items Uploaded') }}</div>
        <div class="stat-count">{{ $data['uploaded_items'] ?? 0 }}</div>
    </div>
    <div class="dash-stat-summary">
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/items.svg')}}" alt="">
        <div class="stat-name">{{ translate('Items Edited') }}</div>
        <div class="stat-count">{{ $data['edited_items'] ?? 0 }}</div>
    </div>
</div>

{{-- Order Status Grid --}}
<div class="dash-order-status-grid">
    <a class="dash-order-status" href="{{route('admin.order.list',['searching_for_deliverymen'])}}">
        <div class="os-left">
            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/unassigned.svg')}}" alt="">
            <span>{{translate('messages.unassigned_orders')}}</span>
        </div>
        <span class="os-count blue">{{$data['searching_for_dm']}}</span>
    </a>
    <a class="dash-order-status" href="{{route('admin.order.list',['accepted'])}}">
        <div class="os-left">
            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/accepted.svg')}}" alt="">
            <span>{{translate('Accepted by DM')}}</span>
        </div>
        <span class="os-count green">{{$data['accepted_by_dm']}}</span>
    </a>
    <a class="dash-order-status" href="{{route('admin.order.list',['processing'])}}">
        <div class="os-left">
            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/packaging.svg')}}" alt="">
            <span>{{translate('Packaging')}}</span>
        </div>
        <span class="os-count amber">{{$data['preparing_in_rs']}}</span>
    </a>
    <a class="dash-order-status" href="{{route('admin.order.list',['item_on_the_way'])}}">
        <div class="os-left">
            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/out-for.svg')}}" alt="">
            <span>{{translate('Out for Delivery')}}</span>
        </div>
        <span class="os-count green">{{$data['picked_up']}}</span>
    </a>
    <a class="dash-order-status" href="{{route('admin.order.list',['delivered'])}}">
        <div class="os-left">
            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/delivered.svg')}}" alt="">
            <span>{{translate('messages.delivered')}}</span>
        </div>
        <span class="os-count green">{{$data['delivered']}}</span>
    </a>
    <a class="dash-order-status" href="{{route('admin.order.list',['canceled'])}}">
        <div class="os-left">
            <img src="{{asset('/public/assets/admin/img/order-status/canceled.svg')}}" alt="">
            <span>{{translate('messages.canceled')}}</span>
        </div>
        <span class="os-count red">{{$data['canceled']}}</span>
    </a>
    <a class="dash-order-status" href="{{route('admin.order.list',['refunded'])}}">
        <div class="os-left">
            <img src="{{asset('/public/assets/admin/img/order-status/refunded.svg')}}" alt="">
            <span>{{translate('messages.refunded')}}</span>
        </div>
        <span class="os-count red">{{$data['refunded']}}</span>
    </a>
    <a class="dash-order-status" href="{{route('admin.order.list',['failed'])}}">
        <div class="os-left">
            <img src="{{asset('/public/assets/admin/img/order-status/payment-failed.svg')}}" alt="">
            <span>{{translate('messages.payment_failed')}}</span>
        </div>
        <span class="os-count red">{{$data['refund_requested']}}</span>
    </a>
</div>
