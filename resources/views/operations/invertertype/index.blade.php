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
<style>
    .tag-editor {
        min-height: 48px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 10px;
        background: #fff;
    }

    .tag-editor:focus-within {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }

    .tag-editor input {
        border: none;
        outline: none;
        flex: 1 1 140px;
        min-width: 140px;
        padding: 6px 0;
    }

    .tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
    }

    .tag-chip button {
        border: 0;
        background: transparent;
        color: #fff;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        font-size: 14px;
    }
</style>
<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">Inverter Types</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form method="POST" action="{{ !empty($inverterType) ? route('inverter.type.update',$inverterType->id) :  route('inverter.type.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($inverterType) ? $inverterType->id : '' }}" />
            <div class="row g-3  mb-3 align-items-center">
                <div class="col-sm-4">
                    <!-- <div class="form-group"> -->
                    <label>Inverter Type Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Inverter Type Name" value="{{ !empty($inverterType) ? $inverterType->name : old('name') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>
                <div class="col-sm-4">
                    <label>Tags</label>
                    <div class="tag-editor" id="inverterTagsEditor">
                        <input type="text" id="inverterTagsInput" placeholder="Type tag and press Enter">
                    </div>
                    <input type="hidden" id="inverterTagsValue" name="tags"
                        value='@json(!empty($inverterType) ? $inverterType->tag_list : (old("tags") ? json_decode(old("tags"), true) : []))'>
                    <small class="text-muted">You can add multiple tags for one inverter type.</small>
                </div>
                <div class="col-4 mt-3">
                    <label></label>
                    <div class="form-group float-left ">
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
        <h4 class="card-title">Inverter Type List</h3>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Tags</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($inverterTypes as $key => $list)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $list->name }}</td>
                    <td>{{ count($list->tag_list) ? implode(', ', $list->tag_list) : '-' }}</td>
                    <td class="text-center">
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{ route('view-inverter-type',$list->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteModal('{{ $list->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
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
