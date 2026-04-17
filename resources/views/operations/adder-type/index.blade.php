@extends("layouts.master")
@section('title', 'Adders Type')
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
        background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
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
        <h4 class="card-title">Add Adder Types</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form method="POST" action="{{ !empty($adder) ? route('adder.type.update',$adder->id) :  route('adder.type.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($adder) ? $adder->id : '' }}" />
            <div class="row g-3  mb-3 align-items-center">
               
                <div class="col-sm-4 ">
                    <!-- <div class="form-group"> -->
                    <label>Adder Type Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Name" value="{{ !empty($adder) ? $adder->name : old('name') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>
                <div class="col-sm-4">
                    <label>Tag</label>
                    <div class="tag-editor" id="adderTagEditor">
                        <input type="text" id="adderTagInput" placeholder="Type tag and press Enter">
                    </div>
                    <input type="hidden" id="adderTagValue" name="tag"
                        value="{{ !empty($adder) ? $adder->tag : old('tag') }}">
                    <small class="text-muted">Only one tag is allowed for each adder type.</small>
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
        <h4 class="card-title">Adder Types</h3>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Tag</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($adders as $key => $adderList)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $adderList->name }}</td>
                    <td>{{ $adderList->tag ?? '-' }}</td>
                    <td class="text-center">
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{ route('view.adder.types',$adderList->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteDealerModal('{{ $adderList->id }}')">
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
                <button type="button" class="btn btn-danger color-fff" onclick="deleteDealerFee()">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section("scripts")
<script>
    (function() {
        const editor = document.getElementById('adderTagEditor');
        const input = document.getElementById('adderTagInput');
        const hidden = document.getElementById('adderTagValue');

        if (!editor || !input || !hidden) {
            return;
        }

        function syncHidden(value) {
            hidden.value = value;
        }

        function clearChip() {
            const chip = editor.querySelector('.tag-chip');
            if (chip) {
                chip.remove();
            }
        }

        function renderChip(value) {
            clearChip();

            if (!value) {
                syncHidden('');
                return;
            }

            const chip = document.createElement('span');
            chip.className = 'tag-chip';
            chip.innerHTML = `<span>${value}</span><button type="button" aria-label="Remove tag">&times;</button>`;
            chip.querySelector('button').addEventListener('click', function() {
                clearChip();
                syncHidden('');
            });
            editor.insertBefore(chip, input);
            syncHidden(value);
        }

        input.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                const value = input.value.trim();
                if (!value) {
                    return;
                }

                renderChip(value);
                input.value = '';
            }
        });

        editor.addEventListener('click', function() {
            input.focus();
        });

        renderChip(hidden.value.trim());
    })();

    function deleteDealerModal(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show")
    }

    function deleteDealerFee() {
        $.ajax({
            method: "POST",
            url: "{{ route('adder.type.delete') }}",
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
