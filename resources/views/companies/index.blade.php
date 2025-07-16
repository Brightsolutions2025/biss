<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Companies') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-body">

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

                        <!-- 🔍 Search -->
                        <h5 class="mb-3">Search</h5>
                        <form method="GET" action="{{ route('companies.index') }}" class="mb-4">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    id="name"
                                    name="name"
                                    required
                                    value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Search
                            </button>
                        </form>

                        <!-- ➕ Add New -->
                        <h5 class="mt-5">Add</h5>
                        <p class="mb-4">
                            Want to add a new company? Click
                            <a href="{{ route('companies.create') }}">here</a>!
                        </p>

                        <!-- 📋 List -->
                        <h5>List</h5>
                        @forelse ($companies as $company)
                            <div class="border p-3 mb-3 rounded d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $company->name }}</strong>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ $company->path() }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form method="POST" action="{{ route('companies.destroy', $company->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this company?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No companies recorded yet.</p>
                        @endforelse

                        <!-- 📄 Pagination -->
                        @php
                            $currentPage = $companies->currentPage();
                            $lastPage = $companies->lastPage();
                            $linkCount = 5;
                            $half = floor($linkCount / 2);

                            $start = max(1, $currentPage - $half);
                            $end = min($lastPage, $start + $linkCount - 1);

                            if ($end - $start < $linkCount - 1) {
                                $start = max(1, $end - $linkCount + 1);
                            }
                        @endphp

                        @if ($lastPage > 1)
                            <div class="mt-4 d-flex justify-content-center">
                                <nav>
                                    <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                        <!-- Previous -->
                                        <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                            <a class="page-link" href="{{ $companies->previousPageUrl() ?? '#' }}">&laquo;</a>
                                        </li>

                                        <!-- First Page & Ellipsis -->
                                        @if ($start > 1)
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $companies->url(1) }}">1</a>
                                            </li>
                                            @if ($start > 2)
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            @endif
                                        @endif

                                        <!-- Page Numbers -->
                                        @for ($i = $start; $i <= $end; $i++)
                                            <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                                <a class="page-link" href="{{ $companies->url($i) }}">{{ $i }}</a>
                                            </li>
                                        @endfor

                                        <!-- Ellipsis & Last Page -->
                                        @if ($end < $lastPage)
                                            @if ($end < $lastPage - 1)
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            @endif
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $companies->url($lastPage) }}">{{ $lastPage }}</a>
                                            </li>
                                        @endif

                                        <!-- Next -->
                                        <li class="page-item {{ !$companies->hasMorePages() ? 'disabled' : '' }}">
                                            <a class="page-link" href="{{ $companies->nextPageUrl() ?? '#' }}">&raquo;</a>
                                        </li>

                                    </ul>
                                </nav>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
