<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">Outbase Request Report</h2>
    </x-slot>

    <div class="container py-4">
        {{-- Filter Form --}}
        <form method="GET" class="row g-3 mb-4 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date', $startDate) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date', $endDate) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Location Contains</label>
                <input type="text" name="location" class="form-control" value="{{ request('location') }}" placeholder="e.g. Makati, Plant">
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-primary" type="submit">Filter</button>
            </div>
        </form>

        <!-- Download Buttons -->
        <div class="d-flex gap-2 mb-3">
            <a id="pdfLink"
                href="{{ route('reports.outbase_history.pdf', request()->only('start_date', 'end_date', 'status', 'location')) }}"
                class="btn btn-outline-primary">
                Download PDF
            </a>

            <a id="excelLink"
                href="{{ route('reports.outbase_history.excel', request()->only('start_date', 'end_date', 'status', 'location')) }}"
                class="btn btn-outline-success">
                Export Excel
            </a>
        </div>

        @if($outbaseRequests->isEmpty())
            <div class="alert alert-info">You have no outbase requests for the selected period.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-light">
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
                                <td>{{ \Carbon\Carbon::parse($req->date)->toFormattedDateString() }}</td>
                                <td>{{ $req->employee->department->name ?? 'N/A' }}</td>
                                <td>{{ \Carbon\Carbon::parse($req->time_start)->format('h:i A') }}</td>
                                <td>{{ \Carbon\Carbon::parse($req->time_end)->format('h:i A') }}</td>
                                <td>{{ $req->location }}</td>
                                <td>{{ $req->reason }}</td>
                                <td>
                                    <span class="badge bg-{{ $req->status == 'approved' ? 'success' : ($req->status == 'rejected' ? 'danger' : 'secondary') }}">
                                        {{ ucfirst($req->status) }}
                                    </span>
                                </td>
                                <td>{{ $req->approver?->name ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
