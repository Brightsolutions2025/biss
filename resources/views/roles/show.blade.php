<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('View Role') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

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

                <div class="card shadow-sm">
                    <div class="card-body">

                        <!-- Role Name -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Role Name</label>
                            <p class="form-control-plaintext">{{ $role->name }}</p>
                        </div>

                        <!-- Role Description -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <p class="form-control-plaintext">{{ $role->description ?? '—' }}</p>
                        </div>

                        <!-- Permissions -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Assigned Permissions</label>
                            @if($role->permissions->isNotEmpty())
                                <ul class="list-group">
                                    @foreach ($role->permissions as $permission)
                                        <li class="list-group-item">{{ $permission->name }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted">No permissions assigned.</p>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="d-flex gap-2">
                                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-primary">Edit</a>

                                <form method="POST" action="{{ route('roles.destroy', $role->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this role?')">
                                        Delete
                                    </button>
                                </form>
                            </div>

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
