@extends("layouts.master")
@section('title', 'Intake Forms')
@section('content')
<style>
    body {
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    }
    .premium-card {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        border-radius: 25px;
        box-shadow: 0 25px 70px rgba(45, 55, 72, 0.4);
        border: none;
        color: white;
        position: relative;
        overflow: hidden;
    }
    .premium-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #4a5568, #718096, #4a5568);
    }
    .premium-table-card {
        border-radius: 20px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
        border: none;
        overflow: hidden;
        background: white;
    }
    .premium-btn {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        border: none;
        color: white;
        border-radius: 12px;
        padding: 14px 35px;
        font-weight: 700;
        letter-spacing: 0.5px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 8px 20px rgba(45, 55, 72, 0.5);
        position: relative;
        overflow: hidden;
    }
    .premium-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    .premium-btn:hover::before {
        width: 300px;
        height: 300px;
    }
    .premium-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(45, 55, 72, 0.7);
    }
    .premium-action-btn {
        display: inline-block;
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .premium-action-btn:hover {
        transform: scale(1.3) rotate(5deg);
        background: rgba(45, 55, 72, 0.05);
    }
    .datatable {
        border-collapse: separate;
        border-spacing: 0 8px;
    }
    .datatable thead th {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        color: white;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 12px;
        padding: 18px 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(45, 55, 72, 0.3);
    }
    .datatable tbody tr {
        background: white;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    .datatable tbody tr:hover {
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }
    .datatable tbody td {
        padding: 18px 15px;
        vertical-align: middle;
        border: none;
    }
    .datatable tbody tr td:first-child {
        border-radius: 10px 0 0 10px;
    }
    .datatable tbody tr td:last-child {
        border-radius: 0 10px 10px 0;
    }
    .badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    }
    .card-header {
        border-bottom: 3px solid #e2e8f0;
        padding: 20px 25px;
    }
    .card-title {
        font-weight: 700;
        font-size: 20px;
        letter-spacing: 0.5px;
    }
</style>

<div class="body d-flex py-lg-3 py-md-2">
    <div class="container-xxl">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="premium-card mb-4">
                    <div class="card-header py-4 px-4 d-sm-flex align-items-center justify-content-between border-0">
                        <h3 class="fw-bold flex-fill mb-0 mt-sm-0">
                            <i class="icofont-ui-note me-2"></i>Intake Forms
                        </h3>
                        @can("Create Intakeform")
                        <a href="{{route('intake-form.create')}}" class="btn btn-light premium-btn mt-1 w-sm-100">
                            <i class="icofont-plus-circle me-2 fs-6"></i>New Intake Form
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="premium-table-card mt-4">
            <div class="card-header bg-white">
                <h4 class="card-title mb-0" style="color: #2d3748;">
                    <i class="icofont-list me-2"></i>Intake Form List
                </h4>
            </div>
            <div class="card-body p-4">
                <table id="example1" class="table table-hover datatable responsive nowrap w-100">
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
                            <td><span class="badge bg-primary">{{ ++$key }}</span></td>
                            <td><strong>{{ $customer->first_name }}</strong></td>
                            <td>{{ $customer->last_name }}</td>
                            <td>{{ $customer->city }}</td>
                            <td>{{ $customer->state }}</td>
                            <td>{{ $customer->street }}</td>
                            <td class="text-center">
                                @can("Edit Intakeform")
                                <a class="premium-action-btn" data-toggle="tooltip" title="Edit" href="{{route('intake-form.edit',$customer->id)}}">
                                    <i class="icofont-pencil text-warning fs-4"></i>
                                </a>
                                @endcan
                                {{-- @can("Delete Customer")
                                <a class="premium-action-btn ml-2" data-toggle="tooltip" title="Delete" onclick="deleteCustomerModal('{{ $customer->id }}')">
                                    <i class="icofont-trash text-danger fs-4"></i>
                                </a>
                                @endcan --}}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteproject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <input type="hidden" id="deleteId" />
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); color: white; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title fw-bold">Delete item Permanently?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body justify-content-center flex-column d-flex">
                <i class="icofont-ui-delete text-danger display-2 text-center mt-2"></i>
                <p class="mt-4 fs-5 text-center">You can only delete this item Permanently</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="deleteCustomer()">Delete</button>
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
            url: "{{ route('delete.intake-form') }}",
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
