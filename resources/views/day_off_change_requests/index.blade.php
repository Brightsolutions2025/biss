{{-- resources/views/day_off_change_requests/index.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Day Off Change Requests') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                {{-- Success / Error Messages --}}
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Search & Filter -->
                <h5 class="mb-3">Search</h5>
                <form method="GET" action="{{ route('day_off_change_requests.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Employee</label>
                            <input
                                type="text"
                                name="employee"
                                value="{{ request('employee') }}"
                                class="form-control"
                                placeholder="Employee name"
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Reason Type</label>
                            <select name="reason_type" class="form-select">
                                <option value="">All</option>
                                <option value="Offset due to Extension of Work"
                                    {{ request('reason_type') === 'Offset due to Extension of Work' ? 'selected' : '' }}>
                                    Offset due to Extension of Work
                                </option>
                                <option value="Schedule / Day Off Adjustment"
                                    {{ request('reason_type') === 'Schedule / Day Off Adjustment' ? 'selected' : '' }}>
                                    Schedule / Day Off Adjustment
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-primary w-100">Apply</button>
                        </div>
                    </div>
                </form>

                <br>

                <!-- Add New -->
                <h5 class="mb-3">Add New Request</h5>
                <p>
                    Need to file a new day off change request?
                    <a href="{{ route('day_off_change_requests.create') }}">Click here</a>.
                </p>

                <!-- Request List -->
                <h5 class="mt-4">List of Day Off Change Requests</h5>

                @forelse ($requests as $request)
                    <div class="card mb-3 border shadow-sm">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">

                            <div class="mb-2 mb-md-0">
                                <strong>{{ $request->employee->user->name ?? 'Unknown Employee' }}</strong>
                                <div class="text-muted small">
                                    Reason Type: {{ $request->reason_type }}<br>
                                    Extension Date: {{ $request->extension_date->format('Y-m-d') }}<br>
                                    Time: {{ $request->time_start }} – {{ $request->time_end }}<br>
                                    Hours: {{ number_format($request->number_of_hours, 2) }}<br>
                                    Old Day Off: {{ $request->old_date->format('Y-m-d') }}<br>
                                    New Day Off: {{ $request->new_date->format('Y-m-d') }}<br>
                                    Status:
                                    <span class="fw-semibold text-capitalize">{{ $request->status }}</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <a
                                    href="{{ route('day_off_change_requests.show', $request) }}"
                                    class="btn btn-sm btn-outline-primary me-2"
                                >
                                    View
                                </a>

                                <a
                                    href="{{ route('day_off_change_requests.edit', $request) }}"
                                    class="btn btn-sm btn-outline-secondary me-2"
                                >
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route('day_off_change_requests.destroy', $request) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to delete this request?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>

                        </div>
                    </div>
                @empty
                    <p class="text-muted">No day off change requests found.</p>
                @endforelse

                {{-- Pagination --}}
                @if ($requests->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $requests->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
