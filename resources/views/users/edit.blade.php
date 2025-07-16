<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Edit User') }}
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

                        <form method="POST" action="{{ route('users.update', $user->id) }}" id="userForm">
                            @csrf
                            @method('PATCH')

                            {{-- Name --}}
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Password --}}
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-muted small">(leave blank to keep current)</span></label>
                                <input id="password" name="password" type="password" class="form-control">
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Role Selection --}}
                            <div class="mb-4">
                                <label class="form-label">Assign Roles</label>
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="text-center">Include</th>
                                            <th scope="col">Role Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($roles as $role)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="role-checkbox" value="{{ $role->id }}"
                                                        {{ $user->roles->pluck('id')->contains($role->id) ? 'checked' : '' }}>
                                                </td>
                                                <td>{{ $role->name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <input type="hidden" name="rolesInput" id="rolesInput">
                            </div>

                            {{-- Buttons --}}
                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-primary">
                                    Update User
                                </button>

                                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('userForm');
            const rolesInput = document.getElementById('rolesInput');

            form.addEventListener('submit', function (e) {
                const selectedRoles = [];
                const checkboxes = document.querySelectorAll('.role-checkbox');

                checkboxes.forEach(function (checkbox) {
                    if (checkbox.checked) {
                        selectedRoles.push(checkbox.value);
                    }
                });

                rolesInput.value = selectedRoles.join(',');
            });

            // Initialize on load
            const initializeRolesInput = () => {
                const selectedRoles = [];
                document.querySelectorAll('.role-checkbox:checked').forEach(cb => selectedRoles.push(cb.value));
                document.getElementById('rolesInput').value = selectedRoles.join(',');
            };

            initializeRolesInput();
        });
    </script>
</x-app-layout>
