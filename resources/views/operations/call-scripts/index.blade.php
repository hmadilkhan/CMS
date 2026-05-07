@extends('layouts.master')
@section('title', 'Call Scripts')
@section('content')
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/42.0.0/ckeditor5.css">
    @if (session('success'))
        <div class="alert alert-primary" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif
    @include('operations.partials.index-styles')
    <div class="operation-page-header">
        <div>
            <h1 class="operation-page-title">Call Scripts</h1>
            <p class="operation-page-subtitle">Maintain department-specific scripts for call workflows.</p>
        </div>
        <div class="operation-summary">
            <span>Total Records</span>
            <strong>{{ $callScripts->count() }}</strong>
        </div>
    </div>
    <div class="card operation-card">
        <div class="card-header">
            <h4 class="card-title">Create Call Script</h4>
        </div>
        <div class="card-body">
            <form class="operation-form" method="POST"
                action="{{ !empty($script) ? route('call.scripts.update', $script->id) : route('call.scripts.store') }}">
                @csrf
                <input type="hidden" name="id" value="{{ !empty($script) ? $script->id : '' }}" />
                <div class="row g-3">
                    <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                        <label class="form-label">Call</label></br>
                        <select class="form-select select2" aria-label="Default select Call" id="call" name="call" required>
                            <option value="">Select Call</option>
                            @foreach ($calls as $call)
                                <option {{ !empty($script) && $script->call_id == $call->id ? 'selected' : '' }}
                                    value="{{ $call->id }}">
                                    {{ $call->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('call')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                        <label class="form-label">Department</label>
                        <select class="form-select select2" aria-label="Default select Call" id="department"
                            name="department" required>
                            <option value="">Select Department</option>
                            @foreach ($departments as $department)
                                <option {{ !empty($script) && $script->department_id == $department->id ? 'selected' : '' }}
                                    value="{{ $department->id }}">
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                        <label>Extra Filter</label>
                        <input type="text" class="form-control @error('extra') is-invalid @enderror" id="extra"
                            name="extra" placeholder="Enter Extra Filter"
                            value="{{ old('extra', !empty($script) ? $script->extra_filter : '') }}">
                        @error('extra')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-12 col-sm-12">
                        <label class="form-label">Script</label></br>
                        <textarea id="editor" name="script" class="form-control @error('script') is-invalid @enderror" rows="5">{!! old('script', !empty($script) ?  $script->script : '') !!}</textarea>
                        @error('script')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="operation-actions">
                            <button type="submit" class="btn btn-primary" value="save"><i
                                    class="icofont-save"></i> Save
                            </button>
                            <a href="{{ route('call.scripts.list') }}" class="btn btn-outline-secondary"><i
                                    class="icofont-ban"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card operation-card mt-3">
        <div class="card-header">
            <h4 class="card-title">Call Scripts</h4>
        </div>
        <div class="card-body">
            <table id="example1" class="table table-hover operation-table datatable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Type</th>
                        <th>Department</th>
                        <th>Script</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($callScripts as $key => $callScript)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $callScript->call->name ?? 'N/A' }}</td>
                            <td>{{ $callScript->department->name ?? 'N/A' }}</td>
                            <td>{!! $callScript->script !!}</td>
                            <td class="text-center">
                                <a class="action-link" data-toggle="tooltip" title="Edit"
                                    href="{{ route('call.scripts.list', $callScript->id) }}">
                                    <i class="icofont-pencil text-warning"></i></a>
                                <a class="action-link ml-2" data-toggle="tooltip" title="Delete"
                                    onclick="deleteDealerModal('{{ $callScript->id }}')">
                                    <i class="icofont-trash text-danger"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($callScripts->isEmpty())
            <div class="empty-state">No call scripts have been added yet.</div>
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
    <script type="importmap">
        {
                    "imports": {
                        "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/42.0.0/ckeditor5.js",
                        "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/42.0.0/"
                    }
                }
            </script>
    <script type="module">
        import {
            ClassicEditor,
            Essentials,
            Paragraph,
            Bold,
            Italic,
            Font
        } from 'ckeditor5';

        ClassicEditor
            .create(document.querySelector('#editor'), {
                plugins: [Essentials, Paragraph, Bold, Italic, Font],
                toolbar: [
                    'undo', 'redo', '|', 'bold', 'italic', '|',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor'
                ]
            })
            .then(editor => {
                window.editor = editor;
            })
            .catch(error => {
                // console.log(error);
            });
    </script>
@endsection
@section('scripts')
    <script>
        function deleteDealerModal(id) {
            $("#deleteId").val(id);
            $("#deleteproject").modal("show")
        }

        function deleteDealerFee() {
            $.ajax({
                method: "POST",
                url: "{{ route('call.scripts.delete') }}",
                data: {
                    _token: "{{ csrf_token() }}",
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
