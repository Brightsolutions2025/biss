<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Leave Balances') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">

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

                {{-- Search Form --}}
                <h5 class="mb-3">Search</h5>
                <form method="GET" action="{{ route('leave_balances.index') }}" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="employee_name" class="form-label">Employee Name</label>
                            <input type="text" name="employee_name" id="employee_name" class="form-control" value="{{ request('employee_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" name="year" id="year" class="form-control" value="{{ request('year') }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </div>
                </form>

                {{-- Add New --}}
                <h5 class="mb-2">Add</h5>
                <p class="mb-4">
                    Want to add a new leave balance? Click
                    <a href="{{ route('leave_balances.create') }}">here</a>!
                </p>

                {{-- Leave Balances List --}}
                <h5 class="mb-3">List</h5>

                @forelse ($leaveBalances as $balance)
                    <div class="card mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">
                                    {{ $balance->employee->last_name }}, {{ $balance->employee->first_name }} – {{ $balance->year }}
                                </h6>
                                <p class="mb-0 text-muted small">
                                    Beginning Balance: {{ $balance->beginning_balance }}
                                </p>
                            </div>
                            <div class="text-end">
                                <a href="{{ route('leave_balances.show', $balance->id) }}" class="btn btn-sm btn-outline-primary me-2">View</a>
                                <a href="{{ route('leave_balances.edit', $balance->id) }}" class="btn btn-sm btn-outline-warning me-2">Edit</a>
                                <form method="POST" action="{{ route('leave_balances.destroy', $balance->id) }}" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No leave balances recorded yet.</p>
                @endforelse

                <!-- Pagination -->
                @if ($leaveBalances->hasPages())
                    @php
                        $currentPage = $leaveBalances->currentPage();
                        $lastPage = $leaveBalances->lastPage();
                        $linkCount = 5;
                        $half = floor($linkCount / 2);

                        $start = max(1, $currentPage - $half);
                        $end = min($lastPage, $start + $linkCount - 1);

                        if ($end - $start < $linkCount - 1) {
                            $start = max(1, $end - $linkCount + 1);
                        }
                    @endphp

                    <div class="mt-4">
                        <div class="d-flex justify-content-center">
                            <nav>
                                <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                    <!-- Previous -->
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $leaveBalances->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    <!-- First page & Ellipsis -->
                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $leaveBalances->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    <!-- Page Numbers -->
                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $leaveBalances->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    <!-- Ellipsis & Last Page -->
                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $leaveBalances->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    <!-- Next -->
                                    <li class="page-item {{ !$leaveBalances->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $leaveBalances->nextPageUrl() ?? '#' }}">&raquo;</a>
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
