<div class="row">
    <div class="col-lg-12 text-center">
        <h1>{{ translate('delivery_man_earning_list') }}</h1>
    </div>

    <div class="col-lg-12">
        <table>
            <thead>
                <tr>
                    <th>{{ translate('delivery_man_info') }}</th>
                    <th></th>
                    <th>
                        {{ translate('name') }} - {{ $data['dm']->f_name.' '.$data['dm']->l_name }}
                        <br>
                        {{ translate('phone') }} - {{ $data['dm']->phone }}
                        <br>
                        {{ translate('email') }} - {{ $data['dm']->email }}
                        <br>
                        {{ translate('total_order') }} - {{ $data['dm']->order_count }}
                        <br>
                        {{ translate('total_earning') }} - {{ $data['dm']->wallet->total_earning }}
                    </th>
                    <th></th><th></th><th></th><th></th><th></th><th></th><th></th>
                </tr>

                <tr>
                    <th>{{ translate('Filter_Criteria') }}</th>
                    <th></th>
                    <th>
                        {{ translate('selected_date_range') }} - {{ $data['date'] ?? translate('N/A') }}
                        <br>
                        {{ translate('export_date') }} - {{ now()->format('d M Y, h:i A') }}
                    </th>
                    <th></th><th></th><th></th><th></th><th></th><th></th><th></th>
                </tr>

                <tr>
                    <th>{{ translate('sl') }}</th>
                    <th>{{ translate('messages.order_id') }}</th>
                    <th>{{ translate('messages.order_date') }}</th>
                    <th>{{ translate('messages.payment_method') }}</th>
                    <th>{{ translate('messages.order_amount') }}</th>
                    <th>{{ translate('messages.distance') }}</th>
                    <th>{{ translate('messages.actual_delivery_charge') }}</th>
                    <th>{{ translate('messages.convenience_fee') }}</th>
                    <th>{{ translate('messages.delivery_fee_earned') }}</th>
                    <th>{{ translate('messages.tips') }}</th>
                    <th>{{ translate('messages.total_earning') }}</th>
                </tr>
            </thead>

            <tbody>
                @foreach($data['earnings'] as $key => $earning)
                    <tr>
                        <td>{{ $key+1 }}</td>
                        <td>{{ $earning->order_id }}</td>
                        <td>{{ $earning->order->created_at ? \Carbon\Carbon::parse($earning->order->created_at)->format('d M Y, h:i A') : 'N/A' }}</td>
                        <td>{{ $earning->order->payment_method ?? 'N/A' }}</td>
                        <td>{{ $earning->order->order_amount ?? 0 }}</td>
                        <td>{{ $earning->order->distance }} km</td>
                        <td>{{ $earning->delivery_charge ?? 0 }}</td>
                        <td>{{ $earning->additional_charge ?? 0 }}</td>
                        <td>{{ $earning->original_delivery_charge }}</td>
                        <td>{{ $earning->dm_tips }}</td>
                        <td>{{ $earning->original_delivery_charge + $earning->dm_tips }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
