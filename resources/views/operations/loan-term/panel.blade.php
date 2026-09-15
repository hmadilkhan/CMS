{{--
    The Loan Terms screen's body - see operations/departments/panel.blade.php
    for what `$console` means here.
--}}
@php
    $console = $console ?? false;
    $screenUrl = fn ($id = null) => $console
        ? route('operations.console', array_filter(['section' => 'loan-terms', 'id' => $id]))
        : route('loan.term', array_filter(['id' => $id]));
@endphp
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
        <h1 class="operation-page-title">Loan Terms</h1>
        <p class="operation-page-subtitle">Maintain loan term options for finance products.</p>
    </div>
    <div class="operation-summary">
        <span>Total Records</span>
        <strong>{{ $loanTerms->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">{{ !empty($loanTerm) ? 'Update Loan Term' : 'Add Loan Term' }}</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form class="operation-form" method="POST" action="{{ !empty($loanTerm) ? route('loan.term.update',$loanTerm->id) :  route('loan.term.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($loanTerm) ? $loanTerm->id : '' }}" />
            @if ($console)
            <input type="hidden" name="ops_section" value="loan-terms" />
            @endif
            <div class="row g-3 align-items-start">
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label class="form-label">Finance Option</label>
                    <select class="form-select select2" aria-label="Default select Types" id="finance_option_id" name="finance_option_id" required>
                        <option value="">Select Finance</option>
                        @foreach ($financeOptions as $financeOption)
                        <option {{(!empty($loanTerm) && $loanTerm->finance_option_id == $financeOption->id ? 'selected' : '')}} value="{{ $financeOption->id }}">
                            {{ $financeOption->name }}
                        </option>
                        @endforeach
                    </select>
                    @error("finance_option_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label>Loan Term</label>
                    <input type="text" required class="form-control @error('year') is-invalid @enderror" id="year" name="year" placeholder="Enter Loan Term" value="{{ old('year', !empty($loanTerm) ? $loanTerm->year : '') }}">
                    @error('year')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i> Save</button>
                        <a href="{{ $screenUrl() }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Loan Terms</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-hover operation-table datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Finance Option</th>
                    <th>Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($loanTerms as $key => $list)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $list->finance->name ?? 'N/A' }}</td>
                    <td>{{ $list->year }}</td>
                    <td class="text-center">
                        <a class="action-link" data-toggle="tooltip" title="Edit" href="{{ $screenUrl($list->id) }}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a class="action-link ml-2" data-toggle="tooltip" title="Delete" onclick="deleteModal('{{ $list->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($loanTerms->isEmpty())
        <div class="empty-state">No loan terms have been added yet.</div>
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
                <button type="button" class="btn btn-danger color-fff" onclick="deleteInverterType()">Delete</button>
            </div>
        </div>
    </div>
</div>
{{-- The screen's own scripts, where the layout puts them: after jQuery. --}}
@section('scripts')
<script>
    function deleteModal(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show")
    }

    function deleteInverterType() {
        $.ajax({
            method: "POST",
            url: "{{ route('loan.term.delete') }}",
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
