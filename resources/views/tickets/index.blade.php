@extends("layouts.master")
@section('title', 'Customers')
@section('content')
<div class="body d-flex py-lg-3 py-md-2">
    <div class="container-xxl">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card border-0 mb-4 no-bg">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Tickets</h3>
                       
                    </div>
                </div>
            </div>
        </div><!-- Row End -->
        <div class="card mt-3">
            <div class="card-header">
                <h4 class="card-title">Ticket List</h3>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped datatable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $key => $ticket)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $ticket->name }}</td>
                            <td>{{ $ticket->email }}</td>
                            <td>{{ $ticket->phone }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td>{{ $ticket->message }}</td>
                            <td>{{ $ticket->status }}</td>
                            <td class="text-center">
                                @can("Change Ticket Status")
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" onclick="deleteCustomerModal('{{$ticket->id}}')">
                                    <i class="icofont-pencil text-warning fs-4"></i>
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div> <!-- ROW END -->
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