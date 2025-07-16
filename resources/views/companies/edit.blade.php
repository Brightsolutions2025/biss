<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Edit Company') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
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

                        <form method="POST" action="{{ route('companies.update', $company) }}">
                            @csrf
                            @method('PATCH')

                            <!-- Company Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Company Name</label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $company->name) }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Industry -->
                            <div class="mb-3">
                                <label for="industry" class="form-label">Industry</label>
                                <input
                                    id="industry"
                                    name="industry"
                                    type="text"
                                    class="form-control @error('industry') is-invalid @enderror"
                                    value="{{ old('industry', $company->industry) }}">
                                @error('industry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Address -->
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input
                                    id="address"
                                    name="address"
                                    type="text"
                                    class="form-control @error('address') is-invalid @enderror"
                                    value="{{ old('address', $company->address) }}">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="mb-4">
                                <label for="phone" class="form-label">Phone</label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="text"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $company->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Offset Validity Settings -->
                            <div class="mb-3">
                                <label for="offset_valid_after_days" class="form-label">
                                    Offset Valid After (days)
                                </label>
                                <input
                                    id="offset_valid_after_days"
                                    name="offset_valid_after_days"
                                    type="number"
                                    min="0"
                                    class="form-control @error('offset_valid_after_days') is-invalid @enderror"
                                    value="{{ old('offset_valid_after_days', $company->offset_valid_after_days) }}">
                                @error('offset_valid_after_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="offset_valid_before_days" class="form-label">
                                    Offset Filing Grace Period
                                </label>
                                <input
                                    id="offset_valid_before_days"
                                    name="offset_valid_before_days"
                                    type="number"
                                    min="0"
                                    class="form-control @error('offset_valid_before_days') is-invalid @enderror"
                                    value="{{ old('offset_valid_before_days', $company->offset_valid_before_days) }}">
                                @error('offset_valid_before_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Update') }}
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
