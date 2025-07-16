<table>
    <thead>
        <tr>
            <th colspan="7">
                Approved Leaves Timeline<br>
                Period: {{ \Carbon\Carbon::parse($startDate)->toFormattedDateString() }} to {{ \Carbon\Carbon::parse($endDate)->toFormattedDateString() }}
            </th>
        </tr>
        <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Leave Type</th>
            <th>Reason</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Total Days</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($leaveRequests as $leave)
            <tr>
                <td>{{ $leave->employee->user->name ?? 'N/A' }}</td>
                <td>{{ $leave->employee->department->name ?? 'N/A' }}</td>
                <td>{{ $leave->type ?? 'N/A' }}</td>
                <td>{{ $leave->reason }}</td>
                <td>{{ \Carbon\Carbon::parse($leave->start_date)->toDateString() }}</td>
                <td>{{ \Carbon\Carbon::parse($leave->end_date)->toDateString() }}</td>
                <td>{{ \Carbon\Carbon::parse($leave->start_date)->diffInDays(\Carbon\Carbon::parse($leave->end_date)) + 1 }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
