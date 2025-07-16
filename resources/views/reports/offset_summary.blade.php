<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">Offset Request Summary</h2>
    </x-slot>

    <div class="container py-4">
        <form method="GET" class="row g-3 mb-4 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control"
                    value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control"
                    value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Project/Event Contains</label>
                <input type="text" name="project" class="form-control" value="{{ request('project') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Min Hours</label>
                <input type="number" name="min_hours" class="form-control" step="0.1" value="{{ request('min_hours') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Max Hours</label>
                <input type="number" name="max_hours" class="form-control" step="0.1" value="{{ request('max_hours') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Approver Contains</label>
                <input type="text" name="approver" class="form-control" value="{{ request('approver') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sort by Date</label>
                <select name="sort" class="form-select">
                    <option value="asc" {{ request('sort') === 'asc' ? 'selected' : '' }}>Oldest First</option>
                    <option value="desc" {{ request('sort') === 'desc' ? 'selected' : '' }}>Newest First</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary" type="submit">Filter</button>
            </div>
        </form>
        
        <!-- Download Buttons -->
        <div class="d-flex gap-2 mb-3">
            <a id="pdfLink"
                href="{{ route('reports.offset_summary.pdf', request()->only('start_date', 'end_date', 'status', 'project', 'min_hours', 'max_hours', 'approver', 'sort')) }}"
                class="btn btn-outline-primary">
                Download PDF
            </a>

            <a id="excelLink"
                href="{{ route('reports.offset_summary.excel', request()->only('start_date', 'end_date', 'status', 'project', 'min_hours', 'max_hours', 'approver', 'sort')) }}"
                class="btn btn-outline-success">
                Export Excel
            </a>
        </div>

        @if ($offsetRequests->isEmpty())
            <div class="alert alert-info">No offset requests found for the selected period.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-light">
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
                                <td>{{ \Carbon\Carbon::parse($req->date)->toFormattedDateString() }}</td>
                                <td>{{ \Carbon\Carbon::parse($req->time_start)->format('h:i A') }}</td>
                                <td>{{ \Carbon\Carbon::parse($req->time_end)->format('h:i A') }}</td>
                                <td>{{ $req->number_of_hours }}</td>
                                <td>{{ $req->reason ?? '-' }}</td>
                                <td>{{ $req->project_or_event_description }}</td>
                                <td>
                                    <span class="badge bg-{{ $req->status === 'approved' ? 'success' : ($req->status === 'rejected' ? 'danger' : 'secondary') }}">
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
