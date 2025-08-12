<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Create New Ticket') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
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

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
                            @csrf

                            <!-- Subject -->
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="form-control" required>
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                            </div>

                            <!-- Ticket Type -->
                            <div class="mb-3">
                                <label for="ticket_type_id" class="form-label">Ticket Type <span class="text-danger">*</span></label>
                                <select name="ticket_type_id" id="ticket_type_id" class="form-select" required>
                                    <option value="">-- Select Type --</option>
                                    @foreach ($ticketTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('ticket_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Priority -->
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select name="priority" id="priority" class="form-select">
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </div>

                            <!-- Due Date -->
                            <div class="mb-3">
                                <label for="due_at" class="form-label">Due Date</label>
                                <input id="due_at" name="due_at" type="datetime-local" value="{{ old('due_at') }}" class="form-control">
                            </div>

                            <!-- Requires Approval -->
                            <div class="mb-3">
                                <label for="requires_approval" class="form-label">Requires Approval?</label>
                                <select name="requires_approval" id="requires_approval" class="form-select">
                                    <option value="0" {{ old('requires_approval') == '0' ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ old('requires_approval') == '1' ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>

                            <!-- Attachments -->
                            <div class="mb-4">
                                <label for="files" class="form-label">Supporting Documents (optional)</label>
                                <input
                                    id="files"
                                    name="files[]"
                                    type="file"
                                    class="form-control"
                                    multiple
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx"
                                >
                                @error('files.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Submit Ticket') }}
                                </button>

                                <a href="{{ route('tickets.index') }}" class="btn btn-secondary">
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
