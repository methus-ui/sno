<div class="row">
    <div class="col-lg-12 text-center">
        <h1>{{ translate('messages.deliveryman_attendance_report') }}</h1>
    </div>
    <div class="col-lg-12">
        <table>
            <thead>
                <tr>
                    <th>{{ translate('messages.delivery_man_info') }}</th>
                    <th></th>
                    <th>
                        @if($data['delivery_man'])
                            {{ translate('messages.name') }}- {{ $data['delivery_man']->f_name.' '.$data['delivery_man']->l_name}}
                            <br>
                            {{ translate('messages.phone') }}- {{ $data['delivery_man']->phone}}
                            <br>
                            {{ translate('messages.email') }}- {{ $data['delivery_man']->email}}
                            <br>
                            {{ translate('messages.employee_id') }}- {{ $data['delivery_man']->id }}
                        @else
                            {{ translate('messages.all_delivery_men') }}
                            <br>
                            {{ translate('messages.total_records') }}- {{ $data['attendances']->count() }}
                        @endif
                    </th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
                <tr>
                    <th>{{ translate('messages.filter_criteria') }}</th>
                    <th></th>
                    <th>
                        {{ translate('messages.report_type') }}- {{ ucfirst($data['report_type']) }}
                        <br>
                        {{ translate('messages.date_range') }}- {{ $data['start_date'] }} {{ translate('messages.to') }} {{ $data['end_date'] }}
                        <br>
                        {{ translate('messages.generated_on') }}- {{ now()->format('d M Y, H:i A') }}
                    </th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
                <tr>
                    <th>{{ translate('messages.sl') }}</th>
                    <th>{{ translate('messages.delivery_man') }}</th>
                    <th>{{ translate('messages.date') }}</th>
                    <th>{{ translate('messages.punch_in') }}</th>
                    <th>{{ translate('messages.punch_out') }}</th>
                    <th>{{ translate('messages.working_hours') }}</th>
                    <th>{{ translate('messages.status') }}</th>
                    <th>{{ translate('messages.phone') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($data['attendances'] as $key => $attendance)
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>{{ $attendance->deliveryMan->f_name.' '.$attendance->deliveryMan->l_name }}</td>
                    <td>{{ $attendance->date->format('d M Y') }}</td>
                    <td>
                        @if($attendance->punch_in_time)
                            {{ \Carbon\Carbon::parse($attendance->punch_in_time)->format('H:i A') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($attendance->punch_out_time)
                            {{ \Carbon\Carbon::parse($attendance->punch_out_time)->format('H:i A') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $attendance->formatted_working_hours }}</td>
                    <td>{{ ucfirst($attendance->status) }}</td>
                    <td>{{ $attendance->deliveryMan->phone }}</td>
                </tr>
            @endforeach
            
            @if($data['attendances']->count() > 0)
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="5">{{ translate('messages.summary') }}</td>
                    <td>{{ number_format($data['attendances']->sum('working_hours'), 2) }} {{ translate('messages.hours') }}</td>
                    <td>
                        P: {{ $data['attendances']->where('status', 'present')->count() }}, 
                        Pa: {{ $data['attendances']->where('status', 'partial')->count() }}, 
                        A: {{ $data['attendances']->where('status', 'absent')->count() }}
                    </td>
                    <td></td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>