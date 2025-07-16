<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Add a New Role') }}
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

                        <form method="POST" action="{{ route('roles.store') }}" onsubmit="return collectPermissions()">
                            @csrf

                            <!-- Role Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Role Name</label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('name') }}"
                                    required
                                >
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Role Description -->
                            <div class="mb-4">
                                <label for="description" class="form-label">Description (optional)</label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="3"
                                    class="form-control"
                                >{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Permission Selection -->
                            <div class="mb-4">
                                <label class="form-label">Assign Permissions</label>
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Include</th>
                                            <th scope="col">Permission Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($permissions as $permission)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="permission-checkbox" value="{{ $permission->id }}">
                                                </td>
                                                <td>{{ $permission->name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <input type="hidden" name="permissionsInput" id="permissionsInput">
                            </div>

                            <!-- Submit Button -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Add') }}
                                </button>

                                <a href="javascript:history.back()" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function collectPermissions() {
            const selected = [];
            document.querySelectorAll('.permission-checkbox:checked').forEach(cb => {
                selected.push(cb.value);
            });
            document.getElementById('permissionsInput').value = selected.join(',');
            return true; // allow form submission
        }
    </script>
</x-app-layout>
