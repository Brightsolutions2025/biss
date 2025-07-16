<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Employees') }}</h2>
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

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Search</h5>
                        <form method="GET" action="{{ route('employees.index') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    name="name" 
                                    id="name" 
                                    value="{{ request('name') }}" 
                                    placeholder="Search by name">
                            </div>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Add</h5>
                        <p class="card-text">
                            Want to add a new employee? Click 
                            <a href="{{ route('employees.create') }}" class="text-decoration-underline">here</a>!
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Employee List</h5>

                        @forelse ($employees as $employee)
                            <div class="mb-3 border-bottom pb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong><br>
                                        <small class="text-muted">
                                            {{ $employee->position ?? 'No position' }}
                                            @if ($employee->department)
                                                — Dept: {{ $employee->department->name }}
                                            @endif
                                            @if ($employee->team)
                                                , Team: {{ $employee->team->name }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-primary btn-sm me-1">View</a>
                                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-warning btn-sm me-1">Edit</a>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Are you sure you want to delete this employee?')" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No employees recorded yet.</p>
                        @endforelse

                        {{-- Pagination --}}
                        @php
                            $currentPage = $employees->currentPage();
                            $lastPage = $employees->lastPage();
                            $linkCount = 5;
                            $half = floor($linkCount / 2);

                            $start = max(1, $currentPage - $half);
                            $end = min($lastPage, $start + $linkCount - 1);

                            if ($end - $start < $linkCount - 1) {
                                $start = max(1, $end - $linkCount + 1);
                            }
                        @endphp

                        @if ($employees->lastPage() > 1)
                            <div class="mt-4">
                                <div class="d-flex justify-content-center">
                                    <nav>
                                        <ul class="pagination pagination-sm flex-wrap justify-content-center">
                                            {{-- Previous --}}
                                            <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                                <a class="page-link" href="{{ $employees->previousPageUrl() ?? '#' }}">&laquo;</a>
                                            </li>

                                            {{-- First & Ellipsis --}}
                                            @if ($start > 1)
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $employees->url(1) }}">1</a>
                                                </li>
                                                @if ($start > 2)
                                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                                @endif
                                            @endif

                                            {{-- Pages --}}
                                            @for ($i = $start; $i <= $end; $i++)
                                                <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                                    <a class="page-link" href="{{ $employees->url($i) }}">{{ $i }}</a>
                                                </li>
                                            @endfor

                                            {{-- Ellipsis & Last --}}
                                            @if ($end < $lastPage)
                                                @if ($end < $lastPage - 1)
                                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                                @endif
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $employees->url($lastPage) }}">{{ $lastPage }}</a>
                                                </li>
                                            @endif

                                            {{-- Next --}}
                                            <li class="page-item {{ !$employees->hasMorePages() ? 'disabled' : '' }}">
                                                <a class="page-link" href="{{ $employees->nextPageUrl() ?? '#' }}">&raquo;</a>
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
