{{-- resources/views/client_contacts/show.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Contact Details for') }} {{ $clientContact->client->name }}
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

                <!-- Contact Info -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name</label>
                    <input type="text" class="form-control bg-light" value="{{ $clientContact->name }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="text" class="form-control bg-light" value="{{ $clientContact->email ?? '—' }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" class="form-control bg-light" value="{{ $clientContact->phone ?? '—' }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Position</label>
                    <input type="text" class="form-control bg-light" value="{{ $clientContact->position ?? '—' }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">LinkedIn URL</label>
                    <input type="text" class="form-control bg-light" value="{{ $clientContact->linkedin_url ?? '—' }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Primary Contact</label>
                    <input type="text" class="form-control bg-light" value="{{ $clientContact->is_primary ? 'Yes' : 'No' }}" disabled>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('client_contacts.edit', $clientContact) }}" class="btn btn-primary">
                        Edit
                    </a>

                    <form method="POST" action="{{ route('client_contacts.destroy', $clientContact) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this contact?')">
                            Delete
                        </button>
                    </form>

                    <a href="javascript:history.back()" class="btn btn-secondary">
                        {{ __('Cancel') }}
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
