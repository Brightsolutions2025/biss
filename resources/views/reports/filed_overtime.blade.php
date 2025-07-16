<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">{{ __('Filed Overtime Report') }}</h2>
    </x-slot>

    <div class="container py-4">
        <div class="mb-3">
            <p class="fw-semibold">List of all your overtime requests including status, hours, and expiration date.</p>
        </div>

        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
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
                    @forelse ($overtimeRequests as $ot)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($ot->date)->toFormattedDateString() }}</td>
                            <td>{{ \Carbon\Carbon::parse($ot->time_start)->format('h:i A') }}</td>
                            <td>{{ \Carbon\Carbon::parse($ot->time_end)->format('h:i A') }}</td>
                            <td>{{ number_format($ot->number_of_hours, 2) }}</td>
                            <td>{{ $ot->reason }}</td>
                            <td>
                                @if ($ot->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif ($ot->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>
                            <td>{{ $ot->approval_date ? \Carbon\Carbon::parse($ot->approval_date)->toFormattedDateString() : '—' }}</td>
                            <td>{{ $ot->expires_at ? \Carbon\Carbon::parse($ot->expires_at)->toFormattedDateString() : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No overtime records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <!-- Download Buttons -->
            <div class="d-flex gap-2 mb-3">
                <a href="{{ route('reports.overtime_history.pdf', request()->only('start_date', 'end_date', 'status')) }}"
                    class="btn btn-outline-primary">
                    Download PDF
                </a>

                <a href="{{ route('reports.overtime_history.excel', request()->only('start_date', 'end_date', 'status')) }}"
                    class="btn btn-outline-success">
                    Export Excel
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
