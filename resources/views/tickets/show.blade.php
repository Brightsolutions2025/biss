<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Ticket Details') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

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

                        @php
                            $fields = [
                                'Ticket Number'    => $ticket->ticket_number,
                                'Company'          => $ticket->company->name ?? 'N/A',
                                'Department'       => $ticket->department->name ?? '—',
                                'Team'             => $ticket->team->name ?? '—',
                                'Ticket Type'      => $ticket->ticketType->name ?? 'N/A',
                                'Subject'          => $ticket->subject,
                                'Description'      => $ticket->description ?? '—',
                                'Due Date'         => $ticket->due_at ? $ticket->due_at->format('Y-m-d H:i') : '—',
                                'Resolved At'      => $ticket->resolved_at ? $ticket->resolved_at->format('Y-m-d H:i') : '—',
                                'Priority'         => ucfirst($ticket->priority),
                                'Status'           => ucfirst(str_replace('_', ' ', $ticket->status)),
                                'Created By'       => $ticket->createdBy->name ?? '—',
                                'Assigned To'      => $ticket->assignedTo->name ?? '—',
                                'Requires Approval'=> $ticket->requires_approval ? 'Yes' : 'No',
                                'Approved By'      => $ticket->approvedBy->name ?? '—',
                                'Approved At'      => $ticket->approved_at ? $ticket->approved_at->format('Y-m-d H:i') : '—',
                                'Created At'       => $ticket->created_at->format('Y-m-d H:i'),
                                'Updated At'       => $ticket->updated_at->format('Y-m-d H:i'),
                            ];
                        @endphp

                        @foreach ($fields as $label => $value)
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ $label }}</label>
                                <input type="text" class="form-control" value="{{ $value }}" disabled>
                            </div>
                        @endforeach

                        @if ($ticket->files->count())
                            <div class="mb-4">
                                <label class="form-label">Attached Files</label>
                                <ul class="list-unstyled">
                                    @foreach ($ticket->files as $file)
                                        <li class="mb-2">
                                            <a href="{{ route('files.download', $file->id) }}" target="_blank" class="d-inline-flex align-items-center gap-2">
                                                @php
                                                    $extension = pathinfo($file->file_name, PATHINFO_EXTENSION);
                                                @endphp

                                                @switch($extension)
                                                    @case('pdf')
                                                        <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                        @break
                                                    @case('jpg')
                                                    @case('jpeg')
                                                    @case('png')
                                                        <i class="bi bi-image text-primary"></i>
                                                        @break
                                                    @case('doc')
                                                    @case('docx')
                                                        <i class="bi bi-file-earmark-word text-primary"></i>
                                                        @break
                                                    @case('xlsx')
                                                        <i class="bi bi-file-earmark-excel text-success"></i>
                                                        @break
                                                    @default
                                                        <i class="bi bi-paperclip"></i>
                                                @endswitch

                                                {{ $file->file_name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif


                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 mt-4">
                            <a href="{{ route('tickets.edit', $ticket->id) }}" class="btn btn-primary">Edit</a>

                            <form method="POST" action="{{ route('tickets.destroy', $ticket->id) }}"
                                  onsubmit="return confirm('Are you sure you want to delete this ticket?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>

                            <a href="{{ route('tickets.index') }}" class="btn btn-secondary">
                                Back
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
