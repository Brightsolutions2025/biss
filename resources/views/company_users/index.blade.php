<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Company Users') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <div class="card shadow-sm mb-4">
                    <div class="card-body">

                        {{-- Flash Messages --}}
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
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

                        {{-- Search --}}
                        <h5 class="mb-3">Search</h5>
                        <form method="GET" action="{{ route('company_users.index') }}" class="mb-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    name="name" 
                                    id="name"
                                    value="{{ request('name') }}">
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Search
                            </button>
                        </form>

                        {{-- Add --}}
                        <h5 class="mt-4">Add</h5>
                        <p>
                            Want to assign users to this company? Click 
                            <a href="{{ route('company_users.create') }}" class="text-decoration-underline">here</a>!
                        </p>

                        {{-- List --}}
                        <h5 class="mt-4 mb-3">List</h5>

                        @forelse ($companyUsers as $companyUser)
                            <div class="mb-3 border-bottom pb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $companyUser->user->name }}</strong><br>
                                        <small class="text-muted">
                                            Email: {{ $companyUser->user->email }} — Assigned: {{ $companyUser->created_at->format('Y-m-d') }}
                                        </small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('company_users.show', $companyUser) }}" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                        <a href="{{ route('company_users.edit', $companyUser) }}" class="btn btn-sm btn-outline-warning text-dark">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('company_users.destroy', $companyUser) }}" onsubmit="return confirm('Are you sure you want to remove this user from the company?')">
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
                            <p class="text-muted">No users assigned to this company yet.</p>
                        @endforelse

                        @php
                            $currentPage = $companyUsers->currentPage();
                            $lastPage = $companyUsers->lastPage();
                            $linkCount = 5;
                            $half = floor($linkCount / 2);

                            $start = max(1, $currentPage - $half);
                            $end = min($lastPage, $start + $linkCount - 1);

                            if ($end - $start < $linkCount - 1) {
                                $start = max(1, $end - $linkCount + 1);
                            }
                        @endphp

                        <!-- Pagination -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-center">
                                <nav>
                                    <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                        <!-- Previous -->
                                        <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                            <a class="page-link" href="{{ $companyUsers->previousPageUrl() ?? '#' }}">&laquo;</a>
                                        </li>

                                        <!-- First page & Ellipsis -->
                                        @if ($start > 1)
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $companyUsers->url(1) }}">1</a>
                                            </li>
                                            @if ($start > 2)
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            @endif
                                        @endif

                                        <!-- Page Numbers -->
                                        @for ($i = $start; $i <= $end; $i++)
                                            <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                                <a class="page-link" href="{{ $companyUsers->url($i) }}">{{ $i }}</a>
                                            </li>
                                        @endfor

                                        <!-- Ellipsis & Last Page -->
                                        @if ($end < $lastPage)
                                            @if ($end < $lastPage - 1)
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            @endif
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $companyUsers->url($lastPage) }}">{{ $lastPage }}</a>
                                            </li>
                                        @endif

                                        <!-- Next -->
                                        <li class="page-item {{ !$companyUsers->hasMorePages() ? 'disabled' : '' }}">
                                            <a class="page-link" href="{{ $companyUsers->nextPageUrl() ?? '#' }}">&raquo;</a>
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
