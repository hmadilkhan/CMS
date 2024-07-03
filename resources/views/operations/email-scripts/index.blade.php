@extends('layouts.master')
@section('title', 'Email Scripts')
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
    <div class="card card-info">
        <div class="card-header">
            <h4 class="card-title">Create Email Script</h4>
        </div>
        <div class="card-body">
            <form method="POST"
                action="{{ !empty($script) ? route('email.scripts.update', $script->id) : route('email.scripts.store') }}">
                @csrf
                <input type="hidden" name="id" value="{{ !empty($script) ? $script->id : '' }}" />
                <div class="row g-3  mb-3 ">
                    <div class="col-sm-4">
                        <label class="form-label">Email Type</label></br>
                        <select class="form-select select2" aria-label="Default select Call" id="email_type_id"
                            name="email_type_id">
                            <option value="">Select Email Type</option>
                            @foreach ($emailTypes as $emailType)
                                <option {{ !empty($script) && $script->email_type_id == $emailType->id ? 'selected' : '' }}
                                    value="{{ $emailType->id }}">
                                    {{ $emailType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('email_type_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Department</label>
                        <select class="form-select select2" aria-label="Default select Call" id="department"
                            name="department">
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
                    <div class="col-sm-4 ">
                        <label>Extra Filter</label>
                        <input type="text" class="form-control @error('extra') is-invalid @enderror" id="extra"
                            name="extra" placeholder="Enter Extra Filter"
                            value="{{ !empty($script) ? $script->extra_filter : old('extra') }}">
                        @error('extra')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="row g-3  mb-3 ">
                    <div class="col-md-12 col-sm-12">
                        <label class="form-label">Script</label></br>
                        <textarea id="editor" name="script" class="form-control" rows="5">{!! !empty($script) ? $script->script : '' !!}</textarea>
                    </div>
                </div>
                <div class="row g-3  mb-3 ">
                    <div class="col-4 mt-3">
                        <label></label>
                        <div class="form-group float-left ">
                            <button type="button" class="btn btn-danger float-right ml-2 text-white"><i
                                    class="icofont-ban"></i>
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary float-right " value="save"><i
                                    class="icofont-save"></i> Save
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header">
            <h4 class="card-title">Email Scripts</h3>
        </div>
        <div class="card-body">
            <table id="example1" class="table table-bordered table-striped datatable">
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
                    @foreach ($emailScripts as $key => $emailScript)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $emailScript->email->name }}</td>
                            <td>{{ $emailScript->department->name }}</td>
                            <td>{{ $emailScript->script }}</td>
                            <td class="text-center">
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Edit"
                                    href="{{ route('email.scripts.list', $emailScript->id) }}">
                                    <i class="icofont-pencil text-warning"></i></a>
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2"
                                    onclick="deleteDealerModal('{{ $emailScript->id }}')">
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
                url: "{{ route('email.scripts.delete') }}",
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
