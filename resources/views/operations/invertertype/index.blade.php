@extends("layouts.master")
@section('title', 'Inverter Types')
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
        <h1 class="operation-page-title">Inverter Types</h1>
        <p class="operation-page-subtitle">Maintain inverter type names and searchable tags.</p>
    </div>
    <div class="operation-summary">
        <span>Total Records</span>
        <strong>{{ $inverterTypes->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">{{ !empty($inverterType) ? 'Update Inverter Type' : 'Add Inverter Type' }}</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form class="operation-form" method="POST" action="{{ !empty($inverterType) ? route('inverter.type.update',$inverterType->id) :  route('inverter.type.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($inverterType) ? $inverterType->id : '' }}" />
            <div class="row g-3 align-items-start">
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <!-- <div class="form-group"> -->
                    <label>Inverter Type Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Inverter Type Name" value="{{ old('name', !empty($inverterType) ? $inverterType->name : '') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label>Inverter Efficiency Rating (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control @error('inverter_efficiency_rating') is-invalid @enderror" id="inverter_efficiency_rating" name="inverter_efficiency_rating" placeholder="0.00" value="{{ old('inverter_efficiency_rating', !empty($inverterType) ? $inverterType->inverter_efficiency_rating : '') }}">
                    @error('inverter_efficiency_rating')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label>Tags</label>
                    <div class="tag-editor" id="inverterTagsEditor">
                        <input type="text" id="inverterTagsInput" placeholder="Type tag and press Enter">
                    </div>
                    <input type="hidden" id="inverterTagsValue" name="tags"
                        value='@json(!empty($inverterType) ? $inverterType->tag_list : (old("tags") ? json_decode(old("tags"), true) : []))'>
                    <small class="text-muted">You can add multiple tags for one inverter type.</small>
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i> Save</button>
                        <a href="{{ route('view-inverter-type') }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Inverter Type List</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-hover operation-table datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Inverter Efficiency Rating (%)</th>
                    <th>Tags</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($inverterTypes as $key => $list)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $list->name }}</td>
                    <td class="cost-value">{{ $list->inverter_efficiency_rating !== null ? number_format($list->inverter_efficiency_rating, 2) : '-' }}</td>
                    <td>{{ count($list->tag_list) ? implode(', ', $list->tag_list) : '-' }}</td>
                    <td class="text-center">
                        <a class="action-link" data-toggle="tooltip" title="Edit" href="{{ route('view-inverter-type',$list->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a class="action-link ml-2" data-toggle="tooltip" title="Delete" onclick="deleteModal('{{ $list->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($inverterTypes->isEmpty())
        <div class="empty-state">No inverter types have been added yet.</div>
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
@endsection
@section("scripts")
<script>
    (function() {
        const editor = document.getElementById('inverterTagsEditor');
        const input = document.getElementById('inverterTagsInput');
        const hidden = document.getElementById('inverterTagsValue');

        if (!editor || !input || !hidden) {
            return;
        }

        let tags = [];

        function syncHidden() {
            hidden.value = JSON.stringify(tags);
        }

        function render() {
            editor.querySelectorAll('.tag-chip').forEach((chip) => chip.remove());

            tags.forEach((tag, index) => {
                const chip = document.createElement('span');
                chip.className = 'tag-chip';
                chip.innerHTML = `<span>${tag}</span><button type="button" aria-label="Remove tag">&times;</button>`;
                chip.querySelector('button').addEventListener('click', function() {
                    tags.splice(index, 1);
                    render();
                });
                editor.insertBefore(chip, input);
            });

            syncHidden();
        }

        function addTag(value) {
            const normalized = value.trim();
            if (!normalized || tags.includes(normalized)) {
                return;
            }

            tags.push(normalized);
            render();
        }

        input.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                addTag(input.value);
                input.value = '';
            }
        });

        editor.addEventListener('click', function() {
            input.focus();
        });

        try {
            const existing = JSON.parse(hidden.value || '[]');
            tags = Array.isArray(existing) ? existing.filter(Boolean) : [];
        } catch (error) {
            tags = [];
        }

        render();
    })();

    function deleteModal(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show")
    }

    function deleteInverterType() {
        $.ajax({
            method: "POST",
            url: "{{ route('inverter.type.delete') }}",
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
