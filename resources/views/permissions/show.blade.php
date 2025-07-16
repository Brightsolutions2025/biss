<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Permission Details') }}
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

                        {{-- Permission Name --}}
                        <div class="mb-3">
                            <label class="form-label">Permission Name</label>
                            <input type="text" class="form-control" value="{{ $permission->name }}" disabled>
                        </div>

                        {{-- Permission Description --}}
                        <div class="mb-4">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" disabled>{{ $permission->description }}</textarea>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex gap-2">
                            <a href="{{ route('permissions.edit', $permission->id) }}" class="btn btn-primary">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('permissions.destroy', $permission->id) }}"
                                  onsubmit="return confirm('Are you sure you want to delete this permission?')">
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
