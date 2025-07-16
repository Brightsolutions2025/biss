<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('User Details') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

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

                        {{-- Name --}}
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" disabled class="form-control bg-light" value="{{ $user->name }}">
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" disabled class="form-control bg-light" value="{{ $user->email }}">
                        </div>

                        {{-- Email Verified --}}
                        <div class="mb-3">
                            <label class="form-label">Email Verified</label>
                            <input type="text" disabled class="form-control bg-light" value="{{ $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i') : 'Not verified' }}">
                        </div>

                        {{-- Roles --}}
                        <div class="mb-4">
                            <label class="form-label">Roles</label>
                            <div>
                                @forelse ($roles as $role)
                                    <span class="badge bg-primary me-1 mb-1">{{ $role->name }}</span>
                                @empty
                                    <p class="text-muted">No roles assigned.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex gap-3">
                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('users.destroy', $user->id) }}" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    Delete
                                </button>
                            </form>
                            <a href="javascript:history.back()" class="btn btn-secondary">
                                Back
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
