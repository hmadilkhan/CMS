@extends('layouts.master')
@section('title', 'Projects')
@section('content')
    <style>
        body {
            background-color: #f4f7f6;
            margin-top: 20px;
        }

        .card {
            background: #fff;
            transition: .5s;
            border: 0;
            margin-bottom: 30px;
            border-radius: .55rem;
            position: relative;
            width: 100%;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 10%);
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
    </style>
    <div class="card card-info">
        <div class="card-body">
            <div class="row clearfix">
                <div class="col-md-12">
                    <div class="card border-0 mb-4 no-bg">
                        <div
                            class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 text-center fs-10 text-uppercase">
                                {{ $project->project_name }}</h3>
                            <a class="btn {{ $task->status == 'Hold' ? 'btn-warning' : ($task->status == 'Cancelled' ? 'btn-danger' : 'btn-dark') }} text-white me-1 mt-1 w-sm-100"
                                id="openemployee">{{ $task->status }}</a>
                            <a href="{{ route('projects.index') }}" class="btn btn-dark me-1 mt-1 w-sm-100"
                                id="openemployee"><i class="icofont-arrow-left me-2 fs-6"></i>Back to List</a>
                        </div>
                    </div>
                    <div class="card border-0 mb-4 no-bg d-flex py-2 project-tab flex-wrap w-sm-100">
                        <ul class="nav nav-tabs tab-body-header rounded ms-3 prtab-set w-sm-100" role="tablist">
                            @foreach ($departments as $department)
                                @if ($department->id < $project->department_id)
                                    <li class="nav-item "><a class="nav-link active bg-success" data-bs-toggle="tab"
                                            role="tab">{{ $department->name }}</a></li>
                                @elseif($department->id == $project->department_id)
                                    <li class="nav-item "><a class="nav-link active " data-bs-toggle="tab"
                                            role="tab">{{ $department->name }}</a></li>
                                @else
                                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab"
                                            role="tab">{{ $department->name }}</a></li>
                                @endif
                            @endforeach
                        </ul>
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
                                role="tab">Action Menu</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#customer"
                                role="tab">Customer</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#salespartner"
                                role="tab">Sales Partner</a></li>
                        {{-- @if (auth()->user()->getRoleNames()[0] == 'Manager' or auth()->user()->getRoleNames()[0] == 'Sales Manager' or auth()->user()->getRoleNames()[0] == 'Admin' or auth()->user()->getRoleNames()[0] == 'Super Admin') --}}
                        @can('View Adder Details')
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#addersDiv"
                                    role="tab">Adders</a></li>
                        @endcan
                        @can('View Financial Details')
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#financial"
                                    role="tab">Financial</a></li>
                        @endcan
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#communication"
                                role="tab">Communication</a></li>
                        {{-- @endif --}}
                    </ul>
                </div>
            </div>
        </div>
    </div>


    <div class="tab-content">
        <div class="tab-pane fade show active" id="default" role="tabpanel">
            <div class="row clearfix mt-2">
                @if (auth()->user()->getRoleNames()[0] == 'Manager' or
                        auth()->user()->getRoleNames()[0] == 'Admin' or
                        auth()->user()->getRoleNames()[0] == 'Super Admin')
                    <div class="col-md-4">
                        <div class="card card-info">
                            <div class="card-body">
                                <div
                                    class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                    <h5 class=" fw-bold flex-fill mb-0 mt-sm-0">Assign Task</h5>
                                </div>
                                <form method="post" action="{{ route('projects.assign') }}">
                                    <div class="row g-3 mb-3">
                                        @csrf
                                        <input type="hidden" name="project_id" value="{{ $project->id }}">
                                        <input type="hidden" name="task_id" value="{{ $task->id }}">
                                        <input type="hidden" name="sub_department_id"
                                            value="{{ $task->sub_department_id }}">
                                        <input type="hidden" name="department_id" value="{{ $project->department_id }}">
                                        <div class="col-sm-12 mb-2">
                                            <label for="employee" class="form-label mt-2">Select Employee</label>
                                            <select class="form-select select2" aria-label="Default Select Employee"
                                                id="employee" name="employee">
                                                <option value="">Select Employee</option>
                                                @foreach ($employees as $employee)
                                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('loan_term_id')
                                                <div class="text-danger message mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-sm-12 mb-1">
                                            <label for="formFileMultipleoneone" class="form-label">Notes</label>
                                            <textarea class="form-control" rows="1" name="notes">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <div class="text-danger message mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-sm-12 mb-1">
                                            <button type="submit" class="btn btn-dark me-1 w-sm-100"><i
                                                    class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-info">
                            <div class="card-body">
                                <div
                                    class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                    <h5 class=" fw-bold flex-fill mb-0 mt-sm-0">Update Project Status</h5>
                                </div>
                                <form method="post" action="{{ route('projects.status') }}">
                                    <div class="row flex flex-column g-3 mb-3">
                                        @csrf
                                        <input type="hidden" name="project_id" value="{{ $project->id }}">
                                        <input type="hidden" name="taskid" value="{{ $task->id }}">

                                        <div class="col-sm-12 mb-1">
                                            <label for="employee" class="form-label mt-1">Select Status</label>
                                            <select class="form-select select2" aria-label="Default Select Status"
                                                id="status" name="status">
                                                <option value="">Select Status</option>
                                                <option {{ old('status') == 'In-Progress' ? 'selected' : '' }}
                                                    value="In-Progress">In-Progress</option>
                                                <option {{ old('status') == 'Hold' ? 'selected' : '' }} value="Hold">
                                                    Hold</option>
                                                <option {{ old('status') == 'Cancelled' ? 'selected' : '' }}
                                                    value="Cancelled">Cancelled</option>
                                            </select>
                                            @error('status')
                                                <div class="text-danger message mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-sm-12 mb-1">
                                            <label for="formFileMultipleoneone" class="form-label">Reason</label>
                                            <textarea class="form-control" rows="1" name="reason"></textarea>
                                            @error('reason')
                                                <div class="text-danger message mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-sm-12 mb-1">
                                            <button type="submit" class="btn btn-dark me-1 w-sm-100"><i
                                                    class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
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
                                            @foreach ($tools as $tool)
                                                <li class="list-group-item light-primary-bg"><a target="_blank"
                                                        href="{{ asset('storage/tools/' . $tool->file) }}"
                                                        class="ml-3">{{ $tool->name }}</a></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
            @can('Project Move')
                <div class="row">
                    <div class="col-md-4">
                        <div class="card card-info mt-2">
                            <div class="card-body">
                                <div class="row clearfix">
                                    <div class="col-md-12">
                                        <div class="card border-0 mb-4 no-bg">
                                            <div
                                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                                <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Call Logs</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <form id="call-log-form" method="post" action="{{ route('projects.call.logs') }}">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $project->id }}">
                                        <input type="hidden" name="taskid" value="{{ $task->id }}">

                                        <div class="row g-3 mb-3">
                                            <div class="col-md-12">
                                                <div class="col-sm-12 mb-3">
                                                    <label for="call_no" class="form-label">Select Call</label>
                                                    <select class="form-select select2" aria-label="Default Select call"
                                                        id="call_no" name="call_no">
                                                        <option value="">Select Call</option>
                                                        <option
                                                            {{ old('call_no') != '' && old('call_no') == 'yes' ? 'selected' : '' }}
                                                            value="1">Call No 1</option>
                                                        <option
                                                            {{ old('call_no') != '' && old('call_no') == 'no' ? 'selected' : '' }}
                                                            value="2">Call No 2</option>
                                                    </select>
                                                    <div id="call_no_message" class="text-danger message mt-2"></div>
                                                </div>
                                                <div class="col-sm-12 mb-3">
                                                    <label for="call_no_1" class="form-label">Did You Call ?</label>
                                                    <select class="form-select select2" aria-label="Default select options"
                                                        id="call_no_1" name="call_no_1">
                                                        <option value="">Select Options</option>
                                                        <option
                                                            {{ old('call_no_1') != '' && old('call_no_1') == 'yes' ? 'selected' : '' }}
                                                            value="yes">Yes</option>
                                                        <option
                                                            {{ old('call_no_1') != '' && old('call_no_1') == 'no' ? 'selected' : '' }}
                                                            value="no">Customer Not Responding</option>
                                                    </select>
                                                    <div id="call_no_1_message" class="text-danger message mt-2"></div>
                                                </div>
                                                <div class="col-sm-12 mb-3">
                                                    <label for="notes_1" class="form-label">Comments:</label>
                                                    <input type="text" class="form-control" id="notes_1" name="notes_1"
                                                        value="{{ old('notes_1') }}" />
                                                    <div id="notes_1_message" class="text-danger message mt-2"></div>
                                                </div>

                                            </div>
                                            {{-- <div class="col-md-6">
                                    <div class="col-sm-8 mb-3">
                                        <label for="call_no_2" class="form-label">Call No 2</label>
                                        <select class="form-select select2" aria-label="Default select Call No 1" id="call_no_2" name="call_no_2">
                                            <option value="">Select Call</option>
                                            <option {{old("call_no_2") != "" && old("call_no_2") == 'yes'  ? 'selected' : '' }} value="yes">Yes</option>
                                            <option {{old("call_no_2") != "" && old("call_no_2") == 'no'  ? 'selected' : '' }} value="no">Customer Not Responding</option>
                                        </select>
                                        <div id="call_no_2_message" class="text-danger message mt-2"></div>
                                    </div>
                                    <div class="col-sm-8 mb-3">
                                        <label for="notes_2" class="form-label">Comments:</label>
                                        <input type="text" class="form-control" id="notes_2" name="notes_2" value="{{old('notes_2')}}" />
                                        <div id="notes_2_message" class="text-danger message mt-2"></div>
                                    </div>
                                </div>  --}}
                                            <div class="col-sm-12 mb-3">
                                                <button type="button" class="btn btn-dark me-1 mt-1 w-sm-100"
                                                    id="saveCallLogs"><i
                                                        class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-info mt-2">
                            <div class="card-body">
                                <div class="row clearfix">
                                    <div class="col-md-12">
                                        <div class="card border-0 mb-4 no-bg">
                                            <div
                                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                                <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Notes</h3>
                                            </div>
                                        </div>
                                        <form method="post" action="{{ route('department.notes') }}">
                                            <div class="row flex flex-column g-3 mb-3">
                                                @csrf
                                                <input type="hidden" name="project_id" value="{{ $project->id }}">
                                                <input type="hidden" name="taskid" value="{{ $task->id }}">
                                                <input type="hidden" name="department_id"
                                                    value="{{ $project->department_id }}">
                                                <div class="col-sm-12 mb-1">
                                                    <label for="formFileMultipleoneone" class="form-label">Department
                                                        Notes</label>
                                                    <textarea class="form-control" rows="3" name="department_notes"></textarea>
                                                    @error('department_notes')
                                                        <div class="text-danger message mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-sm-12 mb-1">
                                                    <button type="submit" class="btn btn-dark me-1 w-sm-100"><i
                                                            class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-info mt-2">
                            <div class="card-body">
                                <div class="row clearfix">
                                    <div class="col-md-12">
                                        <div class="card border-0 mb-4 no-bg">
                                            <div
                                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                                <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Files</h3>
                                            </div>
                                        </div>
                                    </div>.
                                    <form id="files-form" method="post" action="{{ route('projects.files') }}"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $project->id }}">
                                        <input type="hidden" name="taskid" value="{{ $task->id }}">

                                        <div class="row g-3 mb-3">
                                            <div class="col-md-12 mb-3">
                                                <label for="formFileMultipleoneone" class="form-label"
                                                    id="requiredfiles">Required Files</label>
                                                <input class="form-control" type="file" id="file" name="file[]"
                                                    accept=".png,.jpg,.pdf" multiple>
                                                @error('file')
                                                    <div id="file_message" class="text-danger message mt-2">{{ $message }}
                                                    </div>
                                                @enderror
                                                <div id="file_message" class="text-danger message mt-2"></div>
                                            </div>
                                            <div class="col-sm-12 mb-3">
                                                <button type="button" class="btn btn-dark me-1 mt-1 w-sm-100"
                                                    id="saveFiles"><i class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card card-info mt-2">
                    <div class="card-body">
                        <div class="row clearfix">
                            <form id="form" method="post" action="{{ route('projects.move') }}"
                                enctype="multipart/form-data">
                                <input type="hidden" name="id" value="{{ $project->id }}">
                                <input type="hidden" name="taskid" value="{{ $task->id }}">
                                @csrf
                                <div class="col-md-12">
                                    <div class="card border-0 mb-4 no-bg">
                                        <div
                                            class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project </h3>
                                            @if ($errors->any())
                                                <div class="alert alert-danger">
                                                    <ul>
                                                        @foreach ($errors->all() as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row  mb-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label">Select Where to sent this project</label>
                                            <br />
                                            <label class="fancy-radio">
                                                <input type="radio" id="stage" name="stage" value="back">
                                                <span><i></i>Back</span>
                                            </label>
                                            <label class="fancy-radio">
                                                <input type="radio" id="stage" name="stage" value="forward">
                                                <span><i></i>Forward</span>
                                            </label>
                                            <p id="error-radio"></p>
                                        </div>
                                        @error('stage')
                                            <div class="text-danger message mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-sm-3 ">
                                        <label for="finance_option_id" class="form-label">Move Back
                                            {{ count($filesCount) }}</label>
                                        <select class="form-select select2" aria-label="Default select Move Back"
                                            id="back" name="back">
                                            <option value="">Select Move Back</option>
                                            @if (!empty($backdepartments))
                                                @foreach ($backdepartments as $mdepartment)
                                                    <option
                                                        {{ old('back') != '' && old('back') == $mdepartment->id ? 'selected' : '' }}
                                                        value="{{ $mdepartment->id }}">{{ $mdepartment->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('back')
                                            <div class="text-danger message mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    @if (!empty($forwarddepartments))
                                        @foreach ($forwarddepartments as $bdepartment)
                                            <input type="hidden" id="{{ $bdepartment['id'] }}_length"
                                                value="{{ $bdepartment['document_length'] }}" />
                                        @endforeach
                                    @endif
                                    <div class="col-sm-3 ">
                                        <label for="finance_option_id" class="form-label">Move Forward</label>
                                        <select class="form-select select2" aria-label="Default select Move Forward"
                                            id="forward" name="forward">
                                            <option value="">Select Move Forward</option>
                                            @if (!empty($forwarddepartments))
                                                @foreach ($forwarddepartments as $bdepartment)
                                                    <option
                                                        {{ old('forward') != '' && old('forward') == $bdepartment['id'] ? 'selected' : '' }}
                                                        value="{{ $bdepartment['id'] }}">{{ $bdepartment['name'] }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('forward')
                                            <div class="text-danger message mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-sm-3 ">
                                        <label for="finance_option_id" class="form-label">Sub Department</label>
                                        <select class="form-select select2" aria-label="Default select Sub Department"
                                            id="sub_department" name="sub_department">
                                            <option value="">Select Sub Department</option>
                                        </select>
                                        @error('sub_department')
                                            <div class="text-danger message mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    {{-- <div class="col-sm-3 mb-3">
                                <label for="formFileMultipleoneone" class="form-label" id="requiredfiles">Required Files</label>
                                <input class="form-control" type="file" id="file" name="file[]" accept=".png,.jpg,.pdf" multiple>
                                @error('file')
                                <div id="file_message" class="text-danger message mt-2">{{$message}}</div>
                                @enderror
                                <div id="file_message" class="text-danger message mt-2"></div>
                            </div> --}}

                                    <div id="fieldDiv" class="mt-2"></div>
                                    {{-- <div class="col-sm-12 mb-3 mt-2">
                                <label for="formFileMultipleoneone" class="form-label">Notes</label>
                                <textarea class="form-control" rows="3" name="notes"></textarea>
                                @error('notes')
                                <div class="text-danger message mt-2">{{$message}}</div>
                                @enderror
                            </div> --}}
                                    <div class="col-sm-12 mb-3 mt-3">
                                        <button type="button" class="btn btn-dark me-1 mt-1 w-sm-100" id="saveProject"><i
                                                class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
                <div class="card card-info mt-2">
                    <div class="card-body">
                        <div class="row clearfix">
                            <div class="col-md-12">
                                <div class="card border-0 mb-4 no-bg">
                                    <div
                                        class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project Notes </h3>
                                    </div>
                                </div>
                            </div>
                            @foreach ($departments as $department)
                                <div class="col-md-12">
                                    <div class="card border-0 mb-4 no-bg">
                                        <div
                                            class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom border-top">
                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">{{ $department->name }}</h3>
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $filtered_collection = $project->departmentnotes
                                        ->filter(function ($item) use ($department) {
                                            return $item->department_id == $department->id;
                                        })
                                        ->values();

                                    $logs = $project->logs
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

                                <input type="hidden" id="{{ $department->id }}_log_count" value="{{ count($logs) }}" />
                                <div class="col-sm-4 mb-3">
                                    <div class="col-sm-12 mb-3">
                                        <label for="formFileMultipleoneone"
                                            class="form-label fw-bold flex-fill mb-2 mt-sm-0">Department Notes</label>
                                        @foreach ($filtered_collection as $value)
                                            @if ($value->notes != '')
                                                <textarea class="form-control" disabled rows="3">{{ $value->notes }}</textarea>
                                            @endif
                                        @endforeach
                                    </div>
                                    @include('projects.partial.show-department-fields')
                                </div>
                                <div class="col-sm-4 mb-3">
                                    @foreach ($logs as $key => $log)
                                        <label for="formFileMultipleoneone"
                                            class="form-label fw-bold flex-fill mb-2 mt-sm-0">Call No {{ ++$key }} :
                                            {{ $log->call_no == 'no' ? 'Customer not responding' : $log->call_no }} </label>
                                        <textarea class="form-control" disabled rows="3">{{ $log->notes }}</textarea>
                                    @endforeach
                                </div>
                                <div class="col-sm-4 mb-3">
                                    <label for="formFileMultipleoneone"
                                        class="form-label fw-bold flex-fill mb-2 mt-sm-0">Files</label>
                                    <ul class="list-group list-group-custom">
                                        @foreach ($files as $file)
                                            <!-- <label class="badge bg-light"> <a target="_blank" href="{{ asset('storage/projects/' . $file->filename) }}" class="ml-3">{{ $file->filename }}</a></label> -->
                                            <li class="list-group-item light-primary-bg"><a target="_blank"
                                                    href="{{ asset('storage/projects/' . $file->filename) }}"
                                                    class="ml-3">{{ $file->filename }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="customer" role="tabpanel">
                <div class="card mt-1">
                    <div class="card-body">
                        <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Customer Details</h3>
                        </div>
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
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="salespartner" role="tabpanel">
                <div class="card mt-1">
                    <div class="card-body">
                        <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Sales Partner Details</h3>
                        </div>
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
            </div>
            <div class="tab-pane fade" id="addersDiv" role="tabpanel">
                <div class="card mt-1">
                    <div class="card-body">
                        <div class="card-header py-3 px-0 d-sm-flex align-items-center  border-bottom">
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0" data-bs-toggle="collapse"
                                data-bs-target="#adderTable" aria-expanded="false" aria-controls="adderTable">Adders Details
                            </h3>
                        </div>
                        <form method="post" action="{{ route('projects.adders') }}">
                            @csrf
                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                            <input type="hidden" name="customer_id" value="{{ $project->customer->id }}">
                            <input type="hidden" name="finance_option_id"
                                value="{{ $project->customer->finances->finance->id }}">
                            @if (auth()->user()->getRoleNames()[0] == 'Manager' or
                                    auth()->user()->getRoleNames()[0] == 'Admin' or
                                    auth()->user()->getRoleNames()[0] == 'Super Admin')
                                <div class="row g-4 mb-3">
                                    <div class="col-sm-3 mt-5">
                                        <div class="col-sm-12 mb-1">
                                            <label for="adders" class="form-label">Adders</label><br />
                                            <select style="width: 100%;" class="form-select select2"
                                                aria-label="Default select Adders" id="adders" name="adders">
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
                                            aria-label="Default select UOM" id="uom">
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
                                            placeholder="Adders Amount">
                                        @error('amount')
                                            <div class="text-danger message mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-sm-2 mt-5">
                                        <button type="button" id="btnAdder" class="btn btn-primary mt-4"><i
                                                class="icofont-save me-2 fs-6"></i>Add</button>
                                    </div>
                                </div>
                                </hr>
                            @endif
                            <table id="adderTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Adder</th>
                                        <th>Unit</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
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
                                            <td>{{ $adder->amount }}</td>
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
            </div>
            <div class="tab-pane fade" id="financial" role="tabpanel">
                <div class="card mt-1">
                    <div class="card-body">
                        {{-- @if (auth()->user()->getRoleNames()[0] == 'Manager' or auth()->user()->getRoleNames()[0] == 'Admin' or auth()->user()->getRoleNames()[0] == 'Super Admin') --}}
                        @can('View Financial Details')
                            <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                <h3 class=" fw-bold flex-fill mb-0 mt-sm-0" data-bs-toggle="collapse" data-bs-target="#finance"
                                    aria-expanded="false" aria-controls="finance">Financial Details</h3>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-sm-3 ">
                                    <label for="finance_option_id" class="form-label">Finance Option</label>
                                    <input type="text" class="form-control"
                                        value="{{ $project->customer->finances->finance->name }}">
                                </div>
                                @if ($project->customer->finances->finance->name != 'Cash')
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
                                        value="{{ $project->customer->finances->contract_amount }}" id="contract_amount"
                                        name="contract_amount">
                                </div>
                                <div class="col-sm-3 ">
                                    <label for="redline_costs" class="form-label">Redline Costs</label>
                                    <input type="text" class="form-control"
                                        value="{{ $project->customer->finances->redline_costs }}" id="redline_costs"
                                        name="redline_costs">
                                </div>
                                <div class="col-sm-3 ">
                                    <label for="adders" class="form-label">Adders</label>
                                    <input type="text" class="form-control"
                                        value="{{ $project->customer->finances->adders }}" id="adders_amount"
                                        name="adders_amount">
                                </div>
                                <div class="col-sm-3 ">
                                    <label for="commission" class="form-label">Commission</label>
                                    <input type="text" class="form-control"
                                        value="{{ $project->customer->finances->commission }}" id="commission"
                                        name="commission">
                                </div>
                                <div class="col-sm-3 ">
                                    <label for="dealer_fee" class="form-label">Dealer Fee</label>
                                    <input type="text" class="form-control"
                                        value="{{ $project->customer->finances->dealer_fee }}" id="dealer_fee"
                                        name="dealer_fee">
                                </div>
                                <div class="col-sm-3 ">
                                    <label for="dealer_fee_amount" class="form-label">Dealer Fee Amount</label>
                                    <input type="text" class="form-control"
                                        value="{{ $project->customer->finances->dealer_fee_amount }}" id="dealer_fee_amount"
                                        name="dealer_fee_amount">
                                </div>
                            </div>
                            <div class="col-sm-12 mb-3">
                                <button type="submit" class="btn btn-dark me-1 mt-1 w-sm-100"><i
                                        class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                            </div>
                            {{-- @endif --}}
                        @endcan
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="communication" role="tabpanel">
                <div class="card mt-1">
                    <div class="card-body">
                        <div class="container">
                            <div class="row clearfix">
                                <div class="col-lg-12">
                                    <div class="card">
                                        <div class="chat">
                                            <div class="chat-history">
                                                <ul class="m-b-0">
                                                    <li class="clearfix">
                                                        <div class="message other-message float-right"> Hi Aiden, how are you?
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
                                                            onclick="openEmailModal()"><i class="fa fa-send"></i></span></div>
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

        <div class="modal fade" id="createemail" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title  fw-bold" id="createprojectlLabel"> Send Email</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="emailform" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="deadline-form" id="empform">
                                <div class="row g-3 mb-3">
                                    <div class="mb-1">
                                        <label for="exampleFormControlInput877" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" name="subject"
                                            placeholder="Enter Subject" value="">
                                        <div id="name_message" class="text-danger message mt-2"></div>
                                    </div>
                                    <div class="mb-1">
                                        <label for="exampleFormControlInput877" class="form-label">Content</label>
                                        <textarea type="text" class="form-control" id="content" name="content" placeholder="Enter Subject"
                                            value=""></textarea>
                                        <div id="name_message" class="text-danger message mt-2"></div>
                                    </div>
                                    <div class="mb-1">
                                        <label for="exampleFormControlInput877" class="form-label">Attachments</label>
                                        <input type="file" multiple class="form-control" id="file" name="files">
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

        </div>
        </div><!-- Row End -->
        </div>
        </div>


    @endsection
@section('scripts')
    <script>
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

        function openEmailModal() {
            $("#createemail").modal("show");
        }

        $("#emailform").submit(function(e) {
            e.preventDefault();
            // alert();
            // var fd = new FormData();
            // var files = $('#file')[0].files[0];
            // fd.append('file', files);
            $.ajax({
                url: '{{route("send.email")}}',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                type: 'POST',
                data:new FormData(this),
                contentType: false,
                processData: false,
                cache: false,
                success: function(response) {
                    console.log(response);
                    // if (response != 0) {
                    //     $("#img").attr("src", response);
                    //     $(".preview img").show(); // Display image element
                    // } else {
                    //     alert('file not uploaded');
                    // }
                },
            });
        });

        $("#forward").change(function() {
            let totalCount = $("#" + $("#forward").val() + "_length").val();
            $("#requiredfiles").html(totalCount + " File Required");
            getSubDepartments($(this).val())
            // $(".additionalFields").css("display","none");
            // $(".fields_"+$(this).val()).css("display","block");
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
            $("#file_message").html('')
            let fileCount = $("[name='file[]']").prop("files").length;
            let stage = $('input[name="stage"]:checked').val()
            let totalCount = $("#" + $("#forward").val() + "_length")
                .val(); //"{{ $project->department->document_length }}";
            let alreadyUploaded = "{{ count($filesCount) }}";
            let currentproject = "{{ $project->department->id }}";
            let project = $("#forward").val();
            let logs = $("#" + ($("#forward").val() - 1) + "_log_count")
                .val() //"{{ count($project->department->logs) }}"
            $("#call_no_1_message").html("");
            $("#call_no_2_message").html("");
            $("#notes_1_message").html("");
            $("#notes_2_message").html("");
            // alert(($("#notes_1").val() == ""))
            // alert((stage == "forward" && alreadyUploaded == 0 && (project != $("#forward").val())))

            if (project != 1 && project != 8 && logs == 0 && stage == "forward") {
                // if ($("#call_no_1").val() == "") {
                //     $("#call_no_1").focus();
                //     $("#call_no_1_message").html("Please select the desired option");
                // } else if ($("#notes_1").val() == "") {
                //     $("#notes_1").focus();
                //     $("#notes_1_message").html("Please enter notes");
                // } else if ($("#call_no_2").val() == "") {
                //     $("#call_no_2").focus();
                //     $("#call_no_2_message").html("Please select the desired option");
                // } else if ($("#notes_2").val() == "") {
                //     $("#notes_2").focus();
                //     $("#notes_2_message").html("Please enter notes");
                // } else {

                if (stage == "forward" && (currentproject != $("#forward").val())) { //&& alreadyUploaded == 0
                    $("#form").submit();
                    /* THIS CODE IS COMMENTED BECAUSE OF THE REQUIREMENT THAT FILES NOT MANDATORY*/
                    // if (fileCount == totalCount) {
                    //     $("#file_message").html('')
                    //     $("#form").submit();
                    // } else {
                    //     $("#file_message").html("Please select total " + totalCount + " files");
                    // }
                } else {
                    $("#form").submit();
                }
                // }
            } else {
                if (stage == "forward" && (currentproject != $("#forward").val())) { //&& alreadyUploaded == 0
                    $("#form").submit();

                    /* THIS CODE IS COMMENTED BECAUSE OF THE REQUIREMENT THAT FILES NOT MANDATORY*/
                    // if (fileCount == totalCount) {
                    //     $("#file_message").html('')
                    //     $("#form").submit();
                    // } else {
                    //     $("#file_message").html("Please select total " + totalCount + " files");
                    // }
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
                // } else if ($("#call_no_2").val() == "") {
                //     $("#call_no_2").focus();
                //     $("#call_no_2_message").html("Please select the desired option");
                // } else if ($("#notes_2").val() == "") {
                //     $("#notes_2").focus();
                //     $("#notes_2_message").html("Please enter notes");
            } else {
                $("#call-log-form").submit();
            }
        })

        $("#saveFiles").click(function() {
            $("#file_message").html('')
            let fileCount = $("[name='file[]']").prop("files").length;
            let stage = $('input[name="stage"]:checked').val()
            // let totalCount = $("#" + $("#forward").val() + "_length").val(); //"{{ $project->department->document_length }}";
            let alreadyUploaded = "{{ count($filesCount) }}";
            // let currentproject = "{{ $project->department->id }}";
            // let project = $("#forward").val();
            // let logs = $("#{{ $project->id }}_log_count").val()
            let departmentLength = "{{ $project->department->document_length }}";
            let balance = departmentLength - alreadyUploaded;
            console.log("Balance", balance);
            if (fileCount <= balance) {
                // console.log("everthing is good");
                if (balance > 0) {
                    // console.log("File Count", fileCount);
                    // console.log("Already Uploaded", alreadyUploaded);
                    // console.log("Logs",logs);
                    // console.log("Department Length", "{{ $project->department->document_length }}");
                    $("#files-form").submit();
                }
            } else {
                // console.log("Only " + balance + " files can be selected");
                if (balance == 0) {
                    $("#file_message").html("All files are already selected. No files will be uploaded")
                } else {
                    $("#file_message").html("Only " + balance + " files can be selected")
                }
            }

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
                console.log($(this).children().eq(8).text() * 1);
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
            console.log(contractAmount, dealerFeeAmount);
            console.log(redlineFee, adders);
            let commission = contractAmount - dealerFeeAmount - redlineFee - adders;
            $("#commission").val(commission.toFixed(2));
        }

        $("#hoa").change(function() {
            alert()
            if ($(this).val() == "yes") {
                $("#hoa_select").css("display", "block")
            } else {
                $("#hoa_select").css("display", "none")
            }
        })
        $("#mpu_required").change(function() {
            if ($(this).val() == "yes") {
                $(".mpuselect").css("display", "block")
            } else {
                $(".mpuselect").css("display", "none")
            }
        })
    </script>
@endsection
