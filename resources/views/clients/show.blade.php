{{-- resources/views/clients/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Client Details') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

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

                <!-- Client Info -->

                <div class="mb-3">
                    <label class="form-label fw-semibold">Client Name</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->name }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Contact Person</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->contact_person }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control bg-light" value="{{ $client->email }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->phone }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Address</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->address }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Billing Address</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->billing_address }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Industry</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->industry }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">TIN</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->tin }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Category</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->category }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Client Type</label>
                    <input type="text" class="form-control bg-light" value="{{ ucfirst($client->client_type) }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Website</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->website }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea class="form-control bg-light" rows="3" disabled>{{ $client->notes }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Rating</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->rating }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Active</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->is_active ? 'Yes' : 'No' }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Payment Terms</label>
                    <input type="text" class="form-control bg-light" value="{{ $client->payment_terms }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Credit Limit</label>
                    <input type="text" class="form-control bg-light" value="{{ number_format($client->credit_limit, 2) }}" disabled>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-primary">
                        Edit
                    </a>

                    <form method="POST" action="{{ route('clients.destroy', $client->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this client?')">
                            Delete
                        </button>
                    </form>

                    <a href="{{ route('clients.index') }}" class="btn btn-secondary ms-auto">
                        Back to List
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
