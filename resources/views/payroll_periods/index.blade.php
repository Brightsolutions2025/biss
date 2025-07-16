<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Payroll Periods') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
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

                <!-- Search Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Search</h5>
                        <form method="GET" action="{{ route('payroll_periods.index') }}">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input 
                                    type="date" 
                                    name="start_date" 
                                    id="start_date" 
                                    class="form-control @error('start_date') is-invalid @enderror" 
                                    value="{{ request('start_date') }}"
                                >
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </form>
                    </div>

                    <!-- Add Link -->
                    <div class="card-body">
                        <h5>Add</h5>
                        <p>Want to add a new payroll period? Click 
                            <a href="{{ route('payroll_periods.create') }}" class="text-decoration-underline">here</a>!
                        </p>
                    </div>

                    <!-- List Section -->
                    <div class="card-body">
                        <h5 class="card-title mb-4">Payroll Period List</h5>

                        @forelse ($payrollPeriods as $period)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>From:</strong> {{ \Carbon\Carbon::parse($period->start_date)->toFormattedDateString() }}<br>
                                        <strong>To:</strong> {{ \Carbon\Carbon::parse($period->end_date)->toFormattedDateString() }}
                                    </div>
                                    <div class="btn-group btn-group-sm mt-2 mt-md-0" role="group">
                                        <a href="{{ route('payroll_periods.show', $period) }}" class="btn btn-outline-primary me-2">View</a>
                                        <a href="{{ route('payroll_periods.edit', $period) }}" class="btn btn-outline-warning me-2">Edit</a>
                                        <form method="POST" action="{{ route('payroll_periods.destroy', $period) }}" onsubmit="return confirm('Are you sure you want to delete this payroll period?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No payroll periods recorded yet.</p>
                        @endforelse
                        <!-- Pagination -->
                        @php
                            $currentPage = $payrollPeriods->currentPage();
                            $lastPage = $payrollPeriods->lastPage();
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
                                            <a class="page-link" href="{{ $payrollPeriods->previousPageUrl() ?? '#' }}">&laquo;</a>
                                        </li>

                                        <!-- First page & Ellipsis -->
                                        @if ($start > 1)
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $payrollPeriods->url(1) }}">1</a>
                                            </li>
                                            @if ($start > 2)
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            @endif
                                        @endif

                                        <!-- Page Numbers -->
                                        @for ($i = $start; $i <= $end; $i++)
                                            <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                                <a class="page-link" href="{{ $payrollPeriods->url($i) }}">{{ $i }}</a>
                                            </li>
                                        @endfor

                                        <!-- Ellipsis & Last Page -->
                                        @if ($end < $lastPage)
                                            @if ($end < $lastPage - 1)
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            @endif
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $payrollPeriods->url($lastPage) }}">{{ $lastPage }}</a>
                                            </li>
                                        @endif

                                        <!-- Next -->
                                        <li class="page-item {{ !$payrollPeriods->hasMorePages() ? 'disabled' : '' }}">
                                            <a class="page-link" href="{{ $payrollPeriods->nextPageUrl() ?? '#' }}">&raquo;</a>
                                        </li>

                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
