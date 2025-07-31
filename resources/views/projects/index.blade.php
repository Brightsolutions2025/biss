{{-- resources/views/projects/index.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Projects') }}
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

                <!-- Search, Filter & Sort -->
                <h5 class="mb-3">Search</h5>
                <form method="GET" action="{{ route('projects.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search by Project Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', request('name')) }}" class="form-control" placeholder="e.g. Website Redesign">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All</option>
                                <option value="planned" {{ old('status', request('status')) == 'planned' ? 'selected' : '' }}>Planned</option>
                                <option value="in-progress" {{ old('status', request('status')) == 'in-progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="on-hold" {{ old('status', request('status')) == 'on-hold' ? 'selected' : '' }}>On Hold</option>
                                <option value="completed" {{ old('status', request('status')) == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ old('status', request('status')) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select name="sort_by" id="sort_by" class="form-select">
                                <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Name</option>
                                <option value="start_date" {{ request('sort_by') == 'start_date' ? 'selected' : '' }}>Start Date</option>
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Date Created</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                    </div>
                </form>

                <br>

                <!-- Add New -->
                <h5 class="mb-3">Add Project</h5>
                <p>
                    Want to start a new project?
                    <a href="{{ route('projects.create') }}">Click here</a>!
                </p>

                <!-- Project List -->
                <h5 class="mt-4">List of Projects</h5>

                @forelse ($projects as $project)
                    <div class="card mb-3">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <div class="mb-2 mb-md-0">
                                <strong>{{ $project->name }}</strong>
                                <div class="text-muted small">
                                    Type: {{ ucfirst($project->project_type) }}<br>
                                    Client: {{ $project->client->name ?? 'N/A' }}<br>
                                    Status: {{ ucfirst($project->status) }}<br>
                                    Budget: ₱{{ number_format($project->budget, 2) ?? '0.00' }}<br>
                                    Start: {{ $project->start_date ?? 'N/A' }} | End: {{ $project->end_date ?? 'N/A' }}
                                </div>
                                @if ($project->tags)
                                    <div class="mt-1">
                                        @foreach(json_decode($project->tags, true) ?? [] as $tag)
                                            <span class="badge bg-secondary">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="d-flex align-items-center">
                                <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-primary me-2">View</a>
                                <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-secondary me-2">Edit</a>
                                <form method="POST" action="{{ route('projects.destroy', $project) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this project?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No projects recorded yet.</p>
                @endforelse

                <!-- Pagination -->
                @if ($projects->hasPages())
                    @php
                        $currentPage = $projects->currentPage();
                        $lastPage = $projects->lastPage();
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
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $projects->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $projects->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $projects->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $projects->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    <li class="page-item {{ !$projects->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $projects->nextPageUrl() ?? '#' }}">&raquo;</a>
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
