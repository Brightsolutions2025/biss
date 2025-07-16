<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Department Details') }}
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

                <!-- Department Info -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Department Name</label>
                    <input type="text" class="form-control bg-light" value="{{ $department->name }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea class="form-control bg-light" rows="3" disabled>{{ $department->description }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Department Head</label>
                    <input type="text" class="form-control bg-light" 
                        value="{{ $department->head?->name ?? 'None' }}" disabled>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('departments.edit', $department->id) }}" class="btn btn-primary">
                        Edit
                    </a>

                    <form method="POST" action="{{ route('departments.destroy', $department->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this department?')">
                            Delete
                        </button>
                    </form>

                    <a href="{{ route('departments.index') }}" class="btn btn-secondary ms-auto">
                        Back to List
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
