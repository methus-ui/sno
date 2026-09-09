<table>
    <thead>
        <tr>
            <th colspan="9" style="text-align: center; font-weight: bold; font-size: 16px;">
                ATTENDANCE REPORT ({{ strtoupper($data['report_type']) }})
            </th>
        </tr>
        <tr>
            <th colspan="2" style="font-weight: bold;">Report Period:</th>
            <th colspan="7">{{ $data['start_date'] }} to {{ $data['end_date'] }}</th>
        </tr>
        <tr>
            <th colspan="2" style="font-weight: bold;">
                {{ $data['employee'] ? 'Employee:' : 'All Employees' }}
            </th>
            <th colspan="7">
                {{ $data['employee'] ? $data['employee']->f_name . ' ' . $data['employee']->l_name : 'All Employees' }}
            </th>
        </tr>
        <tr style="background-color: #005D5F; color: white; font-weight: bold;">
            <th>SL</th>
            <th>Employee Name</th>
            <th>Employee ID</th>
            <th>Date</th>
            <th>Punch In</th>
            <th>Punch Out</th>
            <th>Total Hours</th>
            <th>Status</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data['attendances'] as $key => $attendance)
        <tr>
            <td>{{ $key + 1 }}</td>
            <td>{{ $attendance->admin->f_name ?? '' }} {{ $attendance->admin->l_name ?? '' }}</td>
            <td>{{ $attendance->admin->id ?? '' }}</td>
            <td>{{ $attendance->attendance_date->format('Y-m-d') }}</td>
            <td>{{ $attendance->punch_in ? $attendance->punch_in->format('H:i:s') : '-' }}</td>
            <td>{{ $attendance->punch_out ? $attendance->punch_out->format('H:i:s') : '-' }}</td>
            <td>{{ $attendance->total_hours ?? '0.00' }}</td>
            <td>{{ ucfirst($attendance->status) }}</td>
            <td>{{ $attendance->notes ?? '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align: center;">No attendance records found</td>
        </tr>
        @endforelse
    </tbody>
</table>