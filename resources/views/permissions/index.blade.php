<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Permissions') }}
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
                <form method="GET" action="{{ route('permissions.index') }}" class="mb-4">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
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
                <p>Want to add a new permission? Click 
                    <a href="{{ route('permissions.create') }}">here</a>!
                </p>

                <!-- List -->
                <h5 class="mt-4 mb-3">List of Permissions</h5>

                @forelse ($permissions as $permission)
                    <div class="card mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $permission->name }}</strong>
                                @if($permission->description)
                                    <p class="mb-0 text-muted">{{ $permission->description }}</p>
                                @endif
                            </div>
                            <div class="d-flex">
                                <a href="{{ route('permissions.show', $permission) }}" class="btn btn-sm btn-outline-primary me-2">
                                    View
                                </a>
                                <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-sm btn-outline-secondary me-2">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('permissions.destroy', $permission) }}" 
                                    onsubmit="return confirm('Are you sure you want to delete this permission?')">
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
                    <p class="text-muted">No permissions recorded yet.</p>
                @endforelse

                @php
                    $currentPage = $permissions->currentPage();
                    $lastPage = $permissions->lastPage();
                    $linkCount = 5;
                    $half = floor($linkCount / 2);

                    $start = max(1, $currentPage - $half);
                    $end = min($lastPage, $start + $linkCount - 1);

                    if ($end - $start < $linkCount - 1) {
                        $start = max(1, $end - $linkCount + 1);
                    }
                @endphp

                @if ($permissions->hasPages())
                    <div class="mt-4">
                        <div class="d-flex justify-content-center">
                            <nav>
                                <ul class="pagination pagination-sm flex-wrap justify-content-center">
                                    <!-- Previous -->
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $permissions->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    <!-- First page & Ellipsis -->
                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $permissions->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    <!-- Page Numbers -->
                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $permissions->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    <!-- Ellipsis & Last Page -->
                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $permissions->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    <!-- Next -->
                                    <li class="page-item {{ !$permissions->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $permissions->nextPageUrl() ?? '#' }}">&raquo;</a>
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
