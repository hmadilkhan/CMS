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
@include('operations.partials.index-styles')
<div class="operation-page-header">
    <div>
        <h1 class="operation-page-title">Adder Types</h1>
        <p class="operation-page-subtitle">Maintain adder categories and their search tags.</p>
    </div>
    <div class="operation-summary">
        <span>Total Records</span>
        <strong>{{ $adders->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">{{ !empty($adder) ? 'Update Adder Type' : 'Add Adder Type' }}</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form class="operation-form" method="POST" action="{{ !empty($adder) ? route('adder.type.update',$adder->id) :  route('adder.type.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($adder) ? $adder->id : '' }}" />
            <div class="row g-3 align-items-start">
               
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <!-- <div class="form-group"> -->
                    <label>Adder Type Name</label>
                    <input type="text" required class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Name" value="{{ old('name', !empty($adder) ? $adder->name : '') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label>Tag</label>
                    <div class="tag-editor" id="adderTagEditor">
                        <input type="text" id="adderTagInput" placeholder="Type tag and press Enter">
                    </div>
                    <input type="hidden" id="adderTagValue" name="tag"
                        value="{{ !empty($adder) ? $adder->tag : old('tag') }}">
                    @error('tag')
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                    <small class="text-muted">Only one tag is allowed for each adder type.</small>
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i> Save</button>
                        <a href="{{ route('view.adder.types') }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Adder Types</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-hover operation-table datatable">
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
                        <a class="action-link" data-toggle="tooltip" title="Edit" href="{{ route('view.adder.types',$adderList->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a class="action-link ml-2" data-toggle="tooltip" title="Delete" onclick="deleteDealerModal('{{ $adderList->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($adders->isEmpty())
        <div class="empty-state">No adder types have been added yet.</div>
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
