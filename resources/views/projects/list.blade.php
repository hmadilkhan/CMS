@extends("layouts.master")
@section('title', 'Project List')
@section('content')
@include('operations.partials.index-styles')
<div class="body d-flex py-lg-3 py-md-2">
    <div class="container-xxxl">
        <div class="operation-page-header">
            <div>
                <h1 class="operation-page-title"><i class="icofont-list me-2"></i>Project List</h1>
                <p class="operation-page-subtitle">Review project status, assignment, department, and customer address details.</p>
            </div>
        </div>
        <div class="operation-card mt-3">
            <div class="card-body">
                <table id="example1" class="table table-hover operation-table datatable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Code #</th>
                            <th>Project Name</th>
                            <th>Sales Partner</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Sub Department</th>
                            <th>Address</th>
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
                            <td>{{ $project->customer->street }}</td>
                            <td>
                                <span class="small  {{($project->status == 'In-Progress' ? 'light-danger-bg' : 'light-success-bg')}}  p-1 rounded"><i class="icofont-ui-clock"></i> {{$project->assignedPerson[0]->status}}</span>
                            </td>
                            @can("View Project")
                            <td class="text-center">
                                <a class="action-link" style="cursor: pointer;" data-toggle="tooltip" title="View" href="{{route('projects.show',$project->id)}}">
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
