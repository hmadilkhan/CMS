<!-- <div class=" fade"> -->
<div class="row g-3 gy-5 py-3 row-deck">
    <!-- <div class="row g-3 row-cols-3 row-cols-sm-3 row-cols-md-3 row-cols-lg-3 row-cols-xl-3 row-cols-xxl-3 row-deck py-1 pb-4"> -->
        @foreach($projects as $project)
        <!-- <div class="col"> -->
            <!-- <div class="card teacher-card">
                <div class="card-body  d-flex">
                    <div class="profile-av pe-xl-4 pe-md-2 pe-sm-4 pe-4 text-center w220">
                        <img src="assets/images/lg/avatar3.jpg" alt="" class="avatar xl rounded-circle img-thumbnail shadow-sm">
                        <div class="about-info d-flex align-items-center mt-1 justify-content-center flex-column">
                            <h6 class="mb-0 fw-bold d-block fs-6 mt-2">{{$project->department->name}}</h6>
                            <div class="btn-group mt-2" role="group" aria-label="Basic outlined example">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editproject"><i class="icofont-edit text-success"></i></button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#deleteproject"><i class="icofont-ui-delete text-danger"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="teacher-info border-start ps-xl-4 ps-md-3 ps-sm-4 ps-4 w-100">
                        <h6 class="mb-0 mt-2  fw-bold d-block fs-6">{{$project->subdepartment->name}}</h6>
                        <div class="video-setting-icon mt-3 pt-3 border-top">
                            <div class="row g-2 pt-4">
                                <div class="col-12 d-flex align-items-center">
                                    <div class="">
                                        <h3 class="mb-0 fw-bold  fs-6  mb-2">{{$project->project_name}}</h3>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="icofont-ui-calendar"></i>
                                        <span class="ms-2">Project Start Date</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <span class="ms-2 text-success">{{date("d M Y",strtotime($project->start_date))}}</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="icofont-ui-calendar"></i>
                                        <span class="ms-2">Project End Date</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <span class="ms-2 text-danger">{{date("d M Y",strtotime($project->end_date))}}</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="icofont-group-students "></i>
                                        <span class="ms-2">Assigned To</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <span class="ms-2">{{$project->assignedPerson[0]->employee->name}}</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="icofont-dollar "></i>
                                        <span class="ms-2">Budget</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <span class="ms-2">${{number_format($project->budget,0)}}</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="small fw-bold mb-0">Progress</h4>
                                        <span class="small {{($project->assignedPerson[0]->status == 'In-Progress' ? 'light-warning-bg' : 'light-success-bg')}}  p-1 rounded"><i class="icofont-ui-clock"></i> {{$project->assignedPerson[0]->status}}</span>
                                    </div>
                                </div>
                            </div>
                            <p>{{$project->description}}</p>
                        </div>
                        <div class="d-flex flex-wrap align-items-center ct-btn-set">
                            <a href="{{route('projects.show',$project->id)}}" class="btn btn-dark btn-sm mt-1"><i class="icofont-eye me-2 fs-6"></i>Details</a>
                        </div>
                    </div>
                </div>
            </div> -->
        <!-- </div> -->
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-sm-6">
            <div class="card">
                <div class="card-body">
                <div class="d-flex align-items-center justify-content-between profile-av pe-xl-4 pe-md-2 pe-sm-4 pe-4 w220">
                    <img src="assets/images/lg/avatar3.jpg" alt="" class="avatar xl rounded-circle img-thumbnail shadow-sm">
                </div>
                <h6 class="mb-0 fw-bold d-block fs-6 mt-2">{{$project->department->name}}</h6>
                <div class="d-flex align-items-center justify-content-between mt-5">
                    <div class="lesson_name">
                        <!-- <div class="project-block light-info-bg">
                            <i class="icofont-handshake-deal"></i>
                        </div>
                        <span class="large text-muted project_name fw-bold"> {{$project->department->name}} </span>
                        <h5 class="mb-0 fw-bold  fs-6  mb-2">{{$project->subdepartment->name}}</h5> -->
                    </div>
                    <!-- <div class="btn-group" role="group" aria-label="Basic outlined example">

                    </div> -->
                </div>

                <div class="row g-2 pt-4">
                    <div class="col-12 d-flex align-items-center">
                        <div class="">
                            <h3 class="mb-0 fw-bold  fs-6  mb-2">{{$project->project_name}}</h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-ui-calendar"></i>
                            <span class="ms-2">Project Start Date</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                           
                            <span class="ms-2 text-success">{{date("d M Y",strtotime($project->start_date))}}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-ui-calendar"></i>
                            <span class="ms-2">Project End Date</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-sand-clock"></i>
                            <span class="ms-2 text-danger">{{date("d M Y",strtotime($project->end_date))}}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-group-students "></i>
                            <span class="ms-2">Assigned To</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-ui-text-chat"></i>
                            <span class="ms-2">{{$project->assignedPerson[0]->employee->name}}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-dollar "></i>
                            <span class="ms-2">Budget</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-dollar"></i>
                            <span class="ms-2">${{number_format($project->budget,0)}}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="small fw-bold mb-0">Progress</h4>
                            <span class="small {{($project->assignedPerson[0]->status == 'In-Progress' ? 'light-warning-bg' : 'light-success-bg')}}  p-1 rounded"><i class="icofont-ui-clock"></i> {{$project->assignedPerson[0]->status}}</span>
                        </div>
                    </div>
                </div>

                <div class="dividers-block"></div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <!-- <p>{{$project->description}}</p> -->
                    <a href="{{route('projects.show',$project->id)}}" class="btn btn-dark btn-sm mt-1"><i class="icofont-eye me-2 fs-6"></i>Details</a>
                </div>

            </div>
        </div>
        </div>
    <!-- </div> -->
        @endforeach
    <!-- </div> -->
</div>
<!-- </div> -->
<!-- </div> -->