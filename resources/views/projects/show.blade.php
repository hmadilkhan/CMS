@extends('layouts.master')
@section('title', 'Projects')
@section('content')
    <style>
        body {
            /* background: linear-gradient(135deg, #d7d9da 0%, #e1dede 100%); */
            margin-top: 20px;
        }

        .card {
            background: #fff;
            transition: all 0.3s ease;
            border: 0;
            margin-bottom: 30px;
            border-radius: 16px;
            position: relative;
            width: 100%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        /* .card:hover:not(.modal-open .card) {
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
                transform: translateY(-2px);
            } */

        body.modal-open .card {
            pointer-events: none;
        }

        body.modal-open .modal .card {
            pointer-events: auto;
        }

        .chat-app .people-list {
            width: 280px;
            position: absolute;
            left: 0;
            top: 0;
            padding: 20px;
            z-index: 7
        }

        .chat-app .chat {
            margin-left: 280px;
            border-left: 1px solid #eaeaea
        }

        .people-list {
            -moz-transition: .5s;
            -o-transition: .5s;
            -webkit-transition: .5s;
            transition: .5s
        }

        .people-list .chat-list li {
            padding: 10px 15px;
            list-style: none;
            border-radius: 3px
        }

        .people-list .chat-list li:hover {
            background: #efefef;
            cursor: pointer
        }

        .people-list .chat-list li.active {
            background: #efefef
        }

        .people-list .chat-list li .name {
            font-size: 15px
        }

        .people-list .chat-list img {
            width: 45px;
            border-radius: 50%
        }

        .people-list img {
            float: left;
            border-radius: 50%
        }

        .people-list .about {
            float: left;
            padding-left: 8px
        }

        .people-list .status {
            color: #999;
            font-size: 13px
        }

        .chat .chat-header {
            padding: 15px 20px;
            border-bottom: 2px solid #f4f7f6
        }

        .chat .chat-header img {
            float: left;
            border-radius: 40px;
            width: 40px
        }

        .chat .chat-header .chat-about {
            float: left;
            padding-left: 10px
        }

        .chat .chat-history {
            padding: 20px;
            border-bottom: 2px solid #fff
        }

        .chat .chat-history ul {
            padding: 0
        }

        .chat .chat-history ul li {
            list-style: none;
            margin-bottom: 30px
        }

        .chat .chat-history ul li:last-child {
            margin-bottom: 0px
        }

        .chat .chat-history .message-data {
            margin-bottom: 15px
        }

        .chat .chat-history .message-data img {
            border-radius: 40px;
            width: 40px
        }

        .chat .chat-history .message-data-time {
            color: #434651;
            padding-left: 6px
        }

        .chat .chat-history .message {
            color: #444;
            padding: 18px 20px;
            line-height: 26px;
            font-size: 16px;
            border-radius: 7px;
            display: inline-block;
            position: relative
        }

        .chat .chat-history .message:after {
            bottom: 100%;
            left: 7%;
            border: solid transparent;
            content: " ";
            height: 0;
            width: 0;
            position: absolute;
            pointer-events: none;
            border-bottom-color: #fff;
            border-width: 10px;
            margin-left: -10px
        }

        .chat .chat-history .my-message {
            background: #efefef
        }

        .chat .chat-history .my-message:after {
            bottom: 100%;
            left: 30px;
            border: solid transparent;
            content: " ";
            height: 0;
            width: 0;
            position: absolute;
            pointer-events: none;
            border-bottom-color: #efefef;
            border-width: 10px;
            margin-left: -10px
        }

        .chat .chat-history .other-message {
            background: #e8f1f3;
            text-align: right
        }

        .chat .chat-history .other-message:after {
            border-bottom-color: #e8f1f3;
            left: 93%
        }

        .chat .chat-message {
            padding: 20px
        }

        .online,
        .offline,
        .me {
            margin-right: 2px;
            font-size: 8px;
            vertical-align: middle
        }

        .online {
            color: #86c541
        }

        .offline {
            color: #e47297
        }

        .me {
            color: #1d8ecd
        }

        .float-right {
            float: right
        }

        .clearfix:after {
            visibility: hidden;
            display: block;
            font-size: 0;
            content: " ";
            clear: both;
            height: 0
        }

        @media only screen and (max-width: 767px) {
            .chat-app .people-list {
                height: 465px;
                width: 100%;
                overflow-x: auto;
                background: #fff;
                left: -400px;
                display: none
            }

            .chat-app .people-list.open {
                left: 0
            }

            .chat-app .chat {
                margin: 0
            }

            .chat-app .chat .chat-header {
                border-radius: 0.55rem 0.55rem 0 0
            }

            .chat-app .chat-history {
                height: 300px;
                overflow-x: auto
            }
        }

        @media only screen and (min-width: 768px) and (max-width: 992px) {
            .chat-app .chat-list {
                height: 650px;
                overflow-x: auto
            }

            .chat-app .chat-history {
                height: 600px;
                overflow-x: auto
            }
        }

        @media only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape) and (-webkit-min-device-pixel-ratio: 1) {
            .chat-app .chat-list {
                height: 480px;
                overflow-x: auto
            }

            .chat-app .chat-history {
                height: calc(100vh - 350px);
                overflow-x: auto
            }
        }

        .main-container {
            width: 650px;
            /* margin-left: auto;
                                                                                                                                                                                            margin-right: auto; */
        }

        .tags-input {
            border: 1px solid #ced4da;
            padding: 5px;
            border-radius: 4px;
            display: flex;
            flex-wrap: wrap;
            cursor: text;
        }

        .tags-input input {
            border: none;
            outline: none;
            flex-grow: 1;
            min-width: 150px;
        }

        .tag {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
        }

        .tag i {
            margin-left: 5px;
            cursor: pointer;
        }

        .invalid-feedback {
            display: none;
            color: red;
        }

        .blink-dot {
            position: relative;
        }

        .blink-dot::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 10px;
            height: 10px;
            background-color: red;
            border-radius: 50%;
            animation: blinker 1s linear infinite;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.6);
        }

        @keyframes blinker {
            50% {
                opacity: 0;
            }
        }

        .card-header {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            color: white;
            border-radius: 16px 16px 0 0 !important;
            padding: 1.5rem;
            border: none;
        }

        .nav-tabs {
            border: none;
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            background: #f8f9fa;
            color: #2c3e50;
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            color: white;
        }

        .btn-dark {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-dark:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            color: white;
        }

        .table tbody tr {
            transition: all 0.3s;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }

        .nav-item.dropdown:hover .dropdown-menu {
            display: block;
            position: absolute;
            /* Optional for controlling positioning */
            top: 100%;
            /* Ensures the menu appears below the parent item */
            left: 0;
            /* Aligns the dropdown menu with the parent */
            z-index: 1000;
            /* Keeps the dropdown menu on top */
        }

        /* Premium Modal Styles */
        #assign-notes .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        #assign-notes .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        #assign-notes .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        #assign-notes .form-check-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/42.0.0/ckeditor5.css">
    <div class="card card-info">
        <div class="card-body">
            <div class="row clearfix">
                <div class="col-md-12">
                    @if ($alertStatus)
                        <div class="alert alert-{{ $alertClass }} alert-dismissible fade show" role="alert">
                            {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    <div class="card border-0 mb-4 no-bg">
                        <div
                            class="card-header py-3 px-0 d-sm-flex align-items-center me-1 mt-1 w-sm-100  justify-content-between border-bottom">
                            <div class="d-flex">
                                <h6 class="mb-0 fs-6  font-monospace fw-bold mt-sm-0 px-3 py-3 text-center">
                                    @if (empty($project->pto_approval_date))
                                        {{ now()->diffInDays(Carbon\Carbon::parse($project->customer->sold_date)) }}
                                    @else
                                        {{ Carbon\Carbon::parse($project->pto_approval_date)->diffInDays(Carbon\Carbon::parse($project->customer->sold_date)) }}
                                    @endif
                                </h6>
                                @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Manager']))
                                    <a class="me-1 mt-1 w-sm-100"><select class="form-select "
                                            aria-label="Default Select Status" id="employee" name="employee">
                                            <option value="">Select Employee</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                            @endforeach
                                        </select></a>
                                @endif
                            </div>
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 text-center fs-10 text-uppercase">
                                {{ $project->project_name }}
                            </h3>
                            @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Manager']))
                                <a class="me-1 mt-1 w-sm-100"><select class="form-select "
                                        aria-label="Default Select Status" id="status" name="status">
                                        <option value="">Select Status</option>
                                        <option {{ $task->status == 'In-Progress' ? 'selected' : '' }} value="In-Progress">
                                            In-Progress</option>
                                        <option {{ $task->status == 'Hold' ? 'selected' : '' }} value="Hold">
                                            Hold</option>
                                        <option {{ $task->status == 'Cancelled' ? 'selected' : '' }} value="Cancelled">
                                            Cancelled
                                        </option>
                                    </select></a>
                            @endif
                            <a href="{{ route('projects.index') }}" class="btn btn-dark me-1 mt-1 w-sm-100"
                                id="openemployee"><i class="icofont-arrow-left me-2 fs-6"></i>Back to List</a>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center align-items-center">
                        <nav class="navbar navbar-expand-lg ">
                            <div class="container-fluid">
                                <div class="collapse navbar-collapse">
                                    <ul class="nav nav-tabs tab-body-header rounded ms-3 prtab-set w-sm-100"
                                        style="overflow: visible !important;">
                                        @foreach ($departments as $department)
                                            @php
                                                if ($project->customer->is_adu == 0) {
                                                    # code...
                                                    $filtered_collection = $nextSubDepartments
                                                        ->filter(function ($item) use ($department) {
                                                            return $item->department_id == $department->id;
                                                        })
                                                        ->values();
                                                } else {
                                                    $filtered_collection = $nextSubDepartments
                                                        ->filter(function ($item) use ($department) {
                                                            return $item->department_id == $department->id &&
                                                                $item->name == 'New Construction';
                                                        })
                                                        ->values();
                                                }
                                            @endphp
                                            @if ($department->id < $project->department_id)
                                                <li class="nav-item dropdown bg-success">
                                                    <a class="nav-link dropdown-toggle  text-white" id="navbarDropdown"
                                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        {{ $department->name }}
                                                    </a>
                                                    @if (count($filtered_collection) > 0)
                                                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                            @foreach ($filtered_collection as $subdepartment)
                                                                <li><a onclick="moveProjectModal('{{ $project->id }}','{{ $task->id }}','{{ $department->id }}','{{ $subdepartment->id }}')"
                                                                        class="dropdown-item">{{ $subdepartment->name }}</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            @elseif($department->id == $project->department_id)
                                                <li class="nav-item dropdown bg-success">
                                                    <a class="nav-link dropdown-toggle active text-white"
                                                        id="navbarDropdown" role="button" data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                        {{ $department->name }}
                                                    </a>
                                                    @if (count($filtered_collection) > 0)
                                                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                            @foreach ($filtered_collection as $subdepartment)
                                                                <li><a onclick="moveProjectModal('{{ $project->id }}','{{ $task->id }}','{{ $department->id }}','{{ $subdepartment->id }}')"
                                                                        class="dropdown-item">{{ $subdepartment->name }}</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            @else
                                                <li class="nav-item dropdown">
                                                    <a class="nav-link dropdown-toggle " id="navbarDropdown" role="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        {{ $department->name }}
                                                    </a>
                                                    @if (count($filtered_collection) > 0)
                                                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                            @foreach ($filtered_collection as $subdepartment)
                                                                <li><a onclick="moveProjectModal('{{ $project->id }}','{{ $task->id }}','{{ $department->id }}','{{ $subdepartment->id }}')"
                                                                        class="dropdown-item">{{ $subdepartment->name }}</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            @endif
                                        @endforeach

                                    </ul>
                                </div>
                            </div>
                        </nav>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row clearfix mt-2">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs px-3 border-bottom-0" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#default"
                                role="tab">Project</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#customer"
                                role="tab">Customer</a></li>
                        @can('View Financial Details')
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#financial"
                                    role="tab">Financial</a></li>
                        @endcan
                        @can('View Tickets')
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tickets"
                                    role="tab">Tickets</a></li>
                        @endcan
                        <li class="nav-item"><a
                                class="nav-link {{ $project->viewed_emails_count > 0 ? 'blink-dot' : '' }}"
                                data-bs-toggle="tab" href="#communication" role="tab">Communication</a></li>
                        @can('Project History')
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#history"
                                    role="tab">Project
                                    History</a></li>
                        @endcan
                    </ul>
                </div>
            </div>
        </div>
    </div>


    <div class="tab-content">
        <div class="tab-pane fade show active" id="default" role="tabpanel">

            <ul class="nav nav-tabs px-3 border-bottom-0" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#actionmenu"
                        role="tab">Action Menu</a></li>
                @can('Department Tools')
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#departmenttools"
                            role="tab">Department Tools</a></li>
                @endcan
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="actionmenu" role="tabpanel">
                    <div class="card card-info mt-2">
                        <div class="card-body">
                            <div class="row clearfix">
                                <div class="col-md-12">
                                    <div class="card border-0 mb-4 no-bg">
                                        <div
                                            class="card-header py-3 px-0 d-sm-flex align-items-center bg-light text-center  justify-content-between border-bottom">
                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project Notes </h3>
                                        </div>
                                    </div>
                                </div>
                                @foreach ($departments as $department)
                                    <div class="col-md-12">
                                        <div class="card border-0 mb-4 bg-light text-center">
                                            <div
                                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom border-top">
                                                <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 px-2">{{ $department->name }}
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                    @php
                                        $filtered_collection = $project->departmentnotes
                                            ->filter(function ($item) use ($department) {
                                                return $item->department_id == $department->id;
                                            })
                                            ->values();

                                        $files = $project->files
                                            ->filter(function ($item) use ($department) {
                                                return $item->department_id == $department->id;
                                            })
                                            ->values();

                                    @endphp

                                    <div class="col-sm-6 mb-3">
                                        @livewire('project.notes-section', ['projectId' => $project->id, 'taskId' => $task->id, 'departmentId' => $department->id, 'projectDepartmentId' => $project->department_id, 'ghost' => $ghost,'viewSource' => 'crm'], key($project->id))
                                        @livewire('project.project-fields', ['project' => $project, 'taskId' => $task->id, 'departmentId' => $department->id, 'projectDepartmentId' => $project->department_id, 'ghost' => $ghost,'viewSource' => 'crm'], key($project->id))
                                    </div>

                                    <div class="col-sm-6 mb-3">
                                        @livewire('project.enhanced-files-section', ['projectId' => $project->id, 'taskId' => $task->id, 'departmentId' => $department->id, 'projectDepartmentId' => $project->department_id, 'ghost' => $ghost,'viewSource' => 'crm'], key('enhanced-' . $department->id))
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="departmenttools" role="tabpanel">
                    <div class=" mt-2">
                        <div class="card-body">
                            @can('Department Tools')
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="col-sm-12 py-3 px-5">
                                                <div class="card-header px-0 d-sm-flex align-items-center   border-bottom">
                                                    <h5 class=" fw-bold flex-fill mb-0 mt-sm-0">Department Tools</h5>
                                                </div>
                                                <div class="row flex flex-column g-3 mb-3">
                                                    <ul class="list-group list-group-custom">
                                                        @if (!empty($tools))
                                                            @foreach ($tools as $tool)
                                                                <li class="list-group-item light-primary-bg"><a
                                                                        target="_blank"
                                                                        href="{{ asset('storage/tools/' . $tool->file) }}"
                                                                        class="ml-3">{{ $tool->name }}</a></li>
                                                            @endforeach
                                                        @else
                                                            <div>No Tools found.</div>
                                                        @endcan
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="customer" role="tabpanel">
        <div class="card mt-1">
            <div class="card-header">
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Customer Details</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input disabled value="{{ $project->customer->first_name }}" type="text"
                            class="form-control" id="first_name" name="first_name" placeholder="First Name">
                    </div>
                    <div class="col-sm-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input disabled value="{{ $project->customer->last_name }}" type="text"
                            class="form-control" id="last_name" name="last_name" placeholder="Last Name">
                    </div>
                    <div class="col-sm-3">
                        <label for="street" class="form-label">Street</label>
                        <input disabled value="{{ $project->customer->street }}" type="text" class="form-control"
                            id="street" name="street" placeholder="Street">
                    </div>
                    <div class="col-sm-3">
                        <label for="city" class="form-label">City</label>
                        <input disabled value="{{ $project->customer->city }}" type="text" class="form-control"
                            id="city" name="city" placeholder="City">
                    </div>
                    <div class="col-sm-3">
                        <label for="state" class="form-label">State</label>
                        <input disabled value="{{ $project->customer->state }}" type="text" class="form-control"
                            id="state" name="state" placeholder="State">
                    </div>
                    <div class="col-sm-3">
                        <label for="zipcode" class="form-label">Zip Code</label>
                        <input disabled value="{{ $project->customer->zipcode }}" type="text"
                            class="form-control" id="zipcode" name="zipcode" placeholder="Zip Code">
                    </div>
                    <div class="col-sm-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input disabled value="{{ $project->customer->phone }}" type="text" class="form-control"
                            id="phone" name="phone" placeholder="phone">
                    </div>
                    <div class="col-sm-3">
                        <label for="email" class="form-label">Email</label>
                        <input disabled value="{{ $project->customer->email }}" type="text" class="form-control"
                            id="email" name="email" placeholder="Email">
                    </div>

                    <div class="col-sm-3">
                        <label for="sold_date" class="form-label">Sold Date</label>
                        <input disabled value="{{ $project->customer->sold_date }}" type="date"
                            class="form-control" id="sold_date" name="sold_date" placeholder="Sold Date">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Sales Partner</label>
                        <input disabled value="{{ $project->customer->salespartner->name }}" type="text"
                            class="form-control" />
                    </div>
                    <div class="col-sm-3">
                        <label for="code" class="form-label">Panel Qty</label>
                        <input disabled value="{{ $project->customer->panel_qty }}" type="text"
                            class="form-control" id="panel_qty" name="panel_qty" placeholder="Panel Qty">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Module Type</label>
                        <input disabled value="{{ $project->customer->module->name }}" type="text"
                            class="form-control" id="module_type_id" name="module_type_id"
                            placeholder="Module Type">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Inverter Type</label>
                        <input disabled value="{{ $project->customer->inverter->name }}" type="text"
                            class="form-control" id="inverter_type_id" name="inverter_type_id"
                            placeholder="Inverter Type">
                    </div>
                    <div class="col-sm-3">
                        <label for="module_qty" class="form-label">System Size</label>
                        <input disabled value="{{ $project->customer->module_value }}" type="text"
                            class="form-control" id="module_qty" name="module_qty" placeholder="System Size">
                    </div>
                    <div class="col-sm-3">
                        <label for="inverter_qty" class="form-label">Inverter Qty</label>
                        <input disabled value="{{ $project->customer->inverter_qty }}" type="text"
                            class="form-control" id="inverter_qty" name="inverter_qty" placeholder="Inverter Qty">
                    </div>
                    @if ($project->customer->loan_id)
                        <div class="col-sm-3">
                            <label for="inverter_qty" class="form-label">Loan Id</label>
                            <input disabled value="{{ $project->customer->loan_id }}" type="text"
                                class="form-control" placeholder="Loan Id">
                        </div>
                    @endif
                    @if ($project->customer->sold_production_value)
                        <div class="col-sm-3">
                            <label for="inverter_qty" class="form-label">Sold Production Value</label>
                            <input disabled value="{{ $project->customer->sold_production_value }}" type="text"
                                class="form-control" placeholder="Sold Production Value">
                        </div>
                    @endif
                    <div class="col-sm-3">
                            <label for="inverter_qty" class="form-label">Preferred Language</label>
                            <input disabled value="{{ $project->customer->preferred_language }}" type="text"
                                class="form-control" placeholder="Preferred Language">
                        </div>
                </div>
            </div>
        </div>
        <div class="card mt-1">
            <div class="card-header">
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Sales Partner Details</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3 mt-1">
                    <div
                        class="col-sm-3d-flex align-items-center justify-content-between profile-av pe-xl-4 pe-md-2 pe-sm-4 pe-4 w220">
                        <img src="{{ $project->customer->salespartner->image != '' ? asset('storage/users/' . $project->customer->salespartner->image) : asset('assets/images/profile_av.png') }}"
                            alt="" class="avatar xl rounded-circle img-thumbnail shadow-sm">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Sales Partner Name</label>
                        <input disabled value="{{ $project->customer->salespartner->name }}" type="text"
                            class="form-control" id="first_name" name="first_name" placeholder="First Name">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Email</label>
                        <input disabled value="{{ $project->customer->salespartner->email }}" type="text"
                            class="form-control" id="last_name" name="last_name" placeholder="Last Name">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Phone</label>
                        <input disabled value="{{ $project->customer->salespartner->phone }}" type="text"
                            class="form-control" id="street" name="street" placeholder="Street">
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-1">
            <div class="card-body">
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Sales Person</h3>
                </div>
                <div class="row g-3 mb-3 mt-1">
                    <div
                        class="col-sm-3d-flex align-items-center justify-content-between profile-av pe-xl-4 pe-md-2 pe-sm-4 pe-4 w220">
                        <img src="{{ !empty($project->salesPartnerUser) && $project->salesPartnerUser->image != '' ? asset('storage/users/' . $project->salesPartnerUser->image) : asset('assets/images/profile_av.png') }}"
                            alt="" class="avatar xl rounded-circle img-thumbnail shadow-sm">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Sales Person Name</label>
                        <input disabled value="{{ $project->salesPartnerUser->name ?? '-' }}" type="text"
                            class="form-control" id="first_name" name="first_name" placeholder="First Name">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Email</label>
                        <input disabled value="{{ $project->salesPartnerUser->email ?? '-' }}" type="text"
                            class="form-control" id="last_name" name="last_name" placeholder="Last Name">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Phone</label>
                        <input disabled value="{{ $project->salesPartnerUser->phone ?? '-' }}" type="text"
                            class="form-control" id="street" name="street" placeholder="Street">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="financial" role="tabpanel">
        <div class="card mt-1">
            <div class="card-body">
                @can('View Financial Details')
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 px-3" data-bs-toggle="collapse"
                            data-bs-target="#finance" aria-expanded="false" aria-controls="finance">Financial Details
                        </h3>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-3 ">
                            <label for="finance_option_id" class="form-label">Finance Option</label>
                            <input type="text" class="form-control"
                                value="{{ $project->customer->finances->finance->name }}">
                        </div>
                        @if (
                            $project->customer->finances->finance->name != 'Cash' &&
                                $project->customer->finances->finance->name != 'LightReach PPA')
                            <div class="col-sm-3  loandiv">
                                <label for="loan_term_id" class="form-label">Loan Term</label>
                                <input type="text" class="form-control"
                                    value="{{ !empty($project->customer->finances->term) ? $project->customer->finances->term->year : '' }}"
                                    id="loan_term_id" name="loan_term_id">
                            </div>
                            <div class="col-sm-3  loandiv">
                                <label for="loan_apr_id" class="form-label">Loan Apr</label>
                                <input type="text" class="form-control"
                                    value="{{ !empty($project->customer->finances->apr) ? $project->customer->finances->apr->apr : '' }}"
                                    id="loan_apr_id" name="loan_apr_id">
                            </div>
                        @endif
                        <div class="col-sm-3 ">
                            <label for="contract_amount" class="form-label">Contract Amount</label>
                            <input type="text" class="form-control"
                                value="$ {{ number_format($project->customer->finances->contract_amount, 2) }}"
                                id="contract_amount" name="contract_amount">
                        </div>
                        @php
                            $totalOverridePanelCost = $project->customer->panel_qty * $project->overwrite_panel_price;
                            $totalOverride = $totalOverridePanelCost + $project->overwrite_base_price;
                            $actualRedlineCost = $project->customer->finances->redline_costs - $totalOverride;
                            $totalCommission = $totalOverride + $project->customer->finances->commission;
                            // $project->customer->finances->redline_costs
                        @endphp
                        <div class="col-sm-3 ">
                            <label for="redline_costs" class="form-label">Redline Costs</label>
                            <input type="text" class="form-control"
                                value="$ {{ number_format($actualRedlineCost, 2) }}" id="redline_costs"
                                name="redline_costs">
                        </div>
                        <div class="col-sm-3 ">
                            <label for="adders" class="form-label">Adders</label>
                            <input type="text" class="form-control"
                                value="$ {{ number_format($project->customer->finances->adders, 2) }}" id="adders_amount"
                                name="adders_amount">
                        </div>
                        <div class="col-sm-3 ">
                            <label for="commission" class="form-label">Commission</label>
                            <input type="text" class="form-control"
                                value="$ {{ number_format($totalCommission, 2) }}" id="commission" name="commission">
                        </div>
                        @if (
                            $project->customer->finances->finance->name != 'Cash' &&
                                $project->customer->finances->finance->name != 'LightReach PPA')
                            <div class="col-sm-3 ">
                                <label for="dealer_fee" class="form-label">Dealer Fee</label>
                                <input type="text" class="form-control"
                                    value="{{ $project->customer->finances->dealer_fee }}" id="dealer_fee"
                                    name="dealer_fee">
                            </div>
                            <div class="col-sm-3 ">
                                <label for="dealer_fee_amount" class="form-label">Dealer Fee Amount</label>
                                <input type="text" class="form-control"
                                    value="$ {{ number_format($project->customer->finances->dealer_fee_amount, 2) }}"
                                    id="dealer_fee_amount" name="dealer_fee_amount">
                            </div>
                        @endif
                        @can('Holdback Amount')
                            <div class="col-sm-3 ">
                                <label for="commission" class="form-label">Holdback Amount</label>
                                <input type="text" class="form-control"
                                    value="$ {{ number_format($project->customer->finances->holdback_amount, 2) }}">
                            </div>
                        @endcan
                    </div>
                    {{-- <div class="col-sm-12 mb-3">
                        <button type="submit" class="btn btn-dark me-1 mt-1 w-sm-100"><i
                                class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                    </div> --}}
                    {{-- @endif --}}
                @endcan
            </div>
        </div>
        @can('View Adder Details')
            <div class="card mt-1">
                <div class="card-body">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 px-3" data-bs-toggle="collapse"
                            data-bs-target="#adderTable" aria-expanded="false" aria-controls="adderTable">Adders
                            Details
                        </h3>
                    </div>
                    <form method="post" action="{{ route('projects.adders') }}">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $project->id }}">
                        <input type="hidden" name="customer_id" value="{{ $project->customer->id }}">
                        <input type="hidden" name="finance_option_id"
                            value="{{ $project->customer->finances->finance->id }}">
                        @if (auth()->user()->hasAnyRole(['Manager', 'Admin', 'Super Admin']))
                            @if ($isAddersLocked)
                                <div class="alert alert-warning mb-3">
                                    <i class="icofont-lock"></i> Adders section is locked.
                                    @if (auth()->user()->hasAnyRole(['Manager', 'Admin', 'Super Admin']))
                                        <button type="button" class="btn btn-sm btn-warning"
                                            onclick="toggleAddersLock('unlocked')">Unlock</button>
                                    @endif
                                </div>
                            @endif
                            <div class="row g-4 mb-3" id="addersForm"
                                style="{{ $isAddersLocked ? 'pointer-events: none; opacity: 0.6;' : '' }}">
                                <div class="col-sm-3 mt-5">
                                    <div class="col-sm-12 mb-1">
                                        <label for="adders" class="form-label">Adders</label><br />
                                        <select style="width: 100%;" class="form-select select2"
                                            aria-label="Default select Adders" id="adders" name="adders"
                                            {{ $isAddersLocked ? 'disabled' : '' }}>
                                            <option value="">Select Adders</option>
                                            @foreach ($adders as $adder)
                                                <option value="{{ $adder->id }}">
                                                    {{ $adder->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-3 mt-5">
                                    <label for="uom" class="form-label">UOM</label><br />
                                    <select style="width: 100%;" class="form-control select2"
                                        aria-label="Default select UOM" id="uom"
                                        {{ $isAddersLocked ? 'disabled' : '' }}>
                                        <option value="">Select UOM</option>
                                        @foreach ($uoms as $uom)
                                            <option value="{{ $uom->id }}">
                                                {{ $uom->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('uom')
                                        <div class="text-danger message mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-2 mt-5">
                                    <label for="amount" class="form-label">Amount</label>
                                    <input type="text" class="form-control" id="amount" name="amount"
                                        placeholder="Adders Amount" {{ $isAddersLocked ? 'disabled' : '' }}>
                                    @error('amount')
                                        <div class="text-danger message mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-2 mt-5">
                                    <button type="button" id="btnAdder" class="btn btn-primary mt-4"
                                        {{ $isAddersLocked ? 'disabled' : '' }}><i
                                            class="icofont-save me-2 fs-6"></i>Add</button>
                                </div>
                            </div>
                            </hr>
                        @endif
                        <table id="adderTable" class="table table-bordered table-striped text-white">
                            <thead>
                                <tr>
                                    <th class="text-white">No.</th>
                                    <th class="text-white">Adder</th>
                                    <th class="text-white">Unit</th>
                                    <th class="text-white">Amount</th>
                                    <th class="text-white">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($project->customer->adders as $key => $adder)
                                    @php $index = ++$key; @endphp
                                    <tr id="row{{ $key }}">
                                        <input type="hidden" value="{{ $adder->adder_type_id }}" name="adders[]" />
                                        <!-- <input type="hidden" value="{{ $adder->adder_sub_type_id }}" name="subadders[]" /> -->
                                        <input type="hidden" value="{{ $adder->adder_unit_id }}" name="uom[]" />
                                        <input type="hidden" value="{{ $adder->amount }}" name="amount[]" />
                                        <td>{{ $index }}</td>
                                        <td>{{ $adder->type->name }}</td>
                                        <td>{{ $adder->unit->name }}</td>
                                        <td>$ {{ number_format($adder->amount, 2) }}</td>
                                        <td>
                                            <i style='cursor: pointer;' class='icofont-trash text-danger'
                                                onClick="deleteItem('{{ $index }}','{{ $adder->id }}')">
                                                Delete</i>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        @endcan
        @can('Pre and Post Project Cost')
            @livewire('project.project-cost', ['project' => $project], key($project->id))
        @endcan
        {{-- Insert AccountTransactions Livewire component here --}}
        @can('Account Transactions View')
            @livewire('account-transactions', ['project_id' => $project->id], key($project->id))
        @endcan
    </div>

    @include('projects.tickets-tab')

    <div class="tab-pane fade" id="communication" role="tabpanel">
        <div class="card mt-1">
            <div class="card-body">
                <ul class="nav nav-tabs px-3 border-bottom-0" role="tablist">
                    {{-- <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#calls"
                            role="tab">Calls</a></li> --}}
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#emails"
                            role="tab">Emails</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#acceptance"
                            role="tab">Acceptance</a></li>
                </ul>
                <div class="tab-content">
                    {{-- <div class="tab-pane fade show active" id="calls" role="tabpanel">
                        @if (!in_array('Sales Person', auth()->user()->getRoleNames()->toArray()))
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card card-info mt-2">
                                        <div class="card-body">
                                            <div class="row clearfix">
                                                <div class="col-md-12">
                                                    <div class="card border-0 mb-4 no-bg">
                                                        <div
                                                            class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 m-4">Call
                                                                Logs
                                                            </h3>
                                                        </div>
                                                    </div>
                                                </div>
                                                <form id="call-log-form" method="post"
                                                    action="{{ route('projects.call.logs') }}">
                                                    @csrf
                                                    <input type="hidden" name="id"
                                                        value="{{ $project->id }}">
                                                    <input type="hidden" name="taskid"
                                                        value="{{ $task->id }}">

                                                    <div class="row g-3 mb-3">
                                                        <div class="col-md-12">
                                                            <div class="col-sm-12 mb-3">
                                                                <label for="call_no" class="form-label">Select
                                                                    Call</label><br />
                                                                <select class=" form-control select2"
                                                                    aria-label="Default Select call" id="call_no"
                                                                    name="call_no">
                                                                    <option value="">Select Call</option>
                                                                    @foreach ($calls as $call)
                                                                        <option value="{{ $call->id }}">
                                                                            {{ $call->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <div id="call_no_message"
                                                                    class="text-danger message mt-2"></div>
                                                            </div>
                                                            <div class="col-sm-12 mb-3">
                                                                <label for="notes_1"
                                                                    class="form-label">Comments:</label>
                                                                <input type="text" class="form-control"
                                                                    id="notes_1" name="notes_1"
                                                                    value="{{ old('notes_1') }}" />
                                                                <div id="notes_1_message"
                                                                    class="text-danger message mt-2"></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-12 mb-3">
                                                            <button type="button"
                                                                class="btn btn-dark me-1 mt-1 w-sm-100"
                                                                id="saveCallLogs"><i
                                                                    class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6" id="call_script"
                                    style="font-size: 15px;text-align: justify;text-justify: inter-word;"></div>

                            </div>
                        @endif
                        <div class="card card-info mt-2">
                            <div class="card-body">
                                <div class="row clearfix">
                                    <div class="col-md-12">
                                        <div class="card border-0 mb-4 no-bg">
                                            <div
                                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between text-center border">
                                                <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project Call Logs </h3>
                                            </div>
                                        </div>
                                    </div>
                                    @foreach ($departments as $department)
                                        <div class="col-md-12">
                                            <div class="card border-0 mb-4 no-bg">
                                                <div style="background-color: #E5E4E2;"
                                                    class="card-header py-3 px-0 d-sm-flex align-items-center   justify-content-between border-bottom border-top">
                                                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 px-2">
                                                        {{ $department->name }}
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                        @php
                                            $logs = $project->logs
                                                ->filter(function ($item) use ($department) {
                                                    return $item->department_id == $department->id;
                                                })
                                                ->values();
                                        @endphp

                                        <input type="hidden" id="{{ $department->id }}_log_count"
                                            value="{{ count($logs) }}" />

                                        @foreach ($logs as $key => $log)
                                            <div class="col-sm-12 mb-3">
                                                <label for="formFileMultipleoneone"
                                                    class="form-label fw-bold flex-fill mb-2 mt-sm-0">
                                                    {{ $log->call->name }} :</label>
                                                <textarea class="form-control" disabled rows="3">{{ $log->notes }}</textarea>
                                                <label
                                                    class="float-right mb-4 fst-italic">{{ (!empty($log->user) ? $log->user->name : '') . ' on ' . date('m/d/Y H:i:s', strtotime($log->created_at)) }}</label>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div> --}}
                    <div class="tab-pane fade show active" id="emails" role="tabpanel">
                        <div class="container">
                            @if (!in_array('Sales Person', auth()->user()->getRoleNames()->toArray()))
                                <form id="emailform" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                                    <input type="hidden" name="customer_id" value="{{ $project->customer_id }}">
                                    <input type="hidden" name="department_id"
                                        value="{{ $project->department_id }}" />
                                    <input type="hidden" name="customer_email"
                                        value="{{ $project->customer->email }}" />
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card card-info mt-2">
                                                <div class="card-body">
                                                    <div class="row clearfix">
                                                        <div class="col-md-12">
                                                            <div class="card border-0 mb-4 no-bg">
                                                                <div
                                                                    class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                                                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 m-4">
                                                                        Emails
                                                                    </h3>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12 mb-3">
                                                                    <label for="email_no" class="form-label">Select
                                                                        Email</label><br />
                                                                    <select class=" form-control select2"
                                                                        style="width: 100%;"
                                                                        aria-label="Default Select call"
                                                                        id="email_no" name="email_no">
                                                                        <option value="">Select Email
                                                                        </option>
                                                                        @foreach ($emailTypes as $emailType)
                                                                            <option value="{{ $emailType->id }}">
                                                                                {{ $emailType->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <div id="email_no_message"
                                                                        class="text-danger message mt-2"></div>
                                                                </div>
                                                                <div class="col-md-12 mb-1">
                                                                    <div class="mb-3">
                                                                        <label for="ccEmails" class="form-label">CC
                                                                            Emails</label>
                                                                        <div class="tags-input" id="ccEmails">
                                                                        </div>
                                                                        <input type="hidden" name="ccEmails"
                                                                            id="ccEmailsHidden">
                                                                        <div class="invalid-feedback" id="emailError">
                                                                            Please enter valid email addresses
                                                                            separated
                                                                            by commas.</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-12 mb-1">
                                                                    <label for="exampleFormControlInput877"
                                                                        class="form-label">Subject</label>
                                                                    <input type="text" class="form-control"
                                                                        id="subject" name="subject"
                                                                        placeholder="Enter Subject" value="">
                                                                    <div id="name_message"
                                                                        class="text-danger message mt-2"></div>
                                                                </div>
                                                                <div class="mb-1">
                                                                    <label for="exampleFormControlInput877"
                                                                        class="form-label">Attachments</label>
                                                                    <input type="file" multiple
                                                                        class="form-control" id="image"
                                                                        name="images[]">
                                                                    <div id="name_message"
                                                                        class="text-danger message mt-2"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-12 mb-3">
                                                                <button id="btnEmail" type="submit"
                                                                    class="btn btn-dark me-1 mt-1 w-sm-100"><i
                                                                        class="icofont-arrow-left me-2 fs-6"></i>Send
                                                                    Email</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 main-container"
                                            style="font-size: 15px;text-align: justify;text-justify: inter-word;">
                                            <textarea id="editor" name="content" class="form-control" rows="5"></textarea>
                                        </div>
                                    </div>
                                </form>
                            @endif
                            <div
                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">

                                <a class="btn  text-white me-1 mt-1 w-sm-100" id="openemployee"></a>
                                <a class="btn btn-dark me-1 mt-1 w-sm-100" onclick="fetchEmails()"><i
                                        class="icofont-refresh me-2 fs-6"></i>Refresh</a>
                            </div>
                            <div id="emailDiv"></div>
                            {{-- <div class="row clearfix">
                                            <div class="col-lg-12">
                                                <div class="card">
                                                    <div class="chat">
                                                        <div class="chat-history">
                                                            <ul class="m-b-0">
                                                                <li class="clearfix">
                                                                    <div class="message other-message float-right"> Hi Aiden, how
                                                                        are you?
                                                                        How is the project coming along? </div>
                                                                </li>
                                                                <li class="clearfix">
                                                                    <div class="message-data">
                                                                        <span class="message-data-time">10:12 AM, Today</span>
                                                                    </div>
                                                                    <div class="message my-message">Are we meeting today?</div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="chat-message clearfix">
                                                            <div class="input-group mb-0">
                                                                <input type="text" class="form-control"
                                                                    placeholder="Enter text here...">
                                                                <div class="input-group-prepend"><span class="input-group-text"
                                                                        onclick="openEmailModal()"><i
                                                                            class="fa fa-send"></i></span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> --}}
                        </div>
                    </div>
                    <div class="tab-pane fade" id="acceptance" role="tabpanel">
                        <div class="card mt-1">
                            <div class="card-body">
                                @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Employee']))
                                    <div class="card shadow-sm border-0">
                                        <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                            <h5 class="mb-0"><i class="icofont-file-document me-2"></i>Project Acceptance Submission</h5>
                                        </div>
                                        <div class="card-body p-4">
                                            <form id="accept-form" method="post" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="project_id" value="{{ $project->id }}" />
                                                <input type="hidden" name="sales_partner_id"
                                                    value="{{ $project->customer->sales_partner_id }}" />
                                                <input type="hidden" name="mode" value="post" />
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <label for="file" class="form-label fw-bold">
                                                            <i class="icofont-upload-alt me-1"></i>Upload Design <span class="text-danger">*</span>
                                                        </label>
                                                        <input class="form-control form-control-lg" type="file" id="file" name="file"
                                                            accept=".png,.jpg,.pdf" multiple>
                                                        <small class="text-muted">Accepted formats: PNG, JPG, PDF</small>
                                                        @error('file')
                                                            <div id="file_message" class="text-danger message mt-2">
                                                                {{ $message }}
                                                            </div>
                                                        @enderror
                                                        <div id="file_message" class="text-danger message mt-2"></div>
                                                    </div>
                                                    
                                                    <div class="col-md-6 mb-4">
                                                        <label for="notes" class="form-label fw-bold">
                                                            <i class="icofont-ui-note me-1"></i>Notes <span class="text-muted">(Optional)</span>
                                                        </label>
                                                        <textarea class="form-control" id="notes" name="notes" rows="4" 
                                                            placeholder="Add any additional notes or comments..."></textarea>
                                                        <small class="text-muted">Optional field for additional information</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                                    <button type="submit" class="btn btn-lg px-5" 
                                                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;"
                                                        id="saveFiles">
                                                        <i class="icofont-paper-plane me-2"></i>Submit
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                                <div class="row" id="project-acceptance-view"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="history" role="tabpanel">

        <ul class="nav nav-tabs px-3 border-bottom-0" role="tablist">
            @can('Project Interaction')
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#interaction"
                        role="tab">Interaction</a></li>
            @endcan
            @can('Department Logs')
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#logs" role="tab">Department
                        Logs</a></li>
            @endcan
        </ul>
        <div class="tab-content">
            @can('Project Interaction')
                <div class="tab-pane fade show active" id="interaction" role="tabpanel">
                    <div class="card card-info mt-2">
                        <div class="card-body">
                            <div class="row clearfix">
                                <div class="col-md-12">
                                    <div class="card border-0 mb-4 no-bg">
                                        <div
                                            class="card-header py-3 px-0 d-sm-flex align-items-center bg-light text-center  justify-content-between border-bottom">
                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project Interaction </h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <table class="table">
                                                                <thead class="bg-light">
                                                                    <th>Date Time</th>
                                                                    <th>Description</th>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($interactions as $interaction)
                                                                        <tr>
                                                                            <td>{{ $interaction->created_at }}</td>
                                                                            <td>{{ $interaction->description ?? 'N/A' }}
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
            @can('Department Logs')
                <div class="tab-pane fade" id="logs" role="tabpanel">
                    <div class="card card-info mt-2">
                        <div class="card-body">
                            <div class="row clearfix">
                                <div class="col-md-12">
                                    <div class="card border-0 mb-4 no-bg">
                                        <div
                                            class="card-header py-3 px-0 d-sm-flex align-items-center bg-light text-center  justify-content-between border-bottom">
                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Department Logs </h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-7">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <table class="table">
                                                                <thead class="bg-light">
                                                                    <th>Department Name</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Exit Date</th>
                                                                    <th>Action By</th>
                                                                    <th>Total Duration</th>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($projectLogs as $log)
                                                                        @php
                                                                            if ($log->status == 'In-Progress') {
                                                                                $exitDate = date('Y-m-d H:i:s');
                                                                            } else {
                                                                                $exitDate = $log->updated_at;
                                                                            }
                                                                        @endphp
                                                                        <tr>
                                                                            <td>{{ $log->department->name }}</td>
                                                                            <td>{{ date('d M Y H:i:s', strtotime($log->created_at)) }}
                                                                            </td>
                                                                            <td>{{ $log->status != 'In-Progress' ? date('d M Y H:i:s', strtotime($log->updated_at)) : 'N/A' }}
                                                                            </td>
                                                                            <td>{{ $log->user->name ?? 'N/A' }}</td>
                                                                            <td>{{ max(1, \Carbon\Carbon::parse($log->created_at)->diffInDays(\Carbon\Carbon::parse($exitDate))) }}
                                                                                Days</td>
                                                                            {{-- max(1,\Carbon\Carbon::parse($log->created_at)->diffInDays(\Carbon\Carbon::parse($exitDate))) --}}
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <table class="table">
                                                                <thead class="bg-light">
                                                                    <th>Department Name</th>
                                                                    <th>Total Days</th>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($totalDaysOfDepartments as $departmentDays)
                                                                        <tr>
                                                                            <td>{{ $departmentDays['department'] ?? 'N/A' }}
                                                                            </td>
                                                                            <td>{{ $departmentDays['days'] ?? 'N/A' }}
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>

</div>


<div class="modal fade" id="createemail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title  fw-bold" id="createprojectlLabel"> Send Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="emailform1" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <input type="hidden" name="customer_id" value="{{ $project->customer_id }}">
                <input type="hidden" name="department_id" value="{{ $project->department_id }}" />
                <div class="modal-body">
                    <div class="deadline-form" id="empform">
                        <div class="row g-3 mb-3">
                            <div class="mb-1">
                                <label for="exampleFormControlInput877" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject1" name="subject"
                                    placeholder="Enter Subject" value="">
                                <div id="name_message" class="text-danger message mt-2"></div>
                            </div>
                            <div class="mb-1">
                                <label for="exampleFormControlInput877" class="form-label">Content</label>
                                <textarea type="text" class="form-control" id="content1" name="content" placeholder="Enter Subject"
                                    value=""></textarea>
                                <div id="name_message" class="text-danger message mt-2"></div>
                            </div>
                            <div class="mb-1">
                                <label for="exampleFormControlInput877" class="form-label">Attachments</label>
                                <input type="file" multiple class="form-control" id="image1" name="image[]">
                                <div id="name_message" class="text-danger message mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Send</button>
                    <button type="button" class="btn btn-danger text-white" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="assign-notes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content"
            style="border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold text-white" id="createprojectlLabel">
                    <i class="icofont-ui-note me-2"></i>Assign Notes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="assignNotes" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <input type="hidden" name="customer_id" value="{{ $project->customer_id }}">
                <input type="hidden" name="department_id" value="{{ $project->department_id }}" />
                <div class="modal-body" style="padding: 2rem;">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <label for="assignnotes" class="form-label fw-bold" style="color: #667eea;">
                                <i class="icofont-pencil-alt-2 me-2"></i>Assign Notes
                            </label>
                            <div class="position-relative">
                                <textarea class="form-control" id="assignnotes" name="assignnotes" rows="4"
                                    style="border-radius: 12px; border: 2px solid #e9ecef; padding: 1rem; transition: all 0.3s;"
                                    placeholder="Enter your notes here..."></textarea>
                            </div>
                        </div>

                        <div class="col-sm-12 mb-3">
                            <div class="form-check" style="padding-left: 2rem;">
                                <input class="form-check-input" type="checkbox" id="followUpCheckbox"
                                    name="follow_up"
                                    style="width: 20px; height: 20px; cursor: pointer; border-radius: 6px;">
                                <label class="form-check-label fw-bold" for="followUpCheckbox"
                                    style="color: #667eea; margin-left: 0.5rem; cursor: pointer;">
                                    <i class="icofont-calendar me-2"></i>Set Follow-up Date
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12 mb-3" id="followUpDateContainer" style="display: none;">
                            <label for="followUpDate" class="form-label fw-bold" style="color: #667eea;">
                                <i class="icofont-clock-time me-2"></i>Follow-up Date
                            </label>
                            <input type="date" class="form-control" id="followUpDate" name="follow_up_date"
                                style="border-radius: 12px; border: 2px solid #e9ecef; padding: 0.75rem; transition: all 0.3s;">
                        </div>


                        <div class="col-sm-12 mb-3">
                            <button type="submit" class="btn w-100"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                                <i class="icofont-save me-2"></i>Save Assignment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal  Delete Folder/ File-->
<div class="modal fade" id="deletefile" tabindex="-1" aria-hidden="true">
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
                <button type="button" class="btn btn-danger color-fff" onclick="deleteFileCall()">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dremoveadders" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title  fw-bold" id="dremovetaskLabel"> Remove Adder?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body justify-content-center flex-column d-flex">
                <i class="icofont-ui-rate-remove text-danger display-2 text-center mt-2"></i>
                <p class="mt-4 fs-5 text-center">This will be permanently remove from Adders</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger color-fff">Remove</button>
            </div>
        </div>
    </div>
</div>

<!-- PROJECT MOVE MODEL -->
<div class="modal fade" id="moveProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <input type="hidden" id="projectId" />
        <input type="hidden" id="taskId" />
        <input type="hidden" id="departmentId" />
        <input type="hidden" id="subDepartmentId" />
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title  fw-bold" id="deleteprojectLabel"> Move Project ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body justify-content-center flex-column d-flex">
                <i class="icofont-aim text-success display-2 text-center mt-2"></i>
                <p class="mt-4 fs-5 text-center">Are you sure you want to move the project ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success color-fff" onclick="moveProject()">Move</button>
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
<!-- A friendly reminder to run on a server, remove this during the integration. -->
{{-- <script>
    window.onload = function() {
        if (window.location.protocol === "file:") {
            alert("This sample requires an HTTP server. Please serve this file with a web server.");
        }
    };
</script> --}}
@endsection
@section('scripts')
<script>
    // Toggle follow-up date field visibility
    $('#followUpCheckbox').change(function() {
        if ($(this).is(':checked')) {
            $('#followUpDateContainer').show();
            $('#followUpDate').attr('required', true);
        } else {
            $('#followUpDateContainer').hide();
            $('#followUpDate').attr('required', false).val('');
        }
    });

    // Form validation
    $('#assignNotes').on('submit', function(e) {
        if ($('#followUpCheckbox').is(':checked') && !$('#followUpDate').val()) {
            e.preventDefault();
            alert('Please select a follow-up date.');
            return false;
        }
    });
    
    $(".additionalFields").css("display", "none");
    $("#back").prop("disabled", true)
    $("#forward").prop("disabled", true)
    $('input[type=radio][name=stage]').change(function() {
        if (this.value == "back") {
            $("#back").prop("disabled", false)
            $("#forward").prop("disabled", true)
        }
        if (this.value == "forward") {
            $("#forward").prop("disabled", false)
            $("#back").prop("disabled", true)
        }

    });
    $("#back").change(function() {
        getSubDepartments($(this).val())
    });

    function moveProjectModal(projectId, taskId, departmentId, subDepartmentId) {
        $('#projectId').val(projectId);
        $('#taskId').val(taskId);
        $('#departmentId').val(departmentId);
        $('#subDepartmentId').val(subDepartmentId);
        $("#moveProjectModal").modal("show");
    }

    function moveProject(projectId, taskId, departmentId, subDepartmentId) {
        $("#moveProjectModal").modal("show");
        $.ajax({
            url: "{{ route('move.project') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                projectId: $('#projectId').val(),
                taskId: $('#taskId').val(),
                departmentId: $('#departmentId').val(),
                subDepartmentId: $('#subDepartmentId').val(),
            },
            success: function(response) {
                // console.log(response);

                if (response.status == 200) {
                    Swal.fire(
                        'Sent!',
                        response.message,
                        'success'
                    )
                    $("#moveProjectModal").modal("hide");
                    location.reload();
                } else if (response.status = 422) {
                    Swal.fire(
                        'Failed!',
                        response.error,
                        'error'
                    )
                } else {
                    console.log(500);
                }
            },
            error: function(error) {
                if (error.responseJSON.status == 422) {
                    $("#moveProjectModal").modal("hide");
                    Swal.fire(
                        'Failed!',
                        error.responseJSON.error,
                        'error'
                    )
                }
                console.log(error);
            }
        });
    }

    function openEmailModal() {
        $("#createemail").modal("show");
    }
    setTimeout(function() {
        $("#emailform").submit(function(e) {
            e.preventDefault();
            $("#btnEmail").attr('disabled', true);
            $.ajax({
                url: "{{ route('send.email') }}",
                type: 'POST',
                data: new FormData(this),
                dataType: 'JSON',
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    if (response.status == 200) {
                        $("#subject").val('');
                        window.editor.setData('');
                        $("#email_no").val('').change();
                        $("#ccEmails").val('');
                        Swal.fire(
                            'Sent!',
                            response.message,
                            'success'
                        )
                        fetchEmails();
                        $("#btnEmail").removeAttr("disabled");
                    } else {
                        Swal.fire(
                            'Failed!',
                            response.message,
                            'error'
                        )
                        $("#btnEmail").removeAttr("disabled");
                    }
                },
                error: function(error) {
                    console.log(error);
                    $("#btnEmail").removeAttr("disabled");
                }
            });
        });
    }, 3000);

    $("#forward").change(function() {
        let totalCount = $("#" + $("#forward").val() + "_length").val();
        $("#requiredfiles").html(totalCount + " File Required");
        getSubDepartments($(this).val())
        getDepartmentsFields($(this).val())
    });

    function getDepartmentsFields(id) {
        if (id != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.departments.fields') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                    projectId: "{{ $project->id }}",
                },
                success: function(response) {
                    $("#fieldDiv").html();
                    $("#fieldDiv").html(response);
                },
                error: function(error) {
                    console.log(error);
                }
            })
        }
    }

    $("#status").change(function() {
        $.ajax({
            method: "POST",
            url: "{{ route('projects.status') }}",
            data: {
                _token: "{{ csrf_token() }}",
                status: $(this).val(),
                project_id: "{{ $project->id }}",
            },
            success: function(response) {
                if (response.status == 200) {
                    alert("Status Updated");
                } else {
                    alert("Some error occurred!");
                }
            },
            error: function(error) {
                console.log(error);
            }
        })
    });

    $("#employee").change(function() {
        if ($(this).val() != "") {
            $("#assign-notes").modal("show");
        }
    });

    // Follow-up checkbox toggle
    $("#followUpCheckbox").change(function() {
        if ($(this).is(':checked')) {
            $("#followUpDateContainer").slideDown(300);
            $("#followUpDate").attr('required', true);
        } else {
            $("#followUpDateContainer").slideUp(300);
            $("#followUpDate").attr('required', false);
            $("#followUpDate").val('');
        }
    });

    $("#assignNotes").submit(function(e) {
        e.preventDefault();

        var formData = {
            _token: "{{ csrf_token() }}",
            employee: $("#employee").val(),
            project_id: "{{ $project->id }}",
            task_id: "{{ $task->id }}",
            sub_department_id: "{{ $task->sub_department_id }}",
            department_id: "{{ $project->department_id }}",
            notes: $("#assignnotes").val(),
            follow_up: $("#followUpCheckbox").is(':checked') ? 1 : 0,
            follow_up_date: $("#followUpDate").val()
        };

        $.ajax({
            method: "POST",
            url: "{{ route('projects.assign') }}",
            data: formData,
            success: function(response) {
                if (response.status == 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Employee assigned successfully',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    $("#assign-notes").modal("hide");
                    $("#assignnotes").val('');
                    $("#followUpCheckbox").prop('checked', false);
                    $("#followUpDate").val('');
                    $("#followUpDateContainer").hide();
                    $("#employee").val('').change();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Some error occurred!',
                    });
                }
            },
            error: function(error) {
                console.log(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to assign employee',
                });
            }
        })
    })

    function getSubDepartments(id) {
        if (id != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.sub.departments') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                },
                dataType: 'json',
                success: function(response) {
                    $('#sub_department').empty();
                    $('#sub_department').append($('<option value="">Select Sub Department</soption>'));
                    $.each(response.subdepartments, function(i, value) {
                        $('#sub_department').append($('<option  value="' + value.id + '">' + value
                            .name + '</option>'));
                    });
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }
    }

    $("#saveProject").click(function(e) {
        $("#file_message").html('');
        let stage = $('input[name="stage"]:checked').val();
        let totalCount = $("#" + $("#forward").val() + "_length").val();
        let alreadyUploaded = "{{ count($filesCount) }}";
        let currentproject = "{{ $project->department->id }}";
        let project = $("#forward").val();
        let logs = $("#" + ($("#forward").val() - 1) + "_log_count").val();
        $("#call_no_1_message").html("");
        $("#call_no_2_message").html("");
        $("#notes_1_message").html("");
        $("#notes_2_message").html("");

        if (project != 1 && project != 8 && logs == 0 && stage == "forward") {
            if (stage == "forward" && (currentproject != $("#forward").val())) {
                $("#form").submit();
            } else {
                $("#form").submit();
            }
        } else {
            if (stage == "forward" && (currentproject != $("#forward").val())) {
                $("#form").submit();
            } else {
                $("#form").submit();
            }
        }

    });

    $("#saveCallLogs").click(function() {
        $("#call_no_message").html("");
        $("#call_no_1_message").html("");
        $("#notes_1_message").html("");
        if ($("#call_no").val() == "") {
            $("#call_no").focus();
            $("#call_no_message").html("Please Select Call No");
        } else if ($("#call_no_1").val() == "") {
            $("#call_no_1").focus();
            $("#call_no_1_message").html("Please select the desired option");
        } else if ($("#notes_1").val() == "") {
            $("#notes_1").focus();
            $("#notes_1_message").html("Please enter results of the call");
        } else {
            $("#call-log-form").submit();
        }
    })

    $("#saveFiles").click(function() {
        $("#files-form").submit();
    })

    $("#adders").change(function() {
        if ($(this).val() != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.adders') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    // subadder: $(this).val(),
                    adder: $(this).val(),
                },
                dataType: 'json',
                success: function(response) {
                    $("#uom").val(response.adders.adder_unit_id).change();
                    $("#amount").val(response.adders.price);
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }
    })

    $("#sub_type").change(function() {
        if ($(this).val() != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.adders') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    subadder: $(this).val(),
                    adder: $("#adders").val(),
                },
                dataType: 'json',
                success: function(response) {
                    $("#uom").val(response.adders.adder_unit_id).change();
                    $("#amount").val(response.adders.price);
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }
    })

    $("#btnAdder").click(function() {
        let rowLength = $('#adderTable tbody').find('tr').length;
        let adders_id = $("#adders").val();
        let subadder_id = $("#sub_type").val();
        let unit_id = $("#uom").val();
        let adders_name = $.trim($("#adders option:selected").text());
        let subadder_name = $.trim($("#sub_type option:selected").text());
        let unit_name = $.trim($("#uom option:selected").text());
        let amount = $("#amount").val();
        if (unit_id == 3) {
            let moduleQty = $('#module_qty').val();
            let panelQty = $('#panel_qty').val();
            amount = amount * moduleQty; //* panelQty;
        }
        if (unit_id == 5) {
            let panelQty = $('#panel_qty').val();
            amount = amount * panelQty; //* panelQty;
        }
        let result = checkExistence(adders_id, subadder_id, unit_id);
        if (result == false) {
            addAdderToDB("{{ $project->customer->id }}", adders_id, unit_id, amount);
            emptyControls();
        } else {
            alert("already added")
        }
    });


    function deleteItem(id, adderId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    url: "{{ route('adders.remove') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: adderId,
                        customer_id: "{{ $project->customer->id }}",
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
                            Swal.fire(
                                'Deleted!',
                                'Adder has been deleted.',
                                'success'
                            )
                            // $("#row" + id).remove();
                            populateAddersTable(response.adders);
                        }
                    },
                    error: function(error) {
                        Swal.fire(
                            'Error!',
                            'Some error occurred :)',
                            'error'
                        )
                    }
                });
            }
            if (result.dismiss) {
                Swal.fire(
                    'Cancelled!',
                    'Adder is safe :)',
                    'error'
                )
            }
        })
    }

    function addAdderToDB(customerId, adderTypeId, adderUnitId, amount) {
        $.ajax({
            url: "{{ route('adders.store') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                customer_id: customerId,
                adder_type_id: adderTypeId,
                adder_unit_id: adderUnitId,
                amount: amount,
            },
            dataType: 'json',
            success: function(response) {
                if (response.status == 200) {
                    Swal.fire(
                        'Added!',
                        'Adders has been added.',
                        'success'
                    )
                    populateAddersTable(response.adders)
                }
            },
            error: function(error) {
                Swal.fire(
                    'Error!',
                    'Some error occurred :)',
                    'error'
                )
            }
        });
    }

    function populateAddersTable(adders) {
        $("#adderTable > tbody").empty();
        $.each(adders, function(index, item) {
            let newRow = "<tr id='row" + (index + 1) + "'>" +
                '<input type="hidden" value="' + item.adder_type_id + '" name="adders[]" />' +
                '<input type="hidden" value="' + item.adder_sub_type_id + '" name="subadders[]" />' +
                '<input type="hidden" value="' + item.adder_unit_id + '" name="uom[]" />' +
                '<input type="hidden" value="' + item.amount + '" name="amount[]" />' +


                "<td>" + (index + 1) + "</td>" +
                "<td>" + item.type.name + "</td>" +
                "<td>" + item.unit.name + "</td>" +
                "<td>" + item.amount + "</td>" +

                "<td colspan='4'><i style='cursor: pointer;' class='icofont-trash text-danger' onClick=deleteItem(" +
                (index + 1) + "," + item.id + ")>&nbsp;&nbsp;Delete</i></td>" +
                "</tr>";

            $("#adderTable > tbody").append(newRow);
        });

    }

    function editItem(id, addersId, subAdderId, uomId, amount) {
        $("#adders").val(addersId).change();
        $("#sub_type").val(subAdderId).change()
        $("#uom").val(uomId).change();
        $("#amount").val(amount).change();

    }

    function checkExistence(firstval, secondval, thirdval) {
        let result = false;
        $("#adderTable tbody tr").each(function(index) {
            let first = $(this).children().eq(0).val();
            let second = $(this).children().eq(1).val();
            let third = $(this).children().eq(2).val();
            if (firstval == first && secondval == second && thirdval == third) {
                result = true;
            } else {
                result = false;
            }
        });
        return result;
    }

    function calculateAddersAmount() {
        let adders_amount = 0;
        $("#adderTable tbody tr").each(function(index) {
            // console.log($(this).children().eq(8).text() * 1);
            adders_amount += $(this).children().eq(8).text() * 1;
        });
        $("#adders_amount").val(adders_amount);
        calculateCommission();
    }

    function emptyControls() {
        $("#adders").val('').change();
        $("#sub_type").val('').change();
        $("#uom").val('').change();
        $("#amount").val('');
    }

    function calculateCommission() {
        let contractAmount = parseFloat($("#contract_amount").val());
        let dealerFeeAmount = parseFloat($("#dealer_fee_amount").val());
        let redlineFee = parseFloat($("#redline_costs").val());
        let adders = parseFloat($("#adders_amount").val());
        let commission = contractAmount - dealerFeeAmount - redlineFee - adders;
        $("#commission").val(commission.toFixed(2));
    }

    $("#hoa").change(function() {
        if ($(this).val() == "yes") {
            $("#hoa_select").css("display", "block")
        } else {
            $("#hoa_select").css("display", "none")
        }
    })
    // $("#mpu_required").change(function() {
    //     if ($(this).val() == "yes") {
    //         $(".mpuselect").css("display", "block")
    //     } else {
    //         $(".mpuselect").css("display", "none")
    //     }
    // })

    $("#call_no").change(function() {
        // alert($(this).val());
        $.ajax({
            url: "{{ route('projects.call.script') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                call: $(this).val(),
                department: "{{ $project->department_id }}",
                project: "{{ $project->id }}",
            },
            success: function(response) {
                // console.log(response);
                $("#call_script").empty();
                $("#call_script").html(response);
                let customer_name =
                    "{{ $project->customer->first_name . ' ' . $project->customer->last_name }}";
                let salespartner = "{{ $project->customer->salespartner->name }}";
                $('#call_script').html(function(i, old) {
                    return old
                        .replace("user_name", "<b>{{ auth()->user()->name }}</b>")
                        .replace("company_name", "<b>Solen Energy Co.</b>")
                        .replace("customer_name", "<b>" + customer_name + "</b>")
                        .replace("customer_name_1", "<b>" + customer_name + "</b>")
                        .replace("salespartner_name", "<b>" + salespartner + "</b>")
                        .replace("salespartner_name_1", "<b>" + salespartner + "</b>")
                    // let text = $(this).html();
                    // let customer_name = "{{ $project->customer->first_name . ' ' . $project->customer->last_name }}"
                    // console.log(customer_name);
                    // $(this).html(text.replace("user_name", "<b>{{ auth()->user()->name }}</b>"));
                    // $(this).html(text.replace("company_name", "<b>Solen Energy Co.</b>"));
                    // $(this).html(text.replace("customer_name", "<b>"+customer_name+"</b>"));
                });

            },
            error: function(error) {
                Swal.fire(
                    'Error!',
                    'Some error occurred :)',
                    'error'
                )
            }
        });
    })

    $("#email_no").change(function() {
        $.ajax({
            url: "{{ route('projects.email.script') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                emailType: $(this).val(),
                department: "{{ $project->department_id }}",
                project: "{{ $project->id }}",
            },
            success: function(response) {
                window.editor.setData(response);
                let customer_name =
                    "{{ $project->customer->first_name . ' ' . $project->customer->last_name }}";
                let salespartner = "{{ $project->customer->salespartner->name }}";
                let code = "{{ $project->code }}";
                let customerreplace = window.editor.getData();
                let customer_replaced_text = customerreplace.replace("customer_name", "<b>" +
                    customer_name + "</b>");
                window.editor.setData(customer_replaced_text);
                let customerreplace_1 = window.editor.getData();
                let customer_replaced_text_1 = customerreplace_1.replace("customer_name_1", "<b>" +
                    customer_name + "</b>");
                window.editor.setData(customer_replaced_text_1);
                let salespartnerName = window.editor.getData();
                let sales_partner_text = salespartnerName.replace("salespartner_name", "<b>" +
                    salespartner +
                    "</b>");
                window.editor.setData(sales_partner_text);
                let salespartnerName1 = window.editor.getData();
                let sales_partner_text1 = salespartnerName1.replace("salespartner_name_1", "<b>" +
                    salespartner +
                    "</b>");
                window.editor.setData(sales_partner_text1);
                let projectcode = window.editor.getData();
                
                
                projectcode = projectcode.replace("project_code", "<b>" +
                    code +
                    "</b>");
                window.editor.setData(projectcode);
            },
            error: function(error) {
                Swal.fire(
                    'Error!',
                    'Some error occurred :)',
                    'error'
                )
            }
        });
    })
    // fetchEmails()

    function fetchEmails() {
        $("#emailDiv").empty();
        let loadingDiv =
            '<div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">' +
            '<h3 class=" fw-bold flex-fill mb-0 mt-sm-0 text-center fs-10 text-uppercase">' +
            'Fetching Emails. Please Wait.........' +
            '</h3></div>';
        $("#emailDiv").append(loadingDiv);
        $.ajax({
            method: "POST",
            url: "{{ route('fetch.emails') }}",
            data: {
                _token: "{{ csrf_token() }}",
                project_id: "{{ $project->id }}",
                customer_id: "{{ $project->customer_id }}",
            },
            success: function(response) {

                if (response.status == 200) {
                    showEmails("{{ $project->id }}");
                }
            },
            error: function(error) {
                console.log(error);
            }
        })
    }

    function showEmails(projectId) {
        $.ajax({
            method: "POST",
            url: "{{ route('show.emails') }}",
            data: {
                _token: "{{ csrf_token() }}",
                project_id: projectId,
            },
            success: function(response) {
                $("#emailDiv").empty();
                $("#emailDiv").html(response);
            },
            error: function(error) {
                console.log(error);
            }
        })
    }

    function deleteFile(id) {
        $("#deleteId").val(id);
        $("#deletefile").modal("show")
    }

    function deleteFileCall() {
        // alert();
        $.ajax({
            method: "POST",
            url: "{{ route('delete.file') }}",
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
    document.addEventListener('DOMContentLoaded', function() {
        const tagsInput = document.querySelector('.tags-input');
        const input = document.createElement('input');
        const hiddenInput = document.getElementById('ccEmailsHidden');
        const form = document.getElementById('emailForm');
        const emailError = document.getElementById('emailError');

        tagsInput.appendChild(input);

        function createTag(email) {
            const tag = document.createElement('span');
            tag.classList.add('tag');
            tag.textContent = email;

            const closeIcon = document.createElement('i');
            closeIcon.classList.add('bi', 'bi-x');
            closeIcon.addEventListener('click', () => {
                tagsInput.removeChild(tag);
                updateHiddenInput();
            });

            tag.appendChild(closeIcon);
            tagsInput.insertBefore(tag, input);

            updateHiddenInput();
        }

        function updateHiddenInput() {
            const tags = document.querySelectorAll('.tag');
            const emails = Array.from(tags).map(tag => tag.textContent.trim());
            hiddenInput.value = emails.join(',');
        }

        function validateEmails(emails) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emails.every(email => emailPattern.test(email));
        }

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const email = input.value.trim();
                if (email && validateEmails([email])) {
                    createTag(email);
                    input.value = '';
                    emailError.style.display = 'none';
                } else {
                    emailError.style.display = 'block';
                }
            }
        });

        tagsInput.addEventListener('click', () => {
            input.focus();
        });

    });
    $('#accept-form').on('submit', function(e) {
        e.preventDefault();

        // Create a FormData object
        var formData = new FormData(this); // Automatically collects all form inputs, including files

        // Send the form data using jQuery AJAX
        $.ajax({
            url: "{{ route('project.accept.file') }}", // The URL where the request is sent
            type: 'POST',
            data: formData,
            contentType: false, // Tell jQuery not to set contentType
            processData: false, // Tell jQuery not to process the data (i.e., don't try to convert it into a string)
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                    'content') // Include the CSRF token from meta tag
            },
            success: function(response) {
                $("#project-acceptance-view").empty();
                $("#project-acceptance-view").html(response);
            },
            error: function(xhr) {
                console.error('Error uploading file: ' + xhr.responseText);
            }
        });
    });
    getAcceptanceForm();

    function getAcceptanceForm() {
        $.ajax({
            url: "{{ route('project.accept.file') }}", // The URL where the request is sent
            type: 'POST',
            data: {
                "_token": "{{ csrf_token() }}",
                project_id: '{{ $project->id }}',
                mode: 'view'
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                    'content') // Include the CSRF token from meta tag
            },
            success: function(response) {
                $("#project-acceptance-view").empty();
                $("#project-acceptance-view").html(response);
                // if (response.success) {
                //     console.log('File uploaded successfully.');
                // } else {
                //     console.error('Error: ' + response.message);
                // }
            },
            error: function(xhr) {
                console.error('Error uploading file: ' + xhr.responseText);
            }
        });
    }

    function acceptanceAction(mode, id, projectId) {
        let reason = $("#reason").val();
        $("#reason_message").html('');

        if (mode == 2 && reason == "") {
            $("#reason_message").html("Please Enter Reason");
        } else {
            $.ajax({
                url: "{{ route('action.project.acceptance') }}", // The URL where the request is sent
                type: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    id: id,
                    projectId: projectId,
                    mode: mode,
                    reason: reason,
                },
                success: function(response) {
                    if (response.status == 200) {
                        getAcceptanceForm();
                    }
                    if (response.status == 500) {
                        alert(response.message)
                    }
                },
                error: function(xhr) {
                    console.error('Error uploading file: ' + xhr.responseText);
                }
            });
        }
    }

    function toggleAddersLock(status) {
        $.ajax({
            url: "{{ route('toggle.adders.lock') }}",
            type: 'POST',
            data: {
                "_token": "{{ csrf_token() }}",
                project_id: {{ $project->id }},
                status: status
            },
            success: function(response) {
                if (response.status == 200) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                console.error('Error: ' + xhr.responseText);
            }
        });
    }

    (function() {
        'use strict';

        $(document).ready(function() {
            // Restore active tab on page load
            const activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                $('.nav-link[href="' + activeTab + '"]').tab('show');
                localStorage.removeItem('activeTab');
            }

            // Ticket form submission
            $('#ticketForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const form = this;
                const assignedTo = $('#assigned_to').val();
                const notes = $('#notes').val().trim();

                $(form).find('.is-invalid').removeClass('is-invalid');

                let isValid = true;

                if (!assignedTo) {
                    $('#assigned_to').addClass('is-invalid');
                    isValid = false;
                }

                if (!notes) {
                    $('#notes').addClass('is-invalid');
                    isValid = false;
                }

                if (!isValid) {
                    return false;
                }

                // Save active tab before reload
                localStorage.setItem('activeTab', '#tickets');

                $('#premiumLoader').css('display', 'flex');

                var formData = new FormData(form);

                $.ajax({
                    url: $(form).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $(form)[0].reset();
                        $('#ticketFilesList').html('');
                        setTimeout(function() {
                            $('#premiumLoader').hide();
                            location.reload();
                        }, 500);
                    },
                    error: function(error) {
                        $('#premiumLoader').hide();
                        localStorage.removeItem('activeTab');
                        alert('Error creating ticket. Please try again.');
                    }
                });

                return false;
            });

            $('#assigned_to, #notes').on('change input', function() {
                $(this).removeClass('is-invalid');
            });

            $('#updateTicketForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                // Save active tab before reload
                localStorage.setItem('activeTab', '#tickets');

                $('#premiumLoader').css('display', 'flex');
                $('.premium-loader-text').text('Updating Ticket...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        setTimeout(function() {
                            $('#updateTicketModal').modal('hide');
                            $('#premiumLoader').hide();
                            $('.premium-loader-text').text(
                            'Creating Ticket...');
                            location.reload();
                        }, 500);
                    },
                    error: function(error) {
                        $('#premiumLoader').hide();
                        $('.premium-loader-text').text('Creating Ticket...');
                        localStorage.removeItem('activeTab');
                        alert('Error updating ticket. Please try again.');
                    }
                });

                return false;
            });
        });

        window.updateTicket = function(ticketId) {
            $('#updateTicketModal').modal('show');
            $('#updateTicketForm').attr('action', '/service-tickets/' + ticketId);
        };

        window.viewTicketDetails = function(ticketId) {
            $('#ticketModal').modal('show');
            $('#ticketDetailsContent').html(
                '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                );

            $.ajax({
                url: '/service-tickets/' + ticketId + '/details',
                method: 'GET',
                success: function(response) {
                    $('#ticketDetailsContent').html(response);
                },
                error: function(error) {
                    $('#ticketDetailsContent').html(
                        '<div class="alert alert-danger">Error loading ticket details</div>');
                }
            });
        };
    })();
</script>
@endsection
