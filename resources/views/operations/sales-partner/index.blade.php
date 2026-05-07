@extends("layouts.master")
@section('title', 'Sales Partners')
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
        <h1 class="operation-page-title">Sales Partners</h1>
        <p class="operation-page-subtitle">Maintain sales partner contact details and logo assets.</p>
    </div>
    <div class="operation-summary">
        <span>Total Records</span>
        <strong>{{ $partners->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">{{ !empty($partner) ? 'Update Sales Partner' : 'Add Sales Partner' }}</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form class="operation-form" method="POST" action="{{ !empty($partner) ? route('sales.partner.update',$partner->id) :  route('sales.partner.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($partner) ? $partner->id : '' }}" />
            <input type="hidden" name="previous_logo" value="{{ !empty($partner) ? $partner->image : '' }}" />
            <div class="row g-3 align-items-start">
               
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label>Sales Partner Name</label>
                    <input type="text" required class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Name" value="{{ old('name', !empty($partner) ? $partner->name : '') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label>Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Enter Email" value="{{ old('email', !empty($partner) ? $partner->email : '') }}">
                    @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label>Phone</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" placeholder="Enter Phone" value="{{ old('phone', !empty($partner) ? $partner->phone : '') }}">
                    @error('phone')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-12 mb-2">
                    <div class="form-group">
                        <label for="formFileMultipleoneone" class="form-label">Image</label>
                        <input class="form-control @error('file') is-invalid @enderror" type="file" id="formFileMultipleoneone" name="file" accept="image/*">
                        @error('file')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i> Save</button>
                        <a href="{{ route('sales.partner.types') }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Sales Partner List</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-hover operation-table datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($partners as $key => $partnerList)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td><img width="50" height="50" class="rounded" src="{{($partnerList->image != '' ? asset('storage/salespartners/'.$partnerList->image) : (asset('assets/images/profile_av.png')))}}"/></td>
                    <td>{{ $partnerList->name }}</td>
                    <td>{{ $partnerList->email }}</td>
                    <td>{{ $partnerList->phone }}</td>
                    <td class="text-center">
                        <a class="action-link" data-toggle="tooltip" title="Edit" href="{{ route('sales.partner.types',$partnerList->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a class="action-link ml-2" data-toggle="tooltip" title="Delete" onclick="deleteDealerModal('{{ $partnerList->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($partners->isEmpty())
        <div class="empty-state">No sales partners have been added yet.</div>
        @endif
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
                <button type="button" class="btn btn-danger color-fff" onclick="deleteDealerFee()">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section("scripts")
<script>
    function deleteDealerModal(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show")
    }

    function deleteDealerFee() {
        $.ajax({
            method: "POST",
            url: "{{ route('sales.partner.delete') }}",
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
