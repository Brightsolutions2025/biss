<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Edit Department') }}
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

                        <form method="POST" action="{{ route('departments.update', $department->id) }}">
                            @csrf
                            @method('PATCH')

                            <!-- Department Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Department Name</label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name', $department->name) }}"
                                    required
                                    class="form-control"
                                >
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="4"
                                    class="form-control"
                                >{{ old('description', $department->description) }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="head_id">Department Head</label>
                                <select name="head_id" id="head_id" class="form-control">
                                    <option value="">-- None --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('head_id', $department->head_id ?? '') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <br>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('departments.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
