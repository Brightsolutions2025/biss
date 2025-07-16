<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Departments') }}
        </h2>
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

                <!-- Search -->
                <h5 class="mb-3">Search</h5>
                <form method="GET" action="{{ route('departments.index') }}" class="mb-4">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Department Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name"
                            class="form-control"
                            value="{{ request('name') }}"
                        >
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>

                <!-- Add New -->
                <h5 class="mt-4">Add</h5>
                <p>
                    Want to add a new department? 
                    <a href="{{ route('departments.create') }}">Click here</a>!
                </p>

                <!-- Department List -->
                <h5 class="mt-4">List of Departments</h5>

                @forelse ($departments as $department)
                    <div class="card mb-3">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <div class="mb-2 mb-md-0">
                                <strong>{{ $department->name }}</strong>
                                <div class="text-muted small">
                                    {{ $department->employees_count ?? 0 }} employees
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('departments.show', $department) }}" class="btn btn-sm btn-outline-primary">View</a>
                                <a href="{{ route('departments.edit', $department) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                <form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('Are you sure you want to delete this department?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No departments recorded yet.</p>
                @endforelse

                @php
                    $currentPage = $departments->currentPage();
                    $lastPage = $departments->lastPage();
                    $linkCount = 5;
                    $half = floor($linkCount / 2);

                    $start = max(1, $currentPage - $half);
                    $end = min($lastPage, $start + $linkCount - 1);

                    if ($end - $start < $linkCount - 1) {
                        $start = max(1, $end - $linkCount + 1);
                    }
                @endphp

                @if ($departments->hasPages())
                    <div class="mt-4">
                        <div class="d-flex justify-content-center">
                            <nav>
                                <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                    <!-- Previous -->
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $departments->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    <!-- First & Ellipsis -->
                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $departments->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    <!-- Numbered Pages -->
                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $departments->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    <!-- Ellipsis & Last -->
                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $departments->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    <!-- Next -->
                                    <li class="page-item {{ !$departments->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $departments->nextPageUrl() ?? '#' }}">&raquo;</a>
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
