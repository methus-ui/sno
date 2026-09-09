<div class="row">
    <div class="col-lg-12 text-center">
        <h1>{{ translate('messages.day_close_report') }}</h1>
    </div>

    <div class="col-lg-12">
        <table>
            <thead>
                <tr>
                    <th>{{ translate('messages.delivery_man_info') }}</th>
                    <th></th>
                    <th>
                        {{ translate('messages.name') }} - {{ $data['dm']->f_name.' '.$data['dm']->l_name }}
                        <br>
                        {{ translate('messages.phone') }} - {{ $data['dm']->phone }}
                        <br>
                        {{ translate('messages.email') }} - {{ $data['dm']->email }}
                        <br>
                        {{ translate('messages.delivered_orders') }} - {{ $data['dateOrderCount'] }}
                        <br>
                        {{ translate('messages.cash_collected') }} - {{ $data['dateCashCollected'] }}
                        <br>
                        {{ translate('messages.total_cash_in_hand') }} - {{ $data['cashInHand'] }}
                    </th>
                    <th></th><th></th><th></th><th></th>
                </tr>

                <tr>
                    <th>{{ translate('messages.day_close_summary') }}</th>
                    <th></th>
                    <th>
                        {{ translate('messages.date') }} - {{ \Carbon\Carbon::parse($data['selectedDate'])->format('d M Y') }}
                        <br>
                        {{ translate('messages.day_status') }} - {{ $data['isDayClosed'] ? translate('messages.day_closed') : translate('messages.day_not_closed') }}
                        <br>
                        {{ translate('messages.delivery_earnings') }} - {{ $data['dateEarnings'] }}
                        <br>
                        {{ translate('messages.tips') }} - {{ $data['dateTips'] }}
                        <br>
                        {{ translate('messages.outside_purchase') }} - {{ $data['dateOutsidePurchase'] }}
                        <br>
                        {{ translate('messages.cash_in_hand') }} - {{ $data['dateCashInHand'] }}
                        <br>
                        {{ translate('messages.avg_delivery_time') }} - {{ $data['avgDeliveryTime'] ? round($data['avgDeliveryTime']) . ' min' : 'N/A' }}
                        <br>
                        {{ translate('messages.total_wallet_earning') }} - {{ $data['totalEarning'] }}
                    </th>
                    <th></th><th></th><th></th><th></th>
                </tr>

                <tr>
                    <th>{{ translate('messages.export_info') }}</th>
                    <th></th>
                    <th>
                        {{ translate('messages.export_date') }} - {{ now()->format('d M Y, h:i A') }}
                    </th>
                    <th></th><th></th><th></th><th></th>
                </tr>

                <tr>
                    <th>{{ translate('messages.sl') }}</th>
                    <th>{{ translate('messages.order_id') }}</th>
                    <th>{{ translate('messages.customer') }}</th>
                    <th>{{ translate('messages.order_amount') }}</th>
                    <th>{{ translate('messages.outside_purchase') }}</th>
                    <th>{{ translate('messages.payment_method') }}</th>
                    <th>{{ translate('messages.delivered_at') }}</th>
                </tr>
            </thead>

            <tbody>
                @foreach($data['dateOrders'] as $key => $order)
                    <tr>
                        <td>{{ $key+1 }}</td>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->customer ? $order->customer->f_name.' '.$order->customer->l_name : translate('messages.N/A') }}</td>
                        <td>{{ $order->order_amount }}</td>
                        <td>{{ $order->outside_purchase_amount ?? 0 }}</td>
                        <td>{{ translate('messages.'.$order->payment_method) }}</td>
                        <td>{{ $order->delivered ? \Carbon\Carbon::parse($order->delivered)->format('d M Y, h:i A') : translate('messages.N/A') }}</td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="7"></td>
                </tr>
                <tr>
                    <th colspan="7">{{ translate('messages.financial_summary') }}</th>
                </tr>
                <tr>
                    <td colspan="3">{{ translate('messages.total_cash_collected') }}</td>
                    <td colspan="4">{{ $data['dateCashCollected'] }}</td>
                </tr>
                <tr>
                    <td colspan="3">{{ translate('messages.total_outside_purchase') }}</td>
                    <td colspan="4">{{ $data['dateOutsidePurchase'] }}</td>
                </tr>
                <tr>
                    <td colspan="3">{{ translate('messages.cash_in_hand_after_deductions') }}</td>
                    <td colspan="4">{{ $data['dateCashInHand'] }}</td>
                </tr>
                <tr>
                    <td colspan="3">{{ translate('messages.total_amount_to_collect_from_dm') }}</td>
                    <td colspan="4">{{ $data['dateCashInHand'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
