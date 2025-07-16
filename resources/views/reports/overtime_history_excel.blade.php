<table>
    <tr>
        <td colspan="8" style="font-weight: bold; font-size: 16px;">{{ $companyName }}</td>
    </tr>
    <tr>
        <td colspan="8" style="font-weight: bold;">Filed Overtime Report</td>
    </tr>
    <tr>
        <td colspan="8">
            @if (!empty($filters['start_date']) && !empty($filters['end_date']))
                Period Covered: {{ \Carbon\Carbon::parse($filters['start_date'])->format('F d, Y') }} to {{ \Carbon\Carbon::parse($filters['end_date'])->format('F d, Y') }}
            @elseif (!empty($filters['start_date']))
                As of {{ \Carbon\Carbon::parse($filters['start_date'])->format('F d, Y') }}
            @elseif (!empty($filters['end_date']))
                As of {{ \Carbon\Carbon::parse($filters['end_date'])->format('F d, Y') }}
            @else
                As of {{ now()->format('F d, Y') }}
            @endif
        </td>
    </tr>
</table>

<br>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Time Start</th>
            <th>Time End</th>
            <th>Hours</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Approval Date</th>
            <th>Expiration Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($overtimeRequests as $ot)
            <tr>
                <td>{{ \Carbon\Carbon::parse($ot->date)->toDateString() }}</td>
                <td>{{ \Carbon\Carbon::parse($ot->time_start)->format('H:i') }}</td>
                <td>{{ \Carbon\Carbon::parse($ot->time_end)->format('H:i') }}</td>
                <td>{{ number_format($ot->number_of_hours, 2) }}</td>
                <td>{{ $ot->reason }}</td>
                <td>{{ ucfirst($ot->status) }}</td>
                <td>{{ $ot->approval_date ? \Carbon\Carbon::parse($ot->approval_date)->toDateString() : '—' }}</td>
                <td>{{ $ot->expires_at ? \Carbon\Carbon::parse($ot->expires_at)->toDateString() : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
