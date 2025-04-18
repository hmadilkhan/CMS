@extends("layouts.master")
@section('title', 'Labor Costs')
@section('content')
<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">Create New Labor Cost</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form method="POST" action="{{ route('labor-costs.store') }}">
            @csrf
            <div class="row g-3  mb-3 align-items-center">
                <div class="col-sm-4 ">
                    <!-- <div class="form-group"> -->
                    <label>Labor Cost</label>
                    <input type="text" class="form-control @error('cost') is-invalid @enderror" id="cost" name="cost" placeholder="Enter Cost">
                    @error('cost')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>
                <div class="col-4 mt-3">
                    <label></label>
                    <div class="form-group float-left mt-3">
                        <button type="button" class="btn btn-danger float-right ml-2 text-white"><i class="icofont-ban"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary float-right " value="save"><i class="icofont-save"></i> Save
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
    </div>
</div>
<div class="card mt-3">
    <div class="card-header">
        <h4 class="card-title">Labor Cost List</h3>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($costs as $key => $cost)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>$ {{ number_format($cost->cost,2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection