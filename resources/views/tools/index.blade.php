@extends("layouts.master")
@section('title', 'Tools')
@section('content')
<div class="card card-info">
    <div class="card-header">
        <h5 class="card-title">Create New Tool</h5>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form method="POST" action="{{ !empty($tool) ? route('tools.update',$tool->id) :  route('tools.store') }}" enctype="multipart/form-data">
            @csrf
            @if(!empty($tool))
                @method("PUT")
            @endif
            <input type="hidden" name="id" value="{{ !empty($tool) ? $tool->id : '' }}" />
            <input type="hidden" name="previous_logo" value="{{ !empty($tool) ? $tool->file : '' }}" />
            <div class="row g-3  mb-3 align-items-center">
                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Department</label>
                        <select id="department_id" name="department_id" class="form-control select2 @error('department_id') is-invalid @enderror" style="width: 100%;">
                            <option value="">Select Department</option>
                            @foreach ($departments as $department)
                                <option {{!empty($tool) && $tool->department_id == $department->id ? 'selected' : ''}} value="{{ $department->id }}" {{ old('type') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-3 ">
                    <!-- <div class="form-group"> -->
                    <label>Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Name" value="{{ !empty($tool) ? $tool->name : old('name') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>
               
                <div class="col-sm-3">
                    <!-- <div class="form-group "> -->
                    <label>Description</label>
                    <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" placeholder="Enter description" value="{{ !empty($tool) ? $tool->description : old('description') }}">
                    @error('description')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>

                <div class="col-sm-4 mb-2">
                    <div class="form-group">
                        <label for="formFileMultipleoneone" class="form-label">Image</label>
                        <input class="form-control" type="file" id="formFileMultipleoneone" name="file">
                    </div>
                </div>
                <div class="col-sm-4 mb-3">
                    <label></label>
                    <div class="form-group float-left mt-3">
                        <button type="button" class="btn btn-danger float-right ml-2 text-white"><i class="icofont-ban"></i>
                            Cancel
                        </button>
                        <button type="submit" name="buttonstatus" class="btn btn-primary float-right " value="save"><i class="icofont-save"></i> Save
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
        <h4 class="card-title">Tools List</h3>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>File</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tools as $key => $tool)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $tool->department->name }}</td>
                    <td>{{ $tool->name }}</td>
                    <td>{{ $tool->description }}</td>
                    <td><a target="_blank" href="{{asset('storage/tools/'.$tool->file)}}">{{ $tool->file }}</a></td>
                    <td class="text-center">
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{ route('tools.index',$tool->id)  }}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteUser('{{ $tool->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection