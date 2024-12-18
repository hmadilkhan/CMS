@extends("layouts.master")
@section('title', 'Project List')
@section('content')
<div class="body d-flex py-lg-3 py-md-2">
    <div class="container-xxxl">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card border-0 mb-4 no-bg">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project List</h3>
                    </div>
                </div>
            </div>
        </div><!-- Row End -->
        <div class="card mt-3">
            <div class="card-header">
                
            </div>
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped datatable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Code #</th>
                            <th>Project Name</th>
                            <th>Sales Partner</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Sub Department</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($projects as $key => $project)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $project->code }}</td>
                            <td>{{ $project->project_name }}</td>
                            <td>{{ $project->customer->salespartner->name }}</td>
                            <td>{{ $project->assignedPerson[0]->employee->name }}</td>
                            <td>{{ $project->department->name }}</td>
                            <td>{{ $project->subdepartment->name }}</td>
                            <td>
                                <span class="small  {{($project->status == 'In-Progress' ? 'light-danger-bg' : 'light-success-bg')}}  p-1 rounded"><i class="icofont-ui-clock"></i> {{$project->assignedPerson[0]->status}}</span>
                            </td>
                            @can("View Project")
                            <td class="text-center">
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{route('projects.show',$project->id)}}">
                                    <i class="icofont-eye text-primary fs-4"></i></a>
                            </td>
                            @endcan
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div> <!-- ROW END -->
    </div>
</div>
@endsection