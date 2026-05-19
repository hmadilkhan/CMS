@extends("layouts.master")
@section('title', 'Customers')
@section('content')
@include('operations.partials.index-styles')
<div class="body d-flex py-lg-3 py-md-2">
    <div class="container-xxl">
        <div class="operation-page-header">
            <div>
                <h1 class="operation-page-title">Customer</h1>
                <p class="operation-page-subtitle">View and manage customer records.</p>
            </div>
            @can("Create Customer")
                <a href="{{route('customers.create')}}" class="btn btn-dark me-1 mt-1 w-sm-100" id="openemployee"><i class="icofont-plus-circle me-2 fs-6"></i>Add Customer</a>
            @endcan
        </div>
        <div class="operation-card mt-3">
            <div class="card-body">
                <table id="example1" class="table table-hover operation-table datatable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Full Name</th>
                            <th>Last Name</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Street</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $key => $customer)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $customer->first_name }}</td>
                            <td>{{ $customer->last_name }}</td>
                            <td>{{ $customer->city }}</td>
                            <td>{{ $customer->state }}</td>
                            <td>{{ $customer->street }}</td>
                            <td class="text-center">
                                @can("Edit Customer")
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{route('customers.edit',$customer->id)}}">
                                    <i class="icofont-pencil text-warning fs-4"></i></a>
                                @endcan
                                @can("Delete Customer")
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteCustomerModal('{{ $customer->id }}')">
                                    <i class="icofont-trash text-danger fs-4"></i></a>
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
                <h5 class="modal-title  fw-bold" id="deleteprojectLabel"> Delete item Permanently?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body justify-content-center flex-column d-flex">
                <i class="icofont-ui-delete text-danger display-2 text-center mt-2"></i>
                <p class="mt-4 fs-5 text-center">You can only delete this item Permanently</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger color-fff" onclick="deleteCustomer()">Delete</button>
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
            url: "{{ route('delete.customer') }}",
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
