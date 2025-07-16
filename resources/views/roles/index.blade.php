<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Roles') }}
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
                <form method="GET" action="{{ route('roles.index') }}" class="mb-4">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Role Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name"
                            class="form-control"
                            value="{{ request('name') }}">
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>

                <!-- Add New Role -->
                <h5 class="mb-2">Add</h5>
                <p class="mb-4">
                    Want to add a new role? 
                    <a href="{{ route('roles.create') }}" class="text-decoration-underline text-primary">Click here</a>!
                </p>

                <!-- Roles List -->
                <h5 class="mb-3">List of Roles</h5>

                @forelse ($roles as $role)
                    <div class="card mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $role->name }}</h6>
                                <small class="text-muted">{{ $role->description }}</small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('roles.show', $role) }}" class="btn btn-sm btn-outline-primary">View</a>
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-success">Edit</a>
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Are you sure you want to delete this role?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No roles recorded yet.</p>
                @endforelse

                <!-- Custom Pagination -->
                @if ($roles->lastPage() > 1)
                    @php
                        $currentPage = $roles->currentPage();
                        $lastPage = $roles->lastPage();
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
                                        <a class="page-link" href="{{ $roles->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    <!-- First page & Ellipsis -->
                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $roles->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    <!-- Page Numbers -->
                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $roles->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    <!-- Ellipsis & Last Page -->
                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $roles->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    <!-- Next -->
                                    <li class="page-item {{ !$roles->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $roles->nextPageUrl() ?? '#' }}">&raquo;</a>
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
