<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Company-User Assignment Details') }}
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

                        {{-- Company Name --}}
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ $companyUser->company->name }}">
                        </div>

                        {{-- User Name --}}
                        <div class="mb-3">
                            <label class="form-label">User Name</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ $companyUser->user->name }}">
                        </div>

                        {{-- User Email --}}
                        <div class="mb-3">
                            <label class="form-label">User Email</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ $companyUser->user->email }}">
                        </div>

                        {{-- Assigned At --}}
                        <div class="mb-4">
                            <label class="form-label">Assigned At</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ $companyUser->created_at->format('F j, Y g:i A') }}">
                        </div>

                        {{-- Action Buttons --}}
                        <div class="d-flex gap-3">
                            <a href="{{ route('company_users.index') }}" class="btn btn-secondary">
                                Back
                            </a>

                            <form method="POST" action="{{ route('company_users.destroy', [$companyUser->company_id, $companyUser->user_id]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Are you sure you want to remove this user from the company?')"
                                        class="btn btn-danger">
                                    Remove User
                                </button>
                            </form>
                            <a href="javascript:history.back()" class="btn btn-secondary">
                                Back
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
