@extends('layouts.website')
@section('title', 'Projects Details')
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

        .main-container {
            width: 650px;
            /* margin-left: auto;
                margin-right: auto; */
        }
    </style>
    <div id="mytask-layout" class="theme-indigo">
        <div class="card card-info">
            <div class="card-body">
                <div class="row clearfix">
                    <div class="col-md-12">
                        <div class="card border-0 mb-4 no-bg">
                            <div
                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">{{ $project->project_name }}</h3>
                                <a class="btn {{ $task->status == 'Hold' ? 'btn-warning' : ($task->status == 'Cancelled' ? 'btn-danger' : 'btn-dark') }} text-white me-1 mt-1 w-sm-100"
                                    id="openemployee">{{ $task->status }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Customer Details</h3>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input disabled value="{{ $project->customer->first_name }}" type="text" class="form-control"
                                id="first_name" name="first_name" placeholder="First Name">
                        </div>
                        <div class="col-sm-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input disabled value="{{ $project->customer->last_name }}" type="text" class="form-control"
                                id="last_name" name="last_name" placeholder="Last Name">
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
                            <input disabled value="{{ $project->customer->zipcode }}" type="text" class="form-control"
                                id="zipcode" name="zipcode" placeholder="Zip Code">
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
                            <input disabled value="{{ $project->customer->sold_date }}" type="date" class="form-control"
                                id="sold_date" name="sold_date" placeholder="Sold Date">
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
                    </hr>
                </div>


            </div>
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs px-3 border-bottom-0" role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#default"
                                    role="tab">Project Details</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#calls"
                                    role="tab">Call Details</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#emails"
                                    role="tab">Email Details</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="default" role="tabpanel">
                    <div class="card card-info mt-2">
                        <div class="card-body">
                            <div class="row clearfix">
                                <div class="col-md-12">
                                    <div class="card border-0 mb-4 no-bg">
                                        <div
                                            class="card-header py-3 px-0 d-sm-flex align-items-center text-center  justify-content-between border-bottom">
                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project Notes </h3>
                                        </div>
                                    </div>
                                </div>
                                @foreach ($departments as $department)
                                    <div class="col-md-12">
                                        <div class="card border-0 mb-4 no-bg">
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
                                        <div class="col-sm-12 mb-3">
                                            <label for="formFileMultipleoneone"
                                                class="form-label fw-bold flex-fill mb-2 mt-sm-0">Department Notes</label>
                                            @foreach ($filtered_collection as $value)
                                                @if ($value->notes != '')
                                                    <textarea class="form-control" disabled rows="3">{{ $value->notes }} {{ !empty($value->user) ? '( Added by ' . $value->user->name . ')' : '' }}</textarea>
                                                @endif
                                            @endforeach
                                        </div>
                                        @include('projects.partial.website-department-fields')
                                    </div>

                                    <div class="col-sm-6 mb-3">
                                        <label for="formFileMultipleoneone"
                                            class="form-label fw-bold flex-fill mb-2 mt-sm-0">Files</label>
                                        <ul class="list-group list-group-custom">
                                            @foreach ($files as $file)
                                                <!-- <label class="badge bg-light"> <a target="_blank" href="{{ asset('storage/projects/' . $file->filename) }}" class="ml-3">{{ $file->filename }}</a></label> -->
                                                <li class="list-group-item light-primary-bg">
                                                    @can('File Delete')
                                                        <i class="icofont-trash text-danger fs-6" style="cursor:pointer;"
                                                            onclick="deleteFile('{{ $file->id }}')">&nbsp;</i>
                                                    @endcan
                                                    <a target="_blank"
                                                        href="{{ asset('storage/projects/' . $file->filename) }}"
                                                        class="ml-3">{{ $file->filename }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>`
                    </div>
                </div>
                <div class="tab-pane fade show active" id="calls" role="tabpanel">
                    <div class="card card-info mt-2">
                        <div class="card-body">
                            <div class="row clearfix">
                                <div class="col-md-12">
                                    <div class="card border-0 mb-4 no-bg">
                                        <div
                                            class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between text-center border">
                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project Call Logs
                                            </h3>
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
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>`
                    </div>
                </div>
                <div class="tab-pane fade" id="emails" role="tabpanel">
                    <div class="container">
                        <div
                            class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Emails</h3>
                        </div>
                        <hr />
                        <div id="emailDiv"></div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
    @section('scripts')
        <script>
            showEmails("{{ $project->id }}");

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
        </script>
    @endsection
