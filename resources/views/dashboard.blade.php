@extends("layouts.master")
@section("content")
<div class="container-xxl">
    <div class="row g-3 mb-3 row-deck">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <div class="info-header">
                        <h6 class="mb-0 fw-bold ">{{((auth()->user()->getRoleNames()[0] != "Super Admin" and auth()->user()->getRoleNames()[0] != "Admin") ? 'Project Assigned to Me' : 'Projects Information')}}</h6>
                    </div>
                </div>
                <div class="card-body">
                    <table id="myProjectTable" class="table table-hover align-middle mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th>No.</th>
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
                            @foreach ($projects["projects"] as $key => $project)
                            <tr>
                                <td>{{ ++$key }}</td>
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
            </div>
        </div>
    </div><!-- Row End -->
</div>
@section('scripts')
<script src="{{asset('assets/bundles/apexcharts.bundle.js')}}"></script>
<script src="{{asset('page/index.js')}}"></script>
@endsection
<!-- Jquery Core Js -->
<!-- <script src="assets/bundles/libscripts.bundle.js"></script> -->
<!-- Plugin Js-->
<!-- <script src="{{asset('assets/bundles/libscripts.bundle.js')}}"></script>

<script src="{{asset('page/hr.js')}}"></script>
<script src="{{asset('page/index.js')}}'"></script> -->
@endsection