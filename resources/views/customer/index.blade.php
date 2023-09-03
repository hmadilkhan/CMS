@extends("layouts.master")
@section('title', 'Customers')
@section('content')
<div class="body d-flex py-lg-3 py-md-2">
    <div class="container-xxl">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card border-0 mb-4 no-bg">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Customer</h3>
                        @can("Create Customer")
                        <a href="{{route('customers.create')}}" class="btn btn-dark me-1 mt-1 w-sm-100" id="openemployee"><i class="icofont-plus-circle me-2 fs-6"></i>Add Customer</a>
                        @endcan
                    </div>
                </div>
            </div>
        </div><!-- Row End -->
        <div class="card mt-3">
            <div class="card-header">
                <h4 class="card-title">Customer List</h3>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped datatable">
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
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteUser('{{ $customer->id }}')">
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
@endsection