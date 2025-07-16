<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Shifts') }}</h2>
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

                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Search</h5>
                        <form method="GET" action="{{ route('shifts.index') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label">Shift Name</label>
                                <input 
                                    type="text"
                                    class="form-control"
                                    id="name"
                                    name="name"
                                    value="{{ request('name') }}"
                                    placeholder="Search by shift name">
                            </div>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </form>
                    </div>
                </div>

                <!-- Add Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Add New Shift</h5>
                        <p class="card-text">
                            Want to add a new shift? Click 
                            <a href="{{ route('shifts.create') }}" class="text-decoration-underline">here</a>!
                        </p>
                    </div>
                </div>

                <!-- List Section -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Shift List</h5>

                        @forelse ($shifts as $shift)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $shift->name }}</strong><br>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $shift->time_in)->format('g:i A') }} – 
                                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $shift->time_out)->format('g:i A') }}
                                            @if ($shift->is_night_shift)
                                                <span class="badge bg-warning text-dark ms-2">Night Shift</span>
                                            @endif
                                        </small>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('shifts.show', $shift) }}" class="btn btn-outline-primary btn-sm">View</a>
                                        <a href="{{ route('shifts.edit', $shift) }}" class="btn btn-outline-warning btn-sm">Edit</a>
                                        <form method="POST" action="{{ route('shifts.destroy', $shift) }}" onsubmit="return confirm('Are you sure you want to delete this shift?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No shifts recorded yet.</p>
                        @endforelse

                        @php
                            $currentPage = $shifts->currentPage();
                            $lastPage = $shifts->lastPage();
                            $linkCount = 5;
                            $half = floor($linkCount / 2);

                            $start = max(1, $currentPage - $half);
                            $end = min($lastPage, $start + $linkCount - 1);

                            if ($end - $start < $linkCount - 1) {
                                $start = max(1, $end - $linkCount + 1);
                            }
                        @endphp

                        <!-- Pagination -->
                        @if ($shifts->hasPages())
                            <div class="mt-4">
                                <div class="d-flex justify-content-center">
                                    <nav>
                                        <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                            <!-- Previous -->
                                            <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                                <a class="page-link" href="{{ $shifts->previousPageUrl() ?? '#' }}">&laquo;</a>
                                            </li>

                                            <!-- First Page + Ellipsis -->
                                            @if ($start > 1)
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $shifts->url(1) }}">1</a>
                                                </li>
                                                @if ($start > 2)
                                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                                @endif
                                            @endif

                                            <!-- Page Numbers -->
                                            @for ($i = $start; $i <= $end; $i++)
                                                <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                                    <a class="page-link" href="{{ $shifts->url($i) }}">{{ $i }}</a>
                                                </li>
                                            @endfor

                                            <!-- Ellipsis + Last Page -->
                                            @if ($end < $lastPage)
                                                @if ($end < $lastPage - 1)
                                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                                @endif
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $shifts->url($lastPage) }}">{{ $lastPage }}</a>
                                                </li>
                                            @endif

                                            <!-- Next -->
                                            <li class="page-item {{ !$shifts->hasMorePages() ? 'disabled' : '' }}">
                                                <a class="page-link" href="{{ $shifts->nextPageUrl() ?? '#' }}">&raquo;</a>
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
    </div>
</x-app-layout>
