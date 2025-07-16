<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Offset Requests') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="card shadow-sm">
                <div class="card-body">

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <h5 class="mb-3">Search</h5>
                    <form method="GET" action="{{ route('offset_requests.index') }}" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <!-- Date From -->
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Date From</label>
                                <input
                                    type="date"
                                    name="date_from"
                                    id="date_from"
                                    class="form-control"
                                    value="{{ request('date_from') }}">
                            </div>

                            <!-- Date To -->
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Date To</label>
                                <input
                                    type="date"
                                    name="date_to"
                                    id="date_to"
                                    class="form-control"
                                    value="{{ request('date_to') }}">
                            </div>

                            <!-- Employee -->
                            <div class="col-md-3">
                                <label for="employee_id" class="form-label">Employee</label>
                                <select name="employee_id" id="employee_id" class="form-select">
                                    <option value="">All</option>
                                    @foreach ($employeeList as $employee)
                                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="denied" {{ request('status') == 'denied' ? 'selected' : '' }}>Denied</option>
                                </select>
                            </div>

                            <!-- Search Button -->
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                        </div>
                    </form>

                    <h5 class="mb-2">Add</h5>
                    <p class="mb-4">
                        Want to file a new offset request? Click 
                        <a href="{{ route('offset_requests.create') }}" class="link-primary">here</a>!
                    </p>

                    <h5 class="mb-3">List</h5>

                    @forelse ($offsetRequests as $request)
                        <div class="border-bottom mb-3 pb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="fw-bold mb-1">
                                        {{ $request->employee->first_name }} {{ $request->employee->last_name }} —
                                        {{ \Carbon\Carbon::parse($request->date)->format('F d, Y') }}
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        {{ $request->project_or_event_description }} |
                                        {{ $request->time_start }} - {{ $request->time_end }} |
                                        {{ $request->number_of_hours }} hrs |
                                        Status: <span class="fw-semibold">{{ ucfirst($request->status) }}</span>
                                    </p>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('offset_requests.show', $request) }}" class="btn btn-outline-primary btn-sm">View</a>
                                    <a href="{{ route('offset_requests.edit', $request) }}" class="btn btn-outline-warning btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('offset_requests.destroy', $request) }}" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No offset requests recorded yet.</div>
                    @endforelse

                    {{-- Pagination --}}
                    @php
                        $currentPage = $offsetRequests->currentPage();
                        $lastPage = $offsetRequests->lastPage();
                        $linkCount = 5;
                        $half = floor($linkCount / 2);

                        $start = max(1, $currentPage - $half);
                        $end = min($lastPage, $start + $linkCount - 1);

                        if ($end - $start < $linkCount - 1) {
                            $start = max(1, $end - $linkCount + 1);
                        }
                    @endphp

                    @if ($offsetRequests->hasPages())
                        <div class="mt-4">
                            <div class="d-flex justify-content-center">
                                <nav>
                                    <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                        {{-- Previous --}}
                                        <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                            <a class="page-link" href="{{ $offsetRequests->previousPageUrl() ?? '#' }}">&laquo;</a>
                                        </li>

                                        {{-- First Page + Ellipsis --}}
                                        @if ($start > 1)
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $offsetRequests->url(1) }}">1</a>
                                            </li>
                                            @if ($start > 2)
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            @endif
                                        @endif

                                        {{-- Page Numbers --}}
                                        @for ($i = $start; $i <= $end; $i++)
                                            <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                                <a class="page-link" href="{{ $offsetRequests->url($i) }}">{{ $i }}</a>
                                            </li>
                                        @endfor

                                        {{-- Ellipsis + Last Page --}}
                                        @if ($end < $lastPage)
                                            @if ($end < $lastPage - 1)
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            @endif
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $offsetRequests->url($lastPage) }}">{{ $lastPage }}</a>
                                            </li>
                                        @endif

                                        {{-- Next --}}
                                        <li class="page-item {{ !$offsetRequests->hasMorePages() ? 'disabled' : '' }}">
                                            <a class="page-link" href="{{ $offsetRequests->nextPageUrl() ?? '#' }}">&raquo;</a>
                                        </li>

                                    </ul>
                                </nav>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function () {
                $('#employee_id').select2({
                    theme: 'bootstrap-5',
                    placeholder: "Select employee",
                    allowClear: true,
                    width: '100%'
                });
            });
        </script>
    @endpush


</x-app-layout>
