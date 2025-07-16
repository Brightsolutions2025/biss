<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Users') }}
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

                <div class="mb-4">
                    <h5 class="mb-3">Search</h5>
                    <form method="GET" action="{{ route('users.index') }}" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name</label>
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
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                Search
                            </button>
                        </div>
                    </form>
                </div>

                <div class="mb-4">
                    <h5 class="mb-2">Add</h5>
                    <p>Want to add a new user? Click 
                        <a href="{{ route('users.create') }}" class="link-primary">here</a>!
                    </p>
                </div>

                <div class="mb-4">
                    <h5 class="mb-3">List</h5>

                    @forelse ($users as $user)
                        <div class="card mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $user->name }}</h6>
                                    <p class="mb-0 text-muted">{{ $user->email }}</p>
                                </div>
                                <div class="d-flex gap-3">
                                    <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No users found for this company.</p>
                    @endforelse

                    {{-- Pagination --}}
                    @php
                        $currentPage = $users->currentPage();
                        $lastPage = $users->lastPage();
                        $linkCount = 5;
                        $half = floor($linkCount / 2);

                        $start = max(1, $currentPage - $half);
                        $end = min($lastPage, $start + $linkCount - 1);

                        if ($end - $start < $linkCount - 1) {
                            $start = max(1, $end - $linkCount + 1);
                        }
                    @endphp

                    @if ($users->hasPages())
                        <div class="mt-4 d-flex justify-content-center">
                            <nav>
                                <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                    {{-- Previous --}}
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $users->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    {{-- First page & Ellipsis --}}
                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $users->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    {{-- Page Links --}}
                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $users->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    {{-- Ellipsis & Last Page --}}
                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $users->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    {{-- Next --}}
                                    <li class="page-item {{ !$users->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $users->nextPageUrl() ?? '#' }}">&raquo;</a>
                                    </li>

                                </ul>
                            </nav>
                        </div>
                    @endif

                </div>

            </div>
        </div>
    </div>
</x-app-layout>
