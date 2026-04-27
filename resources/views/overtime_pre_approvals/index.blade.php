<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Overtime Pre-Approvals') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

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

                <h5 class="mb-3">Search</h5>

                <form method="GET" action="{{ route('overtime_pre_approvals.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select name="employee_id" id="employee_id" class="form-select">
                                <option value="">All Employees</option>
                                @foreach ($employeeList as $employee)
                                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-control">
                        </div>

                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-control">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                    </div>
                </form>

                <br>

                <h5 class="mb-3">Add New Pre-Approval</h5>
                <p>
                    Need to request overtime approval ahead of time?
                    <a href="{{ route('overtime_pre_approvals.create') }}">Click here</a>!
                </p>

                <h5 class="mt-4">List of Overtime Pre-Approvals</h5>

                @forelse ($overtimePreApprovals as $preApproval)
                    <div class="card mb-3 border shadow-sm">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <div class="mb-2 mb-md-0">
                                <strong>
                                    {{ $preApproval->employee->first_name ?? 'Unknown' }}
                                    {{ $preApproval->employee->last_name ?? 'Employee' }}
                                </strong>

                                <div class="text-muted small">
                                    Date: {{ optional($preApproval->date)->format('Y-m-d') ?? $preApproval->date }}<br>
                                    Planned Time:
                                    {{ \Illuminate\Support\Carbon::parse($preApproval->planned_time_start)->format('h:i A') }}
                                    -
                                    {{ \Illuminate\Support\Carbon::parse($preApproval->planned_time_end)->format('h:i A') }}<br>
                                    Estimated Hours: {{ number_format($preApproval->estimated_number_of_hours ?? 0, 2) }}<br>
                                    Reason: {{ $preApproval->reason }}<br>
                                    Planned Tasks: {{ $preApproval->planned_tasks ?? 'N/A' }}<br>
                                    Status:
                                    <span class="badge 
                                        @if ($preApproval->status === 'approved') bg-success
                                        @elseif ($preApproval->status === 'rejected') bg-danger
                                        @else bg-warning text-dark
                                        @endif">
                                        {{ ucfirst($preApproval->status) }}
                                    </span>
                                    <br>
                                    Approval Date: {{ $preApproval->approval_date ?? 'N/A' }}<br>
                                    Rejection Reason: {{ $preApproval->rejection_reason ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <a href="{{ route('overtime_pre_approvals.show', $preApproval) }}" class="btn btn-sm btn-outline-primary">
                                    View
                                </a>

                                <a href="{{ route('overtime_pre_approvals.edit', $preApproval) }}" class="btn btn-sm btn-outline-secondary">
                                    Edit
                                </a>

                                @if ($preApproval->status === 'pending')
                                    <form method="POST" action="{{ route('overtime_pre_approvals.approve', $preApproval) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this overtime pre-approval?')">
                                            Approve
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('overtime_pre_approvals.reject', $preApproval) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="reason" value="Rejected from list page.">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Reject this overtime pre-approval?')">
                                            Reject
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('overtime_pre_approvals.destroy', $preApproval) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this record?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No overtime pre-approval records found.</p>
                @endforelse

                @php
                    $currentPage = $overtimePreApprovals->currentPage();
                    $lastPage = $overtimePreApprovals->lastPage();
                    $linkCount = 5;
                    $half = floor($linkCount / 2);

                    $start = max(1, $currentPage - $half);
                    $end = min($lastPage, $start + $linkCount - 1);

                    if ($end - $start < $linkCount - 1) {
                        $start = max(1, $end - $linkCount + 1);
                    }
                @endphp

                @if ($overtimePreApprovals->hasPages())
                    <div class="mt-4">
                        <div class="d-flex justify-content-center">
                            <nav>
                                <ul class="pagination pagination-sm flex-wrap justify-content-center">
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $overtimePreApprovals->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $overtimePreApprovals->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $overtimePreApprovals->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $overtimePreApprovals->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    <li class="page-item {{ !$overtimePreApprovals->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $overtimePreApprovals->nextPageUrl() ?? '#' }}">&raquo;</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>