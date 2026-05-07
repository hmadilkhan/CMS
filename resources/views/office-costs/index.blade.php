@extends("layouts.master")
@section('title', 'Office Costs')
@section('content')
@if(session('success'))
<div class="alert alert-primary" role="alert">
    {{session('success')}}
</div>
@endif
@if(session('error'))
<div class="alert alert-danger" role="alert">
    {{session('error')}}
</div>
@endif
@include('operations.partials.index-styles')
<div class="operation-page-header">
    <div>
        <h1 class="operation-page-title">Office Cost</h1>
        <p class="operation-page-subtitle">Maintain the current office cost value used in pricing calculations.</p>
    </div>
    <div class="operation-summary">
        <span>Total Records</span>
        <strong>{{ $costs->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">Create New Office Cost</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form class="operation-form" method="POST" action="{{ route('office-costs.store') }}">
            @csrf
            <div class="row g-3 align-items-start">
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <!-- <div class="form-group"> -->
                    <label>Office Costs</label>
                    <div class="input-group cost-input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" required class="form-control @error('cost') is-invalid @enderror" id="cost" name="cost" placeholder="0.00" value="{{ old('cost') }}">
                    </div>
                    @error('cost')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i> Save</button>
                        <a href="{{ route('office-costs.index') }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Office Cost List</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-hover operation-table datatable">
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
                    <td class="cost-value">$ {{ number_format($cost->cost,2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($costs->isEmpty())
        <div class="empty-state">No office cost has been added yet.</div>
        @endif
    </div>
</div>
@endsection
