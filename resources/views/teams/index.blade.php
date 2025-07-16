<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Teams') }}
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
                <form method="GET" action="{{ route('teams.index') }}" class="mb-4">
                    <div class="mb-3">
                        <label for="name" class="form-label">Team Name</label>
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
                <h5 class="mt-4 mb-2">Add</h5>
                <p>Want to add a new team? Click 
                    <a href="{{ route('teams.create') }}">here</a>!
                </p>

                <!-- List -->
                <h5 class="mt-4 mb-3">Team List</h5>

                @forelse ($teams as $team)
                    <div class="card mb-3">
                        <div class="card-body d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-title mb-1">{{ $team->name }}</h6>
                                <p class="card-text mb-1 text-muted">
                                    Department: {{ $team->department->name }}
                                </p>
                                <p class="card-text text-muted">{{ $team->description }}</p>
                            </div>
                            <div>
                                <a href="{{ route('teams.show', $team) }}" class="btn btn-sm btn-outline-primary me-2">View</a>
                                <a href="{{ route('teams.edit', $team) }}" class="btn btn-sm btn-outline-secondary me-2">Edit</a>
                                <form method="POST" action="{{ route('teams.destroy', $team) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this team?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No teams recorded yet.</p>
                @endforelse

                <!-- Pagination -->
                @if ($teams->hasPages())
                    <div class="mt-4">
                        <div class="d-flex justify-content-center">
                            <nav>
                                <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                    <!-- Previous -->
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $teams->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    <!-- First page & Ellipsis -->
                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $teams->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    <!-- Page Numbers -->
                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $teams->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    <!-- Ellipsis & Last Page -->
                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $teams->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    <!-- Next -->
                                    <li class="page-item {{ !$teams->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $teams->nextPageUrl() ?? '#' }}">&raquo;</a>
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
