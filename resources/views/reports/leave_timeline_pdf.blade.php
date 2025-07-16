<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Approved Leaves Timeline - Calendar</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .calendar {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar caption {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
            text-align: center;
        }

        .calendar th, .calendar td {
            border: 1px solid #999;
            width: 14.28%;
            vertical-align: top;
            height: 90px;
            padding: 4px;
        }

        .calendar th {
            background: #f0f0f0;
            text-align: center;
        }

        .day-number {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .leave-entry {
            font-size: 10px;
            background-color: #d1e7dd;
            border-left: 3px solid #198754;
            padding-left: 4px;
            margin-top: 2px;
        }

        .no-leave {
            color: #bbb;
            font-size: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h2>Approved Leaves Timeline</h2>
    <div class="subtitle">
        Period: {{ \Carbon\Carbon::parse($startDate)->toFormattedDateString() }}
        to {{ \Carbon\Carbon::parse($endDate)->toFormattedDateString() }}
    </div>

    <table class="calendar">
        @php
            $startOfMonth = \Carbon\Carbon::parse($startDate)->startOfMonth();
            $endOfMonth = \Carbon\Carbon::parse($startDate)->endOfMonth();
            $currentDay = $startOfMonth->copy()->startOfWeek();
        @endphp

        <thead>
            <tr>
                <th>Sun</th>
                <th>Mon</th>
                <th>Tue</th>
                <th>Wed</th>
                <th>Thu</th>
                <th>Fri</th>
                <th>Sat</th>
            </tr>
        </thead>

        <tbody>
        @while ($currentDay <= $endOfMonth)
            <tr>
                @for ($i = 0; $i < 7; $i++)
                    <td>
                        <div class="day-number">{{ $currentDay->format('j') }}</div>

                        @php
                            $entries = $leaveRequests->filter(function ($leave) use ($currentDay) {
                                $start = \Carbon\Carbon::parse($leave->start_date);
                                $end = \Carbon\Carbon::parse($leave->end_date);
                                return $currentDay->between($start, $end);
                            });
                        @endphp

                        @if ($entries->isEmpty())
                            <div class="no-leave">—</div>
                        @else
                            @foreach ($entries as $entry)
                                <div class="leave-entry">
                                    {{ $entry->employee->user->name ?? 'N/A' }}
                                    <br>
                                    <small>{{ $entry->type ?? 'Leave' }}</small>
                                </div>
                            @endforeach
                        @endif

                        @php $currentDay->addDay(); @endphp
                    </td>
                @endfor
            </tr>
        @endwhile
        </tbody>
    </table>
</body>
</html>
