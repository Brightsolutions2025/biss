<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Edit Contact for Client:') }} {{ $clientContact->client->name ?? 'Unknown Client' }}
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

                        <form method="POST" action="{{ route('client_contacts.update', $clientContact) }}">
                            @csrf
                            @method('PUT')

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('name', $clientContact->name) }}"
                                    required
                                >
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    class="form-control"
                                    value="{{ old('email', $clientContact->email) }}"
                                >
                                @error('email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('phone', $clientContact->phone) }}"
                                >
                                @error('phone')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Position -->
                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input
                                    id="position"
                                    name="position"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('position', $clientContact->position) }}"
                                >
                                @error('position')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- LinkedIn URL -->
                            <div class="mb-3">
                                <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                                <input
                                    id="linkedin_url"
                                    name="linkedin_url"
                                    type="url"
                                    class="form-control"
                                    value="{{ old('linkedin_url', $clientContact->linkedin_url) }}"
                                >
                                @error('linkedin_url')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Is Primary -->
                            <div class="mb-4 form-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="is_primary"
                                    name="is_primary"
                                    value="1"
                                    {{ old('is_primary', $clientContact->is_primary) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="is_primary">Primary Contact</label>
                                <input type="hidden" name="is_primary" value="0">
                                @error('is_primary')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Update Contact') }}
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
