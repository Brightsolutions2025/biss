<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">{{ __('Offset Usage and Expiry Tracker') }}</h2>
    </x-slot>

    <div class="container py-4">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control" value="{{ request('from', $from) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control" value="{{ request('to', $to) }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Overtime Date</th>
                        <th>Approved Hours</th>
                        <th>Used Hours</th>
                        <th>Remaining</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offsetData as $row)
                        <tr class="{{ $row['expired'] && $row['remaining_hours'] > 0 ? 'table-danger' : '' }}">
                            <td>{{ $row['employee_name'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ number_format($row['approved_hours'], 2) }}</td>
                            <td>{{ number_format($row['used_hours'], 2) }}</td>
                            <td>{{ number_format($row['remaining_hours'], 2) }}</td>
                            <td>{{ $row['expires_at'] ?? 'N/A' }}</td>
                            <td>
                                @if ($row['expired'] && $row['remaining_hours'] > 0)
                                    <span class="badge bg-danger">Expired</span>
                                @elseif ($row['remaining_hours'] <= 0)
                                    <span class="badge bg-success">Fully Used</span>
                                @else
                                    <span class="badge bg-warning text-dark">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <!-- Download Buttons -->
            <div class="d-flex gap-2 mb-3">
                <a id="pdfLink"
                    href="{{ route('reports.offset_tracker.pdf', request()->only('from', 'to')) }}"
                    class="btn btn-outline-primary">
                    Download PDF
                </a>

                <a id="excelLink"
                    href="{{ route('reports.offset_tracker.excel', request()->only('from', 'to')) }}"
                    class="btn btn-outline-success">
                    Export Excel
                </a>
            </div>
            @if ($employeeModel)
                <h5 class="fw-bold mt-4">Detailed Overtime & Offset Requests: {{ $employeeModel->user->name }}</h5>

                <table class="table table-bordered table-sm table-hover align-middle mt-2">
                    <thead class="table-light">
                        <tr>
                            <th>OT Date</th>
                            <th class="text-end">OT Hours</th>
                            <th class="text-end text-danger">Expired?</th>
                            <th>Offset Date</th>
                            <th class="text-end">Offset Hours</th>
                            <th class="text-end text-success">Remaining Balance (Unexpired)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employeeModel->overtimeRequests as $overtime)
                            @php
                                $offsets = $overtime->offsetRequests;
                                $firstOffset = $offsets->first();
                                $remainingOffsets = $offsets->skip(1);
                                $isExpired = $overtime->expires_at && \Carbon\Carbon::parse($overtime->expires_at)->lt(now());
                                $offsetTotal = $offsets->sum('number_of_hours');
                                $balance = $isExpired ? 0 : ($overtime->number_of_hours - $offsetTotal);
                            @endphp

                            <tr>
                                <td>
                                    <a href="{{ route('overtime_requests.show', $overtime->id) }}" class="text-decoration-underline text-primary">
                                        {{ \Carbon\Carbon::parse($overtime->date)->format('Y-m-d') }}
                                    </a>
                                </td>
                                <td class="text-end">{{ number_format($overtime->number_of_hours, 2) }}</td>
                                <td class="text-end {{ $isExpired ? 'text-danger' : 'text-success' }}">
                                    {{ $isExpired ? 'Yes' : 'No' }}
                                </td>
                                <td>
                                    @if ($firstOffset)
                                        <a href="{{ route('offset_requests.show', $firstOffset->id) }}" class="text-decoration-underline text-primary">
                                            {{ $firstOffset->date }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">{{ $firstOffset ? number_format($firstOffset->number_of_hours, 2) : '-' }}</td>
                                <td class="text-end text-success" rowspan="{{ max(1, $offsets->count()) }}">
                                    {{ number_format($balance, 2) }}
                                </td>
                            </tr>

                            @foreach ($remainingOffsets as $offset)
                                <tr>
                                    <td colspan="3"></td>
                                    <td>
                                        <a href="{{ route('offset_requests.show', $offset->id) }}" class="text-decoration-underline text-primary">
                                            {{ $offset->date }}
                                        </a>
                                    </td>
                                    <td class="text-end">{{ number_format($offset->number_of_hours, 2) }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No overtime or offset records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
