<table>
    <thead>
        <tr>
            <th>{{ translate('messages.SL') }}</th>
            <th>{{ translate('messages.delivery_man') }}</th>
            <th>{{ translate('messages.phone') }}</th>
            <th>{{ translate('messages.period_from') }}</th>
            <th>{{ translate('messages.period_to') }}</th>
            <th>{{ translate('messages.total_deliveries') }}</th>
            <th>{{ translate('messages.avg_delivery_time') }}</th>
            <th>{{ translate('messages.salary') }}</th>
            <th>{{ translate('messages.incentive') }}</th>
            <th>{{ translate('messages.total') }}</th>
            <th>{{ translate('messages.deductions') }}</th>
            <th>{{ translate('messages.net_payable') }}</th>
            <th>{{ translate('messages.status') }}</th>
            <th>{{ translate('messages.paid_method') }}</th>
            <th>{{ translate('messages.paid_on') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payrolls as $k => $payroll)
            <tr>
                <td>{{ $k + 1 }}</td>
                <td>{{ $payroll->deliveryMan ? $payroll->deliveryMan->f_name . ' ' . $payroll->deliveryMan->l_name : 'N/A' }}</td>
                <td>{{ $payroll->deliveryMan->phone ?? '' }}</td>
                <td>{{ $payroll->period_from->format('d M Y') }}</td>
                <td>{{ $payroll->period_to->format('d M Y') }}</td>
                <td>{{ $payroll->total_deliveries }}</td>
                <td>{{ $payroll->avg_delivery_time ? round($payroll->avg_delivery_time) . ' min' : 'N/A' }}</td>
                <td>{{ $payroll->salary_amount }}</td>
                <td>{{ $payroll->incentive_amount }}</td>
                <td>{{ $payroll->total_amount }}</td>
                <td>{{ $payroll->deductions }}</td>
                <td>{{ $payroll->net_payable }}</td>
                <td>{{ $payroll->status }}</td>
                <td>{{ $payroll->paid_method ?? '' }}</td>
                <td>{{ $payroll->paid_at ? $payroll->paid_at->format('d M Y') : '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
