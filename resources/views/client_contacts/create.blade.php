{{-- resources/views/client_contacts/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Add a New Client Contact') }}
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

                        <form method="POST" action="{{ route('client_contacts.store') }}">
                            @csrf

                            <!-- Client -->
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client</label>
                                <select
                                    id="client_id"
                                    name="client_id"
                                    class="form-control"
                                    required
                                >
                                    <option value="">-- Select Client --</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Contact Name</label>
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

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email (optional)</label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    class="form-control"
                                    value="{{ old('email') }}"
                                >
                                @error('email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone (optional)</label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('phone') }}"
                                >
                                @error('phone')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Position -->
                            <div class="mb-3">
                                <label for="position" class="form-label">Position (optional)</label>
                                <input
                                    id="position"
                                    name="position"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('position') }}"
                                >
                                @error('position')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- LinkedIn URL -->
                            <div class="mb-3">
                                <label for="linkedin_url" class="form-label">LinkedIn URL (optional)</label>
                                <input
                                    id="linkedin_url"
                                    name="linkedin_url"
                                    type="url"
                                    class="form-control"
                                    value="{{ old('linkedin_url') }}"
                                >
                                @error('linkedin_url')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Is Primary -->
                            <div class="mb-4 form-check">
                                <input
                                    id="is_primary"
                                    name="is_primary"
                                    type="checkbox"
                                    class="form-check-input"
                                    value="1"
                                    {{ old('is_primary') ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="is_primary">Primary Contact</label>
                                @error('is_primary')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Add Contact') }}
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
</x-app-layout>
