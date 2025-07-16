<table>
    <tr>
        <td colspan="8" style="font-weight: bold; font-size: 16px;">Offset Request Summary</td>
    </tr>
    <tr>
        <td colspan="8">
            Period Covered:
            {{ \Carbon\Carbon::parse($startDate)->format('F d, Y') }}
            to
            {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}
        </td>
    </tr>
    <tr><td colspan="8"></td></tr> {{-- Empty row for spacing --}}

    <thead>
        <tr>
            <th>Date</th>
            <th>Time Start</th>
            <th>Time End</th>
            <th>Hours</th>
            <th>Reason</th>
            <th>Project/Event</th>
            <th>Status</th>
            <th>Approver</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($offsetRequests as $req)
            <tr>
                <td>{{ \Carbon\Carbon::parse($req->date)->format('Y-m-d') }}</td>
                <td>{{ \Carbon\Carbon::parse($req->time_start)->format('H:i') }}</td>
                <td>{{ \Carbon\Carbon::parse($req->time_end)->format('H:i') }}</td>
                <td>{{ $req->number_of_hours }}</td>
                <td>{{ $req->reason ?? '-' }}</td>
                <td>{{ $req->project_or_event_description }}</td>
                <td>{{ ucfirst($req->status) }}</td>
                <td>{{ $req->approver?->name ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
