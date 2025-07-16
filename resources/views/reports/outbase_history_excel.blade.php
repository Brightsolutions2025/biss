<table>
    <thead>
        <tr>
            <th colspan="8" style="font-size: 14px;">
                Outbase Request Report
            </th>
        </tr>
        <tr>
            <th colspan="8" style="font-size: 12px;">
                Period: {{ \Carbon\Carbon::parse($startDate)->toFormattedDateString() }} 
                to {{ \Carbon\Carbon::parse($endDate)->toFormattedDateString() }}
            </th>
        </tr>
        <tr>
            <th>Date</th>
            <th>Department</th>
            <th>Time Start</th>
            <th>Time End</th>
            <th>Location</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Approver</th>
        </tr>
    </thead>
    <tbody>
        @foreach($outbaseRequests as $req)
            <tr>
                <td>{{ \Carbon\Carbon::parse($req->date)->toDateString() }}</td>
                <td>{{ $req->employee->department->name ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($req->time_start)->format('H:i') }}</td>
                <td>{{ \Carbon\Carbon::parse($req->time_end)->format('H:i') }}</td>
                <td>{{ $req->location }}</td>
                <td>{{ $req->reason }}</td>
                <td>{{ ucfirst($req->status) }}</td>
                <td>{{ $req->approver?->name ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
