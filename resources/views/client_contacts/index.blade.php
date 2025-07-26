{{-- resources/views/client_contacts/index.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Client Contacts') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

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

                <!-- Search, Filter & Sort -->
                <h5 class="mb-3">Search</h5>
                <form method="GET" action="{{ route('client_contacts.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search by Name or Email</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control" placeholder="e.g. John or john@example.com">
                        </div>
                        <div class="col-md-3">
                            <label for="is_primary" class="form-label">Primary Contact</label>
                            <select name="is_primary" id="is_primary" class="form-select">
                                <option value="">All</option>
                                <option value="1" {{ request('is_primary') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ request('is_primary') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select name="sort_by" id="sort_by" class="form-select">
                                <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Name</option>
                                <option value="email" {{ request('sort_by') == 'email' ? 'selected' : '' }}>Email</option>
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
                <h5 class="mb-3">Add Contact</h5>
                <p>
                    Want to add a new contact for this client?
                    <a href="{{ route('client_contacts.create') }}">Click here</a>!
                </p>

                <!-- Contact List -->
                <h5 class="mt-4">List of Contacts</h5>

                @forelse ($contacts as $contact)
                    <div class="card mb-3">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <div class="mb-2 mb-md-0">
                                <strong>{{ $contact->name }}</strong>
                                <div class="text-muted small">
                                    {{ $contact->position ?? 'N/A' }}<br>
                                    {{ $contact->email ?? 'No email' }} | {{ $contact->phone ?? 'No phone' }}
                                    @if ($contact->is_primary)
                                        <span class="badge bg-primary ms-2">Primary</span>
                                    @endif
                                </div>
                                @if ($contact->linkedin_url)
                                    <a href="{{ $contact->linkedin_url }}" class="text-muted small" target="_blank">LinkedIn</a>
                                @endif
                            </div>
                            <div class="d-flex align-items-center">
                                <a href="{{ route('client_contacts.show', $contact) }}" class="btn btn-sm btn-outline-primary me-2">View</a>
                                <a href="{{ route('client_contacts.edit', $contact) }}" class="btn btn-sm btn-outline-secondary me-2">Edit</a>
                                <form method="POST" action="{{ route('client_contacts.destroy', $contact) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this contact?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No contacts recorded yet.</p>
                @endforelse

                {{-- Pagination --}}
                @php
                    $currentPage = $contacts->currentPage();
                    $lastPage = $contacts->lastPage();
                    $linkCount = 5;
                    $half = floor($linkCount / 2);

                    $start = max(1, $currentPage - $half);
                    $end = min($lastPage, $start + $linkCount - 1);

                    if ($end - $start < $linkCount - 1) {
                        $start = max(1, $end - $linkCount + 1);
                    }
                @endphp

                @if ($contacts->hasPages())
                    <div class="mt-4">
                        <div class="d-flex justify-content-center">
                            <nav>
                                <ul class="pagination pagination-sm flex-wrap justify-content-center">
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $contacts->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $contacts->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $contacts->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $contacts->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    <li class="page-item {{ !$contacts->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $contacts->nextPageUrl() ?? '#' }}">&raquo;</a>
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
