<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Add a New Company') }}
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

                        <form method="POST" action="{{ route('companies.store') }}">
                            @csrf

                            {{-- Company Name --}}
                            <div class="mb-3">
                                <label for="name" class="form-label">Company Name</label>
                                <input type="text" name="name" id="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Industry --}}
                            <div class="mb-3">
                                <label for="industry" class="form-label">Industry</label>
                                <input type="text" name="industry" id="industry"
                                       class="form-control @error('industry') is-invalid @enderror"
                                       value="{{ old('industry') }}">
                                @error('industry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Address --}}
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" name="address" id="address"
                                       class="form-control @error('address') is-invalid @enderror"
                                       value="{{ old('address') }}">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div class="mb-4">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" name="phone" id="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Offset Valid After Days --}}
                            <div class="mb-3">
                                <label for="offset_valid_after_days" class="form-label">Offset Valid After Days</label>
                                <input type="number" name="offset_valid_after_days" id="offset_valid_after_days"
                                    class="form-control @error('offset_valid_after_days') is-invalid @enderror"
                                    value="{{ old('offset_valid_after_days', 90) }}">
                                @error('offset_valid_after_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Offset Filing Grace Period --}}
                            <div class="mb-4">
                                <label for="offset_valid_before_days" class="form-label">Offset Filing Grace Period</label>
                                <input type="number" name="offset_valid_before_days" id="offset_valid_before_days"
                                    class="form-control @error('offset_valid_before_days') is-invalid @enderror"
                                    value="{{ old('offset_valid_before_days', 26) }}">
                                @error('offset_valid_before_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Action Buttons --}}
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
</x-app-layout>
