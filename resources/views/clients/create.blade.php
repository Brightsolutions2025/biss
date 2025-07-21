{{-- resources/views/clients/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Add a New Client') }}
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

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST" action="{{ route('clients.store') }}">
                            @csrf

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Client Name</label>
                                <input id="name" name="name" type="text" class="form-control" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Contact Person -->
                            <div class="mb-3">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input id="contact_person" name="contact_person" type="text" class="form-control" value="{{ old('contact_person') }}">
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}">
                            </div>

                            <!-- Phone -->
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input id="phone" name="phone" type="text" class="form-control" value="{{ old('phone') }}">
                            </div>

                            <!-- Address -->
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input id="address" name="address" type="text" class="form-control" value="{{ old('address') }}">
                            </div>

                            <!-- Billing Address -->
                            <div class="mb-3">
                                <label for="billing_address" class="form-label">Billing Address</label>
                                <input id="billing_address" name="billing_address" type="text" class="form-control" value="{{ old('billing_address') }}">
                            </div>

                            <!-- Industry -->
                            <div class="mb-3">
                                <label for="industry" class="form-label">Industry</label>
                                <input id="industry" name="industry" type="text" class="form-control" value="{{ old('industry') }}">
                            </div>

                            <!-- TIN -->
                            <div class="mb-3">
                                <label for="tin" class="form-label">TIN</label>
                                <input id="tin" name="tin" type="text" class="form-control" value="{{ old('tin') }}">
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <input id="category" name="category" type="text" class="form-control" value="{{ old('category') }}">
                            </div>

                            <!-- Client Type -->
                            <div class="mb-3">
                                <label for="client_type" class="form-label">Client Type</label>
                                <select id="client_type" name="client_type" class="form-control">
                                    <option value="">-- Select --</option>
                                    <option value="corporate" {{ old('client_type') == 'corporate' ? 'selected' : '' }}>Corporate</option>
                                    <option value="government" {{ old('client_type') == 'government' ? 'selected' : '' }}>Government</option>
                                    <option value="individual" {{ old('client_type') == 'individual' ? 'selected' : '' }}>Individual</option>
                                </select>
                            </div>

                            <!-- Website -->
                            <div class="mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input id="website" name="website" type="text" class="form-control" value="{{ old('website') }}">
                            </div>

                            <!-- Notes -->
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            </div>

                            <!-- Rating -->
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating (1-5)</label>
                                <input id="rating" name="rating" type="number" min="1" max="5" class="form-control" value="{{ old('rating') }}">
                            </div>

                            <!-- Is Active -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>

                            <!-- Payment Terms -->
                            <div class="mb-3">
                                <label for="payment_terms" class="form-label">Payment Terms</label>
                                <input id="payment_terms" name="payment_terms" type="text" class="form-control" value="{{ old('payment_terms') }}">
                            </div>

                            <!-- Credit Limit -->
                            <div class="mb-4">
                                <label for="credit_limit" class="form-label">Credit Limit</label>
                                <input id="credit_limit" name="credit_limit" type="number" step="0.01" class="form-control" value="{{ old('credit_limit') }}">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Add') }}
                                </button>

                                <a href="{{ route('clients.index') }}" class="btn btn-secondary">
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
