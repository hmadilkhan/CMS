@extends("layouts.master")
@section('title', 'Tickets')
@section('content')
@include('operations.partials.index-styles')
@php
    $activeTicketTab = request('tab') === 'complete' || request()->has('completed_page') ? 'complete' : 'pending';
@endphp
<style>
    .ticket-table {
        table-layout: fixed !important;
        min-width: 1120px;
        width: 100% !important;
        margin-bottom: 0;
    }

    .ticket-table-wrap {
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .ticket-table th,
    .ticket-table td {
        vertical-align: top;
        white-space: normal !important;
        overflow: hidden;
        box-sizing: border-box;
    }

    .ticket-table .ticket-no-column {
        width: 58px;
    }

    .ticket-table .ticket-name-column {
        width: 135px;
    }

    .ticket-table .ticket-email-column {
        width: 190px;
    }

    .ticket-table .ticket-phone-column {
        width: 120px;
    }

    .ticket-table .ticket-address-column {
        width: 240px;
    }

    .ticket-table .ticket-message-column {
        width: 220px;
    }

    .ticket-table .ticket-date-column {
        width: 105px;
    }

    .ticket-table .ticket-status-column {
        width: 95px;
    }

    .ticket-table .ticket-actions-column {
        width: 82px;
    }

    .ticket-table .ticket-address,
    .ticket-table .ticket-message {
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: break-word;
        line-height: 1.35;
    }

    .ticket-table .ticket-cell-content {
        display: block;
        width: 100%;
        max-height: 4.2rem;
        overflow-x: hidden;
        overflow-y: auto;
        white-space: normal !important;
    }
</style>
<div class="body d-flex py-lg-3 py-md-2">
    <div class="container-xxl">

        <div class="operation-page-header">
            <div>
                <h1 class="operation-page-title"><i class="icofont-ticket me-2"></i>Tickets</h1>
                <p class="operation-page-subtitle">Review pending and completed website tickets.</p>
            </div>
        </div>
        <div class="operation-card mt-2">
            <div class="card-body">
                <ul class="nav nav-tabs px-0 border-bottom-0" role="tablist">
                    <li class="nav-item"><a class="nav-link {{ $activeTicketTab === 'pending' ? 'active' : '' }}" data-bs-toggle="tab" href="#pending" role="tab">Pending</a></li>
                    <li class="nav-item"><a class="nav-link {{ $activeTicketTab === 'complete' ? 'active' : '' }}" data-bs-toggle="tab" href="#complete" role="tab">Completed</a></li>
                </ul>
            </div>
        </div>
        <div class="tab-content">
            <div class="tab-pane fade {{ $activeTicketTab === 'pending' ? 'show active' : '' }}" id="pending" role="tabpanel">
                <div class="operation-card mt-3">
                    <div class="card-body">
                        <div class="ticket-table-wrap">
                        <table id="pendingTicketsTable" class="table table-hover operation-table ticket-table">
                            <thead>
                                <tr>
                                    <th class="ticket-no-column">No.</th>
                                    <th class="ticket-name-column">Name</th>
                                    <th class="ticket-email-column">Email</th>
                                    <th class="ticket-phone-column">Phone</th>
                                    <th class="ticket-address-column">Address</th>
                                    <th class="ticket-message-column">Message</th>
                                    <th class="ticket-date-column">Date</th>
                                    <th class="ticket-status-column">Status</th>
                                    <th class="ticket-actions-column">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pendingTickets as $ticket)
                                <tr>
                                    <td class="ticket-no-column">{{ $pendingTickets->firstItem() + $loop->index }}</td>
                                    <td class="ticket-name-column">{{ $ticket->name }}</td>
                                    <td class="ticket-email-column">{{ $ticket->email }}</td>
                                    <td class="ticket-phone-column">{{ $ticket->phone }}</td>
                                    <td class="ticket-address">
                                        <span class="ticket-cell-content">{{ $ticket->address }}</span>
                                    </td>
                                    <td class="ticket-message">
                                        <span class="ticket-cell-content">{{ $ticket->message }}</span>
                                    </td>
                                    <td class="ticket-date-column">{{ $ticket->created_at ? $ticket->created_at->format('M d, Y') : '-' }}</td>
                                    <td class="ticket-status-column">{{ $ticket->status }}</td>
                                    <td class="ticket-actions-column text-center">
                                        @can("Change Ticket Status")
                                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" onclick="deleteCustomerModal('{{$ticket->id}}')">
                                            <i class="icofont-pencil text-warning fs-4"></i>
                                        </a>
                                        @endcan
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">No pending tickets found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                        <div class="mt-3">
                            {{ $pendingTickets->links() }}
                        </div>
                    </div>
                </div> <!-- ROW END -->
            </div>
            <div class="tab-pane fade {{ $activeTicketTab === 'complete' ? 'show active' : '' }}" id="complete" role="tabpanel">
                <div class="operation-card mt-3">
                    <div class="card-body">
                        <div class="ticket-table-wrap">
                        <table id="completedTicketsTable" class="table table-hover operation-table ticket-table">
                            <thead>
                                <tr>
                                    <th class="ticket-no-column">No.</th>
                                    <th class="ticket-name-column">Name</th>
                                    <th class="ticket-email-column">Email</th>
                                    <th class="ticket-phone-column">Phone</th>
                                    <th class="ticket-address-column">Address</th>
                                    <th class="ticket-message-column">Message</th>
                                    <th class="ticket-date-column">Date</th>
                                    <th class="ticket-status-column">Status</th>
                                    <th class="ticket-actions-column">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($completedTickets as $ticket)
                                <tr>
                                    <td class="ticket-no-column">{{ $completedTickets->firstItem() + $loop->index }}</td>
                                    <td class="ticket-name-column">{{ $ticket->name }}</td>
                                    <td class="ticket-email-column">{{ $ticket->email }}</td>
                                    <td class="ticket-phone-column">{{ $ticket->phone }}</td>
                                    <td class="ticket-address">
                                        <span class="ticket-cell-content">{{ $ticket->address }}</span>
                                    </td>
                                    <td class="ticket-message">
                                        <span class="ticket-cell-content">{{ $ticket->message }}</span>
                                    </td>
                                    <td class="ticket-date-column">{{ $ticket->created_at ? $ticket->created_at->format('M d, Y') : '-' }}</td>
                                    <td class="ticket-status-column">{{ $ticket->status }}</td>
                                    <td class="ticket-actions-column text-center">
                                        @can("Change Ticket Status")
                                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" onclick="deleteCustomerModal('{{$ticket->id}}')">
                                            <i class="icofont-pencil text-warning fs-4"></i>
                                        </a>
                                        @endcan
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">No completed tickets found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                        <div class="mt-3">
                            {{ $completedTickets->links() }}
                        </div>
                    </div>
                </div> <!-- ROW END -->
            </div>
        </div>

    </div>
</div>
<!-- Modal  Delete Folder/ File-->
<div class="modal fade" id="deleteproject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <input type="hidden" id="deleteId" />
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title  fw-bold" id="deleteprojectLabel"> Change Ticket status?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body justify-content-center flex-column d-flex">
                <i class="icofont-ticket text-primary display-2 text-center mt-2"></i>
                <p class="mt-4 fs-5 text-center">Ticket status will be change to done?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary color-fff" onclick="deleteCustomer()">Change</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section("scripts")
<script>
    function deleteCustomerModal(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show")
    }

    function deleteCustomer() {
        $.ajax({
            method: "POST",
            url: "{{ route('change.ticket.status') }}",
            data: {
                _token: "{{csrf_token()}}",
                id: $("#deleteId").val()
            },
            success: function(response) {
                if (response.status == 200) {
                    location.reload();
                }
            }
        });
    }
</script>
@endsection
