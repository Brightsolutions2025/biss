<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Overtime Requests') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
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

                <!-- Filter Section -->
                <div class="mb-4">
                    <h5 class="mb-3">Search</h5>
                    <form method="GET" action="{{ route('overtime_requests.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select name="employee_id" id="employee_id" class="form-select">
                                <option value="">-- All Employees --</option>
                                @foreach ($employeeList as $employee)
                                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">-- All Statuses --</option>
                                @foreach(['pending', 'approved', 'rejected'] as $status)
                                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Date Range</label>
                            <div class="d-flex gap-2">
                                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                            </div>
                        </div>

                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </form>
                </div>

                <!-- Add Link -->
                <div class="mb-4">
                    <h5>Add</h5>
                    <p>
                        Want to submit a new overtime request?
                        <a href="{{ route('overtime_requests.create') }}" class="link-primary">Click here</a>!
                    </p>
                </div>

                <!-- List Section -->
                <div class="mb-3">
                    <h5>Overtime Requests</h5>
                </div>

                @forelse ($overtimeRequests as $request)
                    <div class="card mb-3">
                        <div class="card-body d-flex justify-content-between align-items-start flex-wrap">
                            <div class="flex-grow-1">
                                <div class="fw-bold">
                                    {{ $request->employee->last_name }}, {{ $request->employee->first_name }}
                                    <span class="text-muted">({{ $request->employee->employee_number }})</span>
                                </div>
                                <div>Date: {{ \Carbon\Carbon::parse($request->date)->format('M d, Y') }}</div>
                                <div>Time: {{ \Carbon\Carbon::parse($request->time_start)->format('h:i A') }} - {{ \Carbon\Carbon::parse($request->time_end)->format('h:i A') }}</div>
                                <div>Hours: {{ $request->number_of_hours }}</div>
                                <div>Reason: {{ $request->reason }}</div>
                                <div>
                                    Status:
                                    <span class="fw-semibold
                                        {{ 
                                            $request->status === 'approved' ? 'text-success' : 
                                            ($request->status === 'rejected' ? 'text-danger' : 'text-warning') 
                                        }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ms-auto mt-3 mt-sm-0 d-flex flex-wrap gap-2">
                                <a href="{{ route('overtime_requests.show', $request->id) }}" class="btn btn-outline-primary btn-sm">View</a>
                                <a href="{{ route('overtime_requests.edit', $request->id) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                                <form action="{{ route('overtime_requests.destroy', $request->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this request?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No overtime requests recorded{{ request('date') ? ' on this date' : '' }}.</p>
                @endforelse

                @php
                    $currentPage = $overtimeRequests->currentPage();
                    $lastPage = $overtimeRequests->lastPage();
                    $linkCount = 5;
                    $half = floor($linkCount / 2);

                    $start = max(1, $currentPage - $half);
                    $end = min($lastPage, $start + $linkCount - 1);

                    if ($end - $start < $linkCount - 1) {
                        $start = max(1, $end - $linkCount + 1);
                    }
                @endphp

                @if ($overtimeRequests->lastPage() > 1)
                    <div class="mt-4 d-flex justify-content-center">
                        <nav>
                            <ul class="pagination pagination-sm flex-wrap justify-content-center">
                                <!-- Previous -->
                                <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $overtimeRequests->previousPageUrl() ?? '#' }}">&laquo;</a>
                                </li>

                                <!-- First page & Ellipsis -->
                                @if ($start > 1)
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $overtimeRequests->url(1) }}">1</a>
                                    </li>
                                    @if ($start > 2)
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    @endif
                                @endif

                                <!-- Page Numbers -->
                                @for ($i = $start; $i <= $end; $i++)
                                    <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                        <a class="page-link" href="{{ $overtimeRequests->url($i) }}">{{ $i }}</a>
                                    </li>
                                @endfor

                                <!-- Ellipsis & Last Page -->
                                @if ($end < $lastPage)
                                    @if ($end < $lastPage - 1)
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    @endif
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $overtimeRequests->url($lastPage) }}">{{ $lastPage }}</a>
                                    </li>
                                @endif

                                <!-- Next -->
                                <li class="page-item {{ !$overtimeRequests->hasMorePages() ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $overtimeRequests->nextPageUrl() ?? '#' }}">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                @endif

            </div>
        </div>
    </div>
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush

    @push('styles')
        <style>
            .select2-container--default .select2-selection--single {
                height: calc(2.5rem + 2px);
                padding: 0.375rem 0.75rem;
                border: 1px solid #ced4da;
                border-radius: 0.375rem;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 2rem;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 2.5rem;
                right: 10px;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                $('#employee_id').select2({ placeholder: 'Select employee...', allowClear: true, width: '100%' });
                $('#status').select2({ placeholder: 'Select status...', allowClear: true, width: '100%' });
            });
        </script>
    @endpush

</x-app-layout>
